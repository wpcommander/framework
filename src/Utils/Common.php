<?php

namespace WpCommander\Utils;

use WpCommander\Application;

abstract class Common
{
    abstract protected static function get_application_instance(): Application;

    /**
     * Get asset director
     * @param string $asset file path without asset
     * @return string
     */
    public static function asset( $asset = '' )
    {
        return static::get_application_instance()->get_root_url() . 'assets/' . trim( $asset, '/' );
    }

    /**
     * Get plugin currency version
     *
     * @return void
     */
    public static function version()
    {
        return static::get_application_instance()::$config['version'];
    }

    /**
     * Encode json for html attribute.
     *
     * @param array $data
     * @return string
     */
    public static function json_encode_for_attr( array $data )
    {
        return htmlspecialchars( json_encode( $data ), ENT_QUOTES, 'UTF-8' );
    }

    /**
     * Add params to the URL.
     *
     * @param string $url
     * @param array $params Ex: ['key' => 'value', 'key1' => 'value1']
     * @return string
     */    
    public static function url_add_params( string $url, array $params )
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

    /**
     * Import elementor demo json for wordpress post. if you want to import the demo from a JSON file, then pass the JSON file path in the first param and the last param will be the file
     *
     * @param json|string $json
     * @param integer $post_id
     * @param string $type 
     * @return void
     */
    public static function import_elementor_demo( $json, $post_id, $type = 'json' )
    {
        if ( 'file' === $type ) {
            if ( !is_file( $json ) ) {
                return false;
            }

            $json = file_get_contents( $json );

            if ( is_null( $json ) ) {
                return false;
            }
        }

        add_filter( 'elementor/files/allow_unfiltered_upload', '__return_true' );

        $template_manager = \Elementor\Plugin::$instance->templates_manager;
        $result           = $template_manager->import_template( [
            'fileData' => base64_encode( $json ),
            'fileName' => 'wp_commander.json'
        ] );

        $imported_post_id = $result[0]['template_id'];
        $template_data    = get_post_meta( $imported_post_id, '_elementor_data', true );

        update_post_meta( $post_id, '_elementor_data', $template_data );
        wp_delete_post( $imported_post_id );
    }

    /**
     * Using the method you can check the admin's current page inside any hook. no matter whether the wp is fully loaded or not.
     *
     * @param string $file_name wordpress url current file name. Like:- admin, edit, plugins
     * @param array $params Current url available get method request params: Like: page, post_type. Ex:- ['post_type' => 'page']
     * @return boolean
     */
    public static function is_admin_page( string $file_name = 'admin', array $params = [] ): bool
    {
        $pathinfo = pathinfo( $_SERVER['REQUEST_URI'] );

        if ( strpos( $pathinfo['filename'], 'php' ) ) {
            $pathinfo['filename'] = explode( '.', $pathinfo['filename'] )[0];
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
