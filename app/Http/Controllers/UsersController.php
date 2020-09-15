<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Auth0Controller;

class UsersController extends Controller
{
    protected $Auth0;

    /**
     * Setup our users controller to use authentication.
     *
     * @return void
     */
    public function __construct()
    {
        // Inform Lumen this API makes use of the AuthServiceProvider
        $this->middleware('auth');

        // Create an alias to our Auth0Controller for issuing API requests.
        $this->Auth0  = app('App\Http\Controllers\Auth0Controller');
    }

    /**
     * Fetch a paginated list of users from Auth0's Management API.
     * Transform the results for JSON API compatibility, for clean consumption from our frontend client.
     *
     * @return void
     */
    public function list(Request $request)
    {
        // API results are paginated. Passing a ?page parameter (defaulting to 0) will return additional results.
        $page = $request->input('page', 0);

        // Filter user results using a search term.
        $q = $request->input('q', null);

        // Issue a request to Auth0's Management API to pull matching users.
        $users = $this->Auth0->getUsers([
            'q' => $q,
            'page' => $page,
            'sort' => 'nickname:1'
        ]);
        $response = [];

        // Iterate over users and transform into a JSON API compatible response.
        foreach ($users as $user) {
            $response[] = (object)[
              'id' => ltrim($user['user_id'], 'auth0|'),
              'type' => 'user',
              'attributes' => $user
          ];
        }

        // Return the transformed users list.
        return response()->json([
            "jsonapi" => [
                "version" => "1.0"
            ],
            'data' => $response
        ], 200);
    }
}
