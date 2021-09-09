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

namespace TYPO3\CMS\Extbase\Object;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Interface for the TYPO3 Object Manager
 *
 * @deprecated since v11, will be removed in v12. Use symfony DI and GeneralUtility::makeInstance() instead.
 *              See TYPO3 explained documentation for more information.
 * @template T
 */
interface ObjectManagerInterface extends SingletonInterface
{
    /**
     * Returns a fresh or existing instance of the object specified by $objectName.
     *
     * @param string|class-string<T> $objectName The name of the object to return an instance of
     * @param array ...$constructorArguments
     * @return object&T The object instance
     * @deprecated since TYPO3 10.4, will be removed in version 12.0
     */
    public function get(string $objectName, ...$constructorArguments): object;

    /**
     * Create an instance of $className without calling its constructor
     *
     * @param string|class-string<T> $className
     * @return object&T
     */
    public function getEmptyObject(string $className): object;
}
