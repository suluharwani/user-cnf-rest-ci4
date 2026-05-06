<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->group('api', function($routes) {
    
    // Authentication routes (no auth required)
    $routes->post('auth/register', 'Api\AuthController::register');
    $routes->post('auth/login', 'Api\AuthController::login');
     // CDN Public routes (no auth required for downloads if public)
    $routes->get('cdn/download/(:num)', 'Api\CdnController::download/$1');
    $routes->get('cdn/view/(:num)', 'Api\CdnController::view/$1');
    
    // Protected routes (with JWT middleware)
    $routes->group('', ['filter' => 'auth'], function($routes) {
         // Auth profile
        $routes->get('auth/profile', 'Api\AuthController::profile');
        // CDN Management
        $routes->post('cdn/upload', 'Api\CdnController::upload');
        $routes->post('cdn/upload-multiple', 'Api\CdnController::uploadMultiple');
        $routes->get('cdn/files', 'Api\CdnController::listFiles');
        $routes->get('cdn/files/(:num)', 'Api\CdnController::getFileInfo/$1');
        $routes->put('cdn/files/(:num)', 'Api\CdnController::updateFile/$1');
        $routes->delete('cdn/files/(:num)', 'Api\CdnController::deleteFile/$1');
        $routes->get('cdn/stats', 'Api\CdnController::stats');
        $routes->post('cdn/folders', 'Api\CdnController::createFolder');
        $routes->get('cdn/folders', 'Api\CdnController::listFolders');
        
        // Applications CRUD
        $routes->get('applications', 'Api\ApplicationController::index');
        $routes->post('applications', 'Api\ApplicationController::create');
        $routes->get('applications/(:num)', 'Api\ApplicationController::show/$1');
        $routes->put('applications/(:num)', 'Api\ApplicationController::update/$1');
        $routes->delete('applications/(:num)', 'Api\ApplicationController::delete/$1');
        // Applications CRUD
        $routes->get('applications', 'Api\ApplicationController::index');
        $routes->post('applications', 'Api\ApplicationController::create');
        $routes->get('applications/(:num)', 'Api\ApplicationController::show/$1');
        $routes->put('applications/(:num)', 'Api\ApplicationController::update/$1');
        $routes->delete('applications/(:num)', 'Api\ApplicationController::delete/$1');
        
        // Roles CRUD
        $routes->get('roles', 'Api\RoleController::index');
        $routes->post('roles', 'Api\RoleController::create');
        $routes->get('roles/(:num)', 'Api\RoleController::show/$1');
        $routes->put('roles/(:num)', 'Api\RoleController::update/$1');
        $routes->delete('roles/(:num)', 'Api\RoleController::delete/$1');
        
        // Modules
        $routes->get('modules', 'Api\ModuleController::index');
        $routes->post('modules', 'Api\ModuleController::create');
        $routes->get('modules/(:num)', 'Api\ModuleController::show/$1');
        $routes->put('modules/(:num)', 'Api\ModuleController::update/$1');
        $routes->delete('modules/(:num)', 'Api\ModuleController::delete/$1');
        $routes->get('applications/(:num)/modules', 'Api\ModuleController::getByApplication/$1');
        
        // Role Permissions
        $routes->get('roles/(:num)/permissions', 'Api\RolePermissionController::getRolePermissions/$1');
        $routes->post('role-permissions', 'Api\RolePermissionController::setPermissions');
        $routes->get('check-access/(:num)/(:segment)/(:segment)', 'Api\RolePermissionController::checkAccess/$1/$2/$3');
        
        // User Management
        $routes->get('users', 'Api\UserManagementController::index');
        $routes->get('users/(:num)', 'Api\UserManagementController::show/$1');
        $routes->put('users/(:num)', 'Api\UserManagementController::update/$1');
        $routes->delete('users/(:num)', 'Api\UserManagementController::delete/$1');
        $routes->post('users/assign-role', 'Api\UserManagementController::assignRole');
        $routes->post('users/remove-role', 'Api\UserManagementController::removeRole');
          // ✅ User Avatar Routes
        $routes->post('users/(:num)/avatar', 'Api\UserManagementController::uploadAvatar/$1');
        $routes->delete('users/(:num)/avatar', 'Api\UserManagementController::removeAvatar/$1');
        
        $routes->post('users/assign-role', 'Api\UserManagementController::assignRole');
        $routes->post('users/remove-role', 'Api\UserManagementController::removeRole');
        });

    
});
// Inventory routes
$routes->group('api/inv', ['filter' => 'auth'], function($routes) {
    
    // Materials
    $routes->get('materials', 'Api\Inv\InvMaterialController::index');
    $routes->post('materials', 'Api\Inv\InvMaterialController::create');
    $routes->get('materials/(:num)', 'Api\Inv\InvMaterialController::show/$1');
    $routes->put('materials/(:num)', 'Api\Inv\InvMaterialController::update/$1');
    $routes->delete('materials/(:num)', 'Api\Inv\InvMaterialController::delete/$1');
    $routes->get('materials/low-stock', 'Api\Inv\InvMaterialController::lowStock');
    $routes->get('materials/by-type', 'Api\Inv\InvMaterialController::byType');
    
    // Suppliers
    $routes->get('suppliers', 'Api\Inv\InvSupplierController::index');
    $routes->post('suppliers', 'Api\Inv\InvSupplierController::create');
    $routes->get('suppliers/international', 'Api\Inv\InvSupplierController::international');
    $routes->get('suppliers/local', 'Api\Inv\InvSupplierController::local');
    $routes->get('suppliers/(:num)', 'Api\Inv\InvSupplierController::show/$1');
    $routes->put('suppliers/(:num)', 'Api\Inv\InvSupplierController::update/$1');
    
    // Stock
    $routes->get('stock', 'Api\Inv\InvStockController::index');
    $routes->get('stock/warehouse/(:num)', 'Api\Inv\InvStockController::byWarehouse/$1');
    $routes->get('stock/material/(:num)', 'Api\Inv\InvStockController::byMaterial/$1');
    $routes->get('stock/value', 'Api\Inv\InvStockController::stockValue');
    
    // Stock Movements
    $routes->get('movements', 'Api\Inv\InvStockMovementController::index');
    $routes->get('movements/material/(:num)', 'Api\Inv\InvStockMovementController::byMaterial/$1');
    
    // Stock Opname
    $routes->get('opname', 'Api\Inv\InvStockOpnameController::index');
    $routes->post('opname', 'Api\Inv\InvStockOpnameController::create');
    $routes->get('opname/(:num)', 'Api\Inv\InvStockOpnameController::show/$1');
    $routes->put('opname/(:num)/start', 'Api\Inv\InvStockOpnameController::start/$1');
    $routes->post('opname/(:num)/items', 'Api\Inv\InvStockOpnameController::addItem/$1');
    $routes->put('opname/(:num)/complete', 'Api\Inv\InvStockOpnameController::complete/$1');
    
    // Damage Reports
    $routes->get('damage-reports', 'Api\Inv\InvDamageReportController::index');
    $routes->post('damage-reports', 'Api\Inv\InvDamageReportController::create');
    $routes->get('damage-reports/(:num)', 'Api\Inv\InvDamageReportController::show/$1');
    $routes->post('damage-reports/(:num)/items', 'Api\Inv\InvDamageReportController::addItem/$1');
    $routes->put('damage-reports/(:num)/dispose', 'Api\Inv\InvDamageReportController::approveAndDispose/$1');
    $routes->post('damage-reports/(:num)/documents', 'Api\Inv\InvDamageReportController::uploadDocument/$1');
    
    // Documents (Customs, etc)
    $routes->get('documents', 'Api\Inv\InvDocumentController::index');
    $routes->post('documents', 'Api\Inv\InvDocumentController::create');
    $routes->post('documents/(:num)/upload', 'Api\Inv\InvDocumentController::uploadFile/$1');
    $routes->get('documents/generate-number', 'Api\Inv\InvDocumentController::generateCustomsNumber');
    
    // Currency & Exchange Rates
    $routes->get('currencies', 'Api\Inv\InvCurrencyController::index');
    $routes->post('currencies', 'Api\Inv\InvCurrencyController::create');
    $routes->put('currencies/(:num)', 'Api\Inv\InvCurrencyController::update/$1');
    $routes->get('exchange-rates', 'Api\Inv\InvExchangeRateController::index');
    $routes->post('exchange-rates', 'Api\Inv\InvExchangeRateController::create');
    $routes->get('exchange-rates/latest', 'Api\Inv\InvExchangeRateController::latest');
    
    // Purchase Orders
    $routes->get('purchase-orders', 'Api\Inv\InvPurchaseOrderController::index');
    $routes->post('purchase-orders', 'Api\Inv\InvPurchaseOrderController::create');
    $routes->get('purchase-orders/(:num)', 'Api\Inv\InvPurchaseOrderController::show/$1');
    $routes->put('purchase-orders/(:num)/approve', 'Api\Inv\InvPurchaseOrderController::approve/$1');
    
    // Goods Receipt
    $routes->get('goods-receipts', 'Api\Inv\InvGoodsReceiptController::index');
    $routes->post('goods-receipts', 'Api\Inv\InvGoodsReceiptController::create');
    $routes->get('goods-receipts/(:num)', 'Api\Inv\InvGoodsReceiptController::show/$1');
    
    // Warehouse
    $routes->get('warehouses', 'Api\Inv\InvWarehouseController::index');
    $routes->post('warehouses', 'Api\Inv\InvWarehouseController::create');
});