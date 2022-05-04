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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * A PSR-14 event fired before the frontend process is trying to fully resolve a given page
 * by its page ID and the request.
 *
 * Event Listeners can modify incoming parameters (such as $controller->id) or modify the context
 * for resolving a page.
 */
final class BeforePageIsResolvedEvent
{
    public function __construct(
        private TypoScriptFrontendController $controller,
        private ServerRequestInterface $request
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
}
