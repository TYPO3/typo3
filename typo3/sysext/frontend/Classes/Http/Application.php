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

namespace TYPO3\CMS\Frontend\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\AbstractApplication;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;

/**
 * Entry point for the TYPO3 Frontend
 */
class Application extends AbstractApplication
{
    public function __construct(
        RequestHandlerInterface $requestHandler,
        protected readonly ConfigurationManager $configurationManager,
        protected readonly Context $context,
        protected readonly BackendEntryPointResolver $backendEntryPointResolver
    ) {
        $this->requestHandler = $requestHandler;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!Bootstrap::checkIfEssentialConfigurationExists($this->configurationManager)) {
            return $this->installToolRedirect($request);
        }

        // Create new request object having applicationType "I am a frontend request" attribute.
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $this->initializeContext();
        return parent::handle($request);
    }

    /**
     * Create a PSR-7 Response that redirects to the install tool
     */
    protected function installToolRedirect(ServerRequestInterface $request): ResponseInterface
    {
        return new RedirectResponse($this->backendEntryPointResolver->getPathFromRequest($request) . 'install.php', 302);
    }

    /**
     * Initializes the Context used for accessing data and finding out the current state of the application
     */
    protected function initializeContext(): void
    {
        $this->context->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@' . $GLOBALS['EXEC_TIME'])));
        $this->context->setAspect('visibility', new VisibilityAspect());
        $this->context->setAspect('workspace', new WorkspaceAspect(0));
        $this->context->setAspect('backend.user', new UserAspect(null));
        $this->context->setAspect('frontend.user', new UserAspect(null, [0, -1]));
    }
}
