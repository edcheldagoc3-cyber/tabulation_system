<?php
namespace App\Core;

class Controller {
    protected function view($viewPath, $data = []) {
        extract($data);
        $file = __DIR__ . "/../views/$viewPath.php";
        if (file_exists($file)) {
            require $file;
        } else {
            throw new \Exception("View not found: $viewPath");
        }
    }

    protected function render($viewPath, $data = []) {
        extract($data);
        $file = __DIR__ . "/../views/$viewPath.php";
        if (!file_exists($file)) {
            throw new \Exception("View not found: $viewPath");
        }

        ob_start();
        require $file;
        return ob_get_clean();
    }

    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect($url) {
        if (function_exists('app_url')) {
            $url = app_url($url);
        }
        header("Location: $url");
        exit;
    }
}
