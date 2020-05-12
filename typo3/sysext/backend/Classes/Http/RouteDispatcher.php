<?php
namespace TYPO3\CMS\Backend\Http;

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
use TYPO3\CMS\Backend\Routing\Exception\InvalidRequestTokenException;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\Dispatcher;
use TYPO3\CMS\Core\Http\Security\ReferrerEnforcer;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Dispatcher which resolves a route to call a controller and method (but also a callable)
 */
class RouteDispatcher extends Dispatcher
{
    /**
     * Main method to resolve the route and checks the target of the route, and tries to call it.
     *
     * @param ServerRequestInterface $request the current server request
     * @param ResponseInterface $response the prepared response @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     * @return ResponseInterface the filled response by the callable / controller/action
     * @throws InvalidRequestTokenException if the route was not found
     * @throws \InvalidArgumentException if the defined target for the route is invalid
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response = null): ResponseInterface
    {
        $router = GeneralUtility::makeInstance(Router::class);
        $route = $router->matchRequest($request);
        $request = $request->withAttribute('route', $route);
        $request = $request->withAttribute('target', $route->getOption('target'));

        $enforceReferrerResponse = $this->enforceReferrer($request);
        if ($enforceReferrerResponse instanceof ResponseInterface) {
            return $enforceReferrerResponse;
        }
        if (!$this->isValidRequest($request)) {
            throw new InvalidRequestTokenException('Invalid request for route "' . $route->getPath() . '"', 1425389455);
        }

        if ($route->getOption('module')) {
            $this->addAndValidateModuleConfiguration($request, $route);
        }
        $targetIdentifier = $route->getOption('target');
        $target = $this->getCallableFromTarget($targetIdentifier);
        $arguments = [$request];

        // @deprecated Test if target accepts one (ok) or two (deprecated) arguments
        $scanForResponse = !GeneralUtility::makeInstance(Features::class)
            ->isFeatureEnabled('simplifiedControllerActionDispatching');
        if ($scanForResponse) {
            if (is_array($targetIdentifier)) {
                $controllerActionName = implode('::', $targetIdentifier);
                $targetReflection = new \ReflectionMethod($controllerActionName);
            } elseif (is_string($targetIdentifier) && strpos($targetIdentifier, '::') !== false) {
                $controllerActionName = $targetIdentifier;
                $targetReflection = new \ReflectionMethod($controllerActionName);
            } elseif (is_callable($targetIdentifier)) {
                $controllerActionName = 'closure function';
                $targetReflection = new \ReflectionFunction($targetIdentifier);
            } else {
                $controllerActionName = $targetIdentifier . '::__invoke';
                $targetReflection = new \ReflectionMethod($controllerActionName);
            }
            if ($targetReflection->getNumberOfParameters() >= 2) {
                trigger_error(
                    'Handing over second argument $response to controller action ' . $controllerActionName . '() is deprecated and will be removed in TYPO3 v10.0.',
                    E_USER_DEPRECATED
                );
                $arguments[] = $response;
            }
        }

        return call_user_func_array($target, $arguments);
    }

    /**
     * Wrapper method for static form protection utility
     *
     * @return \TYPO3\CMS\Core\FormProtection\AbstractFormProtection
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
     * @return ResponseInterface|null
     */
    protected function enforceReferrer(ServerRequestInterface $request): ?ResponseInterface
    {
        /** @var Features $features */
        $features = GeneralUtility::makeInstance(Features::class);
        if (!$features->isFeatureEnabled('security.backend.enforceReferrer')) {
            return null;
        }
        /** @var Route $route */
        $route = $request->getAttribute('route');
        $referrerFlags = GeneralUtility::trimExplode(',', $route->getOption('referrer') ?? '', true);
        if (!in_array('required', $referrerFlags, true)) {
            return null;
        }
        /** @var ReferrerEnforcer $referrerEnforcer */
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
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @return bool
     * @see \TYPO3\CMS\Backend\Routing\UriBuilder where the token is generated.
     */
    protected function isValidRequest($request)
    {
        $route = $request->getAttribute('route');
        if ($route->getOption('access') === 'public') {
            return true;
        }
        $token = (string)($request->getParsedBody()['token'] ?? $request->getQueryParams()['token']);
        if ($token) {
            return $this->getFormProtection()->validateToken($token, 'route', $route->getOption('_identifier'));
        }
        // backwards compatibility: check for M and module token params
        // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
        $token = (string)($request->getParsedBody()['moduleToken'] ?? $request->getQueryParams()['moduleToken']);
        return $this->getFormProtection()->validateToken($token, 'moduleCall', $request->getParsedBody()['M'] ?? $request->getQueryParams()['M']);
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
        $id = $request->getQueryParams()['id'] ?? $request->getParsedBody()['id'];
        if (MathUtility::canBeInterpretedAsInteger($id) && $id > 0) {
            $permClause = $backendUserAuthentication->getPagePermsClause(Permission::PAGE_SHOW);
            // Check page access
            if (!is_array(BackendUtility::readPageAccess($id, $permClause))) {
                // Check if page has been deleted
                $deleteField = $GLOBALS['TCA']['pages']['ctrl']['delete'];
                $pageInfo = BackendUtility::getRecord('pages', $id, $deleteField, $permClause ? ' AND ' . $permClause : '', false);
                if (!$pageInfo[$deleteField]) {
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
