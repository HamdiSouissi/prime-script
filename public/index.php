<?php
declare(strict_types=1);

/**
 * Prime Script — point d'entrée unique (front controller).
 *
 * Toutes les requêtes passent par ce fichier grâce à public/.htaccess.
 * Les dossiers app/, config/, database/ et storage/ sont hors du dossier public.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

use App\Core\Router;

$router = new Router();

// ------------------------------------------------------------------ Authentification
$router->get('/login', 'AuthController@showLogin', ['guest']);
$router->post('/login', 'AuthController@login', ['guest']);
$router->post('/deconnexion', 'AuthController@logout', ['auth']);

// ------------------------------------------------------------------ Commercial : parcours d'appel
$router->get('/', 'HomeController@index', ['auth']);                            // accueil + nouvel appel
$router->post('/appel/contexte', 'CallController@start', ['auth']);              // écran 1 → contexte
$router->get('/appel/presentation', 'CallController@presentation', ['auth']);   // écran 2
$router->get('/appel/services', 'CallController@services', ['auth']);           // écran 3
$router->get('/appel/speech', 'CallController@speech', ['auth']);               // écran 4
$router->get('/appel/objections', 'CallController@objections', ['auth']);       // panneau latéral (AJAX + secours)
$router->get('/appel/terminer', 'CallController@showEnd', ['auth']);            // écran 5
$router->post('/appel/terminer', 'CallController@end', ['auth']);               // enregistrement
$router->get('/appel/enregistre', 'CallController@saved', ['auth']);            // confirmation
$router->post('/appel/nouveau', 'CallController@reset', ['auth']);             // réinitialise le contexte
$router->get('/historique', 'HistoryController@index', ['auth']);               // mes appels
$router->get('/historique/export.csv', 'HistoryController@export', ['auth']);

// ------------------------------------------------------------------ Administration
$router->get('/admin', 'Admin\DashboardController@index', ['admin']);

// Blocs de speech
$router->get('/admin/blocs', 'Admin\BlockController@index', ['admin']);
$router->get('/admin/blocs/nouveau', 'Admin\BlockController@create', ['admin']);
$router->post('/admin/blocs', 'Admin\BlockController@store', ['admin']);
$router->get('/admin/blocs/{id}/modifier', 'Admin\BlockController@edit', ['admin']);
$router->post('/admin/blocs/{id}/modifier', 'Admin\BlockController@update', ['admin']);
$router->post('/admin/blocs/{id}/dupliquer', 'Admin\BlockController@duplicate', ['admin']);
$router->post('/admin/blocs/{id}/basculer', 'Admin\BlockController@toggle', ['admin']);
$router->post('/admin/blocs/{id}/deplacer', 'Admin\BlockController@move', ['admin']);
$router->post('/admin/blocs/{id}/supprimer', 'Admin\BlockController@destroy', ['admin']);
$router->get('/admin/blocs/{id}/apercu', 'Admin\BlockController@preview', ['admin']);
$router->get('/admin/blocs/{id}/historique', 'Admin\BlockController@versions', ['admin']);
$router->post('/admin/blocs/{id}/restaurer', 'Admin\BlockController@restore', ['admin']);

// Objections
$router->get('/admin/objections', 'Admin\ObjectionController@index', ['admin']);
$router->get('/admin/objections/nouveau', 'Admin\ObjectionController@create', ['admin']);
$router->post('/admin/objections', 'Admin\ObjectionController@store', ['admin']);
$router->get('/admin/objections/{id}/modifier', 'Admin\ObjectionController@edit', ['admin']);
$router->post('/admin/objections/{id}/modifier', 'Admin\ObjectionController@update', ['admin']);
$router->post('/admin/objections/{id}/basculer', 'Admin\ObjectionController@toggle', ['admin']);
$router->post('/admin/objections/{id}/deplacer', 'Admin\ObjectionController@move', ['admin']);
$router->post('/admin/objections/{id}/supprimer', 'Admin\ObjectionController@destroy', ['admin']);

// Secteurs / étapes / services (taxonomies)
$router->get('/admin/secteurs', 'Admin\TaxonomyController@sectors', ['admin']);
$router->get('/admin/etapes', 'Admin\TaxonomyController@stages', ['admin']);
$router->get('/admin/services', 'Admin\TaxonomyController@services', ['admin']);
$router->post('/admin/taxonomie/{kind}', 'Admin\TaxonomyController@store', ['admin']);
$router->post('/admin/taxonomie/{kind}/{id}', 'Admin\TaxonomyController@update', ['admin']);
$router->post('/admin/taxonomie/{kind}/{id}/basculer', 'Admin\TaxonomyController@toggle', ['admin']);
$router->post('/admin/taxonomie/{kind}/{id}/deplacer', 'Admin\TaxonomyController@move', ['admin']);
$router->post('/admin/taxonomie/{kind}/{id}/supprimer', 'Admin\TaxonomyController@destroy', ['admin']);

// Utilisateurs
$router->get('/admin/utilisateurs', 'Admin\UserController@index', ['admin']);
$router->get('/admin/utilisateurs/nouveau', 'Admin\UserController@create', ['admin']);
$router->post('/admin/utilisateurs', 'Admin\UserController@store', ['admin']);
$router->get('/admin/utilisateurs/{id}/modifier', 'Admin\UserController@edit', ['admin']);
$router->post('/admin/utilisateurs/{id}/modifier', 'Admin\UserController@update', ['admin']);
$router->post('/admin/utilisateurs/{id}/basculer', 'Admin\UserController@toggle', ['admin']);
$router->post('/admin/utilisateurs/{id}/mot-de-passe', 'Admin\UserController@resetPassword', ['admin']);

// Journal des appels
$router->get('/admin/appels', 'Admin\CallController@index', ['admin']);
$router->get('/admin/appels/export.csv', 'Admin\CallController@export', ['admin']);

// ------------------------------------------------------------------ Divers
$router->get('/profil', 'ProfileController@index', ['auth']);
$router->post('/profil/mot-de-passe', 'ProfileController@updatePassword', ['auth']);

$router->dispatch();
