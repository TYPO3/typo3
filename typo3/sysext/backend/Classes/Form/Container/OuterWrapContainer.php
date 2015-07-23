<?php
namespace TYPO3\CMS\Backend\Form\Container;

/*
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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Render header and footer row.
 *
 * This is an entry container called from controllers.
 * It wraps the title and a footer around the main html.
 * It either calls a FullRecordContainer or ListOfFieldsContainer to render
 * a full record or only some fields from a full record.
 */
class OuterWrapContainer extends AbstractContainer {

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$languageService = $this->getLanguageService();
		$backendUser = $this->getBackendUserAuthentication();

		$table = $this->data['tableName'];
		$row = $this->data['databaseRow'];

		$options = $this->data;
		if (empty($this->data['fieldListToRender'])) {
			$options['renderType'] = 'fullRecordContainer';
		} else {
			$options['renderType'] = 'listOfFieldsContainer';
		}
		$result = $this->nodeFactory->create($options)->render();

		$childHtml = $result['html'];

		$recordPath = '';
		// @todo: what is this >= 0 check for? wsol cases?!
		if ($this->data['effectivePid'] >= 0) {
			$permissionsClause = $backendUser->getPagePermsClause(1);
			$recordPath = BackendUtility::getRecordPath($this->data['effectivePid'], $permissionsClause, 15);
		}

		// @todo: Hack for getSpriteIconForRecord
		$recordForIconUtility = $row;
		if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_column']) && is_array($row[$GLOBALS['TCA'][$table]['ctrl']['typeicon_column']])) {
			$recordForIconUtility[$GLOBALS['TCA'][$table]['ctrl']['typeicon_column']] = implode(
				',',
				$row[$GLOBALS['TCA'][$table]['ctrl']['typeicon_column']]
			);
		}
		$icon = IconUtility::getSpriteIconForRecord($table, $recordForIconUtility, array('title' => $recordPath));

		// @todo: Could this be done in a more clever way? Does it work at all?
		$tableTitle = $languageService->sL($this->data['processedTca']['ctrl']['title']);

		if ($this->data['command'] === 'new') {
			$newOrUid = ' <span class="typo3-TCEforms-newToken">' . $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.new', TRUE) . '</span>';

			// @todo: There is quite some stuff do to for WS overlays ...
			$workspacedPageRecord = BackendUtility::getRecordWSOL('pages', $this->data['effectivePid'], 'title');
			$pageTitle = BackendUtility::getRecordTitle('pages', $workspacedPageRecord, TRUE, FALSE);
			if ($table === 'pages') {
				$label = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.createNewPage', TRUE);
				$pageTitle = sprintf($label, $tableTitle);
			} else {
				$label = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.createNewRecord', TRUE);
				if ($this->data['effectivePid'] === 0) {
					$label = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.createNewRecordRootLevel', TRUE);
				}
				$pageTitle = sprintf($label, $tableTitle, $pageTitle);
			}
		} else {
			// DocumentTemplate is needed for wrapClickMenuOnIcon(), the method has no state, simply use fresh instance
			/** @var DocumentTemplate $documentTemplate */
			$documentTemplate = GeneralUtility::makeInstance(DocumentTemplate::class);
			$icon = $documentTemplate->wrapClickMenuOnIcon($icon, $table, $row['uid'], 1, '', '+copy,info,edit,view');

			$newOrUid = ' <span class="typo3-TCEforms-recUid">[' . htmlspecialchars($row['uid']) . ']</span>';

			$recordLabel = BackendUtility::getRecordTitle($table, $row, TRUE, FALSE);
			if ($table === 'pages') {
				$label = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.editPage', TRUE);
				$pageTitle = sprintf($label, $tableTitle, $recordLabel);
			} else {
				$label = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.editRecord', TRUE);
				$workspacedPageRecord = BackendUtility::getRecordWSOL('pages', $row['pid'], 'uid,title');
				$pageTitle = BackendUtility::getRecordTitle('pages', $workspacedPageRecord, TRUE, FALSE);
				if ($recordLabel === BackendUtility::getNoRecordTitle(TRUE)) {
					$label = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.editRecordNoTitle', TRUE);
				}
				if ($this->data['effectivePid'] === 0) {
					$label = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.editRecordRootLevel', TRUE);
				}
				if ($recordLabel !== BackendUtility::getNoRecordTitle(TRUE)) {
					// Use record title and prepend an edit label.
					$pageTitle = sprintf($label, $tableTitle, $recordLabel, $pageTitle);
				} else {
					// Leave out the record title since it is not set.
					$pageTitle = sprintf($label, $tableTitle, $pageTitle);
				}
			}
		}

		$html = array();
		$html[] = '<h1>' . $pageTitle . '</h1>';
		$html[] = '<div class="typo3-TCEforms">';
		$html[] = 	$childHtml;
		$html[] = 	'<div class="help-block text-right">';
		$html[] = 		$icon . ' <strong>' . htmlspecialchars($tableTitle) . '</strong>' . ' ' . $newOrUid;
		$html[] = 	'</div>';
		$html[] = '</div>';

		$result['html'] = implode(LF, $html);
		return $result;
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

}
