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

/**
 * Factory class registered in ServiceProvider to create a ViewHelperResolver.
 *
 * Note fluid is a failsafe mode aware extensions since its used in the install
 * tool. We thus need a ServiceProvider.php to correctly instantiate / inject
 * these class objects. This would be simple, but ViewHelperResolver has state,
 * and the failsafe mode expects injected services to not have state.
 *
 * So, to retrieve a ViewHelperResolver instance, an instance of this factory
 * is retrieved instead (which is a singleton), which then creates a 'fresh'
 * instance of the ViewHelperResolver each time create() is called.
 *
 * @internal May change / vanish any time
 */
final class ViewHelperResolverFactory implements ViewHelperResolverFactoryInterface
{
    public function __construct(private readonly ContainerInterface $container) {}

    public function create(): ViewHelperResolver
    {
        $namespaces = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces'] ?? [];
        return new ViewHelperResolver($this->container, $namespaces);
    }
}
