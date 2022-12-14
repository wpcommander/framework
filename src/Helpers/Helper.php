<?php

if ( !function_exists( 'wp_commander_is_admin_page' ) ) {
    function wp_commander_is_admin_page( string $file_name = 'admin', array $params = [] ): bool
    {
        $pathinfo        = pathinfo( $_SERVER['REQUEST_URI'] );
        if(strpos($pathinfo['filename'], 'php')) {
            $pathinfo['filename'] = explode('.', $pathinfo['filename'])[0];
        }
        $is_current_file = $pathinfo['filename'] === $file_name || false;
        if ( $is_current_file ) {
            foreach ( $params as $key => $value ) {
                if ( is_int( strpos( $value, '!' ) ) ) {
                    if ( isset( $_REQUEST[$key] ) && $_REQUEST[$key] == ltrim( $value, '!' ) ) {
                        return false;
                    }
                } elseif ( empty( $_REQUEST[$key] ) || $_REQUEST[$key] != $value ) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }
}

if ( !function_exists( 'wp_commander_render' ) ) {
    function wp_commander_render( $content )
    {
        echo $content;
    }
}

if ( !function_exists( 'wp_commander_url_add_params' ) ) {
    function wp_commander_url_add_params( string $url, array $params )
    {
        $query     = parse_url( $url, PHP_URL_QUERY );
        $url_param = '';
        $i         = 0;

        foreach ( $params as $name => $value ) {
            if ( 0 == $i ) {
                $url_param .= $name . '=' . $value;
            } else {
                $url_param .= '&' . $name . '=' . $value;
            }
            $i++;
        }

        if ( $query ) {
            $url .= '&' . $url_param;
        } else {
            $url .= '?' . $url_param;
        }

        return $url;
    }
}

