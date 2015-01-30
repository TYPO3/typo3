<?php
namespace Helhum\ClassAliasLoader\Composer;

/*
 * This file is part of the class alias loader package.
 *
 * (c) Helmut Hummel <info@helhum.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Composer\Autoload\ClassLoader as ComposerClassLoader;

/**
 * Class ClassAliasLoader
 */
class ClassAliasLoader {

	/**
	 * @var ComposerClassLoader
	 */
	protected $composerClassLoader;

	/**
	 * @var array
	 */
	protected $aliasMap = array();

	/**
	 * @param ComposerClassLoader $composerClassLoader
	 */
	public function __construct(ComposerClassLoader $composerClassLoader) {
		$composerClassLoader->unregister();
		$this->composerClassLoader = $composerClassLoader;
	}

	/**
	 * Set the alias map
	 *
	 * @param array $aliasMap
	 */
	public function setAliasMap(array $aliasMap) {
		$this->aliasMap = $aliasMap;
	}

	/**
	 * Adds an alias map and merges it with already available map
	 *
	 * @param array $aliasMap
	 */
	public function addAliasMap(array $aliasMap) {
		foreach ($aliasMap['aliasToClassNameMapping'] as $alias => $class) {
			$lowerCaseAlias = strtolower($alias);
			$this->aliasMap['aliasToClassNameMapping'][$lowerCaseAlias] = $class;
			$this->aliasMap['classNameToAliasMapping'][$class][$lowerCaseAlias] = $lowerCaseAlias;
		}
	}

	/**
	 * Main class loading method registered with spl_autoload_register()
	 *
	 * @param string $className
	 * @return bool
	 */
	public function loadClassWithAlias($className) {
		// Work around for PHP 5.3.0 - 5.3.2 https://bugs.php.net/50731
		if ('\\' === $className[0]) {
			$className = substr($className, 1);
		}
		$lowerCasedClassName = strtolower($className);
		// Is an original class which has an alias
		if (isset($this->aliasMap['classNameToAliasMapping'][$className])) {
			return $this->loadOriginalClassAndSetAliases($className);
		// Is an alias (we're graceful regarding casing for alias definitions)
		} elseif (isset($this->aliasMap['aliasToClassNameMapping'][$lowerCasedClassName])) {
			$originalClassName = $this->aliasMap['aliasToClassNameMapping'][$lowerCasedClassName];
			return $this->loadOriginalClassAndSetAliases($originalClassName);
		}
		return $this->composerClassLoader->loadClass($className);
	}

	/**
	 * Load classes and set aliases.
	 * The class_exists calls are safety guards to avoid fatals when
	 * class files were included or aliases were set manually in userland code.
	 *
	 * @param string $originalClassName
	 * @return bool
	 */
	protected function loadOriginalClassAndSetAliases($originalClassName) {
		if (class_exists($originalClassName, false) || $this->composerClassLoader->loadClass($originalClassName)) {
			foreach ($this->aliasMap['classNameToAliasMapping'][$originalClassName] as $aliasClassName) {
				if (!class_exists($aliasClassName, false)) {
					class_alias($originalClassName, $aliasClassName);
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Act as a proxy for method calls to composer class loader
	 *
	 * @param string $method
	 * @param array $arguments
	 * @return mixed
	 */
	public function __call($method, $arguments) {
		if (!is_callable(array($this->composerClassLoader, $method))) {
			throw new \InvalidArgumentException(sprintf('Method "%s" does not exist!', $method), 1422631610);
		}
		return call_user_func_array(array($this->composerClassLoader, $method), $arguments);
	}

}
