<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Test case
 */
class DatabaseSystemLanguageRowsTest extends UnitTestCase
{
    /**
     * @var DatabaseSystemLanguageRows
     */
    protected $subject;
    /**
     * @var DatabaseConnection | ObjectProphecy
     */
    protected $dbProphecy;

    /**
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    protected function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
        $this->dbProphecy = $this->prophesize(DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->dbProphecy->reveal();
        $languageService = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $languageService->reveal();
        $languageService->sL(Argument::cetera())->willReturnArgument(0);
        $this->subject = new DatabaseSystemLanguageRows();
    }

    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        GeneralUtility::resetSingletonInstances($this->singletonInstances);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionOnDatabaseError()
    {
        $this->dbProphecy->exec_SELECTgetRows(Argument::cetera())->willReturn(null);
        $this->dbProphecy->sql_error(Argument::cetera())->willReturn(null);
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1438170741);
        $this->subject->addData([]);
    }

    /**
     * @test
     */
    public function addDataSetsDefaultLanguageAndAllEntries()
    {
        $expected = [
            'systemLanguageRows' => [
                -1 => [
                    'uid' => -1,
                    'title' => 'LLL:EXT:lang/locallang_mod_web_list.xlf:multipleLanguages',
                    'iso' => 'DEF',
                    'flagIconIdentifier' => 'flags-multiple',
                ],
                0 => [
                    'uid' => 0,
                    'title' => 'LLL:EXT:lang/locallang_mod_web_list.xlf:defaultLanguage',
                    'iso' => 'DEF',
                    'flagIconIdentifier' => 'empty-empty',
                ],
            ],
        ];
        $this->dbProphecy->exec_SELECTgetRows(Argument::cetera())->willReturn([]);
        $this->assertSame($expected, $this->subject->addData([]));
    }

    /**
     * @test
     */
    public function addDataSetsDefaultLanguageTitleFromPageTsConfig()
    {
        $input = [
            'pageTsConfig' => [
                'mod.' => [
                    'SHARED.' => [
                        'defaultLanguageLabel' => 'foo',
                    ],
                ]
            ],
        ];
        $this->dbProphecy->exec_SELECTgetRows(Argument::cetera())->willReturn([]);
        $expected = $input;
        $expected['systemLanguageRows'] = [
            -1 => [
                'uid' => -1,
                'title' => 'LLL:EXT:lang/locallang_mod_web_list.xlf:multipleLanguages',
                'iso' => 'DEF',
                'flagIconIdentifier' => 'flags-multiple',
            ],
            0 => [
                'uid' => 0,
                'title' => 'foo (LLL:EXT:lang/locallang_mod_web_list.xlf:defaultLanguage)',
                'iso' => 'DEF',
                'flagIconIdentifier' => 'empty-empty',
            ],
        ];
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsDefaultLanguageFlagFromPageTsConfig()
    {
        $input = [
            'pageTsConfig' => [
                'mod.' => [
                    'SHARED.' => [
                        'defaultLanguageFlag' => 'uk',
                    ],
                ]
            ],
        ];
        $this->dbProphecy->exec_SELECTgetRows(Argument::cetera())->willReturn([]);
        $expected = $input;
        $expected['systemLanguageRows'] = [
            -1 => [
                'uid' => -1,
                'title' => 'LLL:EXT:lang/locallang_mod_web_list.xlf:multipleLanguages',
                'iso' => 'DEF',
                'flagIconIdentifier' => 'flags-multiple',
            ],
            0 => [
                'uid' => 0,
                'title' => 'LLL:EXT:lang/locallang_mod_web_list.xlf:defaultLanguage',
                'iso' => 'DEF',
                'flagIconIdentifier' => 'flags-uk',
            ],
        ];
        $this->assertSame($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataResolvesLanguageIsocodeFromDatabaseField()
    {
        $dbRows = [
            [
                'uid' => 3,
                'title' => 'french',
                'language_isocode' => 'fr',
                'static_lang_isocode' => '',
                'flag' => 'fr',
            ],
        ];
        $this->dbProphecy->exec_SELECTgetRows('uid,title,language_isocode,static_lang_isocode,flag', 'sys_language', 'pid=0')->willReturn($dbRows);
        $expected = [
            'systemLanguageRows' => [
                -1 => [
                    'uid' => -1,
                    'title' => 'LLL:EXT:lang/locallang_mod_web_list.xlf:multipleLanguages',
                    'iso' => 'DEF',
                    'flagIconIdentifier' => 'flags-multiple',
                ],
                0 => [
                    'uid' => 0,
                    'title' => 'LLL:EXT:lang/locallang_mod_web_list.xlf:defaultLanguage',
                    'iso' => 'DEF',
                    'flagIconIdentifier' => 'empty-empty',
                ],
                3 => [
                    'uid' => 3,
                    'title' => 'french',
                    'flagIconIdentifier' => 'flags-fr',
                    'iso' => 'fr',
                ],
            ],
        ];
        $this->assertSame($expected, $this->subject->addData([]));
    }

    /**
     * @test
     */
    public function addDataResolvesLanguageIsocodeFromStaticInfoTable()
    {
        if (ExtensionManagementUtility::isLoaded('static_info_tables') === false) {
            $this->markTestSkipped('no ext:static_info_tables available');
        }
        $dbRows = [
            [
                'uid' => 3,
                'title' => 'french',
                'language_isocode' => '',
                'static_lang_isocode' => 42,
                'flag' => 'fr',
            ],
        ];
        $this->dbProphecy->exec_SELECTgetRows('uid,title,language_isocode,static_lang_isocode,flag', 'sys_language', 'pid=0')->shouldBeCalled()->willReturn($dbRows);
        // Needed for backendUtility::getRecord()
        $GLOBALS['TCA']['static_languages'] = [ 'foo' ];
        $this->dbProphecy->exec_SELECTgetSingleRow('lg_iso_2', 'static_languages', 'uid=42')->shouldBeCalled()->willReturn([ 'lg_iso_2' => 'FR' ]);
        $expected = [
            'systemLanguageRows' => [
                -1 => [
                    'uid' => -1,
                    'title' => 'LLL:EXT:lang/locallang_mod_web_list.xlf:multipleLanguages',
                    'iso' => 'DEF',
                    'flagIconIdentifier' => 'flags-multiple',
                ],
                0 => [
                    'uid' => 0,
                    'title' => 'LLL:EXT:lang/locallang_mod_web_list.xlf:defaultLanguage',
                    'iso' => 'DEF',
                    'flagIconIdentifier' => 'empty-empty',
                ],
                3 => [
                    'uid' => 3,
                    'title' => 'french',
                    'flagIconIdentifier' => 'flags-fr',
                    'iso' => 'FR',
                ],
            ],
        ];
        $this->assertSame($expected, $this->subject->addData([]));
    }

    /**
     * @test
     */
    public function addDataAddFlashMessageWithMissingIsoCode()
    {
        $dbRows = [
            [
                'uid' => 3,
                'title' => 'french',
                'language_isocode' => '',
                'static_lang_isocode' => '',
                'flag' => 'fr',
            ],
        ];
        $this->dbProphecy->exec_SELECTgetRows('uid,title,language_isocode,static_lang_isocode,flag', 'sys_language', 'pid=0')->shouldBeCalled()->willReturn($dbRows);
        // Needed for backendUtility::getRecord()
        $GLOBALS['TCA']['static_languages'] = [ 'foo' ];
        $expected = [
            'systemLanguageRows' => [
                -1 => [
                    'uid' => -1,
                    'title' => 'LLL:EXT:lang/locallang_mod_web_list.xlf:multipleLanguages',
                    'iso' => 'DEF',
                    'flagIconIdentifier' => 'flags-multiple',
                ],
                0 => [
                    'uid' => 0,
                    'title' => 'LLL:EXT:lang/locallang_mod_web_list.xlf:defaultLanguage',
                    'iso' => 'DEF',
                    'flagIconIdentifier' => 'empty-empty',
                ],
                3 => [
                    'uid' => 3,
                    'title' => 'french',
                    'flagIconIdentifier' => 'flags-fr',
                    'iso' => '',
                ],
            ],
        ];

        /** @var FlashMessage|ObjectProphecy $flashMessage */
        $flashMessage = $this->prophesize(FlashMessage::class);
        GeneralUtility::addInstance(FlashMessage::class, $flashMessage->reveal());
        /** @var FlashMessageService|ObjectProphecy $flashMessageService */
        $flashMessageService = $this->prophesize(FlashMessageService::class);
        GeneralUtility::setSingletonInstance(FlashMessageService::class, $flashMessageService->reveal());
        /** @var FlashMessageQueue|ObjectProphecy $flashMessageQueue */
        $flashMessageQueue = $this->prophesize(FlashMessageQueue::class);
        $flashMessageService->getMessageQueueByIdentifier(Argument::cetera())->willReturn($flashMessageQueue->reveal());

        $flashMessageQueue->enqueue($flashMessage)->shouldBeCalled();

        $this->assertSame($expected, $this->subject->addData([]));
    }
}
