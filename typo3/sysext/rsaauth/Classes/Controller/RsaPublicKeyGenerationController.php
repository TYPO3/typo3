<?php
namespace TYPO3\CMS\Rsaauth\Controller;

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
use TYPO3\CMS\Rsaauth\Backend\BackendFactory;
use TYPO3\CMS\Rsaauth\Storage\StorageFactory;

/**
 * eID script "RsaPublicKeyGenerationController" to generate an rsa key
 */
class RsaPublicKeyGenerationController
{
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function processRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        /** @var \TYPO3\CMS\Rsaauth\Backend\AbstractBackend $backend */
        $backend = BackendFactory::getBackend();
        if ($backend === null) {
            // add a HTTP 500 error code, if an error occurred
            return $response->withStatus(500);
        }

        $keyPair = $backend->createNewKeyPair();
        $storage = StorageFactory::getStorage();
        $storage->put($keyPair->getPrivateKey());
        session_commit();
        $content = $keyPair->getPublicKeyModulus() . ':' . sprintf('%x', $keyPair->getExponent()) . ':';
        $response->getBody()->write($content);
        return $response;
    }
}
