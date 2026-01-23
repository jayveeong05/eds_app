<?php
require_once __DIR__ . '/BaseController.php';

/**
 * Admin API Controller
 * Handles /api/admin/* routes (backend API for admin panel)
 */
class AdminApiController extends BaseController {
    
    
    public function getDashboardStats() {
        $this->render('/api/admin/get_dashboard_stats.php');
    }

    public function getAllUsers() {
        $this->render('/api/admin/get_all_users.php');
    }

    public function updateUserStatus() {
        $this->render('/api/admin/update_user_status.php');
    }

    public function updateUserRole() {
        $this->render('/api/admin/update_user_role.php');
    }

    public function deleteUser() {
        $this->render('/api/admin/delete_user.php');
    }

    public function getAllPromotions() {
        $this->render('/api/admin/get_all_promotions.php');
    }

    public function updatePromotion() {
        $this->render('/api/admin/update_promotion.php');
    }

    public function deletePromotion() {
        $this->render('/api/admin/delete_promotion.php');
    }

    public function getAllNews() {
        $this->render('/api/admin/get_all_news.php');
    }

    public function updateNews() {
        $this->render('/api/admin/update_news.php');
    }

    public function deleteNews() {
        $this->render('/api/admin/delete_news.php');
    }

    public function getPrinterRequests() {
        $this->render('/api/admin/get_printer_requests.php');
    }

    public function uploadPrinterKb() {
        $this->render('/api/admin/upload_printer_kb.php');
    }

    public function bulkSaveInvoices() {
        $this->render('/api/admin/bulk_save_invoices.php');
    }

    public function getAllMachineCodes() {
        $this->render('/api/admin/get_all_machine_codes.php');
    }

    public function getUserMachineCodes() {
        $this->render('/api/admin/get_user_machine_codes.php');
    }

    public function addUserMachineCode() {
        $this->render('/api/admin/add_user_machine_code.php');
    }

    public function deleteUserMachineCode() {
        $this->render('/api/admin/delete_user_machine_code.php');
    }

    public function getUserDetails() {
        $this->render('/api/admin/get_user_details.php');
    }

    public function getToken() {
        $this->render('/api/admin/get_token.php');
    }
}
