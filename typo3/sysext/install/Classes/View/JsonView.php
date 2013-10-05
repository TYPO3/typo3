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

use TYPO3\CMS\Extbase\Mvc\View\AbstractView;
use TYPO3\CMS\Install\Status\StatusInterface;
use TYPO3\CMS\Install\Status\Exception as StatusException;

/**
 * Simple JsonView (currently returns an associative array)
 */
class JsonView extends AbstractView {

	/**
	 * @return string
	 */
	public function render() {
		$renderedData = $this->variables;
		if (isset($renderedData['status']) && is_array($renderedData['status'])) {
			try {
				$renderedData['status'] = $this->transformStatusMessagesToArray($renderedData['status']);
			} catch (StatusException $e) {
				$renderedData['status'] = array(array(
					'severity' => 'error',
					'title' => htmlspecialchars($e->getMessage())
				));
			}
		}

		return $renderedData;
	}

	/**
	 * Transform an array of messages to an associative array.
	 *
	 * @param array<StatusInterface>
	 * @return array
	 * @throws StatusException
	 */
	protected function transformStatusMessagesToArray(array $statusArray = array()) {
		$result = array();
		foreach ($statusArray as $status) {
			if (!$status instanceof StatusInterface) {
				throw new StatusException (
					'Object must implement StatusInterface',
					1381059600
				);
			}
			$result[] = $this->transformStatusToArray($status);
		}
		return $result;
	}

	/**
	 * Creates an array from a status object.
	 * Used for example to transfer the message as json.
	 *
	 * @param StatusInterface $status
	 * @return array
	 */
	public function transformStatusToArray(StatusInterface $status) {
		$arrayStatus = array();
		$arrayStatus['severity'] = htmlspecialchars($status->getSeverity());
		$arrayStatus['title'] = htmlspecialchars($status->getTitle());
		$arrayStatus['message'] = htmlspecialchars($status->getMessage());
		return $arrayStatus;
	}

}