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
use TYPO3\CMS\Core\DependencyInjection\FailsafeContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface;

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
 * Default ViewHelper namespaces are read from the extension-level
 * configuration file "Configuration/Fluid/Namespaces.php".
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
     * @internal constructor, use `ViewHelperResolverFactory->create()` instead
     */
    public function __construct(ContainerInterface $container, array $namespaces, array $resolverDelegates = [])
    {
        $this->container = $container;
        $this->namespaces = $namespaces;
        $this->resolverDelegates = $resolverDelegates;
    }

    /**
     * @param string $viewHelperClassName
     */
    public function createViewHelperInstanceFromClassName($viewHelperClassName): ViewHelperInterface
    {
        if ($this->container instanceof FailsafeContainer) {
            // Install tool: makeInstance() resolves via the FailsafeContainer when the VH is
            // registered there, else via `new`. VHs with required constructor arguments used by
            // install-tool templates must be wired in install/Classes/ServiceProvider.php.
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
}
