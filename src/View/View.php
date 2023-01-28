<?php

namespace WpCommander\View;

use WpCommander\Application;

class View
{
    protected static $group_configuration = [];

    public static function render( string $path, array $args = [] )
    {
        extract( $args );
        include static::get_path( $path );
    }

    public static function send( string $path, array $args = [] )
    {
        extract( $args );
        ob_start();
        include static::get_path( $path );
        ob_flush();
    }

    public static function get_path( string $path )
    {
        $application = Application::$instance;

        if ( isset( pathinfo( $path )['extension'] ) ) {
            return $application->get_root_dir() . '/resources/views/' . $path;
        } else {
            return $application->get_root_dir() . '/resources/views/' . $path . '.php';
        }
    }
}
