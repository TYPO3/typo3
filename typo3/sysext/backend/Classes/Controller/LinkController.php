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
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * @internal
 */
#[Controller]
final class LinkController
{
    public function __construct(
        protected readonly LinkService $linkService,
        protected readonly ResourceFactory $resourceFactory
    ) {
    }

    public function resourceAction(ServerRequestInterface $request): ResponseInterface
    {
        $identifier = $request->getParsedBody()['identifier'] ?? null;
        $link = null;
        $resource = null;

        if ($identifier) {
            $resource = $this->resourceFactory->retrieveFileOrFolderObject($identifier);
        }

        try {
            if (!$resource instanceof File && !$resource instanceof Folder) {
                throw new \InvalidArgumentException('Resource must be a file or a folder', 1679039649);
            }
            if ($resource->getStorage()->getUid() === 0) {
                throw new InsufficientFileAccessPermissionsException('You are not allowed to access files outside your storages', 1679039650);
            }
            if ($resource instanceof File) {
                $parameters = [
                    'type' => LinkService::TYPE_FILE,
                    'file' => $resource,
                ];
            }
            if ($resource instanceof Folder) {
                $parameters = [
                    'type' => LinkService::TYPE_FOLDER,
                    'folder' => $resource,
                ];
            }
            $link = $this->linkService->asString($parameters);
        } catch (\Exception $exception) {
            $message = match ($exception->getCode()) {
                1679039649 => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_resource.xlf:ajax.error.message.resourceNotFileOrFolder'),
                1679039650 => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_resource.xlf:ajax.error.message.resourceOutsideOfStorages'),
                default => $exception->getMessage(),
            };

            return new JsonResponse($this->getResponseData(false, $message));
        }

        return new JsonResponse($this->getResponseData(true, null, $link));
    }

    /**
     * Prepare response data for a JSON response
     */
    protected function getResponseData(bool $success, ?string $message = null, ?string $link = null): array
    {
        $flashMessageQueue = new FlashMessageQueue('backend');
        if ($message) {
            $flashMessageQueue->enqueue(
                new FlashMessage(
                    $message,
                    $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_resource.xlf:ajax.' . ($success ? 'success' : 'error')),
                    $success ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::ERROR
                )
            );
        }
        return [
            'success' => $success,
            'status' => $flashMessageQueue,
            'link' => $link,
        ];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
