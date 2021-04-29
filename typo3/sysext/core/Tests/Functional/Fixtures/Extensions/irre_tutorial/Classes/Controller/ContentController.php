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

use OliverHader\IrreTutorial\Domain\Model\Content;
use OliverHader\IrreTutorial\Domain\Repository\ContentRepository;
use OliverHader\IrreTutorial\Service\QueueService;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;

/**
 * ContentController
 */
class ContentController extends AbstractController
{
    private ContentRepository $contentRepository;

    /**
     * @var string
     */
    protected $defaultViewObjectName = JsonView::class;

    public function __construct(
        DataMapFactory $dataMapFactory,
        QueueService $queueService,
        ContentRepository $contentRepository
    ) {
        parent::__construct($dataMapFactory, $queueService);
        $this->contentRepository = $contentRepository;
    }

    public function listAction()
    {
        $contents = $this->contentRepository->findAll();
        $value = $this->getStructure($contents);
        return $this->process($value);
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
     */
    public function showAction(Content $content)
    {
        $value = $this->getStructure($content);
        return $this->process($value);
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $newContent
     * @Extbase\IgnoreValidation("newContent")
     */
    public function newAction(Content $newContent = null)
    {
        $this->view->assign('newContent', $newContent);
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $newContent
     */
    public function createAction(Content $newContent)
    {
        $this->contentRepository->add($newContent);
        $this->redirect('list');
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
     * @Extbase\IgnoreValidation("content")
     */
    public function editAction(Content $content)
    {
        $this->view->assign('content', $content);
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
     */
    public function updateAction(Content $content)
    {
        $this->contentRepository->update($content);
        $this->redirect('list');
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
     */
    public function deleteAction(Content $content)
    {
        $this->contentRepository->remove($content);
        $this->redirect('list');
    }
}
