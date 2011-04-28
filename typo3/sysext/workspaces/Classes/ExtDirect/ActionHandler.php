<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 * @package Workspaces
 * @subpackage ExtDirect
 */
class tx_Workspaces_ExtDirect_ActionHandler extends tx_Workspaces_ExtDirect_AbstractHandler {
	/**
	 * @var Tx_Workspaces_Service_Stages
	 */
	protected $stageService;

	/**
	 * Creates this object.
	 */
	public function __construct() {
		$this->stageService = t3lib_div::makeInstance('Tx_Workspaces_Service_Stages');
	}

	/**
	 * Generates a workspace preview link.
	 *
	 * @param integer $uid The ID of the record to be linked
	 * @return string the full domain including the protocol http:// or https://, but without the trailing '/'
	 */
	public function generateWorkspacePreviewLink($uid) {
		$ttlHours = intval($GLOBALS['BE_USER']->getTSConfigVal('options.workspaces.previewLinkTTLHours'));
		$ttlHours = ($ttlHours ? $ttlHours : 24*2) * 3600;
		$linkParams = array(
			'ADMCMD_prev'	=> t3lib_BEfunc::compilePreviewKeyword('', $GLOBALS['BE_USER']->user['uid'], $ttlHours, $this->getCurrentWorkspace()),
			'id'			=> $uid
		);
		return t3lib_BEfunc::getViewDomain($uid) . '/index.php?' . t3lib_div::implodeArrayForUrl('', $linkParams);
	}

	/**
	 * Swaps a sisngle record.
	 *
	 * @param string $table
	 * @param integer $t3ver_oid
	 * @param integer $orig_uid
	 * @return void
	 *
	 * @todo What about reporting errors back to the ExtJS interface? /olly/
	 */
	public function swapSingleRecord($table, $t3ver_oid, $orig_uid) {
		$cmd[$table][$t3ver_oid]['version'] = array(
			'action' => 'swap',
			'swapWith' => $orig_uid,
			'swapIntoWS' => 1
		);

		$tce = t3lib_div::makeInstance ('t3lib_TCEmain');
		$tce->start(array(), $cmd);
		$tce->process_cmdmap();
	}

	/**
	 * Deletes a single record.
	 *
	 * @param string $table
	 * @param integer $uid
	 * @return void
	 *
	 * @todo What about reporting errors back to the ExtJS interface? /olly/
	 */
	public function deleteSingleRecord($table, $uid) {
		$cmd[$table][$uid]['version'] = array(
			'action' => 'clearWSID'
		);

		$tce = t3lib_div::makeInstance ('t3lib_TCEmain');
		$tce->start(array(), $cmd);
		$tce->process_cmdmap();
	}

	/**
	 * Generates a view link for a page.
	 *
	 * @param string $table
	 * @param string $uid
	 * @return void
	 */
	public function viewSingleRecord($table, $uid) {
		return tx_Workspaces_Service_Workspaces::viewSingleRecord($table, $uid);
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
				'position'  => $column->position,
				'hidden'   => $column->hidden
			);
		}
		$GLOBALS['BE_USER']->uc['moduleData']['Workspaces']['columns'] = $data;
		$GLOBALS['BE_USER']->writeUC();
	}

	public function loadColumnModel() {
		if(is_array($GLOBALS['BE_USER']->uc['moduleData']['Workspaces']['columns'])) {
			return $GLOBALS['BE_USER']->uc['moduleData']['Workspaces']['columns'];
		} else {
			return array();
		}
	}

	/**
	 * Gets the dialog window to be displayed before a record can be sent to the next stage.
	 *
	 *	@param integer $uid
	 * @param string $table
	 * @param integer $t3ver_oid
	 * @return array
	 */
	public function sendToNextStageWindow($uid, $table, $t3ver_oid) {
		$elementRecord = t3lib_BEfunc::getRecord($table, $uid);

		if(is_array($elementRecord)) {
			$stageId = $elementRecord['t3ver_stage'];

			if ($this->getStageService()->isValid($stageId)) {
				$nextStage = $this->getStageService()->getNextStage($stageId);
				$result = $this->getSentToStageWindow($nextStage['uid']);
				$result['affects'] = array(
					'table' => $table,
					'nextStage' => $nextStage['uid'],
					't3ver_oid' => $t3ver_oid,
					'uid' => $uid,
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
		$elementRecord = t3lib_BEfunc::getRecord($table, $uid);

		if(is_array($elementRecord)) {
			$stageId = $elementRecord['t3ver_stage'];

			if ($this->getStageService()->isValid($stageId)) {
				if ($stageId !== Tx_Workspaces_Service_Stages::STAGE_EDIT_ID) {
					$prevStage = $this->getStageService()->getPrevStage($stageId);

					$result = $this->getSentToStageWindow($prevStage['uid']);
					$result['affects'] = array(
						'table' => $table,
						'uid' => $uid,
						'nextStage' => $prevStage['uid'],
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
			'nextStage' => $nextStageId,
		);

		return $result;
	}

	/**
	 * Gets a merged variant of recipient defined by uid and custom ones.
	 *
	 * @param array list of recipients
	 * @param string given user string of additional recipients
	 * @return array
	 */
	public function getRecipientList(array $uidOfRecipients, $additionalRecipients) {
		$finalRecipients = array();

		$recipients = array();
		foreach ($uidOfRecipients as $userUid) {
			$beUserRecord = t3lib_befunc::getRecord('be_users',intval($userUid));
			if(is_array($beUserRecord) && $beUserRecord['email'] != '') {
				$recipients[] = $beUserRecord['email'];
			}
		}

		if ($additionalRecipients != '') {
			$additionalRecipients = t3lib_div::trimExplode("\n", $additionalRecipients, TRUE);
		} else {
			$additionalRecipients = array();
		}

		$allRecipients = array_unique(
			array_merge($recipients, $additionalRecipients)
		);

		foreach ($allRecipients as $recipient) {
			if (t3lib_div::validEmail($recipient)) {
				$finalRecipients[] = $recipient;
			}
		}

		return $finalRecipients;
	}

	/**
	 * Gets an object with this structure:
	 *
	 *	affects: object
	 *		table
	 *		t3ver_oid
	 *		nextStage
	 *		uid
	 *	receipients: array with uids
	 *	additional: string
	 *	comments: string
	 *
	 * @param stdObject $parameters
	 * @return array
	 */
	public function sendToNextStageExecute(stdClass $parameters) {
		$cmdArray = array();
		$recipients = array();

		$setStageId = $parameters->affects->nextStage;
		$comments = $parameters->comments;
		$table = $parameters->affects->table;
		$uid = $parameters->affects->uid;
		$t3ver_oid = $parameters->affects->t3ver_oid;
		$recipients = $this->getRecipientList($parameters->receipients, $parameters->additional);

		if ($setStageId == Tx_Workspaces_Service_Stages::STAGE_PUBLISH_EXECUTE_ID) {
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

		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->start(array(), $cmdArray);
		$tce->process_cmdmap();

		$result = array(
			'success' => TRUE,
		);

		return $result;
	}

	/**
	 * Gets an object with this structure:
	 *
	 *	affects: object
	 *		table
	 *		t3ver_oid
	 *		nextStage
	 *	receipients: array with uids
	 *	additional: string
	 *	comments: string
	 *
	 * @param stdObject $parameters
	 * @return array
	 */
	public function sendToPrevStageExecute(stdClass $parameters) {
		$cmdArray = array();
		$recipients = array();

		$setStageId = $parameters->affects->nextStage;
		$comments = $parameters->comments;
		$table = $parameters->affects->table;
		$uid = $parameters->affects->uid;
		$recipients = $this->getRecipientList($parameters->receipients, $parameters->additional);

		$cmdArray[$table][$uid]['version']['action'] = 'setStage';
		$cmdArray[$table][$uid]['version']['stageId'] = $setStageId;
		$cmdArray[$table][$uid]['version']['comment'] = $comments;
		$cmdArray[$table][$uid]['version']['notificationAlternativeRecipients'] = $recipients;

		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->start(array(), $cmdArray);
		$tce->process_cmdmap();

		$result = array(
			'success' => TRUE,
		);

		return $result;
	}

	/**
	 * Gets an object with this structure:
	 *
	 *	affects: object
	 *		elements: array
	 *			0: object
	 *				table
	 *				t3ver_oid
	 *				uid
	 *			1: object
	 *				table
	 *				t3ver_oid
	 *				uid
	 *		nextStage
	 *	receipients: array with uids
	 *	additional: string
	 *	comments: string
	 *
	 * @param stdObject $parameters
	 * @return array
	 */
	public function sendToSpecificStageExecute(stdClass $parameters) {
		$cmdArray = array();

		$setStageId = $parameters->affects->nextStage;
		$comments = $parameters->comments;
		$elements = $parameters->affects->elements;
		$recipients = $this->getRecipientList($parameters->receipients, $parameters->additional);

		foreach($elements as $key=>$element) {
			if ($setStageId == Tx_Workspaces_Service_Stages::STAGE_PUBLISH_EXECUTE_ID) {
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

		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->start(array(), $cmdArray);
		$tce->process_cmdmap();

		$result = array(
			'success' => TRUE,
		);

		return $result;
	}

	/**
	 * Gets the dialog window to be displayed before a record can be sent to a stage.
	 *
	 * @param  $nextStageId
	 * @return array
	 */
	protected function getSentToStageWindow($nextStageId) {
		$stageTitle = $this->getStageService()->getStageTitle($nextStageId);
		$result = array(
			'title' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:actionSendToStage'),
			'items' => array(
				array(
					'xtype' => 'panel',
					'bodyStyle' => 'margin-bottom: 7px; border: none;',
					'html' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:window.sendToNextStageWindow.itemsWillBeSentTo') . $stageTitle,
				),
				array(
					'fieldLabel' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:window.sendToNextStageWindow.sendMailTo'),
					'xtype' => 'checkboxgroup',
					'itemCls' => 'x-check-group-alt',
					'columns' => 1,
					'style' => 'max-height: 200px',
					'autoScroll' => TRUE,
					'items' => array(
						$this->getReceipientsOfStage($nextStageId)
					)
				),
				array(
					'fieldLabel' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:window.sendToNextStageWindow.additionalRecipients'),
					'name' => 'additional',
					'xtype' => 'textarea',
					'width' => 250,
				),
				array(
					'fieldLabel' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:window.sendToNextStageWindow.comments'),
					'name' => 'comments',
					'xtype' => 'textarea',
					'width' => 250,
					'value' => $this->getDefaultCommentOfStage($nextStageId),
				),
			)
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

		foreach ($recipients as $id => $user) {
			if (t3lib_div::validEmail($user['email'])) {
				$name = $user['realName'] ? $user['realName'] : $user['username'];
				$result[] = array(
					'boxLabel' => sprintf('%s (%s)', $name, $user['email']),
					'name' => 'receipients-' . $id,
					'checked' => TRUE,
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
	 * @return Tx_Workspaces_Service_Stages
	 */
	protected function getStageService() {
		if (!isset($this->stageService)) {
			$this->stageService = t3lib_div::makeInstance('Tx_Workspaces_Service_Stages');
		}
		return $this->stageService;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/ExtDirect/ActionHandler.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/ExtDirect/ActionHandler.php']);
}
?>