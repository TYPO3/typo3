<?php
namespace TYPO3\CMS\Form\View\Wizard;

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

use TYPO3\CMS\Form\Domain\Repository\ContentRepository;

/**
 * The form wizard load view
 */
abstract class AbstractWizardView {

	/**
	 * Is the referenced record available
	 *
	 * @var bool TRUE if available, FALSE if not
	 */
	protected $recordIsAvailable = FALSE;

	/**
	 * @var ContentRepository
	 */
	protected $repository;

	/**
	 * Returns an instance of LanguageService
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Creates the object and calls the initialize() method.
	 *
	 * @param ContentRepository $repository
	 */
	public function __construct(ContentRepository $repository) {
		$this->setRepository($repository);
		$this->getLanguageService()->includeLLFile('EXT:form/Resources/Private/Language/locallang_wizard.xlf');
	}

	/**
	 * Sets the content repository to be used.
	 *
	 * @param ContentRepository $repository
	 * @return void
	 */
	public function setRepository(ContentRepository $repository) {
		$this->repository = $repository;
	}

	/**
	 * The main render method
	 *
	 * @return void
	 */
	abstract public function render();

}
