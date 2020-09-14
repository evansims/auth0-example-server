<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Auth0Controller;

class UsersController extends Controller
{
    protected $Auth0;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Inform Lumen this API makes use of the AuthServiceProvider
        $this->middleware('auth');

        $this->Auth0  = app('App\Http\Controllers\Auth0Controller');
    }

    public function list(Request $request)
    {
        // $user = $request->user();
        $page = $request->input('page', 0);
        $q = $request->input('q', null);

        $accounts = $this->Auth0->getUsers(['q' => $q, 'page' => $page, 'sort' => 'nickname:1']);
        $response = [];

        foreach ($accounts as $account) {
          $response[] = (object)[
            'id' => ltrim($account['user_id'], 'auth0|'),
            'type' => 'user',
            'attributes' => $account
          ];
        }

        return response()->json([
          "jsonapi" => [
            "version" => "1.0"
          ],
          'data' => $response
        ], 200);
    }

    public function create()
    {
        return 'create()';
    }

    public function get()
    {
        return 'get()';
    }

    public function update()
    {
        return 'update()';
    }

    public function delete()
    {
        return 'delete()';
    }
}
