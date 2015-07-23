<?php
namespace TYPO3\CMS\Backend\Form\Element;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Generation of TCEform elements of the type "group"
 */
class GroupElement extends AbstractFormElement {

	/**
	 * This will render a selector box into which elements from either
	 * the file system or database can be inserted. Relations.
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 */
	public function render() {
		$table = $this->data['tableName'];
		$fieldName = $this->data['fieldName'];
		$row = $this->data['databaseRow'];
		$parameterArray = $this->data['parameterArray'];
		$config = $parameterArray['fieldConf']['config'];
		$show_thumbs = $config['show_thumbs'];
		$resultArray = $this->initializeResultArray();

		$size = isset($config['size']) ? (int)$config['size'] : $this->minimumInputWidth;
		$maxitems = MathUtility::forceIntegerInRange($config['maxitems'], 0);
		if (!$maxitems) {
			$maxitems = 100000;
		}
		$minitems = MathUtility::forceIntegerInRange($config['minitems'], 0);
		$thumbnails = array();
		$allowed = GeneralUtility::trimExplode(',', $config['allowed'], TRUE);
		$disallowed = GeneralUtility::trimExplode(',', $config['disallowed'], TRUE);
		$disabled = $config['readOnly'];
		$info = array();
		$parameterArray['itemFormElID_file'] = $parameterArray['itemFormElID'] . '_files';

		// whether the list and delete controls should be disabled
		$noList = isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'list');
		$noDelete = isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'delete');

		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist.
		$specConf = BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras']);

		// Register properties in required elements / validation
		$attributes['data-formengine-validation-rules'] = htmlspecialchars(
			$this->getValidationDataAsJsonString(
				array(
					'minitems' => $minitems,
					'maxitems' => $maxitems
				)
			)
		);

		// If maxitems==1 then automatically replace the current item (in list and file selector)
		if ($maxitems === 1) {
			$resultArray['additionalJavaScriptPost'][] =
				'TBE_EDITOR.clearBeforeSettingFormValueFromBrowseWin[' . GeneralUtility::quoteJSvalue($parameterArray['itemFormElName']) . '] = {
					itemFormElID_file: ' . GeneralUtility::quoteJSvalue($parameterArray['itemFormElID_file']) . '
				}';
			$parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = 'setFormValueManipulate(' . GeneralUtility::quoteJSvalue($parameterArray['itemFormElName'])
				. ', \'Remove\'); ' . $parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'];
		} elseif ($noList) {
			// If the list controls have been removed and the maximum number is reached, remove the first entry to avoid "write once" field
			$parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = 'setFormValueManipulate(' . GeneralUtility::quoteJSvalue($parameterArray['itemFormElName'])
				. ', \'RemoveFirstIfFull\', ' . GeneralUtility::quoteJSvalue($maxitems) . '); ' . $parameterArray['fieldChangeFunc']['TBE_EDITOR_fieldChanged'];
		}

		$html = '<input type="hidden" class="t3js-group-hidden-field" name="' . $parameterArray['itemFormElName'] . '_mul" value="' . ($config['multiple'] ? 1 : 0) . '"' . $disabled . ' />';

		// Define parameters for all types below
		$commonParameters = array(
			'size' => $size,
			'dontShowMoveIcons' => isset($config['hideMoveIcons']) || $maxitems <= 1,
			'autoSizeMax' => MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
			'maxitems' => $maxitems,
			'style' => isset($config['selectedListStyle'])
				? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"'
				: '',
			'readOnly' => $disabled,
			'noBrowser' => $noList || isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'browser'),
			'noList' => $noList,
		);

		// Acting according to either "file" or "db" type:
		switch ((string)$config['internal_type']) {
			case 'file_reference':
				$config['uploadfolder'] = '';
				// Fall through
			case 'file':
				// Creating string showing allowed types:
				if (empty($allowed)) {
					$allowed = array('*');
				}
				// Making the array of file items:
				$itemArray = GeneralUtility::trimExplode(',', $parameterArray['itemFormElValue'], TRUE);
				$fileFactory = ResourceFactory::getInstance();
				// Correct the filename for the FAL items
				foreach ($itemArray as &$fileItem) {
					list($fileUid, $fileLabel) = explode('|', $fileItem);
					if (MathUtility::canBeInterpretedAsInteger($fileUid)) {
						$fileObject = $fileFactory->getFileObject($fileUid);
						$fileLabel = $fileObject->getName();
					}
					$fileItem = $fileUid . '|' . $fileLabel;
				}
				// Showing thumbnails:
				if ($show_thumbs) {
					foreach ($itemArray as $imgRead) {
						$imgP = explode('|', $imgRead);
						$imgPath = rawurldecode($imgP[0]);
						// FAL icon production
						if (MathUtility::canBeInterpretedAsInteger($imgP[0])) {
							$fileObject = $fileFactory->getFileObject($imgP[0]);
							if ($fileObject->isMissing()) {
								$thumbnails[] = array(
									'message' => \TYPO3\CMS\Core\Resource\Utility\BackendUtility::getFlashMessageForMissingFile($fileObject)->render()
								);
							} elseif (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileObject->getExtension())) {
								$thumbnails[] = array(
									'name' => htmlspecialchars($fileObject->getName()),
									'image' => $fileObject->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, array())->getPublicUrl(TRUE)
								);
							} else {
								// Icon
								$thumbnails[] = array(
									'name' => htmlspecialchars($fileObject->getName()),
									'image' => IconUtility::getSpriteIconForResource($fileObject, array('title' => $fileObject->getName()))
								);
							}
						} else {
							$rowCopy = array();
							$rowCopy[$fieldName] = $imgPath;
							try {
								$thumbnails[] = array(
									'name' => $imgPath,
									'image' => BackendUtility::thumbCode(
										$rowCopy,
										$table,
										$fieldName,
										'',
										'',
										$config['uploadfolder'],
										0,
										' align="middle"'
									)
								);
							} catch (\Exception $exception) {
								/** @var $flashMessage FlashMessage */
								$message = $exception->getMessage();
								$flashMessage = GeneralUtility::makeInstance(
									FlashMessage::class,
									htmlspecialchars($message), '', FlashMessage::ERROR, TRUE
								);
								/** @var $flashMessageService FlashMessageService */
								$flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
								$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
								$defaultFlashMessageQueue->enqueue($flashMessage);
								$logMessage = $message . ' (' . $table . ':' . $row['uid'] . ')';
								GeneralUtility::sysLog($logMessage, 'core', GeneralUtility::SYSLOG_SEVERITY_WARNING);
							}
						}
					}
				}
				// Creating the element:
				$params = array_merge($commonParameters, array(
					'allowed' => $allowed,
					'disallowed' => $disallowed,
					'thumbnails' => $thumbnails,
					'noDelete' => $noDelete
				));
				$html .= $this->dbFileIcons(
					$parameterArray['itemFormElName'],
					'file',
					implode(',', $allowed),
					$itemArray,
					'',
					$params,
					$parameterArray['onFocus'],
					'',
					'',
					'',
					$config);
				if (!$disabled && !(isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'upload'))) {
					// Adding the upload field:
					$isDirectFileUploadEnabled = (bool)$this->getBackendUserAuthentication()->uc['edit_docModuleUpload'];
					if ($isDirectFileUploadEnabled && $config['uploadfolder']) {
						// Insert the multiple attribute to enable HTML5 multiple file upload
						$multipleAttribute = '';
						$multipleFilenameSuffix = '';
						if (isset($config['maxitems']) && $config['maxitems'] > 1) {
							$multipleAttribute = ' multiple="multiple"';
							$multipleFilenameSuffix = '[]';
						}
						$html .= '
							<div id="' . $parameterArray['itemFormElID_file'] . '">
								<input type="file"' . $multipleAttribute . '
									name="data_files' . $this->data['elementBaseName'] . $multipleFilenameSuffix . '"
									size="35" onchange="' . implode('', $parameterArray['fieldChangeFunc']) . '"
								/>
							</div>';
					}
				}
				break;
			case 'folder':
				// If the element is of the internal type "folder":
				// Array of folder items:
				$itemArray = GeneralUtility::trimExplode(',', $parameterArray['itemFormElValue'], TRUE);
				// Creating the element:
				$params = $commonParameters;
				$html .= $this->dbFileIcons(
					$parameterArray['itemFormElName'],
					'folder',
					'',
					$itemArray,
					'',
					$params,
					$parameterArray['onFocus']
				);
				break;
			case 'db':
				// If the element is of the internal type "db":
				// Creating string showing allowed types:
				$onlySingleTableAllowed = FALSE;
				$languageService = $this->getLanguageService();

				$allowedTables = array();
				if ($allowed[0] === '*') {
					$allowedTables = array(
						'name' => htmlspecialchars($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.allTables'))
					);
				} elseif ($allowed) {
					$onlySingleTableAllowed = count($allowed) === 1;
					foreach ($allowed as $allowedTable) {
						$allowedTables[] = array(
							// @todo: access to globals!
							'name' => htmlspecialchars($languageService->sL($GLOBALS['TCA'][$allowedTable]['ctrl']['title'])),
							'icon' => IconUtility::getSpriteIconForRecord($allowedTable, array()),
							'onClick' => 'setFormValueOpenBrowser(\'db\', ' . GeneralUtility::quoteJSvalue($parameterArray['itemFormElName'] . '|||' . $allowedTable) . '); return false;'
						);
					}
				}
				$perms_clause = $this->getBackendUserAuthentication()->getPagePermsClause(1);
				$itemArray = array();

				// Thumbnails:
				// @todo: this is data processing - must be extracted
				$temp_itemArray = GeneralUtility::trimExplode(',', $parameterArray['itemFormElValue'], TRUE);
				foreach ($temp_itemArray as $dbRead) {
					$recordParts = explode('|', $dbRead);
					list($this_table, $this_uid) = BackendUtility::splitTable_Uid($recordParts[0]);
					// For the case that no table was found and only a single table is defined to be allowed, use that one:
					if (!$this_table && $onlySingleTableAllowed) {
						$this_table = $allowed;
					}
					$itemArray[] = array('table' => $this_table, 'id' => $this_uid);
					if (!$disabled && $show_thumbs) {
						$rr = BackendUtility::getRecordWSOL($this_table, $this_uid);
						$thumbnails[] = array(
							'name' => BackendUtility::getRecordTitle($this_table, $rr, TRUE),
							'image' => IconUtility::getSpriteIconForRecord($this_table, $rr),
							'path' => BackendUtility::getRecordPath($rr['pid'], $perms_clause, 15),
							'uid' => $rr['uid'],
							'table' => $this_table
						);
					}
				}
				// Creating the element:
				$params = array_merge($commonParameters, array(
					'info' => $info,
					'allowedTables' => $allowedTables,
					'thumbnails' => $thumbnails,
				));
				$html .= $this->dbFileIcons(
					$parameterArray['itemFormElName'],
					'db',
					implode(',', $allowed),
					$itemArray,
					'',
					$params,
					$parameterArray['onFocus'],
					$table,
					$fieldName,
					$row['uid'],
					$config
				);
				break;
		}
		// Wizards:
		if (!$disabled) {
			$html = $this->renderWizards(
				array($html),
				$config['wizards'],
				$table,
				$row,
				$fieldName,
				$parameterArray,
				$parameterArray['itemFormElName'],
				$specConf
			);
		}
		$resultArray['html'] = $html;
		return $resultArray;
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

}
