<?php
namespace TYPO3\CMS\Backend\Form\Element;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Generation of TCEform elements of the type "group"
 */
class GroupElement extends AbstractFormElement {

	/**
	 * This will render a selectorbox into which elements from either
	 * the file system or database can be inserted. Relations.
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $additionalInformation An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 */
	public function render($table, $field, $row, &$additionalInformation) {
		$config = $additionalInformation['fieldConf']['config'];
		$show_thumbs = $config['show_thumbs'];
		$size = isset($config['size']) ? (int)$config['size'] : 5;
		$maxitems = MathUtility::forceIntegerInRange($config['maxitems'], 0);
		if (!$maxitems) {
			$maxitems = 100000;
		}
		$minitems = MathUtility::forceIntegerInRange($config['minitems'], 0);
		$allowed = trim($config['allowed']);
		$disallowed = trim($config['disallowed']);
		$item = '';
		$disabled = '';
		if ($this->formEngine->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		$item .= '<input type="hidden" name="' . $additionalInformation['itemFormElName'] . '_mul" value="' . ($config['multiple'] ? 1 : 0) . '"' . $disabled . ' />';
		$this->formEngine->registerRequiredProperty('range', $additionalInformation['itemFormElName'], array($minitems, $maxitems, 'imgName' => $table . '_' . $row['uid'] . '_' . $field));
		$info = '';
		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist.
		$specConf = $this->formEngine->getSpecConfFromString($additionalInformation['extra'], $additionalInformation['fieldConf']['defaultExtras']);
		$additionalInformation['itemFormElID_file'] = $additionalInformation['itemFormElID'] . '_files';
		// whether the list and delete controls should be disabled
		$noList = isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'list');
		$noDelete = isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'delete');
		// if maxitems==1 then automatically replace the current item (in list and file selector)
		if ($maxitems === 1) {
			$this->formEngine->additionalJS_post[] = 'TBE_EDITOR.clearBeforeSettingFormValueFromBrowseWin[\'' . $additionalInformation['itemFormElName'] . '\'] = {
					itemFormElID_file: \'' . $additionalInformation['itemFormElID_file'] . '\'
				}';
			$additionalInformation['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = 'setFormValueManipulate(\'' . $additionalInformation['itemFormElName']
				. '\', \'Remove\'); ' . $additionalInformation['fieldChangeFunc']['TBE_EDITOR_fieldChanged'];
		} elseif ($noList) {
			// If the list controls have been removed and the maximum number is reached, remove the first entry to avoid "write once" field
			$additionalInformation['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = 'setFormValueManipulate(\'' . $additionalInformation['itemFormElName']
				. '\', \'RemoveFirstIfFull\', \'' . $maxitems . '\'); ' . $additionalInformation['fieldChangeFunc']['TBE_EDITOR_fieldChanged'];
		}
		// Acting according to either "file" or "db" type:
		switch ((string) $config['internal_type']) {
			case 'file_reference':
				$config['uploadfolder'] = '';
				// Fall through
			case 'file':
				// Creating string showing allowed types:
				$tempFT = GeneralUtility::trimExplode(',', $allowed, TRUE);
				if (!count($tempFT)) {
					$info .= '*';
				}
				foreach ($tempFT as $ext) {
					if ($ext) {
						$info .= strtoupper($ext) . ' ';
					}
				}
				// Creating string, showing disallowed types:
				$tempFT_dis = GeneralUtility::trimExplode(',', $disallowed, TRUE);
				if (count($tempFT_dis)) {
					$info .= '<br />';
				}
				foreach ($tempFT_dis as $ext) {
					if ($ext) {
						$info .= '-' . strtoupper($ext) . ' ';
					}
				}
				// Making the array of file items:
				$itemArray = GeneralUtility::trimExplode(',', $additionalInformation['itemFormElValue'], TRUE);
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
				$thumbsnail = '';
				if ($show_thumbs) {
					$imgs = array();
					foreach ($itemArray as $imgRead) {
						$imgP = explode('|', $imgRead);
						$imgPath = rawurldecode($imgP[0]);
						// FAL icon production
						if (MathUtility::canBeInterpretedAsInteger($imgP[0])) {
							$fileObject = $fileFactory->getFileObject($imgP[0]);

							if ($fileObject->isMissing()) {
								$flashMessage = \TYPO3\CMS\Core\Resource\Utility\BackendUtility::getFlashMessageForMissingFile($fileObject);
								$imgs[] = $flashMessage->render();
							} elseif (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileObject->getExtension())) {
								$imageUrl = $fileObject->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, array())->getPublicUrl(TRUE);
								$imgTag = '<img src="' . $imageUrl . '" alt="' . htmlspecialchars($fileObject->getName()) . '" />';
								$imgs[] = '<span class="nobr">' . $imgTag . htmlspecialchars($fileObject->getName()) . '</span>';
							} else {
								// Icon
								$imgTag = IconUtility::getSpriteIconForResource($fileObject, array('title' => $fileObject->getName()));
								$imgs[] = '<span class="nobr">' . $imgTag . htmlspecialchars($fileObject->getName()) . '</span>';
							}
						} else {
							$rowCopy = array();
							$rowCopy[$field] = $imgPath;
							$thumbnailCode = '';
							try {
								$thumbnailCode = BackendUtility::thumbCode(
									$rowCopy, $table, $field, $this->formEngine->backPath, 'thumbs.php',
									$config['uploadfolder'], 0, ' align="middle"'
								);
								$thumbnailCode = '<span class="nobr">' . $thumbnailCode . $imgPath . '</span>';

							} catch (\Exception $exception) {
								/** @var $flashMessage FlashMessage */
								$message = $exception->getMessage();
								$flashMessage = GeneralUtility::makeInstance(
									'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
									htmlspecialchars($message), '', FlashMessage::ERROR, TRUE
								);
								/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
								$flashMessageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
								$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
								$defaultFlashMessageQueue->enqueue($flashMessage);

								$logMessage = $message . ' (' . $table . ':' . $row['uid'] . ')';
								GeneralUtility::sysLog($logMessage, 'core', GeneralUtility::SYSLOG_SEVERITY_WARNING);
							}

							$imgs[] = $thumbnailCode;
						}
					}
					$thumbsnail = implode('<br />', $imgs);
				}
				// Creating the element:
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => $maxitems <= 1,
					'autoSizeMax' => MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle'])
						? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"'
						: ' style="' . $this->formEngine->defaultMultipleSelectorStyle . '"',
					'info' => $info,
					'thumbnails' => $thumbsnail,
					'readOnly' => $disabled,
					'noBrowser' => $noList || isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'browser'),
					'noList' => $noList,
					'noDelete' => $noDelete
				);
				$item .= $this->formEngine->dbFileIcons($additionalInformation['itemFormElName'], 'file', implode(',', $tempFT), $itemArray, '', $params, $additionalInformation['onFocus'], '', '', '', $config);
				if (!$disabled && !(isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'upload'))) {
					// Adding the upload field:
					if ($this->formEngine->edit_docModuleUpload && $config['uploadfolder']) {
						// Insert the multiple attribute to enable HTML5 multiple file upload
						$multipleAttribute = '';
						$multipleFilenameSuffix = '';
						if (isset($config['maxitems']) && $config['maxitems'] > 1) {
							$multipleAttribute = ' multiple="multiple"';
							$multipleFilenameSuffix = '[]';
						}
						$item .= '<div id="' . $additionalInformation['itemFormElID_file'] . '"><input type="file"' . $multipleAttribute
							. ' name="' . $additionalInformation['itemFormElName_file'] . $multipleFilenameSuffix . '" size="35" onchange="'
							. implode('', $additionalInformation['fieldChangeFunc']) . '" /></div>';
					}
				}
				break;
			case 'folder':
				// If the element is of the internal type "folder":
				// Array of folder items:
				$itemArray = GeneralUtility::trimExplode(',', $additionalInformation['itemFormElValue'], TRUE);
				// Creating the element:
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => $maxitems <= 1,
					'autoSizeMax' => MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle'])
						? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"'
						: ' style="' . $this->formEngine->defaultMultipleSelectorStyle . '"',
					'info' => $info,
					'readOnly' => $disabled,
					'noBrowser' => $noList || isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'browser'),
					'noList' => $noList
				);
				$item .= $this->formEngine->dbFileIcons($additionalInformation['itemFormElName'], 'folder', '', $itemArray, '', $params, $additionalInformation['onFocus']);
				break;
			case 'db':
				// If the element is of the internal type "db":
				// Creating string showing allowed types:
				$tempFT = GeneralUtility::trimExplode(',', $allowed, TRUE);
				$onlySingleTableAllowed = FALSE;
				if (trim($tempFT[0]) === '*') {
					$info .= '<span class="nobr">' . htmlspecialchars($this->formEngine->getLL('l_allTables')) . '</span><br />';
				} elseif ($tempFT) {
					$onlySingleTableAllowed = count($tempFT) == 1;
					foreach ($tempFT as $theT) {
						$aOnClick = 'setFormValueOpenBrowser(\'db\', \'' . ($additionalInformation['itemFormElName'] . '|||' . $theT) . '\'); return false;';
						$info .= '<span class="nobr">
									<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">'
							. IconUtility::getSpriteIconForRecord($theT, array())
							. htmlspecialchars($this->formEngine->sL($GLOBALS['TCA'][$theT]['ctrl']['title'])) . '</a></span><br />';
					}
				}
				$perms_clause = $this->getBackendUserAuthentication()->getPagePermsClause(1);
				$itemArray = array();
				$imgs = array();
				// Thumbnails:
				$temp_itemArray = GeneralUtility::trimExplode(',', $additionalInformation['itemFormElValue'], TRUE);
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
						$imgs[] = '<span class="nobr">' . $this->formEngine->getClickMenu(IconUtility::getSpriteIconForRecord($this_table, $rr, array(
								'style' => 'vertical-align:top',
								'title' => htmlspecialchars((BackendUtility::getRecordPath($rr['pid'], $perms_clause, 15) . ' [UID: ' . $rr['uid'] . ']'))
							)), $this_table, $this_uid) . '&nbsp;' . BackendUtility::getRecordTitle($this_table, $rr, TRUE)
							. ' <span class="typo3-dimmed"><em>[' . $rr['uid'] . ']</em></span>' . '</span>';
					}
				}
				$thumbsnail = '';
				if (!$disabled && $show_thumbs) {
					$thumbsnail = implode('<br />', $imgs);
				}
				// Creating the element:
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => $maxitems <= 1,
					'autoSizeMax' => MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle'])
						? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"'
						: ' style="' . $this->formEngine->defaultMultipleSelectorStyle . '"',
					'info' => $info,
					'thumbnails' => $thumbsnail,
					'readOnly' => $disabled,
					'noBrowser' => $noList || isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'browser'),
					'noList' => $noList
				);
				$item .= $this->formEngine->dbFileIcons($additionalInformation['itemFormElName'], 'db', implode(',', $tempFT), $itemArray, '', $params, $additionalInformation['onFocus'], $table, $field, $row['uid'], $config);
				break;
		}
		// Wizards:
		$altItem = '<input type="hidden" name="' . $additionalInformation['itemFormElName'] . '" value="' . htmlspecialchars($additionalInformation['itemFormElValue']) . '" />';
		if (!$disabled) {
			$item = $this->formEngine->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $additionalInformation, $additionalInformation['itemFormElName'], $specConf);
		}
		return $item;
	}
}
