<?php
require_once __DIR__ . '/BaseController.php';

/**
 * Admin Controller
 * Handles all /admin/* routes
 */
class AdminController extends BaseController {
    public function index() {
        $this->renderAdminView('index.php');
    }

    public function dashboard() {
        $this->renderAdminView('dashboard.php');
    }

    public function users() {
        $this->renderAdminView('users.php');
    }

    public function invoices() {
        $this->renderAdminView('invoices.php');
    }

    public function promotions() {
        $this->renderAdminView('promotions.php');
    }

    public function news() {
        $this->renderAdminView('news.php');
    }

    public function knowledgeBase() {
        $this->renderAdminView('knowledge_base.php');
    }

    public function printerRequests() {
        $this->renderAdminView('printer_requests.php');
    }

    public function scan() {
        $this->renderAdminView('scan.php');
    }

    public function logout() {
        $this->renderAdminView('logout.php');
    }

    /**
     * Render an admin view from src/Admin/Views/
     */
    private function renderAdminView($file) {
        $fullPath = __DIR__ . '/../Admin/Views/' . $file;
        
        if (!file_exists($fullPath)) {
            http_response_code(404);
            echo "Admin view not found: $file";
            return;
        }

        // Change working directory to the view's location
        // so relative includes work correctly
        $oldCwd = getcwd();
        chdir(dirname($fullPath));
        
        require $fullPath;
        
        chdir($oldCwd);
    }
}
