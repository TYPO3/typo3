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

namespace TYPO3\CMS\Core\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\Event\ModifyFileDumpEvent;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Hook\FileDumpEIDHookInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileDumpController
 */
class FileDumpController
{
    protected ResourceFactory $resourceFactory;
    protected EventDispatcherInterface $eventDispatcher;
    protected ResponseFactoryInterface $responseFactory;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        ResourceFactory $resourceFactory,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->resourceFactory = $resourceFactory;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Main method to dump a file
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws FileDoesNotExistException
     * @throws \UnexpectedValueException
     */
    public function dumpAction(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $this->buildParametersFromRequest($request);

        if (!$this->isTokenValid($parameters, $request)) {
            return $this->responseFactory->createResponse(403);
        }
        $file = $this->createFileObjectByParameters($parameters);
        if ($file === null) {
            return $this->responseFactory->createResponse(404);
        }

        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['FileDumpEID.php']['checkFileAccess'])) {
            trigger_error(
                'The hook $TYPO3_CONF_VARS[SC_OPTIONS][FileDumpEID.php][checkFileAccess] is deprecated and will stop working in TYPO3 v12.0. Use the ModifyFileDumpEvent instead.',
                E_USER_DEPRECATED
            );
        }

        // Hook: allow some other process to do some security/access checks. Hook should return 403 response if access is rejected, void otherwise
        // @deprecated: will be removed in TYPO3 v12.0.
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['FileDumpEID.php']['checkFileAccess'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof FileDumpEIDHookInterface) {
                throw new \UnexpectedValueException($className . ' must implement interface ' . FileDumpEIDHookInterface::class, 1394442417);
            }
            $response = $hookObject->checkFileAccess($file);
            if ($response instanceof ResponseInterface) {
                return $response;
            }
        }

        // Allow some other process to do some security/access checks.
        // Event Listeners should return a 403 response if access is rejected
        $event = new ModifyFileDumpEvent($file, $request);
        $event = $this->eventDispatcher->dispatch($event);
        if ($event->isPropagationStopped()) {
            return $event->getResponse();
        }
        $file = $event->getFile();

        $processingInstructions = [];

        // Apply cropping, if possible
        if (!empty($parameters['cv'])) {
            $cropVariant = $parameters['cv'];
            $cropString = $file instanceof FileReference ? $file->getProperty('crop') : '';
            $cropArea = CropVariantCollection::create((string)$cropString)->getCropArea($cropVariant);
            $processingInstructions = array_merge(
                $processingInstructions,
                [
                    'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($file),
                ]
            );
        }

        // Apply width/height, if given
        if (!empty($parameters['s'])) {
            $size = GeneralUtility::trimExplode(':', $parameters['s']);
            $processingInstructions = array_merge(
                $processingInstructions,
                [
                    'width' => $size[0] ?? null,
                    'height' => $size[1] ?? null,
                    'minWidth' => $size[2] ? (int)$size[2] : null,
                    'minHeight' => $size[3] ? (int)$size[3] : null,
                    'maxWidth' => $size[4] ? (int)$size[4] : null,
                    'maxHeight' => $size[5] ? (int)$size[5] : null,
                ]
            );
        }

        if (!empty($processingInstructions) && !($file instanceof ProcessedFile)) {
            if (is_callable([$file, 'getOriginalFile'])) {
                // Get the original file from the file reference
                $file = $file->getOriginalFile();
            }
            $file = $file->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingInstructions);
        }

        return $file->getStorage()->streamFile(
            $file,
            (bool)($parameters['dl'] ?? false),
            $parameters['fn'] ?? null
        );
    }

    protected function buildParametersFromRequest(ServerRequestInterface $request): array
    {
        $parameters = ['eID' => 'dumpFile'];
        $queryParams = $request->getQueryParams();
        // Identifier of what to process. f, r or p
        // Only needed while hash_equals
        $t = (string)($queryParams['t'] ?? '');
        if ($t) {
            $parameters['t'] = $t;
        }
        // sys_file
        $f = (string)($queryParams['f'] ?? '');
        if ($f) {
            $parameters['f'] = (int)$f;
        }
        // sys_file_reference
        $r = (string)($queryParams['r'] ?? '');
        if ($r) {
            $parameters['r'] = (int)$r;
        }
        // Processed file
        $p = (string)($queryParams['p'] ?? '');
        if ($p) {
            $parameters['p'] = (int)$p;
        }
        // File's width and height in this order: w:h:minW:minH:maxW:maxH
        $s = (string)($queryParams['s'] ?? '');
        if ($s) {
            $parameters['s'] = $s;
        }
        // File's crop variant
        $cv = (string)($queryParams['cv'] ?? '');
        if ($cv) {
            $parameters['cv'] = $cv;
        }
        // As download
        $dl = (string)($queryParams['dl'] ?? '');
        if ($dl) {
            $parameters['dl'] = (int)$dl;
        }
        // Alternative file name
        $fn = (string)($queryParams['fn'] ?? '');
        if ($fn) {
            $parameters['fn'] = $fn;
        }

        return $parameters;
    }

    protected function isTokenValid(array $parameters, ServerRequestInterface $request): bool
    {
        return hash_equals(
            GeneralUtility::hmac(implode('|', $parameters), 'resourceStorageDumpFile'),
            $request->getQueryParams()['token'] ?? ''
        );
    }

    /**
     * @param array $parameters
     * @return File|FileReference|ProcessedFile|null
     */
    protected function createFileObjectByParameters(array $parameters)
    {
        $file = null;
        if (isset($parameters['f'])) {
            try {
                $file = $this->resourceFactory->getFileObject($parameters['f']);
                if ($file->isDeleted() || $file->isMissing() || !$this->isFileValid($file)) {
                    $file = null;
                }
            } catch (\Exception $e) {
                $file = null;
            }
        } elseif (isset($parameters['r'])) {
            try {
                $file = $this->resourceFactory->getFileReferenceObject($parameters['r']);
                if ($file->isMissing() || !$this->isFileValid($file->getOriginalFile())) {
                    $file = null;
                }
            } catch (\Exception $e) {
                $file = null;
            }
        } elseif (isset($parameters['p'])) {
            try {
                $processedFileRepository = GeneralUtility::makeInstance(ProcessedFileRepository::class);
                /** @var ProcessedFile|null $file */
                $file = $processedFileRepository->findByUid($parameters['p']);
                if (!$file || $file->isDeleted() || !$this->isFileValid($file->getOriginalFile())) {
                    $file = null;
                }
            } catch (\Exception $e) {
                $file = null;
            }
        }
        return $file;
    }

    protected function isFileValid(FileInterface $file): bool
    {
        return $file->getStorage()->getDriverType() !== 'Local'
            || GeneralUtility::makeInstance(FileNameValidator::class)
                ->isValid(basename($file->getIdentifier()));
    }
}
