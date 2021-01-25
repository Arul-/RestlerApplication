<?php

namespace Bootstrap\Container;

use Closure;
use Illuminate\Bus\BusServiceProvider;
use Illuminate\Bus\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Encryption\EncryptionServiceProvider;
use Illuminate\Foundation\Events\LocaleUpdated;

class Application extends Container
{

    const VERSION = '7';

    /**
     * The base path of the application installation.
     *
     * @var string
     */
    protected $basePath;
    /**
     * @var mixed
     */
    private $loadedConfigurations = [];
    /**
     * @var mixed
     */
    private $loadedProviders = [];
    /**
     * @var mixed
     */
    private $booted = false;

    /**
     * Create a new Lumen application instance.
     *
     * @param string|null $basePath
     *
     * @return void
     */
    public function __construct($basePath = null)
    {
        $this->basePath = $basePath;
        $this->bootstrapContainer();
        $this->registerEncrypterBindings();
        //$this->registerErrorHandling();
    }

    /**
     * Bootstrap the application container.
     *
     * @return void
     */
    protected function bootstrapContainer()
    {
        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance('path', $this->path());
    }

    /**
     * Get the path to the application "app" directory.
     *
     * @return string
     */
    public function path()
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'app';
    }

    public static function version()
    {
        return static::VERSION;
    }

    /**
     * Get the base path for the application.
     *
     * @param string|null $path
     *
     * @return string
     */
    public function basePath($path = null)
    {
        if (isset($this->basePath)) {
            return $this->basePath . ($path ? '/' . $path : $path);
        }
        if ($this->runningInConsole()) {
            $this->basePath = getcwd();
        } else {
            $this->basePath = realpath(getcwd() . '/../');
        }

        return $this->basePath($path);
    }

    /**
     * Determine if the application is running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
    {
        return php_sapi_name() == 'cli';
    }

    /**
     * Get the database path for the application.
     *
     * @return string
     */
    public function databasePath()
    {
        return database_path();
    }

    /**
     * Get the storage path for the application.
     *
     * @param string|null $path
     *
     * @return string
     */
    public function storagePath($path = null)
    {
        return storage_path($path);
    }

    /**
     * Detect the application's current environment.
     *
     * @param array|string $environments
     *
     * @return string
     */
    public function detectEnvironment($environments)
    {
        $args = isset($_SERVER['argv']) ? $_SERVER['argv'] : null;
        if (php_sapi_name() == 'cli' && !is_null($value = $this->getEnvironmentArgument($args))) {
            //running in console and env param is set
            return $this['env'] = head(array_slice(explode('=', $value), 1));
        } else {
            //running as the web app

            if ($environments instanceof Closure) {
                // If the given environment is just a Closure, we will defer the environment check
                // to the Closure the developer has provided, which allows them to totally swap
                // the webs environment detection logic with their own custom Closure's code.
                return $this['env'] = call_user_func($environments);
            } elseif (is_array($environments)) {
                foreach ($environments as $environment => $hosts) {
                    // To determine the current environment, we'll simply iterate through the possible
                    // environments and look for the host that matches the host for this request we
                    // are currently processing here, then return back these environment's names.
                    foreach ((array)$hosts as $host) {
                        if (str_is($host, gethostname())) {
                            return $this['env'] = $environment;
                        }
                    }
                }
            } elseif (is_string($environments)) {
                return $this['env'] = $environments;
            }
        }

        return $this['env'] = 'production';
    }

    /**
     * Get the environment argument from the console.
     *
     * @param array $args
     *
     * @return string|null
     */
    private function getEnvironmentArgument(array $args)
    {
        return array_first($args, function ($k, $v) {
            return starts_with($v, '--env');
        });
    }

    public function environment()
    {
        return $this['env'];
    }

    public function setLocale($locale)
    {
        $this['config']->set('app.locale', $locale);
        $this['translator']->setLocale($locale);
        $this['events']->dispatch(new LocaleUpdated($locale));
    }
    /**
     * Get the current application locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this['config']->get('app.locale');
    }


    /**
     * Bind the installation paths to the application.
     *
     * @param array $paths
     *
     * @return void
     */
    public function bindInstallPaths(array $paths)
    {
        $this->instance('path', realpath($paths['app']));

        // Here we will bind the install paths into the container as strings that can be
        // accessed from any point in the system. Each path key is prefixed with path
        // so that they have the consistent naming convention inside the container.
        foreach (array_except($paths, ['app']) as $key => $value) {
            $this->instance("path.{$key}", realpath($value));
        }
    }

    /**
     * Register an application error handler.
     *
     * @param Closure $callback
     *
     * @return void
     */
    public function error(Closure $callback)
    {
        $this['exception']->error($callback);
    }

    /**
     * Get Application Namespace
     *
     * @return int|string
     */
    public function getNamespace()
    {
        return getAppNamespace();
    }

    public function runningUnitTests(): bool
    {
        return false;
    }

    public function resourcePath($path = '')
    {
        return resource_path($path);
    }

    public function bootstrapPath($path = '')
    {
        return base_path('bootstrap' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Register container bindings for the application.
     *
     * @return void
     */
    protected function registerEncrypterBindings()
    {
        $this->singleton('encrypter', function () {
            return $this->loadComponent('app', EncryptionServiceProvider::class, 'encrypter');
        });
    }


    /**
     * Configure and load the given component and provider.
     *
     * @param  string  $config
     * @param  array|string  $providers
     * @param  string|null  $return
     * @return mixed
     */
    public function loadComponent($config, $providers, $return = null)
    {
        $this->configure($config);

        foreach ((array) $providers as $provider) {
            $this->register($provider);
        }

        return $this->make($return ?: $config);
    }

    /**
     * Load a configuration file into the application.
     *
     * @param  string  $name
     * @return void
     */
    public function configure($name)
    {
        if (isset($this->loadedConfigurations[$name])) {
            return;
        }

        $this->loadedConfigurations[$name] = true;

        $path = $this->getConfigurationPath($name);

        if ($path) {
            $this->make('config')->set($name, require $path);
        }
    }

    /**
     * Get the path to the given configuration file.
     *
     * If no name is provided, then we'll return the path to the config folder.
     *
     * @param  string|null  $name
     * @return string
     */
    public function getConfigurationPath($name = null)
    {
        if (! $name) {
            $appConfigDir = $this->basePath('config').'/';

            if (file_exists($appConfigDir)) {
                return $appConfigDir;
            } elseif (file_exists($path = __DIR__.'/../config/')) {
                return $path;
            }
        } else {
            $appConfigPath = $this->basePath('config').'/'.$name.'.php';

            if (file_exists($appConfigPath)) {
                return $appConfigPath;
            } elseif (file_exists($path = __DIR__.'/../config/'.$name.'.php')) {
                return $path;
            }
        }
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \Illuminate\Support\ServiceProvider|string  $provider
     * @return void
     */
    public function register($provider)
    {
        if (! $provider instanceof ServiceProvider) {
            $provider = new $provider($this);
        }

        if (array_key_exists($providerName = get_class($provider), $this->loadedProviders)) {
            return;
        }

        $this->loadedProviders[$providerName] = $provider;

        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        if ($this->booted) {
            $this->bootProvider($provider);
        }
    }

    /**
     * Boot the given service provider.
     *
     * @param  \Illuminate\Support\ServiceProvider  $provider
     * @return mixed
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            return $this->call([$provider, 'boot']);
        }
    }
}
