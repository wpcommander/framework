<?php

namespace WpCommander\Contracts;

use WP_REST_Request;

interface Middleware
{
    public function handle( WP_REST_Request $wp_rest_request );
}
