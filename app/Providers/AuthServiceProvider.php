<?php

namespace App\Providers;

use Exception;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $Auth0;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
      $this->Auth0  = app('App\Http\Controllers\Auth0Controller');
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

        $this->app['auth']->viaRequest('api', function ($request) {
          try {
            // Was a JWT token supplied during the request?
            if (!($token = $request->input('token'))) {
              return null;
            }

            // Invoke handleAuthentication() on our Auth0Controller class to verify the token.
            if ($token = $this->Auth0->handleAuthentication($token)) {
              // Token was verified, authentication passed successfully.
              return ['token' => $token];
            }
          } catch (Exception $error) {
            return null;
          }
        });
    }
}
