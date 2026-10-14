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

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Event\FileStorageConfigurationChangedEvent;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileStorageConfigurationException;

/**
 * Reads the file storage definitions from config/system/file-storages.yaml.
 *
 * The file is optional: without it, or with an empty "storages" map, one local
 * storage for the fileadmin directory with identifier 1 is assumed. Entries are
 * returned normalized, with every key present and defaults applied.
 *
 * Reading is tolerant: an entry that cannot be used is logged and made
 * available as an offline storage, unknown options are logged and ignored,
 * so that one broken entry does not stop every file access. Syntax errors and
 * an invalid structure of the file are not tolerated. Writing validates strictly.
 */
#[Autoconfigure(public: true)]
final class FileStorageConfiguration
{
    public const string CONFIG_FILE_NAME = 'file-storages.yaml';

    private const string CACHE_IDENTIFIER_PREFIX = 'file-storages_';

    private const array DEFAULTS = [
        'name' => null,
        'driver' => 'Local',
        'default' => false,
        'browsable' => true,
        'public' => true,
        'writable' => true,
        'online' => true,
        'autoExtractMetadata' => true,
        'processingFolder' => '',
        'allowedFileExtensions' => [],
        'configuration' => [],
    ];

    /**
     * @var array<positive-int, array<string, mixed>>|null
     */
    private ?array $storages = null;

    public function __construct(
        #[Autowire('%env(TYPO3:configPath)%')]
        private readonly string $configPath,
        private readonly YamlFileLoader $yamlFileLoader,
        private readonly LoggerInterface $logger,
        #[Autowire(service: 'cache.core')]
        private readonly PhpFrontend $cache,
    ) {}

    public function getConfigFileName(): string
    {
        return $this->configPath . '/system/' . self::CONFIG_FILE_NAME;
    }

    public function isFilePresent(): bool
    {
        return is_file($this->getConfigFileName());
    }

    /**
     * All configured storages, normalized and keyed by identifier, or the
     * implicit default storage when the file does not define any.
     *
     * @return array<positive-int, array<string, mixed>>
     * @throws InvalidFileStorageConfigurationException
     */
    public function getAll(): array
    {
        if ($this->storages === null) {
            $this->storages = $this->isFilePresent() ? $this->getAllFromFile() : $this->getImplicitDefault();
        }
        return $this->storages;
    }

    /**
     * The normalized result is kept in the core cache, keyed by the content of
     * the file, so a deployed change of the file is picked up without flushing
     * caches. Changes of imported files and environment variables are not.
     *
     * @return array<positive-int, array<string, mixed>>
     * @throws InvalidFileStorageConfigurationException
     */
    private function getAllFromFile(): array
    {
        $cacheIdentifier = self::CACHE_IDENTIFIER_PREFIX . hash('xxh3', (string)file_get_contents($this->getConfigFileName()));
        $storages = $this->cache->require($cacheIdentifier);
        if (is_array($storages)) {
            return $storages;
        }
        $storages = $this->load()['storages'] ?? [];
        if (!is_array($storages)) {
            throw new InvalidFileStorageConfigurationException(
                'The key "storages" in ' . $this->getConfigFileName() . ' must be a map of storage identifiers to storage definitions.',
                1789862401
            );
        }
        $storages = $storages === [] ? $this->getImplicitDefault() : $this->normalizeAll($storages, false);
        $this->cache->set($cacheIdentifier, 'return ' . var_export($storages, true) . ';');
        return $storages;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(int $identifier): ?array
    {
        return $this->getAll()[$identifier] ?? null;
    }

    /**
     * The storage assumed when no file exists: the fileadmin directory below
     * the public path, local driver, default storage, identifier 1.
     *
     * @return array<positive-int, array<string, mixed>>
     */
    public function getImplicitDefault(): array
    {
        $fileadminDirectory = trim((string)($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] ?? 'fileadmin/'), '/');
        if ($fileadminDirectory === '') {
            $fileadminDirectory = 'fileadmin';
        }
        return [
            1 => $this->normalize(1, [
                'name' => 'fileadmin',
                'default' => true,
                'configuration' => [
                    'basePath' => Environment::getRelativePublicPath() . $fileadminDirectory . '/',
                ],
            ]),
        ];
    }

    /**
     * The parsed file with placeholders resolved and imports processed.
     * Only the "storages" key is evaluated by TYPO3.
     */
    public function load(): array
    {
        return $this->yamlFileLoader->load($this->getConfigFileName(), YamlFileLoader::PROCESS_IMPORTS | YamlFileLoader::PROCESS_PLACEHOLDERS | YamlFileLoader::ALLOW_EMPTY_FILE);
    }

    /**
     * The file as written, without placeholder resolution and without imports.
     * This is the base a writer has to work on, so that "%env()%" values survive.
     */
    public function loadRaw(): array
    {
        if (!$this->isFilePresent()) {
            return [];
        }
        return $this->yamlFileLoader->load($this->getConfigFileName(), YamlFileLoader::ALLOW_EMPTY_FILE);
    }

    /**
     * Brings one storage entry into the canonical shape and validates the
     * values that do not depend on other storages.
     *
     * @return array<string, mixed>
     * @throws InvalidFileStorageConfigurationException
     */
    public function normalize(int $identifier, array $entry): array
    {
        if ($identifier < 1) {
            throw new InvalidFileStorageConfigurationException(
                'File storage identifiers must be positive integers, "' . $identifier . '" given.',
                1789862402
            );
        }
        $unknownKeys = array_diff(array_keys($entry), array_keys(self::DEFAULTS));
        if ($unknownKeys !== []) {
            throw new InvalidFileStorageConfigurationException(
                'File storage ' . $identifier . ' defines unknown option(s) "' . implode('", "', $unknownKeys) . '". Driver options belong below the "configuration" key.',
                1789862403
            );
        }
        $entry = array_replace(self::DEFAULTS, $entry);
        $driver = trim((string)$entry['driver']);
        if ($driver === '') {
            throw new InvalidFileStorageConfigurationException(
                'File storage ' . $identifier . ' has an empty "driver".',
                1789862405
            );
        }
        if (!is_array($entry['configuration'])) {
            throw new InvalidFileStorageConfigurationException(
                'The "configuration" of file storage ' . $identifier . ' must be a map of driver options.',
                1789862406
            );
        }
        $name = trim((string)($entry['name'] ?? ''));
        return [
            'identifier' => $identifier,
            'name' => $name !== '' ? $name : 'Storage ' . $identifier,
            'driver' => $driver,
            'default' => $this->toBool($identifier, 'default', $entry['default']),
            'browsable' => $this->toBool($identifier, 'browsable', $entry['browsable']),
            'public' => $this->toBool($identifier, 'public', $entry['public']),
            'writable' => $this->toBool($identifier, 'writable', $entry['writable']),
            'online' => $this->toBool($identifier, 'online', $entry['online']),
            'autoExtractMetadata' => $this->toBool($identifier, 'autoExtractMetadata', $entry['autoExtractMetadata']),
            'processingFolder' => trim((string)$entry['processingFolder']),
            'allowedFileExtensions' => $this->toFileExtensionList($identifier, $entry['allowedFileExtensions']),
            'configuration' => $entry['configuration'],
        ];
    }

    /**
     * Validates the rules spanning all storages: exactly one default.
     *
     * @param array<int, array<string, mixed>> $storages normalized entries keyed by identifier
     * @throws InvalidFileStorageConfigurationException
     */
    public function validateSet(array $storages): void
    {
        $defaults = [];
        foreach ($storages as $identifier => $storage) {
            if ($storage['default']) {
                $defaults[] = $identifier;
            }
        }
        if (count($defaults) > 1) {
            throw new InvalidFileStorageConfigurationException(
                'File storages ' . implode(', ', $defaults) . ' are all marked as default. Exactly one storage may be the default storage.',
                1789862408
            );
        }
        if ($defaults === [] && count($storages) > 1) {
            throw new InvalidFileStorageConfigurationException(
                'None of the file storages ' . implode(', ', array_keys($storages)) . ' is marked as default. Set "default: true" on one of them.',
                1789862409
            );
        }
    }

    public function flush(): void
    {
        $this->storages = null;
    }

    #[AsEventListener]
    public function onConfigurationChanged(FileStorageConfigurationChangedEvent $event): void
    {
        $this->flush();
    }

    /**
     * Normalizes and validates a complete set of storage definitions as read
     * from the file. A single storage is always the default storage.
     *
     * With $strict disabled, as when reading, problems are logged instead of
     * thrown: unusable entries become offline storages, unknown options are
     * ignored and a missing or duplicate default flag is resolved.
     *
     * @return array<positive-int, array<string, mixed>>
     * @throws InvalidFileStorageConfigurationException
     */
    public function normalizeAll(array $storages, bool $strict = true): array
    {
        $normalized = [];
        foreach ($storages as $identifier => $entry) {
            // The structure of the file is not tolerated, like syntax errors
            if (!is_int($identifier) && !preg_match('/^[1-9][0-9]*$/', $identifier)) {
                throw new InvalidFileStorageConfigurationException(
                    'File storage identifiers must be positive integers, "' . $identifier . '" given.',
                    1789862410
                );
            }
            if (!is_array($entry)) {
                throw new InvalidFileStorageConfigurationException(
                    'File storage ' . $identifier . ' must be a map of options.',
                    1789862411
                );
            }
            $identifier = (int)$identifier;
            if ($strict) {
                $normalized[$identifier] = $this->normalize($identifier, $entry);
                continue;
            }
            $unknownKeys = array_diff(array_keys($entry), array_keys(self::DEFAULTS));
            if ($unknownKeys !== []) {
                $this->logger->warning('File storage {identifier} defines unknown option(s) "{options}" in {file}, they are ignored.', [
                    'identifier' => $identifier,
                    'options' => implode('", "', $unknownKeys),
                    'file' => $this->getConfigFileName(),
                ]);
                $entry = array_intersect_key($entry, self::DEFAULTS);
            }
            try {
                $this->assertPlaceholdersResolved($identifier, $entry);
                $normalized[$identifier] = $this->normalize($identifier, $entry);
            } catch (InvalidFileStorageConfigurationException $e) {
                $this->logger->error('{message} The storage is offline.', ['message' => $e->getMessage()]);
                $normalized[$identifier] = $this->createUnavailableEntry($identifier, $entry);
            }
        }
        if (count($normalized) === 1) {
            $normalized[array_key_first($normalized)]['default'] = true;
        }
        ksort($normalized);
        if ($strict) {
            $this->validateSet($normalized);
            return $normalized;
        }
        try {
            $this->validateSet($normalized);
        } catch (InvalidFileStorageConfigurationException $e) {
            $normalized = $this->resolveDefault($normalized);
            $this->logger->error('{message} Storage {identifier} is used as default storage.', [
                'message' => $e->getMessage(),
                'identifier' => array_key_first(array_filter($normalized, static fn(array $storage): bool => $storage['default'])),
            ]);
        }
        return $normalized;
    }

    /**
     * An "%env()%" placeholder whose variable is not set stays in the value as written.
     */
    private function assertPlaceholdersResolved(int $identifier, array $entry): void
    {
        $unresolved = [];
        array_walk_recursive($entry, static function (mixed $value) use (&$unresolved): void {
            if (is_string($value) && preg_match_all('/%env\(([^)]+)\)%/', $value, $matches)) {
                array_push($unresolved, ...$matches[1]);
            }
        });
        if ($unresolved !== []) {
            throw new InvalidFileStorageConfigurationException(
                'File storage ' . $identifier . ' uses the environment variable(s) "' . implode('", "', array_unique($unresolved)) . '", which are not set.',
                1791443854
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function createUnavailableEntry(int $identifier, array $entry): array
    {
        $name = is_scalar($entry['name'] ?? null) ? trim((string)$entry['name']) : '';
        $driver = is_string($entry['driver'] ?? null) && trim($entry['driver']) !== '' ? trim($entry['driver']) : self::DEFAULTS['driver'];
        return [
            'identifier' => $identifier,
            'name' => $name !== '' ? $name : 'Storage ' . $identifier,
            'driver' => $driver,
            'default' => filter_var($entry['default'] ?? false, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'browsable' => true,
            'public' => false,
            'writable' => false,
            'online' => false,
            'autoExtractMetadata' => false,
            'processingFolder' => '',
            'allowedFileExtensions' => [],
            'configuration' => [],
        ];
    }

    /**
     * Keeps the first storage marked as default, or marks the first storage.
     *
     * @param array<positive-int, array<string, mixed>> $storages
     * @return array<positive-int, array<string, mixed>>
     */
    private function resolveDefault(array $storages): array
    {
        $defaultIdentifier = array_key_first(array_filter($storages, static fn(array $storage): bool => $storage['default'])) ?? array_key_first($storages);
        foreach ($storages as $identifier => $storage) {
            $storages[$identifier]['default'] = $identifier === $defaultIdentifier;
        }
        return $storages;
    }

    /**
     * Accepts a list or a comma-separated string, as known from TCA.
     *
     * @return list<non-empty-string>
     * @throws InvalidFileStorageConfigurationException
     */
    private function toFileExtensionList(int $identifier, mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        if (!is_array($value) || !array_is_list($value) || array_filter($value, static fn(mixed $item): bool => !is_string($item)) !== []) {
            throw new InvalidFileStorageConfigurationException(
                'The option "allowedFileExtensions" of file storage ' . $identifier . ' must be a list of file extensions.',
                1792160417
            );
        }
        return array_values(array_unique(array_filter(array_map(
            static fn(string $fileExtension): string => strtolower(trim($fileExtension)),
            $value
        ))));
    }

    private function toBool(int $identifier, string $option, mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($bool === null) {
            throw new InvalidFileStorageConfigurationException(
                'The option "' . $option . '" of file storage ' . $identifier . ' must be a boolean, "' . (is_scalar($value) ? $value : gettype($value)) . '" given.',
                1789862412
            );
        }
        return $bool;
    }
}
