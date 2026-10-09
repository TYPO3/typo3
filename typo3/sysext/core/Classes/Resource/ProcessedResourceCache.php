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

namespace TYPO3\CMS\Core\Resource;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Service\ConfigurationService;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Finds and persists ProcessedResource instances - the non-FAL counterpart to
 * ProcessedFileRepository - without a database, keyed by the original resource's own
 * identifier plus a content hash of its current bytes rather than an absolute filesystem
 * path and modification time, which is unstable across a release-based deployment (see
 * unify-fal-and-system-resource-processing.md §3.6 for the rationale).
 *
 * Each original resource gets its own bounded subdirectory under
 * typo3temp/assets/images/system/, named by a hash of its identifier; variants inside it
 * are named by a hash of the task type and processing configuration, followed by the
 * original's content hash. A result using the original as-is is recorded by a small
 * JSON file of the same name instead. persistRender() removes sibling variants for the same
 * configuration before writing a new one, so a changed original never leaves its stale
 * render behind - the filesystem equivalent of what ProcessedFile::needsReprocessing()
 * achieves with a database row - and that same bounded directory is what lets a future
 * maintenance action reach exactly the non-FAL renders of one resource, rather than
 * requiring a blanket sweep of the whole typo3temp/assets/images/ tree.
 *
 * @internal Not part of TYPO3's public API, use FileProcessingService instead
 */
class ProcessedResourceCache
{
    private const string BASE_DIRECTORY = 'typo3temp/assets/images/system/';
    private const string USES_ORIGINAL_SUFFIX = '.original.json';

    /**
     * @var \WeakMap<SystemResourceInterface, string>|null
     */
    private ?\WeakMap $contentHashes = null;

    public function findOneByOriginalAndTaskTypeAndConfiguration(
        SystemResourceInterface $originalResource,
        string $taskType,
        array $configuration
    ): ProcessedResource {
        $variantPrefix = $this->getVariantPrefix($originalResource, $taskType, $configuration);
        if (is_file($variantPrefix . self::USES_ORIGINAL_SUFFIX)) {
            $properties = json_decode((string)file_get_contents($variantPrefix . self::USES_ORIGINAL_SUFFIX), true);
            return new ProcessedResource($this, $originalResource, $taskType, $configuration, null, false, is_array($properties) ? $properties : []);
        }
        $existingRenderPath = (glob($variantPrefix . '*') ?: [])[0] ?? null;
        return new ProcessedResource($this, $originalResource, $taskType, $configuration, $existingRenderPath);
    }

    /**
     * Records a processing result that uses the original as-is, together with the
     * properties its processor determined, so it is not processed again - the
     * counterpart to ProcessedFileRepository::add() storing such a result in the
     * database. A rendered variant needs no record, its file is found by its name.
     */
    public function add(ProcessedResource $processedResource): void
    {
        if (!$processedResource->usesOriginalFile()) {
            return;
        }
        $originalResource = $processedResource->getOriginalResource();
        GeneralUtility::mkdir_deep($this->getDirectoryForResource($originalResource));
        $markerPath = $this->getVariantPrefix($originalResource, $processedResource->getTaskIdentifier(), $processedResource->getProcessingConfiguration()) . self::USES_ORIGINAL_SUFFIX;
        $this->removeStaleVariants($originalResource, $this->getConfigurationHash($processedResource->getTaskIdentifier(), $processedResource->getProcessingConfiguration()), $markerPath);
        GeneralUtility::writeFile($markerPath, (string)json_encode($processedResource->getProperties()), true);
    }

    /**
     * Moves an already-complete local file (e.g. GraphicalFunctions::resize()'s own,
     * already-atomically-written, output) into this cache's bounded directory for the
     * given processed resource's original and configuration, removing stale sibling
     * variants for that same configuration first. Only ever called by
     * ProcessedResource::updateWithLocalFile() - callers go through that, not this
     * directly, so the resulting path only ever reaches this class and the
     * ProcessedResource instance it belongs to.
     */
    public function persistRender(ProcessedResource $processedResource, string $temporaryLocalFilePath): string
    {
        $originalResource = $processedResource->getOriginalResource();
        $directory = $this->getDirectoryForResource($originalResource);
        GeneralUtility::mkdir_deep($directory);

        $configurationHash = $this->getConfigurationHash($processedResource->getTaskIdentifier(), $processedResource->getProcessingConfiguration());
        $contentHash = $this->getContentHash($originalResource);
        $extension = strtolower(trim((string)(pathinfo($temporaryLocalFilePath)['extension'] ?? '')));
        $finalPath = $directory . $configurationHash . '-' . $contentHash . ($extension !== '' ? '.' . $extension : '');

        $this->removeStaleVariants($originalResource, $configurationHash, $finalPath);

        if (!@rename($temporaryLocalFilePath, $finalPath)) {
            // rename() is only guaranteed atomic within one filesystem. $temporaryLocalFilePath
            // can come from a processor's own temp directory under var/transient/, a different
            // mount than this directory under the public webroot in some deployments, where a
            // direct rename() fails (EXDEV) - fall back to copying into this same directory first,
            // so the rename that actually makes the render visible always stays on one filesystem.
            $sameFilesystemTemporaryPath = $directory . uniqid('_', true);
            if (!copy($temporaryLocalFilePath, $sameFilesystemTemporaryPath) || !rename($sameFilesystemTemporaryPath, $finalPath)) {
                @unlink($sameFilesystemTemporaryPath);
                throw new \RuntimeException(sprintf('Could not persist a rendered variant for "%s" to "%s".', $originalResource->getResourceIdentifier(), $finalPath), 1789916700);
            }
            @unlink($temporaryLocalFilePath);
        }
        return $finalPath;
    }

    private function removeStaleVariants(SystemResourceInterface $originalResource, string $configurationHash, string $currentPath): void
    {
        foreach (glob($this->getDirectoryForResource($originalResource) . $configurationHash . '-*') ?: [] as $staleVariant) {
            if ($staleVariant !== $currentPath) {
                @unlink($staleVariant);
            }
        }
    }

    private function getVariantPrefix(SystemResourceInterface $originalResource, string $taskType, array $configuration): string
    {
        return $this->getDirectoryForResource($originalResource) . $this->getConfigurationHash($taskType, $configuration) . '-' . $this->getContentHash($originalResource);
    }

    private function getContentHash(SystemResourceInterface $originalResource): string
    {
        $this->contentHashes ??= new \WeakMap();
        return $this->contentHashes[$originalResource] ??= $originalResource->getHash();
    }

    private function getDirectoryForResource(SystemResourceInterface $originalResource): string
    {
        return Environment::getPublicPath() . '/' . self::BASE_DIRECTORY . md5($originalResource->getResourceIdentifier()) . '/';
    }

    private function getConfigurationHash(string $taskType, array $configuration): string
    {
        return substr(md5($taskType . '.' . new ConfigurationService()->serialize($configuration)), 0, 10);
    }
}
