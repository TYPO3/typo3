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

namespace TYPO3\CMS\Adminpanel\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Adminpanel\Tests\Unit\Fixtures\MainModuleFixture;
use TYPO3\CMS\Adminpanel\Tests\Unit\Fixtures\SubModuleFixture;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ConfigurationServiceTest extends UnitTestCase
{
    protected MockObject&BackendUserAuthentication $beUser;

    public function setUp(): void
    {
        parent::setUp();
        $this->beUser = $this->getMockBuilder(BackendUserAuthentication::class)->disableOriginalConstructor()->getMock();
        $GLOBALS['BE_USER'] = $this->beUser;
    }

    #[Test]
    public function getMainConfigurationReturnsTsConfigFromUser(): void
    {
        $userTsAdmPanelConfig = [
            'enable.' => [
                'all' => '1',
            ],
        ];
        $this->setUpUserTsConfigForAdmPanel($userTsAdmPanelConfig);

        $configurationService = new ConfigurationService();
        $result = $configurationService->getMainConfiguration();

        self::assertSame($userTsAdmPanelConfig, $result);
    }

    #[Test]
    public function getConfigurationOptionReturnsEmptyStringIfNoConfigurationFound(): void
    {
        $configurationService = new ConfigurationService();
        $result = $configurationService->getConfigurationOption('foo', 'bar');
        self::assertSame('', $result);
    }

    #[Test]
    public function getConfigurationOptionReturnsOverrideOptionIfSet(): void
    {
        $this->setUpUserTsConfigForAdmPanel(
            [
                'override.' => [
                    'preview.' => [
                        'showHiddenPages' => '1',
                    ],
                ],
            ]
        );

        $configurationService = new ConfigurationService();
        $result = $configurationService->getConfigurationOption('preview', 'showHiddenPages');

        self::assertSame('1', $result);
    }

    #[Test]
    public function getConfigurationOptionCastsResultToString(): void
    {
        $this->setUpUserTsConfigForAdmPanel(
            [
                'override.' => [
                    'preview.' => [
                        'showHiddenPages' => 1,
                    ],
                ],
            ]
        );

        $configurationService = new ConfigurationService();
        $result = $configurationService->getConfigurationOption('preview', 'showHiddenPages');

        self::assertSame('1', $result);
    }

    public static function getConfigurationOptionEmptyArgumentDataProvider(): array
    {
        return [
            'empty identifier' => [
                '',
                'foo',
            ],
            'empty option' => [
                'foo',
                '',
            ],
            'both empty' => [
                '',
                '',
            ],
        ];
    }

    #[DataProvider('getConfigurationOptionEmptyArgumentDataProvider')]
    #[Test]
    public function getConfigurationOptionThrowsExceptionOnEmptyArgument(string $identifier, string $option): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1532861423);

        $configurationService = new ConfigurationService();
        $configurationService->getConfigurationOption($identifier, $option);
    }

    #[Test]
    public function getConfigurationOptionReturnsSettingFromUcIfNoOverrideGiven(): void
    {
        $this->setUpUserTsConfigForAdmPanel([]);
        $this->beUser->uc = [
            'AdminPanel' => [
                'preview_showHiddenPages' => '1',
            ],
        ];

        $configurationService = new ConfigurationService();
        $result = $configurationService->getConfigurationOption('preview', 'showHiddenPages');

        self::assertSame('1', $result);
    }

    #[Test]
    public function saveConfigurationTriggersOnSubmitOnEnabledModules(): void
    {
        $requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        $subModuleFixture = $this->getMockBuilder(SubModuleFixture::class)->getMock();
        $subModuleFixture->expects($this->atLeastOnce())->method('onSubmit')->with([], $requestMock);
        $mainModuleFixture = $this->getMockBuilder(MainModuleFixture::class)->getMock();
        $mainModuleFixture->method('isEnabled')->willReturn(true);
        $mainModuleFixture->expects($this->atLeastOnce())->method('onSubmit')->with([], $requestMock);
        $mainModuleFixture->method('getSubModules')->willReturn(
            [$subModuleFixture]
        );
        $modules = [
            $mainModuleFixture,
        ];

        $configurationService = new ConfigurationService();
        $configurationService->saveConfiguration($modules, $requestMock);
    }

    #[Test]
    public function saveConfigurationSavesMergedExistingAndNewConfiguration(): void
    {
        // existing configuration from UC
        $this->beUser->uc = [
            'AdminPanel' => [
                'foo' => 'bar',
            ],
        ];

        $this->beUser->expects($this->atLeastOnce())->method('writeUC');

        // new configuration to save
        $requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $requestMock->method('getParsedBody')->willReturn(
            [
                'TSFE_ADMIN_PANEL' => [
                    'baz' => 'bam',
                ],
            ]
        );

        $configurationService = new ConfigurationService();
        $configurationService->saveConfiguration([], $requestMock);

        $expected = [
            'AdminPanel' => [
                'foo' => 'bar',
                'baz' => 'bam',
            ],
        ];
        self::assertSame($expected, $this->beUser->uc);
    }

    private function setUpUserTsConfigForAdmPanel(array $userTsAdmPanelConfig): void
    {
        $this->beUser->method('getTSConfig')->willReturn(
            ['admPanel.' => $userTsAdmPanelConfig]
        );
    }
}
