<?php

if ( !function_exists( 'wp_commander_is_admin_page' ) ) {
    function wp_commander_is_admin_page( string $file_name = 'admin', array $params = [] ): bool
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

if ( !function_exists( 'wp_commander_import_elementor_demo' ) ) {
    function wp_commander_import_elementor_demo( $json_path, $templateId )
    {
        if ( !is_file( $json_path ) ) {
            return;
        }

        $json = file_get_contents( $json_path );

        if ( is_null( $json ) ) {
            return;
        }

        add_filter( 'elementor/files/allow_unfiltered_upload', '__return_true' );

        $template_manager = \Elementor\Plugin::$instance->templates_manager;
        $result           = $template_manager->import_template( [
            'fileData' => base64_encode( $json ),
            'fileName' => 'wp_commander.json'
        ] );

        $imported_post_id = $result[0]['template_id'];
        $template_data    = get_post_meta( $imported_post_id, '_elementor_data', true );

        update_post_meta( $templateId, '_elementor_data', $template_data );
        wp_delete_post( $imported_post_id );
    }
}

if ( !function_exists( 'wp_commander_json_encode_for_attr' ) ) {
    function wp_commander_json_encode_for_attr( array $data )
    {
        return htmlspecialchars( json_encode( $data ), ENT_QUOTES, 'UTF-8' );
    }
}
