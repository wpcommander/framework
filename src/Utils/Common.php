<?php

namespace WpCommander\Utils;

use WpCommander\Application;

class Common
{
    /**
     * Get asset director
     * @param string $asset file path without asset
     * @return string
     */
    public static function asset( $asset = '' )
    {
        return Application::$instance->get_root_url() . 'assets/' . trim( $asset, '/' );
    }

    public static function root_dir( $dir = '' )
    {
        return Application::instance()->get_root_dir() . '/' . ltrim( $dir, '/' );
    }

    public static function get_include(string $dir)
    {
        return include_once self::root_dir($dir);
    }

    public static function get_include_once( string $dir )
    {
        return include_once self::root_dir( $dir );
    }

    /**
     * Get plugin currency version
     *
     * @return void
     */
    public static function version()
    {
        return Application::$config['version'];
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

    public static function response( $data, $status = 200 ) {
        return compact( 'data', 'status' );
    }

    public static function get_elementor_icon( $icon, $attributes = [], $tag = 'i' )
    {
        if ( empty( $icon['library'] ) ) {
            return false;
        }

        if ( 'svg' === $icon['library'] ) {
            $output = \Elementor\Icons_Manager::render_uploaded_svg_icon( $icon['value'] );
        } else {
            $output = \Elementor\Icons_Manager::render_font_icon( $icon, $attributes, $tag );
        }

        return $output;
    }

    public static function enqueue_script($handler, $src, $dependencies = [], $in_footer = false)
    {
        self::script($handler, $src, $dependencies, $in_footer, 'wp_enqueue_script');
    }

    public static function register_script($handler, $src, $dependencies = [], $in_footer = false)
    {
        self::script($handler, $src, $dependencies, $in_footer, 'wp_register_script');
    }

    public static function script($handler, $src, $dependencies = [], $in_footer, $method)
    {
        $file_src = self::asset($src);
        $file_dependencies = self::get_include('assets/' . rtrim($src, '.js') . '.asset.php');

        foreach ($dependencies as $dependency) {
            $file_dependencies['dependencies'][] = $dependency;
        }

        $method($handler, $file_src, $file_dependencies['dependencies'], $file_dependencies['version'], $in_footer);
    }

    public static function enqueue_style($handler, $src, $dependencies = [], $media = 'all')
    {
        self::style($handler, $src, $dependencies, $media, 'wp_enqueue_style');
    }

    public static function register_style($handler, $src, $dependencies = [], $media = 'all')
    {
        self::style($handler, $src, $dependencies, $media, 'wp_register_style');
    }

    private static function style($handler, $src, $dependencies = [], $media, $method)
    {
        $method($handler, self::asset($src), $dependencies, self::version(), $media);
    }
}
