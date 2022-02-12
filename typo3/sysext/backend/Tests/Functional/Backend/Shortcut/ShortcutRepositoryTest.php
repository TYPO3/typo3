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

namespace TYPO3\CMS\Backend\Tests\Functional\Backend\Shortcut;

use TYPO3\CMS\Backend\Backend\Shortcut\ShortcutRepository;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ShortcutRepositoryTest extends FunctionalTestCase
{
    protected ShortcutRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ShortcutsBase.csv');

        $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $this->subject = new ShortcutRepository(
            $this->getContainer()->get(ConnectionPool::class),
            $this->getContainer()->get(IconFactory::class),
            $this->getContainer()->get(ModuleProvider::class)
        );
    }

    /**
     * @dataProvider shortcutExistsTestDataProvider
     * @test
     *
     * @param string $routeIdentifier
     * @param array  $arguments
     * @param int    $userid
     * @param bool   $exists
     */
    public function shortcutExistsTest(string $routeIdentifier, array $arguments, int $userid, bool $exists): void
    {
        $GLOBALS['BE_USER']->user['uid'] = $userid;
        self::assertEquals($exists, $this->subject->shortcutExists($routeIdentifier, json_encode($arguments)));
    }

    public function shortcutExistsTestDataProvider(): \Generator
    {
        yield 'Shortcut exists' => [
            'web_list',
            ['id' => 123, 'GET' => ['clipBoard' => 1]],
            1,
            true,
        ];
        yield 'Not this user' => [
            'web_list',
            ['id' => 123, 'GET' => ['clipBoard' => 1]],
            2,
            false,
        ];
        yield 'Wrong route identifer' => [
            'web_layout',
            ['id' => 123, 'GET' => ['clipBoard' => 1]],
            1,
            false,
        ];
        yield 'Wrong arguments' => [
            'web_list',
            ['id' => 321, 'GET' => ['clipBoard' => 1]],
            1,
            false,
        ];
    }

    /**
     * @test
     */
    public function addShortcutTest(): void
    {
        foreach ($this->getShortcutsToAdd() as $shortcut) {
            $this->subject->addShortcut(
                $shortcut['routeIdentifier'],
                json_encode($shortcut['arguments']),
                $shortcut['title']
            );
        }

        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/ShortcutsAddedResult.csv');
    }

    public function getShortcutsToAdd(): array
    {
        return [
            'Basic shortcut with all information' => [
                'routeIdentifier' => 'web_list',
                'arguments' => ['id' => 111, 'GET' => ['clipBoard' => 1]],
                'title' => 'Recordlist of id 111',
            ],
            'Shortcut with empty title' => [
                'routeIdentifier' => 'record_edit',
                'arguments' => ['edit' => ['pages' => [112 => 'edit']]],
                'title' => '',
            ],
            'Shortcut with invalid route' => [
                'routeIdentifier' => 'invalid_route',
                'arguments' => ['edit' => ['pages' => [112 => 'edit']]],
                'title' => 'Some title',
            ],
        ];
    }

    /**
     * This effectively also tests ShortcutRepository::initShortcuts()
     *
     * @test
     */
    public function getShortcutsByGroupTest(): void
    {
        $expected = [
            1 => [
                'table' => null,
                'recordid' => null,
                'groupLabel' => 'Pages',
                'type' => 'other',
                'icon' => 'data-identifier="module-list"',
                'label' => 'Recordlist',
                'href' => '/typo3/module/web/list?token=%s&id=123&GET%5BclipBoard%5D=1',
            ],
            2 => [
                'table' => 'tt_content',
                'recordid' => 113,
                'groupLabel' => null,
                'type' => 'edit',
                'label' => 'Edit Content',
                'icon' => 'data-identifier="mimetypes-x-content-text"',
                'href' => '/typo3/record/edit?token=%s&edit%5Btt_content%5D%5B113%5D=edit',
            ],
            6 => [
                'table' => null,
                'recordid' => null,
                'groupLabel' => null,
                'type' => 'other',
                'label' => 'Shortcut', // This is a fallback to not display shortcuts without title
                'icon' => 'data-identifier="module-page"',
                'href' => '/typo3/module/web/layout?token=%s&id=123',
            ],
        ];

        $shortcuts = $this->subject->getShortcutsByGroup(1);
        self::assertCount(3, $shortcuts);

        foreach ($shortcuts as $shortcut) {
            $id = (int)$shortcut['raw']['uid'];
            self::assertEquals(1, $shortcut['group']);
            self::assertEquals($expected[$id]['table'], $shortcut['table'] ?? null);
            self::assertEquals($expected[$id]['recordid'], $shortcut['recordid'] ?? null);
            self::assertEquals($expected[$id]['groupLabel'], $shortcut['groupLabel'] ?? null);
            self::assertEquals($expected[$id]['type'], $shortcut['type']);
            self::assertEquals($expected[$id]['label'], $shortcut['label']);
            self::assertStringContainsString($expected[$id]['icon'], $shortcut['icon']);
            self::assertStringMatchesFormat($expected[$id]['href'], $shortcut['href']);
        }
    }
}
