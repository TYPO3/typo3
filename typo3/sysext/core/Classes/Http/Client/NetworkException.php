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

use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * @internal
 */
class NetworkException extends GuzzleConnectException implements NetworkExceptionInterface
{
    public function __construct(
        string $message,
        int $code,
        RequestInterface $request,
        GuzzleConnectException $previous
    ) {
        parent::__construct($message, $request, $previous);
        $this->code = $code;
    }

    public function getRequest(): RequestInterface
    {
        return parent::getRequest();
    }
}
