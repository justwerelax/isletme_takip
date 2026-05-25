<?php
session_start();
define('BASE_PATH', __DIR__);

// Autoload
require_once BASE_PATH . '/src/Core/Database.php';
require_once BASE_PATH . '/src/Core/Auth.php';
require_once BASE_PATH . '/src/Helpers/Calculator.php';

// Router
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

// Public pages (no auth required)
$publicPages = ['login', 'setup'];

if (!in_array($page, $publicPages)) {
    Auth::requireLogin();
}

// Route to controller
$controllerMap = [
    'login'     => 'AuthController',
    'logout'    => 'AuthController',
    'dashboard' => 'DashboardController',
    'entries'   => 'EntryController',
    'pos'       => 'PosController',
    'advances'  => 'AdvanceController',
    'installments' => 'InstallmentController',
    'loans'     => 'InstallmentController',
    'months'    => 'MonthController',
    'reports'   => 'ReportController',
    'tasks'     => 'TaskController',
    'staff'     => 'StaffController',
    'settings'  => 'SettingsController',
    'setup'     => 'SetupController',
    'export'       => 'ExportController',
    'quick_import' => 'QuickImportController',
];

$controllerName = $controllerMap[$page] ?? null;

if (!$controllerName) {
    http_response_code(404);
    echo '<h1>404 - Sayfa bulunamadı</h1>';
    exit;
}

$controllerFile = BASE_PATH . "/src/Controllers/{$controllerName}.php";
if (!file_exists($controllerFile)) {
    http_response_code(404);
    echo '<h1>Controller bulunamadı: ' . htmlspecialchars($controllerName) . '</h1>';
    exit;
}

require_once $controllerFile;
$controller = new $controllerName();

// Handle logout specially
if ($page === 'logout') {
    $controller->logout();
    exit;
}

if (method_exists($controller, $action)) {
    $controller->$action();
} else {
    $controller->index();
}
