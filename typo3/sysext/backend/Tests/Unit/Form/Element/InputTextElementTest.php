<?php
namespace typo3\sysext\backend\Tests\Unit\Form\Element;

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

use TYPO3\CMS\Backend\Form\Element\InputTextElement;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class InputTextElementTest extends UnitTestCase
{
    /**
     * @var string Selected timezone backup
     */
    protected $timezoneBackup = '';

    /**
     * We're fiddling with hard timestamps in the tests, but time methods in
     * the system under test do use timezone settings. Therefore we backup the
     * current timezone setting, set it to UTC explicitly and reconstitute it
     * again in tearDown()
     */
    protected function setUp()
    {
        $this->timezoneBackup = date_default_timezone_get();
    }

    /**
     * Tear down
     */
    protected function tearDown()
    {
        date_default_timezone_set($this->timezoneBackup);
        parent::tearDown();
    }

    /**
     * Data provider for renderAppliesCorrectTimestampConversion
     *
     * @return array
     */
    public function renderAppliesCorrectTimestampConversionDataProvider()
    {
        // Three elements: input (UTC), timezone of output, expected output
        return [
            // German standard time (without DST) is one hour ahead of UTC
            'date in 2016 in German timezone' => [
                1457103519, 'Europe/Berlin', 1457103519 + 3600
            ],
            'date in 1969 in German timezone' => [
                -7200, 'Europe/Berlin', -3600
            ],
            // Los Angeles is 8 hours behind UTC
            'date in 2016 in Los Angeles timezone' => [
                1457103519, 'America/Los_Angeles', 1457103519 - 28800
            ],
            'date in UTC' => [
                1457103519, 'UTC', 1457103519
            ]
        ];
    }

    /**
     * @test
     * @dataProvider renderAppliesCorrectTimestampConversionDataProvider
     * @param int $input
     * @param string $serverTimezone
     * @param int $expectedOutput
     */
    public function renderAppliesCorrectTimestampConversion($input, $serverTimezone, $expectedOutput)
    {
        date_default_timezone_set($serverTimezone);
        $data = [
            'parameterArray' => [
                'tableName' => 'table_foo',
                'fieldName' => 'field_bar',
                'fieldConf' => [
                    'config' => [
                        'type' => 'input',
                        'dbType' => 'datetime',
                        'eval' => 'datetime',
                        'default' => '0000-00-00 00:00:00'
                    ]
                ],
                'itemFormElValue' => $input
            ]
        ];
        /** @var NodeFactory $nodeFactoryProphecy */
        $nodeFactoryProphecy = $this->prophesize(NodeFactory::class)->reveal();
        $subject = new InputTextElement($nodeFactoryProphecy, $data);
        $result = $subject->render();
        $this->assertContains('<input type="hidden" name="" value="' . $expectedOutput . '" />', $result['html']);
    }
}
