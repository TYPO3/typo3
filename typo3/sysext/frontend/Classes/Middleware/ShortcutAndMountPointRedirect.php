<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Middleware;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Checks mount points or shortcuts and redirects to the target
 */
class ShortcutAndMountPointRedirect implements MiddlewareInterface
{
    /**
     * @var TypoScriptFrontendController
     */
    private $controller;

    public function __construct(TypoScriptFrontendController $controller = null)
    {
        $this->controller = $controller ?: $GLOBALS['TSFE'];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Check for shortcut page and mount point redirect
        $redirectToUri = $this->getRedirectUri();
        if ($redirectToUri !== null && $redirectToUri !== (string)$request->getUri()) {
            return new RedirectResponse($redirectToUri, 307);
        }

        return $handler->handle($request);
    }

    protected function getRedirectUri(): ?string
    {
        $redirectToUri = $this->controller->getRedirectUriForShortcut();
        if ($redirectToUri !== null) {
            return $redirectToUri;
        }
        return $this->controller->getRedirectUriForMountPoint();
    }
}
