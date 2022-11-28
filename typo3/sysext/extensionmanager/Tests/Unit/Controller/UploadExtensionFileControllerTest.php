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

use TYPO3\CMS\Extensionmanager\Controller\UploadExtensionFileController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Update from TER controller test
 */
class UploadExtensionFileControllerTest extends UnitTestCase
{
    /**
     * @return array The test data for getExtensionFromZipFileExtractsExtensionKey
     */
    public function getExtensionFromZipFileExtractsExtensionKeyDataProvider(): array
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

    /**
     * @test
     * @dataProvider getExtensionFromZipFileExtractsExtensionKeyDataProvider
     */
    public function getExtensionKeyFromFileNameExtractsExtensionKey(string $filename, string $expectedKey): void
    {
        $subject = $this->getAccessibleMock(UploadExtensionFileController::class, null, [], '', false);
        self::assertEquals($expectedKey, $subject->_call('getExtensionKeyFromFileName', $filename));
    }
}
