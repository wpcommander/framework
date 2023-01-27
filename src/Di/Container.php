<?php
/**
 * WpCommander Framework DI Container with PSR11 ContainerInterface
 *
 * @package  WpCommander
 * @license  https://www.gnu.org/licenses/gpl-3.0.en.html GNU Public License
 * @since    1.0.0
 */

namespace WpCommander\Di;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface {

	public array $entries   = array();
	public array $instances = array();
	/**
	 * Finds an entry of the container by its identifier and returns it or create and returns it.
	 *
	 * @param string|object $class Identifier of the entry to look for.
	 *
	 * @return mixed Entry.
	 */
	public function get( $class ) {

		if ( is_string( $class ) && $this->has( $class ) ) {

			$class = $this->entries[ $class ];
		}

		return $this->resolve_class( $class );
	}

	/**
	 * Returns true if the container can return an entry for the given identifier.
	 * Returns false otherwise.
	 *
	 * `has($class)` returning.
	 *
	 * @param string $class Identifier of the entry to look for.
	 *
	 * @return bool
	 */
	public function has( string $class ): bool {
		return isset( $this->entries[ $class ] );
	}

	/**
	 * Bind dependency
	 *
	 * @param string $class Identifier of the entry to look for.
	 * @param string $concrete
	 * @return void
	 */
	public function bind( string $class, string $concrete ): void {
		$this->entries[ $class ] = $concrete;
	}

	public function set_instance(string $class, object $instance) {
		$this->instances[$class] = $instance;
	}

	/**
	 * Singleton
	 *
	 * @param string $class
	 * @return mixed
	 */
	public function singleton( string $class ) {

		if ( isset( $this->instances[ $class ] ) ) {
			return $this->instances[ $class ];
		}

		$key = $class;

		if ( $this->has( $key ) ) {
			$class = $this->entries[ $key ];
		}

		$instance = $this->resolve_class( $class, true );

		$this->instances[ $key ] = $instance;

		return $instance;
	}

	/**
	 * Resolve class
	 *
	 * @param string|object $class $class Identifier of the entry to look for.
	 *
	 * @throws ContainerException Error while retrieving the entry.
	 *
	 * @return mixed
	 */
	protected function resolve_class( $class, $singleton = false ) {
		// 1. Inspect the class that we are trying to get from the container
		$refection_class = new \ReflectionClass( $class );

		if ( ! $refection_class->isInstantiable() ) {
			throw new ContainerException( 'Class "' . $class . '" is not instantiable' );
		}

		// 2. Inspect the constructor of the class
		$constructor = $refection_class->getConstructor();

		if ( ! $constructor ) {
			return new $class();
		}

		// 3. Inspect the constructor parameters (dependencies)
		$parameters = $constructor->getParameters();

		if ( ! $parameters ) {
			return new $class();
		}

		// 4. If the constructor parameter is a class then try to resolve that class using the container
		$dependencies = array_map(
			function( \ReflectionParameter $parameter ) use ( $class, $singleton ) {
				$type = $parameter->getType();

				$name = $parameter->getName();

				if ( ! $type ) {
					throw new ContainerException( 'Failed to resolve class "' . $class . '" because param "' . $name . '" is missing a type hint' );
				}

				if ( $type instanceof \ReflectionNamedType && ! $type->isBuiltin() ) {

					if ( $singleton ) {
						return $this->singleton( $type->getName() );
					}
					return $this->get( $type->getName() );
				}

				throw new ContainerException( 'Failed to resolve class "' . $class . '" because invalid param "' . $name . '"' );
			},
			$parameters
		);

		return $refection_class->newInstanceArgs( $dependencies );
	}
}
