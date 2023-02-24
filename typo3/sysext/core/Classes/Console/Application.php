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

namespace TYPO3\CMS\Core\Console;

use TYPO3\CMS\Core\Core\Environment;

/**
 * Extend the Application class to be able to provide a custom version string
 */
class Application extends \Symfony\Component\Console\Application
{
    /**
     * Create a custom version of getLongVersion
     */
    public function getLongVersion(): string
    {
        return sprintf(
            '%1$s <info>%2$s</info> (<comment>Application Context:</comment> <info>%3$s</info>) - PHP <info>%4$s</info>',
            $this->getName(),
            $this->getVersion(),
            Environment::getContext(),
            PHP_VERSION
        );
    }
}
