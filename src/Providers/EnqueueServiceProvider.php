<?php

namespace WpCommander\Providers;

use WpCommander\Contracts\ServiceProvider;

final class EnqueueServiceProvider extends ServiceProvider
{
    public function boot()
    {
        add_action( 'admin_enqueue_scripts', [$this, 'action_admin_enqueue_scripts'] );
        add_action( 'wp_enqueue_scripts', [$this, 'action_wp_enqueue_scripts'] );
        add_filter( 'script_loader_tag', [$this, 'filter_script_loader_tag'], 10, 3 );
    }

    /**
     * Filters the HTML script tag of an enqueued script.
     *
     * @param string $tag    The <code>&lt;script&gt;</code> tag for the enqueued script.
     * @param string $handle The script's registered handle.
     * @param string $src    The script's source URL.
     * @return string The <code>&lt;script&gt;</code> tag for the enqueued script.
     */
    public function filter_script_loader_tag( string $tag, string $handle, string $src ): string
    {
        if ( strpos( $handle, $this->application::$config['namespace'] . '-async' ) !== false ) {
            $tag = str_replace( ' src', ' async="async" src', $tag );
        }

        if ( strpos( $handle, $this->application::$config['namespace'] . '-defer' ) !== false ) {
            $tag = str_replace( '<script ', '<script defer ', $tag );
        }

        return $tag;
    }

    /**
     * Enqueue scripts for all admin pages.
     *
     * @param string $hook_suffix The current admin page.
     */
    public function action_admin_enqueue_scripts( string $hook_suffix ): void
    {
        $application = $this->application;
        include_once $application->get_root_dir() . '/enqueues/admin-scripts.php';
    }

    /**
     * Fires when scripts and styles are enqueued.
     *
     */
    public function action_wp_enqueue_scripts(): void
    {
        $application = $this->application;
        include_once $application->get_root_dir() . '/enqueues/frontend-scripts.php';
    }

}
