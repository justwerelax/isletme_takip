<?php
class AuthController {
    public function index() {
        if (Auth::check()) {
            header('Location: ?page=dashboard');
            exit;
        }

        $error = Auth::flash('error');
        require BASE_PATH . '/templates/auth/login.php';
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=login');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $_SESSION['flash_error'] = 'Kullanıcı adı ve şifre gereklidir.';
            header('Location: ?page=login');
            exit;
        }

        if (Auth::login($username, $password)) {
            header('Location: ?page=dashboard');
        } else {
            $_SESSION['flash_error'] = 'Geçersiz kullanıcı adı veya şifre.';
            header('Location: ?page=login');
        }
        exit;
    }

    public function logout() {
        Auth::logout();
        session_start();
        $_SESSION['flash_success'] = 'Başarıyla çıkış yapıldı.';
        header('Location: ?page=login');
        exit;
    }
}
