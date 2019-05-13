<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\IO;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\PharStreamWrapper\Assertable;
use TYPO3\PharStreamWrapper\Exception;
use TYPO3\PharStreamWrapper\Manager;
use TYPO3\PharStreamWrapper\Resolver\PharInvocationResolver;

class PharStreamWrapperInterceptor implements Assertable
{
    /**
     * Asserts the given path of a Phar file is located in a valid path
     * in typo3conf/ext/* of the local TYPO3 installation.
     *
     * @param string $path
     * @param string $command
     * @return bool
     * @throws Exception
     */
    public function assert(string $path, string $command): bool
    {
        if ($this->isAllowed($path) === true) {
            return true;
        }
        throw new Exception(
            sprintf('Executing %s is denied', $path),
            1530103998
        );
    }

    /**
     * @param string $path
     * @return bool
     */
    protected function isAllowed(string $path): bool
    {
        $invocation = Manager::instance()->resolve($path, PharInvocationResolver::RESOLVE_ALIAS);
        if ($invocation === null) {
            return false;
        }
        $baseName = $invocation->getBaseName();
        return GeneralUtility::validPathStr($baseName)
            && GeneralUtility::isFirstPartOfStr($baseName, Environment::getExtensionsPath());
    }
}
