<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

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
class EmConfUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function constructEmConfAddsCommentBlock()
    {
        $extensionData = [
            'extKey' => 'key',
            'EM_CONF' => [],
        ];
        $fixture = new \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility();
        $emConf = $fixture->constructEmConf($extensionData);
        $this->assertContains('Extension Manager/Repository config file for ext', $emConf);
    }

    /**
     * @test
     */
    public function fixEmConfTransfersOldConflictSettingToNewFormatWithSingleConflictingExtension()
    {
        $input = [
            'title' => 'a title',
            'conflicts' => 'foo',
        ];
        $expected = [
            'title' => 'a title',
            'constraints' => [
                'depends' => [],
                'conflicts' => [
                    'foo' => '',
                ],
                'suggests' => [],
            ],
        ];
        $fixture = new \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility();
        $this->assertEquals($expected, $fixture->fixEmConf($input));
    }

    /**
     * @test
     */
    public function fixEmConfTransfersOldConflictSettingToNewFormatWithTwoConflictingExtensions()
    {
        $input = [
            'title' => 'a title',
            'conflicts' => 'foo,bar',
        ];
        $expected = [
            'title' => 'a title',
            'constraints' => [
                'depends' => [],
                'conflicts' => [
                    'foo' => '',
                    'bar' => '',
                ],
                'suggests' => [],
            ],
        ];
        $fixture = new \TYPO3\CMS\Extensionmanager\Utility\EmConfUtility();
        $this->assertEquals($expected, $fixture->fixEmConf($input));
    }
}
