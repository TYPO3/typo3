<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Georg Ringer <typo3@ringerge.org>
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

/**
 * Render sys_notes
 *
 * @package TYPO3
 * @subpackage sys_note
 * @author Georg Ringer <typo3@ringerge.org>
 */
class Tx_SysNote_SysNote {

	/**
	 * Render sys_notes by pid
	 *
	 * @param string $pidList comma separated list of page ids
	 * @return string
	 */
	public function renderByPid($pidList) {
			// Create query for selecting the notes:
		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'*',
					'sys_note',
					'pid IN (' . $GLOBALS['TYPO3_DB']->cleanIntList($pidList) . ')
						AND (personal=0 OR cruser=' . intval($GLOBALS['BE_USER']->user['uid']) . ')' .
						t3lib_BEfunc::deleteClause('sys_note'),
					'',
					'sorting'
				);

		$out = '';

		if (count($rows) > 0) {
			$categories = array();

				// Load full table description:
			t3lib_div::loadTCA('sys_note');

				// Traverse categories
			if ($GLOBALS['TCA']['sys_note'] && $GLOBALS['TCA']['sys_note']['columns']['category']
				&& is_array($GLOBALS['TCA']['sys_note']['columns']['category']['config']['items'])
			) {
				foreach ($GLOBALS['TCA']['sys_note']['columns']['category']['config']['items'] as $el) {
					$categories[$el[1]] = $GLOBALS['LANG']->sL($el[0]);
				}
			}

				// For each note found, make rendering:
			foreach ($rows as $row) {
				if ($row['personal'] == 1 && (int) $row['cruser'] !== (int) $GLOBALS['BE_USER']->user['uid'] && $GLOBALS['BE_USER']->isAdmin() === FALSE) {
					continue;
				}
				$author = t3lib_BEfunc::getRecord('be_users', $row['cruser']);
				$authorInformation = $author['realName'];
				if (empty($authorInformation)) {
					$authorInformation = $author['username'];
				}
				$title = !(empty($categories[$row['category']])) ? $categories[$row['category']] . ': ' : '';

				$headerParts = array();
				if (!empty($authorInformation)) {
					$headerParts[] = '<span>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.author', TRUE) . '</span> ' . $authorInformation;
				}
				$headerParts[] = '<span>' . $GLOBALS['LANG']->sL('LLL:EXT:sys_note/locallang.xlf:date', TRUE) . ':</span> ' . t3lib_BEfunc::date($row['tstamp']);
				if ($row['personal'] == 1) {
					$headerParts[] = '<span>' . rtrim($GLOBALS['LANG']->sL('LLL:EXT:sys_note/locallang_tca.xlf:sys_note.personal', TRUE), ':') . '</span>';
				}

					// Compile content:
				$out .= '
					<div class="single-note category-' . $row['category'] . '">
						<div class="header">
							 ' . implode(' &middot; ', $headerParts) . '
							<div class="right">
								<a href="#" onclick="' . htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[sys_note][' . $row['uid'] . ']=edit', $GLOBALS['BACK_PATH'])) . '">' .
									t3lib_iconWorks::getSpriteIcon('actions-document-open') .
								'</a>
							</div>
						</div>
						<div class="content">
							<div class="title">' . htmlspecialchars($title)  . htmlspecialchars($row['subject']) . '</div>
							' . nl2br(htmlspecialchars($row['message'])) . '
						</div>
					</div>';
			}

			$out = '<div id="typo3-dblist-sysnotes">' . $out . '</div>';
		}

		return $out;
	}
}

?>
