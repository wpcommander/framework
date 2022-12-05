<?php

namespace WpCommander\View;

use WpCommander\Application;

abstract class View
{
    protected static $group_configuration = [];

    abstract protected static function get_application_instance(): Application;

    public static function get( string $path, array $args = [] )
    {
        extract( $args );
        $application = static::get_application_instance();
        ob_start();
        if ( isset( pathinfo( $path )['extension'] ) ) {
            include $application->get_root_dir() . '/resources/views/' . $path;
        } else {
            include $application->get_root_dir() . '/resources/views/' . $path . '.php';
        }
        return ob_get_clean();
    }

    public static function render( string $path, array $args = [] )
    {
        wp_commander_render( static::get( $path, $args ) );
    }
}
