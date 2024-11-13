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

namespace TYPO3\CMS\Core\Package\Initialization;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Package\Event\PackageInitializationEvent;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Listener to import static sql data ("ext_tables_static+adt.sql") after package activation
 */
final readonly class ImportStaticSqlDataOnPackageInitialization
{
    public function __construct(
        private Registry $registry,
        private SqlReader $sqlReader,
        private SchemaMigrator $schemaMigrator,
    ) {}

    #[AsEventListener(after: ImportExtensionDataOnPackageInitialization::class)]
    public function __invoke(PackageInitializationEvent $event): void
    {
        $extTablesStaticSqlFile = $event->getPackage()->getPackagePath() . 'ext_tables_static+adt.sql';
        $extTablesStaticSqlRelFile = PathUtility::stripPathSitePrefix($extTablesStaticSqlFile);
        $oldFileHash = $this->registry->get('extensionDataImport', $extTablesStaticSqlRelFile);
        $currentFileHash = '';
        // We used to only store "1" in the database when data was imported
        $needsUpdate = !$oldFileHash || $oldFileHash === 1;
        if (file_exists($extTablesStaticSqlFile)) {
            $currentFileHash = hash_file('xxh3', $extTablesStaticSqlFile);
            $needsUpdate = $oldFileHash !== $currentFileHash;
            if ($needsUpdate) {
                $extTablesStaticSqlContent = (string)file_get_contents($extTablesStaticSqlFile);
                $statements = $this->sqlReader->getStatementArray($extTablesStaticSqlContent);
                $this->schemaMigrator->importStaticData($statements, true);
            }
        }
        if ($needsUpdate) {
            $this->registry->set('extensionDataImport', $extTablesStaticSqlRelFile, $currentFileHash);
            $event->addStorageEntry(__CLASS__, $extTablesStaticSqlFile);
        }
    }
}
