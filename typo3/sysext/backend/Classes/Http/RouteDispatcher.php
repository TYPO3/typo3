<?php

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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\InvalidRequestTokenException;
use TYPO3\CMS\Backend\Routing\Exception\MissingRequestTokenException;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\RouteRedirect;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\FormProtection\AbstractFormProtection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\Dispatcher;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Security\ReferrerEnforcer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Dispatcher which resolves a route to call a controller and method (but also a callable)
 */
class RouteDispatcher extends Dispatcher
{
    private UriBuilder $uriBuilder;

    public function __construct(ContainerInterface $container, UriBuilder $uriBuilder)
    {
        parent::__construct($container);
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * Main method checks the target of the route, and tries to call it.
     *
     * @param ServerRequestInterface $request the current server request
     * @return ResponseInterface the filled response by the callable / controller/action
     * @throws InvalidRequestTokenException if the route requested a token, but this token did not match
     * @throws MissingRequestTokenException if the route requested a token, but there was none
     * @throws \InvalidArgumentException if the defined target for the route is invalid
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        /** @var Route $route */
        $route = $request->getAttribute('route');

        $enforceReferrerResponse = $this->enforceReferrer($request, $route);
        if ($enforceReferrerResponse instanceof ResponseInterface) {
            return $enforceReferrerResponse;
        }
        // Ensure that a token exists, and the token is requested, if the route requires a valid token
        $this->assertRequestToken($request, $route);

        if ($route->hasOption('module')) {
            $this->addAndValidateModuleConfiguration($request, $route);

            // This module request (which is usually opened inside the list_frame)
            // has been issued from a toplevel browser window (e.g. a link was opened in a new tab).
            // Redirect to open the module as frame inside the TYPO3 backend layout.
            // HEADS UP: This header will only be available in secure connections (https:// or .localhost TLD)
            if ($request->getHeaderLine('Sec-Fetch-Dest') === 'document') {
                return new RedirectResponse(
                    $this->uriBuilder->buildUriWithRedirect(
                        'main',
                        [],
                        RouteRedirect::createFromRoute($route, $request->getQueryParams())
                    )
                );
            }
        }
        $targetIdentifier = $route->getOption('target');
        $target = $this->getCallableFromTarget($targetIdentifier);
        $arguments = [$request];
        return $target(...$arguments);
    }

    /**
     * Wrapper method for static form protection utility
     *
     * @return AbstractFormProtection
     */
    protected function getFormProtection()
    {
        return FormProtectionFactory::get();
    }

    /**
     * Evaluates HTTP `Referer` header (which is denied by client to be a custom
     * value) - attempts to ensure the value is given using a HTML client refresh.
     * see: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referer
     *
     * @param ServerRequestInterface $request
     * @param Route $route
     * @return ResponseInterface|null
     */
    protected function enforceReferrer(ServerRequestInterface $request, Route $route): ?ResponseInterface
    {
        $features = GeneralUtility::makeInstance(Features::class);
        if (!$features->isFeatureEnabled('security.backend.enforceReferrer')) {
            return null;
        }
        $referrerFlags = GeneralUtility::trimExplode(',', $route->getOption('referrer') ?? '', true);
        if (!in_array('required', $referrerFlags, true)) {
            return null;
        }
        $referrerEnforcer = GeneralUtility::makeInstance(ReferrerEnforcer::class, $request);
        return $referrerEnforcer->handle([
            'flags' => $referrerFlags,
            'subject' => $route->getPath(),
        ]);
    }

    /**
     * Checks if the request token is valid. This is checked to see if the route is really
     * created by the same instance. Should be called for all routes in the backend except
     * for the ones that don't require a login.
     *
     * @param ServerRequestInterface $request
     * @param Route $route
     * @see UriBuilder where the token is generated.
     */
    protected function assertRequestToken(ServerRequestInterface $request, Route $route): void
    {
        if ($route->getOption('access') === 'public') {
            return;
        }
        $token = (string)($request->getParsedBody()['token'] ?? $request->getQueryParams()['token'] ?? '');
        if (empty($token)) {
            throw new MissingRequestTokenException(
                sprintf('Invalid request for route "%s"', $route->getPath()),
                1627905246
            );
        }
        if (!$this->getFormProtection()->validateToken($token, 'route', $route->getOption('_identifier'))) {
            throw new InvalidRequestTokenException(
                sprintf('Invalid request for route "%s"', $route->getPath()),
                1425389455
            );
        }
    }

    /**
     * Adds configuration for a module and checks module permissions for the
     * current user.
     *
     * @param ServerRequestInterface $request
     * @param Route $route
     * @throws \RuntimeException
     */
    protected function addAndValidateModuleConfiguration(ServerRequestInterface $request, Route $route)
    {
        $moduleName = $route->getOption('moduleName');
        $moduleConfiguration = $this->getModuleConfiguration($moduleName);
        $route->setOption('moduleConfiguration', $moduleConfiguration);

        $backendUserAuthentication = $GLOBALS['BE_USER'];

        // Check permissions and exit if the user has no permission for entry
        $backendUserAuthentication->modAccess($moduleConfiguration);
        // '' for "no value found at all" to guarantee that the following if condition fails.
        $id = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? '';
        if (MathUtility::canBeInterpretedAsInteger($id) && $id > 0) {
            $permClause = $backendUserAuthentication->getPagePermsClause(Permission::PAGE_SHOW);
            // Check page access
            if (!is_array(BackendUtility::readPageAccess($id, $permClause))) {
                // Check if page has been deleted
                $deleteField = $GLOBALS['TCA']['pages']['ctrl']['delete'];
                $pageInfo = BackendUtility::getRecord('pages', $id, $deleteField, $permClause ? ' AND ' . $permClause : '', false);
                if (!($pageInfo[$deleteField] ?? false)) {
                    throw new \RuntimeException('You don\'t have access to this page', 1289917924);
                }
            }
        }
    }

    /**
     * Returns the module configuration which is provided during module registration
     *
     * @param string $moduleName
     * @return array
     * @throws \RuntimeException
     */
    protected function getModuleConfiguration($moduleName)
    {
        if (!isset($GLOBALS['TBE_MODULES']['_configuration'][$moduleName])) {
            throw new \RuntimeException('Module ' . $moduleName . ' is not configured.', 1289918325);
        }
        return $GLOBALS['TBE_MODULES']['_configuration'][$moduleName];
    }
}
