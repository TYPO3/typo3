<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Utility;

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
 * Test case
 */
class DebuggerUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Utility\DebuggerUtility
     */
    protected $debugger;

    protected function setUp()
    {
        $this->debugger = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Utility\DebuggerUtility::class, array('dummy'));
    }

    /**
     * @test
     */
    public function debuggerRewindsInstancesOfIterator()
    {
        /** @var $objectStorage \TYPO3\CMS\Extbase\Persistence\ObjectStorage */
        $objectStorage = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->setMethods(array('dummy'))
            ->getMock();
        for ($i = 0; $i < 5; $i++) {
            $obj = new \stdClass();
            $obj->property = $i;
            $objectStorage->attach($obj);
        }
        $this->debugger->var_dump($objectStorage, null, 8, true, false, true);
        $this->assertTrue($objectStorage->valid());
    }

    /**
     * @test
     */
    public function varDumpShowsPropertiesOfStdClassObjects()
    {
        $testObject = new \stdClass();
        $testObject->foo = 'bar';
        $result = $this->debugger->var_dump($testObject, null, 8, true, false, true);
        $this->assertRegExp('/foo.*bar/', $result);
    }
}
