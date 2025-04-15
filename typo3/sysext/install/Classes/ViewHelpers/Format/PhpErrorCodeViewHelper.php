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

namespace TYPO3\CMS\Install\ViewHelpers\Format;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to transform a PHP error code to readable text
 *
 * ```
 *   <i:format.phpErrorCode phpErrorCode="{someErrorCodeIntegerValue}" />
 * ```
 *
 * @internal
 */
final class PhpErrorCodeViewHelper extends AbstractViewHelper
{
    protected static array $levelNames = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
        // @todo: Remove 2048 (deprecated E_STRICT) in v14, as this value is no longer used by PHP itself
        //        and only kept here here because possible custom PHP extensions may still use it.
        //        See https://wiki.php.net/rfc/deprecations_php_8_4#remove_e_strict_error_level_and_deprecate_e_strict_constant
        2048 /* deprecated E_STRICT */ => 'PHP Runtime Notice',
    ];

    public function initializeArguments(): void
    {
        $this->registerArgument('phpErrorCode', 'int', '', true);
    }

    /**
     * Render a readable string for PHP error code.
     */
    public function render(): string
    {
        $phpErrorCode = (int)$this->arguments['phpErrorCode'];
        $levels = [];
        if (($phpErrorCode & E_ALL) == E_ALL) {
            $levels[] = 'E_ALL';
            $phpErrorCode &= ~E_ALL;
        }
        foreach (self::$levelNames as $level => $name) {
            if (($phpErrorCode & $level) == $level) {
                $levels[] = $name;
            }
        }
        $output = '';
        if (!empty($levels)) {
            $output = implode(' | ', $levels);
        }
        return $output;
    }
}
