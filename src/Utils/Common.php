<?php

namespace WpCommander\Utils;

use WpCommander\Application;

abstract class Common
{
    abstract protected static function get_application_instance(): Application;

    public static function asset( $asset )
    {
        return self::get_application_instance()->get_root_url() . 'assets/' . trim( $asset, '/' );
    }

    public static function version()
    {
        return self::get_application_instance()::$config['version'];
    }
}
