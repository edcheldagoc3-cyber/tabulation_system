<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;

class AuthController extends Controller {

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!function_exists('verify_csrf_token') || !verify_csrf_token()) {
                $this->view('auth/login', ['error' => 'Invalid security token. Please refresh and try again.']);
                return;
            }
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            if (Auth::login($email, $password)) {
                $user = Auth::user();
                if ($user->role === 'admin') {
                    $this->redirect('/admin');
                } elseif ($user->role === 'tabulator') {
                    $this->redirect('/tabulator');

                } 
                elseif($user->role === 'viewer'){
                    $this->redirect('/viewer');

                    }else {
                    $this->redirect('/judge');
                }
            } else {
                $this->view('auth/login', ['error' => 'Invalid email or password.']);
            }
        } else {
            $this->view('auth/login');
        }
    }

    public function logout() {
        Auth::logout();
        $this->redirect('/login');
    }
}
