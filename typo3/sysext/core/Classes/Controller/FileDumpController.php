<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Controller;

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
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Hook\FileDumpEIDHookInterface;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileDumpController
 */
class FileDumpController
{
    /**
     * Main method to dump a file
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException
     * @throws \UnexpectedValueException
     */
    public function dumpAction(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = ['eID' => 'dumpFile'];
        $t = $this->getGetOrPost($request, 't');
        if ($t) {
            $parameters['t'] = $t;
        }
        $f = $this->getGetOrPost($request, 'f');
        if ($f) {
            $parameters['f'] = $f;
        }
        $p = $this->getGetOrPost($request, 'p');
        if ($p) {
            $parameters['p'] = $p;
        }

        if (hash_equals(GeneralUtility::hmac(implode('|', $parameters), 'resourceStorageDumpFile'), $this->getGetOrPost($request, 'token'))) {
            if (isset($parameters['f'])) {
                try {
                    $file = ResourceFactory::getInstance()->getFileObject($parameters['f']);
                    if ($file->isDeleted() || $file->isMissing() || !$this->isFileValid($file)) {
                        $file = null;
                    }
                } catch (\Exception $e) {
                    $file = null;
                }
            } else {
                $file = GeneralUtility::makeInstance(ProcessedFileRepository::class)->findByUid($parameters['p']);
                if (!$file || $file->isDeleted() || !$this->isFileValid($file->getOriginalFile())) {
                    $file = null;
                }
            }

            if ($file === null) {
                return (new Response)->withStatus(404);
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

            return $file->getStorage()->streamFile($file);
        }
        return (new Response)->withStatus(403);
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $parameter
     * @return string
     */
    protected function getGetOrPost(ServerRequestInterface $request, string $parameter): string
    {
        return (string)($request->getParsedBody()[$parameter] ?? $request->getQueryParams()[$parameter] ?? '');
    }

    protected function isFileValid(FileInterface $file): bool
    {
        return $file->getStorage()->getDriverType() !== 'Local'
            || GeneralUtility::verifyFilenameAgainstDenyPattern(basename($file->getIdentifier()));
    }
}
