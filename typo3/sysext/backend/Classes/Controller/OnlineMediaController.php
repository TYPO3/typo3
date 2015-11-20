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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class OnlineMediaController handles uploading online media
 */
class OnlineMediaController
{
    /**
     * AJAX endpoint for storing the URL as a sys_file record
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function createAction(ServerRequestInterface $request, ResponseInterface $response)
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
                $data['error'] = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:online_media.error.invalid_url');
            }
            $response->getBody()->write(json_encode($data));
        }
        return $response;
    }

    /**
     * Process add media request
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $files = $request->getParsedBody()['file'];
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
                        $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:online_media.new_media.added'),
                        FlashMessage::OK,
                        true
                    );
                } else {
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:online_media.error.invalid_url'),
                        $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:online_media.error.new_media.failed'),
                        FlashMessage::ERROR,
                        true
                    );
                }
                $this->addFlashMessage($flashMessage);
            }
        }

        $redirect = isset($request->getParsedBody()['redirect']) ? $request->getParsedBody()['redirect'] : $request->getQueryParams()['redirect'];
        $redirect = GeneralUtility::sanitizeLocalUrl($redirect);
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
     * @return void
     */
    protected function addFlashMessage(FlashMessage $flashMessage)
    {
        /** @var $flashMessageService FlashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

        /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
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
