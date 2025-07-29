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
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Backend\ThumbnailSize;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\SysLog\Action\File as SystemLogFileAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Validation\ResultException;
use TYPO3\CMS\Core\Validation\ResultRenderingTrait;

/**
 * @internal
 */
#[AsController]
final readonly class ResourceController
{
    use ResultRenderingTrait;

    public function __construct(
        private ResourceFactory $resourceFactory,
    ) {}

    public function requestThumbnailAction(ServerRequestInterface $request): ResponseInterface
    {
        $identifier = $request->getQueryParams()['identifier'] ?? null;
        $thumbnailSizeIdentifier = $request->getQueryParams()['size'] ?? 'default';
        $keepAspectRatio = (bool)($request->getQueryParams()['keepAspectRatio'] ?? false);
        $resource = null;

        if ($identifier) {
            $resource = $this->resourceFactory->retrieveFileOrFolderObject($identifier);
        }
        if ($resource === null || !($resource instanceof File && ($resource->isImage() || $resource->isMediaFile()))) {
            return new Response(null, 404);
        }
        if (!$resource->checkActionPermission('read')) {
            return new Response(null, 403);
        }

        $thumbnailSize = ThumbnailSize::tryFrom($thumbnailSizeIdentifier) ?? ThumbnailSize::DEFAULT;
        [$width, $height] = $keepAspectRatio ? $thumbnailSize->getDimensions() : $thumbnailSize->getCroppedDimensions();
        $thumbnail = $resource
            ->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, ['width' => $width, 'height' => $height]);

        return new RedirectResponse(
            GeneralUtility::locationHeaderUrl($thumbnail->getPublicUrl() ?? '')
        );
    }

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
            if ($origin->getStorage()->isFallbackStorage()) {
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
        } catch (ResultException $exception) {
            // Possible Exception thrown within the `->rename(...)` chain via ResourceConsistencyService
            return new JsonResponse($this->getResponseData(false, $this->renderResultException($exception, $this->getLanguageService())));
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
        // Next to the flash message, also log the action to be consistent with the use in ExtendedFileUtiltiy
        $this->getBackendUser()->writelog(SystemLogType::FILE, SystemLogFileAction::RENAME, $success ? SystemLogErrorClassification::MESSAGE : SystemLogErrorClassification::USER_ERROR, null, $message, []);
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
            'name' => $resource->getName(),
            'uid' => $resource instanceof File ? $resource->getUid() : null,
            'metaUid' => $resource instanceof File ? $resource->getMetaData()->offsetGet('uid') : null,
        ];
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
