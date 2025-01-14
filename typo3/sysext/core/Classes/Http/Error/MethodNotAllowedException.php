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

namespace TYPO3\CMS\Core\Http\Error;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\HtmlResponse;

/**
 * @internal
 */
final class MethodNotAllowedException extends Exception
{
    public readonly array $allowedMethods;

    /**
     * @param non-empty-list<non-empty-string> $allowedMethods
     */
    public function __construct(array $allowedMethods, int $code = 0, ?\Throwable $previous = null)
    {
        $this->allowedMethods = array_map('strtoupper', $allowedMethods);

        parent::__construct(
            sprintf(
                'HTTP method is not allowed! Allowed method(s): %s',
                implode(', ', $this->allowedMethods),
            ),
            $code,
            $previous,
        );
    }

    public function createResponse(): ResponseInterface
    {
        return new HtmlResponse($this->message, 405, ['Allow' => implode(', ', $this->allowedMethods)]);
    }
}
