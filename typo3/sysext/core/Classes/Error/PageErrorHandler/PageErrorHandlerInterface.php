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

namespace TYPO3\CMS\Core\Error\PageErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Page error handler interface, used to jump in for Frontend-related calls
 *
 * Needs to be implemented by all custom PHP-related Page Error Handlers.
 */
interface PageErrorHandlerInterface
{
    public function handlePageError(ServerRequestInterface $request, string $message, array $reasons = []): ResponseInterface;
}
