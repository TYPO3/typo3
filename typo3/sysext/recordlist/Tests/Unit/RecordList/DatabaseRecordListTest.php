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

namespace TYPO3\CMS\Recordlist\Tests\Unit\RecordList;

use Prophecy\Argument;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DatabaseRecordListTest extends UnitTestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    protected $resetSingletonInstances = true;

    protected DatabaseRecordList $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        $iconFactoryProphecy->getIcon(Argument::cetera())->shouldBeCalled()->willReturn(new Icon());
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
        GeneralUtility::setSingletonInstance(UriBuilder::class, new UriBuilder(new Router()));

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $this->subject = new DatabaseRecordList();
    }

    /**
     * @test
     */
    public function makeFieldListReturnsEmptyArrayOnBrokenTca(): void
    {
        self::assertEmpty($this->subject->makeFieldList('myTabe', true, true));
    }

    /**
     * @test
     */
    public function makeFieldListReturnsUniqueList(): void
    {
        $GLOBALS['TCA']['myTable'] = [
            'ctrl'=> [
                'tstamp' => 'updatedon',
                // Won't be added due to defined in "columns"
                'crdate' => 'createdon',
                'cruser_id' => 'createdby',
                'sortby' => 'sorting',
                'versioningWS' => true,
            ],
            'columns' => [
                // Regular field
                'title' => [
                    'config' => [
                        'type' => 'input'
                    ],
                ],
                // Overwrite automatically set management field from "ctrl"
                'createdon' => [
                    'config' => [
                        'type' => 'input'
                    ],
                ],
                // Won't be added due to type "none"
                'reference' => [
                    'config' => [
                        'type' => 'none'
                    ],
                ]
            ]
        ];

        self::assertEquals(
            ['title', 'createdon', 'uid', 'pid', 'updatedon', 'createdby', 'sorting', 't3ver_state', 't3ver_wsid', 't3ver_oid'],
            $this->subject->makeFieldList('myTable', true, true)
        );
    }
}
