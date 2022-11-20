<?php

namespace WpCommander\Contracts;

use WpCommander\Application;

abstract class ServiceProvider
{
    public $application;

    public function __construct( Application $application )
    {
        $this->application = $application;
    }

    abstract public function boot();
}
