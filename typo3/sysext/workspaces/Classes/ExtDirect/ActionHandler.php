<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Steffen Ritter (steffen@typo3.org)
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

class tx_Workspaces_ExtDirect_ActionHandler extends tx_Workspaces_ExtDirect_AbstractHandler {
	/**
	 * @var Tx_Workspaces_Service_Stages
	 */
	protected $stageService;

	public function __construct() {
		$this->stageService = t3lib_div::makeInstance('Tx_Workspaces_Service_Stages');
	}

	/**
	 * @param integer $uid
	 * @return array
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
	 * @param string $table
	 * @param integer $t3ver_oid
	 * @param integer $orig_uid
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
	 * @param string $pid
	 * @return void
	 */
	public function viewSingleRecord($pid) {
		return t3lib_BEfunc::viewOnClick($pid);
	}


	/**
	 * @param object $model
	 * @return void
	 */
	public function saveColumnModel($model) {
		$data = array();
		foreach ($model AS $column) {
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
	 * @param string $table
	 * @param integer $uid
	 * @param integer $t3ver_oid
	 * @return array
	 */
	public function sendToNextStageWindow($table, $uid, $t3ver_oid) {
		$elementRecord = t3lib_BEfunc::getRecord($table, $uid);

		if(is_array($elementRecord)) {
			$stageId = $elementRecord['t3ver_stage'];
			$nextStage = $this->getStageService()->getNextStage($stageId);

			$result = $this->getSentToStageWindow($nextStage['uid']);
			$result['affects'] = array(
				'table' => $table,
				'nextStage' => $nextStage['uid'],
				't3ver_oid' => $t3ver_oid,
				'uid' => $uid,
			);
		} else {
			$result = array(
				'error' => array(
					'code' => 1287264776,
					'message' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:error.sendToNextStage.noRecordFound'),
				),
				'success' => FALSE,
			);
		}

		return $result;
	}

	/**
	 * @param unknown_type $table
	 * @param unknown_type $t3ver_oid
	 * @param integer $t3ver_oid
	 * @return array
	 */
	public function sendToPrevStageWindow($table, $uid) {
		$elementRecord = t3lib_BEfunc::getRecord($table, $uid);

		if(is_array($elementRecord)) {
			$stageId = intval($elementRecord['t3ver_stage']);
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
				$result = array(
					'error' => array(
						'code' => 1287264746,
						'message' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:error.sendToPrevStage.noPreviousStage'),
					),
					'success' => FALSE,
				);
			}

		} else {
			$result = array(
				'error' => array(
					'code' => 1287264765,
					'message' => $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:error.sendToNextStage.noRecordFound'),
				),
				'success' => FALSE,
			);
		}

		return $result;
	}

	/**
	 * @param int $nextStage
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
	 *
	 * @param array list of recipients
	 * @param string given user string of additional recipients
	 * @return array
	 */
	public function getRecipientList($uidOfRecipients, $additionalRecipients) {
		$finalRecipients = array();

		$recipients = array();
		foreach ($uidOfRecipients as $userUid) {
			$beUserRecord = t3lib_befunc::getRecord('be_users',intval($userUid));
			if(is_array($beUserRecord) && $beUserRecord['email'] != '') {
				$recipients[] = $beUserRecord['email'];
			}
		}

		if ($additionalRecipients != '') {
			$additionalRecipients = explode("\n",$additionalRecipients);
		} else {
			$additionalRecipients = array();
		}

		$finalRecipients = array_merge($recipients,$additionalRecipients);
		$finalRecipients = array_unique($finalRecipients);

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
		$recipients = array();

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
	 * @param integer $stage
	 * @return array
	 */
	protected function getReceipientsOfStage($stage) {
		$result = array();

		$recipients = $this->getStageService()->getResponsibleBeUser($stage);

		foreach ($recipients as $id => $name) {
			$result[] = array(
				'boxLabel' => $name,
				'name' => 'receipients-' . $id,
				'checked' => TRUE,
			);
		}

		return $result;
	}

	/**
	 * @param integer $stage
	 * @return string
	 */
	protected function getDefaultCommentOfStage($stage) {
		$result = '';

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


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/ExtDirect/ActionHandler.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/ExtDirect/ActionHandler.php']);
}