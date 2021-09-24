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

namespace TYPO3\CMS\Lowlevel\ConfigurationModuleProvider;

use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Utility\ArrayUtility;

class BackendRoutesProvider extends AbstractProvider
{
    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function getConfiguration(): array
    {
        $configurationArray = [];
        foreach ($this->router->getRoutes() as $identifier => $route) {
            $configurationArray[$identifier] = [
                'path' => $route->getPath(),
                'options' => $route->getOptions(),
                'methods' => implode(',', $route->getMethods()) ?: '*',
            ];
        }
        ArrayUtility::naturalKeySortRecursive($configurationArray);
        return $configurationArray;
    }
}
