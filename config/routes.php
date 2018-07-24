<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Core\Plugin;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

/**
 * The default class to use for all routes
 *
 * The following route classes are supplied with CakePHP and are appropriate
 * to set as the default:
 *
 * - Route
 * - InflectedRoute
 * - DashedRoute
 *
 * If no call is made to `Router::defaultRouteClass()`, the class used is
 * `Route` (`Cake\Routing\Route\Route`)
 *
 * Note that `Route` does not do any inflections on URLs which will result in
 * inconsistently cased URLs when used with `:plugin`, `:controller` and
 * `:action` markers.
 *
 * Cache: Routes are cached to improve performance, check the RoutingMiddleware
 * constructor in your `src/Application.php` file to change this behavior.
 *
 */
Router::defaultRouteClass(DashedRoute::class);

// Router::prefix('/auth', ['_namePrefix' => 'auth:'], function ($routes) {
//   $routes->resources('WvUser', [
//     'map' => ['login' => ['action' => 'login', 'method' => 'GET']]
//   ]);
// });

Router::scope('/', function (RouteBuilder $routes) {
    /**
     * Here, we are connecting '/' (base path) to a controller called 'Pages',
     * its action called 'display', and we pass a param to select the view file
     * to use (in this case, src/Template/Pages/home.ctp)...
     */
    $routes->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);

    $routes->post(
        '/auth/login/*',
        ['controller' => 'WvUser', 'action' => 'login']
    );

    $routes->post(
        '/auth/signup/*',
        ['controller' => 'WvUser', 'action' => 'signup']
    );

    $routes->post(
        '/auth/recover',
        ['controller' => 'WvUser', 'action' => 'forgotpassword']
    );

    $routes->post(
        '/post/submit/*',
        ['controller' => 'WvPost', 'action' => 'add']
    );

    $routes->post(
        '/files/submit/*',
        ['controller' => 'WvFileuploads', 'action' => 'add']
    );

    $routes->post(
        '/comments/submit/*',
        ['controller' => 'WvComments', 'action' => 'new']
    );

    $routes->post(
        '/favlocation/submit/*',
        ['controller' => 'WvFavLocation', 'action' => 'add']
    );

    $routes->post(
        '/activity/submit/*',
        ['controller' => 'WvActivitylog', 'action' => 'add']
    );

    $routes->post(
        '/polls/submit/*',
        ['controller' => 'WvUserPolls', 'action' => 'add']
    );

    $routes->post(
        '/user/access/*',
        ['controller' => 'WvUser', 'action' => 'updateaccess']
    );

    $routes->post(
        '/user/update/*',
        ['controller' => 'WvUser', 'action' => 'updateuserinfo']
    );

    $routes->post(
        '/user/verify/*',
        ['controller' => 'WvUser', 'action' => 'email_verification']
    );

    $routes->post(
        '/user/changepicture/*',
        ['controller' => 'WvUser', 'action' => 'changeProfilePicture']
    );

    $routes->post(
        '/favlocation/remove/*',
        ['controller' => 'WvFavLocation', 'action' => 'delete']
    );

    $routes->get(
        '/auth/logout/*',
        ['controller' => 'WvUser', 'action' => 'logout']
    );

    $routes->get(
        '/user/getinfo/*',
        ['controller' => 'WvUser', 'action' => 'getuserinfo']
    );

    $routes->get(
        '/post/get',
        ['controller' => 'WvPost', 'action' => 'getfeed']
    );

    $routes->get(
        '/post/get/:id',
        ['controller' => 'WvPost', 'action' => 'getpost']
    );

    $routes->get(
        '/comments/get/:postId',
        ['controller' => 'WvComments', 'action' => 'get']
    );

    $routes->get(
        '/favlocation/get/',
        ['controller' => 'WvFavLocation', 'action' => 'get']
    );
    /**
     * ...and connect the rest of 'Pages' controller's URLs.
     */
    $routes->connect('/pages/*', ['controller' => 'Pages', 'action' => 'display']);

    /**
     * Connect catchall routes for all controllers.
     *
     * Using the argument `DashedRoute`, the `fallbacks` method is a shortcut for
     *    `$routes->connect('/:controller', ['action' => 'index'], ['routeClass' => 'DashedRoute']);`
     *    `$routes->connect('/:controller/:action/*', [], ['routeClass' => 'DashedRoute']);`
     *
     * Any route class can be used with this method, such as:
     * - DashedRoute
     * - InflectedRoute
     * - Route
     * - Or your own route class
     *
     * You can remove these routes once you've connected the
     * routes you want in your application.
     */
    $routes->fallbacks(DashedRoute::class);
});

/**
 * Load all plugin routes. See the Plugin documentation on
 * how to customize the loading of plugin routes.
 */
Plugin::routes();
