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

use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Extensionmanager\Controller\DownloadController;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Download from TER controller test
 */
class DownloadControllerTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function installFromTerReturnsArrayWithBooleanResultAndErrorArrayWhenExtensionManagerExceptionIsThrown(): void
    {
        $dummyExceptionMessage = 'exception message';
        $dummyException = new ExtensionManagerException($dummyExceptionMessage, 1476108614);

        $dummyExtensionName = 'dummy_extension';
        $dummyExtension = new Extension();
        $dummyExtension->setExtensionKey($dummyExtensionName);

        /** @var ExtensionManagementService|MockObject $downloadUtilityMock */
        $extensionManagementServiceMock = $this->getMockBuilder(ExtensionManagementService::class)->disableOriginalConstructor()->getMock();
        $extensionManagementServiceMock->expects(self::any())->method('setDownloadPath')->willThrowException($dummyException);

        $subject = new DownloadController($this->prophesize(ExtensionRepository::class)->reveal(), $extensionManagementServiceMock);

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
                        'message' => $dummyExceptionMessage,
                    ],
                ],
            ],
        ];

        self::assertSame($expectedResult, $result);
    }
}
