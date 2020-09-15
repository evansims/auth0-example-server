# Auth0 Demonstration Server/API

Thanks for your interest in my demonstration backend server of [Auth0's](https://auth0.com/) APIs. This is one half of a project demonstrating how one might go about implementing Auth0's authentication API to interact with a custom backend.

This repository houses the PHP-based backend server, using the [Lumen microframework](https://lumen.laravel.com/). [The accompanying JavaScript-based frontend can be found here.](https://github.com/evansims/auth0-example-client)

## Overview of the Demonstration API

This project demonstrates the following functions:

-   Approving requests authenticated with Auth0's Authentication API
    -   Validating JWT access tokens provided by the [frontend client](https://github.com/evansims/auth0-example-client)
    -   Verifying access token signatures using JWKS
-   Issuing Auth0 Management API calls
    -   We're using the /users endpoint, but this demonstration could be expanded for any calls to the Management API.
    -   We're demonstrating searching users, and paginated results.
-   This demonstration server transforms responses to the JSON API specification, for easy consumption by the [frontend client](https://github.com/evansims/auth0-example-client) as Ember Data models.

## Requirements

-   An [Auth0 account](https://manage.auth0.com/dashboard)
-   [Docker](https://www.docker.com/)
-   The counterpart [frontend client](https://github.com/evansims/auth0-example-server)

## Getting Started

Begin by cloning this repository to your local machine.

```
git clone https://github.com/evansims/auth0-example-server.git
```

### 1. Setup an Auth0 Machine-to-Machine Application

1. [Sign up for an Auth0 account](https://manage.auth0.com/dashboard), if you don't already have one, and sign in.
2. From your [Auth0 dashboard](https://manage.auth0.com/dashboard/) select 'Applications' from the sidebar.
3. Press the 'Create Application' button and choose 'Machine to Machine Application.'
4. Make note of the `Client ID`, `Client Secret` and `Domain` values shown, you'll need these later.

### 2. Setup an Auth0 API

1. From your [Auth0 dashboard](https://manage.auth0.com/dashboard/) select 'APIs' from the sidebar.
2. For efficiency sake we'll select the existing 'Auth0 Management API', but you can create your own if you prefer. Select your API of choice.
3. Under the _Settings_ tab, make note of the unique `Identifier` value, you'll need this for configuring the server and the [frontend](https://github.com/evansims/auth0-example-client).
4. Under the _Machine to Machine Applications_ tab, authorize the Application you created in the previous step (by flipping it's toggle in the right/green position.)
5. Click the `>` arrow next to your application's name to display permissions/scopes available to your application.
    - The only permission required for this example is 'read:users' but you can "select all" if you prefer.
6. Press 'update' to apply the permissions to your Application.

### 3. Create demo users

1. From your [Auth0 dashboard](https://manage.auth0.com/dashboard/) expand 'Users & Roles' in the sidebar.
2. Select Users.
3. Create a variety of example users for the demonstration.
    - One of these users will be used by you to sign into the [frontend](https://github.com/evansims/auth0-example-client), so set an email address and password you'll remember.

### 4. Configure your Auth0 credentials

1. On your machine, within the cloned directory of this repository, duplicate `.env.default` and name it `.env`.
2. Set the value of `AUTH0_CLIENTID` to the `Client ID` of your M2M Application, noted in the previous steps.
3. Set `AUTH0_SECRET` to the `Client Secret` of your M2M Application.
4. Set `AUTH0_DOMAIN` to the `Domain` of your M2M Application.
5. Set `AUTHO_AUDIENCE` to the `Identifier` of your API, noted in the previous steps.
6. Save the configuration file.

Note that these values are sensitive! Treat them like they're passwords, and never commit them to public repositories or share them with untrusted parties.

### 5. Launch the backend

This project makes use of [Docker](https://www.docker.com/) to simplify getting started and reduce the complexity of installing dependencies.

Open a shell, navigate to this repository's cloned directory on your local machine, and run:

```
$ docker-compose up -d
```

You will also need to install the project's dependencies, which can be done by running:

```
$ docker-compose exec app composer update
```

You can now access the API at [http://localhost:8000](http://localhost:8000) on your local machine.

Whenever you're ready to shut down the server, simply run:

```
$ docker-compose down
```

## Using the Demonstration API

### Authenticating

This demonstration API expects a `token` parameter containing a valid Auth0 JWT for all requests. Calls without these will be rejected. These tokens are generated by the [frontend client](https://github.com/evansims/auth0-example-client) after signing in.

[You can learn more about generating access tokens here.](https://auth0.com/docs/tokens/access-tokens)

Manually issuing an API call with an access token might look like this:

```
curl \
  --request GET \
  --url 'http://localhost:8000/users?token=YOUR_ACCESS_TOKEN'
```

### Listing and Searching for Users

This demonstration API pulls a list of users from Auth0's Management API through a `/users` endpoint. This endpoint supports searching and pagination.

> The following examples use cURL to make the requests, which can be ran from your terminal/shell. Text in ALL_CAPS is meant to be replaced with your own values as appropriate.

To return a paginated list of users:

```
curl \
  --request GET \
  --url 'http://localhost:8000/users?token=YOUR_ACCESS_TOKEN'
```

To paginate for more results users, pass a `page` parameter with the next page value you want. Remember that results begin at page 0.

```
curl \
  --request GET \
  --url 'http://localhost:8000/users?token=YOUR_ACCESS_TOKEN&page=PAGE_NUMBER'
```

To search for users, pass a `q` parameter.

```
curl \
  --request GET \
  --url 'http://localhost:8000/users?token=YOUR_ACCESS_TOKEN&q=SEARCH_TERM'
```

## Auth0 Documentation

-   Documentation for Auth0's APIs can be found [here](https://auth0.com/docs/api/info).
-   Quickstarts for a variety of use cases, languages and respective frameworks can be found [here](https://auth0.com/docs/quickstarts).

## Contributing

Pull requests are welcome!

## Security Vulnerabilities

If you discover a security vulnerability within this project, please send an e-mail to Evan Sims at hello@evansims.com. All security vulnerabilities will be promptly addressed.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
