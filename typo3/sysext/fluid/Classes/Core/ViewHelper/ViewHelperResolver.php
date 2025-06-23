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

namespace TYPO3\CMS\Fluid\Core\ViewHelper;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DependencyInjection\FailsafeContainer;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperCollection;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolverDelegateInterface;

/**
 * Class whose purpose is dedicated to resolving classes which
 * can be used as ViewHelpers and ExpressionNodes in Fluid.
 *
 * This CMS-specific version of the ViewHelperResolver works
 * almost exactly like the one from Fluid itself, with the main
 * differences being that this one supports a legacy mode flag
 * which when toggled on makes the Fluid parser behave exactly
 * like it did in the legacy CMS Fluid package.
 *
 * In addition to modifying the behavior or the parser when
 * legacy mode is requested, this ViewHelperResolver is also
 * made capable of "mixing" two different ViewHelper namespaces
 * to effectively create aliases for the Fluid core ViewHelpers
 * to be loaded in the TYPO3\CMS\ViewHelpers scope as well.
 *
 * Default ViewHelper namespaces are read TYPO3 configuration at:
 *
 * $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']
 *
 * Extending this array allows third party ViewHelper providers
 * to automatically add or extend namespaces which then become
 * available in every Fluid template file without having to
 * register the namespace.
 *
 * @internal This is a helper class which is not considered part of TYPO3's Public API.
 */
class ViewHelperResolver extends \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolver
{
    protected ContainerInterface $container;

    /**
     * ViewHelperResolver constructor
     *
     * Loads namespaces defined in global TYPO3 configuration. Overlays `f:`
     * with `f:debug:` when Fluid debugging is enabled in the admin panel,
     * causing debugging-specific ViewHelpers to be resolved in that case.
     *
     * @internal constructor, use `ViewHelperResolverFactory->create()` instead
     */
    public function __construct(ContainerInterface $container, array $namespaces)
    {
        $this->container = $container;
        $this->namespaces = $namespaces;
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
            && $this->getBackendUser() instanceof BackendUserAuthentication
        ) {
            if ($this->getBackendUser()->uc['AdminPanel']['preview_showFluidDebug'] ?? false) {
                $this->namespaces['f'][] = 'TYPO3\\CMS\\Fluid\\ViewHelpers\\Debug';
            }
        }
    }

    /**
     * @param string $viewHelperClassName
     */
    public function createViewHelperInstanceFromClassName($viewHelperClassName): ViewHelperInterface
    {
        if ($this->container instanceof FailsafeContainer) {
            // The install tool creates VH instances using makeInstance to not rely on symfony DI here,
            // otherwise we'd have to have all install-tool used ones in ServiceProvider.php. However,
            // none of the install tool used VH's use injection.
            /** @var ViewHelperInterface $viewHelperInstance */
            $viewHelperInstance = GeneralUtility::makeInstance($viewHelperClassName);
            return $viewHelperInstance;
        }

        if ($this->container->has($viewHelperClassName)) {
            /** @var ViewHelperInterface $viewHelperInstance */
            $viewHelperInstance = $this->container->get($viewHelperClassName);
        } else {
            /** @var ViewHelperInterface $viewHelperInstance */
            $viewHelperInstance = new $viewHelperClassName();
        }
        return $viewHelperInstance;
    }

    /**
     * Creates a ViewHelperResolver delegate object based on a ViewHelper
     * namespace string. The logic here is: If a ViewHelper namespace is
     * defined with an existing class name, that class will be responsible
     * for resolving the ViewHelpers in that namespace (= it is a
     * ViewHelperResolver  delegate). If no such class exists, the default
     * ViewHelper resolving is used (implemented in ViewHelperCollection).
     * The default implementation by Fluid is extended to support dependency
     * injection in ViewHelperResolver delegates.
     */
    public function createResolverDelegateInstanceFromClassName(string $delegateClassName): ViewHelperResolverDelegateInterface
    {
        if ($this->container instanceof FailsafeContainer && class_exists($delegateClassName)) {
            // The install tool creates resolver instances using makeInstance
            // to not rely on symfony DI. Currently the install tool doesn't
            // use any custom resolvers, however this might change in the future.
            return GeneralUtility::makeInstance($delegateClassName);
        }
        if ($this->container->has($delegateClassName)) {
            return $this->container->get($delegateClassName);
        }
        if (class_exists($delegateClassName)) {
            return new $delegateClassName();
        }
        // Fall back to default ViewHelper resolving logic
        return new ViewHelperCollection($delegateClassName);
    }

    protected function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
