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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extensionmanager\Controller\UploadExtensionFileController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Update from TER controller test
 */
#[AllowMockObjectsWithoutExpectations]
final class UploadExtensionFileControllerTest extends UnitTestCase
{
    /**
     * @return array The test data for getExtensionFromZipFileExtractsExtensionKey
     */
    public static function getExtensionFromZipFileExtractsExtensionKeyDataProvider(): array
    {
        return [
            'simple' => [
                'extension_0.0.0.zip',
                'extension',
            ],
            'underscore in extension name' => [
                'extension_key_10.100.356.zip',
                'extension_key',
            ],
            'camel case file name' => [
                'extensionName_1.1.1.zip',
                'extensionname',
            ],
            'version with dashes' => [
                'extension_1-2-3.zip',
                'extension',
            ],
            'characters after version' => [
                'extension_1-2-3(1).zip',
                'extension',
            ],
            'characters after version with extra space' => [
                'extension_1-2-3 (1).zip',
                'extension',
            ],
            'no version' => [
                'extension.zip',
                'extension',
            ],
        ];
    }

    #[DataProvider('getExtensionFromZipFileExtractsExtensionKeyDataProvider')]
    #[Test]
    public function getExtensionKeyFromFileNameExtractsExtensionKey(string $filename, string $expectedKey): void
    {
        $subject = $this->getAccessibleMock(UploadExtensionFileController::class, null, [], '', false);
        self::assertEquals($expectedKey, $subject->_call('getExtensionKeyFromFileName', $filename));
    }

    public static function getVersionFromFileNameDataProvider(): array
    {
        return [
            'key and version' => ['news_12.3.0.zip', '12.3.0'],
            'key with digits' => ['ws_t3x_1.2.3.zip', '1.2.3'],
            'dashes between the numbers' => ['news_12-3-0.zip', '12.3.0'],
            'upper case' => ['News_1.2.3.ZIP', '1.2.3'],
            'no version' => ['news.zip', ''],
            'two numbers only' => ['news_1.2.zip', ''],
        ];
    }

    /**
     * The archive name is the only place a manually uploaded archive carries its
     * version when composer.json declares none; TER names its archives the same way.
     */
    #[DataProvider('getVersionFromFileNameDataProvider')]
    #[Test]
    public function getVersionFromFileNameReadsTheVersionTheArchiveIsNamedWith(string $filename, string $expectedVersion): void
    {
        $subject = $this->getAccessibleMock(UploadExtensionFileController::class, null, [], '', false);
        self::assertSame($expectedVersion, $subject->_call('getVersionFromFileName', $filename));
    }
}
