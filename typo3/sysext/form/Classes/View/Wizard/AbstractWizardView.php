<?php
namespace TYPO3\CMS\Form\View\Wizard;

/**
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
 * The form wizard load view
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
abstract class AbstractWizardView {

	/**
	 * Is the referenced record available
	 *
	 * @var boolean TRUE if available, FALSE if not
	 */
	protected $recordIsAvailable = FALSE;

	/**
	 * @var \TYPO3\CMS\Form\Domain\Repository\ContentRepository
	 */
	protected $repository;

	/**
	 * Creates the object and calls the initialize() method.
	 *
	 * @param \TYPO3\CMS\Form\Domain\Repository\ContentRepository $repository
	 */
	public function __construct(\TYPO3\CMS\Form\Domain\Repository\ContentRepository $repository) {
		$this->setRepository($repository);
	}

	/**
	 * Sets the content repository to be used.
	 *
	 * @param \TYPO3\CMS\Form\Domain\Repository\ContentRepository $repository
	 * @return void
	 */
	public function setRepository(\TYPO3\CMS\Form\Domain\Repository\ContentRepository $repository) {
		$this->repository = $repository;
	}

	/**
	 * The main render method
	 *
	 * @return void
	 */
	abstract public function render();

}
