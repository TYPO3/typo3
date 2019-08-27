<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Http\Client;

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

use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

/**
 * @internal
 */
class ClientException extends RuntimeException implements ClientExceptionInterface, GuzzleException
{
    public function __construct(string $message, int $code, GuzzleException $previous)
    {
        parent::__construct($message, $code, $previous);
    }
}
