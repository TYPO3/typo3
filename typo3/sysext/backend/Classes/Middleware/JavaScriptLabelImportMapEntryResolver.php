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

namespace TYPO3\CMS\Backend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Page\Event\ResolveVirtualJavaScriptImportEvent;

/**
 * @internal
 */
final readonly class JavaScriptLabelImportMapEntryResolver implements MiddlewareInterface
{
    public function __construct(
        private ListenerProvider $listenerProvider,
        private UriBuilder $uriBuilder,
        #[Autowire(expression: 'service("package-dependent-cache-identifier").withAdditionalHashedIdentifier("JavaScriptLanguageDomain").toString()')]
        private string $javaScriptLanguageDomainCacheIdentifier,
    ) {}

    /**
     * Adds HTTP headers defined in $GLOBALS['TYPO3_CONF_VARS']['BE']['HTTP']['Response']['Headers']
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->listenerProvider->addListener(
            ResolveVirtualJavaScriptImportEvent::class,
            self::class,
            'resolveVirtualLabelImport'
        );

        return $handler->handle($request);
    }

    public function resolveVirtualLabelImport(ResolveVirtualJavaScriptImportEvent $event): void
    {
        if ($event->resolution === null && $event->virtualName === 'labels/') {
            $path = (string)$this->uriBuilder->buildUriFromRoute('language_domain', [
                'locale' => $GLOBALS['LANG']->getLocale()?->getName() ?? 'en',
                'cacheBustInfix' => $this->javaScriptLanguageDomainCacheIdentifier,
                'domain' => '__DOMAIN__',
            ]);

            // domain identifier will be aded via JavaScript importmap prefix handling, strip it to generate
            // a base identifier
            $path = str_replace('__DOMAIN__', '', $path);

            $event->resolution = $path;
        }
    }
}
