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

namespace TYPO3\CMS\Filelist\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\OnlineMedia\Service\PreviewService;
use TYPO3\CMS\Core\Resource\ResourceFactory;

/**
 * Controller class to update an online media resource
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
class FileUpdateOnlineMediaController
{
    public function __construct(
        protected readonly ResourceFactory $resourceFactory,
        protected readonly OnlineMediaHelperRegistry $onlineMediaHelperRegistry,
        protected readonly PreviewService $previewService,
        protected readonly ResponseFactoryInterface $responseFactory,
        protected readonly StreamFactoryInterface $streamFactory
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $resource = $request->getParsedBody()['resource'] ?? [];

        if (($resource['type'] ?? '') !== 'file' || !isset($resource['uid'])) {
            return $this->createResponse(['success' => false], 400);
        }

        $fileObject = null;
        try {
            $fileObject = $this->resourceFactory->getFileObject($resource['uid']);
        } catch (FileDoesNotExistException $e) {
        }

        if ($fileObject === null
            || !($onlineMediaHelper = $this->onlineMediaHelperRegistry->getOnlineMediaHelper($fileObject))
            || !$fileObject->checkActionPermission('editMeta')
            || !$fileObject->getMetaData()->offsetExists('uid')
            || !$this->getBackendUser()->check('tables_modify', 'sys_file_metadata')
        ) {
            return $this->createResponse(['success' => false], 400);
        }

        try {
            $this->previewService->updatePreviewImage($fileObject);
        } catch (\InvalidArgumentException $e) {
            return $this->createResponse(['success' => false], 400);
        }

        // Update remaining meta data from online media helper
        $fileObject->getMetaData()->add($onlineMediaHelper->getMetaData($fileObject))->save();

        return $this->createResponse(['success' => true]);
    }

    protected function createResponse(array $data = [], int $status = 200): ResponseInterface
    {
        return $this->responseFactory->createResponse($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($this->streamFactory->createStream((string)json_encode($data)));
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
