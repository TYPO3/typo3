<?php
namespace TYPO3\CMS\Styleguide\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Felix Kopp <felix-source@phorax.com>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Messaging\FlashMessage;

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
	/**
	 * Icons
	 */
	public function iconsAction() {
	}

	 * FlashMessages
	 */
	public function flashMessagesAction() {
		$this->addFlashMessage($this->getLoremIpsum(), 'Info - Title for Info message', FlashMessage::INFO, TRUE);
		$this->addFlashMessage($this->getLoremIpsum(), 'Notice - Title for Info message', FlashMessage::NOTICE, TRUE);
		$this->addFlashMessage($this->getLoremIpsum(), 'Error - Title for Info message', FlashMessage::ERROR, TRUE);
		$this->addFlashMessage($this->getLoremIpsum(), 'Ok - Title for Info message', FlashMessage::OK, TRUE);
		$this->addFlashMessage($this->getLoremIpsum(), 'Warning - Title for Info message', FlashMessage::WARNING, TRUE);
	}

	/**
	 * Helpers
	 */
	public function helpersAction() {

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

?>
