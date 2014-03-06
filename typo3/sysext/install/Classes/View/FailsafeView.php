<?php
namespace TYPO3\CMS\Install\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Helmut Hummel <helmut.hummel@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A view with basically wraps the standalone view for normal conditions
 * and implements a renderAlertStatus message for alert conditions
 * which would also make the install tool to fail.
 */
class FailsafeView extends \TYPO3\CMS\Extbase\Mvc\View\AbstractView {

	/**
	 * @var string
	 */
	protected $templatePathAndFileName;

	/**
	 * @var string
	 */
	protected $layoutRootPath;

	/**
	 * @var string
	 */
	protected $partialRootPath;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager = NULL;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
	}

	/**
	 * Hand over regular rendering to standalone view,
	 * or render alert status
	 *
	 * @param bool $alert
	 * @return string
	 */
	public function render($alert = FALSE) {
		if ($alert) {
			return $this->renderAlertStatus();
		}
		/** @var \TYPO3\CMS\Install\View\StandaloneView $realView */
		$realView = $this->objectManager->get('TYPO3\\CMS\\Install\\View\\StandaloneView');
		$realView->assignMultiple($this->variables);
		$realView->setTemplatePathAndFilename($this->templatePathAndFileName);
		$realView->setLayoutRootPath($this->layoutRootPath);
		$realView->setPartialRootPath($this->partialRootPath);

		return $realView->render();
	}

	/**
	 * In case an alert happens we fall back to a simple PHP template
	 *
	 * @return string
	 */
	protected function renderAlertStatus() {
		$templatePath = preg_replace('#\.html$#', '.phtml', $this->templatePathAndFileName);
		ob_start();
		include $templatePath;
		$renderedTemplate = ob_get_contents();
		ob_end_clean();

		return $renderedTemplate;
	}

	/**
	 * @param string $templatePathAndFileName
	 */
	public function setTemplatePathAndFileName($templatePathAndFileName) {
		$this->templatePathAndFileName = $templatePathAndFileName;
	}

	/**
	 * @param string $layoutRootPath
	 */
	public function setLayoutRootPath($layoutRootPath) {
		$this->layoutRootPath = $layoutRootPath;
	}

	/**
	 * @param string $partialRootPath
	 */
	public function setPartialRootPath($partialRootPath) {
		$this->partialRootPath = $partialRootPath;
	}
}
