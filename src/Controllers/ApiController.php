<?php
require_once __DIR__ . '/BaseController.php';

/**
 * API Controller
 * Handles general /api/* routes
 */
class ApiController extends BaseController {
    
    
    public function verifyToken() {
        $this->render('/api/verify_token.php');
    }

    public function verifyAdmin() {
        $this->render('/api/verify_admin.php');
    }

    public function upload() {
        $this->render('/api/upload.php');
    }

    public function getKnowledgeBase() {
        $this->render('/api/get_knowledge_base.php');
    }

    public function getCodeInvoices() {
        $this->render('/api/get_code_invoices.php');
    }

    public function getPromotions() {
        $this->render('/api/get_promotions.php');
    }

    public function getNews() {
        $this->render('/api/get_news.php');
    }

    public function getProfile() {
        $this->render('/api/get_profile.php');
    }

    public function updateProfile() {
        $this->render('/api/update_profile.php');
    }

    public function getPresignedUrl() {
        $this->render('/api/get_presigned_url.php');
    }

    public function getMachineCodes() {
        $this->render('/api/get_machine_codes.php');
    }

    public function checkActivation() {
        $this->render('/api/check_activation.php');
    }

    public function clearKbHistory() {
        $this->render('/api/clear_kb_history.php');
    }

    public function createKbSession() {
        $this->render('/api/create_kb_session.php');
    }

    public function getKbMessages() {
        $this->render('/api/get_kb_messages.php');
    }

    public function getKbHistory() {
        $this->render('/api/get_kb_history.php');
    }

    public function sendKbMessage() {
        $this->render('/api/send_kb_message.php');
    }

    public function getKbFavorites() {
        $this->render('/api/get_kb_favorites.php');
    }

    public function getKbSessions() {
        $this->render('/api/get_kb_sessions.php');
    }

    public function uploadKnowledgeBase() {
        $this->render('/api/upload_knowledge_base.php');
    }

    public function deleteKnowledgeBase() {
        $this->render('/api/delete_knowledge_base.php');
    }

    public function toggleKbFavorite() {
        $this->render('/api/toggle_kb_favorite.php');
    }

    public function printerChat() {
        $this->render('/api/printer_chat.php');
    }

    public function sendPrinterMessage() {
        $this->render('/api/send_printer_message.php');
    }

    public function addInvoice() {
        $this->render('/api/add_invoice.php');
    }

    public function addNews() {
        $this->render('/api/add_news.php');
    }

    public function addPromotion() {
        $this->render('/api/add_promotion.php');
    }
}
