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
class ContentController extends AbstractController {

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
	public function listAction() {
		$contents = $this->contentRepository->findAll();
		$value = $this->getStructure($contents);
		$this->process($value);
	}

	/**
	 * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
	 * @return void
	 */
	public function showAction(\OliverHader\IrreTutorial\Domain\Model\Content $content) {
		$value = $this->getStructure($content);
		$this->process($value);
	}

	/**
	 * @param \OliverHader\IrreTutorial\Domain\Model\Content $newContent
	 * @ignorevalidation $newContent
	 * @return void
	 */
	public function newAction(\OliverHader\IrreTutorial\Domain\Model\Content $newContent = NULL) {
		$this->view->assign('newContent', $newContent);
	}

	/**
	 * @param \OliverHader\IrreTutorial\Domain\Model\Content $newContent
	 * @return void
	 */
	public function createAction(\OliverHader\IrreTutorial\Domain\Model\Content $newContent) {
		$this->contentRepository->add($newContent);
		$this->redirect('list');
	}

	/**
	 * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
	 * @ignorevalidation $content
	 * @return void
	 */
	public function editAction(\OliverHader\IrreTutorial\Domain\Model\Content $content) {
		$this->view->assign('content', $content);
	}

	/**
	 * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
	 * @return void
	 */
	public function updateAction(\OliverHader\IrreTutorial\Domain\Model\Content $content) {
		$this->contentRepository->update($content);
		$this->redirect('list');
	}

	/**
	 * @param \OliverHader\IrreTutorial\Domain\Model\Content $content
	 * @return void
	 */
	public function deleteAction(\OliverHader\IrreTutorial\Domain\Model\Content $content) {
		$this->contentRepository->remove($content);
		$this->redirect('list');
	}

}