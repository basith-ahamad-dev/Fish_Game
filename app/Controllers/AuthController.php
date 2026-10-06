<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return $this->view('auth/login', ['title' => 'Sign in'], 'auth');
    }

    public function login(): Response
    {
        $this->validate([
            'identifier' => 'required|max:120',
            'password'   => 'required|max:200',
        ], ['identifier' => 'Username or email'], '/login');

        $error = (new AuthService())->attempt(
            $this->request->string('identifier'),
            (string) $this->request->input('password'),
            $this->request->bool('remember'),
            $this->request->ip()
        );

        if ($error) {
            throw $this->validationFailed(['identifier' => $error], '/login');
        }

        $intended = Session::get('intended', '/dashboard');
        Session::forget('intended');
        $this->flash('success', 'Welcome back, ' . auth_user()['username'] . '!');

        return $this->redirect(str_starts_with($intended, '/') ? $intended : '/dashboard');
    }

    public function showRegister(): Response
    {
        return $this->view('auth/register', ['title' => 'Create account'], 'auth');
    }

    public function register(): Response
    {
        $data = $this->validate([
            'username' => 'required|username',
            'email'    => 'required|email|max:120',
            'password' => 'required|min:8|max:72|strong_password|confirmed',
        ], [], '/register');

        $auth = new AuthService();
        $conflicts = $auth->registrationConflicts($data['username'], $data['email']);
        if ($conflicts) {
            throw $this->validationFailed($conflicts, '/register');
        }

        $user = $auth->register($data['username'], $data['email'], $data['password']);
        $this->flash('success', "Welcome aboard, {$user['username']}! Pick a difficulty and cast your first line.");

        return $this->redirect('/dashboard');
    }

    public function logout(): Response
    {
        (new AuthService())->logout($this->request);
        Session::start();
        $this->flash('info', 'You have been signed out. See you on the water!');

        return $this->redirect('/');
    }
}
