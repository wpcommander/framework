<?php

namespace WpCommander\Route;

abstract class RegisterRoute {

	public $namespace;

	public $version;

	public function set_namespace( $namespace ) {
		$this->namespace = trim( $namespace, '/' );
	}

	public function get_namespace() {
		return $this->namespace;
	}

	public function set_version( $version ) {
		$this->version = trim( $version, '/' );
	}

	public function get_version() {
		return $this->version;
	}
}
