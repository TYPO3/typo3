<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The parser configuration. Contains all configuration needed to configure
 * the building of a SyntaxTree.
 *
 */
class Tx_Fluid_Core_Parser_Configuration {

	/**
	 * Generic interceptors registered with the configuration.
	 * @var array<Tx_Extbase_Persistence_ObjectStorage>
	 */
	protected $interceptors = array();

	/**
	 * Adds an interceptor to apply to values coming from object accessors.
	 *
	 * @param Tx_Fluid_Core_Parser_InterceptorInterface $interceptor
	 * @return void
	 */
	public function addInterceptor(Tx_Fluid_Core_Parser_InterceptorInterface $interceptor) {
		foreach ($interceptor->getInterceptionPoints() as $interceptionPoint) {
			if (!isset($this->interceptors[$interceptionPoint])) {
				$this->interceptors[$interceptionPoint] = t3lib_div::makeInstance('Tx_Extbase_Persistence_ObjectStorage');
			}
			if (!$this->interceptors[$interceptionPoint]->contains($interceptor)) {
				$this->interceptors[$interceptionPoint]->attach($interceptor);
			}
		}
	}

	/**
	 * Returns all interceptors for a given Interception Point.
	 *
	 * @param integer $interceptionPoint one of the Tx_Fluid_Core_Parser_InterceptorInterface::INTERCEPT_* constants,
	 * @return Tx_Extbase_Persistence_ObjectStorage<Tx_Fluid_Core_Parser_InterceptorInterface>
	 */
	public function getInterceptors($interceptionPoint) {
		if (isset($this->interceptors[$interceptionPoint]) && $this->interceptors[$interceptionPoint] instanceof Tx_Extbase_Persistence_ObjectStorage) {
			return $this->interceptors[$interceptionPoint];
		}
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_ObjectStorage');
	}

}
?>