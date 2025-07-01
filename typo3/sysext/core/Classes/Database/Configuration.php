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

namespace TYPO3\CMS\Core\Database;

use Doctrine\DBAL\Configuration as DoctrineConfiguration;
use Psr\Container\ContainerInterface;

final class Configuration extends DoctrineConfiguration
{
    private ?ContainerInterface $container = null;

    public function getContainer(): ContainerInterface
    {
        return $this->container ?? throw new \LogicException('Doctrine database configuration requires a container to be set via `setContainer()`', 1782369693);
    }

    public function setContainer(ContainerInterface $container): self
    {
        $this->container = $container;
        return $this;
    }
}
