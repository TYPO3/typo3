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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher\Fixtures;

/**
 * Fixture file
 */
class MethodCallArgumentValueMatcherFixture
{
    public function aMethod(): void
    {
        // Match: confirmMsgString() uses 'argOld' as fixed input
        $foo->confirmMsgString('argOld');

        // Match: confirmMsgString() uses 'argOld' as fixed input, more args
        $foo->confirmMsgString('argOld', 'arg2', 'arg3', 'arg4');

        // Match: confirmMsgInt() uses '13' as fixed input
        $foo->confirmMsgInt(13);

        // Match: confirmMsgInt() uses '13' as fixed input, more args
        $foo->confirmMsgInt(13, 'arg2', 'arg3', 'arg4');

        // Match: confirmMsgFloat() uses '13.37' as fixed input
        $foo->confirmMsgFloat(13.37);

        // Match: confirmMsgFloat() uses '13.37' as fixed input, more args
        $foo->confirmMsgFloat(13.37, 'arg2', 'arg3', 'arg4');

        // ------------------------------

        // STRONG Match: confirmMsgString() uses 'argOld' as fixed input
        \TYPO3\CMS\GeneralUtility::confirmMsgString('argOld');

        // STRONG Match: confirmMsgString() uses 'argOld' as fixed input, more args
        \TYPO3\CMS\GeneralUtility::confirmMsgString('argOld', 'arg2', 'arg3', 'arg4');

        // STRONG Match: confirmMsgInt() uses '13' as fixed input
        \TYPO3\CMS\GeneralUtility::confirmMsgInt(13);

        // STRONG Match: confirmMsgInt() uses '13' as fixed input, more args
        \TYPO3\CMS\GeneralUtility::confirmMsgInt(13, 'arg2', 'arg3', 'arg4');

        // STRONG Match: confirmMsgFloat() uses '13.37' as fixed input
        \TYPO3\CMS\GeneralUtility::confirmMsgFloat(13.37);

        // STRONG Match: confirmMsgFloat() uses '13.37' as fixed input, more args
        \TYPO3\CMS\GeneralUtility::confirmMsgFloat(13.37, 'arg2', 'arg3', 'arg4');

        // STRONG Multi-Match: confirmMsgMultiple() uses 'string', '42', '13.37' as fixed input
        \TYPO3\CMS\GeneralUtility::confirmMsgMultiple('string', 42, 13.37, 'anything goes');

        // ------------------------------
        // No Match: confirmMsg() uses 'argOld' as variable input, not a scalar
        $argument = 'argOld';
        $foo->confirmMsgString($argument);

        // No Match: Variable input from getter (not scalar)
        $foo->confirmMsgString($this->getArgumentValue('argOld'));

        // No match: Other fixed input
        $foo->confirmMsgString('argNew');

        // No match: Other fixed input, more args
        $foo->confirmMsgString('argNew', 'arg2', 'arg3', 'arg4');

        // No match: Other variable input
        $argument = 'argNew';
        $foo->confirmMsgString($argument);

        // No match: Other variable input from getter
        $foo->confirmMsgString($this->getArgumentValue('argNew'));

        // No Match: confirmMsgInt() uses '13' as fixed input, but not int
        $foo->confirmMsgInt('13');

        // No Match: confirmMsgInt() uses '13' as fixed input, but not int, more args
        $foo->confirmMsgInt('13', 'arg2', 'arg3', 'arg4');

        // No Match: confirmMsgFloat() uses '13.37' as fixed input, but not float
        $foo->confirmMsgFloat('13.37');

        // Not Match: confirmMsgFloat() uses '13.37' as fixed input, but not float, more args
        $foo->confirmMsgfloat('13.37', 'arg2', 'arg3', 'arg4');

        // Not STRONG Match: confirmMsgString() uses 'argOld' as fixed input
        \TYPO3\GeneralUtility::confirmMsgString('argOld');

        // Not STRONG Match: confirmMsgString() uses 'argOld' as fixed input, more args
        \TYPO3\GeneralUtility::confirmMsgString('argOld', 'arg2', 'arg3', 'arg4');

        // Not STRONG Match: confirmMsgInt() uses '13' as fixed input
        \TYPO3\GeneralUtility::confirmMsgInt(13);

        // Not STRONG Match: confirmMsgInt() uses '13' as fixed input, more args
        \TYPO3\GeneralUtility::confirmMsgInt(13, 'arg2', 'arg3', 'arg4');

        // Not STRONG Match: confirmMsgFloat() uses '13.37' as fixed input
        \TYPO3\GeneralUtility::confirmMsgFloat(13.37);

        // Not STRONG Match: confirmMsgFloat() uses '13.37' as fixed input, more args
        \TYPO3\GeneralUtility::confirmMsgFloat(13.37, 'arg2', 'arg3', 'arg4');

        // Not STRONG Multi-Match: confirmMsgMultiple() does not use 'string', '99', '12.34' as fixed input [but parts!]
        \TYPO3\CMS\GeneralUtility::confirmMsgMultipleNotAllMatched('string', 99, 9999);
    }

    public function getArgumentValue($string)
    {
        return $string;
    }
}
