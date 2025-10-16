<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Home → Login
$routes->get('/', 'Auth::login');

// ---------- Auth ----------
$routes->get('login',  'Auth::login');
$routes->post('login', 'Auth::doLogin');
$routes->get('logout', 'Auth::logout');

// ---------- Main Dashboard ----------
$routes->get('dashboard',          'Dashboard::index');
$routes->get('dashboard/refresh',  'Dashboard::refresh');

// ---------- Dashboards (CRUD + view + legacy remove) ----------
$routes->get ('dashboards',                   'Dashboards::index');            // list
$routes->get ('dashboards/new',               'Dashboards::create');           // form create
$routes->post('dashboards/store',             'Dashboards::store');            // save create
$routes->get ('dashboards/(:num)/edit',       'Dashboards::edit/$1');          // form edit
$routes->post('dashboards/(:num)/update',     'Dashboards::update/$1');        // save edit
$routes->post('dashboards/(:num)/delete',     'Dashboards::delete/$1');        // delete
$routes->get ('dashboards/(:num)',            'Dashboards::view/$1');          // open dashboard (redir to cameras mapping)
$routes->post('dashboards/remove',            'Dashboards::remove');           // (legacy) remove mapping item

// ---------- NVRs CRUD ----------
$routes->get ('nvrs',                 'Nvrs::index');
$routes->get ('nvrs/new',             'Nvrs::create');
$routes->post('nvrs/store',           'Nvrs::store');
$routes->get ('nvrs/(:num)/edit',     'Nvrs::edit/$1');
$routes->post('nvrs/(:num)/update',   'Nvrs::update/$1');
$routes->post('nvrs/(:num)/delete',   'Nvrs::delete/$1');

// ---------- Cameras ----------
$routes->get ('cameras',                'Cameras::index');          // list monitor per NVR (Shinobi)
$routes->post('cameras/assign',         'Cameras::assign');         // map monitor → dashboard
$routes->post('cameras/unassign',       'Cameras::unassign');       // hapus mapping by id

// (opsional) Mappings management – jika controllernya sudah ada
$routes->get ('cameras/mappings',             'Cameras::mappings');        // list mapping
$routes->post('cameras/mappings/update',      'Cameras::updateMapping');   // POST: id, alias, sort_order
$routes->post('cameras/mappings/delete',      'Cameras::deleteMapping');   // POST: id

// ---------- Users (superadmin only; local/ldap edit) ----------
$routes->get ('users',                'Users::index');
$routes->get ('users/new',            'Users::create');
$routes->post('users/store',          'Users::store');
$routes->get ('users/(:num)/edit',    'Users::edit/$1');
$routes->post('users/(:num)/update',  'Users::update/$1');
$routes->post('users/(:num)/delete',  'Users::delete/$1');

// DEBUGGING
$routes->get('debug/dashboard_check', 'Debug::dashboard_check');

// ---------- Videos ----------
$routes->get('videos',       'Videos::index');   // UI list rekaman
$routes->get('videos/data',  'Videos::data');    // proxy Get Videos Shinobi (JSON)
$routes->get('videos/monitors', 'Videos::monitors'); // dropdown camera per NVR

// ---------- Linked Account ----------
$routes->get('users/link/(:num)', 'AccountLinks::linkUI/$1');           // UI checklist
$routes->post('users/link/save', 'AccountLinks::saveLinks');            // Simpan link
$routes->get('account-switcher', 'AccountLinks::switcherPopup');        // HTML popup utk navbar
$routes->post('switch-as/(:num)', 'AccountLinks::switchAs/$1');         // Superadmin → child
$routes->post('switch-to-parent', 'AccountLinks::switchToParent');      // Child → parent (wajib password)
