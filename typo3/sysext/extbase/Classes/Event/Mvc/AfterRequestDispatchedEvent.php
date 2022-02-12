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

namespace TYPO3\CMS\Extbase\Event\Mvc;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

/**
 * Event which is fired after the dispatcher has successfully dispatched a request to a controller/action.
 */
final class AfterRequestDispatchedEvent
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly ResponseInterface $response
    ) {
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
