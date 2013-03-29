<?php
namespace TYPO3\CMS\Core\Messaging;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Alexander Schnitzler <alex.schnitzler@typovision.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A class representing flash messages.
 *
 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
 */
class FlashMessageService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Array of \TYPO3\CMS\Core\Messaging\FlashMessageQueue objects
	 *
	 * @var array
	 */
	protected $flashMessageQueues = array();

	/**
	 * Return the message queue for the given identifier.
	 * If no queue exists, an empty one will be created.
	 *
	 * @param string $identifier
	 * @return \TYPO3\CMS\Core\Messaging\FlashMessageQueue
	 * @api
	 */
	public function getMessageQueueByIdentifier($identifier = 'core.template.flashMessages') {
		if (!isset($this->flashMessageQueues[$identifier])) {
			$this->flashMessageQueues[$identifier] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
				'TYPO3\CMS\Core\Messaging\FlashMessageQueue',
				$identifier
			);
		}
		return $this->flashMessageQueues[$identifier];
	}
}

?>