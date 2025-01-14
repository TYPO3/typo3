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

namespace TYPO3\CMS\Core\Http;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Error\MethodNotAllowedException;

trait AllowedMethodsTrait
{
    /**
     * Assert if request method matches allowed HTTP methods and throw exception on mismatch.
     *
     * @param non-empty-string ...$allowedHttpMethods
     * @throws MethodNotAllowedException
     */
    protected function assertAllowedHttpMethod(ServerRequestInterface $request, string ...$allowedHttpMethods): void
    {
        if (array_filter($allowedHttpMethods) === []) {
            throw new \LogicException(
                'Allowed HTTP methods cannot be empty.',
                1732188461,
            );
        }
        if (!in_array($request->getMethod(), $allowedHttpMethods, true)) {
            throw new MethodNotAllowedException($allowedHttpMethods, 1732193708);
        }
    }
}
