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

use Doctrine\DBAL\Driver\Statement;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Tests\UnitTestCase;
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
     * @var array A backup of registered singleton instances
     */
    protected $singletonInstances = [];

    protected function setUp()
    {
        $this->singletonInstances = GeneralUtility::getSingletonInstances();
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

        // Prophecies and revelations for a lot of the database stack classes
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderRevelation = $queryBuilderProphecy->reveal();
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $queryRestrictionContainerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionContainerRevelation = $queryRestrictionContainerProphecy->reveal();
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $statementProphecy = $this->prophesize(Statement::class);

        // Register connection pool revelation in framework, this is the entry point used by the system during tests
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolProphecy->getQueryBuilderForTable('sys_language')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryRestrictionContainerProphecy->removeAll()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->getRestrictions()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->select('uid', 'title', 'language_isocode', 'flag')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->from('sys_language')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->orderBy('sorting')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionBuilderProphecy->reveal());
        $expressionBuilderProphecy->eq('pid', 0)->shouldBeCalled()->willReturn('pid = 0');
        $queryBuilderProphecy->where('pid = 0')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->createNamedParameter(Argument::cetera())->willReturnArgument(0);
        $queryBuilderProphecy->execute()->shouldBeCalled()->willReturn($statementProphecy->reveal());
        $statementProphecy->fetch()->shouldBeCalledTimes(1)->willReturn(false);

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

        // Prophecies and revelations for a lot of the database stack classes
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderRevelation = $queryBuilderProphecy->reveal();
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $queryRestrictionContainerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionContainerRevelation = $queryRestrictionContainerProphecy->reveal();
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $statementProphecy = $this->prophesize(Statement::class);

        // Register connection pool revelation in framework, this is the entry point used by the system during tests
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolProphecy->getQueryBuilderForTable('sys_language')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryRestrictionContainerProphecy->removeAll()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->getRestrictions()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->select('uid', 'title', 'language_isocode', 'flag')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->from('sys_language')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->orderBy('sorting')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionBuilderProphecy->reveal());
        $expressionBuilderProphecy->eq('pid', 0)->shouldBeCalled()->willReturn('pid = 0');
        $queryBuilderProphecy->where('pid = 0')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->createNamedParameter(Argument::cetera())->willReturnArgument(0);
        $queryBuilderProphecy->execute()->shouldBeCalled()->willReturn($statementProphecy->reveal());
        $statementProphecy->fetch()->shouldBeCalledTimes(1)->willReturn(false);

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

        // Prophecies and revelations for a lot of the database stack classes
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderRevelation = $queryBuilderProphecy->reveal();
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $queryRestrictionContainerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionContainerRevelation = $queryRestrictionContainerProphecy->reveal();
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $statementProphecy = $this->prophesize(Statement::class);

        // Register connection pool revelation in framework, this is the entry point used by the system during tests
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolProphecy->getQueryBuilderForTable('sys_language')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryRestrictionContainerProphecy->removeAll()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->getRestrictions()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->select('uid', 'title', 'language_isocode', 'flag')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->from('sys_language')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->orderBy('sorting')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionBuilderProphecy->reveal());
        $expressionBuilderProphecy->eq('pid', 0)->shouldBeCalled()->willReturn('pid = 0');
        $queryBuilderProphecy->where('pid = 0')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->createNamedParameter(Argument::cetera())->willReturnArgument(0);
        $queryBuilderProphecy->execute()->shouldBeCalled()->willReturn($statementProphecy->reveal());
        $statementProphecy->fetch()->shouldBeCalledTimes(1)->willReturn(false);

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
        $aDatabaseResultRow = [
            'uid' => 3,
            'title' => 'french',
            'language_isocode' => 'fr',
            'static_lang_isocode' => '',
            'flag' => 'fr',
        ];

        // Prophecies and revelations for a lot of the database stack classes
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderRevelation = $queryBuilderProphecy->reveal();
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $queryRestrictionContainerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionContainerRevelation = $queryRestrictionContainerProphecy->reveal();
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $statementProphecy = $this->prophesize(Statement::class);

        // Register connection pool revelation in framework, this is the entry point used by the system during tests
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolProphecy->getQueryBuilderForTable('sys_language')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryRestrictionContainerProphecy->removeAll()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->orderBy('sorting')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->getRestrictions()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->select('uid', 'title', 'language_isocode', 'flag')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->from('sys_language')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionBuilderProphecy->reveal());
        $expressionBuilderProphecy->eq('pid', 0)->shouldBeCalled()->willReturn('pid = 0');
        $queryBuilderProphecy->where('pid = 0')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->createNamedParameter(Argument::cetera())->willReturnArgument(0);
        $queryBuilderProphecy->execute()->shouldBeCalled()->willReturn($statementProphecy->reveal());

        $statementProphecy->fetch()->shouldBeCalledTimes(2)->willReturn($aDatabaseResultRow, false);

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
    public function addDataAddFlashMessageWithMissingIsoCode()
    {
        $aDatabaseResultRow = [
            'uid' => 3,
            'title' => 'french',
            'language_isocode' => '',
            'static_lang_isocode' => '',
            'flag' => 'fr',
        ];

        // Prophecies and revelations for a lot of the database stack classes
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderRevelation = $queryBuilderProphecy->reveal();
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $queryRestrictionContainerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionContainerRevelation = $queryRestrictionContainerProphecy->reveal();
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $statementProphecy = $this->prophesize(Statement::class);

        // Register connection pool revelation in framework, this is the entry point used by the system during tests
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolProphecy->getQueryBuilderForTable('sys_language')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryRestrictionContainerProphecy->removeAll()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->getRestrictions()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->select('uid', 'title', 'language_isocode', 'flag')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->from('sys_language')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->orderBy('sorting')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionBuilderProphecy->reveal());
        $expressionBuilderProphecy->eq('pid', 0)->shouldBeCalled()->willReturn('pid = 0');
        $queryBuilderProphecy->where('pid = 0')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->createNamedParameter(Argument::cetera())->willReturnArgument(0);
        $queryBuilderProphecy->execute()->shouldBeCalled()->willReturn($statementProphecy->reveal());

        $statementProphecy->fetch()->shouldBeCalledTimes(2)->willReturn($aDatabaseResultRow, false);

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
