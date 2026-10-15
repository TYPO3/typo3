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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Driver\Fixtures;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\AbstractFlysystemDriver;
use TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException;
use TYPO3\CMS\Core\Utility\PathUtility;

final class LocalFlysystemDriver extends AbstractFlysystemDriver
{
    public ?CountingFilesystemAdapter $countingAdapter = null;

    protected function createFilesystemAdapter(): FilesystemAdapter
    {
        $basePath = (string)($this->configuration['basePath'] ?? '');
        if ($basePath === '') {
            throw new InvalidConfigurationException('Configuration must contain base path.', 1792093599);
        }
        if (!PathUtility::isAbsolutePath($basePath)) {
            $basePath = Environment::getProjectPath() . '/' . $basePath;
        }
        $this->countingAdapter = new CountingFilesystemAdapter(new LocalFilesystemAdapter($basePath, mimeTypeDetector: new FinfoMimeTypeDetector()));
        if ($this->configuration['generatesPublicUrls'] ?? false) {
            return new PublicUrlGeneratingAdapter($this->countingAdapter);
        }
        return $this->countingAdapter;
    }
}
