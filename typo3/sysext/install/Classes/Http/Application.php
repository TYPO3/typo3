<?php
namespace TYPO3\CMS\Install\Http;

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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Http\AbstractApplication;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Entry point for the TYPO3 Install Tool
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class Application extends AbstractApplication
{
    /**
     * All available request handlers that can handle an install tool request
     * @var array
     */
    protected $availableRequestHandlers = [];

    /**
     * Construct Application
     *
     * @param RequestHandlerInterface $requestHandler
     * @param RequestHandlerInterface $installerRequestHandler
     */
    public function __construct(
        RequestHandlerInterface $requestHandler,
        RequestHandlerInterface $installerRequestHandler
    ) {
        $this->availableRequestHandlers = [
            $requestHandler,
            $installerRequestHandler
        ];
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->initializeContext();
        foreach ($this->availableRequestHandlers as $handler) {
            if ($handler->canHandleRequest($request)) {
                return $handler->handle($request);
            }
        }
        throw new \TYPO3\CMS\Core\Exception('No suitable request handler found.', 1518448686);
    }

    /**
     * Initializes the Context used for accessing data and finding out the current state of the application
     * Will be moved to a DI-like concept once introduced, for now, this is a singleton
     */
    protected function initializeContext()
    {
        GeneralUtility::makeInstance(Context::class, [
            'date' => new DateTimeAspect(new \DateTimeImmutable('@' . $GLOBALS['EXEC_TIME'])),
            'visibility' => new VisibilityAspect(true, true, true),
            'workspace' => new WorkspaceAspect(0),
            'backend.user' => new UserAspect(),
        ]);
    }
}
