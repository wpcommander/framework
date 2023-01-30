<?php

namespace WpCommander;

use WpCommander\Configs\Config;
use WpCommander\Contracts\ServiceProvider;
use WpCommander\Di\Container;
use WpCommander\Providers\EnqueueServiceProvider;
use WpCommander\Providers\MigrationServiceProvider;
use WpCommander\Providers\RouteServiceProvider;

class Application extends Config
{
    public static $instance, $config;
    protected static $instances = [], $is_boot = false, $root_dir, $root_url;
    public static Container $container;

    /**
     * @return static
     */
    public static function instance()
    {
        if ( !static::$instance ) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
	 * This method is helping to load the plugin
	 *
	 * @param string $root_dir plugin root file `__DIR__`.
	 * @param string $root_file plugin root file `__FILE__`.
	 * @return void
	 */
    public function boot( string $root_dir, string $root_file )
    {
        if ( static::$is_boot ) {
            return;
        }

        /**
         * For develop composer package
         */
        if( defined('DoatKolomDev') && DoatKolomDev === true && is_file($root_dir . '/vendor-src/autoload.php')) {

            require_once $root_dir . '/vendor-src/autoload.php';
        }

        static::$is_boot   = true;
        static::$container = new Container();
        static::$container->set_instance(Application::class, static::$instance);

        $this->set_root_dir_and_url( $root_dir, $root_file );
        $this->set_config();
        $this->run_system_provider();
        $this->run_provider();
    }

    private function set_config()
    {
        static::$config = $this->get_config( 'app' );
    }

    public function get_config( string $file_name )
    {
        return $this->get_config_form_file( $file_name, $this->get_root_dir() );
    }

    /**
	 * Boot plugin system provider
	 *
	 * @return void
	 */
	private function run_system_provider() {

		foreach ( $this->get_system_provider() as $provider ) {
			/**
			 * @var ServiceProvider $provider_object
			 */
			$provider_object = static::$container->singleton( $provider );
			$provider_object->boot();
		}
	}

    /**
	 * Boot Admin and Other form `config/app.php`
	 *
	 * @return void
	 */
	public function run_provider() {

		if ( is_admin() ) {
			foreach ( static::$config['admin_providers'] as $provider ) {
				/**
				 * @var ServiceProvider $provider_object
				 */
				$provider_object = static::$container->singleton( $provider );
				$provider_object->boot();
			}
		}

		foreach ( static::$config['providers'] as $provider ) {
			/**
			 * @var ServiceProvider $provider_object
			 */
			$provider_object = static::$container->singleton( $provider );
			$provider_object->boot();
		}
	}

    /**
	 * Set plugin root directory and path
	 *
	 * @param string $root_dir plugin root file `__DIR__`.
	 * @param string $root_file plugin root file `__FILE__`.
	 * @return void
	 */
    private function set_root_dir_and_url( string $root_dir, string $root_file )
    {
        static::$root_dir = $root_dir;
        static::$root_url = trailingslashit( plugin_dir_url( $root_file ) );
    }

    /**
	 * Get plugin root directory
	 *
	 * @return string
	 */
    public function get_root_dir(): string
    {
        return static::$root_dir;
    }

    /**
	 * Get plugin root url
	 *
	 * @return string
	 */
    public function get_root_url(): string
    {
        return static::$root_url;
    }

    /**
	 * Register system service provider
	 *
	 * @return array
	 */
    private function get_system_provider()
    {
        return [
            MigrationServiceProvider::class,
            RouteServiceProvider::class,
            EnqueueServiceProvider::class
        ];
    }
}
