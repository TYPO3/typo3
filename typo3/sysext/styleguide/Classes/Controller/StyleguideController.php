<?php
namespace TYPO3\CMS\Styleguide\Controller;

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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Styleguide\Utility\KauderwelschUtility;

/**
 * Backend module for Styleguide
 */
class StyleguideController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * Buttons
	 */
	public function buttonsAction() {
	}

	/**
	 * Index
	 */
	public function indexAction() {
	}

	/**
	 * Typography
	 */
	public function typographyAction() {
	}

	/**
	 * Forms
	 */
	public function formsAction() {
	}

	/**
	 * Trees
	 */
	public function treesAction() {
	}

	/**
	 * Tables
	 */
	public function tablesAction() {
	}

	/**
	 * Bootstrap
	 */
	public function bootstrapAction() {
	}

	/**
	 * TCA
	 */
	public function tcaAction() {
	}

	/**
	 * Icons
	 */
	public function iconsAction() {
	}

	/**
	 * FlashMessages
	 */
	public function flashMessagesAction() {
		$this->addFlashMessage($this->getLoremIpsum(), 'Info - Title for Info message', FlashMessage::INFO, TRUE);
		$this->addFlashMessage($this->getLoremIpsum(), 'Notice - Title for Notice message', FlashMessage::NOTICE, TRUE);
		$this->addFlashMessage($this->getLoremIpsum(), 'Error - Title for Error message', FlashMessage::ERROR, TRUE);
		$this->addFlashMessage($this->getLoremIpsum(), 'Ok - Title for OK message', FlashMessage::OK, TRUE);
		$this->addFlashMessage($this->getLoremIpsum(), 'Warning - Title for Warning message', FlashMessage::WARNING, TRUE);
	}

	/**
	 * Helpers
	 */
	public function helpersAction() {
	}

	/**
	 * Tabs
	 */
	public function tabAction() {
		/** @var \TYPO3\CMS\Backend\Template\DocumentTemplate */
		$doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');

		$menuItems = array(
			0 => array(
				'label' => 'First label',
				'content' => 'First content'
			),
			1 => array(
				'label' => 'Second label',
				'content' => 'Second content'
			),
			2 => array(
				'label' => 'Third label',
				'content' => 'Third content'
			)
		);
		$tabs = $doc->getDynTabMenu($menuItems, 'ident');

		$this->view->assign('tabs', $tabs);

	}

	/**
	 * Lorem ipsum test with fixed length
	 *
	 * @return string
	 */
	protected function getLoremIpsum() {
		return 'Bacon ipsum dolor sit <strong>strong amet capicola</strong> jerky pork chop rump shoulder shank. Shankle strip <a href="#">steak pig salami link</a>. Leberkas shoulder ham hock cow salami bacon <em>em pork pork</em> chop, jerky pork belly drumstick ham. Tri-tip strip steak sirloin prosciutto pastrami. Corned beef venison tenderloin, biltong meatball pork tongue short ribs jowl cow hamburger strip steak. Doner turducken jerky short loin chuck filet mignon.';
	}

}