<?php

namespace WpCommander\Route;

use WpCommander\Application;

abstract class Route
{
    abstract protected static function get_application_instance(): Application;

    public static function get( $path, $callback, $public = false ): void
    {
        static::register( $path, $callback, 'GET', $public );
    }

    public static function post( $path, $callback, $public = false ): void
    {
        static::register( $path, $callback, 'POST', $public );
    }

    private static function register( $path, $callback, $method, $public ): void
    {
        $path = trim( $path, '/' );
        $path = static::format_api_regex( $path );
        $args = [
            'methods'             => $method,
            'callback'            => function () use ( $callback ) {
                if ( is_array( $callback ) ) {
                    $controller = new $callback[0];
                    $method     = $callback[1];
                    return $controller->$method();
                }
                return $callback();
            },
            'permission_callback' => function () use ( $public ) {
                if ( $public ) {
                    return true;
                }
                return current_user_can( 'manage_options' );
            }
        ];

        $application = static::get_application_instance();

        /**
         * @var RegisterRoutes $registerRoutes
         */
        $registerRoutes = $application->make( $application->configuration()['api']['register_routes'] );
        $namespace      = $registerRoutes->get_namespace();
        $version        = $registerRoutes->get_version();
        if ( $version ) {
            $full_path = '/' . $namespace . '/' . $version . '/' . $path;
        } else {
            $full_path = '/' . $namespace . '/' . $path;
        }
        $registerRoutes->wp_rest_server->register_route( $namespace, $full_path, [$args] );
    }

    protected static function format_api_regex( string $route ): string
    {
        if ( strpos( $route, '}' ) !== false ) {
            if ( strpos( $route, '?}' ) !== false ) {
                $route = static::optional_param( $route );
            } else {
                $route = static::required_param( $route );
            }
        }

        return $route;
    }

    protected static function optional_param( string $route ): string
    {
        preg_match_all( '#\{(.*?)\}#', $route, $match );
        foreach ( $match[0] as $key => $value ) {
            $route = str_replace( '/' . $value, '(?:/(?P<' . str_replace( '?', '', $match[1][$key] ) . '>[-\w]+))?', $route );
        }

        return $route;
    }

    protected static function required_param( string $route ): string
    {
        preg_match_all( '#\{(.*?)\}#', $route, $match );
        foreach ( $match[0] as $key => $value ) {
            $route = str_replace( $value, '(?P<' . $match[1][$key] . '>[-\w]+)', $route );
        }
        return $route;
    }
}
