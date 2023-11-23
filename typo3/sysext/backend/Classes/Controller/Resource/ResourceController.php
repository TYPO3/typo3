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

namespace TYPO3\CMS\Backend\Controller\Resource;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
#[Controller]
final class ResourceController
{
    public function __construct(
        private readonly ResourceFactory $resourceFactory,
    ) {}

    public function renameResourceAction(ServerRequestInterface $request): ResponseInterface
    {
        $identifier = $request->getParsedBody()['identifier'] ?? null;
        $origin = null;

        if ($identifier) {
            $origin = $this->resourceFactory->retrieveFileOrFolderObject($identifier);
        }

        try {
            if (!$origin instanceof File && !$origin instanceof Folder) {
                throw new \InvalidArgumentException('Resource must be a file or a folder', 1676979120);
            }
            if ($origin->getStorage()->getUid() === 0) {
                throw new InsufficientFileAccessPermissionsException('You are not allowed to access files outside your storages', 1676299579);
            }
            if (!$origin->checkActionPermission('rename')) {
                throw new InsufficientFileAccessPermissionsException('You are not allowed to rename the resource', 1676979130);
            }
            $resourceName = $request->getParsedBody()['resourceName'] ?? null;
            if (!$resourceName || trim((string)$resourceName) === '') {
                throw new \InvalidArgumentException('The resource name cannot be empty', 1676978732);
            }
            $oldName = $origin->getName();
            $resource = $origin->rename($resourceName);
        } catch (\Exception $exception) {
            $message = match ($exception->getCode()) {
                1676979120 => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_resource.xlf:ajax.error.message.resourceNotFileOrFolder'),
                1676299579 => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_resource.xlf:ajax.error.message.resourceOutsideOfStorages'),
                1676979130 => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_resource.xlf:ajax.error.message.resourceNoPermissionRename'),
                1676978732 => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_resource.xlf:ajax.error.message.resourceNameCannotBeEmpty'),
                default => $exception->getMessage(),
            };
            return new JsonResponse($this->getResponseData(false, $message));
        }

        return new JsonResponse($this->getResponseData(
            true,
            sprintf(
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_resource.xlf:ajax.success.message.renamed'),
                $oldName,
                $resource->getName()
            ),
            $origin,
            $resource,
        ));
    }

    /**
     * Prepare response data for a JSON response
     */
    private function getResponseData(bool $success, string $message, ?ResourceInterface $origin = null, ?ResourceInterface $resource = null): array
    {
        $flashMessageQueue = new FlashMessageQueue('backend');
        $flashMessageQueue->enqueue(
            new FlashMessage(
                $message,
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_resource.xlf:ajax.' . ($success ? 'success' : 'error'))
            )
        );
        return [
            'success' => $success,
            'status' => $flashMessageQueue,
            'origin' => $this->getResourceResponseData($origin),
            'resource' => $this->getResourceResponseData($resource),
        ];
    }

    /**
     * Prepare resource data for a JSON response
     */
    private function getResourceResponseData(?ResourceInterface $resource): ?array
    {
        if (!$resource) {
            return null;
        }
        return [
            'type' => $resource instanceof File ? 'file' : 'folder',
            'identifier' => $resource instanceof File || $resource instanceof Folder ? $resource->getCombinedIdentifier() : null,
            'stateIdentifier' => $resource->getStorage()->getUid() . '_' . GeneralUtility::md5int($resource->getIdentifier()),
            'name' => $resource->getName(),
            'uid' => $resource instanceof File ? $resource->getUid() : null,
            'metaUid' => $resource instanceof File ? $resource->getMetaData()->offsetGet('uid') : null,
        ];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
