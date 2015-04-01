<?php
namespace TYPO3\CMS\Openid;

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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Lang\LanguageService;

/**
 * OpenID selection wizard for the backend
 *
 * @author Christian Weiske <cweiske@cweiske.de>
 */
class Wizard extends OpenidService {

	/**
	 * OpenID of the user after authentication
	 *
	 * @var string
	 */
	protected $claimedId;

	/**
	 * Name of the form element this wizard should write the OpenID into
	 *
	 * @var string
	 */
	protected $parentFormItemName;

	/**
	 * Name of the function that needs to be called after setting the value
	 *
	 * @var string
	 */
	protected $parentFormFieldChangeFunc;

	/**
	 * Run the wizard and output HTML.
	 *
	 * @return void
	 */
	public function main() {
		$p = GeneralUtility::_GP('P');
		if (isset($p['itemName'])) {
			$this->parentFormItemName = $p['itemName'];
		}
		if (isset($p['fieldChangeFunc']['TBE_EDITOR_fieldChanged'])) {
			$this->parentFormFieldChangeFunc = $p['fieldChangeFunc']['TBE_EDITOR_fieldChanged'];
		}

		if (GeneralUtility::_GP('tx_openid_mode') === 'finish' && $this->openIDResponse === NULL) {
			$this->includePHPOpenIDLibrary();
			$openIdConsumer = $this->getOpenIDConsumer();
			$this->openIDResponse = $openIdConsumer->complete($this->getReturnUrl());
			$this->handleResponse();
		} elseif (GeneralUtility::_POST('openid_url') != '') {
			$openIDIdentifier = GeneralUtility::_POST('openid_url');
			$this->sendOpenIDRequest($openIDIdentifier);

			// When sendOpenIDRequest() returns, there was an error
			$flashMessageService = GeneralUtility::makeInstance(
				FlashMessageService::class
			);
			$flashMessage = GeneralUtility::makeInstance(
				FlashMessage::class,
				sprintf(
					$this->getLanguageService()->sL('LLL:EXT:openid/Resources/Private/Language/Wizard.xlf:error.setup'),
					htmlspecialchars($openIDIdentifier)
				),
				$this->getLanguageService()->sL('LLL:EXT:openid/Resources/Private/Language/Wizard.xlf:title.error'),
				FlashMessage::ERROR
			);
			$flashMessageService->getMessageQueueByIdentifier()->enqueue($flashMessage);
		}
		$this->renderHtml();
	}

	/**
	 * Return URL that shall be called by the OpenID server
	 *
	 * @return string Full URL with protocol and hostname
	 */
	protected function getReturnUrl() {
		$parameters = [
			'tx_openid_mode' => 'finish',
			'P[itemName]' => $this->parentFormItemName,
			'P[fieldChangeFunc][TBE_EDITOR_fieldChanged]' => $this->parentFormFieldChangeFunc
		];
		return BackendUtility::getModuleUrl('wizard_openid', $parameters, FALSE, TRUE);
	}

	/**
	 * Check OpenID response and set flash messages depending on its state
	 *
	 * @return void
	 */
	protected function handleResponse() {
		/** @var $flashMessageService FlashMessageService */
		$flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
		$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();

		$lang = $this->getLanguageService();
		if (!$this->openIDResponse instanceof \Auth_OpenID_ConsumerResponse) {
			$flashMessage = GeneralUtility::makeInstance(
				FlashMessage::class,
				$lang->sL('LLL:EXT:openid/Resources/Private/Language/Wizard.xlf:error.no-response'),
				$lang->sL('LLL:EXT:openid/Resources/Private/Language/Wizard.xlf:title.error'),
				FlashMessage::ERROR
			);
		} elseif ($this->openIDResponse->status == Auth_OpenID_SUCCESS) {
			// all fine
			$this->claimedId = $this->getSignedParameter('openid_claimed_id');
			$flashMessage = GeneralUtility::makeInstance(
				FlashMessage::class,
				sprintf(
					$lang->sL('LLL:EXT:openid/Resources/Private/Language/Wizard.xlf:youropenid'),
					htmlspecialchars($this->claimedId)
				),
				$lang->sL('LLL:EXT:openid/Resources/Private/Language/Wizard.xlf:title.success'),
				FlashMessage::OK
			);
		} elseif ($this->openIDResponse->status == Auth_OpenID_CANCEL) {
			$flashMessage = GeneralUtility::makeInstance(
				FlashMessage::class,
				$lang->sL('LLL:EXT:openid/Resources/Private/Language/Wizard.xlf:error.cancelled'),
				$lang->sL('LLL:EXT:openid/Resources/Private/Language/Wizard.xlf:title.error'),
				FlashMessage::ERROR
			);
		} else {
			// another failure. show error message and form again
			$flashMessage = GeneralUtility::makeInstance(
				FlashMessage::class,
				sprintf(
					$lang->sL('LLL:EXT:openid/Resources/Private/Language/Wizard.xlf:error.general'),
					htmlspecialchars($this->openIDResponse->status),
					''
				),
				$lang->sL('LLL:EXT:openid/Resources/Private/Language/Wizard.xlf:title.error'),
				FlashMessage::ERROR
			);
		}

		$defaultFlashMessageQueue->enqueue($flashMessage);
	}

	/**
	 * Render HTML with message and OpenID form and output it
	 *
	 * @return void
	 */
	protected function renderHtml() {
		// use FLUID standalone view for wizard content
		$view = GeneralUtility::makeInstance(StandaloneView::class);
		$view->setTemplatePathAndFilename(
			ExtensionManagementUtility::extPath('openid') .
			'Resources/Private/Templates/Wizard/Content.html'
		);

		/** @var $flashMessageService FlashMessageService */
		$flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
		$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();

		$messages = array();
		foreach ($defaultFlashMessageQueue->getAllMessagesAndFlush() as $message) {
			$messages[] = $message->render();
		}
		$view->assign('messages', $messages);
		$view->assign('formAction', BackendUtility::getModuleUrl('wizard_openid', [], FALSE, TRUE));
		$view->assign('claimedId', $this->claimedId);
		$view->assign('parentFormItemName', $this->parentFormItemName);
		$view->assign('parentFormItemNameNoHr', strtr($this->parentFormItemName, array('_hr' => '')));
		$view->assign('parentFormFieldChangeFunc', $this->parentFormFieldChangeFunc);
		$view->assign('showForm', TRUE);
		if (isset($_REQUEST['openid_url'])) {
			$view->assign('openid_url', $_REQUEST['openid_url']);
		}

		header('HTTP/1.0 200 OK');
		echo $view->render();
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}
