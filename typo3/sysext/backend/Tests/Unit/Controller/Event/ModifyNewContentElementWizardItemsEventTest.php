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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller\Event;

use TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ModifyNewContentElementWizardItemsEventTest extends UnitTestCase
{
    protected ModifyNewContentElementWizardItemsEvent $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ModifyNewContentElementWizardItemsEvent(
            [
                'aItem' => [
                    'aKey' => 'aValue',
                ],
                'bItem' => [],
                'cItem' => [],
            ],
            [
                '_thePath' => '/',
            ],
            1,
            2,
            3
        );
    }

    /**
     * @test
     */
    public function wizardItemsModifyTest(): void
    {
        self::assertCount(3, $this->subject->getWizardItems());
        self::assertTrue($this->subject->hasWizardItem('aItem'));
        self::assertEquals(['aKey' => 'aValue'], $this->subject->getWizardItem('aItem'));

        self::assertFalse($this->subject->removeWizardItem('dItem'));
        self::assertTrue($this->subject->removeWizardItem('aItem'));

        self::assertCount(2, $this->subject->getWizardItems());
        self::assertFalse($this->subject->hasWizardItem('aItem'));
        self::assertNull($this->subject->getWizardItem('aItem'));

        $items = $this->subject->getWizardItems();
        $items['dItem'] = ['dKey' => 'dValue'];
        $this->subject->setWizardItems($items);

        self::assertCount(3, $this->subject->getWizardItems());
        self::assertTrue($this->subject->hasWizardItem('dItem'));
        self::assertEquals(['dKey' => 'dValue'], $this->subject->getWizardItem('dItem'));

        self::assertEquals(['_thePath' => '/'], $this->subject->getPageInfo());
        self::assertEquals(1, $this->subject->getColPos());
        self::assertEquals(2, $this->subject->getSysLanguage());
        self::assertEquals(3, $this->subject->getUidPid());
    }

    public static function addWizardItemTestDataProvider(): iterable
    {
        yield 'Change an existing item configuration' => [
            'aItem',
            [
                'aKey' => 'anotherValue',
                'aAKey' => 'aAnotherValue',
            ],
            [],
            [
                'aItem' => [
                    'aKey' => 'anotherValue',
                    'aAKey' => 'aAnotherValue',
                ],
                'bItem' => [],
                'cItem' => [],
            ],
        ];
        yield 'Relocate an existing item before' => [
            'bItem',
            [],
            ['before' => 'aItem'],
            [
                'bItem' => [],
                'aItem' => [
                    'aKey' => 'aValue',
                ],
                'cItem' => [],
            ],
        ];
        yield 'Relocate an existing item after' => [
            'aItem',
            [],
            ['after' => 'bItem'],
            [
                'bItem' => [],
                'aItem' => [],
                'cItem' => [],
            ],
        ];
        yield 'Relocate while changing item configuration' => [
            'aItem',
            [
                'aKey' => 'anotherValue',
                'aAKey' => 'aAnotherValue',
            ],
            ['after' => 'cItem'],
            [
                'bItem' => [],
                'cItem' => [],
                'aItem' => [
                    'aKey' => 'anotherValue',
                    'aAKey' => 'aAnotherValue',
                ],
            ],
        ];
        yield 'Invalid position reference' => [
            'aItem',
            ['aKey' => 'aValue'],
            ['after' => 'cItem'],
            [
                'aItem' => [
                    'aKey' => 'aValue',
                ],
                'bItem' => [],
                'cItem' => [],
            ],
        ];
        yield 'Add new item' => [
            'dItem',
            [],
            [],
            [
                'aItem' => [
                    'aKey' => 'aValue',
                ],
                'bItem' => [],
                'cItem' => [],
                'dItem' => [],
            ],
        ];
        yield 'Add new item before' => [
            'dItem',
            [],
            ['before' => 'bItem'],
            [
                'aItem' => [
                    'aKey' => 'aValue',
                ],
                'dItem' => [],
                'bItem' => [],
                'cItem' => [],
            ],
        ];
        yield 'Add new item after' => [
            'dItem',
            [],
            ['after' => 'aItem'],
            [
                'aItem' => [
                    'aKey' => 'aValue',
                ],
                'dItem' => [],
                'bItem' => [],
                'cItem' => [],
            ],
        ];
        yield 'Add new item before with configuration' => [
            'dItem',
            ['dKey' => 'dValue'],
            ['before' => 'aItem'],
            [
                'dItem' => [
                    'dKey' => 'dValue',
                ],
                'aItem' => [
                    'aKey' => 'aValue',
                ],
                'bItem' => [],
                'cItem' => [],
            ],
        ];
        yield 'Add new item with configuration and invalid position' => [
            'dItem',
            ['dKey' => 'dValue'],
            ['after' => 'eItem'],
            [
                'aItem' => [
                    'aKey' => 'aValue',
                ],
                'bItem' => [],
                'cItem' => [],
                'dItem' => [
                    'dKey' => 'dValue',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider addWizardItemTestDataProvider
     */
    public function addWizardItemTest(string $identifier, array $configuration, array $position, array $expected): void
    {
        $this->subject->setWizardItem($identifier, $configuration, $position);
        self::assertEquals($expected, $this->subject->getWizardItems());
    }
}
