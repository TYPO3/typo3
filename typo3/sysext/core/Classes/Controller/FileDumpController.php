<?php
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
use TYPO3\CMS\Core\Resource\Hook\FileDumpEIDHookInterface;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * Class FileDumpController
 */
class FileDumpController
{
    /**
     * Main method to dump a file
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return NULL|ResponseInterface
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException
     * @throws \UnexpectedValueException
     */
    public function dumpAction(ServerRequestInterface $request, ResponseInterface $response)
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

        if (GeneralUtility::hmac(implode('|', $parameters), 'resourceStorageDumpFile') === $this->getGetOrPost($request, 'token')) {
            if (isset($parameters['f'])) {
                try {
                    $file = ResourceFactory::getInstance()->getFileObject($parameters['f']);
                    if ($file->isDeleted() || $file->isMissing()) {
                        $file = null;
                    }
                } catch (\Exception $e) {
                    $file = null;
                }
            } else {
                $file = GeneralUtility::makeInstance(ProcessedFileRepository::class)->findByUid($parameters['p']);
                if (!$file || $file->isDeleted()) {
                    $file = null;
                }
            }

            if ($file === null) {
                HttpUtility::setResponseCodeAndExit(HttpUtility::HTTP_STATUS_404);
            }

            // Hook: allow some other process to do some security/access checks. Hook should issue 403 if access is rejected
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['FileDumpEID.php']['checkFileAccess'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['FileDumpEID.php']['checkFileAccess'] as $classRef) {
                    $hookObject = GeneralUtility::getUserObj($classRef);
                    if (!$hookObject instanceof FileDumpEIDHookInterface) {
                        throw new \UnexpectedValueException($classRef . ' must implement interface ' . FileDumpEIDHookInterface::class, 1394442417);
                    }
                    $hookObject->checkFileAccess($file);
                }
            }
            $file->getStorage()->dumpFileContents($file);
            // @todo Refactor FAL to not echo directly, but to implement a stream for output here and use response
            return null;
        } else {
            return $response->withStatus(403);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $parameter
     * @return NULL|mixed
     */
    protected function getGetOrPost(ServerRequestInterface $request, $parameter)
    {
        return isset($request->getParsedBody()[$parameter])
            ? $request->getParsedBody()[$parameter]
            : (isset($request->getQueryParams()[$parameter]) ? $request->getQueryParams()[$parameter] : null);
    }
}
