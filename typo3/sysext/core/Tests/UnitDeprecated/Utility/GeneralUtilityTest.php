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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Utility;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class GeneralUtilityTest extends UnitTestCase
{
    #[Test]
    public function hmacReturnsHashOfProperLength(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
        $hmac = GeneralUtility::hmac('message');
        self::assertTrue(!empty($hmac) && is_string($hmac));
        self::assertEquals(strlen($hmac), 40);
    }

    #[Test]
    public function hmacReturnsEqualHashesForEqualInput(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
        $msg0 = 'message';
        $msg1 = 'message';
        self::assertEquals(GeneralUtility::hmac($msg0), GeneralUtility::hmac($msg1));
    }

    #[Test]
    public function hmacReturnsNoEqualHashesForNonEqualInput(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
        $msg0 = 'message0';
        $msg1 = 'message1';
        self::assertNotEquals(GeneralUtility::hmac($msg0), GeneralUtility::hmac($msg1));
    }

}
