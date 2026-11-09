<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/api/admin/stats' => [[['_route' => 'api_admin_stats', '_controller' => 'App\\Controller\\AdminDashboardController::getStats'], null, ['GET' => 0], null, false, false, null]],
        '/api/me' => [[['_route' => 'api_me', '_controller' => 'App\\Controller\\AuthController::me'], null, ['GET' => 0], null, false, false, null]],
        '/api/avis' => [
            [['_route' => 'api_avis_public_list', '_controller' => 'App\\Controller\\AvisController::listPublic'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_avis_create', '_controller' => 'App\\Controller\\AvisController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/avis/en-attente' => [[['_route' => 'api_avis_pending_list', '_controller' => 'App\\Controller\\AvisController::listEnAttente'], null, ['GET' => 0], null, false, false, null]],
        '/api/commandes' => [
            [['_route' => 'api_commandes_list', '_controller' => 'App\\Controller\\CommandeController::list'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_commandes_create', '_controller' => 'App\\Controller\\CommandeController::create'], null, ['POST' => 0], null, false, false, null],
        ],
        '/api/menus' => [[['_route' => 'api_menus_list', '_controller' => 'App\\Controller\\MenuController::list'], null, ['GET' => 0], null, false, false, null]],
        '/api/register' => [[['_route' => 'api_register', '_controller' => 'App\\Controller\\SecurityController::register'], null, ['POST' => 0], null, false, false, null]],
        '/api/login_check' => [[['_route' => 'api_login_check'], null, null, null, false, false, null]],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/api/(?'
                    .'|avis/([^/]++)/moderation(*:39)'
                    .'|commandes/([^/]++)/statut(*:71)'
                    .'|menus/([^/]++)(*:92)'
                .')'
                .'|/_error/(\\d+)(?:\\.([^/]++))?(*:128)'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        39 => [[['_route' => 'api_avis_moderer', '_controller' => 'App\\Controller\\AvisController::moderer'], ['id'], ['PATCH' => 0], null, false, false, null]],
        71 => [[['_route' => 'api_commandes_update_statut', '_controller' => 'App\\Controller\\CommandeController::updateStatut'], ['id'], ['PATCH' => 0, 'PUT' => 1], null, false, false, null]],
        92 => [[['_route' => 'api_menus_show', '_controller' => 'App\\Controller\\MenuController::show'], ['id'], ['GET' => 0], null, false, true, null]],
        128 => [
            [['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
