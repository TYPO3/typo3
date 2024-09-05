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

namespace TYPO3\CMS\Core\Tests\Functional\Schema;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\PassiveRelation;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RelationMapBuilderTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    #[Test]
    public function relationMapBuilderContainsRelationsForFeGroups(): void
    {
        $subject = $this->get(RelationMapBuilder::class);
        $relationMap = $subject->buildFromStructure($GLOBALS['TCA']);

        $result = $relationMap->getActiveRelations('fe_users', 'usergroup');
        self::assertCount(1, $result);
        self::assertEquals('fe_groups', $result[0]->toTable());
        self::assertNull($result[0]->toField());
    }

    #[Test]
    public function relationMapBuilderContainsActiveAndPassiveRelationsToInlineField(): void
    {
        $subject = $this->get(RelationMapBuilder::class);
        $relationMap = $subject->buildFromStructure($GLOBALS['TCA']);

        $inlineParentTable = 'sys_workspace';
        $inlineParentField = 'custom_stages';
        $inlineChildTable = 'sys_workspace_stage';
        $inlineChildField = 'parentid';
        $result = $relationMap->getActiveRelations($inlineParentTable, $inlineParentField);
        self::assertCount(1, $result);
        self::assertEquals($inlineChildTable, $result[0]->toTable());
        self::assertEquals($inlineChildField, $result[0]->toField());
        $result = $relationMap->getPassiveRelations($inlineChildTable, $inlineChildField);
        self::assertCount(1, $result);
        self::assertEquals($inlineParentTable, $result[0]->fromTable());
        self::assertEquals($inlineParentField, $result[0]->fromField());
    }

    #[Test]
    public function relationMapBuilderContainsFileReferenceForTtContent(): void
    {
        $subject = $this->get(RelationMapBuilder::class);
        $relationMap = $subject->buildFromStructure($GLOBALS['TCA']);

        $result = $relationMap->getActiveRelations('tt_content', 'assets');
        self::assertCount(1, $result);
        self::assertEquals('sys_file_reference', $result[0]->toTable());
        self::assertEquals('uid_foreign', $result[0]->toField());
        $result = $relationMap->getPassiveRelations('sys_file_reference', 'uid_foreign');
        // find the relation back to tt_content
        $relationToTtContent = array_filter($result, static fn(PassiveRelation $relation) => $relation->fromTable() === 'tt_content');
        $relationToTtContent = array_values($relationToTtContent);

        self::assertEquals(
            ['image', 'assets', 'media'],
            array_map(static fn(PassiveRelation $relation) => $relation->fromField(), $relationToTtContent)
        );
    }
}
