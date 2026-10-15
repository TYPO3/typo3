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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Driver;

use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\Driver\FilesystemAdapterFactoryInterface;
use TYPO3\CMS\Core\Resource\Driver\FlysystemDriver;
use TYPO3\CMS\Core\Resource\Exception\InvalidConfigurationException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FlysystemDriverTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function driverWithoutAdapterFactoryIsNotConfigured(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(1792093601);
        new FlysystemDriver()->processConfiguration();
    }

    #[Test]
    public function adapterFactoryCreatesTheAdapterFromTheStorageConfiguration(): void
    {
        $baseDirectory = $this->instancePath . '/flysystem-driver';
        mkdir($baseDirectory);
        file_put_contents($baseDirectory . '/file.txt', 'content');
        $adapterFactory = new class implements FilesystemAdapterFactoryInterface {
            public function createAdapter(array $configuration): FilesystemAdapter
            {
                return new LocalFilesystemAdapter($configuration['path']);
            }
        };
        $subject = new FlysystemDriver(['path' => $baseDirectory], $adapterFactory);
        $subject->processConfiguration();
        self::assertSame('content', $subject->getFileContents('/file.txt'));
        GeneralUtility::rmdir($baseDirectory, true);
    }
}
