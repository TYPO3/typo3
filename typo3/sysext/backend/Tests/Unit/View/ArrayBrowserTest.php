<?php

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

namespace TYPO3\CMS\Backend\Tests\Unit\View;

use TYPO3\CMS\Backend\View\ArrayBrowser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ArrayBrowserTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    ///////////////////////////////
    // Tests concerning depthKeys
    ///////////////////////////////
    /**
     * @test
     */
    public function depthKeysWithEmptyFirstParameterAddsNothing()
    {
        $subject = new ArrayBrowser();
        self::assertEquals([], $subject->depthKeys([], []));
    }

    /**
     * @test
     */
    public function depthKeysWithNumericKeyAddsOneNumberForKeyFromFirstArray()
    {
        $subject = new ArrayBrowser();
        self::assertEquals([0 => 1], $subject->depthKeys(['foo'], []));
    }
}
