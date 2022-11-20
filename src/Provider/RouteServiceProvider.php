<?php

namespace WpCommander\Provider;

use WpCommander\Contracts\ServiceProvider;
use WpCommander\Route\RegisterRoutes;

final class RouteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        add_action( 'rest_api_init', [$this, 'action_rest_api_init'] );
    }

    /**
     * Fires when preparing to serve a REST API request.
     *
     * @param \WP_REST_Server $wp_rest_server Server object.
     */
    public function action_rest_api_init( \WP_REST_Server $wp_rest_server ): void
    {
        $application = $this->application::$instance;

        $config = $application::$config;

        /**
         * Create RegisterRoutes instance
         * @var RegisterRoutes $registerRoutes
         */
        $registerRoutes = $application->make( $application->configuration()['api']['register_routes'] );

        $registerRoutes->set_namespace( $config['namespace'] );

        include_once $application->get_root_dir() . '/routes/api.php';

        foreach ( $config['api_versions'] as $version ) {
            $registerRoutes->set_version( $version );
            include_once $application->get_root_dir() . '/routes/' . $version . '.php';
        }
    }
}
