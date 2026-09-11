<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

function verify_csrf_token(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    }
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function base_path(): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    return $base === '/' ? '' : $base;
}

function app_url(string $path = ''): string {
    $base = base_path();
    $path = '/' . ltrim($path, '/');
    return $base === '' ? $path : $base . $path;
}

function redirect_to(string $path): void {
    header('Location: ' . app_url($path));
    exit;
}

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../';
    $class = str_replace('\\', '/', $class);
    $file = $baseDir . $class . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
$base = rtrim(str_replace('\\', '/', dirname($scriptPath)), '/');
if ($base !== '' && $base !== '/' && strpos($requestPath, $base) === 0) {
    $requestPath = substr($requestPath, strlen($base));
}
$requestPath = trim(str_replace('/index.php', '', $requestPath), '/');

if ($requestPath === '' || $requestPath === 'home') {
    if (!empty($_SESSION['role'])) {
        $role = $_SESSION['role'];
        if ($role === 'admin') {
            redirect_to('admin');
        }
        if ($role === 'tabulator') {
            redirect_to('tabulator');
        }
        if ($role === 'judge') {
            redirect_to('judge');
        }
    }

    redirect_to('viewer');
}

$routes = [
    // Auth
    ['methods' => ['GET', 'POST'], 'pattern' => '#^login$#', 'handler' => ['AuthController', 'login']],
    ['methods' => ['GET', 'POST'], 'pattern' => '#^logout$#', 'handler' => ['AuthController', 'logout']],

    // Admin dashboard and CRUD
    ['methods' => ['GET'], 'pattern' => '#^admin$#', 'handler' => ['AdminController', 'index']],

    // Admin list pages
    ['methods' => ['GET'], 'pattern' => '#^admin/events$#', 'handler' => ['AdminController', 'events']],
    ['methods' => ['GET'], 'pattern' => '#^admin/categories$#', 'handler' => ['AdminController', 'categories']],
    ['methods' => ['GET'], 'pattern' => '#^admin/criteria$#', 'handler' => ['AdminController', 'criteria']],
    ['methods' => ['GET'], 'pattern' => '#^admin/contestants$#', 'handler' => ['AdminController', 'contestants']],
    ['methods' => ['GET'], 'pattern' => '#^admin/judges$#', 'handler' => ['AdminController', 'judges']],
    ['methods' => ['GET'], 'pattern' => '#^admin/users$#', 'handler' => ['AdminController', 'users']],
    ['methods' => ['GET'], 'pattern' => '#^admin/scores$#', 'handler' => ['AdminController', 'scores']],

    // Admin event CRUD
    ['methods' => ['GET'], 'pattern' => '#^admin/events/manage/(\d+)$#', 'handler' => ['AdminController', 'manageEvent']],
    ['methods' => ['GET', 'POST'], 'pattern' => '#^admin/events/create$#', 'handler' => ['AdminController', 'createEvent']],
    ['methods' => ['GET', 'POST'], 'pattern' => '#^admin/events/edit/(\d+)$#', 'handler' => ['AdminController', 'editEvent']],
    ['methods' => ['POST'], 'pattern' => '#^admin/events/delete/(\d+)$#', 'handler' => ['AdminController', 'deleteEvent']],
    ['methods' => ['GET', 'POST'], 'pattern' => '#^admin/events/duplicate/(\d+)$#', 'handler' => ['AdminController', 'duplicateEvent']],

    // Reusable rubric templates
    ['methods' => ['GET'], 'pattern' => '#^admin/templates$#', 'handler' => ['AdminController', 'templates']],
    ['methods' => ['GET', 'POST'], 'pattern' => '#^admin/templates/create$#', 'handler' => ['AdminController', 'createTemplate']],
    ['methods' => ['POST'], 'pattern' => '#^admin/templates/delete/(\d+)$#', 'handler' => ['AdminController', 'deleteTemplate']],
    ['methods' => ['GET'], 'pattern' => '#^admin/templates/export/(\d+)$#', 'handler' => ['AdminController', 'exportTemplate']],
    ['methods' => ['POST'], 'pattern' => '#^admin/templates/import$#', 'handler' => ['AdminController', 'importTemplate']],

    // Admin category CRUD
    ['methods' => ['GET', 'POST'], 'pattern' => '#^admin/categories/create$#', 'handler' => ['AdminController', 'createCategory']],
    ['methods' => ['GET', 'POST'], 'pattern' => '#^admin/categories/edit/(\d+)$#', 'handler' => ['AdminController', 'editCategory']],
    ['methods' => ['POST'], 'pattern' => '#^admin/categories/delete/(\d+)$#', 'handler' => ['AdminController', 'deleteCategory']],

    // Admin criteria CRUD
    ['methods' => ['GET', 'POST'], 'pattern' => '#^admin/criteria/create$#', 'handler' => ['AdminController', 'createCriterion']],
    ['methods' => ['GET', 'POST'], 'pattern' => '#^admin/criteria/edit/(\d+)$#', 'handler' => ['AdminController', 'editCriterion']],
    ['methods' => ['POST'], 'pattern' => '#^admin/criteria/delete/(\d+)$#', 'handler' => ['AdminController', 'deleteCriterion']],

    // Admin contestant CRUD
    ['methods' => ['POST'], 'pattern' => '#^admin/contestants/bulk-create$#', 'handler' => ['AdminController', 'bulkCreateContestants']],
    ['methods' => ['GET', 'POST'], 'pattern' => '#^admin/contestants/create$#', 'handler' => ['AdminController', 'createContestant']],
    ['methods' => ['GET', 'POST'], 'pattern' => '#^admin/contestants/edit/(\d+)$#', 'handler' => ['AdminController', 'editContestant']],
    ['methods' => ['POST'], 'pattern' => '#^admin/contestants/delete/(\d+)$#', 'handler' => ['AdminController', 'deleteContestant']],

    // Admin judge assignment
    ['methods' => ['GET', 'POST'], 'pattern' => '#^admin/judges/assign$#', 'handler' => ['AdminController', 'assignJudge']],
    ['methods' => ['POST'], 'pattern' => '#^admin/judges/remove/(\d+)$#', 'handler' => ['AdminController', 'removeJudge']],

    // Admin user management
    ['methods' => ['GET', 'POST'], 'pattern' => '#^admin/users/create$#', 'handler' => ['AdminController', 'createUser']],
    ['methods' => ['GET', 'POST'], 'pattern' => '#^admin/users/edit/(\d+)$#', 'handler' => ['AdminController', 'editUser']],
    ['methods' => ['POST'], 'pattern' => '#^admin/users/delete/(\d+)$#', 'handler' => ['AdminController', 'deleteUser']],

    // Admin score unlock (FR9)
    ['methods' => ['POST'], 'pattern' => '#^admin/scores/unlock/(\d+)/(\d+)$#', 'handler' => ['AdminController', 'unlockScore']],

    // Judge
    ['methods' => ['GET'], 'pattern' => '#^judge$#', 'handler' => ['JudgeController', 'dashboard']],
    ['methods' => ['GET'], 'pattern' => '#^judge/score/(\d+)$#', 'handler' => ['JudgeController', 'score']],

    // Tabulator
    ['methods' => ['GET'], 'pattern' => '#^tabulator$#', 'handler' => ['TabulatorController', 'index']],
    ['methods' => ['GET'], 'pattern' => '#^tabulator/review/(\d+)$#', 'handler' => ['TabulatorController', 'review']],
    ['methods' => ['POST'], 'pattern' => '#^tabulator/recompute/(\d+)$#', 'handler' => ['TabulatorController', 'recompute']],
    ['methods' => ['GET'], 'pattern' => '#^tabulator/scores$#', 'handler' => ['TabulatorController', 'scores']],
    ['methods' => ['POST'], 'pattern' => '#^tabulator/release/(\d+)$#', 'handler' => ['TabulatorController', 'release']],
    ['methods' => ['POST'], 'pattern' => '#^tabulator/hold/(\d+)$#', 'handler' => ['TabulatorController', 'hold']],

    // Viewer
    ['methods' => ['GET'], 'pattern' => '#^viewer$#', 'handler' => ['ViewerController', 'index']],

    // Score API
    ['methods' => ['GET'], 'pattern' => '#^score/form$#', 'handler' => ['ScoreController', 'form']],
    ['methods' => ['POST'], 'pattern' => '#^score/submit$#', 'handler' => ['ScoreController', 'submit']],

    // Reports
    ['methods' => ['GET'], 'pattern' => '#^reports$#', 'handler' => ['ReportController', 'index']],
    ['methods' => ['GET'], 'pattern' => '#^reports/category/(\d+)$#', 'handler' => ['ReportController', 'category']],
    ['methods' => ['GET'], 'pattern' => '#^reports/event/(\d+)$#', 'handler' => ['ReportController', 'event']],
    ['methods' => ['GET'], 'pattern' => '#^reports/scoresheet/(\d+)/(\d+)$#', 'handler' => ['ReportController', 'scoreSheet']],
    ['methods' => ['GET'], 'pattern' => '#^reports/printable/(\d+)$#', 'handler' => ['ReportController', 'printable']],
    ['methods' => ['GET'], 'pattern' => '#^reports/export-csv/(\d+)$#', 'handler' => ['ReportController', 'exportCsv']],
    ['methods' => ['GET'], 'pattern' => '#^reports/export-pdf/(\d+)$#', 'handler' => ['ReportController', 'exportPdf']],
    ['methods' => ['GET'], 'pattern' => '#^reports/export-breakdown-pdf/(\d+)$#', 'handler' => ['ReportController', 'exportBreakdownPdf']],

];

foreach ($routes as $route) {
    if (!in_array($requestMethod, $route['methods'], true)) {
        continue;
    }

    if (!preg_match($route['pattern'], $requestPath, $matches)) {
        continue;
    }

    array_shift($matches);
    [$controllerName, $methodName] = $route['handler'];
    $controllerClass = 'App\\Controllers\\' . $controllerName;

    if (!class_exists($controllerClass)) {
        http_response_code(500);
        echo "Controller not found: " . e($controllerClass);
        exit;
    }

    $controller = new $controllerClass();
    if (!method_exists($controller, $methodName)) {
        http_response_code(500);
        echo "Method not found: " . e($methodName);
        exit;
    }

    $controller->$methodName(...$matches);
    exit;
}

http_response_code(404);
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>404 Not Found</title></head><body style="font-family:Arial,sans-serif;padding:40px;"><h1>404</h1><p>Page not found.</p></body></html>';
