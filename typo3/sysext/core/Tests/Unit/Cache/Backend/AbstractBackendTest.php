<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend;

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

use TYPO3\CMS\Core\Tests\Unit\Cache\Backend\Fixtures\ConcreteBackendFixture;

/**
 * Test case
 */
class AbstractBackendTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function theConstructorCallsSetterMethodsForAllSpecifiedOptions()
    {
        // The fixture class implements methods setSomeOption() and getSomeOption()
        $backend = new ConcreteBackendFixture('Testing', ['someOption' => 'someValue']);
        $this->assertSame('someValue', $backend->getSomeOption());
    }
}
