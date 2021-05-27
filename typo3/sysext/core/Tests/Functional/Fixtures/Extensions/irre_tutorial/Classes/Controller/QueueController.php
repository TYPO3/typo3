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

use OliverHader\IrreTutorial\Domain\Repository\ContentRepository;
use OliverHader\IrreTutorial\Service\QueueService;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

/**
 * ContentController
 */
class QueueController extends AbstractController
{
    private ContentRepository $contentRepository;
    private PersistenceManagerInterface $persistenceManager;

    /**
     * @var string
     */
    protected $defaultViewObjectName = JsonView::class;

    public function __construct(
        DataMapFactory $dataMapFactory,
        QueueService $queueService,
        ContentRepository $contentRepository,
        PersistenceManagerInterface $persistenceManager
    ) {
        parent::__construct($dataMapFactory, $queueService);
        $this->contentRepository = $contentRepository;
        $this->persistenceManager = $persistenceManager;
    }

    public function indexAction()
    {
        $calls = [];
        $calls[] = ['Content', 'list'];
        $contents = $this->contentRepository->findAll();
        foreach ($contents as $content) {
            $uid = $content->getUid();
            $calls[] = ['Content', 'show', ['content' => (string)$uid]];
        }
        $this->queueService->set($calls);
        return new ForwardResponse('process');
    }

    public function processAction()
    {
        $call = $this->queueService->shift();
        if ($call === null) {
            return new ForwardResponse('finish');
        }
        // Clear these states and fetch fresh entities!
        $this->persistenceManager->clearState();

        $response = (new ForwardResponse($call[1]))
            ->withControllerName($call[0]);

        $arguments = $call[2] ?? null;
        if (is_array($arguments)) {
            $response  = $response->withArguments($arguments);
        }

        return $response;
    }

    public function finishAction()
    {
        $this->request->setDispatched(true);
        $value = $this->queueService->getValues();
        $this->view->assign('value', $value);
    }
}
