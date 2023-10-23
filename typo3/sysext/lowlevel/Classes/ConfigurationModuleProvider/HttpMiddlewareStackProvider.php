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

use Psr\Container\ContainerInterface;

class HttpMiddlewareStackProvider extends AbstractProvider
{
    public function __construct(protected readonly ContainerInterface $container) {}

    public function getConfiguration(): array
    {
        $configurationArray = [];
        foreach (['frontend', 'backend'] as $stackName) {
            // reversing the array allows the admin to read the stack from top to bottom
            $configurationArray[$stackName] = array_reverse((array)$this->container->get($stackName . '.middlewares'));
        }
        $configurationArray['raw'] = $this->container->get('middlewares');
        return $configurationArray;
    }
}
