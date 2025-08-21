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

namespace TYPO3\CMS\Frontend\Response;

/**
 * This class is used to collect response data such as protocol version,
 * status code, reason phrase and headers to be able to set them in the
 * response object later. The methods of the class are intentionally
 * inspired by the PSR-7 Psr\Http\Message\ResponseInterface.
 *
 * Note this data attribute has been created since central FE rendering related
 * services like ContentObjectRenderer can not deal with "additional" content data
 * such as HTTP headers or assets from content elements. The goal is to make
 * ContentObjectRenderer and other parts of the framework aware of this in the
 * future. This will obsolete or at least change this attribute again.
 *
 * @internal Strictly internal! This is likely to at least change or vanish again.
 */
final class ResponseData
{
    private string $protocolVersion = '1.1';
    private int $statusCode = 200;
    private string $reasonPhrase = 'OK';
    private array $headers = [];

    /**
     * @todo: This should not exist at all! It is job of the web server and not of the application
     *        to add a proper HTTP version identifier. Remove as soon as extbase bootstrap does not
     *        handle this anymore, application should not change this!
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function setProtocolVersion(string $protocolVersion): void
    {
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * @todo: This only exists for extbase b/w compat, see extbase bootstrap: In general, plugin like extbase
     *        should always *throw* PropagateResponseException to *stop rendering* in case of 3xx or 4xx.
     *        We should *break* this part in TYPO3 v15 by removing the handling from extbase bootstrap, and
     *        *communicate* that content elements which create non-200 should *throw*. PropagateResponseException
     *        should then be made non-internal.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @todo: Follows getStatusCode(), should be adapted as well.
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function setReasonPhrase(string $reasonPhrase): void
    {
        $this->reasonPhrase = $reasonPhrase;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeader(string $name, $value): void
    {
        $this->headers[$name] = $value;
    }
}
