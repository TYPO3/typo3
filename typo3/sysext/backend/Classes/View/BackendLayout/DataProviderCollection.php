<?php
namespace TYPO3\CMS\Backend\View\BackendLayout;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Collection of backend layout data providers.
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class DataProviderCollection implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array|DataProviderInterface[]
	 */
	protected $dataProviders = array();

	/**
	 * @var array
	 */
	protected $results = array();

	/**
	 * Adds a data provider to this collection.
	 *
	 * @param string $identifier
	 * @param string|object $classNameOrObject
	 * @throws \UnexpectedValueException
	 * @throws \LogicException
	 */
	public function add($identifier, $classNameOrObject) {
		if (strpos($identifier, '__') !== FALSE) {
			throw new \UnexpectedValueException(
				'Identifier "' . $identifier . '" must not contain "__"',
				1381597629
			);
		}

		if (is_object($classNameOrObject)) {
			$className = get_class($classNameOrObject);
			$dataProvider = $classNameOrObject;
		} else {
			$className = $classNameOrObject;
			$dataProvider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($classNameOrObject);
		}

		if (!$dataProvider instanceof DataProviderInterface) {
			throw new \LogicException(
				$className . ' must implement interface TYPO3\\CMS\\Backend\\View\\BackendLayout\\DataProviderInterface',
				1381269811
			);
		}

		$this->dataProviders[$identifier] = $dataProvider;
	}

	/**
	 * Gets all backend layout collections and thus, all
	 * backend layouts. Each data provider returns its own
	 * backend layout collection.
	 *
	 * @param DataProviderContext $dataProviderContext
	 * @return array|BackendLayoutCollection[]
	 */
	public function getBackendLayoutCollections(DataProviderContext $dataProviderContext) {
		$result = array();

		foreach ($this->dataProviders as $identifier => $dataProvider) {
			$backendLayoutCollection = $this->createBackendLayoutCollection($identifier);
			$dataProvider->addBackendLayouts($dataProviderContext, $backendLayoutCollection);
			$result[$identifier] = $backendLayoutCollection;
		}

		return $result;
	}

	/**
	 * Gets a backend layout by a combined identifier, which is
	 * e.g. "myextension_regular" and "myextension" is the identifier
	 * of the accordant data provider and "regular" the identifier of
	 * the accordant backend layout.
	 *
	 * @param string $combinedIdentifier
	 * @param integer $pageId
	 * @return NULL|BackendLayout
	 */
	public function getBackendLayout($combinedIdentifier, $pageId) {
		$backendLayout = NULL;

		if (strpos($combinedIdentifier, '__') === FALSE) {
			$dataProviderIdentifier = 'default';
			$backendLayoutIdentifier = $combinedIdentifier;
		} else {
			list($dataProviderIdentifier, $backendLayoutIdentifier) = explode('__', $combinedIdentifier, 2);
		}

		if (isset($this->dataProviders[$dataProviderIdentifier])) {
			$backendLayout = $this->dataProviders[$dataProviderIdentifier]->getBackendLayout($backendLayoutIdentifier, $pageId);
		}

		return $backendLayout;
	}

	/**
	 * Creates a new backend layout collection.
	 *
	 * @param string $identifier
	 * @return BackendLayoutCollection
	 */
	protected function createBackendLayoutCollection($identifier) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Backend\\View\\BackendLayout\\BackendLayoutCollection', $identifier
		);
	}

}