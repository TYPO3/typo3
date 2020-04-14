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

namespace TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception;

/**
 * Class TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchMethodException
 */
class NoSuchMethodException extends \Exception
{
    /**
     * @param string $className
     * @param string $methodName
     * @return NoSuchMethodException
     */
    public static function create(string $className, string $methodName): NoSuchMethodException
    {
        return new self(
            'Method ' . $className . '::' . $methodName . ' does not exist',
            1547373924
        );
    }
}
