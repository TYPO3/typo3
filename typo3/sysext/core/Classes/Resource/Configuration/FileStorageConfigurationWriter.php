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

namespace TYPO3\CMS\Core\Resource\Configuration;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Resource\Event\FileStorageConfigurationChangedEvent;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileStorageConfigurationException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Writes config/system/file-storages.yaml.
 *
 * Changes are applied to the file as written on disk, not to the resolved
 * configuration, so "%env()%" placeholders and "imports" of untouched
 * entries are preserved. Callers pass compact entries; keys that carry the
 * default value are omitted from the file.
 *
 * Every change is validated against the resolved configuration, imports
 * included, written atomically and verified after writing. A change that
 * would leave an invalid configuration behind is rolled back.
 *
 * The file is part of the deployment. Writing it is meant for command line
 * tools, upgrade wizards and set up code, not for regular web requests.
 */
#[Autoconfigure(public: true)]
final readonly class FileStorageConfigurationWriter
{
    private const array KEY_ORDER = ['name', 'driver', 'default', 'browsable', 'public', 'writable', 'online', 'autoExtractMetadata', 'processingFolder', 'allowedFileExtensions', 'configuration'];

    public function __construct(
        private FileStorageConfiguration $configuration,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Adds or replaces the storage with the given identifier. When no storages
     * are defined yet, the implicit default storage is written along.
     *
     * @throws InvalidFileStorageConfigurationException
     */
    public function write(int $identifier, array $entry): void
    {
        $this->apply([$identifier => $entry]);
    }

    /**
     * Removes the storage with the given identifier from the file. Storages of
     * imported files are refused, unknown identifiers are ignored.
     *
     * @throws InvalidFileStorageConfigurationException
     */
    public function remove(int $identifier): void
    {
        $this->apply([], [$identifier]);
    }

    /**
     * Adds, replaces and removes several storages in one validated write, e.g.
     * to move the default flag from one storage to another.
     *
     * @param array<int, array<string, mixed>> $entries compact entries keyed by identifier
     * @param list<int> $removals identifiers of storages to remove
     * @throws InvalidFileStorageConfigurationException
     */
    public function apply(array $entries, array $removals = []): void
    {
        $raw = $this->configuration->loadRaw();
        $rawStorages = $this->assertStorageMap($raw['storages'] ?? []);
        $resolvedStorages = $this->loadResolvedStorages();
        // Removing a storage that is not defined is a no-op
        $removals = array_values(array_filter($removals, static fn(int $identifier): bool => isset($resolvedStorages[$identifier])));
        if ($entries === [] && $removals === []) {
            return;
        }
        $this->assertDefinedInMainFile(array_merge(array_keys($entries), $removals), $rawStorages, $resolvedStorages);
        if ($resolvedStorages === []) {
            // The file replaces the implicit default entirely, so the default has to be written out first
            foreach ($this->configuration->getImplicitDefault() as $implicitIdentifier => $implicitEntry) {
                unset($implicitEntry['identifier']);
                $rawStorages[$implicitIdentifier] = $this->compact($implicitEntry);
                $resolvedStorages[$implicitIdentifier] = $implicitEntry;
            }
        }
        foreach ($removals as $identifier) {
            unset($rawStorages[$identifier], $resolvedStorages[$identifier]);
        }
        foreach ($entries as $identifier => $entry) {
            $rawStorages[$identifier] = $this->compact($entry);
            $resolvedStorages[$identifier] = $entry;
        }
        $expected = $this->configuration->normalizeAll($resolvedStorages);
        $raw['storages'] = $this->sort($rawStorages);
        $this->dumpAndVerify($raw, $expected, array_keys($entries));
        $changed = array_merge(array_keys($entries), $removals);
        $this->eventDispatcher->dispatch(new FileStorageConfigurationChangedEvent(count($changed) === 1 ? $changed[0] : null));
    }

    /**
     * Replaces all storage definitions. Refuses to overwrite a file that
     * already defines storages, imports included, so a migration cannot
     * destroy manual work.
     *
     * @param array<int, array<string, mixed>> $storages compact entries keyed by identifier
     * @throws InvalidFileStorageConfigurationException
     */
    public function writeAll(array $storages): void
    {
        if ($this->loadResolvedStorages() !== []) {
            throw new InvalidFileStorageConfigurationException(
                $this->configuration->getConfigFileName() . ' already defines file storages and is not overwritten.',
                1789862414
            );
        }
        $raw = $this->configuration->loadRaw();
        $compacted = [];
        foreach ($storages as $identifier => $entry) {
            $compacted[$identifier] = $this->compact($entry);
        }
        $expected = $this->configuration->normalizeAll($storages);
        $raw['storages'] = $this->sort($compacted);
        $this->dumpAndVerify($raw, $expected, array_keys($storages));
        $this->eventDispatcher->dispatch(new FileStorageConfigurationChangedEvent(null));
    }

    /**
     * @return array<int, array<string, mixed>> the storages of the file with imports and placeholders processed
     */
    private function loadResolvedStorages(): array
    {
        if (!$this->configuration->isFilePresent()) {
            return [];
        }
        return $this->assertStorageMap($this->configuration->load()['storages'] ?? []);
    }

    private function assertStorageMap(mixed $storages): array
    {
        if (!is_array($storages)) {
            throw new InvalidFileStorageConfigurationException(
                'The key "storages" in ' . $this->configuration->getConfigFileName() . ' must be a map of storage identifiers to storage definitions.',
                1789862413
            );
        }
        return $storages;
    }

    /**
     * A storage that comes from an imported file is changed in that file. Writing
     * it into the main file would merge both definitions key by key.
     *
     * @param list<int> $identifiers
     */
    private function assertDefinedInMainFile(array $identifiers, array $rawStorages, array $resolvedStorages): void
    {
        foreach ($identifiers as $identifier) {
            if (isset($resolvedStorages[$identifier]) && !isset($rawStorages[$identifier])) {
                throw new InvalidFileStorageConfigurationException(
                    'File storage ' . $identifier . ' is defined in a file imported by ' . $this->configuration->getConfigFileName() . '. Change it in the imported file.',
                    1791443851
                );
            }
        }
    }

    private function compact(array $entry): array
    {
        $compact = [];
        foreach (self::KEY_ORDER as $key) {
            if (!array_key_exists($key, $entry)) {
                continue;
            }
            $value = $entry[$key];
            $isDefaultValue = match ($key) {
                'name' => $value === null || $value === '',
                'driver' => $value === 'Local',
                'default' => $value === false,
                'browsable', 'public', 'writable', 'online', 'autoExtractMetadata' => $value === true,
                'processingFolder' => $value === '' || $value === null,
                'allowedFileExtensions' => $value === [] || $value === '' || $value === null,
                'configuration' => $value === [] || $value === null,
            };
            if (!$isDefaultValue) {
                $compact[$key] = $value;
            }
        }
        return $compact;
    }

    private function sort(array $storages): array
    {
        ksort($storages);
        return $storages;
    }

    /**
     * Writes the file and reads it back. Imports of the file are merged into
     * the written entries key by key, so the resolved result can differ from
     * what was written. If it does, or if it is invalid, the previous file is
     * restored.
     *
     * @param array<int, array<string, mixed>> $expected normalized storages
     * @param list<int> $writtenIdentifiers
     */
    private function dumpAndVerify(array $raw, array $expected, array $writtenIdentifiers): void
    {
        $fileName = $this->configuration->getConfigFileName();
        $previousContent = is_file($fileName) ? file_get_contents($fileName) : false;
        if (isset($raw['imports'])) {
            $imports = $raw['imports'];
            unset($raw['imports']);
            $raw['imports'] = $imports;
        }
        $this->writeAtomically($fileName, Yaml::dump($raw, 99, 2));
        $this->configuration->flush();
        try {
            $actual = $this->configuration->getAll();
            if (array_keys($actual) !== array_keys($expected)) {
                throw new InvalidFileStorageConfigurationException(
                    'After writing ' . $fileName . ', the storages ' . implode(', ', array_keys($actual)) . ' are defined instead of ' . implode(', ', array_keys($expected)) . '.',
                    1791443852
                );
            }
            foreach ($writtenIdentifiers as $identifier) {
                foreach (['driver', 'default'] as $key) {
                    if ($actual[$identifier][$key] !== $expected[$identifier][$key]) {
                        throw new InvalidFileStorageConfigurationException(
                            'After writing ' . $fileName . ', the option "' . $key . '" of file storage ' . $identifier . ' is overridden by an imported file.',
                            1791443853
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            if ($previousContent === false) {
                @unlink($fileName);
            } else {
                $this->writeAtomically($fileName, $previousContent);
            }
            $this->configuration->flush();
            throw $e;
        }
    }

    /**
     * Writes into a temporary file next to the target and renames it, so that
     * a parallel request never reads a partially written file.
     */
    private function writeAtomically(string $fileName, string $content): void
    {
        GeneralUtility::mkdir_deep(dirname($fileName));
        $temporaryFileName = $fileName . '.' . bin2hex(random_bytes(8)) . '.tmp';
        if (!GeneralUtility::writeFile($temporaryFileName, $content, true) || !rename($temporaryFileName, $fileName)) {
            @unlink($temporaryFileName);
            throw new InvalidFileStorageConfigurationException(
                'The file ' . $fileName . ' could not be written.',
                1789862415
            );
        }
    }
}
