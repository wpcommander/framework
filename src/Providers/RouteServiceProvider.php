<?php

namespace WpCommander\Providers;

use WpCommander\Contracts\ServiceProvider;
use WpCommander\Route\RegisterRoute;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        add_action( 'rest_api_init', [$this, 'action_rest_api_init'] );
    }

    /**
     * Fires when preparing to serve a REST API request.
     */
    public function action_rest_api_init(): void
    {
        $application = $this->application;
		$config      = $application::$config;
		$container   = $application::$container;

		/**
		* Create RegisterRoute instance
		*
		* @var RegisterRoute $register_route
		*/
		$register_route = $container->singleton( $application->configuration()['api']['register_route'] );

		$register_route->set_namespace( $config['namespace'] );

		include $application->get_root_dir() . '/routes/api.php';

		foreach ( $config['api_versions'] as $version ) {
			$register_route->set_version( $version );
			include $application->get_root_dir() . '/routes/' . $version . '.php';
		}
    }
}
