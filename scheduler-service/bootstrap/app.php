<?php

require_once __DIR__.'/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();

$app->withEloquent();

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or you can make another file.
|
*/

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

/*
|--------------------------------------------------------------------------
| Register Config Files
|--------------------------------------------------------------------------
|
| Now we will register the "app" configuration file. If the file exists in
| your configuration directory it will be loaded; otherwise, we'll load
| the default version. You may register other files below as needed.
|
*/

$app->configure('app');
$app->configure('logging');
$app->configure('database');

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that run before and after each request into a
| route or middleware that'll be assigned to some specific routes.
|
*/

$app->middleware([
    App\Http\Middleware\Cors::class,
    App\Http\Middleware\RequestLogger::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|

*/
// Ensure migration services are available for local/dev console operations
if ($app->environment('local') || PHP_SAPI === 'cli') {
    $app->register(Illuminate\Database\MigrationServiceProvider::class);
}

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__.'/../routes/web.php';
});

/*
|--------------------------------------------------------------------------
| Set up in memory database if configured
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can all be added to
| the application. This will provide all of the URLs the application
| can respond to, as well as the controllers that may handle them.
|
*/

// Skip automatic bootstrap migrations while running artisan to avoid recursion
$isArtisan = isset($_SERVER['argv']) && is_array($_SERVER['argv']) && in_array('artisan', $_SERVER['argv']);

// If using a file-backed sqlite DB, optionally recreate it on boot when flag is set
if (env('DB_CONNECTION') === 'sqlite' && env('DB_DATABASE') !== ':memory:' && $app->environment('local') && ! $isArtisan) {

    $dbRel = env('DB_DATABASE', 'database/database.sqlite');
    $dbPath = base_path($dbRel);
    $needMigrate = false;

    // If DB_RECREATE_ON_BOOT is set true, delete and recreate the file
    if (filter_var(env('DB_RECREATE_ON_BOOT', false), FILTER_VALIDATE_BOOLEAN) || !file_exists($dbPath)) {
        @mkdir(dirname($dbPath), 0755, true);
        if (file_exists($dbPath)) {
            @unlink($dbPath);
        }
        @touch($dbPath);
        $needMigrate = true;
    }

    // Ensure runtime config points to the absolute path
    $app['config']->set('database.connections.sqlite.database', $dbPath);

    // If file was just created (or recreated), run migrations
    if (file_exists($dbPath) && $needMigrate) {
        try {
            $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
            try {
                $kernel->call('migrate:install');
            } catch (\Throwable $e) {
                error_log('migrate:install failed: ' . $e->getMessage());
            }
            $kernel->call('migrate', ['--force' => true, '--path' => 'database/migrations']);
            if (file_exists(database_path('seeders/DatabaseSeeder.php'))) {
                $kernel->call('db:seed', ['--force' => true]);
            }
        } catch (\Throwable $e) {
            error_log('Bootstrap file DB migrate failed: ' . $e->getMessage());
        }
    }
}

return $app;
