<?php
namespace TYPO3\CMS\Saltedpasswords\Tests\Unit\Evaluation;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\Evaluation\Evaluator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class EvaluatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function passwordIsTurnedIntoSaltedString()
    {
        $isSet = null;
        $originalPassword = 'password';
        $saltedPassword = (new Evaluator())->evaluateFieldValue($originalPassword, '', $isSet);
        $isSalted = substr($saltedPassword, 0, 1) === '$';
        $this->assertTrue($isSet);
        $this->assertNotEquals($originalPassword, $saltedPassword);
        $this->assertTrue($isSalted);
    }

    /**
     * @test
     */
    public function md5HashIsUpdatedToTemporarySaltedString()
    {
        $isSet = null;
        $originalPassword = '5f4dcc3b5aa765d61d8327deb882cf99';
        $saltedPassword = (new Evaluator())->evaluateFieldValue($originalPassword, '', $isSet);
        $this->assertTrue($isSet);
        $this->assertNotEquals($originalPassword, $saltedPassword);
        $this->assertTrue(GeneralUtility::isFirstPartOfStr($saltedPassword, 'M$'));
    }

    /**
     * @test
     */
    public function temporarySaltedStringIsNotTouched()
    {
        $isSet = null;
        $originalPassword = 'M$P$CibIRipvLfaPlaaeH8ifu9g21BrPjp.';
        $saltedPassword = (new Evaluator())->evaluateFieldValue($originalPassword, '', $isSet);
        $this->assertNull($isSet);
        $this->assertSame($originalPassword, $saltedPassword);
    }
}
