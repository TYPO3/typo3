<?php
namespace OliverHader\IrreTutorial\Controller;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014 Oliver Hader <oliver.hader@typo3.org>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * ContentController
 */
abstract class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @inject
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory
	 */
	protected $dataMapFactory;

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request
	 * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response
	 * @throws \RuntimeException
	 */
	public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response) {
		try {
			parent::processRequest($request, $response);
		} catch (\TYPO3\CMS\Extbase\Property\Exception $exception) {
			throw new \RuntimeException(
				$this->getRuntimeIdentifier() . ': ' . $exception->getMessage() . ' (' . $exception->getCode() . ')'
			);
		}
	}

	/**
	 * @param \Iterator|\TYPO3\CMS\Extbase\DomainObject\AbstractEntity[] $iterator
	 * @return array
	 */
	protected function getStructure($iterator) {
		$structure = array();

		if (!$iterator instanceof \Iterator) {
			$iterator = array($iterator);
		}

		foreach ($iterator as $entity) {
			$tableName = $this->dataMapFactory->buildDataMap(get_class($entity))->getTableName();
			$identifier = $tableName . ':' . $entity->getUid();
			$structureItem = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettableProperties($entity);
			foreach ($structureItem as $propertyName => $propertyValue) {
				if ($propertyValue instanceof \Iterator) {
					$structureItem[$propertyName] = $this->getStructure($propertyValue);
				}
			}
			$structure[$identifier] = $structureItem;
		}

		return $structure;
	}

	/**
	 * @param mixed $value
	 */
	protected function process($value) {
		if ($this->getQueueService()->isActive()) {
			$this->getQueueService()->addValue($this->getRuntimeIdentifier(), $value);
			$this->forward('process', 'Queue');
		}
		$this->view->assign('value', $value);
	}

	/**
	 * @return string
	 */
	protected function getRuntimeIdentifier() {
		$arguments = array();
		foreach ($this->request->getArguments() as $argumentName => $argumentValue) {
			$arguments[] = $argumentName . '=' . $argumentValue;
		}
		return $this->request->getControllerActionName() . '(' . implode(', ', $arguments) . ')';
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
	 */
	protected function getPersistenceManager() {
		return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\PersistenceManagerInterface');
	}

	/**
	 * @return \OliverHader\IrreTutorial\Service\QueueService
	 */
	protected function getQueueService() {
		return $this->objectManager->get('OliverHader\\IrreTutorial\\Service\\QueueService');
	}

}