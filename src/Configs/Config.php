<?php

namespace WpCommander\Configs;

abstract class Config
{
    protected static $configs = [];

    protected function get_config_form_file( string $file_name, string $plugin_root_dir )
    {
        if ( isset( static::$configs[$file_name] ) ) {
            return static::$configs[$file_name];
        }

        $filePath = $plugin_root_dir . "/config/" . $file_name . ".php";

        if ( is_file( $filePath ) ) {
            $config_data                 = include_once $filePath;
            static::$configs[$file_name] = $config_data;
            return $config_data;
        }

        return false;
    }
}
