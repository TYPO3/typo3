<?php

/*
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

namespace OliverHader\IrreTutorial\Controller;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface;
use TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerException;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;

/**
 * ContentController
 */
class QueueController extends AbstractController
{
    /**
     * @Extbase\Inject
     * @var \OliverHader\IrreTutorial\Domain\Repository\ContentRepository
     */
    public $contentRepository;

    /**
     * @var string
     */
    protected $defaultViewObjectName = JsonView::class;

    public function indexAction()
    {
        $calls = [];
        $calls[] = ['Content', 'list'];
        $contents = $this->contentRepository->findAll();
        foreach ($contents as $content) {
            $uid = $content->getUid();
            $calls[] = ['Content', 'show', ['content' => (string)$uid]];
        }
        $this->getQueueService()->set($calls);
        $this->forward('process');
    }

    public function processAction()
    {
        $call = $this->getQueueService()->shift();
        if ($call === null) {
            $this->forward('finish');
        }
        // Clear these states and fetch fresh entities!
        $this->getPersistenceManager()->clearState();
        $this->forward($call[1], $call[0], null, $call[2] ?? null);
    }

    public function finishAction()
    {
        $this->request->setDispatched(true);
        $value = $this->getQueueService()->getValues();
        $this->view->assign('value', $value);
    }

    /**
     * Finds and instantiates a controller that matches the current request.
     * If no controller can be found, an instance of NotFoundControllerInterface is returned.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request to dispatch
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidControllerException
     * @return \TYPO3\CMS\Extbase\Mvc\Controller\ControllerInterface
     */
    protected function resolveController(RequestInterface $request)
    {
        $controllerObjectName = $request->getControllerObjectName();
        $controller = $this->objectManager->get($controllerObjectName);
        if (!$controller instanceof ControllerInterface) {
            throw new InvalidControllerException('Invalid controller "' . $request->getControllerObjectName() . '". The controller must implement the TYPO3\\CMS\\Extbase\\Mvc\\Controller\\ControllerInterface.', 1202921619);
        }
        return $controller;
    }
}
