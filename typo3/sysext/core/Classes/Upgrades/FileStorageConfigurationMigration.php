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

namespace TYPO3\CMS\Core\Upgrades;

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfiguration;
use TYPO3\CMS\Core\Resource\Configuration\FileStorageConfigurationWriter;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileStorageConfigurationException;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Moves the rows of the former sys_file_storage table into
 * config/system/file-storages.yaml. Runs before the table is dropped.
 *
 * @internal This class is only meant to be used within EXT:core and is not part of the TYPO3 Core API.
 */
#[UpgradeWizard('fileStorageConfigurationMigration')]
final class FileStorageConfigurationMigration implements UpgradeWizardInterface, ChattyInterface
{
    private const string TABLE_NAME = 'sys_file_storage';
    private const string RENAMED_TABLE_NAME = 'zzz_deleted_sys_file_storage';

    private OutputInterface $output;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly FileStorageConfiguration $configuration,
        private readonly FileStorageConfigurationWriter $writer,
        private readonly FlexFormTools $flexFormTools,
        private readonly DriverRegistry $driverRegistry,
    ) {}

    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    public function getTitle(): string
    {
        return 'Move file storage records to ' . $this->getRelativeConfigFileName();
    }

    public function getDescription(): string
    {
        return 'File storages are configured in ' . $this->getRelativeConfigFileName() . ' instead of the'
            . ' database table sys_file_storage. This wizard writes the existing records into that file.'
            . ' An installation with only the auto-created fileadmin storage needs no file and is skipped.'
            . ' The file is part of your deployment: run the wizard where the file can be committed,'
            . ' move credentials to %env()% placeholders and deploy the file to every environment.'
            . ' If the file exists already, the wizard reports storages of this database that are missing in it.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        $tableName = $this->getSourceTableName();
        if ($tableName === null) {
            return false;
        }
        $rows = $this->fetchRows($tableName);
        if ($rows === []) {
            return false;
        }
        try {
            $configuredStorages = $this->getConfiguredStorages();
        } catch (\Throwable) {
            // An unreadable file is reported when the wizard is executed
            return true;
        }
        if ($configuredStorages !== []) {
            // A file exists already, e.g. committed from another environment. The wizard stays
            // open as long as storages of this database are missing in it.
            return array_diff_key($rows, $configuredStorages) !== [];
        }
        return !$this->equalsImplicitDefault($rows);
    }

    public function executeUpdate(): bool
    {
        $tableName = $this->getSourceTableName();
        if ($tableName === null) {
            return true;
        }
        $rows = $this->fetchRows($tableName);
        try {
            $configuredStorages = $this->getConfiguredStorages();
        } catch (\Throwable $e) {
            $this->output->writeln('<error>' . $this->getRelativeConfigFileName() . ' cannot be read: ' . $e->getMessage() . '</error>');
            return false;
        }
        if ($configuredStorages !== []) {
            $missing = array_diff_key($rows, $configuredStorages);
            if ($missing === []) {
                return true;
            }
            foreach ($missing as $uid => $row) {
                $this->output->writeln('<error>Storage ' . $uid . ' "' . $row['name'] . '" exists in the database but not in ' . $this->getRelativeConfigFileName() . '.</error>');
            }
            $this->output->writeln('The file is not changed. Add the storages to it, or remove them from the database if they are not used any more, and run the wizard again.');
            return false;
        }
        if ($rows === [] || $this->equalsImplicitDefault($rows)) {
            return true;
        }
        $storages = [];
        foreach ($rows as $uid => $row) {
            $storages[$uid] = $this->convertRow($row);
        }
        $storages = $this->ensureSingleDefault($storages);
        foreach ($storages as $uid => $entry) {
            if (!$this->driverRegistry->driverExists($entry['driver'])) {
                $this->output->writeln('Storage ' . $uid . ' "' . $entry['name'] . '" uses the driver "' . $entry['driver'] . '", which is not registered. The entry is written anyway and stays unavailable until the driver is installed.');
            }
            if ($entry['driver'] !== 'Local' && $entry['configuration'] !== []) {
                $this->output->writeln('<comment>Storage ' . $uid . ' "' . $entry['name'] . '" uses the driver "' . $entry['driver'] . '". Its driver configuration is written as is and may contain credentials. Replace them with %env()% placeholders before committing the file.</comment>');
            }
            if (!$entry['online']) {
                $this->output->writeln('<comment>Storage ' . $uid . ' "' . $entry['name'] . '" was marked offline, possibly after a temporary error. It is written with "online: false"; remove that line if the storage is available.</comment>');
            }
            if (trim((string)($rows[$uid]['description'] ?? '')) !== '') {
                $this->output->writeln('The description of storage ' . $uid . ' "' . $entry['name'] . '" is not part of the file: ' . trim((string)$rows[$uid]['description']));
            }
        }
        try {
            $this->writer->writeAll($storages);
        } catch (InvalidFileStorageConfigurationException $e) {
            $this->output->writeln('<error>' . $e->getMessage() . '</error>');
            return false;
        }
        $this->output->writeln('Wrote ' . count($storages) . ' storage(s) to ' . $this->configuration->getConfigFileName() . '. Commit the file and deploy it to every environment before the table ' . $tableName . ' is removed with the database analyzer.');
        return true;
    }

    /**
     * Exactly one storage is the default: the former table did not enforce that.
     *
     * @param array<int, array<string, mixed>> $storages
     * @return array<int, array<string, mixed>>
     */
    private function ensureSingleDefault(array $storages): array
    {
        $defaults = array_keys(array_filter($storages, static fn(array $entry): bool => $entry['default']));
        if ($defaults === [] && count($storages) > 1) {
            $firstUid = array_key_first($storages);
            $storages[$firstUid]['default'] = true;
            $this->output->writeln('No storage was marked as default, storage ' . $firstUid . ' is now the default storage.');
        } elseif (count($defaults) > 1) {
            $keptUid = array_shift($defaults);
            foreach ($defaults as $uid) {
                $storages[$uid]['default'] = false;
            }
            $this->output->writeln('Storages ' . $keptUid . ', ' . implode(', ', $defaults) . ' were all marked as default. Storage ' . $keptUid . ' stays the default storage.');
        }
        return $storages;
    }

    /**
     * @return array<int, mixed> the storages defined by the file, imports included
     */
    private function getConfiguredStorages(): array
    {
        if (!$this->configuration->isFilePresent()) {
            return [];
        }
        $storages = $this->configuration->load()['storages'] ?? [];
        return is_array($storages) ? $storages : [];
    }

    /**
     * @return array<string, mixed> a compact entry for the writer
     */
    private function convertRow(array $row): array
    {
        $driver = (string)$row['driver'];
        $configuration = $this->convertConfiguration((string)($row['configuration'] ?? ''));
        if ($driver === 'Local') {
            $configuration = $this->convertLocalConfiguration($configuration);
        }
        $name = trim((string)$row['name']);
        return [
            'name' => $name,
            'driver' => $driver,
            'default' => (bool)$row['is_default'],
            'browsable' => (bool)$row['is_browsable'],
            'public' => (bool)$row['is_public'],
            'writable' => (bool)$row['is_writable'],
            'online' => (bool)$row['is_online'],
            'autoExtractMetadata' => (bool)$row['auto_extract_metadata'],
            'processingFolder' => trim((string)($row['processingfolder'] ?? '')),
            'configuration' => $configuration,
        ];
    }

    private function convertConfiguration(string $flexFormXml): array
    {
        if (trim($flexFormXml) === '') {
            return [];
        }
        return $this->flexFormTools->convertFlexFormContentToArray($flexFormXml);
    }

    /**
     * Local base paths were relative to the public path when "pathType" was
     * "relative"; the file knows project-relative and absolute paths only.
     */
    private function convertLocalConfiguration(array $configuration): array
    {
        $basePath = (string)($configuration['basePath'] ?? '');
        if (($configuration['pathType'] ?? '') === 'relative') {
            $basePath = Environment::getRelativePublicPath() . ltrim($basePath, '/');
        } elseif (PathUtility::isAbsolutePath($basePath)) {
            $projectPath = Environment::getProjectPath() . '/';
            if (str_starts_with($basePath, $projectPath)) {
                $basePath = substr($basePath, strlen($projectPath));
            }
        }
        if ($basePath !== '') {
            $configuration['basePath'] = rtrim($basePath, '/') . '/';
        }
        unset($configuration['pathType']);
        if (isset($configuration['caseSensitive'])) {
            $configuration['caseSensitive'] = (bool)$configuration['caseSensitive'];
        }
        if (($configuration['baseUri'] ?? '') === '') {
            unset($configuration['baseUri']);
        }
        return $configuration;
    }

    private function equalsImplicitDefault(array $rows): bool
    {
        if (count($rows) !== 1 || array_key_first($rows) !== 1) {
            return false;
        }
        $entry = $this->convertRow(reset($rows));
        return $entry['driver'] === 'Local'
            && $entry['browsable'] && $entry['public'] && $entry['writable'] && $entry['online'] && $entry['autoExtractMetadata']
            && $entry['processingFolder'] === ''
            && ($entry['configuration']['basePath'] ?? null) === $this->getImplicitDefaultBasePath()
            && !isset($entry['configuration']['baseUri'])
            && $this->caseSensitivityEqualsDetection($entry['configuration']);
    }

    /**
     * The implicit storage detects the case sensitivity at runtime. A record whose
     * stored value differs, e.g. created on another file system, has to keep it,
     * as the identifiers in sys_file were written with it.
     */
    private function caseSensitivityEqualsDetection(array $configuration): bool
    {
        if (!isset($configuration['caseSensitive'])) {
            return true;
        }
        try {
            $driver = new LocalDriver(['basePath' => $configuration['basePath']]);
            $driver->processConfiguration();
            return $driver->isCaseSensitiveFileSystem() === (bool)$configuration['caseSensitive'];
        } catch (\Throwable) {
            return false;
        }
    }

    private function getImplicitDefaultBasePath(): string
    {
        return $this->configuration->getImplicitDefault()[1]['configuration']['basePath'];
    }

    /**
     * @return array<int, array<string, mixed>> rows keyed by uid
     */
    private function fetchRows(string $tableName): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)))
            ->orderBy('uid')
            ->executeQuery();
        $rows = [];
        while ($row = $result->fetchAssociative()) {
            $rows[(int)$row['uid']] = $row;
        }
        return $rows;
    }

    /**
     * The table, or the table as renamed by the database analyzer when it was
     * removed before this wizard ran.
     */
    private function getSourceTableName(): ?string
    {
        $schemaManager = $this->connectionPool->getConnectionForTable(self::TABLE_NAME)->createSchemaManager();
        foreach ([self::TABLE_NAME, self::RENAMED_TABLE_NAME] as $tableName) {
            if ($schemaManager->tablesExist([$tableName])) {
                return $tableName;
            }
        }
        return null;
    }

    private function getRelativeConfigFileName(): string
    {
        $fileName = $this->configuration->getConfigFileName();
        $projectPath = Environment::getProjectPath() . '/';
        return str_starts_with($fileName, $projectPath) ? substr($fileName, strlen($projectPath)) : $fileName;
    }
}
