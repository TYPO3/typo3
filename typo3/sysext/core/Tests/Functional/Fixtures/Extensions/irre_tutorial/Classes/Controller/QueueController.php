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
class QueueController extends AbstractController {

	/**
	 * @inject
	 * @var \OliverHader\IrreTutorial\Domain\Repository\ContentRepository
	 */
	protected $contentRepository;

	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'TYPO3\\CMS\\Extbase\\Mvc\\View\\JsonView';

	/**
	 * @return void
	 */
	public function indexAction() {
		$calls = array();
		$calls[] = array('Content', 'list');
		$contents = $this->contentRepository->findAll();
		foreach ($contents as $content) {
			$uid = $content->getUid();
			$calls[] = array('Content', 'show', array('content' => (string)$uid));
		}
		$this->getQueueService()->set($calls);
		$this->forward('process');
	}

	/**
	 * @return void
	 */
	public function processAction() {
		$call = $this->getQueueService()->shift();
		if ($call === NULL) {
			$this->forward('finish');
		}
		// Clear these states and fetch fresh entities!
		$this->getPersistenceManager()->clearState();
		$this->forward($call[1], $call[0], NULL, isset($call[2]) ? $call[2] : NULL);
	}

	public function finishAction() {
		$this->request->setDispatched(TRUE);
		$value = $this->getQueueService()->getValues();
		$this->view->assign('value', $value);
	}

	/**
	 * Finds and instanciates a controller that matches the current request.
	 * If no controller can be found, an instance of NotFoundControllerInterface is returned.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request to dispatch
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerException
	 * @return \TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface
	 */
	protected function resolveController(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request) {
		$controllerObjectName = $request->getControllerObjectName();
		$controller = $this->objectManager->get($controllerObjectName);
		if (!$controller instanceof \TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerException('Invalid controller "' . $request->getControllerObjectName() . '". The controller must implement the TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerInterface.', 1202921619);
		}
		return $controller;
	}

}