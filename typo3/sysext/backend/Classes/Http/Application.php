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

namespace TYPO3\CMS\Backend\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\AbstractApplication;

/**
 * Entry point for the TYPO3 Backend (HTTP requests)
 */
class Application extends AbstractApplication
{
    public function __construct(
        RequestHandlerInterface $requestHandler,
        protected readonly Context $context,
    ) {
        $this->requestHandler = $requestHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        // Set up the initial context
        $this->initializeContext();
        return parent::handle($request);
    }

    /**
     * Initializes the Context used for accessing data and finding out the current state of the application
     */
    protected function initializeContext(): void
    {
        $this->context->setAspect(
            'date',
            new DateTimeAspect(
                (new \DateTimeImmutable())->setTimestamp($GLOBALS['EXEC_TIME'])
            )
        );
        $this->context->setAspect('visibility', new VisibilityAspect(true, true, false, true));
    }
}
