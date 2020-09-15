<?php

namespace App\Http\Controllers;

use Exception;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\ValidationData;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use GuzzleHttp\Client as Guzzle;

class Auth0Controller extends Controller
{
    // Guzzle client for issuing api requests.
    protected $http;

    // Token issued for API requests.
    protected $apiToken;

    /**
     * Setup our Guzzle client and get our M2M token for issuing requests.
     *
     * @return void
     */
    public function __construct()
    {
        // Create a Guzzle client for issuing our API requests to Auth0.
        $this->http = new Guzzle([
            'base_uri' => $this->apiConstructUri('api/v2'),
            'timeout'  => 2.0,
        ]);

        // Create and store an access token for our API requests.
        $token = $this->apiRequest(
            $this->apiConstructUri('oauth/token'),
            [
                'method' => 'POST',
                'form' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => env('AUTH0_CLIENTID'),
                    'client_secret' => env('AUTH0_SECRET'),
                    'audience' => env('AUTHO_AUDIENCE')
                ]
            ]
        );

        // Check if we received an access token.
        if ($token && isset($token['access_token'])) {
            $this->apiToken = $token['access_token'];
            return;
        }

        abort(403, 'Unauthorized action.');
    }

    /**
     * Construct a URI using the AUTH0_DOMAIN configured.
     *
     * @return string
     */
    private function apiConstructUri($endpoint)
    {
        return 'https://' . join('/', [env('AUTH0_DOMAIN'), $endpoint]) . '/';
    }

    /**
     * Build and send an API request to Auth0's servers.
     * Verify the JSON response and transform into a PHP array.
     *
     * @return array
     */
    public function apiRequest($endpoint, $options = [])
    {
        // Build our API request payload.
        $request = [
            'headers' => $options['headers'] ?? [],
            'form_params' => $options['form'] ?? [],
            'query' => $options['query'] ?? [],
            'body' => $options['body'] ?? ''
        ];

        // If we have a Bearer token, attach that to the request to authorize the call.
        if ($bearer = ($options['token'] ?? $this->apiToken)) {
            $request['headers']['Authorization'] = 'Bearer ' . $bearer;
        }

        // Issue the API request.
        $response = $this->http->request(($options['method'] ?? 'GET'), $endpoint, array_filter($request));

        // Was the call successful?
        if ($response->getStatusCode() === 200) {
            // Was the response in valid JSON?
            if (strpos($response->getHeader('Content-Type')[0], 'application/json') !== false) {
                // Decode and return an object representing the response.
                return json_decode((string)$response->getBody()->getContents(), true);
            }
        }

        // Encountered an invalid response, abort.
        return [];
    }

    /**
     * Return an array of users matching the query from the Auth0 Management API.
     *
     * @return array
     */
    public function getUsers($options = [])
    {
        // Possible filtering options we can pass to Auth0's /users endpoint.
        // See: https://auth0.com/docs/api/management/v2#!/Users/get_users
        $query = array_filter([
            'page' => $options['page'] ?? 0,
            'per_page' => $options['per_page'] ?? 5,
            'include_totals' => $options['include_totals'] ?? false,
            'sort' => $options['sort'] ?? 'username:1',
            'fields' => $options['fields'] ?? null,
            'include_fields' => $options['include_fields'] ?? null,
            'q' => $options['q'] ?? null
        ]);

        // Issue the request and return an object representing the results.
        return $this->apiRequest('users', ['query' => $query]);
    }

    /**
     * Return an array representing a specific user from the Auth0 Management API.
     *
     * @return array
     */
    public function getUser($id, $options = [])
    {
        // Possible filtering options we can pass to Auth0's /users/{id} endpoint.
        // See: https://auth0.com/docs/api/management/v2#!/Users/get_users_by_id
        $query = array_filter([
            'fields' => $options['fields'] ?? null,
            'include_fields' => $options['include_fields'] ?? null
        ]);

        // Issue the request and return an object representing the results.
        return $this->apiRequest(join('/', ['user', $id]), ['query' => $query]);
    }

    /**
     * Invoked by the AuthServiceProvider at boot to validate an API request.
     * Every API request checks for and validates a JWT before processing calls.
     * An invalid or missing JWT will immediately terminate the request.
     *
     * @return array
     */
    public function handleAuthentication($token)
    {
        // Parse the supplied JWT using a helper library
        if (!($token = (new Parser())->parse($token))) {
            throw new Exception("Invalid JWT token supplied.");
            return;
        }

        // Verify the supplied key's algorithm is appropriate
        if ($token->getHeader('alg') !== 'RS256') {
            throw new Exception("Invalid JWT algorithm supplied.");
            return;
        }

        // Do some basic validation checks on the JWT to ensure it's appropriately configured
        $validator = new ValidationData();
        $validator->setIssuer('https://' . env('AUTH0_DOMAIN') . '/');
        $validator->setAudience(env('AUTH0_AUDIENCE'));

        // Did the token validate successfully?
        if (!$token->validate($validator)) {
            throw new Exception("Invalid JWT token supplied.");
            return;
        }

        // Get the JWT signature hint, to help identify what key the JWT was signed using.
        if (!$signatureHint = $token->getHeader('kid', null)) {
            throw new Exception("Unable to find signature hint.");
            return;
        }

        // Get the available JWT signing keys from Auth0's servers, match it with the one specified in the JWT
        $signature = $this->getJWKS($signatureHint);

        // Verify the signature of the JWT using our JWKS key.
        if (!$token->verify(new Sha256(), $signature)) {
            throw new Exception("Signature of JWT was invalid.");
            return;
        }

        // Verify the token is genuine with the Authentication API
        // NOTE: This is redundant as we are verifying the signature of the JWT, but is useful for reference.
        if (!($user = $this->apiRequest('https://' . env('AUTH0_DOMAIN') . '/userinfo', ['token' => $token]))) {
            throw new Exception("Token is not genuine.");
            return;
        }

        return ['token' => $token];
    }

    /**
     * Download the appropriate JWKS from Auth0's servers. Filter and parse the results and extra the
     * x509 certificate we need to verify an incoming JWT signature.
     *
     * @return object
     */
    private function getJWKS($kid)
    {
        // Issue the request to Auth0's servers, which are always located at /.well-known/jwks.json off your API domain.
        if ($jwks = $this->apiRequest('https://' . env('AUTH0_DOMAIN') . '/.well-known/jwks.json')) {
            // Attempt to find the key that was requested in the JWKS array.
            if ($jwks && is_array($jwks) && isset($jwks['keys']) && is_array($jwks)) {
                foreach ($jwks['keys'] as $jwk) {
                    // Does the key's kid match what we're looking for?
                    if (isset($jwk['n']) && isset($jwk['kid']) && $jwk['kid'] === $kid) {
                        // Return the x509 certificate.
                        return new Key("-----BEGIN CERTIFICATE-----\n{$jwk['x5c'][0]}\n-----END CERTIFICATE-----");
                    }
                }
            }
        }

        return null;
    }
}
