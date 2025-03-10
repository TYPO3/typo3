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

namespace TYPO3\CMS\Beuser\ViewHelpers;

use TYPO3\CMS\Beuser\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to get a value from an array by given key.
 *
 * ```
 *   <f:render
 *       partial="Permission/Ownername"
 *       arguments="{
 *           username: '{beuser:arrayElement(array:beUsers, key:data.row.perms_userid, subKey:\'username\')}'
 *       }"
 *   />
 * ```
 *
 * @internal
 */
final class ArrayElementViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('array', 'array', 'Array to search in', true);
        $this->registerArgument('key', 'string', 'Key to return its value', true);
        $this->registerArgument('subKey', 'string', 'If result of key access is an array, subkey can be used to fetch an element from this again', false, '');
    }

    /**
     * Return array element by key. Accessed values must be scalar (string, int, float or double)
     *
     * @throws Exception
     */
    public function render(): string
    {
        $array = $this->arguments['array'];
        $key = $this->arguments['key'];
        $subKey = $this->arguments['subKey'];
        $result = '';
        if (isset($array[$key])) {
            $result = $array[$key];
            if (is_array($result) && $subKey && isset($result[$subKey])) {
                $result = $result[$subKey];
            }
        }
        if (!is_scalar($result)) {
            throw new Exception('Only scalar return values (string, int, float or double) are supported.', 1382284105);
        }
        return (string)$result;
    }
}
