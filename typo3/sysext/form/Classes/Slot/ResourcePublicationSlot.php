<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Slot;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Slot for using FAL resources in public (frontend)
 *
 * @internal will be renamed at some point.
 */
class ResourcePublicationSlot implements SingletonInterface
{
    /**
     * @var list<string>
     */
    protected $fileIdentifiers = [];

    /**
     * @param ResourceStorage $storage
     * @param DriverInterface $driver
     * @param ResourceInterface $resource
     * @param bool $relativeToCurrentScript
     * @param array $urlData
     */
    public function onPreGeneratePublicUrl(
        ResourceStorage $storage,
        DriverInterface $driver,
        ResourceInterface $resource,
        bool $relativeToCurrentScript,
        array $urlData
    ): void {
        if (!$resource instanceof FileInterface
            || !$this->has($resource)
            || $storage->getDriverType() !== 'Local'
        ) {
            return;
        }
        $urlData['publicUrl'] = $this->getStreamUrl($resource);
    }

    public function add(FileInterface $resource): void
    {
        if ($this->has($resource)) {
            return;
        }
        $this->fileIdentifiers[] = $resource->getIdentifier();
    }

    public function has(FileInterface $resource): bool
    {
        return in_array($resource->getIdentifier(), $this->fileIdentifiers, true);
    }

    protected function getStreamUrl(ResourceInterface $resource): string
    {
        $queryParameterArray = ['eID' => 'dumpFile', 't' => ''];
        if ($resource instanceof File) {
            $queryParameterArray['f'] = $resource->getUid();
            $queryParameterArray['t'] = 'f';
        } elseif ($resource instanceof ProcessedFile) {
            $queryParameterArray['p'] = $resource->getUid();
            $queryParameterArray['t'] = 'p';
        }

        $queryParameterArray['token'] = GeneralUtility::hmac(implode('|', $queryParameterArray), 'resourceStorageDumpFile');
        $publicUrl = GeneralUtility::locationHeaderUrl(PathUtility::getAbsoluteWebPath(Environment::getPublicPath() . '/index.php'));
        $publicUrl .= '?' . http_build_query($queryParameterArray, '', '&', PHP_QUERY_RFC3986);
        return $publicUrl;
    }
}
