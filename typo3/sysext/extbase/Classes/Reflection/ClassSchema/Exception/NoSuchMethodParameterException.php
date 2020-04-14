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
 * Class TYPO3\CMS\Extbase\Reflection\ClassSchema\Exception\NoSuchMethodParameterException
 */
class NoSuchMethodParameterException extends \Exception
{
    /**
     * @param string $className
     * @param string $methodName
     * @param string $parameterName
     * @return NoSuchMethodParameterException
     */
    public static function createForParameterName(string $className, string $methodName, $parameterName): NoSuchMethodParameterException
    {
        return new self(
            'Method parameter ' . $className . '::' . $methodName . '($' . $parameterName . ') does not exist',
            1547375654
        );
    }

    /**
     * @param string $className
     * @param string $methodName
     * @param int $position
     * @return NoSuchMethodParameterException
     */
    public static function createForParameterPosition(string $className, string $methodName, int $position): NoSuchMethodParameterException
    {
        return new self(
            'Method parameter #' . $position . ' of method ' . $className . '::' . $methodName . ' does not exist',
            1547459332
        );
    }
}
