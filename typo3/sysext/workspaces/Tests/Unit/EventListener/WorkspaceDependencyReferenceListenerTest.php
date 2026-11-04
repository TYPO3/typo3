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

namespace TYPO3\CMS\Workspaces\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Schema\FieldTypeFactory;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Workspaces\Dependency\DependencyCollectionAction;
use TYPO3\CMS\Workspaces\Event\IsReferenceConsideredForDependencyEvent;
use TYPO3\CMS\Workspaces\EventListener\WorkspaceDependencyReferenceListener;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class WorkspaceDependencyReferenceListenerTest extends UnitTestCase
{
    private TcaSchemaFactory $tcaSchemaFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->expects($this->atLeastOnce())->method('has')->with(self::isString())->willReturn(false);
        $this->tcaSchemaFactory = new TcaSchemaFactory(
            new TcaSchemaBuilder(
                new RelationMapBuilder(self::createStub(FlexFormTools::class)),
                new FieldTypeFactory(),
            ),
            '',
            $cacheMock
        );

        $this->tcaSchemaFactory->load([
            'tt_content' => [
                'columns' => [
                    'image' => [
                        'config' => [
                            'type' => 'file',
                            'foreign_table' => 'sys_file_reference',
                            'foreign_field' => 'uid_foreign',
                        ],
                    ],
                    'tx_irre_children' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'tx_irre_child',
                            'foreign_field' => 'parentid',
                        ],
                    ],
                    'pi_flexform' => [
                        'config' => [
                            'type' => 'flex',
                        ],
                    ],
                    'header' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'categories' => [
                        'config' => [
                            'type' => 'category',
                        ],
                    ],
                    'select_field' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'foreign_table' => 'pages',
                        ],
                    ],
                    'inline_mm' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'tx_irre_child',
                            'MM' => 'tx_irre_mm',
                        ],
                    ],
                    'file_no_foreign' => [
                        'config' => [
                            'type' => 'file',
                        ],
                    ],
                    'single_image' => [
                        'config' => [
                            'type' => 'file',
                            'foreign_table' => 'sys_file_reference',
                            'foreign_field' => 'uid_foreign',
                            'relationship' => 'manyToOne',
                        ],
                    ],
                    'inline_many_to_one' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'tx_irre_child',
                            'foreign_field' => 'parentid',
                            'relationship' => 'manyToOne',
                        ],
                    ],
                    'inline_one_to_one' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'tx_irre_child',
                            'foreign_field' => 'parentid',
                            'relationship' => 'oneToOne',
                        ],
                    ],
                    'inline_list' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'tx_irre_child',
                        ],
                    ],
                    'records' => [
                        'config' => [
                            'type' => 'group',
                            'allowed' => 'tt_content',
                        ],
                    ],
                    'bodytext' => [
                        'config' => [
                            'type' => 'text',
                            'softref' => 'typolink_tag,email[subst],url',
                        ],
                    ],
                ],
            ],
            'tx_irre_child' => [
                'columns' => [
                    'parentid' => [
                        'config' => [
                            'type' => 'passthrough',
                        ],
                    ],
                ],
            ],
            'sys_file_reference' => [
                'columns' => [
                    'uid_foreign' => [
                        'config' => [
                            'type' => 'passthrough',
                        ],
                    ],
                ],
            ],
            'pages' => [
                'columns' => [],
            ],
        ]);
    }

    public static function structuralRelationsAreMarkedAsDependencyDataProvider(): iterable
    {
        yield 'file field with foreign_field is a dependency' => [
            'image',
            'sys_file_reference',
            true,
        ];
        yield 'inline field with foreign_field is a dependency' => [
            'tx_irre_children',
            'tx_irre_child',
            true,
        ];
        yield 'flex field is a dependency' => [
            'pi_flexform',
            'pages',
            true,
        ];
        yield 'input field is not a dependency' => [
            'header',
            'pages',
            false,
        ];
        yield 'category field is not a dependency' => [
            'categories',
            'pages',
            false,
        ];
        yield 'select field is not a dependency' => [
            'select_field',
            'pages',
            false,
        ];
        yield 'inline with MM is not a dependency' => [
            'inline_mm',
            'tx_irre_child',
            false,
        ];
        yield 'file without foreign_field is not a dependency' => [
            'file_no_foreign',
            'sys_file_reference',
            false,
        ];
        yield 'inline field with comma separated list is a dependency' => [
            'inline_list',
            'tx_irre_child',
            true,
        ];
        yield 'flex field is a dependency regardless of the referenced table' => [
            'pi_flexform',
            'sys_file',
            true,
        ];
        yield 'file field with foreign_field and relationship manyToOne is a dependency' => [
            'single_image',
            'sys_file_reference',
            true,
        ];
        yield 'inline field with foreign_field and relationship manyToOne is a dependency' => [
            'inline_many_to_one',
            'tx_irre_child',
            true,
        ];
        yield 'inline field with foreign_field and relationship oneToOne is a dependency' => [
            'inline_one_to_one',
            'tx_irre_child',
            true,
        ];
        // @todo Whether group relations outside of FlexForms should be considered is up to discussion.
        yield 'group field is not a dependency' => [
            'records',
            'tt_content',
            false,
        ];
        // @todo Whether soft references outside of FlexForms should be considered is up to discussion.
        yield 'text field with soft references is not a dependency' => [
            'bodytext',
            'pages',
            false,
        ];
        yield 'field without schema is not a dependency' => [
            'tx_removed_field',
            'pages',
            false,
        ];
    }

    #[Test]
    #[DataProvider('structuralRelationsAreMarkedAsDependencyDataProvider')]
    public function structuralRelationsAreMarkedAsDependency(string $fieldName, string $referenceTable, bool $expectedIsDependency): void
    {
        $listener = new WorkspaceDependencyReferenceListener($this->tcaSchemaFactory);
        $event = new IsReferenceConsideredForDependencyEvent(
            'tt_content',
            1,
            $fieldName,
            $referenceTable,
            42,
            DependencyCollectionAction::Publish,
            1,
        );

        $listener->__invoke($event);

        self::assertSame($expectedIsDependency, $event->isDependency());
    }

    #[Test]
    public function listenerWorksForAllActions(): void
    {
        $listener = new WorkspaceDependencyReferenceListener($this->tcaSchemaFactory);

        foreach (DependencyCollectionAction::cases() as $action) {
            $event = new IsReferenceConsideredForDependencyEvent(
                'tt_content',
                1,
                'tx_irre_children',
                'tx_irre_child',
                42,
                $action,
                1,
            );

            $listener->__invoke($event);
            self::assertTrue($event->isDependency(), 'Expected dependency for action: ' . $action->name);
        }
    }

    #[Test]
    public function referenceFromTableWithoutSchemaIsNotADependency(): void
    {
        $listener = new WorkspaceDependencyReferenceListener($this->tcaSchemaFactory);
        $event = new IsReferenceConsideredForDependencyEvent(
            'tx_removed_table',
            1,
            'tx_irre_children',
            'tx_irre_child',
            42,
            DependencyCollectionAction::Publish,
            1,
        );

        $listener->__invoke($event);

        self::assertFalse($event->isDependency());
    }
}
