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

namespace TYPO3\CMS\Form\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Form\Service\RichTextConfigurationService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for RichTextConfigurationService
 *
 * Note: RichTextConfigurationService is a final class, so we test only the public API.
 * Private methods are tested indirectly through the public resolveCkEditorConfiguration method.
 */
final class RichTextConfigurationServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function resolveCkEditorConfigurationReturnsArrayWithBasicStructure(): void
    {
        $testConfiguration = [
            'editor' => [
                'config' => [
                    'toolbar' => ['bold', 'italic'],
                ],
            ],
        ];

        $richtextMock = $this->createMock(Richtext::class);
        $richtextMock->method('getConfiguration')->willReturn($testConfiguration);

        $rteHtmlParserMock = $this->createMock(RteHtmlParser::class);
        $publisherMock = $this->createMock(SystemResourcePublisherInterface::class);
        $factoryMock = $this->createMock(SystemResourceFactory::class);
        $uriBuilderMock = $this->createMock(UriBuilder::class);

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['lang' => 'en'];
        $GLOBALS['BE_USER'] = $backendUser;
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = false;

        $subject = new RichTextConfigurationService(
            $richtextMock,
            $rteHtmlParserMock,
            $publisherMock,
            $factoryMock,
            $uriBuilderMock
        );

        $result = $subject->resolveCkEditorConfiguration();

        self::assertArrayHasKey('toolbar', $result);
        self::assertSame(['bold', 'italic'], $result['toolbar']);
    }

    #[Test]
    public function resolveCkEditorConfigurationSetsLanguageConfiguration(): void
    {
        $testConfiguration = [
            'editor' => [
                'config' => [
                    'toolbar' => ['bold'],
                ],
            ],
        ];

        $richtextMock = $this->createMock(Richtext::class);
        $richtextMock->method('getConfiguration')->willReturn($testConfiguration);

        $rteHtmlParserMock = $this->createMock(RteHtmlParser::class);
        $publisherMock = $this->createMock(SystemResourcePublisherInterface::class);
        $factoryMock = $this->createMock(SystemResourceFactory::class);
        $uriBuilderMock = $this->createMock(UriBuilder::class);

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['lang' => 'de'];
        $GLOBALS['BE_USER'] = $backendUser;
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = false;

        $subject = new RichTextConfigurationService(
            $richtextMock,
            $rteHtmlParserMock,
            $publisherMock,
            $factoryMock,
            $uriBuilderMock
        );

        $result = $subject->resolveCkEditorConfiguration();

        self::assertArrayHasKey('language', $result);
        self::assertIsArray($result['language']);
        self::assertSame('de', $result['language']['ui']);
        self::assertSame('en', $result['language']['content']);
    }

    #[Test]
    public function resolveCkEditorConfigurationTranslatesLanguageReferences(): void
    {
        $testConfiguration = [
            'editor' => [
                'config' => [
                    'toolbar' => ['bold'],
                    'label' => 'LLL:EXT:form/Resources/Private/Language/locallang.xlf:button.bold',
                ],
            ],
        ];

        $richtextMock = $this->createMock(Richtext::class);
        $richtextMock->method('getConfiguration')->willReturn($testConfiguration);

        $rteHtmlParserMock = $this->createMock(RteHtmlParser::class);
        $publisherMock = $this->createMock(SystemResourcePublisherInterface::class);
        $factoryMock = $this->createMock(SystemResourceFactory::class);
        $uriBuilderMock = $this->createMock(UriBuilder::class);

        $languageService = $this->createMock(LanguageService::class);
        $languageService->method('sL')->willReturnCallback(function ($input) {
            if (str_starts_with($input, 'LLL:')) {
                return 'Translated: Bold';
            }
            return $input;
        });

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['lang' => 'en'];
        $GLOBALS['BE_USER'] = $backendUser;
        $GLOBALS['LANG'] = $languageService;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = false;

        $subject = new RichTextConfigurationService(
            $richtextMock,
            $rteHtmlParserMock,
            $publisherMock,
            $factoryMock,
            $uriBuilderMock
        );

        $result = $subject->resolveCkEditorConfiguration();

        self::assertStringNotContainsString('LLL:', $result['label']);
        self::assertSame('Translated: Bold', $result['label']);
    }

    #[Test]
    public function resolveCkEditorConfigurationProcessesExternalPlugins(): void
    {
        $testConfiguration = [
            'editor' => [
                'config' => [
                    'toolbar' => ['bold'],
                ],
                'externalPlugins' => [
                    'typo3link' => [
                        'route' => 'rteckeditor_wizard_browse_links',
                        'configName' => 'linkBrowser',
                    ],
                ],
            ],
        ];

        $richtextMock = $this->createMock(Richtext::class);
        $richtextMock->method('getConfiguration')->willReturn($testConfiguration);

        $rteHtmlParserMock = $this->createMock(RteHtmlParser::class);
        $publisherMock = $this->createMock(SystemResourcePublisherInterface::class);
        $factoryMock = $this->createMock(SystemResourceFactory::class);

        $uriBuilderMock = $this->createMock(UriBuilder::class);
        $uriBuilderMock->method('buildUriFromRoute')->willReturn('https://example.com/wizard');

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['lang' => 'en'];
        $GLOBALS['BE_USER'] = $backendUser;
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = false;

        $subject = new RichTextConfigurationService(
            $richtextMock,
            $rteHtmlParserMock,
            $publisherMock,
            $factoryMock,
            $uriBuilderMock
        );

        $result = $subject->resolveCkEditorConfiguration();

        self::assertArrayHasKey('linkBrowser', $result);
        self::assertArrayHasKey('routeUrl', $result['linkBrowser']);
        self::assertSame('https://example.com/wizard', $result['linkBrowser']['routeUrl']);
    }

    #[Test]
    public function resolveCkEditorConfigurationMergesCustomConfig(): void
    {
        $testConfiguration = [
            'editor' => [
                'config' => [
                    'toolbar' => ['bold', 'italic'],
                    'customConfig' => 'test',
                ],
            ],
        ];

        $richtextMock = $this->createMock(Richtext::class);
        $richtextMock->method('getConfiguration')->willReturn($testConfiguration);

        $rteHtmlParserMock = $this->createMock(RteHtmlParser::class);
        $publisherMock = $this->createMock(SystemResourcePublisherInterface::class);
        $factoryMock = $this->createMock(SystemResourceFactory::class);
        $uriBuilderMock = $this->createMock(UriBuilder::class);

        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $backendUser->user = ['lang' => 'en'];
        $GLOBALS['BE_USER'] = $backendUser;
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] = false;

        $subject = new RichTextConfigurationService(
            $richtextMock,
            $rteHtmlParserMock,
            $publisherMock,
            $factoryMock,
            $uriBuilderMock
        );

        $result = $subject->resolveCkEditorConfiguration();

        self::assertSame('test', $result['customConfig']);
        self::assertArrayHasKey('toolbar', $result);
    }

    #[Test]
    public function transformTextForPersistenceCallsRteHtmlParser(): void
    {
        $testConfiguration = [
            'proc.' => [
                'overruleMode' => 'default',
            ],
        ];

        $richtextMock = $this->createMock(Richtext::class);
        $richtextMock->method('getConfiguration')->willReturn($testConfiguration);

        $rteHtmlParserMock = $this->createMock(RteHtmlParser::class);
        $rteHtmlParserMock->expects($this->once())
            ->method('transformTextForPersistence')
            ->with('<p>Test content</p>', ['overruleMode' => 'default'])
            ->willReturn('<p>Transformed content</p>');

        $publisherMock = $this->createMock(SystemResourcePublisherInterface::class);
        $factoryMock = $this->createMock(SystemResourceFactory::class);
        $uriBuilderMock = $this->createMock(UriBuilder::class);

        $subject = new RichTextConfigurationService(
            $richtextMock,
            $rteHtmlParserMock,
            $publisherMock,
            $factoryMock,
            $uriBuilderMock
        );

        $result = $subject->transformTextForPersistence('<p>Test content</p>');

        self::assertSame('<p>Transformed content</p>', $result);
    }

    #[Test]
    public function transformTextForRichTextEditorCallsRteHtmlParser(): void
    {
        $testConfiguration = [
            'proc.' => [
                'overruleMode' => 'default',
            ],
        ];

        $richtextMock = $this->createMock(Richtext::class);
        $richtextMock->method('getConfiguration')->willReturn($testConfiguration);

        $rteHtmlParserMock = $this->createMock(RteHtmlParser::class);
        $rteHtmlParserMock->expects($this->once())
            ->method('transformTextForRichTextEditor')
            ->with('<p>DB content</p>', ['overruleMode' => 'default'])
            ->willReturn('<p>RTE content</p>');

        $publisherMock = $this->createMock(SystemResourcePublisherInterface::class);
        $factoryMock = $this->createMock(SystemResourceFactory::class);
        $uriBuilderMock = $this->createMock(UriBuilder::class);

        $subject = new RichTextConfigurationService(
            $richtextMock,
            $rteHtmlParserMock,
            $publisherMock,
            $factoryMock,
            $uriBuilderMock
        );

        $result = $subject->transformTextForRichTextEditor('<p>DB content</p>');

        self::assertSame('<p>RTE content</p>', $result);
    }
}
