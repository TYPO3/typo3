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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;

/**
 * @internal
 */
final readonly class ResponseService
{
    public function __construct(private StreamFactoryInterface $streamFactory) {}

    public function dropNonceFromHtmlResponse(ResponseInterface $response, ConsumableNonce $nonce): ResponseInterface
    {
        if (!str_starts_with($response->getHeaderLine('Content-Type'), 'text/html')) {
            return $response;
        }
        $responseBody = $response->getBody();
        if (!$responseBody->isReadable() || !$responseBody->isWritable() || $responseBody->getSize() === 0) {
            return $response;
        }
        $stream = $this->streamFactory->createStream($this->dropNonceFromHtml((string)$responseBody, $nonce));
        return $response->withBody($stream);
    }

    public function dropNonceFromHtml(string $html, ConsumableNonce $nonce): string
    {
        $noncePattern = preg_quote($nonce->value, '/');
        return preg_replace(
            '/\s*nonce="' . $noncePattern . '"|' . $noncePattern . '/',
            '',
            $html
        );
    }
}
