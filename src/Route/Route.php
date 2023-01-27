<?php

namespace WpCommander\Route;

use Closure;
use WpCommander\Application;
use WpCommander\Contracts\Middleware;
use WP_REST_Request;
use WpCommander\Di\ContainerException;

abstract class Route
{
    protected static $group_configuration = [];

    abstract protected static function get_application_instance(): Application;

    /**
	 * Group APIs of the same prefix or middleware type
	 *
	 * @param string|array $prefix_or_configuration
	 * @param \Closure     $routes
	 * @return void
	 */
    public static function group( $prefix_or_configuration, Closure $routes )
    {
        if ( is_string( $prefix_or_configuration ) ) {
            static::$group_configuration['prefix'] = $prefix_or_configuration;
        } else {
            static::$group_configuration = $prefix_or_configuration;
        }

        $routes();
        static::$group_configuration = [];
    }

    /**
	 * Get method type route
	 *
	 * @param string         $path
	 * @param array|\Closure $callback
	 * @return void
	 */
    public static function get( string $path, $callback ): void
    {
        static::register( $path, $callback, 'GET' );
    }

    /**
	 * Post method type route
	 *
	 * @param string         $path
	 * @param array|\Closure $callback
	 * @return void
	 */
    public static function post( string $path, $callback ): void
    {
        static::register( $path, $callback, 'POST' );
    }

    /**
	 * Patch method type route
	 *
	 * @param string         $path
	 * @param array|\Closure $callback
	 * @return void
	 */
    public static function patch( string $path, $callback ): void
    {
        static::register( $path, $callback, 'PATCH' );
    }

    /**
	 * Registering rest API with `rest_get_server()`
	 *
	 * @param string         $path
	 * @param array|\Closure $callback
	 * @param string         $method
	 * @return void
	 */
    private static function register( string $path, $callback, $method ): void {

		$group_configuration = static::$group_configuration;

		if ( ! empty( $group_configuration['prefix'] ) ) {
			$path = trim( $group_configuration['prefix'], '/' ) . '/' . trim( $path, '/' );
		} else {
			$path = trim( $path, '/' );
		}

		$args = array(
			'methods'             => $method,
			'callback'            => function ( WP_REST_Request $wp_rest_request ) use ( $callback ) {

				$response = static::get_response( $callback, $wp_rest_request );

				if ( $wp_rest_request->has_param( 'return' ) ) {
					return $response;
				}

				wp_send_json( $response['data'], $response['status'] );
			},
			'permission_callback' => function ( WP_REST_Request $wp_rest_request ) use ( $group_configuration ) {
				return static::handle_middleware( $wp_rest_request, $group_configuration );
			},
		);

		/**
		 * Create RegisterRoute instance
		 *
		 * @var RegisterRoute $register_route
		 */
		$register_route = static::get_application_instance()::$container->singleton( static::get_application_instance()->configuration()['api']['register_route'] );
		$namespace      = $register_route->get_namespace();
		$version        = $register_route->get_version();
		$route          = static::format_api_regex( $path );

		if ( $version ) {
			$full_path = '/' . $namespace . '/' . $version . '/' . $route['path'];
		} else {
			$full_path = '/' . $namespace . '/' . $route['path'];
		}

		rest_get_server()->register_route( $namespace, $full_path, array( $args ) );
	}

	/**
	 * Inject callback dependencies and get response
	 *
	 * @param array|\Closure  $callback
	 * @param WP_REST_Request $wp_rest_request
	 *
	 * @throws \Exception Error while callback is wrong.
	 *
	 * @return mixed
	 */
	protected static function get_response( $callback, WP_REST_Request $wp_rest_request ) {

		/**
		 * If send controller callback
		 */
		if ( is_array( $callback ) && count( $callback ) === 2 ) {
			/**
			 * Create controller instance with DI Container
			 */
			$controller = static::get_application_instance()::$container->get( $callback[0] );
			$method     = $callback[1];

			/**
			 * Get method required parameters
			 */
			$reflection_controller = new \ReflectionObject( $controller );
			$parameters            = $reflection_controller->getMethod( $method )->getParameters();

			/**
			 * Get dependencies
			 */
			$dependencies = static::get_dependencies( $parameters, $wp_rest_request );

			return $controller->$method( ...$dependencies );
		}

		/**
		 * If send anonyms function callback
		 */
		if ( is_callable( $callback ) ) {
			/**
			* Get method required parameters
			*/
			$reflection_callback = new \ReflectionFunction( $callback );
			$parameters          = $reflection_callback->getParameters();

			/**
			* Get dependencies
			*/
			$dependencies = static::get_dependencies( $parameters, $wp_rest_request );

			return $callback( ...$dependencies );
		}

		throw new \Exception( 'Please bind callable method in this route' );
	}

	/**
	 * Get route callback dependencies
	 *
	 * @param array           $parameters
	 * @param WP_REST_Request $wp_rest_request
	 *
	 * @throws ContainerException Error while retrieving the entry.
	 *
	 * @return array
	 */
	protected static function get_dependencies( array $parameters, WP_REST_Request $wp_rest_request ) {

		return array_map(
			function( \ReflectionParameter $parameter ) use ( $wp_rest_request ) {
				$type = $parameter->getType();
				$name = $parameter->getName();

				if ( ! $type ) {
					throw new ContainerException( 'Failed to resolve because param "' . $name . '" is missing a type hint' );
				}

				if ( $type->isBuiltin() ) {
					return $wp_rest_request->get_param( $name );
				}

				if ( 'WP_REST_Request' === $type->getName() ) {
					return $wp_rest_request;
				}

				if ( $type instanceof \ReflectionNamedType ) {
					return static::get_application_instance()::$container->get( $type->getName() );
				}

				throw new ContainerException( 'Failed to resolve class "' . $name . '" because invalid param "' . $name . '"' );
			},
			$parameters
		);
	}

    /**
	 * Handle route all middleware
	 *
	 * @param WP_REST_Request $wp_rest_request
	 * @param array           $group_configuration
	 * @return bool
	 */
	protected static function handle_middleware( WP_REST_Request $wp_rest_request, array $group_configuration ) {

		if ( isset( $group_configuration['middleware'] ) && is_array( $group_configuration['middleware'] ) ) {
			/**
			 * Get plugin all middleware
			 */
			$application       = static::get_application_instance();
			$config_middleware = $application::$config['middleware'];

			/**
			 * Check route middleware one by one
			 */
			foreach ( $group_configuration['middleware'] as $middleware ) {

				/**
				 * If the route middleware exists in `config/app.php`
				 */
				if ( isset( $config_middleware[ $middleware ] ) ) {
					$middleware_instance = $application::$container->get( $config_middleware[ $middleware ] );
					if ( $middleware_instance instanceof Middleware && ! $middleware_instance->handle( $wp_rest_request ) ) {
						return false;
					}
				}
			}
		}

		return true;
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
