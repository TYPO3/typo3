<?php
namespace TYPO3\CMS\Backend\Controller;

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

use TYPO3\CMS\Core\Http\AjaxRequestHandler;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class OnlineMediaController handles uploading online media
 */
class OnlineMediaController {

	/**
	 * @param array $_
	 * @param AjaxRequestHandler $ajaxObj
	 * @return void
	 */
	public function addAjaxAction($_, AjaxRequestHandler $ajaxObj = NULL) {
		$ajaxObj->setContentFormat('json');

		$url = GeneralUtility::_POST('url');
		$targetFolderIdentifier = GeneralUtility::_POST('targetFolder');
		$allowedExtensions = GeneralUtility::trimExplode(',', GeneralUtility::_POST('allowed') ?: '');

		if (!empty($url)) {
			$file = $this->addMediaFromUrl($url, $targetFolderIdentifier, $allowedExtensions);
			if ($file !== NULL) {
				$ajaxObj->addContent('file', $file->getUid());
			} else {
				$ajaxObj->addContent('error', $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:online_media.error.invalid_url'));
			}
		}
	}

	/**
	 * Process add media request
	 *
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function mainAction(ServerRequestInterface $request, ResponseInterface $response) {
		$files = $request->getParsedBody()['file'];
		$newMedia = [];
		if (isset($files['newMedia'])) {
			$newMedia = (array)$files['newMedia'];
		}

		foreach ($newMedia as $media) {
			if (!empty($media['url']) && !empty($media['target'])) {
				$allowed = !empty($media['allowed']) ? GeneralUtility::trimExplode(',', $media['allowed']) : [];
				$file = $this->addMediaFromUrl($media['url'], $media['target'], $allowed);
				if ($file !== NULL) {
					$flashMessage = GeneralUtility::makeInstance(
						FlashMessage::class,
						$file->getName(),
						$this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:online_media.new_media.added'),
						FlashMessage::OK,
						TRUE
					);
				} else {
					$flashMessage = GeneralUtility::makeInstance(
						FlashMessage::class,
						$this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:online_media.error.invalid_url'),
						$this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:online_media.error.new_media.failed'),
						FlashMessage::ERROR,
						TRUE
					);
				}
				$this->addFlashMessage($flashMessage);
			}
		}

		$redirect = isset($request->getParsedBody()['redirect']) ? $request->getParsedBody()['redirect'] : $request->getQueryParams()['redirect'];
		if ($redirect) {
			$response = $response
				->withHeader('Location', GeneralUtility::locationHeaderUrl($redirect))
				->withStatus(303);
		}

		return $response;
	}

	/**
	 * @param string $url
	 * @param string $targetFolderIdentifier
	 * @param string[] $allowedExtensions
	 * @return File|NULL
	 */
	protected function addMediaFromUrl($url, $targetFolderIdentifier, array $allowedExtensions = []) {
		$targetFolder = NULL;
		if ($targetFolderIdentifier) {
			try {
				$targetFolder = ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier($targetFolderIdentifier);
			} catch (\Exception $e) {
				$targetFolder = NULL;
			}
		}
		if ($targetFolder === NULL) {
			$targetFolder = $this->getBackendUser()->getDefaultUploadFolder();
		}
		return OnlineMediaHelperRegistry::getInstance()->transformUrlToFile($url, $targetFolder, $allowedExtensions);
	}

	/**
	 * Add flash message to message queue
	 *
	 * @param FlashMessage $flashMessage
	 * @return void
	 */
	protected function addFlashMessage(FlashMessage $flashMessage) {
		/** @var $flashMessageService FlashMessageService */
		$flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

		/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
		$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
		$defaultFlashMessageQueue->enqueue($flashMessage);
	}

	/**
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}
