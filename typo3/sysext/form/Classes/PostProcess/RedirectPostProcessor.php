<?php
namespace TYPO3\CMS\Form\PostProcess;

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
 * The redirect post processor
 */
class RedirectPostProcessor implements \TYPO3\CMS\Form\PostProcess\PostProcessorInterface {

	/**
	 * @var \TYPO3\CMS\Form\Domain\Model\Form
	 */
	protected $form;

	/**
	 * @var array
	 */
	protected $typoScript;

	/**
	 * @var \TYPO3\CMS\Form\Request
	 */
	protected $requestHandler;

	/**
	 * @var string
	 */
	protected $destination;

	/**
	 * @var array
	 */
	protected $dirtyHeaders = array();

	/**
	 * Constructor
	 *
	 * @param \TYPO3\CMS\Form\Domain\Model\Form $form Form domain model
	 * @param array $typoScript Post processor TypoScript settings
	 */
	public function __construct(\TYPO3\CMS\Form\Domain\Model\Form $form, array $typoScript) {
		$this->form = $form;
		$this->typoScript = $typoScript;
		$this->requestHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Form\\Request');
	}

	/**
	 * The main method called by the post processor
	 *
	 * @return string HTML message from this processor
	 */
	public function process() {
		$this->setDestination();
		return $this->render();
	}

	/**
	 * Sets the redirect destination
	 *
	 * @return void
	 */
	protected function setDestination() {
		$this->destination = '';
		if ($this->typoScript['destination']) {
			$urlConf = array('parameter' => $this->typoScript['destination']);
			$this->destination = $GLOBALS['TSFE']->cObj->typoLink_URL($urlConf);
		}
	}

	/**
	 * Render the message after trying to send the mail
	 *
	 * @return string HTML message from the mail view
	 */
	protected function render() {
		\TYPO3\CMS\Core\Utility\HttpUtility::redirect($this->destination);
		return '';
	}
}
