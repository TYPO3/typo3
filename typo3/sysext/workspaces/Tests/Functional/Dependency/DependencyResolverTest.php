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

namespace TYPO3\CMS\Workspaces\Tests\Functional\Dependency;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Dependency\DependencyCollectionAction;
use TYPO3\CMS\Workspaces\Dependency\DependencyResolver;
use TYPO3\CMS\Workspaces\Dependency\ElementEntity;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DependencyResolverTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/workspaces/Tests/Functional/Fixtures/Extensions/test_workspace_dependencies',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DependencyScenarios.csv');
    }

    /**
     * Discard only considers relations to delete placeholders. The related records of
     * the scenarios using this data provider are new or changed, so no structure is
     * resolved for Discard, see discardNestsChildThatIsDeletePlaceholder().
     */
    public static function structureIsResolvedForAllActionsButDiscardDataProvider(): iterable
    {
        yield 'publish' => [
            'action' => DependencyCollectionAction::Publish,
            'isResolved' => true,
        ];
        yield 'stage change' => [
            'action' => DependencyCollectionAction::StageChange,
            'isResolved' => true,
        ];
        yield 'display' => [
            'action' => DependencyCollectionAction::Display,
            'isResolved' => true,
        ];
        yield 'discard' => [
            'action' => DependencyCollectionAction::Discard,
            'isResolved' => false,
        ];
    }

    #[Test]
    #[DataProvider('structureIsResolvedForAllActionsButDiscardDataProvider')]
    public function inlineChildWithForeignFieldIsNestedBelowItsParent(DependencyCollectionAction $action, bool $isResolved): void
    {
        self::assertSame(
            $isResolved ? ['tt_content:3001' => ['tt_content:3001', 'tx_testworkspacedependencies_child:3001']] : [],
            $this->resolve($action, ['tt_content:3001'])
        );
    }

    #[Test]
    #[DataProvider('structureIsResolvedForAllActionsButDiscardDataProvider')]
    public function inlineChildWithCommaSeparatedListIsNestedBelowItsParent(DependencyCollectionAction $action, bool $isResolved): void
    {
        self::assertSame(
            $isResolved ? ['tt_content:3011' => ['tt_content:3011', 'tx_testworkspacedependencies_child:3011']] : [],
            $this->resolve($action, ['tt_content:3011'])
        );
    }

    #[Test]
    #[DataProvider('structureIsResolvedForAllActionsButDiscardDataProvider')]
    public function requestedInlineChildResolvesItsParentAsOuterMostParent(DependencyCollectionAction $action, bool $isResolved): void
    {
        self::assertSame(
            $isResolved ? ['tt_content:3011' => ['tt_content:3011', 'tx_testworkspacedependencies_child:3011']] : [],
            $this->resolve($action, ['tx_testworkspacedependencies_child:3011'])
        );
    }

    #[Test]
    #[DataProvider('structureIsResolvedForAllActionsButDiscardDataProvider')]
    public function changedInlineChildIsNestedBelowItsChangedParent(DependencyCollectionAction $action, bool $isResolved): void
    {
        self::assertSame(
            $isResolved ? ['tt_content:3141' => ['tt_content:3141', 'tx_testworkspacedependencies_child:3141']] : [],
            $this->resolve($action, ['tt_content:3141'])
        );
    }

    #[Test]
    #[DataProvider('structureIsResolvedForAllActionsButDiscardDataProvider')]
    public function fileReferenceIsNestedBelowItsParent(DependencyCollectionAction $action, bool $isResolved): void
    {
        self::assertSame(
            $isResolved ? ['tt_content:3021' => ['tt_content:3021', 'sys_file_reference:3021']] : [],
            $this->resolve($action, ['tt_content:3021'])
        );
    }

    /**
     * tt_content.tx_testworkspacedependencies_image is a type=file field with
     * "relationship" => "manyToOne", which resolves to a single file reference in
     * Record objects. Its file reference is stored with foreign_field like the one of
     * any other file field, but it is not nested below the content element, other than
     * the file reference in fileReferenceIsNestedBelowItsParent().
     *
     * @todo The core listener only accepts RelationshipType::OneToMany and ::List.
     *       Before IsReferenceConsideredForDependencyEvent was introduced, foreign_field
     *       was evaluated, which made single file fields a dependency as well.
     */
    #[Test]
    public function fileReferenceOfSingleFileFieldIsNotConsideredAsDependency(): void
    {
        self::assertSame([], $this->resolve(DependencyCollectionAction::Publish, ['tt_content:3201']));
    }

    #[Test]
    #[DataProvider('structureIsResolvedForAllActionsButDiscardDataProvider')]
    public function inlineChildInFlexFormIsNestedBelowItsParent(DependencyCollectionAction $action, bool $isResolved): void
    {
        self::assertSame(
            $isResolved ? ['tt_content:3101' => ['tt_content:3101', 'tx_testworkspacedependencies_child:3101']] : [],
            $this->resolve($action, ['tt_content:3101'])
        );
    }

    #[Test]
    #[DataProvider('structureIsResolvedForAllActionsButDiscardDataProvider')]
    public function requestedInlineChildInFlexFormResolvesItsParentAsOuterMostParent(DependencyCollectionAction $action, bool $isResolved): void
    {
        self::assertSame(
            $isResolved ? ['tt_content:3101' => ['tt_content:3101', 'tx_testworkspacedependencies_child:3101']] : [],
            $this->resolve($action, ['tx_testworkspacedependencies_child:3101'])
        );
    }

    public static function referencesToNewRecordsAreNotConsideredAsDependencyDataProvider(): iterable
    {
        yield 'group field tt_content.records pointing to new content' => ['tt_content:3061'];
        yield 'group field tt_content.pages pointing to new page' => ['tt_content:3071'];
        yield 'soft reference in tt_content.bodytext linking to new page' => ['tt_content:3081'];
        yield 'group field pages.shortcut pointing to new page' => ['pages:3091'];
    }

    /**
     * The referenced records only exist in the workspace. They are not nested below the
     * referencing record, so publishing the referencing record alone leaves a relation
     * (or link) in live that points to a record which is not published.
     *
     * @todo Whether such references should be considered for dependencies is up to discussion.
     *       IsReferenceConsideredForDependencyEvent does not provide the information
     *       to tell them apart (e.g. sys_refindex.softref_key), nor any information on
     *       the state of the referenced record.
     */
    #[Test]
    #[DataProvider('referencesToNewRecordsAreNotConsideredAsDependencyDataProvider')]
    public function referencesToNewRecordsAreNotConsideredAsDependency(string $element): void
    {
        self::assertSame([], $this->resolve(DependencyCollectionAction::Publish, [$element]));
    }

    /**
     * tt_content:3112 is changed in the workspace as tt_content:3113. Only Publish and
     * Display resolve elements to their versioned uid, StageChange keeps the live uid
     * sys_refindex points to. Discard only considers relations to delete placeholders.
     *
     * @todo Whether elements are resolved to their versioned uid for StageChange as well is up to discussion.
     */
    public static function flexFormStructureDataProvider(): iterable
    {
        yield 'publish' => [
            'action' => DependencyCollectionAction::Publish,
            'expectedStructure' => ['tt_content:3111' => ['tt_content:3111', 'tt_content:3113', 'pages:3114']],
        ];
        yield 'stage change' => [
            'action' => DependencyCollectionAction::StageChange,
            'expectedStructure' => ['tt_content:3111' => ['tt_content:3111', 'tt_content:3112', 'pages:3114']],
        ];
        yield 'display' => [
            'action' => DependencyCollectionAction::Display,
            'expectedStructure' => ['tt_content:3111' => ['tt_content:3111', 'tt_content:3113', 'pages:3114']],
        ];
        yield 'discard' => [
            'action' => DependencyCollectionAction::Discard,
            'expectedStructure' => [],
        ];
    }

    /**
     * tt_content:3111 references tt_content:3112 (changed in the workspace as
     * tt_content:3113) with a group, and pages:3114 with a link, both in a FlexForm.
     * The same kinds of relations outside a FlexForm are not considered (see
     * referencesToNewRecordsAreNotConsideredAsDependency()). Inside a FlexForm, all
     * of them end up in one structure.
     *
     * @todo The core listener considers every relation of a FlexForm field, regardless
     *       of the FlexForm field it originates from. IsReferenceConsideredForDependencyEvent
     *       does not provide sys_refindex.flexpointer to tell them apart.
     */
    #[Test]
    #[DataProvider('flexFormStructureDataProvider')]
    public function everyFlexFormRelationIsConsideredAsStructuralDependency(DependencyCollectionAction $action, array $expectedStructure): void
    {
        self::assertSame($expectedStructure, $this->resolve($action, ['tt_content:3111']));
    }

    /**
     * Requesting the new page pages:3114 that is linked from tt_content:3111 makes the
     * linking record its outermost parent, so the complete structure of
     * everyFlexFormRelationIsConsideredAsStructuralDependency() is resolved.
     *
     * @todo Dependencies are symmetric parent/child structures, a listener cannot express
     *       a relation that only applies in one direction. IsReferenceConsideredForDependencyEvent
     *       does not tell whether children or parents of an element are resolved.
     */
    #[Test]
    #[DataProvider('flexFormStructureDataProvider')]
    public function referencedRecordOfFlexFormResolvesTheReferencingStructure(DependencyCollectionAction $action, array $expectedStructure): void
    {
        self::assertSame($expectedStructure, $this->resolve($action, ['pages:3114']));
    }

    public static function allActionsDataProvider(): iterable
    {
        foreach (DependencyCollectionAction::cases() as $action) {
            yield $action->name => [$action];
        }
    }

    /**
     * The same relation as in referencedRecordOfFlexFormResolvesTheReferencingStructure(),
     * but the referenced record is changed instead of new: sys_refindex points to the live
     * uid tt_content:3112, while parents are looked up by the versioned uid tt_content:3113.
     * The referencing tt_content:3111 is not found, so the referenced record is resolved on
     * its own. This is correct: the referenced record does not depend on the referencing
     * record.
     */
    #[Test]
    #[DataProvider('allActionsDataProvider')]
    public function referencedChangedRecordOfFlexFormDoesNotResolveTheReferencingRecord(DependencyCollectionAction $action): void
    {
        self::assertSame([], $this->resolve($action, ['tt_content:3113']));
    }

    public static function relationToTableWithoutWorkspaceSupportIsInvalidDataProvider(): iterable
    {
        yield 'publish' => [
            'action' => DependencyCollectionAction::Publish,
            'expectedInvalidStates' => ['tt_content:3121' => false, 'sys_file:1' => true],
        ];
        yield 'stage change' => [
            'action' => DependencyCollectionAction::StageChange,
            'expectedInvalidStates' => ['tt_content:3121' => false, 'sys_file:1' => false],
        ];
        yield 'display' => [
            'action' => DependencyCollectionAction::Display,
            'expectedInvalidStates' => ['tt_content:3121' => false, 'sys_file:1' => true],
        ];
        yield 'discard' => [
            'action' => DependencyCollectionAction::Discard,
            'expectedInvalidStates' => [],
        ];
    }

    /**
     * sys_file is not workspace aware, the element is part of the structure, but marked
     * invalid, which removes it from the command map.
     */
    #[Test]
    #[DataProvider('relationToTableWithoutWorkspaceSupportIsInvalidDataProvider')]
    public function relationToTableWithoutWorkspaceSupportIsInvalid(DependencyCollectionAction $action, array $expectedInvalidStates): void
    {
        $dependencyResolver = $this->createDependencyResolver($action, ['tt_content:3121']);
        $invalidStates = [];
        foreach ($dependencyResolver->getOuterMostParents() as $outerMostParent) {
            foreach ($dependencyResolver->getNestedElements($outerMostParent) as $identifier => $element) {
                $invalidStates[$identifier] = $element->isInvalid();
            }
        }

        self::assertSame($expectedInvalidStates, $invalidStates);
    }

    public static function newRecordsReferencingEachOtherAreNotResolvedDataProvider(): iterable
    {
        yield 'one of them requested' => [['tt_content:3131']];
        yield 'both requested' => [['tt_content:3131', 'tt_content:3132']];
    }

    /**
     * tt_content:3131 and tt_content:3132 reference each other. None of them is an
     * outermost parent, so no structure is resolved at all and each record is
     * published on its own, although the listener considers both relations.
     *
     * @todo Cyclic structures without an outermost parent are ignored without notice.
     */
    #[Test]
    #[DataProvider('newRecordsReferencingEachOtherAreNotResolvedDataProvider')]
    public function newRecordsReferencingEachOtherAreNotResolved(array $elements): void
    {
        $dependencyResolver = $this->createDependencyResolver(DependencyCollectionAction::Publish, $elements);

        self::assertTrue($dependencyResolver->getElements()['tt_content:3131']->hasReferences());
        self::assertSame([], $dependencyResolver->getOuterMostParents());
    }

    #[Test]
    public function discardNestsChildThatIsDeletePlaceholder(): void
    {
        self::assertSame(
            ['tt_content:3151' => ['tt_content:3151', 'tx_testworkspacedependencies_child:3151']],
            $this->resolve(DependencyCollectionAction::Discard, ['tt_content:3151'])
        );
    }

    /**
     * @param string[] $elements "table:uid" identifiers
     * @return array<string, string[]> outermost parent identifier => identifiers of its nested elements
     */
    private function resolve(DependencyCollectionAction $action, array $elements): array
    {
        $dependencyResolver = $this->createDependencyResolver($action, $elements);
        $result = [];
        foreach ($dependencyResolver->getOuterMostParents() as $identifier => $outerMostParent) {
            $result[$identifier] = array_map(
                static fn(ElementEntity $element): string => (string)$element,
                array_values($dependencyResolver->getNestedElements($outerMostParent))
            );
        }
        return $result;
    }

    /**
     * @param string[] $elements "table:uid" identifiers
     */
    private function createDependencyResolver(DependencyCollectionAction $action, array $elements): DependencyResolver
    {
        $dependencyResolver = GeneralUtility::makeInstance(DependencyResolver::class);
        $dependencyResolver->setWorkspace(91);
        $dependencyResolver->setEventDispatcher($this->get(EventDispatcherInterface::class));
        $dependencyResolver->setAction($action);
        foreach ($elements as $element) {
            [$table, $uid] = explode(':', $element);
            $dependencyResolver->addElement($table, (int)$uid);
        }
        return $dependencyResolver;
    }
}
