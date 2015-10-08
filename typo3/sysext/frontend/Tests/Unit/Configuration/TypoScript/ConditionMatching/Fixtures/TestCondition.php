<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Configuration\TypoScript\ConditionMatching\Fixtures;

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

/**
 * Fixture for custom conditions
 */
class TestCondition extends \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractCondition
{
    /**
     * Test matcher tests input parameters.
     *
     * @param array $conditionParameters
     * @throws TestConditionException
     * @return bool
     */
    public function matchCondition(array $conditionParameters)
    {
        // Throw an exception if everything is fine, this exception is *expected* in the according unit test
        if ($conditionParameters[0] === '= 7' && $conditionParameters[1] === '!= 6') {
            throw new TestConditionException('All Ok', 1411581139);
        }
    }
}
