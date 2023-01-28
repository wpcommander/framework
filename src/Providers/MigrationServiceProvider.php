<?php

namespace WpCommander\Providers;

use WpCommander\Application;
use WpCommander\Contracts\Migration;
use WpCommander\Contracts\ServiceProvider;

final class MigrationServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $plugin_name = Application::$config['namespace'];

        $version = (float) Application::$config['version'];

        $last_migration_version = (float) get_option( $plugin_name . '_last_migration_version', 0 );

        if ( $last_migration_version < $version ) {

            $option_key = $plugin_name . '_migrations';

            $runes_migrations = get_option( $option_key, [] );

            if ( !empty( $runes_migrations ) ) {
                $runes_migrations = unserialize( $runes_migrations );
            }

            $migrations_path = Application::$instance->get_root_dir() . '/database/migrations';

            $migrations = scandir( $migrations_path );

            foreach ( $migrations as $migration ) {

                $migration_path = $migrations_path . '/' . $migration;

                if ( is_file( $migration_path ) && !in_array( $migration, $runes_migrations ) ) {

                    $migration_class = include_once $migration_path;

                    if ( $migration_class instanceof Migration ) {

                        $migration_class->up();

                        array_push( $runes_migrations, $migration );
                    }
                }
            }

            update_option( $option_key, serialize( $runes_migrations ) );
            update_option( $plugin_name . '_last_migration_version', $version );
        }
    }
}
