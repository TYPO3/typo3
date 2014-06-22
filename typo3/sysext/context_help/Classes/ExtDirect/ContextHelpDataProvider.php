<?php
namespace TYPO3\CMS\ContextHelp\ExtDirect;

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
 * ExtDirect DataProvider for ContextHelp
 *
 * @author Steffen Kamper <steffen@typo3.org>
 */
class ContextHelpDataProvider {

	/**
	 * Fetch the context help for the given table/field parameters
	 *
	 * @param string $table Table identifier
	 * @param string $field Field identifier
	 * @return array complete Help information
	 */
	public function getContextHelp($table, $field) {
		$helpTextArray = \TYPO3\CMS\Backend\Utility\BackendUtility::helpTextArray($table, $field);
		$moreIcon = $helpTextArray['moreInfo'] ? \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-view-go-forward') : '';
		return array(
			'title' => $helpTextArray['title'],
			'description' => '<p class="t3-help-short' . ($moreIcon ? ' tipIsLinked' : '') . '">' . $helpTextArray['description'] . $moreIcon . '</p>',
			'id' => $table . '.' . $field,
			'moreInfo' => $helpTextArray['moreInfo']
		);
	}

	/**
	 * Fetch the context help for the given table
	 *
	 * @param string $table Table identifier
	 * @return array Complete help information
	 */
	public function getTableContextHelp($table) {
		$output = array();
		if (!isset($GLOBALS['TCA_DESCR'][$table]['columns'])) {
			$GLOBALS['LANG']->loadSingleTableDescription($table);
		}
		if (is_array($GLOBALS['TCA_DESCR'][$table]) && is_array($GLOBALS['TCA_DESCR'][$table]['columns'])) {
			$arrow = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-view-go-forward');
			foreach ($GLOBALS['TCA_DESCR'][$table]['columns'] as $field => $data) {
				$output[$field] = array(
					'description' => NULL,
					'title' => NULL,
					'moreInfo' => FALSE,
					'id' => $table . '.' . $field
				);
				// Add alternative title, if defined
				if ($data['alttitle']) {
					$output[$field]['title'] = $data['alttitle'];
				}
				// If we have more information to show
				if ($data['image_descr'] || $data['seeAlso'] || $data['details'] || $data['syntax']) {
					$output[$field]['moreInfo'] = TRUE;
				}
				// Add description
				if ($data['description']) {
					$output[$field]['description'] = $data['description'] . ($output[$field]['moreInfo'] ? $arrow : '');
				}
			}
		}
		return $output;
	}

}
