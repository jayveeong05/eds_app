<?php
/**
 * Front Controller Entry Point
 * All HTTP requests are routed through this file
 */

// 1. Load dependencies
require_once __DIR__ . '/../api/config/load_env.php';
require_once __DIR__ . '/../src/Router.php';

// 2. Set error reporting based on environment
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// 3. Initialize Router
$router = new Router();

// 4. Define routes
// Root route - redirect to admin panel for user convenience
$router->add('GET', '/', function() {
    header('Location: /admin/');
    exit;
});

// Admin routes
$router->add('GET', '/admin', 'AdminController@index');
$router->add('GET', '/admin/', 'AdminController@index');
$router->add('GET', '/admin/dashboard.php', 'AdminController@dashboard');
$router->add('GET', '/admin/users.php', 'AdminController@users');
$router->add('GET', '/admin/invoices.php', 'AdminController@invoices');
$router->add('GET', '/admin/promotions.php', 'AdminController@promotions');
$router->add('GET', '/admin/news.php', 'AdminController@news');
$router->add('GET', '/admin/knowledge_base.php', 'AdminController@knowledgeBase');
$router->add('GET', '/admin/printer_requests.php', 'AdminController@printerRequests');
$router->add('GET', '/admin/scan.php', 'AdminController@scan');
$router->add('GET', '/admin/logout.php', 'AdminController@logout');

// Admin API routes (POST requests)
$router->add('POST', '/admin', 'AdminController@index');
$router->add('POST', '/admin/', 'AdminController@index');


// API routes (Mobile App)
$router->add('POST', '/api/verify_token.php', 'ApiController@verifyToken');
$router->add('POST', '/api/verify_admin.php', 'ApiController@verifyAdmin');
$router->add('POST', '/api/upload.php', 'ApiController@upload');
$router->add('POST', '/api/upload_knowledge_base.php', 'ApiController@uploadKnowledgeBase');
$router->add('POST', '/api/update_knowledge_base.php', 'ApiController@updateKnowledgeBase');
$router->add('GET', '/api/get_knowledge_base.php', 'ApiController@getKnowledgeBase');
$router->add('POST', '/api/delete_knowledge_base.php', 'ApiController@deleteKnowledgeBase');
$router->add('GET', '/api/get_code_invoices.php', 'ApiController@getCodeInvoices');
$router->add('POST', '/api/get_code_invoices.php', 'ApiController@getCodeInvoices');
$router->add('GET', '/api/get_promotions.php', 'ApiController@getPromotions');
$router->add('GET', '/api/get_news.php', 'ApiController@getNews');
$router->add('GET', '/api/get_profile.php', 'ApiController@getProfile');
$router->add('POST', '/api/get_profile.php', 'ApiController@getProfile');
$router->add('POST', '/api/update_profile.php', 'ApiController@updateProfile');
$router->add('GET', '/api/get_presigned_url.php', 'ApiController@getPresignedUrl');
$router->add('POST', '/api/get_presigned_url.php', 'ApiController@getPresignedUrl');
$router->add('GET', '/api/get_machine_codes.php', 'ApiController@getMachineCodes');
$router->add('POST', '/api/get_machine_codes.php', 'ApiController@getMachineCodes');
$router->add('POST', '/api/check_activation.php', 'ApiController@checkActivation');
$router->add('POST', '/api/clear_kb_history.php', 'ApiController@clearKbHistory');
$router->add('POST', '/api/create_kb_session.php', 'ApiController@createKbSession');
$router->add('GET', '/api/get_kb_messages.php', 'ApiController@getKbMessages');
$router->add('POST', '/api/get_kb_history.php', 'ApiController@getKbHistory');
$router->add('GET', '/api/get_kb_favorites.php', 'ApiController@getKbFavorites');
$router->add('GET', '/api/get_kb_sessions.php', 'ApiController@getKbSessions');
$router->add('POST', '/api/toggle_kb_favorite.php', 'ApiController@toggleKbFavorite');
$router->add('POST', '/api/send_kb_message.php', 'ApiController@sendKbMessage');
$router->add('POST', '/api/printer_chat.php', 'ApiController@printerChat');
$router->add('POST', '/api/send_printer_message.php', 'ApiController@sendPrinterMessage');
$router->add('POST', '/api/add_invoice.php', 'ApiController@addInvoice');
$router->add('POST', '/api/add_news.php', 'ApiController@addNews');
$router->add('POST', '/api/add_promotion.php', 'ApiController@addPromotion');


// Admin API routes
$router->add('POST', '/api/admin/get_dashboard_stats.php', 'AdminApiController@getDashboardStats');
$router->add('POST', '/api/admin/get_all_users.php', 'AdminApiController@getAllUsers');
$router->add('POST', '/api/admin/update_user_status.php', 'AdminApiController@updateUserStatus');
$router->add('POST', '/api/admin/update_user_role.php', 'AdminApiController@updateUserRole');
$router->add('POST', '/api/admin/delete_user.php', 'AdminApiController@deleteUser');
$router->add('POST', '/api/admin/get_all_promotions.php', 'AdminApiController@getAllPromotions');
$router->add('POST', '/api/admin/update_promotion.php', 'AdminApiController@updatePromotion');
$router->add('POST', '/api/admin/delete_promotion.php', 'AdminApiController@deletePromotion');
$router->add('POST', '/api/admin/get_all_news.php', 'AdminApiController@getAllNews');
$router->add('POST', '/api/admin/update_news.php', 'AdminApiController@updateNews');
$router->add('POST', '/api/admin/delete_news.php', 'AdminApiController@deleteNews');
$router->add('POST', '/api/admin/get_printer_requests.php', 'AdminApiController@getPrinterRequests');
$router->add('POST', '/api/admin/upload_printer_kb.php', 'AdminApiController@uploadPrinterKb');
$router->add('POST', '/api/admin/bulk_save_invoices.php', 'AdminApiController@bulkSaveInvoices');
$router->add('POST', '/api/admin/get_all_invoices.php', 'AdminApiController@getAllInvoices');
$router->add('POST', '/api/admin/delete_invoice.php', 'AdminApiController@deleteInvoice');
$router->add('POST', '/api/admin/get_all_machine_codes.php', 'AdminApiController@getAllMachineCodes');
$router->add('POST', '/api/admin/get_user_machine_codes.php', 'AdminApiController@getUserMachineCodes');
$router->add('POST', '/api/admin/add_user_machine_code.php', 'AdminApiController@addUserMachineCode');
$router->add('POST', '/api/admin/delete_user_machine_code.php', 'AdminApiController@deleteUserMachineCode');
$router->add('POST', '/api/admin/restore_user.php', 'AdminApiController@restoreUser');
$router->add('POST', '/api/admin/update_user_name.php', 'AdminApiController@updateUserName');
$router->add('POST', '/api/admin/reset_user_password.php', 'AdminApiController@resetUserPassword');
$router->add('GET', '/api/admin/get_user_details.php', 'AdminApiController@getUserDetails');
$router->add('GET', '/api/admin/get_token.php', 'AdminApiController@getToken');

// 5. Dispatch the request
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$router->dispatch($method, $uri);
