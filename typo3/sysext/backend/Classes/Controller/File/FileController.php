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

namespace TYPO3\CMS\Backend\Controller\File;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Gateway for TCE (TYPO3 Core Engine) file-handling through POST forms.
 * This script serves as the file administration part of the TYPO3 Core Engine.
 * Basically it includes two libraries which are used to manipulate files on the server.
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class FileController
{
    /**
     * Array of file-operations.
     *
     * @var array
     */
    protected $file;

    /**
     * Clipboard operations array
     *
     * @var array
     */
    protected $CB;

    /**
     * Defines behaviour when uploading files with names that already exist; possible values are
     * the values of the \TYPO3\CMS\Core\Resource\DuplicationBehavior enumeration
     *
     * @var DuplicationBehavior
     */
    protected $overwriteExistingFiles;

    /**
     * The page where the user should be redirected after everything is done
     *
     * @var string
     */
    protected $redirect;

    /**
     * The result array from the file processor
     *
     * @var array
     */
    protected $fileData;

    protected ExtendedFileUtility $fileProcessor;
    protected ResourceFactory $fileFactory;
    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;

    public function __construct(ResourceFactory $resourceFactory, ExtendedFileUtility $fileProcessor, IconFactory $iconFactory, UriBuilder $uriBuilder)
    {
        $this->fileFactory = $resourceFactory;
        $this->fileProcessor = $fileProcessor;
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it just redirects to the given URL afterwards.
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     * @throws RouteNotFoundException
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        $this->main();

        BackendUtility::setUpdateSignal('updateFolderTree');

        // go and edit the new created file
        if ($request->getParsedBody()['edit'] ?? '') {
            /** @var File $file */
            $file = $this->fileData['newfile'][0];
            if ($file !== null) {
                $this->redirect = $this->getFileEditRedirect($file) ?? $this->redirect;
            }
        }
        if ($this->redirect) {
            return new RedirectResponse(
                GeneralUtility::locationHeaderUrl($this->redirect),
                303
            );
        }
        // empty response
        return new HtmlResponse('');
    }

    /**
     * Handles the actual process from within the ajaxExec function
     * therefore, it does exactly the same as the real typo3/tce_file.php.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function processAjaxRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        $this->main();
        $includeMessages = (bool)($request->getQueryParams()['includeMessages'] ?? false);
        $errors = $this->fileProcessor->getErrorMessages();
        if (!$includeMessages && !empty($errors)) {
            return (new HtmlResponse('<t3err>' . implode(',', $errors) . '</t3err>'))->withStatus(500, '(AJAX)');
        }
        $flatResult = [];
        foreach ($this->fileData as $action => $results) {
            foreach ($results as $result) {
                if (is_array($result)) {
                    foreach ($result as $subResult) {
                        $flatResult[$action][] = $this->flattenResultDataValue($subResult);
                    }
                } else {
                    $flatResult[$action][] = $this->flattenResultDataValue($result);
                }
            }
        }

        // Used in the FileStorageTree when moving / copying folders
        if ($includeMessages) {
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $messages = $flashMessageService->getMessageQueueByIdentifier()->getAllMessagesAndFlush();
            if (!empty($messages)) {
                foreach ($messages as $message) {
                    $flatResult['messages'][] = [
                        'title'    => $message->getTitle(),
                        'message'  => $message->getMessage(),
                        'severity' => $message->getSeverity(),
                    ];
                    if ($message->getSeverity() === AbstractMessage::ERROR) {
                        $flatResult['hasErrors'] = true;
                    }
                }
            }
        }
        return new JsonResponse($flatResult);
    }

    /**
     * Ajax entry point to check if a file exists in a folder
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function fileExistsInFolderAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        $fileName = $request->getParsedBody()['fileName'] ?? $request->getQueryParams()['fileName'] ?? null;
        $fileTarget = $request->getParsedBody()['fileTarget'] ?? $request->getQueryParams()['fileTarget'] ?? null;

        $fileTargetObject = $this->fileFactory->retrieveFileOrFolderObject($fileTarget);
        $processedFileName = $fileTargetObject->getStorage()->sanitizeFileName($fileName, $fileTargetObject);

        $result = [];
        if ($fileTargetObject->hasFile($processedFileName)) {
            $fileInFolder = $fileTargetObject->getStorage()->getFileInFolder($processedFileName, $fileTargetObject);
            if ($fileInFolder instanceof File) {
                $result = $this->flattenFileResultDataValue($fileInFolder);
            }
        }
        return new JsonResponse($result);
    }

    /**
     * Registering incoming data
     *
     * @param ServerRequestInterface $request
     */
    protected function init(ServerRequestInterface $request): void
    {
        // Set the GPvars from outside
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $this->file = (array)($parsedBody['data'] ?? $queryParams['data'] ?? []);
        $redirectUrl = (string)($parsedBody['redirect'] ?? $queryParams['redirect'] ?? '');
        if ($this->file === [] || $redirectUrl !== '') {
            // This in clipboard mode or when a new folder is created
            $this->redirect = GeneralUtility::sanitizeLocalUrl($redirectUrl);
        } else {
            $mode = key($this->file);
            $elementKey = key($this->file[$mode]);
            $this->redirect = GeneralUtility::sanitizeLocalUrl($this->file[$mode][$elementKey]['redirect'] ?? '');
        }
        $this->CB = (array)($parsedBody['CB'] ?? $queryParams['CB'] ?? []);

        if (isset($this->file['rename'][0]['conflictMode'])) {
            $conflictMode = $this->file['rename'][0]['conflictMode'];
            unset($this->file['rename'][0]['conflictMode']);
            $this->overwriteExistingFiles = DuplicationBehavior::cast($conflictMode);
        } else {
            $this->overwriteExistingFiles = DuplicationBehavior::cast($parsedBody['overwriteExistingFiles'] ?? $queryParams['overwriteExistingFiles'] ?? null);
        }
        $this->initClipboard($request);
    }

    /**
     * Initialize the Clipboard. This will fetch the data about files to paste/delete if such an action has been sent.
     */
    protected function initClipboard(ServerRequestInterface $request): void
    {
        if ($this->CB !== []) {
            $clipObj = GeneralUtility::makeInstance(Clipboard::class);
            $clipObj->initializeClipboard($request);
            if ($this->CB['paste'] ?? false) {
                $clipObj->setCurrentPad((string)($this->CB['pad'] ?? ''));
                $this->setPasteCmd($clipObj);
            }
            if ($this->CB['delete'] ?? false) {
                $clipObj->setCurrentPad((string)($this->CB['pad'] ?? ''));
                $this->setDeleteCmd($clipObj);
            }
        }
    }

    /**
     * Performing the file admin action:
     * Initializes the objects, setting permissions, sending data to object.
     */
    protected function main(): void
    {
        $this->fileProcessor->setActionPermissions();
        $this->fileProcessor->setExistingFilesConflictMode($this->overwriteExistingFiles);
        $this->fileProcessor->start($this->file);
        $this->fileData = $this->fileProcessor->processData();
    }

    /**
     * Gets URI to be used for editing given file (if file extension is defined in textfile_ext)
     *
     * @param File $file to be edited
     * @return string|null URI to be redirected to
     * @throws RouteNotFoundException
     */
    protected function getFileEditRedirect(File $file): ?string
    {
        if (!$file->isTextFile()) {
            return null;
        }
        $properties = $file->getProperties();
        $urlParameters = [
            'target' =>  $properties['storage'] . ':' . $properties['identifier'],
        ];
        if ($this->redirect) {
            $urlParameters['returnUrl'] = $this->redirect;
        }
        try {
            return (string)$this->uriBuilder->buildUriFromRoute('file_edit', $urlParameters);
        } catch (RouteNotFoundException $exception) {
            // no route for editing files available
            return '';
        }
    }

    protected function flattenFileResultDataValue(File $result): array
    {
        $thumbUrl = '';
        if ($result->isImage()) {
            $processedFile = $result->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, []);
            if ($processedFile) {
                $thumbUrl = $processedFile->getPublicUrl() ?? '';
            }
        }
        $path = '';
        if (is_callable([$result->getParentFolder(), 'getReadablePath'])) {
            $path = $result->getParentFolder()->getReadablePath();
        }
        return array_merge(
            $result->toArray(),
            [
                'date' => BackendUtility::date($result->getModificationTime()),
                'icon' => $this->iconFactory->getIconForFileExtension($result->getExtension(), Icon::SIZE_SMALL)->render(),
                'thumbUrl' => $thumbUrl,
                'path' => $path,
            ]
        );
    }

    /**
     * Flatten result value from FileProcessor
     *
     * The value can be a File, Folder or boolean
     *
     * @param bool|File|Folder|ProcessedFile $result
     *
     * @return bool|string|array
     */
    protected function flattenResultDataValue($result)
    {
        if ($result instanceof File) {
            $result = $this->flattenFileResultDataValue($result);
        } elseif ($result instanceof Folder) {
            $result = $result->getIdentifier();
        }

        return $result;
    }

    /**
     * Applies the proper paste configuration to $this->file
     */
    protected function setPasteCmd(Clipboard $clipboard): void
    {
        $target = explode('|', (string)$this->CB['paste'])[1] ?? '';
        $mode = $clipboard->currentMode() === 'copy' ? 'copy' : 'move';
        // Traverse elements and make CMD array
        foreach ($clipboard->elFromTable('_FILE') as $key => $path) {
            $this->file[$mode][] = ['data' => $path, 'target' => $target];
            if ($mode === 'move') {
                $clipboard->removeElement($key);
            }
        }
        $clipboard->endClipboard();
    }

    /**
     * Applies the proper delete configuration to $this->file
     */
    protected function setDeleteCmd(Clipboard $clipObj): void
    {
        // Traverse elements and make CMD array
        foreach ($clipObj->elFromTable('_FILE') as $key => $path) {
            $this->file['delete'][] = ['data' => $path];
            $clipObj->removeElement($key);
        }
        $clipObj->endClipboard();
    }
}
