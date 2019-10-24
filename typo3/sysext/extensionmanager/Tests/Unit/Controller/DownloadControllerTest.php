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
 * Download from TER controller test
 */
class DownloadControllerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function installFromTerReturnsArrayWithBooleanResultAndErrorArrayWhenExtensionManagerExceptionIsThrown()
    {
        $dummyExceptionMessage = 'exception message';
        $dummyException = new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException($dummyExceptionMessage, 1476108614);

        $dummyExtensionName = 'dummy_extension';
        $dummyExtension = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Domain\Model\Extension::class)->getMock();
        $dummyExtension->expects(self::any())->method('getExtensionKey')->willReturn($dummyExtensionName);

        /** @var \TYPO3\CMS\Extensionmanager\Utility\DownloadUtility|MockObject $downloadUtilityMock */
        $downloadUtilityMock = $this->getMockBuilder(\TYPO3\CMS\Extensionmanager\Utility\DownloadUtility::class)->getMock();
        $downloadUtilityMock->expects(self::any())->method('setDownloadPath')->willThrowException($dummyException);

        /** @var \TYPO3\CMS\Extensionmanager\Controller\DownloadController $subject */
        $subject = new \TYPO3\CMS\Extensionmanager\Controller\DownloadController();
        $subject->injectDownloadUtility($downloadUtilityMock);

        $reflectionClass = new \ReflectionClass($subject);
        $reflectionMethod = $reflectionClass->getMethod('installFromTer');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invokeArgs($subject, [$dummyExtension]);

        $expectedResult = [
            false,
            [
                $dummyExtensionName => [
                    [
                        'code' => 1476108614,
                        'message' => $dummyExceptionMessage
                    ]
                ]
            ]
        ];

        self::assertSame($expectedResult, $result);
    }
}
