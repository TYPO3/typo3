<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\DefaultUploadFolderResolver;
use TYPO3\CMS\Core\Resource\Exception\OnlineMediaAlreadyExistsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class OnlineMediaController handles uploading online media
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class OnlineMediaController
{
    public function __construct(
        protected readonly ResourceFactory $resourceFactory,
        protected readonly DefaultUploadFolderResolver $uploadFolderResolver,
        protected readonly OnlineMediaHelperRegistry $onlineMediaHelperRegistry,
        protected readonly FlashMessageService $flashMessageService
    ) {
    }

    /**
     * AJAX endpoint for storing the URL as a sys_file record
     */
    public function createAction(ServerRequestInterface $request): ResponseInterface
    {
        $url = $request->getParsedBody()['url'];
        $targetFolderIdentifier = $request->getParsedBody()['targetFolder'];
        $allowedExtensions = GeneralUtility::trimExplode(',', $request->getParsedBody()['allowed'] ?: '');

        if (!empty($url)) {
            $data = [];
            try {
                $file = $this->addMediaFromUrl($url, $targetFolderIdentifier, $allowedExtensions);
            } catch (OnlineMediaAlreadyExistsException $e) {
                // Ignore this exception since the endpoint is called e.g. in inline context, where the
                // folder is not relevant and the same asset can be attached to a record multiple times.
                $file = $e->getOnlineMedia();
            }
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
                try {
                    $file = $this->addMediaFromUrl($media['url'], $media['target'], $allowed);
                    if ($file !== null) {
                        $flashMessage = GeneralUtility::makeInstance(
                            FlashMessage::class,
                            $file->getName(),
                            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.new_media.added'),
                            ContextualFeedbackSeverity::OK,
                            true
                        );
                    } else {
                        $flashMessage = GeneralUtility::makeInstance(
                            FlashMessage::class,
                            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.error.invalid_url'),
                            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.error.new_media.failed'),
                            ContextualFeedbackSeverity::ERROR,
                            true
                        );
                    }
                } catch (OnlineMediaAlreadyExistsException $e) {
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        sprintf(
                            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.error.already_exists'),
                            $e->getOnlineMedia()->getName()
                        ),
                        $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.error.new_media.failed'),
                        ContextualFeedbackSeverity::WARNING,
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
                $targetFolder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($targetFolderIdentifier);
            } catch (\Exception $e) {
                $targetFolder = null;
            }
        }
        if ($targetFolder === null) {
            $targetFolder = $this->uploadFolderResolver->resolve($this->getBackendUser());
        }
        return $this->onlineMediaHelperRegistry->transformUrlToFile($url, $targetFolder, $allowedExtensions);
    }

    /**
     * Add flash message to message queue
     */
    protected function addFlashMessage(FlashMessage $flashMessage): void
    {
        $defaultFlashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
