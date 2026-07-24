<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| ROUTES VECTOR INVOICE
| -------------------------------------------------------------------------
| Route dirapikan agar tidak ada duplikasi dan tidak ada endpoint mati.
*/

$route['default_controller'] = 'dashboard';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// Auth
$route['login']  = 'auth/login';
$route['logout'] = 'auth/logout';
$route['forgot-password'] = 'auth/forgot_password';
$route['reset-password/(:any)'] = 'auth/reset_password/$1';



// Konten aplikasi Android (banner, promosi, portofolio, dan pengaturan publik)
$route['app-content'] = 'MobileContent/index';
$route['app-content/settings']['post'] = 'MobileContent/save_settings';
$route['app-content/banner/save/(:num)']['post'] = 'MobileContent/save_banner/$1';
$route['app-content/promotion/save/(:num)']['post'] = 'MobileContent/save_promotion/$1';
$route['app-content/portfolio/save/(:num)']['post'] = 'MobileContent/save_portfolio/$1';
$route['app-content/delete/(:any)/(:num)']['post'] = 'MobileContent/delete/$1/$2';

// Pelanggan
$route['clients'] = 'clients/index';
$route['clients/create'] = 'clients/create';
$route['clients/view/(:num)'] = 'clients/view/$1';
$route['clients/edit/(:num)'] = 'clients/edit/$1';
$route['clients/toggle/(:num)'] = 'clients/toggle/$1';

// Tim dan profil admin
$route['team'] = 'team/index';
$route['team/create'] = 'team/create';
$route['team/edit/(:num)'] = 'team/edit/$1';
$route['team/toggle/(:num)'] = 'team/toggle/$1';
$route['profile'] = 'profile/index';

// Master desain
$route['designs'] = 'designs/index';
$route['designs/create'] = 'designs/create';
$route['designs/edit/(:num)'] = 'designs/edit/$1';
$route['designs/delete/(:num)'] = 'designs/delete/$1';
$route['designs/delete-source/(:num)'] = 'designs/delete_source/$1';

// Harga / price matrix
$route['prices'] = 'prices/index';
$route['prices/create'] = 'prices/create';
$route['prices/edit/(:num)'] = 'prices/edit/$1';
$route['prices/delete/(:num)'] = 'prices/delete/$1';
$route['prices/get_base_price'] = 'prices/get_base_price';

// Orders
$route['orders'] = 'orders/index';
$route['orders/create'] = 'orders/create';
$route['orders/edit/(:num)'] = 'orders/edit/$1';
$route['orders/view/(:num)'] = 'orders/view/$1';
$route['orders/delete/(:num)'] = 'orders/delete/$1';
$route['orders/add_payment/(:num)'] = 'orders/add_payment/$1';
$route['orders/delete_payment/(:num)'] = 'orders/delete_payment/$1';
$route['orders/add_revision/(:num)'] = 'orders/add_revision/$1';
$route['orders/upload_preview/(:num)'] = 'orders/upload_preview/$1';
$route['orders/upload_source/(:num)'] = 'orders/upload_source/$1';
$route['orders/delete_sources/(:num)'] = 'orders/delete_sources/$1';
$route['orders/delete_previews/(:num)'] = 'orders/delete_previews/$1';
$route['orders/revision_edit/(:num)'] = 'orders/revision_edit/$1';
$route['orders/revision_delete/(:num)'] = 'orders/revision_delete/$1';


// Google Drive Storage
$route['drive-storage'] = 'DriveStorage/index';
$route['drive-storage/image/(:any)'] = 'DriveStorage/image/$1';
$route['drive-storage/cleanup-local'] = 'DriveStorage/cleanup_local';
$route['drive-storage/guide'] = 'DriveStorage/guide';
$route['drive-storage/migrate'] = 'DriveStorage/migrate';
$route['drive-storage/sync-existing'] = 'DriveStorage/sync_existing';
$route['drive-storage/connect'] = 'DriveStorage/connect';
$route['drive-storage/oauth-callback'] = 'DriveStorage/oauth_callback';
$route['drive-storage/disconnect'] = 'DriveStorage/disconnect';

// Invoice
$route['invoices/print/(:num)'] = 'invoices/print_invoice/$1';
$route['invoices/download_jpg/(:num)'] = 'invoices/download_jpg/$1';

// AI Assistant offline/rule-based
$route['ai'] = 'ai/index';
$route['ai/chat'] = 'ai/chat';
$route['ai/get_body_parts'] = 'ai/get_body_parts';
$route['ai/save_design_wizard'] = 'ai/save_design_wizard';
$route['ai/save_price_matrix_wizard'] = 'ai/save_price_matrix_wizard';
$route['ai/save_order_wizard'] = 'ai/save_order_wizard';


// Public demo & API documentation
$route['demo'] = 'demo/index';
$route['api/docs'] = 'apidocs/index';

// REST API Android
$route['api/health']['get'] = 'api/health_api/index';
$route['api/config']['get'] = 'api/config_api/index';
$route['api/home-content']['get'] = 'api/home_content_api/index';
$route['api/auth/register']['post'] = 'api/auth_api/register';
$route['api/auth/login']['post'] = 'api/auth_api/login';
$route['api/auth/logout']['post'] = 'api/auth_api/logout';
$route['api/auth/forgot-password']['post'] = 'api/auth_api/forgot_password';
$route['api/designs']['get'] = 'api/designs_api/index';
$route['api/designs/(:num)']['get'] = 'api/designs_api/show/$1';
$route['api/profile']['get'] = 'api/profile_api/index';
$route['api/profile']['put'] = 'api/profile_api/update';
$route['api/orders']['get'] = 'api/orders_api/index';
$route['api/orders']['post'] = 'api/orders_api/store';
$route['api/orders/(:num)']['get'] = 'api/orders_api/show/$1';
$route['api/orders/(:num)']['put'] = 'api/orders_api/update/$1';
$route['api/orders/(:num)/files']['post'] = 'api/files_api/upload_reference/$1';
$route['api/orders/(:num)/revisions']['post'] = 'api/revisions_api/store/$1';
$route['api/payments/methods']['get'] = 'api/payments_api/methods';
$route['api/payment-confirmations']['get'] = 'api/payments_api/confirmations';
$route['api/orders/(:num)/payment-confirmations']['post'] = 'api/payments_api/submit/$1';
$route['api/notifications']['get'] = 'api/notifications_api/index';
$route['api/notifications/(:num)/read']['put'] = 'api/notifications_api/read/$1';
$route['api/notifications/read-all']['put'] = 'api/notifications_api/read_all';

// Web admin pembayaran
$route['payments'] = 'payments/index';
$route['payments/approve/(:num)'] = 'payments/approve/$1';
$route['payments/reject/(:num)'] = 'payments/reject/$1';
$route['payment-methods'] = 'payments/methods';
$route['payment-methods/create'] = 'payments/method_edit/0';
$route['payment-methods/edit/(:num)'] = 'payments/method_edit/$1';
