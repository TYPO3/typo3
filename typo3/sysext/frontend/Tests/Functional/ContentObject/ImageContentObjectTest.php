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

namespace TYPO3\CMS\Frontend\Tests\Functional\ContentObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\ImageResource;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\ImageContentObject;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ImageContentObjectTest extends FunctionalTestCase
{
    public static function getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFoundDataProvider(): array
    {
        return [
            [null, null],
            ['', null],
            ['', []],
            ['fooo', ['foo' => 'bar']],
        ];
    }

    #[DataProvider('getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFoundDataProvider')]
    #[Test]
    public function getImageTagTemplateFallsBackToDefaultTemplateIfNoTemplateIsFound(?string $key, ?array $configuration): void
    {
        $defaultImgTagTemplate = '<img src="###SRC###" width="###WIDTH###" height="###HEIGHT###" ###PARAMS### ###ALTPARAMS### ###SELFCLOSINGTAGSLASH###>';
        $subject = $this->getAccessibleMock(ImageContentObject::class, null, [$this->get(MarkerBasedTemplateService::class)]);
        $subject->setRequest(new ServerRequest());
        $subject->setContentObjectRenderer($this->get(ContentObjectRenderer::class));
        $result = $subject->_call('getImageTagTemplate', $key, $configuration);
        self::assertEquals($defaultImgTagTemplate, $result);
    }

    #[Test]
    public function getImageTagTemplateReturnTemplateElementIdentifiedByKey(): void
    {
        $configuration = [
            'layout.' => [
                'foo.' => [
                    'element' => '<img src="###SRC###" srcset="###SOURCES###" ###PARAMS### ###ALTPARAMS### ###FOOBAR######SELFCLOSINGTAGSLASH###>',
                ],
            ],
        ];
        $subject = $this->getAccessibleMock(ImageContentObject::class, null, [$this->get(MarkerBasedTemplateService::class)]);
        $subject->setRequest(new ServerRequest());
        $subject->setContentObjectRenderer($this->get(ContentObjectRenderer::class));
        $result = $subject->_call('getImageTagTemplate', 'foo', $configuration);
        self::assertEquals('<img src="###SRC###" srcset="###SOURCES###" ###PARAMS### ###ALTPARAMS### ###FOOBAR######SELFCLOSINGTAGSLASH###>', $result);
    }

    public static function getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefinedDataProvider(): array
    {
        return [
            ['foo', [], null],
            ['foo', ['sourceCollection.' => 1], 'bar'],
        ];
    }

    #[DataProvider('getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefinedDataProvider')]
    #[Test]
    public function getImageSourceCollectionReturnsEmptyStringIfNoSourcesAreDefined(string $layoutKey, array $configuration, ?string $file): void
    {
        $subject = $this->getAccessibleMock(ImageContentObject::class, null, [$this->get(MarkerBasedTemplateService::class)]);
        $result = $subject->_call('getImageSourceCollection', $layoutKey, $configuration, $file);
        self::assertSame('', $result);
    }

    #[Test]
    public function getImageSourceCollectionRendersDefinedSources(): void
    {
        $configuration = [
            'layoutKey' => 'test',
            'layout.' => [
                'test.' => [
                    'element' => '<img ###SRC### ###SRCCOLLECTION### ###SELFCLOSINGTAGSLASH###>',
                    'source' => '---###SRC###---',
                ],
            ],
            'sourceCollection.' => [
                '1.' => [
                    'width' => '200',
                ],
            ],
        ];
        $cObj = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['stdWrap', 'getImgResource'])
            ->disableOriginalConstructor()
            ->getMock();
        // Fake image resource
        $cObj->expects($this->once())
            ->method('getImgResource')
            ->with(self::equalTo('testImageName'))
            ->willReturn(new ImageResource(100, 100, '', 'bar', 'bar'));
        $subject = $this->getAccessibleMock(ImageContentObject::class, null, [$this->get(MarkerBasedTemplateService::class)]);
        $subject->setRequest(new ServerRequest());
        $subject->setContentObjectRenderer($cObj);
        $result = $subject->_call('getImageSourceCollection', 'test', $configuration, 'testImageName');
        self::assertEquals('---bar---', $result);
    }

    #[Test]
    public function getImageSourceCollectionRendersDefinedLayoutKeyDefault(): void
    {
        $configuration = [
            'layoutKey' => 'default',
            'layout.' => [
                'default.' => [
                    'element' => '<img src="###SRC###" width="###WIDTH###" height="###HEIGHT###" ###PARAMS### ###ALTPARAMS### ##SELFCLOSINGTAGSLASH###>',
                    'source' => '',
                ],
            ],
            'sourceCollection.' => [
                'small.' => [
                    'width' => 200,
                    'srcsetCandidate' => '600w',
                    'mediaQuery' => '(max-device-width: 600px)',
                    'dataKey' => 'small',
                ],
                'smallRetina.' => [
                    'if.directReturn' => 0,
                    'width' => 200,
                    'pixelDensity' => '2',
                    'srcsetCandidate' => '600w 2x',
                    'mediaQuery' => '(max-device-width: 600px) AND (min-resolution: 192dpi)',
                    'dataKey' => 'smallRetina',
                ],
            ],
        ];
        $cObj = $this->get(ContentObjectRenderer::class);
        $subject = $this->getAccessibleMock(ImageContentObject::class, null, [$this->get(MarkerBasedTemplateService::class)]);
        $subject->setRequest(new ServerRequest());
        $subject->setContentObjectRenderer($cObj);
        $result = $subject->_call('getImageSourceCollection', 'default', $configuration, 'testImageName');
        self::assertEmpty($result);
    }

    public static function getImageSourceCollectionRendersDefinedLayoutKeyDataDataProvider(): array
    {
        $sourceCollectionArray = [
            'small.' => [
                'width' => 200,
                'srcsetCandidate' => '600w',
                'mediaQuery' => '(max-device-width: 600px)',
                'dataKey' => 'small',
            ],
            'smallRetina.' => [
                'if.directReturn' => 1,
                'width' => 200,
                'pixelDensity' => '2',
                'srcsetCandidate' => '600w 2x',
                'mediaQuery' => '(max-device-width: 600px) AND (min-resolution: 192dpi)',
                'dataKey' => 'smallRetina',
            ],
        ];
        return [
            [
                'srcset',
                [
                    'layoutKey' => 'srcset',
                    'layout.' => [
                        'srcset.' => [
                            'element' => '<img src="###SRC###" srcset="###SOURCECOLLECTION###" ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###>',
                            'source' => '|*|###SRC### ###SRCSETCANDIDATE###,|*|###SRC### ###SRCSETCANDIDATE###',
                        ],
                    ],
                    'sourceCollection.' => $sourceCollectionArray,
                ],
                'xhtml_strict',
                'bar-file.jpg 600w,bar-file.jpg 600w 2x',
            ],
            [
                'picture',
                [
                    'layoutKey' => 'picture',
                    'layout.' => [
                        'picture.' => [
                            'element' => '<picture>###SOURCECOLLECTION###<img src="###SRC###" ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###></picture>',
                            'source' => '<source src="###SRC###" media="###MEDIAQUERY###"###SELFCLOSINGTAGSLASH###>',
                        ],
                    ],
                    'sourceCollection.' => $sourceCollectionArray,
                ],
                'xhtml_strict',
                '<source src="bar-file.jpg" media="(max-device-width: 600px)" /><source src="bar-file.jpg" media="(max-device-width: 600px) AND (min-resolution: 192dpi)" />',
            ],
            [
                'picture',
                [
                    'layoutKey' => 'picture',
                    'layout.' => [
                        'picture.' => [
                            'element' => '<picture>###SOURCECOLLECTION###<img src="###SRC###" ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###></picture>',
                            'source' => '<source src="###SRC###" media="###MEDIAQUERY###"###SELFCLOSINGTAGSLASH###>',
                        ],
                    ],
                    'sourceCollection.' => $sourceCollectionArray,
                ],
                '',
                '<source src="bar-file.jpg" media="(max-device-width: 600px)"><source src="bar-file.jpg" media="(max-device-width: 600px) AND (min-resolution: 192dpi)">',
            ],
            [
                'data',
                [
                    'layoutKey' => 'data',
                    'layout.' => [
                        'data.' => [
                            'element' => '<img src="###SRC###" ###SOURCECOLLECTION### ###PARAMS### ###ALTPARAMS######SELFCLOSINGTAGSLASH###>',
                            'source' => 'data-###DATAKEY###="###SRC###"',
                        ],
                    ],
                    'sourceCollection.' => $sourceCollectionArray,
                ],
                'xhtml_strict',
                'data-small="bar-file.jpg"data-smallRetina="bar-file.jpg"',
            ],
        ];
    }

    #[DataProvider('getImageSourceCollectionRendersDefinedLayoutKeyDataDataProvider')]
    #[Test]
    public function getImageSourceCollectionRendersDefinedLayoutKeyData(string $layoutKey, array $configuration, string $doctype, string $expectedHtml): void
    {
        $cObj = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['getImgResource'])
            ->disableOriginalConstructor()
            ->getMock();
        // Fake image resource
        $cObj->expects($this->exactly(2))
            ->method('getImgResource')
            ->with(self::equalTo('testImageName'))
            ->willReturn(new ImageResource(100, 100, '', 'bar-file.jpg', 'bar-file.jpg'));

        $pageRenderer = $this->get(PageRenderer::class);
        $pageRenderer->setLanguage(new Locale());
        $pageRenderer->setDocType(DocType::createFromConfigurationKey($doctype));

        $subject = $this->getAccessibleMock(ImageContentObject::class, null, [$this->get(MarkerBasedTemplateService::class)]);
        $subject->setRequest(new ServerRequest());
        $subject->setContentObjectRenderer($cObj);
        $result = $subject->_call('getImageSourceCollection', $layoutKey, $configuration, 'testImageName');

        self::assertEquals($expectedHtml, $result);
    }

    public static function linkWrapDataProvider(): array
    {
        $content = StringUtility::getUniqueId();
        return [
            'Handles a tag as wrap.' => [
                '<tag>' . $content . '</tag>',
                $content,
                '<tag>|</tag>',
            ],
            'Handles simple text as wrap.' => [
                'alpha' . $content . 'omega',
                $content,
                'alpha|omega',
            ],
            'Trims whitespace around tags.' => [
                '<tag>' . $content . '</tag>',
                $content,
                "\t <tag>\t |\t </tag>\t ",
            ],
            'A wrap without pipe is placed before the content.' => [
                '<tag>' . $content,
                $content,
                '<tag>',
            ],
            'For an empty string as wrap the content is returned as is.' => [
                $content,
                $content,
                '',
            ],
            'For a valid rootline level the uid will be inserted.' => [
                '<a href="?id=55">' . $content . '</a>',
                $content,
                '<a href="?id={3}"> | </a>',
            ],
            'For an invalid rootline level there is no replacement.' => [
                '<a href="?id={4}">' . $content . '</a>',
                $content,
                '<a href="?id={4}"> | </a>',
            ],
        ];
    }

    #[DataProvider('linkWrapDataProvider')]
    #[Test]
    public function linkWrap(string $expected, string $content, string $wrap): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setLocalRootLine([3 => ['uid' => 55]]);
        $request = (new ServerRequest())->withAttribute('frontend.page.information', $pageInformation);
        $subject = $this->getAccessibleMock(ImageContentObject::class, null, [$this->get(MarkerBasedTemplateService::class)]);
        $subject->setRequest($request);
        $result = $subject->_call('linkWrap', $content, $wrap);
        self::assertEquals($expected, $result);
    }
}
