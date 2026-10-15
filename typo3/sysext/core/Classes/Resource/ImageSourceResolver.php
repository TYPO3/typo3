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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SystemResource\Exception\SystemResourceException;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Type\StaticResourceInterface;
use TYPO3\CMS\Core\SystemResource\Type\SystemResourceInterface;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Resolves a string or object image source - a path, a combined FAL identifier, a
 * sys_file(_reference) uid, an EXT:/PKG: system resource identifier, a system
 * resource object (e.g. the result of <f:resource>), or an already-resolved FAL
 * object - into a single processable resource, so consumers (the
 * Fluid image ViewHelpers, and eventually others) do not each carry their own
 * branching for "is this an EXT:/PKG: source or a FAL one".
 *
 * An EXT:/PKG: source resolves through SystemResourceFactory::createPublicResource(),
 * which already throws CanNotResolvePublicResourceException for a private or
 * undeclared path before anything reads the file - the publicness check happens at
 * resolution time, not after a stat() and getimagesize() have already run against a
 * private file. A system resource object is resolved again from its identifier,
 * so it passes the very same check. A plain path to a public file that is not
 * part of any configured file storage, e.g. below typo3temp/assets/, is resolved
 * as system resource as well. Everything else keeps resolving through
 * ResourceFactory::resolveFileObject(), unchanged.
 *
 * @internal Not part of TYPO3's public API
 */
#[Autoconfigure(public: true)]
final readonly class ImageSourceResolver
{
    public function __construct(
        private ResourceFactory $resourceFactory,
        private SystemResourceFactory $systemResourceFactory,
        private StorageRepository $storageRepository,
    ) {}

    public function resolve(string|int|object|null $source, bool $treatIdAsReference = false): ProcessableFileInterface
    {
        if ($source instanceof StaticResourceInterface && !$source instanceof FileInterface) {
            return $this->resolveSystemResource((string)$source);
        }
        if (is_string($source) && (str_starts_with($source, 'EXT:') || str_starts_with($source, 'PKG:'))) {
            return $this->resolveSystemResource($source);
        }
        if (is_string($source) && ($resource = $this->resolvePublicFileOutsideOfFileStorages($source)) !== null) {
            return $resource;
        }
        return $this->resourceFactory->resolveFileObject($source, $treatIdAsReference);
    }

    /**
     * Resolves a plain path to an existing public file that is not part of any
     * configured local file storage as system resource, or returns null.
     *
     * Only public paths of the project are considered, by default _assets/,
     * typo3temp/assets/ and uploads/ (see config/system/resources.php), and no
     * hidden files or files in hidden directories.
     */
    public function resolvePublicFileOutsideOfFileStorages(string $path): (SystemResourceInterface&ProcessableFileInterface)|null
    {
        if ($path === '' || str_contains($path, ':') || MathUtility::canBeInterpretedAsInteger($path)) {
            return null;
        }
        $publicPath = Environment::getPublicPath() . '/';
        $absolutePath = PathUtility::getCanonicalPath(str_starts_with($path, $publicPath) ? $path : $publicPath . ltrim($path, '/'));
        if (!str_starts_with($absolutePath, $publicPath) || !@is_file($absolutePath)) {
            return null;
        }
        $relativePath = substr($absolutePath, strlen($publicPath));
        foreach (explode('/', $relativePath) as $segment) {
            if (str_starts_with($segment, '.')) {
                // Processing reads the file server-side, so hidden files must not be reachable,
                // even if the web server would deny access to them
                return null;
            }
        }
        $localPath = $relativePath;
        if ($this->storageRepository->findBestMatchingStorageByLocalPath($localPath) > 0) {
            return null;
        }
        try {
            $resource = $this->systemResourceFactory->createPublicResource($relativePath);
        } catch (SystemResourceException) {
            return null;
        }
        return $resource instanceof SystemResourceInterface && $resource instanceof ProcessableFileInterface ? $resource : null;
    }

    private function resolveSystemResource(string $identifier): ProcessableFileInterface
    {
        $resource = $this->systemResourceFactory->createPublicResource($identifier);
        if (!$resource instanceof ProcessableFileInterface) {
            throw new \UnexpectedValueException(sprintf('System resource "%s" can not be processed.', $identifier), 1789915800);
        }
        return $resource;
    }
}
