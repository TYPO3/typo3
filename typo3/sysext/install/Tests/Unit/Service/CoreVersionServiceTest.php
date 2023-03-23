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

use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\CoreVersion\CoreRelease;
use TYPO3\CMS\Install\Service\CoreVersionService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class CoreVersionServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    public function setUpApiResponse(string $url, array $responseData): void
    {
        $response = new JsonResponse($responseData);
        $requestFactoryMock = $this->getMockBuilder(RequestFactory::class)->disableOriginalConstructor()->getMock();
        $requestFactoryMock->expects(self::atLeastOnce())->method('request')->willReturn($response);
        GeneralUtility::addInstance(RequestFactory::class, $requestFactoryMock);
    }

    /**
     * @test
     */
    public function isVersionActivelyCommunityMaintainedReturnsFalseIfMaintainedUntilIsNotSet(): void
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

        $result = $instance->getMaintenanceWindow()->isSupportedByCommunity();

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function isVersionActivelyCommunityMaintainedReturnsTrueIfMaintainedUntilIsAfterToday(): void
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

        $result = $instance->getMaintenanceWindow()->isSupportedByCommunity();

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function isVersionActivelyCommunityMaintainedReturnsFalseIfMaintainedUntilWasBeforeToday(): void
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

        $result = $instance->getMaintenanceWindow()->isSupportedByCommunity();

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function isVersionActivelyEltsMaintainedReturnsFalseIfEltsUntilIsNotSet(): void
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

        $result = $instance->getMaintenanceWindow()->isSupportedByElts();

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function isVersionActivelyEltsMaintainedReturnsTrueIfEltsUntilIsAfterToday(): void
    {
        $this->setUpApiResponse(
            'major/9',
            [
                'version' => 9.0,
                'title' => 'TYPO3 v9',
                'release_date' => '2018-01-30T00:00:00+01:00',
                'elts_until' => '2222-01-30T00:00:00+01:00',
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledMajorVersion']);
        $instance->expects(self::once())->method('getInstalledMajorVersion')->willReturn('9');

        $result = $instance->getMaintenanceWindow()->isSupportedByElts();

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function isVersionActivelyEltsMaintainedReturnsFalseIfEltsUntilWasBeforeToday(): void
    {
        $this->setUpApiResponse(
            'major/7',
            [
                'version' => 7,
                'title' => 'TYPO3 v7',
                'maintained_until' => '2003-02-18T08:10:14+00:00',
                'elts_until' => '2002-06-03T12:01:07+00:00',
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledMajorVersion']);
        $instance->expects(self::once())->method('getInstalledMajorVersion')->willReturn('7');

        $result = $instance->getMaintenanceWindow()->isSupportedByElts();

        self::assertFalse($result);
    }

    /**
     * The maintenance date ranges are built relatively to avoid the need to adjust them once the dates passed
     * @test
     */
    public function getSupportedMajorReleasesReturnsListOfVersions(): void
    {
        $this->setUpApiResponse(
            'major',
            [
                [
                    'version' => 11,
                    'title' => 'TYPO3 11',
                    'maintained_until' => (new \DateTimeImmutable('+3 years'))->format(\DateTimeInterface::ATOM),
                    'elts_until' => (new \DateTimeImmutable('+ 6 years'))->format(\DateTimeInterface::ATOM),
                ],
                [
                    'version' => 10,
                    'title' => 'TYPO3 10 LTS',
                    'maintained_until' => (new \DateTimeImmutable('+2 years'))->format(\DateTimeInterface::ATOM),
                    'elts_until' => (new \DateTimeImmutable('+ 5 years'))->format(\DateTimeInterface::ATOM),
                    'lts' => 10.4,
                ],
                [
                    'version' => 9,
                    'title' => 'TYPO3 9 LTS',
                    'maintained_until' => (new \DateTimeImmutable('+2 months'))->format(\DateTimeInterface::ATOM),
                    'elts_until' => (new \DateTimeImmutable('+3 years'))->format(\DateTimeInterface::ATOM),
                    'lts' => 9.5,
                ],
                [
                    'version' => 8,
                    'title' => 'TYPO3 8 ELTS',
                    'maintained_until' => (new \DateTimeImmutable('-1 year'))->format(\DateTimeInterface::ATOM),
                    'elts_until' => (new \DateTimeImmutable('+2 years'))->format(\DateTimeInterface::ATOM),
                    'lts' => 8.7,
                ],
                [
                    'version' => 7,
                    'title' => 'TYPO3 7 ELTS',
                    'maintained_until' => (new \DateTimeImmutable('-3 years'))->format(\DateTimeInterface::ATOM),
                    'elts_until' => (new \DateTimeImmutable('+2 months'))->format(\DateTimeInterface::ATOM),
                    'lts' => 7.6,
                ],
                [
                    'version' => 6,
                    'title' => 'TYPO3 6 ELTS',
                    'maintained_until' => (new \DateTimeImmutable('-4 years'))->format(\DateTimeInterface::ATOM),
                    'elts_until' => (new \DateTimeImmutable('-5 months'))->format(\DateTimeInterface::ATOM),
                    'lts' => 6.2,
                ],
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, null);
        $result = $instance->getSupportedMajorReleases();

        $expectation = [
            'community' => ['11', '10.4', '9.5'],
            'elts' => ['8.7', '7.6'],
        ];
        self::assertSame($expectation, $result);
    }

    /**
     * @test
     */
    public function isPatchReleaseSuitableForUpdateReturnsTrueIfNewerVersionExists(): void
    {
        $this->setUpApiResponse(
            'major/9/release/latest',
            [
                'version' => '9.1.0',
                'date' => '2018-01-30T15:44:52+00:00',
                'type' => 'regular',
                'tar_package' => [
                    'sha1sum' => '3a277826d716eb4e82a36a2200deefd76d15378c',
                ],
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion', 'getInstalledMajorVersion']);
        $instance->expects(self::atLeastOnce())->method('getInstalledMajorVersion')->willReturn('9');
        $instance->expects(self::atLeastOnce())->method('getInstalledVersion')->willReturn('9.0.0');

        $coreRelease = $instance->getYoungestPatchRelease();
        $result = $instance->isPatchReleaseSuitableForUpdate($coreRelease);

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function isPatchReleaseSuitableForUpdateReturnsFalseIfNoNewerVersionExists(): void
    {
        $this->setUpApiResponse(
            'major/9/release/latest',
            [
                'version' => '9.1.0',
                'date' => '2018-01-30T15:44:52+00:00',
                'type' => 'regular',
                'tar_package' => [
                    'sha1sum' => '3a277826d716eb4e82a36a2200deefd76d15378c',
                ],
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion', 'getInstalledMajorVersion']);
        $instance->expects(self::atLeastOnce())->method('getInstalledMajorVersion')->willReturn('9');
        $instance->expects(self::atLeastOnce())->method('getInstalledVersion')->willReturn('9.1.0');

        $coreRelease = $instance->getYoungestPatchRelease();
        $result = $instance->isPatchReleaseSuitableForUpdate($coreRelease);

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function isUpdateSecurityRelevantReturnsTrueIfNewerSecurityUpdateExists(): void
    {
        $coreRelease = new CoreRelease('8.7.5', new \DateTimeImmutable('2017-09-05T10:54:18+00:00'), 'security', 'e79466bffc81f270f5c262d01a125e82b2e1989a');

        $this->setUpApiResponse(
            'major/8/release',
            [
                [
                    'version' => '8.7.1',
                    'date' => '2017-04-18T17:05:53+00:00',
                    'type' => 'regular',
                    'tar_package' => [
                        'sha1sum' => 'e79466bffc81f270f5c262d01a125e82b2e1989a',
                    ],
                ],
                [
                    'version' => '8.7.4',
                    'date' => '2017-07-25T16:47:27+00:00',
                    'type' => 'regular',
                    'tar_package' => [
                        'sha1sum' => '1129c740796aabbf2efbc5e43f892debe7e1d583',
                    ],
                ],
                [
                    'version' => '8.7.5',
                    'date' => '2017-09-05T10:54:18+00:00',
                    'type' => 'security',
                    'tar_package' => [
                        'sha1sum' => 'e79466bffc81f270f5c262d01a125e82b2e1989a',
                    ],
                ],
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion', 'getInstalledMajorVersion']);
        $instance->expects(self::atLeastOnce())->method('getInstalledMajorVersion')->willReturn('8');
        $instance->expects(self::atLeastOnce())->method('getInstalledVersion')->willReturn('8.7.1');

        $result = $instance->isUpdateSecurityRelevant($coreRelease);

        self::assertTrue($result);
    }

    /**
     * @test
     */
    public function isUpdateSecurityRelevantReturnsFalseIfNewerSecurityUpdateExistsButCannotGetUpgraded(): void
    {
        $coreRelease = new CoreRelease('8.7.6', new \DateTimeImmutable('2017-09-05T10:54:18+00:00'), 'security', 'e79466bffc81f270f5c262d01a125e82b2e1989a');

        $this->setUpApiResponse(
            'major/8/release',
            [
                [
                    'version' => '8.7.5',
                    'date' => '2017-09-05T10:54:18+00:00',
                    'type' => 'security',
                    'tar_package' => [
                        'sha1sum' => 'e79466bffc81f270f5c262d01a125e82b2e1989a',
                    ],
                ],
                [
                    'version' => '8.7.6',
                    'date' => '2017-09-05T10:54:18+00:00',
                    'type' => 'regular',
                    'tar_package' => [
                        'sha1sum' => 'e79466bffc81f270f5c262d01a125e82b2e1989a',
                    ],
                ],
                [
                    'version' => '8.7.33',
                    'date' => '2020-05-12T00:00:00+02:00',
                    'type' => 'security',
                    'tar_package' => [
                        'sha1sum' => '2dd44ab6c98c3f07a0bbe4af6ccc5d7ced7e5856',
                    ],
                ],
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion', 'getInstalledMajorVersion']);
        $instance->expects(self::atLeastOnce())->method('getInstalledMajorVersion')->willReturn('8');
        $instance->expects(self::atLeastOnce())->method('getInstalledVersion')->willReturn('8.7.5');

        $result = $instance->isUpdateSecurityRelevant($coreRelease);

        self::assertFalse($result);
    }

    /**
     * @test
     */
    public function isUpdateSecurityRelevantReturnsFalseIfNoNewerSecurityUpdatesExist(): void
    {
        $coreRelease = new CoreRelease('8.7.6', new \DateTimeImmutable('2017-09-05T10:54:18+00:00'), 'security', 'e79466bffc81f270f5c262d01a125e82b2e1989a');

        $this->setUpApiResponse(
            'major/8/release',
            [
                [
                    'version' => '8.7.5',
                    'date' => '2017-09-05T10:54:18+00:00',
                    'type' => 'security',
                    'tar_package' => [
                        'sha1sum' => 'e79466bffc81f270f5c262d01a125e82b2e1989a',
                    ],
                ],
                [
                    'version' => '8.7.6',
                    'date' => '2017-09-05T10:54:18+00:00',
                    'type' => 'regular',
                    'tar_package' => [
                        'sha1sum' => 'e79466bffc81f270f5c262d01a125e82b2e1989a',
                    ],
                ],
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion', 'getInstalledMajorVersion']);
        $instance->expects(self::atLeastOnce())->method('getInstalledVersion')->willReturn('8.7.5');
        $instance->expects(self::atLeastOnce())->method('getInstalledMajorVersion')->willReturn('8');

        $result = $instance->isUpdateSecurityRelevant($coreRelease);

        self::assertFalse($result);
    }

    /**
     * @test
     * @dataProvider isCurrentInstalledVersionEltsReturnsExpectedResultDataProvider
     */
    public function isCurrentInstalledVersionEltsReturnsExpectedResult(string $major, string $version, bool $expectation): void
    {
        $this->setUpApiResponse(
            'major/8/release',
            [
                [
                    'version' => '8.7.1',
                    'date' => '2017-04-18T17:05:53+00:00',
                    'type' => 'regular',
                    'elts' => false,
                    'tar_package' => [
                        'sha1sum' => 'e79466bffc81f270f5c262d01a125e82b2e1989a',
                    ],
                ],
                [
                    'version' => '8.7.4',
                    'date' => '2017-07-25T16:47:27+00:00',
                    'type' => 'regular',
                    'elts' => false,
                    'tar_package' => [
                        'sha1sum' => '1129c740796aabbf2efbc5e43f892debe7e1d583',
                    ],
                ],
                [
                    'version' => '8.7.33',
                    'date' => '2020-05-12T00:00:00+02:00',
                    'type' => 'security',
                    'elts' => true,
                    'tar_package' => [
                        'sha1sum' => '2dd44ab6c98c3f07a0bbe4af6ccc5d7ced7e5856',
                    ],
                ],
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledMajorVersion', 'getInstalledVersion']);
        $instance->expects(self::atLeastOnce())->method('getInstalledMajorVersion')->willReturn($major);
        $instance->expects(self::atLeastOnce())->method('getInstalledVersion')->willReturn($version);

        self::assertSame($expectation, $instance->isCurrentInstalledVersionElts());
    }

    public static function isCurrentInstalledVersionEltsReturnsExpectedResultDataProvider(): array
    {
        return [
            ['8', '8.7.4', false],
            ['8', '8.7.33', true],
        ];
    }

    /**
     * @dataProvider getYoungestPatchReleaseReturnsLatestReleaseForCurrentMajorVersionDataProvider
     * @test
     */
    public function getYoungestPatchReleaseReturnsLatestReleaseForCurrentMajorVersion(string $major, array $response): void
    {
        $this->setUpApiResponse(
            'major/' . $major . '/release/latest',
            $response
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledMajorVersion']);
        $instance->expects(self::atLeastOnce())->method('getInstalledMajorVersion')->willReturn($major);

        $result = $instance->getYoungestPatchRelease();

        self::assertSame($response['version'], $result->getVersion());
        self::assertEquals($response['date'], $result->getDate()->format(\DateTimeInterface::ATOM));
        self::assertSame($response['type'] === 'security', $result->isSecurityUpdate());
        self::assertEquals($response['tar_package']['sha1sum'], $result->getChecksum());
        self::assertSame($response['elts'], $result->isElts());
    }

    public static function getYoungestPatchReleaseReturnsLatestReleaseForCurrentMajorVersionDataProvider(): array
    {
        return [
            [
                '9',
                [
                    'version' => '9.1.0',
                    'date' => '2018-01-30T15:44:52+00:00',
                    'type' => 'regular',
                    'elts' => false,
                    'tar_package' => [
                        'sha1sum' => '3a277826d716eb4e82a36a2200deefd76d15378c',
                    ],
                ],
            ],
            [
                '8',
                [
                    'version' => '8.7.33',
                    'date' => '2020-05-12T00:00:00+02:00',
                    'type' => 'security',
                    'elts' => true,
                    'tar_package' => [
                        'sha1sum' => '2dd44ab6c98c3f07a0bbe4af6ccc5d7ced7e5856',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function getYoungestCommunityPatchReleaseReturnsLatestNonEltsRelease(): void
    {
        $this->setUpApiResponse(
            'major/8/release',
            [
                [
                    'version' => '8.7.1',
                    'date' => '2017-04-18T17:05:53+00:00',
                    'type' => 'regular',
                    'elts' => false,
                    'tar_package' => [
                        'sha1sum' => 'e79466bffc81f270f5c262d01a125e82b2e1989a',
                    ],
                ],
                [
                    'version' => '8.7.4',
                    'date' => '2017-07-25T16:47:27+00:00',
                    'type' => 'regular',
                    'elts' => false,
                    'tar_package' => [
                        'sha1sum' => '1129c740796aabbf2efbc5e43f892debe7e1d583',
                    ],
                ],
                [
                    'version' => '8.7.30',
                    'date' => '2019-12-17T10:51:34+01:00',
                    'type' => 'security',
                    'elts' => false,
                    'tar_package' => [
                        'sha1sum' => '3df3a112dc7e2857bf39cfd2bc2c0bb7842a824c',
                    ],
                ],
                [
                    'version' => '8.7.33',
                    'date' => '2020-05-12T00:00:00+02:00',
                    'type' => 'security',
                    'elts' => true,
                    'tar_package' => [
                        'sha1sum' => '2dd44ab6c98c3f07a0bbe4af6ccc5d7ced7e5856',
                    ],
                ],
            ]
        );

        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledMajorVersion']);
        $instance->expects(self::atLeastOnce())->method('getInstalledMajorVersion')->willReturn('8');

        $result = $instance->getYoungestCommunityPatchRelease();

        self::assertSame('8.7.30', $result->getVersion());
    }

    /**
     * @test
     */
    public function isInstalledVersionAReleasedVersionReturnsTrueForNonDevelopmentVersion(): void
    {
        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion'], [], '', false);
        $instance->expects(self::once())->method('getInstalledVersion')->willReturn('7.2.0');
        self::assertTrue($instance->isInstalledVersionAReleasedVersion());
    }

    /**
     * @test
     */
    public function isInstalledVersionAReleasedVersionReturnsFalseForDevelopmentVersion(): void
    {
        $instance = $this->getAccessibleMock(CoreVersionService::class, ['getInstalledVersion'], [], '', false);
        $instance->expects(self::once())->method('getInstalledVersion')->willReturn('7.4-dev');
        self::assertFalse($instance->isInstalledVersionAReleasedVersion());
    }
}
