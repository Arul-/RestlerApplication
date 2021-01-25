<?php
define('APP_START', microtime(true));
/*
|--------------------------------------------------------------------------
| Register The Composer Auto Loader && Lazy load the DB
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/
define('BASE', dirname(__DIR__));

require BASE . '/vendor/autoload.php';

use Bootstrap\Config\Config;
use Bootstrap\Container\Application;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\Capsule\Manager as Database;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Pagination\Paginator;
use Illuminate\Queue\Capsule\Manager as Queue;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Livewire\Commands\ComponentParser;
use Livewire\HydrationMiddleware\CallHydrationHooks;
use Livewire\HydrationMiddleware\CallPropertyHydrationHooks;
use Livewire\HydrationMiddleware\HashDataPropertiesForDirtyDetection;
use Livewire\HydrationMiddleware\HydratePublicProperties;
use Livewire\HydrationMiddleware\NormalizeComponentPropertiesForJavaScript;
use Livewire\HydrationMiddleware\NormalizeServerMemoSansDataForJavaScript;
use Livewire\HydrationMiddleware\PerformActionCalls;
use Livewire\HydrationMiddleware\PerformDataBindingUpdates;
use Livewire\HydrationMiddleware\PerformEventEmissions;
use Livewire\HydrationMiddleware\RenderView;
use Livewire\HydrationMiddleware\SecureHydrationWithChecksum;
use Livewire\LifecycleManager;
use Livewire\LivewireBladeDirectives;
use Livewire\LivewireComponentsFinder;
use Livewire\LivewireManager;
use Livewire\LivewireTagCompiler;
use Livewire\LivewireViewCompilerEngine;
use Luracast\Restler\Defaults;
use Luracast\Restler\MediaTypes\Html;
use Luracast\Restler\UI\Forms;
use Luracast\Restler\UI\FormStyles;


/*
|--------------------------------------------------------------------------
| Some of the commonly expected functions
|--------------------------------------------------------------------------
*/

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param string $make
     * @param array $parameters
     *
     * @return mixed|Application
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    function app($make = null, $parameters = [])
    {
        if (is_null($make)) {
            return Application::getInstance();
        }

        return Application::getInstance()->make($make, $parameters);
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable. Supports boolean, empty and null.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            return value($default);
        }
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }
        if (strlen($value) > 1 && Str::startsWith($value, '"') && Str::endsWith($value, '"')) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

if (!function_exists('getAppNamespace')) {
    function getAppNamespace()
    {
        $composer = json_decode(file_get_contents(BASE . '/composer.json'), true);
        foreach ((array)data_get($composer, 'autoload.psr-4') as $namespace => $path) {
            foreach ((array)$path as $pathChoice) {
                if (realpath(BASE . '/' . 'app') == realpath(BASE . '/' . $pathChoice)) {
                    return $namespace;
                }
            }
        }
        throw new RuntimeException("Unable to detect application namespace.");
    }
}

$app = new Application();

/*
|--------------------------------------------------------------------------
| Detect The Application Environment
|--------------------------------------------------------------------------
|
| Laravel Database takes a dead simple approach to your application environments
| so you can create a .env file from .env.example and specify environment under
| APP_ENV otherwise production is assumed
|
*/
if (file_exists(BASE . '/.env')) {
    $dotenv = Dotenv\Dotenv::createMutable(BASE);
    $dotenv->load();
}

$env = $app->detectEnvironment(function () {
    return getenv('APP_ENV') ?: 'development';
});

$app['app'] = $app;
Facade::setFacadeApplication($app);

/*
|--------------------------------------------------------------------------
| Bind Paths
|--------------------------------------------------------------------------
|
| Here we are binding the paths configured in paths.php to the app. You
| should not be changing these here. If you need to change these you
| may do so within the paths.php file and they will be bound here.
|
*/

$app->bindInstallPaths(require __DIR__ . '/paths.php');

if (!function_exists('base_path')) {
    function base_path($path = '')
    {
        return app('path.base') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}
if (!function_exists('app_path')) {
    function app_path($path = '')
    {
        return base_path('app' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}
if (!function_exists('resource_path')) {
    function resource_path($path = '')
    {
        return base_path('resources' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('storage_path')) {
    function storage_path($path = '')
    {
        return app('path.storage') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('config_path')) {
    function config_path($path = '')
    {
        return app('path.config') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('database_path')) {
    function database_path($path = '')
    {
        return app('path.database') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('public_path')) {
    function public_path($path = '')
    {
        return app('path.public') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

$app->instance('config', new Config(app('path.config'), $env));

$app->singleton('events', function () use ($app) {
    return new Dispatcher($app);
});

$app->singleton('dispatcher', function () use ($app) {
    return $app['events'];
});

$app->singleton('Illuminate\Contracts\Events\Dispatcher', function () use ($app) {
    return $app['events'];
});


$app->singleton('files', function () use ($app) {
    return new Filesystem();
});

$app->singleton('cache', function () use ($app) {
    return new CacheManager($app);
});

$app->singleton('db', function () use ($app) {
    $config = $app['config'];
    $default = $config['database.default'];
    $fetch = $config['database.fetch'];
    $db = new Database($app);
    $config['database.fetch'] = $fetch;
    $config['database.default'] = $default;
    $db->addConnection($config['database.connections'][$default]);
    $db->setEventDispatcher($app['events']);
    $db->setAsGlobal();
    $db->bootEloquent();

    $db->getDatabaseManager()->extend('mongodb', function ($config, $name) {
        $config['name'] = $name;
        return new Jenssegers\Mongodb\Connection($config);
    });
    return $db->getDatabaseManager();
});

$app->singleton('queue', function () use ($app) {
    $config = $app['queue'];
    $default = $config['queue.default'];
    $connections = $config['queue.connections'];
    $config['queue.default'] = $default;
    $config['queue.connections'] = $connections;
    $queue = new Queue;
    $queue->addConnection($config['queue.connections'][$default]);
    $queue->setAsGlobal();

    return $queue->getQueueManager();
});

$app->singleton('queue.connection', function () use ($app) {
    return $app['queue']->connection();
});

if (!function_exists('config')) {
    function config($path, $default = null)
    {
        if (is_string($path)) {
            return $app['config'][$path] ?? $default;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Pagination Support
|--------------------------------------------------------------------------
*/
Paginator::currentPathResolver(function () {
    return strtok($_SERVER["REQUEST_URI"], '?');
});

Paginator::currentPageResolver(function ($pageName = 'page') {
    if (isset($_REQUEST[$pageName])) {
        $page = $_REQUEST[$pageName];
        if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int)$page >= 1) {
            return $page;
        }
    }

    return 1;
});

/*
|--------------------------------------------------------------------------
| Redis Support
|--------------------------------------------------------------------------
*/
$app->singleton('redis', function () use ($app) {
    return new Illuminate\Redis\Database($app['config']['database.redis']);
});

/*
|--------------------------------------------------------------------------
| Register The Aliased Auto Loader
|--------------------------------------------------------------------------
|
| We register an auto-loader "before" the Composer loader that can load
| aliased classes with out their namespaces. We'll add it to the stack here.
|
*/

spl_autoload_register(function ($className) use ($app) {
    if (Model::class === $className) {
        include __DIR__ . '/../vendor/illuminate/database/Eloquent/Model.php';
        $app['db'];
        return true;
    }
    if (Jenssegers\Mongodb\Eloquent\Model::class === $className) {
        include __DIR__ . '/../vendor/jenssegers/mongodb/src/Jenssegers/Mongodb/Eloquent/Model.php';
        $app['db'];
        return true;
    }
    if (isset($app['config']['app.aliases'][$className])) {
        $app['db']; //lazy initialization of DB
        return class_alias($app['config']['app.aliases'][$className], $className);
    }

    return false;
}, true, true);
/*
|--------------------------------------------------------------------------
| Configure Restler to adapt to Laravel structure
|--------------------------------------------------------------------------
*/
Html::$viewPath = base_path('resources/views');
Defaults::$cacheDirectory = $app['config']['cache.path'];
Defaults::$productionMode = 'production' == getenv('APP_ENV') ?: 'development';

Html::$template = 'blade';
Forms::$style = FormStyles::$bootstrap3;

include BASE . '/routes/api.php';

function request()
{
    global $currentRequest;
    if (!$currentRequest) {
        $currentRequest = ServerRequest::fromGlobals();
    }
    return $currentRequest;
}

function csrf_token()
{
    return '283472938jsdsjd984734h';
}

if (!function_exists('view')) {
    /**
     * Get the evaluated view contents for the given view.
     *
     * @param string|null $view
     * @param \Illuminate\Contracts\Support\Arrayable|array $data
     * @param array $mergeData
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    function view($view = null, $data = [], $mergeData = [])
    {
        static $factory = null;
        if (!$factory) {
            $app = app();
            $filesystem = new Filesystem();
            $compiler = new BladeCompiler($filesystem, Html::$cacheDirectory ?? Defaults::$cacheDirectory);
            $engine = new CompilerEngine($compiler, $filesystem);

            $app->instance('files', $filesystem);
            $app->instance('blade', $engine);
            $app->instance('blade.compiler', $compiler);
            $app->instance('view.engine.resolver', $resolver = new EngineResolver());

            $resolver->register('file', function () use($app) {
                return new FileEngine($app['files']);
            });
            $resolver->register('php', function () use($app) {
                return new PhpEngine($app['files']);
            });
            $resolver->register('blade', function () use($app) {
                return new CompilerEngine($app['blade.compiler'], $app['files']);
            });

            $app->instance('view.finder', $finder = new FileViewFinder($filesystem, [Html::$viewPath]));
            $factory = new Factory($resolver, $finder, $app['events']);
            $factory->setContainer($app);
            $factory->share('app', $app);
            $app->instance('view', $factory);
        }
        if (0 === func_num_args()) {
            return $factory;
        }
        return $factory->make($view, (array)$data, $mergeData);
        $path = $finder->find($view);
    }
}

$app->singleton('livewire', function () use ($app) {
    $livewire = new LivewireManager();
    // We will generate a manifest file so we don't have to do the lookup every time.
    $defaultManifestPath = $livewire->isOnVapor()
        ? '/tmp/storage/bootstrap/cache/livewire-components.php'
        : $app->bootstrapPath('cache/livewire-components.php');
    $app->singleton(LivewireComponentsFinder::class, function () use ($defaultManifestPath) {
        return new LivewireComponentsFinder(
            new Filesystem,
            config('livewire.manifest_path') ?: $defaultManifestPath,
            ComponentParser::generatePathFromNamespace(
                config('livewire.class_namespace', 'App\\Http\\Livewire')
            )
        );
    });
    view(); //initialize view
    $compiler = app('blade.compiler');
    $resolver = app('view.engine.resolver');
    $resolver->register(
        'blade',
        function () use ($compiler) {
            if (class_exists(\Facade\Ignition\IgnitionServiceProvider::class)) {
                return new CompilerEngineForIgnition($compiler);
            }
            return new LivewireViewCompilerEngine($compiler);
        }
    );
    $compiler->directive('this', [LivewireBladeDirectives::class, 'this']);
    $compiler->directive('entangle', [LivewireBladeDirectives::class, 'entangle']);
    $compiler->directive('livewire', [LivewireBladeDirectives::class, 'livewire']);
    $compiler->directive('livewireStyles', [LivewireBladeDirectives::class, 'livewireStyles']);
    $compiler->directive('livewireScripts', [LivewireBladeDirectives::class, 'livewireScripts']);
    if (method_exists($compiler, 'precompiler')) {
        $compiler->precompiler(function ($string) {
            return app(LivewireTagCompiler::class)->compile($string);
        });
    }

    LifecycleManager::registerHydrationMiddleware([

        /* This is the core middleware stack of Livewire. It's important */
        /* to understand that the request goes through each class by the */
        /* order it is listed in this array, and is reversed on response */
        /*                                                               */
        /* ↓    Incoming Request                  Outgoing Response    ↑ */
        /* ↓                                                           ↑ */
        /* ↓    Secure Stuff                                           ↑ */
        /* ↓ */ SecureHydrationWithChecksum::class, /* --------------- ↑ */
        /* ↓ */ NormalizeServerMemoSansDataForJavaScript::class, /* -- ↑ */
        /* ↓ */ HashDataPropertiesForDirtyDetection::class, /* ------- ↑ */
        /* ↓                                                           ↑ */
        /* ↓    Hydrate Stuff                                          ↑ */
        /* ↓ */ HydratePublicProperties::class, /* ------------------- ↑ */
        /* ↓ */ CallPropertyHydrationHooks::class, /* ---------------- ↑ */
        /* ↓ */ CallHydrationHooks::class, /* ------------------------ ↑ */
        /* ↓                                                           ↑ */
        /* ↓    Update Stuff                                           ↑ */
        /* ↓ */ PerformDataBindingUpdates::class, /* ----------------- ↑ */
        /* ↓ */ PerformActionCalls::class, /* ------------------------ ↑ */
        /* ↓ */ PerformEventEmissions::class, /* --------------------- ↑ */
        /* ↓                                                           ↑ */
        /* ↓    Output Stuff                                           ↑ */
        /* ↓ */ RenderView::class, /* -------------------------------- ↑ */
        /* ↓ */ NormalizeComponentPropertiesForJavaScript::class, /* - ↑ */

    ]);

    LifecycleManager::registerInitialDehydrationMiddleware([

        /* Initial Response */
        /* ↑ */ [SecureHydrationWithChecksum::class, 'dehydrate'],
        /* ↑ */ [NormalizeServerMemoSansDataForJavaScript::class, 'dehydrate'],
        /* ↑ */ [HydratePublicProperties::class, 'dehydrate'],
        /* ↑ */ [CallPropertyHydrationHooks::class, 'dehydrate'],
        /* ↑ */ [CallHydrationHooks::class, 'initialDehydrate'],
        /* ↑ */ [RenderView::class, 'dehydrate'],
        /* ↑ */ [NormalizeComponentPropertiesForJavaScript::class, 'dehydrate'],

    ]);

    LifecycleManager::registerInitialHydrationMiddleware([

        [CallHydrationHooks::class, 'initialHydrate'],

    ]);
    return $livewire;
});
