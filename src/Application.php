<?php

namespace WpCommander;

use WpCommander\Configs\Config;
use WpCommander\Contracts\ServiceProvider;
use WpCommander\Provider\RouteServiceProvider;

abstract class Application extends Config
{
    public static $instance;
    protected static $instances = [];
    public static $config;
    protected static $root_dir;
    protected static $is_boot = false;

    abstract public function configuration(): array;

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

    public function boot( string $root_dir )
    {
        if ( static::$is_boot ) {
            return;
        }

        static::$is_boot = true;
        $this->set_root_dir( $root_dir );
        $this->set_config();
        $this->run_system_provider();
    }

    private function set_config()
    {
        static::$config = $this->get_config( 'app' );
    }

    public function get_config( string $file_name )
    {
        return $this->get_config_form_file( $file_name, $this->get_root_dir() );
    }

    private function run_system_provider()
    {
        foreach ( $this->get_system_provider() as $provider ) {
            /**
             * @var ServiceProvider $provider_object
             */
            $provider_object = new $provider( static::$instance );
            $provider_object->boot( static::$instance );
        }
    }

    private function set_root_dir( string $root_dir )
    {
        static::$root_dir = $root_dir;
    }

    public function get_root_dir(): string
    {
        return static::$root_dir;
    }

    public function make( $class )
    {
        if ( empty( static::$instances[$class] ) ) {
            static::$instances[$class] = new $class;
        }
        return static::$instances[$class];
    }

    private function get_system_provider()
    {
        return [
            RouteServiceProvider::class
        ];
    }
}
