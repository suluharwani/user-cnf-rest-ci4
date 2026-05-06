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
    
    // Protected routes (with JWT middleware)
    $routes->group('', ['filter' => 'auth'], function($routes) {
        
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
    });
});
