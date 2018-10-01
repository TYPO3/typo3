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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class OnlineMediaController handles uploading online media
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class OnlineMediaController
{
    /**
     * AJAX endpoint for storing the URL as a sys_file record
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function createAction(ServerRequestInterface $request): ResponseInterface
    {
        $url = $request->getParsedBody()['url'];
        $targetFolderIdentifier = $request->getParsedBody()['targetFolder'];
        $allowedExtensions = GeneralUtility::trimExplode(',', $request->getParsedBody()['allowed'] ?: '');

        if (!empty($url)) {
            $data = [];
            $file = $this->addMediaFromUrl($url, $targetFolderIdentifier, $allowedExtensions);
            if ($file !== null) {
                $data['file'] = $file->getUid();
            } else {
                $data['error'] = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.error.invalid_url');
            }
            return new JsonResponse($data);
        }
        return new JsonResponse();
    }

    /**
     * Process add media request, and redirects to the previous page
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \RuntimeException
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $files = $request->getParsedBody()['data'];
        $redirect = $request->getParsedBody()['redirect'];
        $newMedia = [];
        if (isset($files['newMedia'])) {
            $newMedia = (array)$files['newMedia'];
        }

        foreach ($newMedia as $media) {
            if (!empty($media['url']) && !empty($media['target'])) {
                $allowed = !empty($media['allowed']) ? GeneralUtility::trimExplode(',', $media['allowed']) : [];
                $file = $this->addMediaFromUrl($media['url'], $media['target'], $allowed);
                if ($file !== null) {
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $file->getName(),
                        $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.added'),
                        FlashMessage::OK,
                        true
                    );
                } else {
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.error.invalid_url'),
                        $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.error.new_media.failed'),
                        FlashMessage::ERROR,
                        true
                    );
                }
                $this->addFlashMessage($flashMessage);
                if (empty($redirect) && $media['redirect']) {
                    $redirect = $media['redirect'];
                }
            }
        }

        $redirect = GeneralUtility::sanitizeLocalUrl($redirect);
        if ($redirect) {
            return new RedirectResponse($redirect, 303);
        }

        throw new \RuntimeException('No redirect after uploading a media found, probably a mis-use of the template not sending the proper Return URL.', 1511945040);
    }

    /**
     * @param string $url
     * @param string $targetFolderIdentifier
     * @param string[] $allowedExtensions
     * @return File|null
     */
    protected function addMediaFromUrl($url, $targetFolderIdentifier, array $allowedExtensions = [])
    {
        $targetFolder = null;
        if ($targetFolderIdentifier) {
            try {
                $targetFolder = ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier($targetFolderIdentifier);
            } catch (\Exception $e) {
                $targetFolder = null;
            }
        }
        if ($targetFolder === null) {
            $targetFolder = $this->getBackendUser()->getDefaultUploadFolder();
        }
        return OnlineMediaHelperRegistry::getInstance()->transformUrlToFile($url, $targetFolder, $allowedExtensions);
    }

    /**
     * Add flash message to message queue
     *
     * @param FlashMessage $flashMessage
     */
    protected function addFlashMessage(FlashMessage $flashMessage)
    {
        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

        /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
