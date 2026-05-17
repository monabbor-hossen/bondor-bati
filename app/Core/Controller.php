<?php
namespace App\Core;

/**
 * Base Controller
 * All other controllers extend this class.
 * Supports rendering views inside the master layout (layout/main.php).
 */
class Controller {

    /**
     * Render a view file, optionally wrapped inside the master layout.
     *
     * @param string $viewName  The view file path relative to app/Views/ (without .php)
     * @param array  $data      Data to extract and pass to the view
     * @param bool   $useLayout Whether to wrap the view inside layout/main.php (default: true)
     */
    protected function view($viewName, $data = [], $useLayout = true) {
        // Extract array to individual variables (e.g. $data['pageTitle'] -> $pageTitle)
        extract($data);

        $viewFile = ROOT_PATH . '/app/Views/' . $viewName . '.php';

        if (!file_exists($viewFile)) {
            die("View file '{$viewName}.php' not found at: {$viewFile}");
        }

        if ($useLayout) {
            // Tell the layout which content file to inject
            $contentView = $viewName;
            // Load the master layout — it will require the $contentView internally
            $layoutFile = ROOT_PATH . '/app/Views/layout/main.php';
            if (file_exists($layoutFile)) {
                require_once $layoutFile;
            } else {
                // Fallback: render view directly if no layout found
                require_once $viewFile;
            }
        } else {
            // Render the view directly without any layout wrapper
            require_once $viewFile;
        }
    }

    /**
     * Redirect to a given URL path
     *
     * @param string $url  e.g. '?url=home' or '?url=inventory/closeDayView'
     */
    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }
}
