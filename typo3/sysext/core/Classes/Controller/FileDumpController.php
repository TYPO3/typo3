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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Hook\FileDumpEIDHookInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileDumpController
 */
class FileDumpController
{
    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    public function __construct(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
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
            return (new Response())->withStatus(403);
        }
        $file = $this->createFileObjectByParameters($parameters);
        if ($file === null) {
            return (new Response())->withStatus(404);
        }

        // Hook: allow some other process to do some security/access checks. Hook should return 403 response if access is rejected, void otherwise
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

        // Apply cropping, if possible
        if (!$file instanceof ProcessedFile) {
            $cropVariant = $parameters['cv'] ?: 'default';
            $cropString = $file instanceof FileReference ? $file->getProperty('crop') : '';
            $cropArea = CropVariantCollection::create((string)$cropString)->getCropArea($cropVariant);
            $processingInstructions = [
                'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($file),
            ];

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
                        'maxHeight' => $size[5] ? (int)$size[5] : null
                    ]
                );
            }
            if (is_callable([$file, 'getOriginalFile'])) {
                // Get the original file from the file reference
                $file = $file->getOriginalFile();
            }
            $file = $file->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingInstructions);
        }

        return $file->getStorage()->streamFile($file);
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
        $v = (string)($queryParams['cv'] ?? '');
        if ($v) {
            $parameters['cv'] = (string)$v;
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
                if ($file->isDeleted() || $file->isMissing()) {
                    $file = null;
                }
            } catch (\Exception $e) {
                $file = null;
            }
        } elseif (isset($parameters['r'])) {
            try {
                $file = $this->resourceFactory->getFileReferenceObject($parameters['r']);
                if ($file->isMissing()) {
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
                if (!$file || $file->isDeleted()) {
                    $file = null;
                }
            } catch (\Exception $e) {
                $file = null;
            }
        }
        return $file;
    }
}
