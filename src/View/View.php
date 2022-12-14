<?php

namespace WpCommander\View;

use WpCommander\Application;

abstract class View
{
    protected static $group_configuration = [];

    abstract protected static function get_application_instance(): Application;

    public static function render( string $path, array $args = [] )
    {
        extract( $args );
        include self::get_path( $path );
    }

    public static function send( string $path, array $args = [] )
    {
        extract( $args );
        ob_start();
        include self::get_path( $path );
        ob_flush();
    }

    public static function get_path( string $path )
    {
        $application = static::get_application_instance();

        if ( isset( pathinfo( $path )['extension'] ) ) {
            return $application->get_root_dir() . '/resources/views/' . $path;
        } else {
            return $application->get_root_dir() . '/resources/views/' . $path . '.php';
        }
    }
}
