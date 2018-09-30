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
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rsaauth\RsaEncryptionEncoder;

/**
 * eID script "RsaPublicKeyGenerationController" to generate an rsa key
 */
class RsaPublicKeyGenerationController
{
    /**
     * @var RsaEncryptionEncoder
     */
    protected $encoder;

    /**
     * Set up dependencies
     * @param RsaEncryptionEncoder|null $encoder
     */
    public function __construct(RsaEncryptionEncoder $encoder = null)
    {
        $this->encoder = $encoder ?: GeneralUtility::makeInstance(RsaEncryptionEncoder::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $keyPair = $this->encoder->getRsaPublicKey();

        if ($keyPair === null) {
            // add a HTTP 500 error code, if an error occurred
            return new JsonResponse(null, 500);
        }

        switch ($request->getHeaderLine('content-type')) {
            case 'application/json':
                $data = [
                    'publicKeyModulus' => $keyPair->getPublicKeyModulus(),
                    'exponent' => sprintf('%x', $keyPair->getExponent()),
                ];
                $response = new JsonResponse($data);
                break;

            default:
                trigger_error('Requesting RSA public keys without "Content-Type: application/json" will be removed in TYPO3 v10.0. Add this header to your AJAX request.', E_USER_DEPRECATED);

                $content = $keyPair->getPublicKeyModulus() . ':' . sprintf('%x', $keyPair->getExponent()) . ':';
                $response = new Response('php://temp', 200, ['Content-Type' => 'application/json; charset=utf-8']);
                $response->getBody()->write($content);
                break;
        }

        return $response;
    }
}
