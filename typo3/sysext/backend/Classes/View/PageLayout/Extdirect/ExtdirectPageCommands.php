<?php
namespace TYPO3\CMS\Backend\View\PageLayout\ExtDirect;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Jigal van Hemert <jigal.van.hemert@typo3.org>
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
 * Commands for the Page module
 *
 * @author Jigal van Hemert <jigal.van.hemert@typo3.org>
 */
class ExtdirectPageCommands {

	/**
	 * Move content element to a position and/or column.
	 *
	 * Function is called from the Page module javascript.
	 *
	 * @param integer $sourceElement  Id attribute of content element which must be moved
	 * @param string $destinationColumn Column to move the content element to
	 * @param integer $destinationElement Id attribute of the element it was dropped on
	 * @return array
	 */
	public function moveContentElement($sourceElement, $destinationColumn, $destinationElement) {
		$moveElementUid = 0;
		$afterElementUid = -1;
		$targetColumn = 0;
		$targetPage = 0;
		list($_, $table, $uid) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('-', $sourceElement);
		if ($table === 'tt_content' && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
			$moveElementUid = intval($uid);
		}
		list($_, $table, $uid) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('-', $destinationElement);
		if ($table === 'tt_content' && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
			$afterElementUid = intval($uid);
		} else {
			// it's dropped in an empty column
			$afterElementUid = -1;
		}
		list($prefix, $column, $prefix2, $page, $_) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('-', $destinationColumn);
		if ($prefix === 'colpos' && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($column) &&
				$prefix2 === 'page' && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($page)
		) {
			$targetColumn = intval($column);
			$targetPage = intval($page);
		}
		// move to empty column
		if ($afterElementUid === -1) {
			$action['cmd']['tt_content'][$moveElementUid]['move'] = $targetPage;
		} else {
			$action['cmd']['tt_content'][$moveElementUid]['move'] = -$afterElementUid;
		}

		$action['data']['tt_content'][$moveElementUid]['colPos'] = $targetColumn;

		\TYPO3\CMS\Core\Utility\GeneralUtility::devLog(
			'Dragdrop',
			'core',
			-1,
			array (
				'action' => $action,
				'sourceElement' => $sourceElement,
				'destinationColumn' => $destinationColumn,
				'destinationElement' => $destinationElement,
			)
		);
		/** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
		$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
		$tce->stripslashes_values = 0;
		$tce->start($action['data'], $action['cmd']);
		$tce->process_datamap();
		$tce->process_cmdmap();

		return array('success' => TRUE);
	}
}
?>