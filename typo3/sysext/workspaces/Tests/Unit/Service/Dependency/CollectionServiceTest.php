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

namespace TYPO3\CMS\Workspaces\Tests\Unit\Service\Dependency;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Workspaces\Dependency\DependencyResolver;
use TYPO3\CMS\Workspaces\Dependency\ElementEntity;
use TYPO3\CMS\Workspaces\Dependency\ReferenceEntity;
use TYPO3\CMS\Workspaces\Service\Dependency\CollectionService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CollectionServiceTest extends UnitTestCase
{
    /**
     * Counts how often the traversal asked an element for its children.
     */
    private int $childrenCalls = 0;

    #[Test]
    public function processSkipsCircularDependencies(): void
    {
        // tt_content:2003 -> tt_content:2001 -> tt_content:2002 -> tt_content:2001 -> ...
        $subject = $this->createSubject([
            'tt_content:2003' => ['tt_content:2001'],
            'tt_content:2001' => ['tt_content:2002'],
            'tt_content:2002' => ['tt_content:2001'],
        ], ['tt_content:2003']);

        $result = $subject->process([
            'tt_content:2003' => $this->createGridRow('tt_content:2003'),
            'tt_content:2001' => $this->createGridRow('tt_content:2001'),
            'tt_content:2002' => $this->createGridRow('tt_content:2002'),
        ]);

        self::assertSame(
            ['tt_content:2003', 'tt_content:2001', 'tt_content:2002'],
            array_column($result, 'id')
        );
        self::assertSame([0, 1, 2], array_column($result, 'Workspaces_CollectionLevel'));
        self::assertSame([1, 1, 0], array_column($result, 'Workspaces_CollectionChildren'));
        self::assertSame('', $result[0]['Workspaces_CollectionParent']);
        self::assertSame(md5('tt_content:2003'), $result[1]['Workspaces_CollectionParent']);
        self::assertSame(md5('tt_content:2001'), $result[2]['Workspaces_CollectionParent']);
    }

    #[Test]
    public function processDoesNotWalkEveryPathOfDenselyRelatedElements(): void
    {
        // Ten elements all referencing each other. Without skipping elements that have
        // already been resolved in the same context, this walks every path through the
        // graph, which is more than 900.000 traversal steps for ten elements alone.
        $identifiers = [];
        for ($uid = 2001; $uid <= 2010; $uid++) {
            $identifiers[] = 'tt_content:' . $uid;
        }
        $relations = [];
        $dataArray = [];
        foreach ($identifiers as $identifier) {
            $relations[$identifier] = array_values(array_diff($identifiers, [$identifier]));
            $dataArray[$identifier] = $this->createGridRow($identifier);
        }
        $subject = $this->createSubject($relations, [$identifiers[0]]);

        $result = $subject->process($dataArray);

        self::assertCount(10, $result);
        self::assertLessThan(1000, $this->childrenCalls);
    }

    /**
     * @param array<string, string[]> $relations Child identifiers per element identifier
     * @param string[] $outerMostParents
     */
    private function createSubject(array $relations, array $outerMostParents): CollectionService
    {
        $elements = [];
        foreach (array_keys($relations) as $identifier) {
            $elements[$identifier] = $this->createElement($identifier);
        }
        foreach ($relations as $identifier => $childIdentifiers) {
            $children = [];
            foreach ($childIdentifiers as $childIdentifier) {
                $children[] = new ReferenceEntity($elements[$childIdentifier], 'pi_flexform');
            }
            $elements[$identifier]->method('getChildren')->willReturnCallback(
                function () use ($children): array {
                    $this->childrenCalls++;
                    return $children;
                }
            );
        }

        $dependencyResolver = self::createStub(DependencyResolver::class);
        $dependencyResolver->method('getOuterMostParents')->willReturn(
            array_intersect_key($elements, array_flip($outerMostParents))
        );

        return new class (self::createStub(EventDispatcherInterface::class), $dependencyResolver) extends CollectionService {
            public function __construct(EventDispatcherInterface $eventDispatcher, DependencyResolver $dependencyResolver)
            {
                parent::__construct($eventDispatcher);
                $this->dependencyResolver = $dependencyResolver;
            }
        };
    }

    private function createElement(string $identifier): ElementEntity&Stub
    {
        $element = self::createStub(ElementEntity::class);
        $element->method('__toString')->willReturn($identifier);
        return $element;
    }

    private function createGridRow(string $identifier): array
    {
        [$table, $uid] = explode(':', $identifier);
        return [
            'id' => $identifier,
            'table' => $table,
            'uid' => (int)$uid,
            'Workspaces_Collection' => 0,
            'Workspaces_CollectionLevel' => 0,
            'Workspaces_CollectionParent' => '',
            'Workspaces_CollectionCurrent' => '',
            'Workspaces_CollectionChildren' => 0,
        ];
    }
}
