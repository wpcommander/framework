<?php

namespace WpCommander\Route;

use Closure;
use WpCommander\Application;
use WpCommander\Contracts\Middleware;
use WP_REST_Request;

abstract class Route
{
    protected static $group_configuration = [];

    abstract protected static function get_application_instance(): Application;

    public static function group( $prefix_or_configuration, Closure $routes )
    {
        if ( is_string( $prefix_or_configuration ) ) {
            self::$group_configuration['prefix'] = $prefix_or_configuration;
        } else {
            self::$group_configuration = $prefix_or_configuration;
        }

        $routes();
        self::$group_configuration = [];
    }

    public static function get( string $path, $callback, $public = false ): void
    {
        static::register( $path, $callback, 'GET', $public );
    }

    public static function post( string $path, $callback, $public = false ): void
    {
        static::register( $path, $callback, 'POST', $public );
    }

    private static function register( string $path, $callback, $method, $public ): void
    {
        $group_configuration = self::$group_configuration;

        if ( !empty( $group_configuration['prefix'] ) ) {
            $path = trim( $group_configuration['prefix'], '/' ) . '/' . trim( $path, '/' );
        } else {
            $path = trim( $path, '/' );
        }

        $route = static::format_api_regex( $path );
        $args  = [
            'methods'             => $method,
            'callback'            => function ( WP_REST_Request $wp_rest_request ) use ( $callback, $route ) {
                if ( is_array( $callback ) ) {
                    $class     = new \ReflectionClass( $callback[0] );
                    $arguments = self::dependency_injection( $route, $class->getMethod( $callback[1] )->getParameters(), $wp_rest_request );

                    $controller = new $callback[0];
                    $method     = $callback[1];
                    $response   = $controller->$method( ...$arguments );
                } else {
                    $reflection_function = new \ReflectionFunction( $callback );
                    $arguments           = self::dependency_injection( $route, $reflection_function->getParameters(), $wp_rest_request );
                    $response   = $callback( ...$arguments );
                }
                if($response) {
                    return $response;
                }
                die;
            },
            'permission_callback' => function ( WP_REST_Request $wp_rest_request ) use ( $public, $group_configuration ) {
                return self::handle_middleware( $wp_rest_request, $group_configuration );
            }
        ];

        $application = static::get_application_instance();

        /**
         * Create RegisterRoute instance
         * @var RegisterRoute $registerRoute
         */
        $registerRoute = $application->make( $application->configuration()['api']['register_route'] );
        $namespace     = $registerRoute->get_namespace();
        $version       = $registerRoute->get_version();
        if ( $version ) {
            $full_path = '/' . $namespace . '/' . $version . '/' . $route['path'];
        } else {
            $full_path = '/' . $namespace . '/' . $route['path'];
        }

        $registerRoute->wp_rest_server->register_route( $namespace, $full_path, [$args] );
    }

    protected static function handle_middleware( WP_REST_Request $wp_rest_request, $group_configuration )
    {
        if ( isset( $group_configuration['middleware'] ) && is_array( $group_configuration['middleware'] ) ) {
            $application       = static::get_application_instance();
            $config_middleware = $application::$config['middleware'];
            foreach ( $group_configuration['middleware'] as $middleware ) {
                if ( isset( $config_middleware[$middleware] ) ) {
                    $middleware_instance = new $config_middleware[$middleware];
                    if ( $middleware_instance instanceof Middleware && !$middleware_instance->handle( $wp_rest_request ) ) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    protected static function dependency_injection( array $route, array $params, WP_REST_Request $wp_rest_request )
    {
        $arguments = [];

        foreach ( $params as $key => $param ) {
            $param_type = $param->getType();
            if ( empty( $param_type ) ) {
                if ( !empty( $route['route_params'][$key] ) ) {
                    $arguments[] = $wp_rest_request->get_param( rtrim( $route['route_params'][$key], '?' ) );
                }
            } else {
                $param_type = $param->getType()->getName();
                if ( in_array( $param_type, ['string', 'int'] ) ) {
                    if ( !empty( $route['route_params'][$key] ) ) {
                        $arguments[] = $wp_rest_request->get_param( rtrim( $route['route_params'][$key], '?' ) );
                    }
                } elseif ( 'WP_REST_Request' === $param_type ) {
                    $arguments[] = $wp_rest_request;
                } else {
                    $arguments[] = new $param_type;
                }
            }
        }

        return $arguments;
    }

    protected static function format_api_regex( string $route ): array
    {
        $route_params = [];

        if ( strpos( $route, '}' ) !== false ) {
            preg_match_all( '#\{(.*?)\}#', $route, $params );
            if ( strpos( $route, '?}' ) !== false ) {
                $route = static::optional_param( $route, $params );
            } else {
                $route = static::required_param( $route, $params );
            }
            $route_params = $params[1];
        }

        return ['path' => trim($route, '/'), 'route_params' => $route_params];
    }

    protected static function optional_param( string $route, array $params ): string
    {
        foreach ( $params[0] as $key => $value ) {
            $route = str_replace( '/' . $value, '(?:/(?P<' . str_replace( '?', '', $params[1][$key] ) . '>[-\w]+))?', $route );
        }

        return $route;
    }

    protected static function required_param( string $route, array $params ): string
    {
        foreach ( $params[0] as $key => $value ) {
            $route = str_replace( $value, '(?P<' . $params[1][$key] . '>[-\w]+)', $route );
        }

        return $route;
    }
}
