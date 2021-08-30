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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Controller class to create a zip file for given items from a file or folder.
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class FileDownloadController
{
    protected ResourceFactory $resourceFactory;
    protected ResponseFactoryInterface $responseFactory;
    protected Context $context;

    public function __construct(
        ResourceFactory $resourceFactory,
        ResponseFactoryInterface $responseFactory,
        Context $context
    ) {
        $this->resourceFactory = $resourceFactory;
        $this->responseFactory = $responseFactory;
        $this->context = $context;
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $items = (array)($request->getParsedBody()['items'] ?? []);
        if ($items === []) {
            return $this->responseFactory->createResponse(400);
        }

        $zipStream = tmpfile();
        if (!is_resource($zipStream)) {
            throw new \RuntimeException('Could not open temporary resource for creating archive', 1630346631);
        }
        $zipFileName = stream_get_meta_data($zipStream)['uri'];
        $zipFile = new \ZipArchive();
        $zipFile->open($zipFileName, \ZipArchive::OVERWRITE);
        $filesAdded = 0;
        foreach ($this->collectFiles($items) as $fileName => $fileObject) {
            // Add files with read permission
            if (!$fileObject->getStorage()->checkFileActionPermission('read', $fileObject)) {
                continue;
            }
            $filesAdded++;
            $zipFile->addFile($fileObject->getForLocalProcessing(false), $fileName);
        }
        if ($filesAdded === 0) {
            $zipFile->addFromString('No files found.txt', 'No files found to create a zip file');
        }
        $zipFile->close();
        $response = $this->createResponse($zipFileName);
        unlink($zipFileName);
        return $response;
    }

    protected function createResponse($temporaryFileName): ResponseInterface
    {
        $downloadFileName = 'typo3_download_' . $this->context->getAspect('date')->getDateTime()->format('Y-m-d-His') . '.zip';
        $response = $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'application/zip')
            ->withHeader('Content-Disposition', 'attachment; filename=' . $downloadFileName)
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Cache-Control', 'public, must-revalidate');
        $body = new Stream('php://temp', 'rw');
        $body->write(file_get_contents($temporaryFileName));
        return $response->withBody($body);
    }

    /**
     * @param array $items
     * @return FileInterface[]
     */
    protected function collectFiles(array $items): array
    {
        $files = [];
        foreach ($items as $itemIdentifier) {
            $fileOrFolderObject = $this->resourceFactory->retrieveFileOrFolderObject($itemIdentifier);
            if ($fileOrFolderObject === null) {
                continue;
            }
            $baseIdentifier = dirname($fileOrFolderObject->getIdentifier());
            if ($fileOrFolderObject instanceof Folder) {
                // handle file / folder structure
                foreach ($this->getFilesAndFoldersRecursive($fileOrFolderObject) as $fileObject) {
                    $commonPrefix = (string)PathUtility::getCommonPrefix([$baseIdentifier, $fileObject->getIdentifier()]);
                    $files[substr($fileObject->getIdentifier(), strlen($commonPrefix))] = $fileObject;
                }
            } else {
                $files[$fileOrFolderObject->getName()] = $fileOrFolderObject;
            }
        }
        return $files;
    }

    protected function getFilesAndFoldersRecursive(Folder $folder): iterable
    {
        foreach ($folder->getSubfolders() as $subFolder) {
            yield from $this->getFilesAndFoldersRecursive($subFolder);
        }
        foreach ($folder->getFiles() as $file) {
            yield $file;
        }
    }
}
