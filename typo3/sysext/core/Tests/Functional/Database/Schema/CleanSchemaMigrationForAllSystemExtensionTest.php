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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Schema;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CleanSchemaMigrationForAllSystemExtensionTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        $this->coreExtensionsToLoad = array_merge(
            array_values($this->coreExtensionsToLoad),
            array_values($this->fetchAllSystemExtensions())
        );
        parent::setUp();
    }

    private function fetchAllSystemExtensions(): array
    {
        $systemExtensions = [];
        $iterator = new \DirectoryIterator(ORIGINAL_ROOT . '/typo3/sysext');
        foreach ($iterator as $item) {
            if ($item->isDot() || !$item->isDir()) {
                continue;
            }
            $extensionName = $item->getBasename();
            $systemExtensions[] = $extensionName;
        }
        return $systemExtensions;
    }

    #[Test]
    public function verifyCleanDatabaseState(): void
    {
        $sqlReader = $this->get(SqlReader::class);
        $schemaMigrator = $this->get(SchemaMigrator::class);
        $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
        $addCreateChange = $schemaMigrator->getUpdateSuggestions($sqlStatements);
        foreach ($addCreateChange['Default'] as $operation => $targets) {
            self::assertEmpty($targets, "Schema probably polluted by previous test, unclean operation: $operation");
        }
        $dropRename = $schemaMigrator->getUpdateSuggestions($sqlStatements, true);
        foreach ($dropRename['Default'] as $operation => $targets) {
            self::assertEmpty($targets, "Schema probably polluted by previous test, unclean operation: $operation");
        }
    }
}
