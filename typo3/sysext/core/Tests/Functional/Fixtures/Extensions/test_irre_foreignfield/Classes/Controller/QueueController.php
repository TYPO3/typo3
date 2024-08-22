<?php

declare(strict_types=1);

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

namespace TYPO3Tests\TestIrreForeignfield\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3Tests\TestIrreForeignfield\Domain\Repository\ContentRepository;
use TYPO3Tests\TestIrreForeignfield\Service\QueueService;

class QueueController extends ActionController
{
    public function __construct(
        private readonly QueueService $queueService,
        private readonly ContentRepository $contentRepository,
        private readonly PersistenceManagerInterface $persistenceManager
    ) {
        $this->defaultViewObjectName = JsonView::class;
    }

    public function indexAction(): ResponseInterface
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

    public function processAction(): ResponseInterface
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
            $response = $response->withArguments($arguments);
        }

        return $response;
    }

    public function finishAction(): ResponseInterface
    {
        $value = $this->queueService->getValues();
        $this->view->assign('value', $value);
        $body = new Stream('php://temp', 'rw');
        $body->write($this->view->render());
        return (new Response($body))->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
