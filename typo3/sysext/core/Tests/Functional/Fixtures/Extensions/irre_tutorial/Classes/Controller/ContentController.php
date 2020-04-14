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

/**
 * ContentController
 */
class ContentController extends AbstractController
{
    /**
     * @Extbase\Inject
     * @var \OliverHader\IrreTutorial\Domain\Repository\ContentRepository
     */
    protected $contentRepository;

    /**
     * @var string
     */
    protected $defaultViewObjectName = \TYPO3\CMS\Extbase\Mvc\View\JsonView::class;

    public function listAction()
    {
        $contents = $this->contentRepository->findAll();
        $value = $this->getStructure($contents);
        $this->process($value);
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
     */
    public function showAction(\OliverHader\IrreTutorial\Domain\Model\Content $content)
    {
        $value = $this->getStructure($content);
        $this->process($value);
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $newContent
     * @Extbase\IgnoreValidation("newContent")
     */
    public function newAction(\OliverHader\IrreTutorial\Domain\Model\Content $newContent = null)
    {
        $this->view->assign('newContent', $newContent);
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $newContent
     */
    public function createAction(\OliverHader\IrreTutorial\Domain\Model\Content $newContent)
    {
        $this->contentRepository->add($newContent);
        $this->redirect('list');
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
     * @Extbase\IgnoreValidation("content")
     */
    public function editAction(\OliverHader\IrreTutorial\Domain\Model\Content $content)
    {
        $this->view->assign('content', $content);
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
     */
    public function updateAction(\OliverHader\IrreTutorial\Domain\Model\Content $content)
    {
        $this->contentRepository->update($content);
        $this->redirect('list');
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
     */
    public function deleteAction(\OliverHader\IrreTutorial\Domain\Model\Content $content)
    {
        $this->contentRepository->remove($content);
        $this->redirect('list');
    }
}
