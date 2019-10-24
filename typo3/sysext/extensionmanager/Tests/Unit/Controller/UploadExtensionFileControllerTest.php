<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Controller;

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

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Update from TER controller test
 */
class UploadExtensionFileControllerTest extends UnitTestCase
{
    /**
     * @return array The test data for getExtensionFromZipFileExtractsExtensionKey
     */
    public function getExtensionFromZipFileExtractsExtensionKeyDataProvider()
    {
        return [
            'simple' => [
                'extension_0.0.0.zip',
                'extension'
            ],
            'underscore in extension name' => [
                'extension_key_10.100.356.zip',
                'extension_key'
            ],
            'camel case file name' => [
                'extensionName_1.1.1.zip',
                'extensionname'
            ],
            'version with dashes' => [
                'extension_1-2-3.zip',
                'extension'
            ],
            'characters after version' => [
                'extension_1-2-3(1).zip',
                'extension'
            ],
            'characters after version with extra space' => [
                'extension_1-2-3 (1).zip',
                'extension'
            ],
            'no version' => [
                'extension.zip',
                'extension'
            ]
        ];
    }
    /**
     * @test
     * @dataProvider getExtensionFromZipFileExtractsExtensionKeyDataProvider
     * @param string $filename The file name to test
     * @param string $expectedKey The expected extension key
     */
    public function getExtensionFromZipFileExtractsExtensionKey($filename, $expectedKey)
    {
        /** @var \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService|MockObject $managementServiceMock */
        $managementServiceMock = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService::class)
            ->setMethods(['isAvailable'])
            ->disableOriginalConstructor()
            ->getMock();
        $managementServiceMock->expects(self::once())
            ->method('isAvailable')
            ->with($expectedKey)
            ->willReturn(false);

        /** @var \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility|MockObject $fileHandlingUtilityMock */
        $fileHandlingUtilityMock = $this->createMock(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility::class);
        $fileHandlingUtilityMock->expects(self::once())->method('unzipExtensionFromFile');

        $fixture = new \TYPO3\CMS\Extensionmanager\Controller\UploadExtensionFileController();
        $fixture->injectManagementService($managementServiceMock);
        $fixture->injectFileHandlingUtility($fileHandlingUtilityMock);

        $reflectionClass = new \ReflectionClass($fixture);
        $reflectionMethod = $reflectionClass->getMethod('getExtensionFromZipFile');
        $reflectionMethod->setAccessible(true);

        $extensionDetails = $reflectionMethod->invokeArgs($fixture, ['', $filename]);

        self::assertEquals($expectedKey, $extensionDetails['extKey']);
    }
}
