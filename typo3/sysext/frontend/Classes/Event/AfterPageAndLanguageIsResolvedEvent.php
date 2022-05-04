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

namespace TYPO3\CMS\Frontend\Event;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * A PSR-14 event fired in the frontend process after a given page has been resolved including
 * its language.
 *
 * This event is intended to e.g. modify TYPO3's language resolving logic by custom additions.
 * This event also allows to send a custom Response via Event Listeners (e.g. a custom 403 response)
 */
final class AfterPageAndLanguageIsResolvedEvent
{
    public function __construct(
        private TypoScriptFrontendController $controller,
        private ServerRequestInterface $request,
        private ?ResponseInterface $response
    ) {
    }

    public function getController(): TypoScriptFrontendController
    {
        return $this->controller;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function setResponse(?ResponseInterface $response): void
    {
        $this->response = $response;
    }
}
