<?php
namespace OliverHader\IrreTutorial\Controller;

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

/**
 * ContentController
 */
class ContentController extends AbstractController
{
    /**
     * @inject
     * @var \OliverHader\IrreTutorial\Domain\Repository\ContentRepository
     */
    protected $contentRepository;

    /**
     * @var string
     */
    protected $defaultViewObjectName = \TYPO3\CMS\Extbase\Mvc\View\JsonView::class;

    /**
     * @return void
     */
    public function listAction()
    {
        $contents = $this->contentRepository->findAll();
        $value = $this->getStructure($contents);
        $this->process($value);
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
     * @return void
     */
    public function showAction(\OliverHader\IrreTutorial\Domain\Model\Content $content)
    {
        $value = $this->getStructure($content);
        $this->process($value);
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $newContent
     * @ignorevalidation $newContent
     * @return void
     */
    public function newAction(\OliverHader\IrreTutorial\Domain\Model\Content $newContent = null)
    {
        $this->view->assign('newContent', $newContent);
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $newContent
     * @return void
     */
    public function createAction(\OliverHader\IrreTutorial\Domain\Model\Content $newContent)
    {
        $this->contentRepository->add($newContent);
        $this->redirect('list');
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
     * @ignorevalidation $content
     * @return void
     */
    public function editAction(\OliverHader\IrreTutorial\Domain\Model\Content $content)
    {
        $this->view->assign('content', $content);
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
     * @return void
     */
    public function updateAction(\OliverHader\IrreTutorial\Domain\Model\Content $content)
    {
        $this->contentRepository->update($content);
        $this->redirect('list');
    }

    /**
     * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
     * @return void
     */
    public function deleteAction(\OliverHader\IrreTutorial\Domain\Model\Content $content)
    {
        $this->contentRepository->remove($content);
        $this->redirect('list');
    }
}
