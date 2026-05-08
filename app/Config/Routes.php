<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ======================================================
// DEFAULT
// ======================================================
$routes->get('/', 'Home::index');

// ======================================================
// API ROUTES
// ======================================================
$routes->group('api', function ($routes) {

    // ==================================================
    // AUTHENTICATION (PUBLIC)
    // ==================================================
    $routes->post('auth/register', 'Api\AuthController::register');
    $routes->post('auth/login', 'Api\AuthController::login');

    // ==================================================
    // CDN PUBLIC
    // ==================================================
    $routes->get('cdn/download/(:num)', 'Api\CdnController::download/$1');
    $routes->get('cdn/view/(:num)', 'Api\CdnController::view/$1');

    // ==================================================
    // PROTECTED ROUTES
    // ==================================================
    $routes->group('', ['filter' => 'auth'], function ($routes) {

        // ==============================================
        // AUTH PROFILE
        // ==============================================
        $routes->get('auth/profile', 'Api\AuthController::profile');

        // ==============================================
        // CDN MANAGEMENT
        // ==============================================
        $routes->post('cdn/upload', 'Api\CdnController::upload');
        $routes->post('cdn/upload-multiple', 'Api\CdnController::uploadMultiple');
        $routes->get('cdn/files', 'Api\CdnController::listFiles');
        $routes->get('cdn/files/(:num)', 'Api\CdnController::getFileInfo/$1');
        $routes->put('cdn/files/(:num)', 'Api\CdnController::updateFile/$1');
        $routes->delete('cdn/files/(:num)', 'Api\CdnController::deleteFile/$1');
        $routes->get('cdn/stats', 'Api\CdnController::stats');
        $routes->post('cdn/folders', 'Api\CdnController::createFolder');
        $routes->get('cdn/folders', 'Api\CdnController::listFolders');

        // ==============================================
        // APPLICATIONS
        // ==============================================
        $routes->get('applications', 'Api\ApplicationController::index');
        $routes->post('applications', 'Api\ApplicationController::create');
        $routes->get('applications/(:num)', 'Api\ApplicationController::show/$1');
        $routes->put('applications/(:num)', 'Api\ApplicationController::update/$1');
        $routes->delete('applications/(:num)', 'Api\ApplicationController::delete/$1');

        // ==============================================
        // ROLES
        // ==============================================
        $routes->get('roles', 'Api\RoleController::index');
        $routes->post('roles', 'Api\RoleController::create');
        $routes->get('roles/(:num)', 'Api\RoleController::show/$1');
        $routes->put('roles/(:num)', 'Api\RoleController::update/$1');
        $routes->delete('roles/(:num)', 'Api\RoleController::delete/$1');

        // ==============================================
        // MODULES
        // ==============================================
        $routes->get('modules', 'Api\ModuleController::index');
        $routes->post('modules', 'Api\ModuleController::create');
        $routes->get('modules/(:num)', 'Api\ModuleController::show/$1');
        $routes->put('modules/(:num)', 'Api\ModuleController::update/$1');
        $routes->delete('modules/(:num)', 'Api\ModuleController::delete/$1');
        $routes->get('applications/(:num)/modules', 'Api\ModuleController::getByApplication/$1');

        // ==============================================
        // ROLE PERMISSIONS
        // ==============================================
        $routes->get('roles/(:num)/permissions', 'Api\RolePermissionController::getRolePermissions/$1');
        $routes->post('role-permissions', 'Api\RolePermissionController::setPermissions');
        $routes->get(
            'check-access/(:num)/(:segment)/(:segment)',
            'Api\RolePermissionController::checkAccess/$1/$2/$3'
        );

        // ==============================================
        // USER MANAGEMENT
        // ==============================================
        $routes->get('users', 'Api\UserManagementController::index');
        $routes->get('users/(:num)', 'Api\UserManagementController::show/$1');
        $routes->put('users/(:num)', 'Api\UserManagementController::update/$1');
        $routes->delete('users/(:num)', 'Api\UserManagementController::delete/$1');

        $routes->post('users/assign-role', 'Api\UserManagementController::assignRole');
        $routes->post('users/remove-role', 'Api\UserManagementController::removeRole');

        // User Avatar
        $routes->post('users/(:num)/avatar', 'Api\UserManagementController::uploadAvatar/$1');
        $routes->delete('users/(:num)/avatar', 'Api\UserManagementController::removeAvatar/$1');
    });
});

// ======================================================
// INVENTORY MODULE
// ======================================================
$routes->group('api/inv', ['filter' => 'auth'], function ($routes) {

    // ==================================================
    // MATERIALS
    // ==================================================
    $routes->get('materials', 'Api\Inv\InvMaterialController::index');
    $routes->post('materials', 'Api\Inv\InvMaterialController::create');
    $routes->get('materials/(:num)', 'Api\Inv\InvMaterialController::show/$1');
    $routes->put('materials/(:num)', 'Api\Inv\InvMaterialController::update/$1');
    $routes->delete('materials/(:num)', 'Api\Inv\InvMaterialController::delete/$1');
    $routes->get('materials/low-stock', 'Api\Inv\InvMaterialController::lowStock');
    $routes->get('materials/by-type', 'Api\Inv\InvMaterialController::byType');

    // ==================================================
    // SUPPLIERS
    // ==================================================
    $routes->get('suppliers', 'Api\Inv\InvSupplierController::index');
    $routes->post('suppliers', 'Api\Inv\InvSupplierController::create');
    $routes->get('suppliers/international', 'Api\Inv\InvSupplierController::international');
    $routes->get('suppliers/local', 'Api\Inv\InvSupplierController::local');
    $routes->get('suppliers/(:num)', 'Api\Inv\InvSupplierController::show/$1');
    $routes->put('suppliers/(:num)', 'Api\Inv\InvSupplierController::update/$1');

    // ==================================================
    // CUSTOMERS
    // ==================================================
    $routes->get('customers', 'Api\Inv\InvCustomerController::index');
    $routes->post('customers', 'Api\Inv\InvCustomerController::create');
    $routes->get('customers/(:num)', 'Api\Inv\InvCustomerController::show/$1');
    $routes->put('customers/(:num)', 'Api\Inv\InvCustomerController::update/$1');

    $routes->post('customers/(:num)/contacts', 'Api\Inv\InvCustomerController::addContact/$1');
    $routes->post('customers/(:num)/addresses', 'Api\Inv\InvCustomerController::addAddress/$1');
    $routes->post('customers/(:num)/documents', 'Api\Inv\InvCustomerController::uploadDocument/$1');

    $routes->put('customers/(:num)/block', 'Api\Inv\InvCustomerController::block/$1');
    $routes->put('customers/(:num)/activate', 'Api\Inv\InvCustomerController::activate/$1');

    // ==================================================
    // CUSTOMER GROUPS
    // ==================================================
    $routes->get('customer-groups', 'Api\Inv\InvCustomerGroupController::index');
    $routes->post('customer-groups', 'Api\Inv\InvCustomerGroupController::create');

    // ==================================================
    // SUPPLIER EVALUATIONS
    // ==================================================
    $routes->get('supplier-evaluations', 'Api\Inv\InvSupplierEvaluationController::index');
    $routes->post('supplier-evaluations', 'Api\Inv\InvSupplierEvaluationController::create');

    $routes->get(
        'suppliers/(:num)/evaluations',
        'Api\Inv\InvSupplierEvaluationController::getSupplierHistory/$1'
    );

    // ==================================================
    // SUPPLIER MATERIALS
    // ==================================================
    $routes->get(
        'suppliers/(:num)/materials',
        'Api\Inv\InvSupplierMaterialController::bySupplier/$1'
    );

    $routes->post(
        'suppliers/(:num)/materials',
        'Api\Inv\InvSupplierMaterialController::addMaterial/$1'
    );

    $routes->get(
        'materials/(:num)/suppliers',
        'Api\Inv\InvSupplierMaterialController::byMaterial/$1'
    );

    // ==================================================
    // BUSINESS DOCUMENTS
    // ==================================================
    $routes->get('business-documents', 'Api\Inv\InvBusinessDocumentController::index');
    $routes->get('business-documents/expired', 'Api\Inv\InvBusinessDocumentController::expired');
    $routes->get('business-documents/expiring', 'Api\Inv\InvBusinessDocumentController::expiringSoon');

    $routes->put(
        'business-documents/(:num)/verify',
        'Api\Inv\InvBusinessDocumentController::verify/$1'
    );

    // ==================================================
    // STOCK
    // ==================================================
    $routes->get('stock', 'Api\Inv\InvStockController::index');
    $routes->get('stock/warehouse/(:num)', 'Api\Inv\InvStockController::byWarehouse/$1');
    $routes->get('stock/material/(:num)', 'Api\Inv\InvStockController::byMaterial/$1');
    $routes->get('stock/value', 'Api\Inv\InvStockController::stockValue');

    // ==================================================
    // STOCK MOVEMENTS
    // ==================================================
    $routes->get('movements', 'Api\Inv\InvStockMovementController::index');
    $routes->get('movements/material/(:num)', 'Api\Inv\InvStockMovementController::byMaterial/$1');

    // ==================================================
    // STOCK OPNAME
    // ==================================================
    $routes->get('opname', 'Api\Inv\InvStockOpnameController::index');
    $routes->post('opname', 'Api\Inv\InvStockOpnameController::create');
    $routes->get('opname/(:num)', 'Api\Inv\InvStockOpnameController::show/$1');

    $routes->put('opname/(:num)/start', 'Api\Inv\InvStockOpnameController::start/$1');

    $routes->post('opname/(:num)/items', 'Api\Inv\InvStockOpnameController::addItem/$1');

    $routes->put(
        'opname/(:num)/complete',
        'Api\Inv\InvStockOpnameController::complete/$1'
    );

    // ==================================================
    // DAMAGE REPORTS
    // ==================================================
    $routes->get('damage-reports', 'Api\Inv\InvDamageReportController::index');
    $routes->post('damage-reports', 'Api\Inv\InvDamageReportController::create');
    $routes->get('damage-reports/(:num)', 'Api\Inv\InvDamageReportController::show/$1');

    $routes->post(
        'damage-reports/(:num)/items',
        'Api\Inv\InvDamageReportController::addItem/$1'
    );

    $routes->put(
        'damage-reports/(:num)/dispose',
        'Api\Inv\InvDamageReportController::approveAndDispose/$1'
    );

    $routes->post(
        'damage-reports/(:num)/documents',
        'Api\Inv\InvDamageReportController::uploadDocument/$1'
    );

    // ==================================================
    // DOCUMENTS
    // ==================================================
    $routes->get('documents', 'Api\Inv\InvDocumentController::index');
    $routes->post('documents', 'Api\Inv\InvDocumentController::create');
    $routes->post('documents/(:num)/upload', 'Api\Inv\InvDocumentController::uploadFile/$1');
    $routes->get('documents/generate-number', 'Api\Inv\InvDocumentController::generateCustomsNumber');

    // ==================================================
    // CURRENCIES
    // ==================================================
    $routes->get('currencies', 'Api\Inv\InvCurrencyController::index');
    $routes->post('currencies', 'Api\Inv\InvCurrencyController::create');
    $routes->put('currencies/(:num)', 'Api\Inv\InvCurrencyController::update/$1');

    // ==================================================
    // EXCHANGE RATES
    // ==================================================
    $routes->get('exchange-rates', 'Api\Inv\InvExchangeRateController::index');
    $routes->post('exchange-rates', 'Api\Inv\InvExchangeRateController::create');
    $routes->get('exchange-rates/latest', 'Api\Inv\InvExchangeRateController::latest');

    // ==================================================
    // PURCHASE ORDERS
    // ==================================================
    $routes->get('purchase-orders', 'Api\Inv\InvPurchaseOrderController::index');
    $routes->post('purchase-orders', 'Api\Inv\InvPurchaseOrderController::create');
    $routes->get('purchase-orders/(:num)', 'Api\Inv\InvPurchaseOrderController::show/$1');
    $routes->put('purchase-orders/(:num)/approve', 'Api\Inv\InvPurchaseOrderController::approve/$1');

    // ==================================================
    // GOODS RECEIPTS
    // ==================================================
    $routes->get('goods-receipts', 'Api\Inv\InvGoodsReceiptController::index');
    $routes->post('goods-receipts', 'Api\Inv\InvGoodsReceiptController::create');
    $routes->get('goods-receipts/(:num)', 'Api\Inv\InvGoodsReceiptController::show/$1');

    // ==================================================
    // WAREHOUSES
    // ==================================================
    $routes->get('warehouses', 'Api\Inv\InvWarehouseController::index');
    $routes->post('warehouses', 'Api\Inv\InvWarehouseController::create');
});

// ======================================================
// ACCOUNTING MODULE
// ======================================================
$routes->group('api/acc', ['filter' => 'auth'], function ($routes) {

    // ==================================================
    // CHART OF ACCOUNTS
    // ==================================================
    $routes->get('coa', 'Api\Acc\AccCoaController::index');
    $routes->get('coa/tree', 'Api\Acc\AccCoaController::tree');
    $routes->post('coa', 'Api\Acc\AccCoaController::create');
    $routes->get('coa/(:num)', 'Api\Acc\AccCoaController::show/$1');

    // ==================================================
    // JOURNALS
    // ==================================================
    $routes->get('journals', 'Api\Acc\AccJournalController::index');
    $routes->post('journals', 'Api\Acc\AccJournalController::create');
    $routes->get('journals/(:num)', 'Api\Acc\AccJournalController::show/$1');

    $routes->put('journals/(:num)/post', 'Api\Acc\AccJournalController::post/$1');

    $routes->post(
        'journals/(:num)/reverse',
        'Api\Acc\AccJournalController::reverse/$1'
    );

    $routes->get(
        'journals/trial-balance',
        'Api\Acc\AccJournalController::trialBalance'
    );

    // ==================================================
    // CASH & BANK
    // ==================================================
    $routes->get('cash-banks', 'Api\Acc\AccCashBankController::index');
    $routes->post('cash-banks', 'Api\Acc\AccCashBankController::create');

    $routes->post(
        'cash-banks/receive',
        'Api\Acc\AccCashBankController::receivePayment'
    );

    $routes->post(
        'cash-banks/pay',
        'Api\Acc\AccCashBankController::makePayment'
    );

    // ==================================================
    // RECEIVABLES
    // ==================================================
    $routes->get('receivables', 'Api\Acc\AccReceivableController::index');
    $routes->post('receivables', 'Api\Acc\AccReceivableController::create');
    $routes->get('receivables/(:num)', 'Api\Acc\AccReceivableController::show/$1');

    $routes->post(
        'receivables/(:num)/payment',
        'Api\Acc\AccReceivableController::addPayment/$1'
    );

    // ==================================================
    // PAYABLES
    // ==================================================
    $routes->get('payables', 'Api\Acc\AccPayableController::index');
    $routes->post('payables', 'Api\Acc\AccPayableController::create');
    $routes->get('payables/(:num)', 'Api\Acc\AccPayableController::show/$1');

    $routes->post(
        'payables/(:num)/payment',
        'Api\Acc\AccPayableController::addPayment/$1'
    );

    // ==================================================
    // FIXED ASSETS
    // ==================================================
    $routes->get('fixed-assets', 'Api\Acc\AccFixedAssetController::index');
    $routes->post('fixed-assets', 'Api\Acc\AccFixedAssetController::create');
    $routes->get('fixed-assets/(:num)', 'Api\Acc\AccFixedAssetController::show/$1');

    $routes->post(
        'fixed-assets/(:num)/depreciate',
        'Api\Acc\AccFixedAssetController::depreciation/$1'
    );

    $routes->post(
        'fixed-assets/depreciate-all',
        'Api\Acc\AccFixedAssetController::runAllDepreciation'
    );

    // ==================================================
    // PAYMENT VOUCHERS
    // ==================================================
    $routes->get('payment-vouchers', 'Api\Acc\AccPaymentVoucherController::index');
    $routes->post('payment-vouchers', 'Api\Acc\AccPaymentVoucherController::create');
    $routes->get('payment-vouchers/(:num)', 'Api\Acc\AccPaymentVoucherController::show/$1');

    $routes->put(
        'payment-vouchers/(:num)/approve',
        'Api\Acc\AccPaymentVoucherController::approve/$1'
    );

    // ==================================================
    // RECONCILIATIONS
    // ==================================================
    $routes->get('reconciliations', 'Api\Acc\AccReconciliationController::index');
    $routes->post('reconciliations', 'Api\Acc\AccReconciliationController::create');
    $routes->get('reconciliations/(:num)', 'Api\Acc\AccReconciliationController::show/$1');

    $routes->put(
        'reconciliations/(:num)/complete',
        'Api\Acc\AccReconciliationController::complete/$1'
    );

    // ==================================================
    // REPORTS
    // ==================================================
    $routes->get('reports/trial-balance', 'Api\Acc\AccReportController::trialBalance');
    $routes->get('reports/income-statement', 'Api\Acc\AccReportController::incomeStatement');
    $routes->get('reports/balance-sheet', 'Api\Acc\AccReportController::balanceSheet');
    $routes->get('reports/ar-aging', 'Api\Acc\AccReportController::arAging');
    $routes->get('reports/ap-aging', 'Api\Acc\AccReportController::apAging');
    $routes->get('reports/fixed-assets', 'Api\Acc\AccReportController::fixedAssetSchedule');
    $routes->get('reports/cash-flow', 'Api\Acc\AccReportController::cashFlow');

    // ==================================================
    // BUDGETS
    // ==================================================
    $routes->get('budgets', 'Api\Acc\AccBudgetController::index');
    $routes->post('budgets', 'Api\Acc\AccBudgetController::create');
    $routes->get('budgets/(:num)', 'Api\Acc\AccBudgetController::show/$1');

    $routes->get(
        'budgets/(:num)/vs-actual',
        'Api\Acc\AccBudgetController::vsActual/$1'
    );

    // ==================================================
    // TAXES
    // ==================================================
    $routes->get('taxes', 'Api\Acc\AccTaxController::index');
    $routes->post('taxes', 'Api\Acc\AccTaxController::create');
    $routes->get('taxes/(:num)', 'Api\Acc\AccTaxController::show/$1');
});