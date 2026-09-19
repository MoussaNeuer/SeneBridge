<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;
use App\Support\App;
use App\Support\Request;

abstract class Controller
{
    protected Request $request;
    protected AuthService $auth;

    /** @var array<string, mixed> Utilisateur courant (session web ou Bearer API). */
    protected array $authUser = [];

    public function __construct()
    {
        $this->request = App::request();
        $this->auth = new AuthService();
        $this->authUser = App::user() ?? $this->authUser;
    }

    /**
     * Rétroaction vers la requête précédente avec messages flash.
     *
     * @return Response
     */
    protected function backWithErrors(array $errors, array $old = [])
    {
        foreach ($errors as $error) {
            App::flash('error', $error);
        }

        App::remember($old !== [] ? $old : $this->request->only(['first_name', 'last_name', 'email', 'phone']));

        return \App\Support\Response::redirectBack();
    }
}