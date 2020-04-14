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

namespace TYPO3\CMS\Core\Http\Client;

use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 */
class RequestException extends GuzzleRequestException implements RequestExceptionInterface
{
    public function __construct(
        string $message,
        int $code,
        RequestInterface $request,
        GuzzleRequestException $previous
    ) {
        parent::__construct($message, $request, null, $previous);
        $this->code = $code;
    }

    public function getRequest(): RequestInterface
    {
        return parent::getRequest();
    }
}
