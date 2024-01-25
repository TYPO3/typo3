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
        $oldHash = $this->registry->get('extensionDataImport', $extTablesStaticSqlRelFile);
        $shortFileHash = '';
        // We used to only store "1" in the database when data was imported
        $needsUpdate = !$oldHash || $oldHash === 1;
        if (file_exists($extTablesStaticSqlFile)) {
            $extTablesStaticSqlContent = (string)file_get_contents($extTablesStaticSqlFile);
            $shortFileHash = hash('xxh3', $extTablesStaticSqlContent);
            $needsUpdate = $oldHash !== $shortFileHash;
            if ($needsUpdate) {
                $statements = $this->sqlReader->getStatementArray($extTablesStaticSqlContent);
                $this->schemaMigrator->importStaticData($statements, true);
            }
        }
        if ($needsUpdate) {
            $this->registry->set('extensionDataImport', $extTablesStaticSqlRelFile, $shortFileHash);
            $event->addStorageEntry(__CLASS__, $extTablesStaticSqlFile);
        }
    }
}
