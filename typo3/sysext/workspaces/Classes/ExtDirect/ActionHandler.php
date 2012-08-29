<?php
namespace TYPO3\CMS\Workspaces\ExtDirect;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 * ExtDirect action handler
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 */
class ActionHandler extends \TYPO3\CMS\Workspaces\ExtDirect\AbstractHandler {

	/**
	 * @var \TYPO3\CMS\Workspaces\Service\StagesService
	 */
	protected $stageService;

	/**
	 * Creates this object.
	 */
	public function __construct() {
		$this->stageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\StagesService');
	}

	/**
	 * Generates a workspace preview link.
	 *
	 * @param integer $uid The ID of the record to be linked
	 * @return string the full domain including the protocol http:// or https://, but without the trailing '/'
	 */
	public function generateWorkspacePreviewLink($uid) {
		return $this->getWorkspaceService()->generateWorkspacePreviewLink($uid);
	}

	/**
	 * Swaps a single record.
	 *
	 * @param string $table
	 * @param integer $t3ver_oid
	 * @param integer $orig_uid
	 * @return void
	 * @todo What about reporting errors back to the ExtJS interface? /olly/
	 */
	public function swapSingleRecord($table, $t3ver_oid, $orig_uid) {
		$cmd[$table][$t3ver_oid]['version'] = array(
			'action' => 'swap',
			'swapWith' => $orig_uid,
			'swapIntoWS' => 1
		);
		$this->processTcaCmd($cmd);
	}

	/**
	 * Deletes a single record.
	 *
	 * @param string $table
	 * @param integer $uid
	 * @return void
	 * @todo What about reporting errors back to the ExtJS interface? /olly/
	 */
	public function deleteSingleRecord($table, $uid) {
		$cmd[$table][$uid]['version'] = array(
			'action' => 'clearWSID'
		);
		$this->processTcaCmd($cmd);
	}

	/**
	 * Generates a view link for a page.
	 *
	 * @param string $table
	 * @param string $uid
	 * @return string
	 */
	public function viewSingleRecord($table, $uid) {
		return \TYPO3\CMS\Workspaces\Service\WorkspaceService::viewSingleRecord($table, $uid);
	}

	/**
	 * Saves the selected columns to be shown to the preferences of the current backend user.
	 *
	 * @param object $model
	 * @return void
	 */
	public function saveColumnModel($model) {
		$data = array();
		foreach ($model as $column) {
			$data[$column->column] = array(
				'position' => $column->position,
				'hidden' => $column->hidden
			);
		}
		$GLOBALS['BE_USER']->uc['moduleData']['Workspaces'][$GLOBALS['BE_USER']->workspace]['columns'] = $data;
		$GLOBALS['BE_USER']->writeUC();
	}

	public function loadColumnModel() {
		if (is_array($GLOBALS['BE_USER']->uc['moduleData']['Workspaces'][$GLOBALS['BE_USER']->workspace]['columns'])) {
			return $GLOBALS['BE_USER']->uc['moduleData']['Workspaces'][$GLOBALS['BE_USER']->workspace]['columns'];
		} else {
			return array();
		}
	}

	/**
	 * Saves the selected language.
	 *
	 * @param integer|string $language
	 * @return void
	 */
	public function saveLanguageSelection($language) {
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($language) === FALSE && $language !== 'all') {
			$language = 'all';
		}
		$GLOBALS['BE_USER']->uc['moduleData']['Workspaces'][$GLOBALS['BE_USER']->workspace]['language'] = $language;
		$GLOBALS['BE_USER']->writeUC();
	}

	/**
	 * Gets the dialog window to be displayed before a record can be sent to the next stage.
	 *
	 * @param integer $uid
	 * @param string $table
	 * @param integer $t3ver_oid
	 * @return array
	 */
	public function sendToNextStageWindow($uid, $table, $t3ver_oid) {
		$elementRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $uid);
		if (is_array($elementRecord)) {
			$stageId = $elementRecord['t3ver_stage'];
			if ($this->getStageService()->isValid($stageId)) {
				$nextStage = $this->getStageService()->getNextStage($stageId);
				$result = $this->getSentToStageWindow($nextStage['uid']);
				$result['affects'] = array(
					'table' => $table,
					'nextStage' => $nextStage['uid'],
					't3ver_oid' => $t3ver_oid,
					'uid' => $uid
				);
			} else {
				$result = $this->getErrorResponse('error.stageId.invalid', 1291111644);
			}
		} else {
			$result = $this->getErrorResponse('error.sendToNextStage.noRecordFound', 1287264776);
		}
		return $result;
	}

	/**
	 * Gets the dialog window to be displayed before a record can be sent to the previous stage.
	 *
	 * @param integer $uid
	 * @param string $table
	 * @return array
	 */
	public function sendToPrevStageWindow($uid, $table) {
		$elementRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $uid);
		if (is_array($elementRecord)) {
			$stageId = $elementRecord['t3ver_stage'];
			if ($this->getStageService()->isValid($stageId)) {
				if ($stageId !== \TYPO3\CMS\Workspaces\Service\StagesService::STAGE_EDIT_ID) {
					$prevStage = $this->getStageService()->getPrevStage($stageId);
					$result = $this->getSentToStageWindow($prevStage['uid']);
					$result['affects'] = array(
						'table' => $table,
						'uid' => $uid,
						'nextStage' => $prevStage['uid']
					);
				} else {
					// element is already in edit stage, there is no prev stage - return an error message
					$result = $this->getErrorResponse('error.sendToPrevStage.noPreviousStage', 1287264746);
				}
			} else {
				$result = $this->getErrorResponse('error.stageId.invalid', 1291111644);
			}
		} else {
			$result = $this->getErrorResponse('error.sendToNextStage.noRecordFound', 1287264765);
		}
		return $result;
	}

	/**
	 * Gets the dialog window to be displayed before a record can be sent to a specific stage.
	 *
	 * @param integer $nextStageId
	 * @return array
	 */
	public function sendToSpecificStageWindow($nextStageId) {
		$result = $this->getSentToStageWindow($nextStageId);
		$result['affects'] = array(
			'nextStage' => $nextStageId
		);
		return $result;
	}

	/**
	 * Gets a merged variant of recipient defined by uid and custom ones.
	 *
	 * @param array list of recipients
	 * @param string given user string of additional recipients
	 * @param integer stage id
	 * @return array
	 */
	public function getRecipientList(array $uidOfRecipients, $additionalRecipients, $stageId) {
		$finalRecipients = array();
		if (!$this->getStageService()->isValid($stageId)) {
			throw new \InvalidArgumentException($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:error.stageId.integer'));
		} else {
			$stageId = (int) $stageId;
		}
		$recipients = array();
		foreach ($uidOfRecipients as $userUid) {
			$beUserRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('be_users', intval($userUid));
			if (is_array($beUserRecord) && $beUserRecord['email'] !== '') {
				$uc = $beUserRecord['uc'] ? unserialize($beUserRecord['uc']) : array();
				$recipients[$beUserRecord['email']] = array(
					'email' => $beUserRecord['email'],
					'lang' => isset($uc['lang']) ? $uc['lang'] : $beUserRecord['lang']
				);
			}
		}
		// the notification mode can be configured in the workspace stage record
		$notification_mode = $this->getStageService()->getNotificationMode($stageId);
		if (intval($notification_mode) === \TYPO3\CMS\Workspaces\Service\StagesService::MODE_NOTIFY_ALL || intval($notification_mode) === \TYPO3\CMS\Workspaces\Service\StagesService::MODE_NOTIFY_ALL_STRICT) {
			// get the default recipients from the stage configuration
			// the default recipients needs to be added in some cases of the notification_mode
			$default_recipients = $this->getStageService()->getResponsibleBeUser($stageId, TRUE);
			foreach ($default_recipients as $default_recipient_uid => $default_recipient_record) {
				if (!isset($recipients[$default_recipient_record['email']])) {
					$uc = $default_recipient_record['uc'] ? unserialize($default_recipient_record['uc']) : array();
					$recipients[$default_recipient_record['email']] = array(
						'email' => $default_recipient_record['email'],
						'lang' => isset($uc['lang']) ? $uc['lang'] : $default_recipient_record['lang']
					);
				}
			}
		}
		if ($additionalRecipients !== '') {
			$emails = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(LF, $additionalRecipients, TRUE);
			$additionalRecipients = array();
			foreach ($emails as $email) {
				$additionalRecipients[$email] = array('email' => $email);
			}
		} else {
			$additionalRecipients = array();
		}
		// We merge $recipients on top of $additionalRecipients because $recipients
		// possibly is more complete with a user language. Furthermore, the list of
		// recipients is automatically unique since we indexed $additionalRecipients
		// and $recipients with the email address
		$allRecipients = array_merge($additionalRecipients, $recipients);
		foreach ($allRecipients as $email => $recipientInformation) {
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($email)) {
				$finalRecipients[] = $recipientInformation;
			}
		}
		return $finalRecipients;
	}

	/**
	 * Discard all items from given page id.
	 *
	 * @param integer $pageId
	 * @return array
	 * @author Michael Klapper <development@morphodo.com>
	 */
	public function discardStagesFromPage($pageId) {
		$cmdMapArray = array();
		/** @var $workspaceService \TYPO3\CMS\Workspaces\Service\WorkspaceService */
		$workspaceService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
		/** @var $stageService \TYPO3\CMS\Workspaces\Service\StagesService */
		$stageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\StagesService');
		$workspaceItemsArray = $workspaceService->selectVersionsInWorkspace($stageService->getWorkspaceId(), ($filter = 1), ($stage = -99), $pageId, ($recursionLevel = 0), ($selectionType = 'tables_modify'));
		foreach ($workspaceItemsArray as $tableName => $items) {
			foreach ($items as $item) {
				$cmdMapArray[$tableName][$item['uid']]['version']['action'] = 'clearWSID';
			}
		}
		$this->processTcaCmd($cmdMapArray);
		return array(
			'success' => TRUE
		);
	}

	/**
	 * Push the given element collection to the next workspace stage.
	 *
	 * <code>
	 * $parameters->additional = your@mail.com
	 * $parameters->affects->__TABLENAME__
	 * $parameters->comments
	 * $parameters->receipients
	 * $parameters->stageId
	 * </code>
	 *
	 * @param stdClass $parameters
	 * @return array
	 * @author Michael Klapper <development@morphodo.com>
	 */
	public function sentCollectionToStage(\stdClass $parameters) {
		$cmdMapArray = array();
		$comment = $parameters->comments;
		$stageId = $parameters->stageId;
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($stageId) === FALSE) {
			throw new \InvalidArgumentException('Missing "stageId" in $parameters array.', 1319488194);
		}
		if (!is_object($parameters->affects) || count($parameters->affects) == 0) {
			throw new \InvalidArgumentException('Missing "affected items" in $parameters array.', 1319488195);
		}
		$recipients = $this->getRecipientList($parameters->receipients, $parameters->additional, $stageId);
		foreach ($parameters->affects as $tableName => $items) {
			foreach ($items as $item) {
				if ($stageId == \TYPO3\CMS\Workspaces\Service\StagesService::STAGE_PUBLISH_EXECUTE_ID) {
					$cmdMapArray[$tableName][$item->t3ver_oid]['version']['action'] = 'swap';
					$cmdMapArray[$tableName][$item->t3ver_oid]['version']['swapWith'] = $item->uid;
					$cmdMapArray[$tableName][$item->t3ver_oid]['version']['comment'] = $comment;
					$cmdMapArray[$tableName][$item->t3ver_oid]['version']['notificationAlternativeRecipients'] = $recipients;
				} else {
					$cmdMapArray[$tableName][$item->uid]['version']['action'] = 'setStage';
					$cmdMapArray[$tableName][$item->uid]['version']['stageId'] = $stageId;
					$cmdMapArray[$tableName][$item->uid]['version']['comment'] = $comment;
					$cmdMapArray[$tableName][$item->uid]['version']['notificationAlternativeRecipients'] = $recipients;
				}
			}
		}
		$this->processTcaCmd($cmdMapArray);
		return array(
			'success' => TRUE,
			// force refresh after publishing changes
			'refreshLivePanel' => $parameters->stageId == -20 ? TRUE : FALSE
		);
	}

	/**
	 * Process TCA command map array.
	 *
	 * @param array $cmdMapArray
	 * @return void
	 * @author Michael Klapper <development@morphodo.com>
	 */
	protected function processTcaCmd(array $cmdMapArray) {
		$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
		$tce->start(array(), $cmdMapArray);
		$tce->process_cmdmap();
	}

	/**
	 * Gets an object with this structure:
	 *
	 * affects: object
	 * table
	 * t3ver_oid
	 * nextStage
	 * uid
	 * receipients: array with uids
	 * additional: string
	 * comments: string
	 *
	 * @param stdClass $parameters
	 * @return array
	 */
	public function sendToNextStageExecute(\stdClass $parameters) {
		$cmdArray = array();
		$setStageId = $parameters->affects->nextStage;
		$comments = $parameters->comments;
		$table = $parameters->affects->table;
		$uid = $parameters->affects->uid;
		$t3ver_oid = $parameters->affects->t3ver_oid;
		$recipients = $this->getRecipientList($parameters->receipients, $parameters->additional, $setStageId);
		if ($setStageId == \TYPO3\CMS\Workspaces\Service\StagesService::STAGE_PUBLISH_EXECUTE_ID) {
			$cmdArray[$table][$t3ver_oid]['version']['action'] = 'swap';
			$cmdArray[$table][$t3ver_oid]['version']['swapWith'] = $uid;
			$cmdArray[$table][$t3ver_oid]['version']['comment'] = $comments;
			$cmdArray[$table][$t3ver_oid]['version']['notificationAlternativeRecipients'] = $recipients;
		} else {
			$cmdArray[$table][$uid]['version']['action'] = 'setStage';
			$cmdArray[$table][$uid]['version']['stageId'] = $setStageId;
			$cmdArray[$table][$uid]['version']['comment'] = $comments;
			$cmdArray[$table][$uid]['version']['notificationAlternativeRecipients'] = $recipients;
		}
		$this->processTcaCmd($cmdArray);
		$result = array(
			'success' => TRUE
		);
		return $result;
	}

	/**
	 * Gets an object with this structure:
	 *
	 * affects: object
	 * table
	 * t3ver_oid
	 * nextStage
	 * receipients: array with uids
	 * additional: string
	 * comments: string
	 *
	 * @param stdClass $parameters
	 * @return array
	 */
	public function sendToPrevStageExecute(\stdClass $parameters) {
		$cmdArray = array();
		$recipients = array();
		$setStageId = $parameters->affects->nextStage;
		$comments = $parameters->comments;
		$table = $parameters->affects->table;
		$uid = $parameters->affects->uid;
		$recipients = $this->getRecipientList($parameters->receipients, $parameters->additional, $setStageId);
		$cmdArray[$table][$uid]['version']['action'] = 'setStage';
		$cmdArray[$table][$uid]['version']['stageId'] = $setStageId;
		$cmdArray[$table][$uid]['version']['comment'] = $comments;
		$cmdArray[$table][$uid]['version']['notificationAlternativeRecipients'] = $recipients;
		$this->processTcaCmd($cmdArray);
		$result = array(
			'success' => TRUE
		);
		return $result;
	}

	/**
	 * Gets an object with this structure:
	 *
	 * affects: object
	 * elements: array
	 * 0: object
	 * table
	 * t3ver_oid
	 * uid
	 * 1: object
	 * table
	 * t3ver_oid
	 * uid
	 * nextStage
	 * receipients: array with uids
	 * additional: string
	 * comments: string
	 *
	 * @param stdClass $parameters
	 * @return array
	 */
	public function sendToSpecificStageExecute(\stdClass $parameters) {
		$cmdArray = array();
		$setStageId = $parameters->affects->nextStage;
		$comments = $parameters->comments;
		$elements = $parameters->affects->elements;
		$recipients = $this->getRecipientList($parameters->receipients, $parameters->additional, $setStageId);
		foreach ($elements as $key => $element) {
			if ($setStageId == \TYPO3\CMS\Workspaces\Service\StagesService::STAGE_PUBLISH_EXECUTE_ID) {
				$cmdArray[$element->table][$element->t3ver_oid]['version']['action'] = 'swap';
				$cmdArray[$element->table][$element->t3ver_oid]['version']['swapWith'] = $element->uid;
				$cmdArray[$element->table][$element->t3ver_oid]['version']['comment'] = $comments;
				$cmdArray[$element->table][$element->t3ver_oid]['version']['notificationAlternativeRecipients'] = $recipients;
			} else {
				$cmdArray[$element->table][$element->uid]['version']['action'] = 'setStage';
				$cmdArray[$element->table][$element->uid]['version']['stageId'] = $setStageId;
				$cmdArray[$element->table][$element->uid]['version']['comment'] = $comments;
				$cmdArray[$element->table][$element->uid]['version']['notificationAlternativeRecipients'] = $recipients;
			}
		}
		$this->processTcaCmd($cmdArray);
		$result = array(
			'success' => TRUE
		);
		return $result;
	}

	/**
	 * Gets the dialog window to be displayed before a record can be sent to a stage.
	 *
	 * @param $nextStageId
	 * @return array
	 */
	protected function getSentToStageWindow($nextStageId) {
		$workspaceRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('sys_workspace', $this->getStageService()->getWorkspaceId());
		$showNotificationFields = FALSE;
		$stageTitle = $this->getStageService()->getStageTitle($nextStageId);
		$result = array(
			'title' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:actionSendToStage'),
			'items' => array(
				array(
					'xtype' => 'panel',
					'bodyStyle' => 'margin-bottom: 7px; border: none;',
					'html' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:window.sendToNextStageWindow.itemsWillBeSentTo') . ' ' . $stageTitle
				)
			)
		);
		switch ($nextStageId) {
		case \TYPO3\CMS\Workspaces\Service\StagesService::STAGE_PUBLISH_EXECUTE_ID:

		case \TYPO3\CMS\Workspaces\Service\StagesService::STAGE_PUBLISH_ID:
			if (!empty($workspaceRec['publish_allow_notificaton_settings'])) {
				$showNotificationFields = TRUE;
			}
			break;
		case \TYPO3\CMS\Workspaces\Service\StagesService::STAGE_EDIT_ID:
			if (!empty($workspaceRec['edit_allow_notificaton_settings'])) {
				$showNotificationFields = TRUE;
			}
			break;
		default:
			$allow_notificaton_settings = $this->getStageService()->getPropertyOfCurrentWorkspaceStage($nextStageId, 'allow_notificaton_settings');
			if (!empty($allow_notificaton_settings)) {
				$showNotificationFields = TRUE;
			}
			break;
		}
		if ($showNotificationFields == TRUE) {
			$result['items'][] = array(
				'fieldLabel' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:window.sendToNextStageWindow.sendMailTo'),
				'xtype' => 'checkboxgroup',
				'itemCls' => 'x-check-group-alt',
				'columns' => 1,
				'style' => 'max-height: 200px',
				'autoScroll' => TRUE,
				'items' => array(
					$this->getReceipientsOfStage($nextStageId)
				)
			);
			$result['items'][] = array(
				'fieldLabel' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:window.sendToNextStageWindow.additionalRecipients'),
				'name' => 'additional',
				'xtype' => 'textarea',
				'width' => 250
			);
		}
		$result['items'][] = array(
			'fieldLabel' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xlf:window.sendToNextStageWindow.comments'),
			'name' => 'comments',
			'xtype' => 'textarea',
			'width' => 250,
			'value' => $this->getDefaultCommentOfStage($nextStageId)
		);
		return $result;
	}

	/**
	 * Gets all assigned recipients of a particular stage.
	 *
	 * @param integer $stage
	 * @return array
	 */
	protected function getReceipientsOfStage($stage) {
		$result = array();
		$recipients = $this->getStageService()->getResponsibleBeUser($stage);
		$default_recipients = $this->getStageService()->getResponsibleBeUser($stage, TRUE);
		foreach ($recipients as $id => $user) {
			if (\TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($user['email'])) {
				$checked = FALSE;
				$disabled = FALSE;
				$name = $user['realName'] ? $user['realName'] : $user['username'];
				// the notification mode can be configured in the workspace stage record
				$notification_mode = $this->getStageService()->getNotificationMode($stage);
				if (intval($notification_mode) === \TYPO3\CMS\Workspaces\Service\StagesService::MODE_NOTIFY_SOMEONE) {
					// all responsible users are checked per default, as in versions before
					$checked = TRUE;
				} elseif (intval($notification_mode) === \TYPO3\CMS\Workspaces\Service\StagesService::MODE_NOTIFY_ALL) {
					// the default users are checked only
					if (!empty($default_recipients[$id])) {
						$checked = TRUE;
						$disabled = TRUE;
					} else {
						$checked = FALSE;
					}
				} elseif (intval($notification_mode) === \TYPO3\CMS\Workspaces\Service\StagesService::MODE_NOTIFY_ALL_STRICT) {
					// all responsible users are checked
					$checked = TRUE;
					$disabled = TRUE;
				}
				$result[] = array(
					'boxLabel' => sprintf('%s (%s)', $name, $user['email']),
					'name' => 'receipients-' . $id,
					'checked' => $checked,
					'disabled' => $disabled
				);
			}
		}
		return $result;
	}

	/**
	 * Gets the default comment of a particular stage.
	 *
	 * @param integer $stage
	 * @return string
	 */
	protected function getDefaultCommentOfStage($stage) {
		$result = $this->getStageService()->getPropertyOfCurrentWorkspaceStage($stage, 'default_mailcomment');
		return $result;
	}

	/**
	 * Gets an instance of the Stage service.
	 *
	 * @return \TYPO3\CMS\Workspaces\Service\StagesService
	 */
	protected function getStageService() {
		if (!isset($this->stageService)) {
			$this->stageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\StagesService');
		}
		return $this->stageService;
	}

	/**
	 * Send all available workspace records to the previous stage.
	 *
	 * @param integer $id Current page id to process items to previous stage.
	 * @return array
	 * @author Michael Klapper <development@morphodo.com>
	 */
	public function sendPageToPreviousStage($id) {
		$workspaceService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
		$workspaceItemsArray = $workspaceService->selectVersionsInWorkspace($this->stageService->getWorkspaceId(), ($filter = 1), ($stage = -99), $id, ($recursionLevel = 0), ($selectionType = 'tables_modify'));
		list($currentStage, $previousStage) = $this->getStageService()->getPreviousStageForElementCollection($workspaceItemsArray);
		// get only the relevant items for processing
		$workspaceItemsArray = $workspaceService->selectVersionsInWorkspace($this->stageService->getWorkspaceId(), ($filter = 1), $currentStage['uid'], $id, ($recursionLevel = 0), ($selectionType = 'tables_modify'));
		return array(
			'title' => 'Status message: Page send to next stage - ID: ' . $id . ' - Next stage title: ' . $previousStage['title'],
			'items' => $this->getSentToStageWindow($previousStage['uid']),
			'affects' => $workspaceItemsArray,
			'stageId' => $previousStage['uid']
		);
	}

	/**
	 * @param integer $id Current Page id to select Workspace items from.
	 * @return array
	 * @author Michael Klapper <development@morphodo.com>
	 */
	public function sendPageToNextStage($id) {
		$workspaceService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
		$workspaceItemsArray = $workspaceService->selectVersionsInWorkspace($this->stageService->getWorkspaceId(), ($filter = 1), ($stage = -99), $id, ($recursionLevel = 0), ($selectionType = 'tables_modify'));
		list($currentStage, $nextStage) = $this->getStageService()->getNextStageForElementCollection($workspaceItemsArray);
		// get only the relevant items for processing
		$workspaceItemsArray = $workspaceService->selectVersionsInWorkspace($this->stageService->getWorkspaceId(), ($filter = 1), $currentStage['uid'], $id, ($recursionLevel = 0), ($selectionType = 'tables_modify'));
		return array(
			'title' => 'Status message: Page send to next stage - ID: ' . $id . ' - Next stage title: ' . $nextStage['title'],
			'items' => $this->getSentToStageWindow($nextStage['uid']),
			'affects' => $workspaceItemsArray,
			'stageId' => $nextStage['uid']
		);
	}

	/**
	 * Fetch the current label and visible state of the buttons.
	 *
	 * @param integer $id
	 * @return array Contains the visibility state and label of the stage change buttons.
	 * @author Michael Klapper <development@morphodo.com>
	 */
	public function updateStageChangeButtons($id) {
		$stageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\StagesService');
		$workspaceService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService');
		// fetch the next and previous stage
		$workspaceItemsArray = $workspaceService->selectVersionsInWorkspace($stageService->getWorkspaceId(), ($filter = 1), ($stage = -99), $id, ($recursionLevel = 0), ($selectionType = 'tables_modify'));
		list(, $nextStage) = $stageService->getNextStageForElementCollection($workspaceItemsArray);
		list(, $previousStage) = $stageService->getPreviousStageForElementCollection($workspaceItemsArray);
		$toolbarButtons = array(
			'feToolbarButtonNextStage' => array(
				'visible' => is_array($nextStage) && count($nextStage) > 0,
				'text' => $nextStage['title']
			),
			'feToolbarButtonPreviousStage' => array(
				'visible' => is_array($previousStage) && count($previousStage),
				'text' => $previousStage['title']
			),
			'feToolbarButtonDiscardStage' => array(
				'visible' => is_array($nextStage) && count($nextStage) > 0 || is_array($previousStage) && count($previousStage) > 0,
				'text' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:label_doaction_discard', TRUE)
			)
		);
		return $toolbarButtons;
	}

}


?>