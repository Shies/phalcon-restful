<?php

/**
 * Small Micro application to run simple/rest based applications
 *
 * @package ApplicationMicro
 * @version 1.0
 * @link http://docs.phalconphp.com/en/latest/reference/micro.html
 * @example
 * $app = new Micro();
 * $app->get('/api/looks/1', function() { echo "Hi"; });
 * $app->finish(function() { echo "Finished"; });
 * $app->run();
 */

namespace Engine;

use Phalcon\DI;
use Phalcon\Db\Profiler as DatabaseProfiler;
use Phalcon\Cache\Frontend\Data as CacheData;
use Phalcon\Cache\Frontend\Output as CacheOutput;
use Phalcon\Registry;
use Phalcon\Mvc\Micro as MvcMicro;
use Phalcon\Mvc\Model\Manager as ModelsManager;
use Phalcon\Mvc\Model\Transaction\Manager as TxManager;
use Phalcon\Loader;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\File;
use Phalcon\Logger\Formatter\Line as FormatterLine;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Http\Response;
use Interfaces\IRun as IRun;

class Micro extends MvcMicro implements IRun
{
    /**
     * Application configuration.
     *
     * @var Config
     */
    protected $_config;
    /**
     * Pages that doesn't require authentication
     * @var array
     */
    protected $_noAuthPages;
    /**
     * Loaders for different modes.
     *
     * @var array
     */
    private $_loaders =
        [
            'database',
            'cache',
            'engine'
        ];

    /**
     * Constructor of the App
     */
    public function __construct()
    {
        $di = new DI\FactoryDefault();
        $this->_config = config('config');
        $registry = new Registry();
        $registry->directories = (object)[
            'modules' => APP_PATH . '/modules/',
            'engine' => APP_PATH . '/engine/',
            'library' => APP_PATH . '/library/'
        ];
        $di->set('registry', $registry);
        $di->setShared('config', $this->_config);
        $eventsManager = new EventsManager();
        $this->setEventsManager($eventsManager);

        $this->_initLogger($di, $this->_config);
        $this->_initLoader($di, $this->_config, $eventsManager);

        foreach ($this->_loaders as $service) {
            $serviceName = ucfirst($service);
            $eventsManager->fire('init:before' . $serviceName, null);
            $result = $this->{'_init' . $serviceName}($di, $this->_config, $eventsManager);
            $eventsManager->fire('init:after' . $serviceName, $result);
        }
        $di->setShared('eventsManager', $eventsManager);
        $this->_noAuthPages = array();
        $this->setRoutes();
    }


    /**
     * Set Routes\Handlers for the application
     *
     *
     * @throws \Exception
     * @internal param File $file
     * @internal param File $file thats array of routes to load
     */
    public function setRoutes()
    {
        $routes = config('routes');
        if (!empty($routes)) {
            foreach ($routes as $obj) {
                $obj = (array)$obj;
                // Which pages are allowed to skip authentication
                if (isset($obj['authentication']) && $obj['authentication'] === false) {

                    $method = strtolower($obj['method']);

                    if (!isset($this->_noAuthPages[$method])) {
                        $this->_noAuthPages[$method] = array();
                    }

                    $this->_noAuthPages[$method][] = $obj['route'];
                }

                $action = $obj['handler'][1];
                $control = $obj['handler'][0];
                $controllerName = class_exists($control) ? $control : false;

                if (!$controllerName) {
                    throw new \Exception("Wrong controller name in routes ({$control})");
                }

                $controller = new $controllerName;
                $controllerAction = $action;

                switch ($obj['method']) {
                    case 'get':
                        $this->get($obj['route'], array($controller, $controllerAction));
                        break;
                    case 'post':
                        $this->post($obj['route'], array($controller, $controllerAction));
                        break;
                    case 'delete':
                        $this->delete($obj['route'], array($controller, $controllerAction));
                        break;
                    case 'put':
                        $this->put($obj['route'], array($controller, $controllerAction));
                        break;
                    case 'head':
                        $this->head($obj['route'], array($controller, $controllerAction));
                        break;
                    case 'options':
                        $this->options($obj['route'], array($controller, $controllerAction));
                        break;
                    case 'patch':
                        $this->patch($obj['route'], array($controller, $controllerAction));
                        break;
                    default:
                        break;
                }

            }
        }
    }

    /**
     * Set events to be triggered before/after certain stages in Micro App
     *
     * @param \Phalcon\Events\Manager $events
     * @internal param object $event events to add
     */
    public function setEvents(\Phalcon\Events\Manager $events)
    {
        $this->setEventsManager($events);
    }

    /**
     *
     */
    public function getUnauthenticated()
    {
        return $this->_noAuthPages;
    }

    /**
     * Main run block that executes the micro application
     *
     */
    public function run()
    {
        // Handle any routes not found
        $this->notFound(function () {
            $response = new Response();
            $response->setStatusCode(404, 'Not Found')->sendHeaders();
            $response->setContent('Page doesn\'t exist.');
            $response->send();
        });
        $this->handle();
    }

    /**
     * Init logger.
     *
     * @param DI $di Dependency Injection.
     * @param Config $config Config object.
     *
     * @return void
     */
    protected function _initLogger($di, $config)
    {
        if ($config->logger->enabled) {
            $di->set(
                'logger',
                function ($file = 'errors', $format = null) use ($config) {
                    $logger =  write_log($file, "");
                    $formatter = new FormatterLine(($format ? $format : $config->logger->format));
                    $logger->setFormatter($formatter);
                    return $logger;
                },
                false
            );
        }
    }

    /**
     * Init loader.
     *
     * @param DI $di Dependency Injection.
     * @param Config $config Config object.
     * @param EventsManager $eventsManager Event manager.
     *
     * @return Loader
     */
    protected function _initLoader($di, $config, $eventsManager)
    {
        // Add all required namespaces and modules.
        $registry = $di->get('registry');
        $namespaces = [];

        $namespaces['Controllers'] = $registry->directories->modules . '/controller/';
        $namespaces['Models'] = $registry->directories->modules . '/model/';
        $namespaces['Engine'] = $registry->directories->engine;
        $dirs['libraryDir'] = $registry->directories->library;
        $dirs['engineDir'] = $registry->directories->engine;
        $dirs['modulesDir'] = $registry->directories->modules;

        $loader = new Loader();
        $loader->registerDirs($dirs);
        $loader->registerNamespaces($namespaces);

        $loader->register();
        $di->set('loader', $loader);
        return $loader;
    }

    /**
     * Init router.
     *
     * @param DI $di Dependency Injection.
     * @param Config $config Config object.
     *
     * @return Router
     */
    protected function _initRouter($di, $config)
    {
        $defaultModuleName = ucfirst(Application::SYSTEM_DEFAULT_MODULE);

        $cacheData = $di->get('cacheData');
        $router = $cacheData->get('router_data');

        if ($config->debug || $router === null) {
            $saveToCache = ($router === null);

            $modules = $di->get('registry')->modules;

            $router = new RouterAnnotations(true);
            $router->removeExtraSlashes(true);
            $router->setDefaultModule(Application::SYSTEM_DEFAULT_MODULE);
            $router->setDefaultNamespace(ucfirst(Application::SYSTEM_DEFAULT_MODULE) . '\Controllers');
            $router->setDefaultController("Index");
            $router->setDefaultAction("index");
            foreach ($modules as $module) {
                $moduleName = ucfirst($module);
                $files = scandir($di->get('registry')->directories->modules . $moduleName . '/Controller');
                foreach ($files as $file) {
                    if ($file == "." || $file == ".." || strpos($file, 'Controller.php') === false
                    ) {
                        continue;
                    }
                    $controller = $moduleName . '\Controllers\\' . str_replace('Controller.php', '', $file);
                    $router->addModuleResource(strtolower($module), $controller);
                }
            }
            if ($saveToCache) {
                $cacheData->save('router_data', $router, 2592000); // 30 days cache
            }
        }

        $di->set('router', $router);
        return $router;
    }

    /**
     * Init database.
     *
     * @param DI $di Dependency Injection.
     * @param Config $config Config object.
     * @param EventsManager $eventsManager Event manager.
     *
     * @return Pdo
     */
    protected function _initDatabase($di, $config, $eventsManager)
    {
        $adapter = '\Phalcon\Db\Adapter\Pdo\\' . ucfirst($config->dbMaster->adapter);
        /** @var Pdo $connMaster */

        $connMaster = new $adapter(
            [
                "host" => $config->dbMaster->host,
                "port" => $config->dbMaster->port,
                "username" => $config->dbMaster->username,
                "password" => $config->dbMaster->password,
                "dbname" => $config->dbMaster->dbname,
                "prefix" => $config->dbMaster->prefix,
                'options' => [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '" . $config->dbMaster->charset . "'",
                    \PDO::ATTR_CASE => \PDO::CASE_LOWER,
                ]
            ]
        );
        $connSlave = new $adapter(
            [
                "host" => $config->dbSlave->host,
                "port" => $config->dbSlave->port,
                "username" => $config->dbSlave->username,
                "password" => $config->dbSlave->password,
                "dbname" => $config->dbSlave->dbname,
                "prefix" => $config->dbSlave->prefix,
                'options' => [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '" . $config->dbSlave->charset . "'",
                    \PDO::ATTR_CASE => \PDO::CASE_LOWER,
                ]
            ]
        );

        $isDebug = $config->debug;
        $isProfiler = $config->profiler;
        if ($isDebug || $isProfiler) {
            // Attach logger & profiler.
            $logger = null;
            $profiler = null;

            if ($isDebug) {
                $logger =  write_log("database", "");

            }
            if ($isProfiler) {
                $profiler = new DatabaseProfiler();
                $di->set('profiler', $profiler);
            }

            $eventsManager->attach(
                'db',
                function ($event, $connection) use ($logger, $profiler) {
                    if ($event->getType() == 'beforeQuery') {
                        $statement = $connection->getSQLStatement();
                        if ($logger) {
                            $logger->log($statement, Logger::INFO);
                        }
                        if ($profiler) {
                            $profiler->startProfile($statement);
                        }
                    }
                    if ($event->getType() == 'afterQuery') {
                        // Stop the active profile.
                        if ($profiler) {
                            $profiler->stopProfile();
                        }
                    }
                }
            );

            $connMaster->setEventsManager($eventsManager);
            $connSlave->setEventsManager($eventsManager);
        }

        $di->set('dbMaster', $connMaster);
        $di->set('db', $connSlave);
        $di->set(
            'modelsManager',
            function () use ($config, $eventsManager) {
                $modelsManager = new ModelsManager();
                $modelsManager->setEventsManager($eventsManager);
                return $modelsManager;
            },
            true
        );

        return $connMaster;
    }

    /**
     * Init cache.
     *
     * @param DI $di Dependency Injection.
     * @param Config $config Config object.
     *
     * @return void
     */
    protected function _initCache($di, $config)
    {
        // Get the parameters.
        if (ucfirst($config->cache->adapter) == "Redis") {
            $cacheAdapter = '\Engine\\' . ucfirst($config->cache->adapter);
        } else
            $cacheAdapter = '\Phalcon\Cache\Backend\\' . ucfirst($config->cache->adapter);

        $frontEndOptions = ['lifetime' => $config->cache->lifetime];
        $backEndOptions = $config->cache->toArray();
        $frontOutputCache = new CacheOutput($frontEndOptions);
        $frontDataCache = new CacheData($frontEndOptions);
        $cacheOutputAdapter = new $cacheAdapter($frontOutputCache, $backEndOptions);

        $di->set('cacheOutput', $cacheOutputAdapter, true);
        $cacheDataAdapter = new $cacheAdapter($frontDataCache, $backEndOptions);
        $di->set('cacheData', $cacheDataAdapter, true);
        $di->set('modelsCache', $cacheDataAdapter, true);
    }

    /**
     * Init engine.
     *
     * @param DI $di Dependency Injection.
     *
     * @return void
     */
    protected function _initEngine($di)
    {
        $di->setShared(
            'transactions',
            function () {
                $manager = new TxManager();
                return $manager->setDbService("dbMaster");

            }
        );
    }
}
