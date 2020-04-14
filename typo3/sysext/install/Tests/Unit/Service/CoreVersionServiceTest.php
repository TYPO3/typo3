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

namespace TYPO3\CMS\Install\Tests\Unit\Service;

use Prophecy\Argument;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\CoreVersionService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CoreVersionServiceTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    public function setUpApiResponse(string $url, array $responseData)
    {
        $response = new JsonResponse($responseData);
        $requestFactory = $this->prophesize(RequestFactory::class);
        $requestFactory->request('https://get.typo3.org/v1/api/' . $url, Argument::cetera())->willReturn($response);
        GeneralUtility::addInstance(RequestFactory::class, $requestFactory->reveal());
    }

    /**
     * @test
     */
    public function getTarGzSha1OfVersionReturnsSha1(): void
    {
        $this->setUpApiResponse(
            'release/8.7.12',
            [
                'version' => '8.7.12',
                'date' => '2018-03-22T11:36:39+00:00',
                'type' => 'regular',
                'tar_package' =>
                    [
                        'md5sum' => 'e835f454229b1077c9042f1bae4d46c7',
                        'sha1sum' => '185f3796751a903554a03378634a438beeef966e',
                        'sha256sum' => '77c3589161bea9d2c30e5d3d944443ba64b56813314ac2511b830e37d3297881',
                    ],
                'zip_package' =>
                    [
                        'md5sum' => 'e5736ca3b3725966a4528a0c53fc849f',
                        'sha1sum' => 'eba49b9033da52d98f48876e97ed090a0c5593e0',
                        'sha256sum' => '7aad3f5864256f3f989c0378cec8bb729e728b30adb25e55ae713d8e682ef72b',
                    ],
            ]
        );
        $coreVersionService = new CoreVersionService();
        $result = $coreVersionService->getTarGzSha1OfVersion('8.7.12');
        self::assertSame('185f3796751a903554a03378634a438beeef966e', $result);
    }

    /**
     * @test
     */
    public function getTarGzSha1OfVersionReturnsSha1ReturnsEmptyStringIfNoVersionData(): void
    {
        $this->setUpApiResponse(
            'release/8.7.44',
            [
                'error' =>
                    [
                        'code' => 404,
                        'message' => 'Not Found',
                    ],
            ]
        );
        $coreVersionService = new CoreVersionService();
        $result = $coreVersionService->getTarGzSha1OfVersion('8.7.44');
        self::assertSame('', $result);
    }

    /**
     * @test
     */
    public function isVersionActivelyMaintainedReturnsTrueIfMaintainedUntilIsNotSet(): void
    {
        $this->setUpApiResponse(
            'major/9',
            [
                'version' => 9.0,
                'title' => 'TYPO3 v9',
                'release_date' => '2018-01-30T00:00:00+01:00',
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledMajorVersion']);
        $instance->expects(self::once())->method('getInstalledMajorVersion')->willReturn('9');

        $result = $instance->isVersionActivelyMaintained();

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function isVersionActivelyMaintainedReturnsTrueIfMaintainedUntilIsAfterToday(): void
    {
        $this->setUpApiResponse(
            'major/9',
            [
                'version' => 9.0,
                'title' => 'TYPO3 v9',
                'release_date' => '2018-01-30T00:00:00+01:00',
                'maintained_until' => '2222-01-30T00:00:00+01:00',
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledMajorVersion']);
        $instance->expects(self::once())->method('getInstalledMajorVersion')->willReturn('9');

        $result = $instance->isVersionActivelyMaintained();

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function isVersionActivelyMaintainedReturnsFalseIfMaintainedUntilWasBeforeToday(): void
    {
        $this->setUpApiResponse(
            'major/7',
            [
                'version' => 7,
                'title' => 'TYPO3 v7',
                'maintained_until' => '2003-02-18T08:10:14+00:00',
                'release_date' => '2002-06-03T12:01:07+00:00',
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledMajorVersion']);
        $instance->expects(self::once())->method('getInstalledMajorVersion')->willReturn('7');

        $result = $instance->isVersionActivelyMaintained();

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function isYoungerPatchReleaseAvailableReturnsTrueIfNewerVersionExists(): void
    {
        $this->setUpApiResponse(
            'major/9/release/latest',
            [
                'version' => '9.1.0',
                'date' => '2018-01-30T15:44:52+00:00',
                'type' => 'regular',
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion', 'getInstalledMajorVersion']);
        $instance->expects(self::atLeastOnce())->method('getInstalledMajorVersion')->willReturn('9');
        $instance->expects(self::atLeastOnce())->method('getInstalledVersion')->willReturn('9.0.0');

        $result = $instance->isYoungerPatchReleaseAvailable();

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function isYoungerPatchReleaseAvailableReturnsFalseIfNoNewerVersionExists(): void
    {
        $this->setUpApiResponse(
            'major/9/release/latest',
            [
                'version' => '9.1.0',
                'date' => '2018-01-30T15:44:52+00:00',
                'type' => 'regular',
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion', 'getInstalledMajorVersion']);
        $instance->expects(self::atLeastOnce())->method('getInstalledMajorVersion')->willReturn('9');
        $instance->expects(self::atLeastOnce())->method('getInstalledVersion')->willReturn('9.1.0');

        $result = $instance->isYoungerPatchReleaseAvailable();

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function isUpdateSecurityRelevantReturnsTrueIfNewerSecurityUpdateExists(): void
    {
        $this->setUpApiResponse(
            'major/8/release/latest/security',
            [
                'version' => '8.7.5',
                'date' => '2017-09-05T10:54:18+00:00',
                'type' => 'security',
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion', 'getInstalledMajorVersion']);
        $instance->expects(self::atLeastOnce())->method('getInstalledMajorVersion')->willReturn('8');
        $instance->expects(self::atLeastOnce())->method('getInstalledVersion')->willReturn('8.7.1');

        $result = $instance->isUpdateSecurityRelevant();

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function isUpdateSecurityRelevantReturnsFalseIfNoNewerSecurityUpdatesExist(): void
    {
        $this->setUpApiResponse(
            'major/8/release/latest/security',
            [
                'version' => '8.7.5',
                'date' => '2017-09-05T10:54:18+00:00',
                'type' => 'security',
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion', 'getInstalledMajorVersion']);
        $instance->expects(self::atLeastOnce())->method('getInstalledVersion')->willReturn('8.7.5');
        $instance->expects(self::atLeastOnce())->method('getInstalledMajorVersion')->willReturn('8');

        $result = $instance->isUpdateSecurityRelevant();

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function getYoungestPatchReleaseReturnsLatestReleaseForCurrentMajorVersion(): void
    {
        $this->setUpApiResponse(
            'major/9/release/latest',
            [
                'version' => '9.1.0',
                'date' => '2018-01-30T15:44:52+00:00',
                'type' => 'regular',
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledMajorVersion']);
        $instance->expects(self::atLeastOnce())->method('getInstalledMajorVersion')->willReturn('9');

        $result = $instance->getYoungestPatchRelease();

        self::assertSame('9.1.0', $result);
    }

    /**
     * @test
     */
    public function isInstalledVersionAReleasedVersionReturnsTrueForNonDevelopmentVersion(): void
    {
        /** @var $instance CoreVersionService|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion'], [], '', false);
        $instance->expects(self::once())->method('getInstalledVersion')->willReturn('7.2.0');
        self::assertTrue($instance->isInstalledVersionAReleasedVersion());
    }

    /**
     * @test
     */
    public function isInstalledVersionAReleasedVersionReturnsFalseForDevelopmentVersion()
    {
        /** @var $instance CoreVersionService|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|\PHPUnit\Framework\MockObject\MockObject */
        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion'], [], '', false);
        $instance->expects(self::once())->method('getInstalledVersion')->willReturn('7.4-dev');
        self::assertFalse($instance->isInstalledVersionAReleasedVersion());
    }
}
