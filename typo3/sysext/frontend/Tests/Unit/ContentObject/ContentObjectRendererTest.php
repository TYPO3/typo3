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

namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheDataCollectorInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface as CacheFrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\ExpressionLanguage\DefaultProvider;
use TYPO3\CMS\Core\ExpressionLanguage\ProviderConfigurationLoader;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LossyTokenizer;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\CaseContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayInternalContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Event\BeforeStdWrapContentStoredInCacheEvent;
use TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler;
use TYPO3\CMS\Frontend\ContentObject\FilesContentObject;
use TYPO3\CMS\Frontend\ContentObject\FluidTemplateContentObject;
use TYPO3\CMS\Frontend\ContentObject\HierarchicalMenuContentObject;
use TYPO3\CMS\Frontend\ContentObject\ImageContentObject;
use TYPO3\CMS\Frontend\ContentObject\ImageResourceContentObject;
use TYPO3\CMS\Frontend\ContentObject\LoadRegisterContentObject;
use TYPO3\CMS\Frontend\ContentObject\RecordsContentObject;
use TYPO3\CMS\Frontend\ContentObject\RestoreRegisterContentObject;
use TYPO3\CMS\Frontend\ContentObject\ScalableVectorGraphicsContentObject;
use TYPO3\CMS\Frontend\ContentObject\TextContentObject;
use TYPO3\CMS\Frontend\ContentObject\UserContentObject;
use TYPO3\CMS\Frontend\ContentObject\UserInternalContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Fixtures\TestSanitizerBuilder;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\LinkResult;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ContentObjectRendererTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;
    protected bool $backupEnvironment = true;

    private ContentObjectRenderer&MockObject&AccessibleObjectInterface $subject;
    private TypoScriptFrontendController&MockObject&AccessibleObjectInterface $frontendControllerMock;
    private CacheManager&MockObject $cacheManagerMock;

    /**
     * Default content object name -> class name map, shipped with TYPO3 CMS
     */
    private array $contentObjectMap = [
        'TEXT' => TextContentObject::class,
        'CASE' => CaseContentObject::class,
        'COBJ_ARRAY' => ContentObjectArrayContentObject::class,
        'COA' => ContentObjectArrayContentObject::class,
        'COA_INT' => ContentObjectArrayInternalContentObject::class,
        'USER' => UserContentObject::class,
        'USER_INT' => UserInternalContentObject::class,
        'FILES' => FilesContentObject::class,
        'IMAGE' => ImageContentObject::class,
        'IMG_RESOURCE' => ImageResourceContentObject::class,
        'CONTENT' => ContentContentObject::class,
        'RECORDS' => RecordsContentObject::class,
        'HMENU' => HierarchicalMenuContentObject::class,
        'CASEFUNC' => CaseContentObject::class,
        'LOAD_REGISTER' => LoadRegisterContentObject::class,
        'RESTORE_REGISTER' => RestoreRegisterContentObject::class,
        'FLUIDTEMPLATE' => FluidTemplateContentObject::class,
        'SVG' => ScalableVectorGraphicsContentObject::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['SIM_ACCESS_TIME'] = 1534278180;
        $this->frontendControllerMock =
            $this->getAccessibleMock(
                TypoScriptFrontendController::class,
                ['sL'],
                [],
                '',
                false
            );
        $this->frontendControllerMock->_set('context', new Context());
        $this->frontendControllerMock->config = [];

        $this->cacheManagerMock = $this->getMockBuilder(CacheManager::class)->disableOriginalConstructor()->getMock();
        GeneralUtility::setSingletonInstance(CacheManager::class, $this->cacheManagerMock);

        $this->subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            ['getResourceFactory', 'getEnvironmentVariable'],
            [$this->frontendControllerMock]
        );

        $logger = new NullLogger();
        $this->subject->setLogger($logger);
        $request = new ServerRequest();
        $this->subject->setRequest($request);

        // @todo: This thing needs a review and should be refactored away, for instance by putting
        //        whatever is really needed into single tests instead. Also, ContentObjectFactory
        //        needs an overhaul in general.
        $contentObjectFactoryMock = $this->createContentObjectFactoryMock();
        $cObj = $this->subject;
        foreach ($this->contentObjectMap as $name => $className) {
            $contentObjectFactoryMock->addGetContentObjectCallback($name, $className, $request, $cObj);
        }
        $container = new Container();
        $container->set(ContentObjectFactory::class, $contentObjectFactoryMock);
        $container->set(EventDispatcherInterface::class, new NoopEventDispatcher());
        GeneralUtility::setContainer($container);

        $this->subject->start([], 'tt_content');
    }

    //////////////////////
    // Utility functions
    //////////////////////
    /**
     * Converts the subject and the expected result into utf-8.
     *
     * @param string $subject the subject, will be modified
     * @param string $expected the expected result, will be modified
     */
    private function handleCharset(string &$subject, string &$expected): void
    {
        $subject = mb_convert_encoding($subject, 'utf-8', 'iso-8859-1');
        $expected = mb_convert_encoding($expected, 'utf-8', 'iso-8859-1');
    }

    private static function getLibParseFunc_RTE(): array
    {
        return [
            'parseFunc' => '',
            'parseFunc.' => [
                'allowTags' => 'a, abbr, acronym, address, article, aside, b, bdo, big, blockquote, br, caption, center, cite, code, col, colgroup, dd, del, dfn, dl, div, dt, em, font, footer, header, h1, h2, h3, h4, h5, h6, hr, i, img, ins, kbd, label, li, link, meta, nav, ol, p, pre, q, samp, sdfield, section, small, span, strike, strong, style, sub, sup, table, thead, tbody, tfoot, td, th, tr, title, tt, u, ul, var',
                'constants' => '1',
                'denyTags' => '*',
                'externalBlocks' => 'article, aside, blockquote, div, dd, dl, footer, header, nav, ol, section, table, ul, pre',
                'externalBlocks.' => [
                    'article.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'aside.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'blockquote.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'dd.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'div.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'dl.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'footer.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'header.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'nav.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'ol.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'section.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'table.' => [
                        'HTMLtableCells' => '1',
                        'HTMLtableCells.' => [
                            'addChr10BetweenParagraphs' => '1',
                            'default.' => [
                                'stdWrap.' => [
                                    'parseFunc' => '=< lib.parseFunc_RTE',
                                    'parseFunc.' => [
                                        'nonTypoTagStdWrap.' => [
                                            'encapsLines.' => [
                                                'nonWrappedTag' => '',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'stdWrap.' => [
                            'HTMLparser' => '1',
                            'HTMLparser.' => [
                                'keepNonMatchedTags' => '1',
                                'tags.' => [
                                    'table.' => [
                                        'fixAttrib.' => [
                                            'class.' => [
                                                'always' => '1',
                                                'default' => 'contenttable',
                                                'list' => 'contenttable',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'stripNL' => '1',
                    ],
                    'ul.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                ],
                'makelinks' => '1',
                'makelinks.' => [
                    'http.' => [
                        'extTarget.' => [
                            'override' => '_blank',
                        ],
                        'keep' => 'path',
                    ],
                ],
                'nonTypoTagStdWrap.' => [
                    'encapsLines.' => [
                        'addAttributes.' => [
                            'P.' => [
                                'class' => 'bodytext',
                                'class.' => [
                                    'setOnly' => 'blank',
                                ],
                            ],
                        ],
                        'encapsTagList' => 'p,pre,h1,h2,h3,h4,h5,h6,hr,dt,li',
                        'innerStdWrap_all.' => [
                            'ifBlank' => '&nbsp;',
                        ],
                        'nonWrappedTag' => 'P',
                        'remapTag.' => [
                            'DIV' => 'P',
                        ],
                    ],
                    'HTMLparser' => '1',
                    'HTMLparser.' => [
                        'htmlSpecialChars' => '2',
                        'keepNonMatchedTags' => '1',
                    ],
                ],
                'sword' => '<span class="csc-sword">|</span>',
                'tags.' => [
                    'link' => 'TEXT',
                    'link.' => [
                        'current' => '1',
                        'parseFunc.' => [
                            'constants' => '1',
                        ],
                        'typolink.' => [
                            'directImageLink' => false,
                            'extTarget.' => [
                                'override' => '',
                            ],
                            'parameter.' => [
                                'data' => 'parameters : allParams',
                            ],
                            'target.' => [
                                'override' => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function createSiteWithLanguage(array $languageConfiguration): Site
    {
        return new Site('test', 1, [
            'identifier' => 'test',
            'rootPageId' => 1,
            'base' => '/',
            'languages' => [
                array_merge(
                    $languageConfiguration,
                    [
                        'base' => '/',
                    ]
                ),
            ],
        ]);
    }

    //////////////////////////////////////
    // Tests related to getContentObject
    //////////////////////////////////////
    /**
     * @see ContentObjectRendererTest::canRegisterAContentObjectClassForATypoScriptName
     */
    #[Test]
    public function willReturnNullForUnregisteredObject(): void
    {
        $object = $this->subject->getContentObject('FOO');
        self::assertNull($object);
    }

    //////////////////////////
    // Tests concerning crop
    //////////////////////////
    /**
     * @see \TYPO3\CMS\Core\Tests\Unit\Html\HtmlCropperTest
     * @see \TYPO3\CMS\Core\Tests\Unit\Text\TextCropperTest
     */
    #[Test]
    public function cropIsMultibyteSafe(): void
    {
        self::assertEquals('бла', $this->subject->crop('бла', '3|...'));
    }

    //////////////////////////////
    // Tests concerning cropHTML
    //////////////////////////////
    /**
     * Data provider for cropHTML.
     *
     * Provides combinations of text type and configuration.
     *
     * @return array [$expect, $conf, $content]
     */
    public static function cropHTMLDataProvider(): array
    {
        $plainText = 'Kasper Sk' . chr(229) . 'rh' . chr(248)
            . 'j implemented the original version of the crop function.';
        $textWithMarkup = '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
            . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong> the '
            . 'original version of the crop function.';
        $textWithEntities = 'Kasper Sk&aring;rh&oslash;j implemented the; '
            . 'original version of the crop function.';
        $textWithLinebreaks = "Lorem ipsum dolor sit amet,\n"
            . "consetetur sadipscing elitr,\n"
            . 'sed diam nonumy eirmod tempor invidunt ut labore e'
            . 't dolore magna aliquyam';
        $textWith2000Chars = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ips &amp;. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vesti&amp;';
        $textWith1000AmpHtmlEntity = str_repeat('&amp;', 1000);
        $textWith2000AmpHtmlEntity = str_repeat('&amp;', 2000);

        return [
            'plain text; 11|...' => [
                'Kasper Sk' . chr(229) . 'r...',
                $plainText,
                '11|...',
            ],
            'plain text; -58|...' => [
                '...h' . chr(248) . 'j implemented the original version of '
                . 'the crop function.',
                $plainText,
                '-58|...',
            ],
            'plain text; 4|...|1' => [
                'Kasp...',
                $plainText,
                '4|...|1',
            ],
            'plain text; 20|...|1' => [
                'Kasper Sk' . chr(229) . 'rh' . chr(248) . 'j...',
                $plainText,
                '20|...|1',
            ],
            'plain text; -5|...|1' => [
                '...tion.',
                $plainText,
                '-5|...|1',
            ],
            'plain text; -49|...|1' => [
                '...the original version of the crop function.',
                $plainText,
                '-49|...|1',
            ],
            'text with markup; 11|...' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'r...</a></strong>',
                $textWithMarkup,
                '11|...',
            ],
            'text with markup; 13|...' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . '...</a></strong>',
                $textWithMarkup,
                '13|...',
            ],
            'text with markup; 14|...' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
                $textWithMarkup,
                '14|...',
            ],
            'text with markup; 15|...' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a> ...</strong>',
                $textWithMarkup,
                '15|...',
            ],
            'text with markup; 29|...' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong> '
                . 'th...',
                $textWithMarkup,
                '29|...',
            ],
            'text with markup; -58|...' => [
                '<strong><a href="mailto:kasper@typo3.org">...h' . chr(248)
                . 'j</a> implemented</strong> the original version of the crop '
                . 'function.',
                $textWithMarkup,
                '-58|...',
            ],
            'text with markup 4|...|1' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasp...</a>'
                . '</strong>',
                $textWithMarkup,
                '4|...|1',
            ],
            'text with markup; 11|...|1' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper...</a>'
                . '</strong>',
                $textWithMarkup,
                '11|...|1',
            ],
            'text with markup; 13|...|1' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper...</a>'
                . '</strong>',
                $textWithMarkup,
                '13|...|1',
            ],
            'text with markup; 14|...|1' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
                $textWithMarkup,
                '14|...|1',
            ],
            'text with markup; 15|...|1' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a>...</strong>',
                $textWithMarkup,
                '15|...|1',
            ],
            'text with markup; 29|...|1' => [
                '<strong><a href="mailto:kasper@typo3.org">Kasper Sk'
                . chr(229) . 'rh' . chr(248) . 'j</a> implemented</strong>...',
                $textWithMarkup,
                '29|...|1',
            ],
            'text with markup; -66|...|1' => [
                '<strong><a href="mailto:kasper@typo3.org">...Sk' . chr(229)
                . 'rh' . chr(248) . 'j</a> implemented</strong> the original v'
                . 'ersion of the crop function.',
                $textWithMarkup,
                '-66|...|1',
            ],
            'text with entities 9|...' => [
                'Kasper Sk...',
                $textWithEntities,
                '9|...',
            ],
            'text with entities 10|...' => [
                'Kasper Sk&aring;...',
                $textWithEntities,
                '10|...',
            ],
            'text with entities 11|...' => [
                'Kasper Sk&aring;r...',
                $textWithEntities,
                '11|...',
            ],
            'text with entities 13|...' => [
                'Kasper Sk&aring;rh&oslash;...',
                $textWithEntities,
                '13|...',
            ],
            'text with entities 14|...' => [
                'Kasper Sk&aring;rh&oslash;j...',
                $textWithEntities,
                '14|...',
            ],
            'text with entities 15|...' => [
                'Kasper Sk&aring;rh&oslash;j ...',
                $textWithEntities,
                '15|...',
            ],
            'text with entities 16|...' => [
                'Kasper Sk&aring;rh&oslash;j i...',
                $textWithEntities,
                '16|...',
            ],
            'text with entities -57|...' => [
                '...j implemented the; original version of the crop function.',
                $textWithEntities,
                '-57|...',
            ],
            'text with entities -58|...' => [
                '...&oslash;j implemented the; original version of the crop '
                . 'function.',
                $textWithEntities,
                '-58|...',
            ],
            'text with entities -59|...' => [
                '...h&oslash;j implemented the; original version of the crop '
                . 'function.',
                $textWithEntities,
                '-59|...',
            ],
            'text with entities 4|...|1' => [
                'Kasp...',
                $textWithEntities,
                '4|...|1',
            ],
            'text with entities 9|...|1' => [
                'Kasper...',
                $textWithEntities,
                '9|...|1',
            ],
            'text with entities 10|...|1' => [
                'Kasper...',
                $textWithEntities,
                '10|...|1',
            ],
            'text with entities 11|...|1' => [
                'Kasper...',
                $textWithEntities,
                '11|...|1',
            ],
            'text with entities 13|...|1' => [
                'Kasper...',
                $textWithEntities,
                '13|...|1',
            ],
            'text with entities 14|...|1' => [
                'Kasper Sk&aring;rh&oslash;j...',
                $textWithEntities,
                '14|...|1',
            ],
            'text with entities 15|...|1' => [
                'Kasper Sk&aring;rh&oslash;j...',
                $textWithEntities,
                '15|...|1',
            ],
            'text with entities 16|...|1' => [
                'Kasper Sk&aring;rh&oslash;j...',
                $textWithEntities,
                '16|...|1',
            ],
            'text with entities -57|...|1' => [
                '...implemented the; original version of the crop function.',
                $textWithEntities,
                '-57|...|1',
            ],
            'text with entities -58|...|1' => [
                '...implemented the; original version of the crop function.',
                $textWithEntities,
                '-58|...|1',
            ],
            'text with entities -59|...|1' => [
                '...implemented the; original version of the crop function.',
                $textWithEntities,
                '-59|...|1',
            ],
            'text with dash in html-element 28|...|1' => [
                'Some text with a link to <link email.address@example.org - '
                . 'mail "Open email window">my...</link>',
                'Some text with a link to <link email.address@example.org - m'
                . 'ail "Open email window">my email.address@example.org<'
                . '/link> and text after it',
                '28|...|1',
            ],
            'html elements with dashes in attributes' => [
                '<em data-foo="x">foobar</em>foo',
                '<em data-foo="x">foobar</em>foo',
                '9',
            ],
            'html elements with iframe embedded 24|...|1' => [
                'Text with iframe <iframe src="//what.ever/"></iframe> and...',
                'Text with iframe <iframe src="//what.ever/">'
                . '</iframe> and text after it',
                '24|...|1',
            ],
            'html elements with script tag embedded 24|...|1' => [
                'Text with script <script>alert(\'foo\');</script> and...',
                'Text with script <script>alert(\'foo\');</script> '
                . 'and text after it',
                '24|...|1',
            ],
            'text with linebreaks' => [
                "Lorem ipsum dolor sit amet,\nconsetetur sadipscing elitr,\ns"
                . 'ed diam nonumy eirmod tempor invidunt ut labore e'
                . 't dolore magna',
                $textWithLinebreaks,
                '121',
            ],
            'long text under the crop limit' => [
                'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit' . ' ...',
                $textWith2000Chars,
                '962|...',
            ],
            'long text above the crop limit' => [
                'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ips &amp;. N' . '...',
                $textWith2000Chars,
                '1000|...',
            ],
            'long text above the crop limit #2' => [
                'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue. Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus, sem quam semper libero, sit amet adipiscing sem neque sed ips &amp;. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante. Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna. Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vesti&amp;' . '...',
                $textWith2000Chars . $textWith2000Chars,
                '2000|...',
            ],
            // ensure that large number of html entities do not break the the regexp splittin
            'long text with large number of html entities' => [
                $textWith1000AmpHtmlEntity . '...',
                $textWith2000AmpHtmlEntity,
                '1000|...',
            ],
        ];
    }

    /**
     * Check if cropHTML works properly.
     *
     * @param string $expect The expected cropped output.
     * @param string $content The given input.
     * @param string $conf The given configuration.
     *
     * Tests are kept due ensure parameter splitting works, also they are mostly duplicates of directly implemented
     * HtmlCropper and TextCropper tests.
     * @see \TYPO3\CMS\Core\Tests\Unit\Html\HtmlCropperTest
     * @see \TYPO3\CMS\Core\Tests\Unit\Text\TextCropperTest
     */
    #[DataProvider('cropHTMLDataProvider')]
    #[Test]
    public function cropHTML(string $expect, string $content, string $conf): void
    {
        $this->handleCharset($content, $expect);
        self::assertSame(
            $expect,
            $this->subject->cropHTML($content, $conf)
        );
    }

    /**
     * Data provider for round
     *
     * @return array [$expect, $content, $conf]
     */
    public static function roundDataProvider(): array
    {
        return [
            // floats
            'down' => [1.0, 1.11, []],
            'up' => [2.0, 1.51, []],
            'rounds up from x.50' => [2.0, 1.50, []],
            'down with decimals' => [0.12, 0.1231, ['decimals' => 2]],
            'up with decimals' => [0.13, 0.1251, ['decimals' => 2]],
            'ceil' => [1.0, 0.11, ['roundType' => 'ceil']],
            'ceil does not accept decimals' => [
                1.0,
                0.111,
                [
                    'roundType' => 'ceil',
                    'decimals' => 2,
                ],
            ],
            'floor' => [2.0, 2.99, ['roundType' => 'floor']],
            'floor does not accept decimals' => [
                2.0,
                2.999,
                [
                    'roundType' => 'floor',
                    'decimals' => 2,
                ],
            ],
            'round, down' => [1.0, 1.11, ['roundType' => 'round']],
            'round, up' => [2.0, 1.55, ['roundType' => 'round']],
            'round does accept decimals' => [
                5.56,
                5.5555,
                [
                    'roundType' => 'round',
                    'decimals' => 2,
                ],
            ],
            // strings
            'emtpy string' => [0.0, '', []],
            'word string' => [0.0, 'word', []],
            'float string' => [1.0, '1.123456789', []],
            // other types
            'null' => [0.0, null, []],
            'false' => [0.0, false, []],
            'true' => [1.0, true, []],
        ];
    }

    /**
     * Check if round works properly
     *
     * Show:
     *
     *  - Different types of input are casted to float.
     *  - Configuration ceil rounds like ceil().
     *  - Configuration floor rounds like floor().
     *  - Otherwise rounds like round() and decimals can be applied.
     *  - Always returns float.
     *
     * @param float $expect The expected output.
     * @param mixed $content The given content.
     * @param array $conf The given configuration of 'round.'.
     */
    #[DataProvider('roundDataProvider')]
    #[Test]
    public function round(float $expect, mixed $content, array $conf): void
    {
        self::assertSame(
            $expect,
            $this->subject->_call('round', $content, $conf)
        );
    }

    #[Test]
    public function recursiveStdWrapProperlyRendersBasicString(): void
    {
        $stdWrapConfiguration = [
            'noTrimWrap' => '|| 123|',
            'stdWrap.' => [
                'wrap' => '<b>|</b>',
            ],
        ];
        self::assertSame(
            '<b>Test</b> 123',
            $this->subject->stdWrap('Test', $stdWrapConfiguration)
        );
    }

    #[Test]
    public function recursiveStdWrapIsOnlyCalledOnce(): void
    {
        $stdWrapConfiguration = [
            'append' => 'TEXT',
            'append.' => [
                'data' => 'register:Counter',
            ],
            'stdWrap.' => [
                'append' => 'LOAD_REGISTER',
                'append.' => [
                    'Counter.' => [
                        'prioriCalc' => 'intval',
                        'cObject' => 'TEXT',
                        'cObject.' => [
                            'data' => 'register:Counter',
                            'wrap' => '|+1',
                        ],
                    ],
                ],
            ],
        ];
        self::assertSame(
            'Counter:1',
            $this->subject->stdWrap('Counter:', $stdWrapConfiguration)
        );
    }

    /**
     * Data provider for numberFormat.
     *
     * @return array [$expect, $content, $conf]
     */
    public static function numberFormatDataProvider(): array
    {
        return [
            'testing decimals' => [
                '0.80',
                0.8,
                ['decimals' => 2],
            ],
            'testing decimals with input as string' => [
                '0.80',
                '0.8',
                ['decimals' => 2],
            ],
            'testing dec_point' => [
                '0,8',
                0.8,
                ['decimals' => 1, 'dec_point' => ','],
            ],
            'testing thousands_sep' => [
                '1.000',
                999.99,
                [
                    'decimals' => 0,
                    'thousands_sep.' => ['char' => 46],
                ],
            ],
            'testing mixture' => [
                '1.281.731,5',
                1281731.45,
                [
                    'decimals' => 1,
                    'dec_point.' => ['char' => 44],
                    'thousands_sep.' => ['char' => 46],
                ],
            ],
        ];
    }

    /**
     * Check if numberFormat works properly.
     */
    #[DataProvider('numberFormatDataProvider')]
    #[Test]
    public function numberFormat(string $expects, mixed $content, array $conf): void
    {
        self::assertSame(
            $expects,
            $this->subject->numberFormat($content, $conf)
        );
    }

    /**
     * Data provider replacement
     *
     * @return array [$expect, $content, $conf]
     */
    public static function replacementDataProvider(): array
    {
        return [
            'multiple replacements, including regex' => [
                'There is an animal, an animal and an animal around the block! Yeah!',
                'There_is_a_cat,_a_dog_and_a_tiger_in_da_hood!_Yeah!',
                [
                    '20.' => [
                        'search' => '_',
                        'replace.' => ['char' => '32'],
                    ],
                    '120.' => [
                        'search' => 'in da hood',
                        'replace' => 'around the block',
                    ],
                    '130.' => [
                        'search' => '#a (Cat|Dog|Tiger)#i',
                        'replace' => 'an animal',
                        'useRegExp' => '1',
                    ],
                ],
            ],
            'replacement with optionSplit, normal pattern' => [
                'There1is2a3cat,3a3dog3and3a3tiger3in3da3hood!3Yeah!',
                'There_is_a_cat,_a_dog_and_a_tiger_in_da_hood!_Yeah!',
                [
                    '10.' => [
                        'search' => '_',
                        'replace' => '1 || 2 || 3',
                        'useOptionSplitReplace' => '1',
                        'useRegExp' => '0',
                    ],
                ],
            ],
            'replacement with optionSplit, using regex' => [
                'There is a tiny cat, a midsized dog and a big tiger in da hood! Yeah!',
                'There is a cat, a dog and a tiger in da hood! Yeah!',
                [
                    '10.' => [
                        'search' => '#(a) (Cat|Dog|Tiger)#i',
                        'replace' => '${1} tiny ${2} || ${1} midsized ${2} || ${1} big ${2}',
                        'useOptionSplitReplace' => '1',
                        'useRegExp' => '1',
                    ],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap.replacement and all of its properties work properly
     *
     * @param string $content The given input.
     * @param string $expects The expected result.
     * @param array $conf The given configuration.
     */
    #[DataProvider('replacementDataProvider')]
    #[Test]
    public function replacement(string $expects, string $content, array $conf): void
    {
        self::assertSame(
            $expects,
            $this->subject->_call('replacement', $content, $conf)
        );
    }

    /**
     * Data provider for calcAge.
     *
     * @return array [$expect, $timestamp, $labels]
     */
    public static function calcAgeDataProvider(): array
    {
        return [
            'minutes' => [
                '2 min',
                120,
                ' min| hrs| days| yrs',
            ],
            'hours' => [
                '2 hrs',
                7200,
                ' min| hrs| days| yrs',
            ],
            'days' => [
                '7 days',
                604800,
                ' min| hrs| days| yrs',
            ],
            'day with provided singular labels' => [
                '1 day',
                86400,
                ' min| hrs| days| yrs| min| hour| day| year',
            ],
            'years' => [
                '44 yrs',
                1417997800,
                ' min| hrs| days| yrs',
            ],
            'different labels' => [
                '2 Minutes',
                120,
                ' Minutes| Hrs| Days| Yrs',
            ],
            'negative values' => [
                '-7 days',
                -604800,
                ' min| hrs| days| yrs',
            ],
            'default label values for wrong label input' => [
                '2 min',
                121,
                '10',
            ],
            'default singular label values for wrong label input' => [
                '1 year',
                31536000,
                '10',
            ],
        ];
    }

    /**
     * Check if calcAge works properly.
     */
    #[DataProvider('calcAgeDataProvider')]
    #[Test]
    public function calcAge(string $expect, int $timestamp, string $labels): void
    {
        // Set exec_time to a hard timestamp, since age calculation depends on current date
        $GLOBALS['EXEC_TIME'] = 1417392000;
        self::assertSame(
            $expect,
            $this->subject->calcAge($timestamp, $labels)
        );
    }

    public static function stdWrapReturnsExpectationDataProvider(): array
    {
        return [
            'Prevent silent bool conversion' => [
                '1+1',
                [
                    'prioriCalc.' => [
                        'wrap' => '|',
                    ],
                ],
                '1+1',
            ],
        ];
    }

    #[DataProvider('stdWrapReturnsExpectationDataProvider')]
    #[Test]
    public function stdWrapReturnsExpectation(string $content, array $configuration, string $expectation): void
    {
        self::assertSame($expectation, $this->subject->stdWrap($content, $configuration));
    }

    public static function stdWrapDoesOnlyCallIfEmptyIfTheTrimmedContentIsEmptyOrZeroDataProvider(): array
    {
        return [
            'ifEmpty is not called if content is present as an non-empty string' => [
                'content' => 'some content',
                'ifEmptyShouldBeCalled' => false,
            ],
            'ifEmpty is not called if content is present as the string "1"' => [
                'content' => '1',
                'ifEmptyShouldBeCalled' => false,
            ],
            'ifEmpty is called if content is present as an empty string' => [
                'content' => '',
                'ifEmptyShouldBeCalled' => true,
            ],
            'ifEmpty is called if content is present as the string "0"' => [
                'content' => '0',
                'ifEmptyShouldBeCalled' => true,
            ],
        ];
    }

    #[DataProvider('stdWrapDoesOnlyCallIfEmptyIfTheTrimmedContentIsEmptyOrZeroDataProvider')]
    #[Test]
    public function stdWrapDoesOnlyCallIfEmptyIfTheTrimmedContentIsEmptyOrZero(string $content, bool $ifEmptyShouldBeCalled): void
    {
        $conf = [
            'ifEmpty.' => [
                'cObject' => 'TEXT',
            ],
        ];

        $subject = $this->getAccessibleMock(ContentObjectRenderer::class, ['stdWrap_ifEmpty']);
        $request = new ServerRequest();
        $subject->setRequest($request);
        $subject->expects(self::exactly(($ifEmptyShouldBeCalled ? 1 : 0)))
            ->method('stdWrap_ifEmpty');

        $subject->stdWrap($content, $conf);
    }

    /**
     * Data provider for substring
     *
     * @return array [$expect, $content, $conf]
     */
    public static function substringDataProvider(): array
    {
        return [
            'sub -1' => ['g', 'substring', '-1'],
            'sub -1,0' => ['g', 'substring', '-1,0'],
            'sub -1,-1' => ['', 'substring', '-1,-1'],
            'sub -1,1' => ['g', 'substring', '-1,1'],
            'sub 0' => ['substring', 'substring', '0'],
            'sub 0,0' => ['substring', 'substring', '0,0'],
            'sub 0,-1' => ['substrin', 'substring', '0,-1'],
            'sub 0,1' => ['s', 'substring', '0,1'],
            'sub 1' => ['ubstring', 'substring', '1'],
            'sub 1,0' => ['ubstring', 'substring', '1,0'],
            'sub 1,-1' => ['ubstrin', 'substring', '1,-1'],
            'sub 1,1' => ['u', 'substring', '1,1'],
            'sub' => ['substring', 'substring', ''],
        ];
    }

    /**
     * Check if substring works properly.
     *
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @param string $conf The given configuration.
     */
    #[DataProvider('substringDataProvider')]
    #[Test]
    public function substring(string $expect, string $content, string $conf): void
    {
        self::assertSame($expect, $this->subject->substring($content, $conf));
    }

    public static function getDataWithTypeGpDataProvider(): array
    {
        return [
            'Value in get-data' => ['onlyInGet', 'GetValue'],
            'Value in post-data' => ['onlyInPost', 'PostValue'],
            'Value in post-data overriding get-data' => ['inGetAndPost', 'ValueInPost'],
        ];
    }

    /**
     * Checks if getData() works with type "gp"
     */
    #[DataProvider('getDataWithTypeGpDataProvider')]
    #[Test]
    public function getDataWithTypeGp(string $key, string $expectedValue): void
    {
        $queryArguments = [
            'onlyInGet' => 'GetValue',
            'inGetAndPost' => 'ValueInGet',
        ];
        $postParameters = [
            'onlyInPost' => 'PostValue',
            'inGetAndPost' => 'ValueInPost',
        ];
        $request = new ServerRequest('https://example.com');
        $request = $request->withQueryParams($queryArguments)->withParsedBody($postParameters);
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        self::assertEquals($expectedValue, $this->subject->getData('gp:' . $key));
    }

    /**
     * Checks if getData() works with type "getenv"
     */
    #[Test]
    public function getDataWithTypeGetenv(): void
    {
        $envName = StringUtility::getUniqueId('frontendtest');
        $value = StringUtility::getUniqueId('someValue');
        putenv($envName . '=' . $value);
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        self::assertEquals($value, $this->subject->getData('getenv:' . $envName));
    }

    /**
     * Checks if getData() works with type "getindpenv"
     */
    #[Test]
    public function getDataWithTypeGetindpenv(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        $this->subject->expects(self::once())->method('getEnvironmentVariable')
            ->with(self::equalTo('SCRIPT_FILENAME'))->willReturn('dummyPath');
        self::assertEquals('dummyPath', $this->subject->getData('getindpenv:SCRIPT_FILENAME'));
    }

    /**
     * Checks if getData() works with type "field"
     */
    #[Test]
    public function getDataWithTypeField(): void
    {
        $key = 'someKey';
        $value = 'someValue';
        $field = [$key => $value];
        self::assertEquals($value, $this->subject->getData('field:' . $key, $field));
    }

    /**
     * Checks if getData() works with type "field" of the field content
     * is multi-dimensional (e.g. an array)
     */
    #[Test]
    public function getDataWithTypeFieldAndFieldIsMultiDimensional(): void
    {
        $key = 'somekey|level1|level2';
        $value = 'somevalue';
        $field = ['somekey' => ['level1' => ['level2' => 'somevalue']]];

        self::assertEquals($value, $this->subject->getData('field:' . $key, $field));
    }

    public static function getDataWithTypeFileReturnsUidOfFileObjectDataProvider(): array
    {
        return [
            'no whitespace' => [
                'typoScriptPath' => 'file:current:uid',
            ],
            'always whitespace' => [
                'typoScriptPath' => 'file : current : uid',
            ],
            'mixed whitespace' => [
                'typoScriptPath' => 'file:current : uid',
            ],
        ];
    }

    /**
     * Basic check if getData gets the uid of a file object
     */
    #[DataProvider('getDataWithTypeFileReturnsUidOfFileObjectDataProvider')]
    #[Test]
    public function getDataWithTypeFileReturnsUidOfFileObject(string $typoScriptPath): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        $uid = StringUtility::getUniqueId();
        $file = $this->createMock(File::class);
        $file->expects(self::once())->method('getUid')->willReturn($uid);
        $this->subject->setCurrentFile($file);
        self::assertEquals($uid, $this->subject->getData($typoScriptPath));
    }

    /**
     * Checks if getData() works with type "parameters"
     */
    #[Test]
    public function getDataWithTypeParameters(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $this->subject->parameters[$key] = $value;
        self::assertEquals($value, $this->subject->getData('parameters:' . $key));
    }

    /**
     * Checks if getData() works with type "register"
     */
    #[Test]
    public function getDataWithTypeRegister(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $GLOBALS['TSFE'] = $this->frontendControllerMock;
        $GLOBALS['TSFE']->register[$key] = $value;
        self::assertEquals($value, $this->subject->getData('register:' . $key));
    }

    /**
     * Checks if getData() works with type "session"
     */
    #[Test]
    public function getDataWithTypeSession(): void
    {
        $frontendUser = $this->getMockBuilder(FrontendUserAuthentication::class)
            ->onlyMethods(['getSessionData'])
            ->getMock();
        $frontendUser->expects(self::once())->method('getSessionData')->with('myext')->willReturn([
            'mydata' => [
                'someValue' => 42,
            ],
        ]);
        $request = (new ServerRequest())->withAttribute('frontend.user', $frontendUser);
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        self::assertEquals(42, $this->subject->getData('session:myext|mydata|someValue'));
    }

    /**
     * Checks if getData() works with type "level"
     */
    #[Test]
    public function getDataWithTypeLevel(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $pageInformation->setLocalRootLine([
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => 'title3'],
        ]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        self::assertEquals(2, $this->subject->getData('level'));
    }

    /**
     * Checks if getData() works with type "leveltitle"
     */
    #[Test]
    public function getDataWithTypeLeveltitle(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $pageInformation->setLocalRootLine([
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => ''],
        ]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        self::assertEquals('', $this->subject->getData('leveltitle:-1'));
        // since "title3" is not set, it will slide to "title2"
        self::assertEquals('title2', $this->subject->getData('leveltitle:-1,slide'));
    }

    /**
     * Checks if getData() works with type "levelmedia"
     */
    #[Test]
    public function getDataWithTypeLevelmedia(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $pageInformation->setLocalRootLine([
            0 => ['uid' => 1, 'title' => 'title1', 'media' => 'media1'],
            1 => ['uid' => 2, 'title' => 'title2', 'media' => 'media2'],
            2 => ['uid' => 3, 'title' => 'title3', 'media' => ''],
        ]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        self::assertEquals('', $this->subject->getData('levelmedia:-1'));
        // since "title3" is not set, it will slide to "title2"
        self::assertEquals('media2', $this->subject->getData('levelmedia:-1,slide'));
    }

    /**
     * Checks if getData() works with type "leveluid"
     */
    #[Test]
    public function getDataWithTypeLeveluid(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $pageInformation->setLocalRootLine([
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => 'title3'],
        ]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        self::assertEquals(3, $this->subject->getData('leveluid:-1'));
        // every element will have a uid - so adding slide doesn't really make sense, just for completeness
        self::assertEquals(3, $this->subject->getData('leveluid:-1,slide'));
    }

    /**
     * Checks if getData() works with type "levelfield"
     */
    #[Test]
    public function getDataWithTypeLevelfield(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $pageInformation->setLocalRootLine([
            0 => ['uid' => 1, 'title' => 'title1', 'testfield' => 'field1'],
            1 => ['uid' => 2, 'title' => 'title2', 'testfield' => 'field2'],
            2 => ['uid' => 3, 'title' => 'title3', 'testfield' => ''],
        ]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        self::assertEquals('', $this->subject->getData('levelfield:-1,testfield'));
        self::assertEquals('field2', $this->subject->getData('levelfield:-1,testfield,slide'));
    }

    /**
     * Checks if getData() works with type "fullrootline"
     */
    #[Test]
    public function getDataWithTypeFullrootline(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $pageInformation->setRootLine([
            0 => ['uid' => 1, 'title' => 'title1', 'testfield' => 'field1'],
            1 => ['uid' => 2, 'title' => 'title2', 'testfield' => 'field2'],
            2 => ['uid' => 3, 'title' => 'title3', 'testfield' => 'field3'],
        ]);
        $pageInformation->setLocalRootLine([
            0 => ['uid' => 1, 'title' => 'title1', 'testfield' => 'field1'],
        ]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        self::assertEquals('field2', $this->subject->getData('fullrootline:-1,testfield'));
    }

    /**
     * Checks if getData() works with type "date"
     */
    #[Test]
    public function getDataWithTypeDate(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        $format = 'Y-M-D';
        $defaultFormat = 'd/m Y';
        self::assertEquals(date($format, $GLOBALS['EXEC_TIME']), $this->subject->getData('date:' . $format));
        self::assertEquals(date($defaultFormat, $GLOBALS['EXEC_TIME']), $this->subject->getData('date'));
    }

    /**
     * Checks if getData() works with type "page"
     */
    #[Test]
    public function getDataWithTypePage(): void
    {
        $pageInformation = new PageInformation();
        $uid = random_int(0, mt_getrandmax());
        $pageInformation->setPageRecord(['uid' => $uid]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        self::assertEquals($uid, $this->subject->getData('page:uid'));
    }

    /**
     * Checks if getData() works with type "current"
     */
    #[Test]
    public function getDataWithTypeCurrent(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $this->subject->data[$key] = $value;
        $this->subject->currentValKey = $key;
        self::assertEquals($value, $this->subject->getData('current'));
    }

    #[Test]
    public function getDataWithTypeDbReturnsCorrectTitle()
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        $dummyRecord = ['uid' => 5, 'title' => 'someTitle'];
        $pageRepository = $this->createMock(PageRepository::class);
        GeneralUtility::addInstance(PageRepository::class, $pageRepository);
        $pageRepository->expects(self::once())->method('getRawRecord')->with('tt_content', '106')->willReturn($dummyRecord);
        self::assertSame('someTitle', $this->subject->getData('db:tt_content:106:title'));
    }

    public static function getDataWithTypeDbReturnsEmptyStringOnInvalidIdentifiersDataProvider(): array
    {
        return [
            'identifier with missing table, uid and column' => [
                'identifier' => 'db',
            ],
            'identifier with empty table, missing uid and column' => [
                'identifier' => 'db:',
            ],
            'identifier with missing table and column' => [
                'identifier' => 'db:tt_content',
            ],
            'identifier with empty table and missing uid and column' => [
                'identifier' => 'db:tt_content:',
            ],
        ];
    }

    #[DataProvider('getDataWithTypeDbReturnsEmptyStringOnInvalidIdentifiersDataProvider')]
    #[Test]
    public function getDataWithTypeDbReturnsEmptyStringOnInvalidIdentifiers(string $identifier): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        self::assertSame('', $this->subject->getData($identifier));
    }

    public static function getDataWithTypeDbReturnsEmptyStringOnInvalidIdentifiersCallsPageRepositoryOnceDataProvider(): array
    {
        return [
            'identifier with empty uid and missing column' => [
                'identifier' => 'db:tt_content:106',
            ],
            'identifier with empty uid and column' => [
                'identifier' => 'db:tt_content:106:',
            ],
            'identifier with empty uid and not existing column' => [
                'identifier' => 'db:tt_content:106:not_existing_column',
            ],
        ];
    }

    #[DataProvider('getDataWithTypeDbReturnsEmptyStringOnInvalidIdentifiersCallsPageRepositoryOnceDataProvider')]
    #[Test]
    public function getDataWithTypeDbReturnsEmptyStringOnInvalidIdentifiersCallsPageRepositoryOnce(string $identifier): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        $dummyRecord = ['uid' => 5, 'title' => 'someTitle'];
        $pageRepository = $this->createMock(PageRepository::class);
        GeneralUtility::addInstance(PageRepository::class, $pageRepository);
        $pageRepository->expects(self::once())->method('getRawRecord')->with('tt_content', '106')->willReturn($dummyRecord);
        self::assertSame('', $this->subject->getData($identifier));
    }

    #[Test]
    public function getDataWithTypeLll(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $language = $this->createMock(SiteLanguage::class);
        $request = $request->withAttribute('language', $language);
        $this->subject->setRequest($request);
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $languageServiceFactory = $this->createMock(LanguageServiceFactory::class);
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceFactory->expects(self::once())->method('createFromSiteLanguage')->with(self::anything())->willReturn($languageServiceMock);
        GeneralUtility::addInstance(LanguageServiceFactory::class, $languageServiceFactory);
        $languageServiceMock->expects(self::once())->method('sL')->with('LLL:' . $key)->willReturn($value);
        self::assertEquals($value, $this->subject->getData('lll:' . $key));
    }

    /**
     * Checks if getData() works with type "path"
     */
    #[Test]
    public function getDataWithTypePath(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        $filenameIn = 'typo3/sysext/frontend/Public/Icons/Extension.svg';
        self::assertEquals($filenameIn, $this->subject->getData('path:' . $filenameIn));
    }

    /**
     * Checks if getData() works with type "context"
     */
    #[Test]
    public function getDataWithTypeContext(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(3));
        $context->setAspect('frontend.user', new UserAspect(new FrontendUserAuthentication(), [0, -1]));
        GeneralUtility::setSingletonInstance(Context::class, $context);
        self::assertEquals(3, $this->subject->getData('context:workspace:id'));
        self::assertEquals('0,-1', $this->subject->getData('context:frontend.user:groupIds'));
        self::assertFalse($this->subject->getData('context:frontend.user:isLoggedIn'));
        self::assertSame('', $this->subject->getData('context:frontend.user:foozball'));
    }

    /**
     * Checks if getData() works with type "site"
     */
    #[Test]
    public function getDataWithTypeSite(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $site = new Site('my-site', 123, [
            'base' => 'http://example.com',
            'custom' => [
                'config' => [
                    'nested' => 'yeah',
                ],
            ],
        ]);
        $request = (new ServerRequest('https://example.com'))
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('site', $site);
        $this->subject->setRequest($request);
        self::assertEquals('http://example.com', $this->subject->getData('site:base'));
        self::assertEquals('yeah', $this->subject->getData('site:custom.config.nested'));
    }

    /**
     * Checks if getData() works with type "site" and base variants
     */
    #[Test]
    public function getDataWithTypeSiteWithBaseVariants(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);

        $packageManager = new PackageManager(new DependencyOrderingService());
        GeneralUtility::addInstance(ProviderConfigurationLoader::class, new ProviderConfigurationLoader(
            $packageManager,
            new NullFrontend('core'),
            'ExpressionLanguageProviders'
        ));
        GeneralUtility::addInstance(DefaultProvider::class, new DefaultProvider(new Typo3Version(), new Context(), new Features()));

        putenv('LOCAL_DEVELOPMENT=1');

        $site = new Site('my-site', 123, [
            'base' => 'http://prod.com',
            'baseVariants' => [
                [
                    'base' => 'http://staging.com',
                    'condition' => 'applicationContext == "Production/Staging"',
                ],
                [
                    'base' => 'http://dev.com',
                    'condition' => 'getenv("LOCAL_DEVELOPMENT") == 1',
                ],
            ],
        ]);

        $request = (new ServerRequest('https://example.com'))
            ->withAttribute('frontend.page.information', $pageInformation)
            ->withAttribute('site', $site);
        $this->subject->setRequest($request);

        self::assertEquals('http://dev.com', $this->subject->getData('site:base'));
    }

    /**
     * Checks if getData() works with type "siteLanguage"
     */
    #[Test]
    public function getDataWithTypeSiteLanguage(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $site = $this->createSiteWithLanguage([
            'base' => '/',
            'languageId' => 1,
            'locale' => 'de_DE',
            'title' => 'languageTitle',
            'navigationTitle' => 'German',
        ]);
        $language = $site->getLanguageById(1);
        $request = $request->withAttribute('language', $language);
        $this->subject->setRequest($request);
        self::assertEquals('German', $this->subject->getData('siteLanguage:navigationTitle'));
        self::assertEquals('de', $this->subject->getData('siteLanguage:twoLetterIsoCode'));
        self::assertEquals('de', $this->subject->getData('siteLanguage:locale:languageCode'));
        self::assertEquals('de-DE', $this->subject->getData('siteLanguage:hreflang'));
        self::assertEquals('de-DE', $this->subject->getData('siteLanguage:locale:full'));
    }

    /**
     * Checks if getData() works with type "parentRecordNumber"
     */
    #[Test]
    public function getDataWithTypeParentRecordNumber(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);

        $recordNumber = random_int(0, mt_getrandmax());
        $this->subject->parentRecordNumber = $recordNumber;
        self::assertEquals($recordNumber, $this->subject->getData('cobj:parentRecordNumber'));
    }

    /**
     * Checks if getData() works with type "debug:rootLine"
     */
    #[Test]
    public function getDataWithTypeDebugRootline(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $pageInformation->setLocalRootLine([
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => ''],
        ]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        $expectedResult = 'array(3items)0=>array(2items)uid=>1(integer)title=>"title1"(6chars)1=>array(2items)uid=>2(integer)title=>"title2"(6chars)2=>array(2items)uid=>3(integer)title=>""(0chars)';
        DebugUtility::useAnsiColor(false);
        $result = $this->subject->getData('debug:rootLine');
        $cleanedResult = str_replace(["\r", "\n", "\t", ' '], '', $result);

        self::assertEquals($expectedResult, $cleanedResult);
    }

    /**
     * Checks if getData() works with type "debug:fullRootLine"
     */
    #[Test]
    public function getDataWithTypeDebugFullRootline(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => ''],
        ];
        $pageInformation->setRootLine($rootline);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);

        $expectedResult = 'array(3items)0=>array(2items)uid=>1(integer)title=>"title1"(6chars)1=>array(2items)uid=>2(integer)title=>"title2"(6chars)2=>array(2items)uid=>3(integer)title=>""(0chars)';

        DebugUtility::useAnsiColor(false);
        $result = $this->subject->getData('debug:fullRootLine');
        $cleanedResult = str_replace(["\r", "\n", "\t", ' '], '', $result);

        self::assertEquals($expectedResult, $cleanedResult);
    }

    /**
     * Checks if getData() works with type "debug:data"
     */
    #[Test]
    public function getDataWithTypeDebugData(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);

        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $this->subject->data = [$key => $value];

        $expectedResult = 'array(1item)' . $key . '=>"' . $value . '"(' . strlen($value) . 'chars)';

        DebugUtility::useAnsiColor(false);
        $result = $this->subject->getData('debug:data');
        $cleanedResult = str_replace(["\r", "\n", "\t", ' '], '', $result);

        self::assertEquals($expectedResult, $cleanedResult);
    }

    /**
     * Checks if getData() works with type "debug:register"
     */
    #[Test]
    public function getDataWithTypeDebugRegister(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);

        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $GLOBALS['TSFE'] = $this->frontendControllerMock;
        $GLOBALS['TSFE']->register = [$key => $value];

        $expectedResult = 'array(1item)' . $key . '=>"' . $value . '"(' . strlen($value) . 'chars)';

        DebugUtility::useAnsiColor(false);
        $result = $this->subject->getData('debug:register');
        $cleanedResult = str_replace(["\r", "\n", "\t", ' '], '', $result);

        self::assertEquals($expectedResult, $cleanedResult);
    }

    /**
     * Checks if getData() works with type "data:page"
     */
    #[Test]
    public function getDataWithTypeDebugPage(): void
    {
        $pageInformation = new PageInformation();
        $uid = random_int(0, mt_getrandmax());
        $pageInformation->setPageRecord(['uid' => $uid]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        $expectedResult = 'array(1item)uid=>' . $uid . '(integer)';
        DebugUtility::useAnsiColor(false);
        $result = $this->subject->getData('debug:page');
        $cleanedResult = str_replace(["\r", "\n", "\t", ' '], '', $result);
        self::assertEquals($expectedResult, $cleanedResult);
    }

    /**
     * Checks if getData() works with type "applicationContext"
     */
    #[Test]
    public function getDataWithApplicationContext(): void
    {
        Environment::initialize(
            new ApplicationContext('Production'),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getPublicPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);
        self::assertSame('Production', $this->subject->getData('applicationContext'));
    }

    #[Test]
    public function renderingContentObjectThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1414513947);
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $this->subject->setRequest($request);
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($this->subject, false);
        $this->subject->render($contentObjectFixture);
    }

    #[Test]
    public function exceptionHandlerIsEnabledByDefaultInProductionContext(): void
    {
        Environment::initialize(
            new ApplicationContext('Production'),
            true,
            false,
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getPublicPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $this->subject->setRequest($request);
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($this->subject);
        $this->subject->render($contentObjectFixture);
    }

    #[Test]
    public function renderingContentObjectDoesNotThrowExceptionIfExceptionHandlerIsConfiguredLocally(): void
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($this->subject);
        $configuration = [
            'exceptionHandler' => '1',
        ];
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $this->subject->setRequest($request);
        $this->subject->render($contentObjectFixture, $configuration);
    }

    #[Test]
    public function renderingContentObjectDoesNotThrowExceptionIfExceptionHandlerIsConfiguredGlobally(): void
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($this->subject);
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([
            'contentObjectExceptionHandler' => '1',
        ]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $this->subject->setRequest($request);
        $this->subject->render($contentObjectFixture);
    }

    #[Test]
    public function globalExceptionHandlerConfigurationCanBeOverriddenByLocalConfiguration(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1414513947);
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($this->subject, false);
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([
            'contentObjectExceptionHandler' => '1',
        ]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $this->subject->setRequest($request);
        $configuration = [
            'exceptionHandler' => '0',
        ];
        $this->subject->render($contentObjectFixture, $configuration);
    }

    #[Test]
    public function renderedErrorMessageCanBeCustomized(): void
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($this->subject);
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $this->subject->setRequest($request);
        $configuration = [
            'exceptionHandler' => '1',
            'exceptionHandler.' => [
                'errorMessage' => 'New message for testing',
            ],
        ];
        self::assertSame('New message for testing', $this->subject->render($contentObjectFixture, $configuration));
    }

    #[Test]
    public function localConfigurationOverridesGlobalConfiguration(): void
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($this->subject);
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([
            'contentObjectExceptionHandler.' => [
                'errorMessage' => 'Global message for testing',
            ],
        ]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $this->subject->setRequest($request);
        $configuration = [
            'exceptionHandler' => '1',
            'exceptionHandler.' => [
                'errorMessage' => 'New message for testing',
            ],
        ];

        self::assertSame('New message for testing', $this->subject->render($contentObjectFixture, $configuration));
    }

    #[Test]
    public function specificExceptionsCanBeIgnoredByExceptionHandler(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1414513947);
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($this->subject);
        $configuration = [
            'exceptionHandler' => '1',
            'exceptionHandler.' => [
                'ignoreCodes.' => ['10.' => '1414513947'],
            ],
        ];
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $this->subject->setRequest($request);
        $this->subject->render($contentObjectFixture, $configuration);
    }

    private function createContentObjectThrowingExceptionFixture(ContentObjectRenderer $subject, bool $addProductionExceptionHandlerInstance = true): AbstractContentObject&MockObject
    {
        $contentObjectFixture = $this->getMockBuilder(AbstractContentObject::class)->getMock();
        $contentObjectFixture->expects(self::once())
            ->method('render')
            ->willReturnCallback(static function (array $conf = []): string {
                throw new \LogicException('Exception during rendering', 1414513947);
            });
        $contentObjectFixture->setContentObjectRenderer($subject);
        if ($addProductionExceptionHandlerInstance) {
            GeneralUtility::addInstance(
                ProductionExceptionHandler::class,
                new ProductionExceptionHandler(new Context(), new Random(), new NullLogger(), new RequestId())
            );
        }
        return $contentObjectFixture;
    }

    public static function _parseFuncReturnsCorrectHtmlDataProvider(): array
    {
        return [
            'Text without tag is wrapped with <p> tag' => [
                'Text without tag',
                self::getLibParseFunc_RTE(),
                '<p class="bodytext">Text without tag</p>',
                false,
            ],
            'Text wrapped with <p> tag remains the same' => [
                '<p class="myclass">Text with &lt;p&gt; tag</p>',
                self::getLibParseFunc_RTE(),
                '<p class="myclass">Text with &lt;p&gt; tag</p>',
                false,
            ],
            'Text with absolute external link' => [
                'Text with <link http://example.com/foo/>external link</link>',
                self::getLibParseFunc_RTE(),
                '<p class="bodytext">Text with <a href="http://example.com/foo/">external link</a></p>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com/foo/'))->withLinkText('external link'),
            ],
            'Empty lines are not duplicated' => [
                LF,
                self::getLibParseFunc_RTE(),
                '<p class="bodytext">&nbsp;</p>',
                false,
            ],
            'Multiple empty lines with no text' => [
                LF . LF . LF,
                self::getLibParseFunc_RTE(),
                '<p class="bodytext">&nbsp;</p>' . LF . '<p class="bodytext">&nbsp;</p>' . LF . '<p class="bodytext">&nbsp;</p>',
                false,
            ],
            'Empty lines are not duplicated at the end of content' => [
                'test' . LF . LF,
                self::getLibParseFunc_RTE(),
                '<p class="bodytext">test</p>' . LF . '<p class="bodytext">&nbsp;</p>',
                false,
            ],
            'Empty lines are not trimmed' => [
                LF . 'test' . LF,
                self::getLibParseFunc_RTE(),
                '<p class="bodytext">&nbsp;</p>' . LF . '<p class="bodytext">test</p>' . LF . '<p class="bodytext">&nbsp;</p>',
                false,
            ],
            // @todo documenting the current behavior of allowTags/denyTags=*
            // @todo probably denyTags should take precedence, which might be breaking
            'All tags are allowed, using allowTags=* and denyTags=*' => [
                '<p><em>Example</em> <u>underlined</u> text</p>',
                [
                    'parseFunc' => '1',
                    'parseFunc.' => [
                        'allowTags' => '*',
                        'denyTags' => '*',
                    ],
                ],
                '<p><em>Example</em> <u>underlined</u> text</p>',
                false,
            ],
            'Only u tags are allowed, so all others are escaped' => [
                '<p><em>Example</em> <u>underlined</u> text</p>',
                [
                    'parseFunc' => '1',
                    'parseFunc.' => [
                        'allowTags' => 'u',
                        'denyTags' => '*',
                    ],
                ],
                '&lt;p&gt;&lt;em&gt;Example&lt;/em&gt; <u>underlined</u> text&lt;/p&gt;',
                false,
            ],
            'No tags are allowed, so all are escaped' => [
                '<p><em>Example</em> <u>underlined</u> text</p>',
                [
                    'parseFunc' => '1',
                    'parseFunc.' => [
                        'denyTags' => '*',
                    ],
                ],
                '&lt;p&gt;&lt;em&gt;Example&lt;/em&gt; &lt;u&gt;underlined&lt;/u&gt; text&lt;/p&gt;',
                false,
            ],
            'No tags are denied, so all are escaped except the ones defined' => [
                '<p><em>Example</em> <u>underlined</u> text</p>',
                [
                    'parseFunc' => '1',
                    'parseFunc.' => [
                        'allowTags' => 'u',
                    ],
                ],
                '&lt;p&gt;&lt;em&gt;Example&lt;/em&gt; <u>underlined</u> text&lt;/p&gt;',
                false,
            ],
            'No tags are allowed, but some are explicitly denied' => [
                '<p><em>Example</em> <u>underlined</u> text</p>',
                [
                    'parseFunc' => '1',
                    'parseFunc.' => [
                        'denyTags' => 'em',
                    ],
                ],
                '<p>&lt;em&gt;Example&lt;/em&gt; <u>underlined</u> text</p>',
                false,
            ],
            'No tags are allowed or denied - allow everything, do not escape anything' => [
                '<p><em>Example</em> <u>underlined</u> text</p>',
                [
                    'parseFunc' => '1',
                    'parseFunc.' => [
                        // This inherits allowTags=* and htmlSanitize=1
                        'somethingElse' => '',
                    ],
                ],
                '<p><em>Example</em> <u>underlined</u> text</p>',
                false,
            ],
            'All tags are allowed' => [
                '<p><em>Example</em> <u>underlined</u> text</p>',
                [
                    'parseFunc' => '1',
                    'parseFunc.' => [
                        'allowTags' => '*',
                    ],
                ],
                '<p><em>Example</em> <u>underlined</u> text</p>',
                false,
            ],
            'All tags are allowed except a list of unwanted tags' => [
                '<p><em>Example</em> <u>underlined</u> text</p>',
                [
                    'parseFunc' => '1',
                    'parseFunc.' => [
                        'allowTags' => '*',
                        'denyTags' => 'em',
                    ],
                ],
                '<p>&lt;em&gt;Example&lt;/em&gt; <u>underlined</u> text</p>',
                false,
            ],
        ];
    }

    #[DataProvider('_parseFuncReturnsCorrectHtmlDataProvider')]
    #[Test]
    public function stdWrap_parseFuncReturnsParsedHtml(string $value, array $configuration, string $expectedResult, LinkResultInterface|false $linkResult): void
    {
        if ($linkResult !== false) {
            $linkFactory = $this->getMockBuilder(LinkFactory::class)->disableOriginalConstructor()->getMock();
            $linkFactory->method('create')->willReturn($linkResult);
            GeneralUtility::addInstance(LinkFactory::class, $linkFactory);
        }
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $this->subject->setRequest($request);
        self::assertEquals($expectedResult, $this->subject->stdWrap_parseFunc($value, $configuration));
    }

    /**
     * Data provider for the parseFuncParsesNestedTagsProperly test
     *
     * @return array multi-dimensional array with test data
     * @see parseFuncParsesNestedTagsProperly
     */
    public static function _parseFuncParsesNestedTagsProperlyDataProvider(): array
    {
        $defaultListItemParseFunc = [
            'parseFunc'  => '',
            'parseFunc.' => [
                'tags.' => [
                    'li'  => 'TEXT',
                    'li.' => [
                        'wrap'    => '<li>LI:|</li>',
                        'current' => '1',
                    ],
                ],
            ],
        ];

        return [
            'parent & child tags with same beginning are processed' => [
                '<div><any data-skip><anyother data-skip>content</anyother></any></div>',
                [
                    'parseFunc'  => '',
                    'parseFunc.' => [
                        'tags.' => [
                            'any' => 'TEXT',
                            'any.' => [
                                'wrap' => '<any data-processed>|</any>',
                                'current' => 1,
                            ],
                            'anyother' => 'TEXT',
                            'anyother.' => [
                                'wrap' => '<anyother data-processed>|</anyother>',
                                'current' => 1,
                            ],
                        ],
                        'htmlSanitize' => true,
                        'htmlSanitize.' => [
                            'build' => TestSanitizerBuilder::class,
                        ],
                    ],
                ],
                '<div><any data-processed><anyother data-processed>content</anyother></any></div>',
            ],
            'list with empty and filled li' => [
                '<ul>
    <li></li>
    <li>second</li>
</ul>',
                $defaultListItemParseFunc,
                '<ul>
    <li>LI:</li>
    <li>LI:second</li>
</ul>',
            ],
            'list with filled li wrapped by a div containing text' => [
                '<div>text<ul><li></li><li>second</li></ul></div>',
                $defaultListItemParseFunc,
                '<div>text<ul><li>LI:</li><li>LI:second</li></ul></div>',
            ],
            'link list with empty li modification' => [
                '<ul>
    <li>
        <ul>
            <li></li>
        </ul>
    </li>
</ul>',
                $defaultListItemParseFunc,
                '<ul>
    <li>LI:
        <ul>
            <li>LI:</li>
        </ul>
    </li>
</ul>',
            ],

            'link list with li modifications' => [
                '<ul>
    <li>first</li>
    <li>second
        <ul>
            <li>first sub</li>
            <li>second sub</li>
        </ul>
    </li>
</ul>',
                $defaultListItemParseFunc,
                '<ul>
    <li>LI:first</li>
    <li>LI:second
        <ul>
            <li>LI:first sub</li>
            <li>LI:second sub</li>
        </ul>
    </li>
</ul>',
            ],
            'link list with li modifications and no text' => [
                '<ul>
    <li>first</li>
    <li>
        <ul>
            <li>first sub</li>
            <li>second sub</li>
        </ul>
    </li>
</ul>',
                $defaultListItemParseFunc,
                '<ul>
    <li>LI:first</li>
    <li>LI:
        <ul>
            <li>LI:first sub</li>
            <li>LI:second sub</li>
        </ul>
    </li>
</ul>',
            ],
            'link list with li modifications on third level' => [
                '<ul>
    <li>first</li>
    <li>second
        <ul>
            <li>first sub
                <ul>
                    <li>first sub sub</li>
                    <li>second sub sub</li>
                </ul>
            </li>
            <li>second sub</li>
        </ul>
    </li>
</ul>',
                $defaultListItemParseFunc,
                '<ul>
    <li>LI:first</li>
    <li>LI:second
        <ul>
            <li>LI:first sub
                <ul>
                    <li>LI:first sub sub</li>
                    <li>LI:second sub sub</li>
                </ul>
            </li>
            <li>LI:second sub</li>
        </ul>
    </li>
</ul>',
            ],
            'link list with li modifications on third level no text' => [
                '<ul>
    <li>first</li>
    <li>
        <ul>
            <li>
                <ul>
                    <li>first sub sub</li>
                    <li>first sub sub</li>
                </ul>
            </li>
            <li>second sub</li>
        </ul>
    </li>
</ul>',
                $defaultListItemParseFunc,
                '<ul>
    <li>LI:first</li>
    <li>LI:
        <ul>
            <li>LI:
                <ul>
                    <li>LI:first sub sub</li>
                    <li>LI:first sub sub</li>
                </ul>
            </li>
            <li>LI:second sub</li>
        </ul>
    </li>
</ul>',
            ],
            'link list with ul and li modifications' => [
                '<ul>
    <li>first</li>
    <li>second
        <ul>
            <li>first sub</li>
            <li>second sub</li>
        </ul>
    </li>
</ul>',
                [
                    'parseFunc'  => '',
                    'parseFunc.' => [
                        'tags.' => [
                            'ul'  => 'TEXT',
                            'ul.' => [
                                'wrap'    => '<ul><li>intro</li>|<li>outro</li></ul>',
                                'current' => '1',
                            ],
                            'li'  => 'TEXT',
                            'li.' => [
                                'wrap'    => '<li>LI:|</li>',
                                'current' => '1',
                            ],
                        ],
                    ],
                ],
                '<ul><li>intro</li>
    <li>LI:first</li>
    <li>LI:second
        <ul><li>intro</li>
            <li>LI:first sub</li>
            <li>LI:second sub</li>
        <li>outro</li></ul>
    </li>
<li>outro</li></ul>',
            ],

            'link list with li containing p tag and sub list' => [
                '<ul>
    <li>first</li>
    <li>
        <ul>
            <li>
                <span>
                    <ul>
                        <li>first sub sub</li>
                        <li>first sub sub</li>
                    </ul>
                </span>
            </li>
            <li>second sub</li>
        </ul>
    </li>
</ul>',
                $defaultListItemParseFunc,
                '<ul>
    <li>LI:first</li>
    <li>LI:
        <ul>
            <li>LI:
                <span>
                    <ul>
                        <li>LI:first sub sub</li>
                        <li>LI:first sub sub</li>
                    </ul>
                </span>
            </li>
            <li>LI:second sub</li>
        </ul>
    </li>
</ul>',
            ],
        ];
    }

    #[DataProvider('_parseFuncParsesNestedTagsProperlyDataProvider')]
    #[Test]
    public function parseFuncParsesNestedTagsProperly(string $value, array $configuration, string $expectedResult): void
    {
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $this->subject->setRequest($request);
        self::assertEquals($expectedResult, $this->subject->stdWrap_parseFunc($value, $configuration));
    }

    public static function _parseFuncCanHandleTagsAcrossMultipleLinesDataProvider(): iterable
    {
        $configuration = [
            'parseFunc' => '',
            'parseFunc.' => [
                'allowTags' => 'div,meta',
            ],
        ];
        yield 'classic tag in one line' => [
            'input' => '<div id="very-important-div">Hello, world!</div>',
            'configuration' => $configuration,
            'expected' => '<div id="very-important-div">Hello, world!</div>',
        ];
        yield 'classic tag in multiple lines' => [
            'input' => '<div
        id="very-important-div"
    >Hello, world!</div>',
            'configuration' => $configuration,
            'expected' => '<div id="very-important-div">Hello, world!</div>',
        ];
        yield 'classic tag in multiple lines with other special chars' => [
            'input' => '<div


        id  =  "very-important-div"

        ' . "\t\t\f\f" . ' class="nothing"
    >Hello, world!</div>',
            'configuration' => $configuration,
            'expected' => '<div id="very-important-div" class="nothing">Hello, world!</div>',
        ];

        yield 'self-closing tag in one line' => [
            'input' => '<meta id="author" content="benni" />',
            'configuration' => $configuration,
            'expected' => '<meta id="author" content="benni">',
        ];
        yield 'self-closing tag in multiple lines' => [
            'input' => '<meta
        id="author"
        content="benni"
/>',
            'configuration' => $configuration,
            'expected' => '<meta id="author" content="benni">',
        ];
        yield 'self-closing tag in multiple lines with other special chars' => [
            'input' => '<meta

        ' . "\t\t\f\f" . '
        id  =  "author"
        ' . "\t\t\f\f" . '

        content = "benni"
/>',
            'configuration' => $configuration,
            'expected' => '<meta id="author" content="benni">',
        ];
        yield 'html5-style tag in one line' => [
            'input' => '<meta id="author" content="benni" />',
            'configuration' => $configuration,
            'expected' => '<meta id="author" content="benni">',
        ];
        yield 'html5-style tag in multiple lines' => [
            'input' => '<meta
id="author"
content="benni">',
            'configuration' => $configuration,
            'expected' => '<meta id="author" content="benni">',
        ];
    }

    #[DataProvider('_parseFuncCanHandleTagsAcrossMultipleLinesDataProvider')]
    #[Test]
    public function parseFuncCanHandleTagsAcrossMultipleLines(string $input, array $configuration, string $expected): void
    {
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $this->subject->setRequest($request);
        self::assertEquals($expected, $this->subject->stdWrap_parseFunc($input, $configuration));

    }

    public static function httpMakelinksDataProvider(): array
    {
        return [
            'http link' => [
                'Some text with a link http://example.com',
                [
                ],
                'Some text with a link <a href="http://example.com">example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com'))->withLinkText('example.com'),
            ],
            'http link with path' => [
                'Some text with a link http://example.com/path/to/page',
                [
                ],
                'Some text with a link <a href="http://example.com/path/to/page">example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com/path/to/page'))->withLinkText('example.com'),
            ],
            'http link with query parameter' => [
                'Some text with a link http://example.com?foo=bar',
                [
                ],
                'Some text with a link <a href="http://example.com?foo=bar">example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com?foo=bar'))->withLinkText('example.com'),
            ],
            'http link with question mark' => [
                'Some text with a link http://example.com?',
                [
                ],
                'Some text with a link <a href="http://example.com">example.com</a>?',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com'))->withLinkText('example.com'),
            ],
            'http link with period' => [
                'Some text with a link http://example.com.',
                [
                ],
                'Some text with a link <a href="http://example.com">example.com</a>.',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com'))->withLinkText('example.com'),
            ],
            'http link with fragment' => [
                'Some text with a link http://example.com#',
                [
                ],
                'Some text with a link <a href="http://example.com#">example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com#'))->withLinkText('example.com'),
            ],
            'http link with query parameter and fragment' => [
                'Some text with a link http://example.com?foo=bar#top',
                [
                ],
                'Some text with a link <a href="http://example.com?foo=bar#top">example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com?foo=bar#top'))->withLinkText('example.com'),
            ],
            'http link with query parameter and keep scheme' => [
                'Some text with a link http://example.com/path/to/page?foo=bar',
                [
                    'keep' => 'scheme',
                ],
                'Some text with a link <a href="http://example.com/path/to/page?foo=bar">http://example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com/path/to/page?foo=bar'))->withLinkText('http://example.com'),
            ],
            'http link with query parameter and keep path' => [
                'Some text with a link http://example.com/path/to/page?foo=bar',
                [
                    'keep' => 'path',
                ],
                'Some text with a link <a href="http://example.com/path/to/page?foo=bar">example.com/path/to/page</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com/path/to/page?foo=bar'))->withLinkText('example.com/path/to/page'),
            ],
            'http link with query parameter and keep path with trailing slash' => [
                'Some text with a link http://example.com/path/to/page/?foo=bar',
                [
                    'keep' => 'path',
                ],
                'Some text with a link <a href="http://example.com/path/to/page/?foo=bar">example.com/path/to/page/</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com/path/to/page/?foo=bar'))->withLinkText('example.com/path/to/page/'),
            ],
            'http link with trailing slash and keep path with trailing slash' => [
                'Some text with a link http://example.com/',
                [
                    'keep' => 'path',
                ],
                'Some text with a link <a href="http://example.com/">example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com/'))->withLinkText('example.com'),
            ],
            'http link with query parameter and keep scheme,path' => [
                'Some text with a link http://example.com/path/to/page?foo=bar',
                [
                    'keep' => 'scheme,path',
                ],
                'Some text with a link <a href="http://example.com/path/to/page?foo=bar">http://example.com/path/to/page</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com/path/to/page?foo=bar'))->withLinkText('http://example.com/path/to/page'),
            ],
            'http link with multiple query parameters' => [
                'Some text with a link http://example.com?foo=bar&fuz=baz',
                [
                    'keep' => 'scheme,path,query',
                ],
                'Some text with a link <a href="http://example.com?foo=bar&amp;fuz=baz">http://example.com?foo=bar&fuz=baz</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com?foo=bar&fuz=baz'))->withLinkText('http://example.com?foo=bar&fuz=baz'),
            ],
            'http link with query parameter and keep scheme,path,query' => [
                'Some text with a link http://example.com/path/to/page?foo=bar',
                [
                    'keep' => 'scheme,path,query',
                ],
                'Some text with a link <a href="http://example.com/path/to/page?foo=bar">http://example.com/path/to/page?foo=bar</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com/path/to/page?foo=bar'))->withLinkText('http://example.com/path/to/page?foo=bar'),
            ],
            'https link' => [
                'Some text with a link https://example.com',
                [
                ],
                'Some text with a link <a href="https://example.com">example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'https://example.com'))->withLinkText('example.com'),
            ],
            'http link with www' => [
                'Some text with a link http://www.example.com',
                [
                ],
                'Some text with a link <a href="http://www.example.com">www.example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://www.example.com'))->withLinkText('www.example.com'),
            ],
            'https link with www' => [
                'Some text with a link https://www.example.com',
                [
                ],
                'Some text with a link <a href="https://www.example.com">www.example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'https://www.example.com'))->withLinkText('www.example.com'),
            ],
        ];
    }

    #[DataProvider('httpMakelinksDataProvider')]
    #[Test]
    public function httpMakelinksReturnsLink(string $data, array $configuration, string $expectedResult, LinkResult $linkResult): void
    {
        $linkFactory = $this->getMockBuilder(LinkFactory::class)->disableOriginalConstructor()->getMock();
        $linkFactory->method('create')->willReturn($linkResult);
        GeneralUtility::addInstance(LinkFactory::class, $linkFactory);

        self::assertSame($expectedResult, $this->subject->_call('http_makelinks', $data, $configuration));
    }

    public static function invalidHttpMakelinksDataProvider(): array
    {
        return [
            'only http protocol' => [
                'http://',
                [
                ],
                'http://',
            ],
            'only https protocol' => [
                'https://',
                [
                ],
                'https://',
            ],
            'ftp link' => [
                'ftp://user@password:example.com',
                [
                ],
                'ftp://user@password:example.com',
            ],
        ];
    }

    #[DataProvider('invalidHttpMakelinksDataProvider')]
    #[Test]
    public function httpMakelinksReturnsNoLink(string $data, array $configuration, string $expectedResult): void
    {
        self::assertSame($expectedResult, $this->subject->_call('http_makelinks', $data, $configuration));
    }

    public static function mailtoMakelinksDataProvider(): array
    {
        return [
            'mailto link' => [
                'Some text with an email address mailto:john@example.com',
                [
                ],
                'Some text with an email address <a href="mailto:john@example.com">john@example.com</a>',
                (new LinkResult(LinkService::TYPE_EMAIL, 'mailto:john@example.com'))->withLinkText('john@example.com'),
            ],
            'mailto link with subject parameter' => [
                'Some text with an email address mailto:john@example.com?subject=hi',
                [
                ],
                'Some text with an email address <a href="mailto:john@example.com?subject=hi">john@example.com</a>',
                (new LinkResult(LinkService::TYPE_EMAIL, 'mailto:john@example.com?subject=hi'))->withLinkText('john@example.com'),
            ],
            'mailto link with multiple parameters' => [
                'Some text with an email address mailto:john@example.com?subject=Greetings&body=Hi+John',
                [
                ],
                'Some text with an email address <a href="mailto:john@example.com?subject=Greetings&amp;body=Hi+John">john@example.com</a>',
                (new LinkResult(LinkService::TYPE_EMAIL, 'mailto:john@example.com?subject=Greetings&body=Hi+John'))->withLinkText('john@example.com'),
            ],
            'mailto link with question mark' => [
                'Some text with an email address mailto:john@example.com?',
                [
                ],
                'Some text with an email address <a href="mailto:john@example.com">john@example.com</a>?',
                (new LinkResult(LinkService::TYPE_EMAIL, 'mailto:john@example.com'))->withLinkText('john@example.com'),
            ],
            'mailto link with period' => [
                'Some text with an email address mailto:john@example.com.',
                [
                ],
                'Some text with an email address <a href="mailto:john@example.com">john@example.com</a>.',
                (new LinkResult(LinkService::TYPE_EMAIL, 'mailto:john@example.com'))->withLinkText('john@example.com'),
            ],
            'mailto link with wrap' => [
                'Some text with an email address mailto:john@example.com.',
                [
                    'wrap' => '<span>|</span>',
                ],
                'Some text with an email address <span><a href="mailto:john@example.com">john@example.com</a></span>.',
                (new LinkResult(LinkService::TYPE_EMAIL, 'mailto:john@example.com'))->withLinkText('john@example.com'),
            ],
            'mailto link with ATagBeforeWrap' => [
                'Some text with an email address mailto:john@example.com.',
                [
                    'wrap' => '<span>|</span>',
                    'ATagBeforeWrap' => 1,
                ],
                'Some text with an email address <a href="mailto:john@example.com"><span>john@example.com</span></a>.',
                (new LinkResult(LinkService::TYPE_EMAIL, 'mailto:john@example.com'))->withLinkText('john@example.com'),
            ],
            'mailto link with ATagParams' => [
                'Some text with an email address mailto:john@example.com.',
                [
                    'ATagParams' => 'class="email"',
                ],
                'Some text with an email address <a href="mailto:john@example.com" class="email">john@example.com</a>.',
                (new LinkResult(LinkService::TYPE_EMAIL, 'mailto:john@example.com'))->withAttribute('class', 'email')->withLinkText('john@example.com'),
            ],
        ];
    }

    #[DataProvider('mailtoMakelinksDataProvider')]
    #[Test]
    public function mailtoMakelinksReturnsMailToLink(string $data, array $configuration, string $expectedResult, LinkResult $linkResult): void
    {
        $linkFactory = $this->getMockBuilder(LinkFactory::class)->disableOriginalConstructor()->getMock();
        $linkFactory->method('create')->willReturn($linkResult);
        GeneralUtility::addInstance(LinkFactory::class, $linkFactory);

        self::assertSame($expectedResult, $this->subject->_call('mailto_makelinks', $data, $configuration));
    }

    #[Test]
    public function mailtoMakelinksReturnsNoMailToLink(): void
    {
        self::assertSame('mailto:', $this->subject->_call('mailto_makelinks', 'mailto:', []));
    }

    #[Test]
    public function stdWrap_splitObjReturnsCount(): void
    {
        $conf = [
            'token' => ',',
            'returnCount' => 1,
        ];
        $expectedResult = 5;
        $amountOfEntries = $this->subject->splitObj('1, 2, 3, 4, 5', $conf);
        self::assertSame(
            $expectedResult,
            $amountOfEntries
        );
    }

    /**
     * Check if calculateCacheKey works properly.
     *
     * @return array Order: expect, conf, times, with, withWrap, will
     */
    public static function calculateCacheKeyDataProvider(): array
    {
        $value = StringUtility::getUniqueId('value');
        $wrap = [StringUtility::getUniqueId('wrap')];
        $valueConf = ['key' => $value];
        $wrapConf = ['key.' => $wrap];
        $conf = array_merge($valueConf, $wrapConf);
        $will = StringUtility::getUniqueId('stdWrap');

        return [
            'no conf' => [
                '',
                [],
                0,
                null,
                null,
                null,
            ],
            'value conf only' => [
                $value,
                $valueConf,
                0,
                null,
                null,
                null,
            ],
            'wrap conf only' => [
                $will,
                $wrapConf,
                1,
                '',
                $wrap,
                $will,
            ],
            'full conf' => [
                $will,
                $conf,
                1,
                $value,
                $wrap,
                $will,
            ],
        ];
    }

    /**
     * Check if calculateCacheKey works properly.
     *
     * - takes key from $conf['key']
     * - processes key with stdWrap based on $conf['key.']
     *
     * @param string $expect Expected result.
     * @param array $conf Properties 'key', 'key.'
     * @param int $times Times called mocked method.
     * @param string|null $with Parameter passed to mocked method.
     * @param array|null $withWrap
     * @param string|null $will Return value of mocked method.
     */
    #[DataProvider('calculateCacheKeyDataProvider')]
    #[Test]
    public function calculateCacheKey(string $expect, array $conf, int $times, ?string $with, ?array $withWrap, ?string $will): void
    {
        $subject = $this->getAccessibleMock(ContentObjectRenderer::class, ['stdWrap']);
        $subject->expects(self::exactly($times))
            ->method('stdWrap')
            ->with($with, $withWrap)
            ->willReturn($will);

        $result = $subject->_call('calculateCacheKey', $conf);
        self::assertSame($expect, $result);
    }

    /**
     * Data provider for getFromCache
     *
     * @return array Order: expect, conf, cacheKey, times, cached.
     */
    public static function getFromCacheDataProvider(): array
    {
        $conf = [StringUtility::getUniqueId('conf')];
        return [
            'empty cache key' => [
                false,
                $conf,
                '',
                0,
                null,
            ],
            'non-empty cache key' => [
                'value',
                $conf,
                'non-empty-key',
                1,
                'value',
            ],
        ];
    }

    /**
     * Check if getFromCache works properly.
     *
     * - CalculateCacheKey is called to calc the cache key.
     * - $conf is passed on as parameter
     * - CacheFrontend is created and called if $cacheKey is not empty.
     * - Else false is returned.
     *
     * @param string|bool $expect Expected result.
     * @param array $conf Configuration to pass to calculateCacheKey mock.
     * @param string $cacheKey Return from calculateCacheKey mock.
     * @param int $times Times the cache is expected to be called (0 or 1).
     * @param string|null $cached Return from cacheFrontend mock.
     */
    #[DataProvider('getFromCacheDataProvider')]
    #[Test]
    public function getFromCache(string|bool $expect, array $conf, string $cacheKey, int $times, ?string $cached): void
    {
        $tags = [StringUtility::getUniqueId('tags')];

        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            [
                'calculateCacheKey',
                'getRequest',
                'getTypoScriptFrontendController',
            ]
        );
        $subject
            ->expects(self::once())
            ->method('calculateCacheKey')
            ->with($conf)
            ->willReturn($cacheKey);
        $cacheDataCollector = $this->createMock(CacheDataCollectorInterface::class);
        $cacheDataCollector
            ->expects(self::exactly($times))
            ->method('addCacheTags')
            ->with(self::isInstanceOf(CacheTag::class));
        $request = (new ServerRequest())
            ->withAttribute('frontend.cache.collector', $cacheDataCollector)
            ->withAttribute('frontend.cache.instruction', new CacheInstruction());
        $subject
            ->expects(self::atLeastOnce())
            ->method('getRequest')
            ->willReturn($request);
        $cacheFrontend = $this->createMock(CacheFrontendInterface::class);
        $cacheFrontend
            ->expects(self::exactly($times))
            ->method('get')
            ->with($cacheKey)
            ->willReturn(['content' => $cached, 'cacheTags' => $tags]);
        $cacheManager = $this->createMock(CacheManager::class);
        $cacheManager
            ->method('getCache')
            ->willReturn($cacheFrontend);
        GeneralUtility::setSingletonInstance(
            CacheManager::class,
            $cacheManager
        );
        self::assertSame($expect, $subject->_call('getFromCache', $conf));
    }

    /**
     * Data provider for getFieldVal
     *
     * @return array [$expect, $fields]
     */
    public static function getFieldValDataProvider(): array
    {
        return [
            'invalid single key' => [null, 'invalid'],
            'single key of null' => [null, 'null'],
            'single key of empty string' => ['', 'empty'],
            'single key of non-empty string' => ['string 1', 'string1'],
            'single key of boolean false' => [false, 'false'],
            'single key of boolean true' => [true, 'true'],
            'single key of integer 0' => [0, 'zero'],
            'single key of integer 1' => [1, 'one'],
            'single key to be trimmed' => ['string 1', ' string1 '],

            'split nothing' => ['', '//'],
            'split one before' => ['string 1', 'string1//'],
            'split one after' => ['string 1', '//string1'],
            'split two ' => ['string 1', 'string1//string2'],
            'split three ' => ['string 1', 'string1//string2//string3'],
            'split to be trimmed' => ['string 1', ' string1 // string2 '],
            '0 is not empty' => [0, '// zero'],
            '1 is not empty' => [1, '// one'],
            'true is not empty' => [true, '// true'],
            'false is empty' => ['', '// false'],
            'null is empty' => ['', '// null'],
            'empty string is empty' => ['', '// empty'],
            'string is not empty' => ['string 1', '// string1'],
            'first non-empty winns' => [0, 'false//empty//null//zero//one'],
            'empty string is fallback' => ['', 'false // empty // null'],
        ];
    }

    /**
     * Check that getFieldVal works properly.
     *
     * Show:
     *
     * - Returns the field from $this->data.
     * - The keys are trimmed.
     *
     * - For a single key (no //) returns the field as is:
     *
     *   - '' => ''
     *   - null => null
     *   - false => false
     *   - true => true
     *   -  0 => 0
     *   -  1 => 1
     *   - 'string' => 'string'
     *
     * - If '//' is present, explodes key candidates.
     * - Returns the first field, that is not "empty".
     * - "Empty" is checked after type cast to string by comparing to ''.
     * - The winning non-empty value is returned as is.
     * - The fallback, if all evals to empty, is the empty string ''.
     * - '//' with single elements and empty string fallback results in:
     *
     *   - '' => ''
     *   - null => ''
     *   - false => ''
     *   - true => true
     *   -  0 => 0
     *   -  1 => 1
     *   - 'string' => 'string'
     *
     * @param mixed $expect The expected string.
     * @param string $fields Field names divides by //.
     */
    #[DataProvider('getFieldValDataProvider')]
    #[Test]
    public function getFieldVal(mixed $expect, string $fields): void
    {
        $data = [
            'string1' => 'string 1',
            'string2' => 'string 2',
            'string3' => 'string 3',
            'empty' => '',
            'null' => null,
            'false' => false,
            'true' => true,
            'zero' => 0,
            'one' => 1,
        ];
        $this->subject->_set('data', $data);
        self::assertSame($expect, $this->subject->getFieldVal($fields));
    }

    public static function caseshiftDataProvider(): array
    {
        return [
            'lower' => [
                'x y', // expected
                'X Y', // content
                'lower', // case
            ],
            'upper' => [
                'X Y',
                'x y',
                'upper',
            ],
            'capitalize' => [
                'One Two',
                'one two',
                'capitalize',
            ],
            'ucfirst' => [
                'One two',
                'one two',
                'ucfirst',
            ],
            'lcfirst' => [
                'oNE TWO',
                'ONE TWO',
                'lcfirst',
            ],
            'uppercamelcase' => [
                'CamelCase',
                'camel_case',
                'uppercamelcase',
            ],
            'lowercamelcase' => [
                'camelCase',
                'camel_case',
                'lowercamelcase',
            ],
        ];
    }

    #[DataProvider('caseshiftDataProvider')]
    #[Test]
    public function caseshift(string $expected, string $content, string $case): void
    {
        self::assertSame($expected, $this->subject->caseshift($content, $case));
    }

    public static function HTMLcaseshiftDataProvider(): array
    {
        return [
            'simple text' => [
                'text',
                'TEXT',
            ],
            'simple tag' => [
                '<i>text</i>',
                '<i>TEXT</i>',
            ],
            'multiple nested tags with classes' => [
                '<div class="typo3">'
                . '<p>A <b>bold<\b> word.</p>'
                . '<p>An <i>italic<\i> word.</p>'
                . '</div>',
                '<div class="typo3">'
                . '<p>A <b>BOLD<\b> WORD.</p>'
                . '<p>AN <i>ITALIC<\i> WORD.</p>'
                . '</div>',
            ],
        ];
    }

    #[DataProvider('HTMLcaseshiftDataProvider')]
    #[Test]
    public function HTMLcaseshift(string $content, string $expected): void
    {
        self::assertSame($expected, (new ContentObjectRenderer())->HTMLcaseshift($content, 'upper'));
    }

    /**
     * Data provider for stdWrap_HTMLparser
     *
     * @return array [$expect, $content, $conf, $times, $will].
     */
    public static function stdWrap_HTMLparserDataProvider(): array
    {
        $content = StringUtility::getUniqueId('content');
        $parsed = StringUtility::getUniqueId('parsed');
        return [
            'no config' => [
                $content,
                $content,
                [],
                0,
                $parsed,
            ],
            'no array' => [
                $content,
                $content,
                ['HTMLparser.' => 1],
                0,
                $parsed,
            ],
            'empty array' => [
                $parsed,
                $content,
                ['HTMLparser.' => []],
                1,
                $parsed,
            ],
            'non-empty array' => [
                $parsed,
                $content,
                ['HTMLparser.' => [true]],
                1,
                $parsed,
            ],
        ];
    }

    /**
     * Check if stdWrap_HTMLparser works properly
     *
     * Show:
     *
     * - Checks if $conf['HTMLparser.'] is an array.
     * - No:
     *   - Returns $content as is.
     * - Yes:
     *   - Delegates to method HTMLparser_TSbridge.
     *   - Parameter 1 is $content.
     *   - Parameter 2 is $conf['HTMLparser'].
     *   - Returns the return value.
     *
     * @param string $expect The expected output.
     * @param string $content The given content.
     * @param array $conf The given configuration.
     * @param int $times Times HTMLparser_TSbridge is called (0 or 1).
     * @param string $will Return of HTMLparser_TSbridge.
     */
    #[DataProvider('stdWrap_HTMLparserDataProvider')]
    #[Test]
    public function stdWrap_HTMLparser(
        string $expect,
        string $content,
        array $conf,
        int $times,
        string $will
    ): void {
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['HTMLparser_TSbridge'])->getMock();
        $subject
            ->expects(self::exactly($times))
            ->method('HTMLparser_TSbridge')
            ->with($content, $conf['HTMLparser.'] ?? [])
            ->willReturn($will);
        self::assertSame(
            $expect,
            $subject->stdWrap_HTMLparser($content, $conf)
        );
    }

    public static function stdWrap_addPageCacheTagsAddsPageTagsDataProvider(): array
    {
        return [
            'No Tag' => [
                [],
                ['addPageCacheTags' => ''],
            ],
            'Two expectedTags' => [
                [new CacheTag('tag1'), new CacheTag('tag2')],
                ['addPageCacheTags' => 'tag1,tag2'],
            ],
            'Two expectedTags plus one with stdWrap' => [
                [new CacheTag('tag1'), new CacheTag('tag2'), new CacheTag('tag3')],
                [
                    'addPageCacheTags' => 'tag1,tag2',
                    'addPageCacheTags.' => ['wrap' => '|,tag3'],
                ],
            ],
        ];
    }

    #[DataProvider('stdWrap_addPageCacheTagsAddsPageTagsDataProvider')]
    #[Test]
    public function stdWrap_addPageCacheTagsAddsPageTags(array $expectedTags, array $configuration): void
    {
        $cacheDataCollector = new CacheDataCollector();
        $request = new ServerRequest();
        $request = $request->withAttribute('frontend.cache.collector', $cacheDataCollector);
        $this->subject->setRequest($request);
        $this->subject->stdWrap_addPageCacheTags('', $configuration);

        self::assertEquals($expectedTags, $cacheDataCollector->getCacheTags());
    }

    /**
     * Check if stdWrap_age works properly.
     *
     * Show:
     *
     * - Delegates to calcAge.
     * - Parameter 1 is the difference between $content and EXEC_TIME.
     * - Parameter 2 is $conf['age'].
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_age(): void
    {
        $now = 10;
        $content = '9';
        $conf = ['age' => StringUtility::getUniqueId('age')];
        $return = StringUtility::getUniqueId('return');
        $difference = $now - (int)$content;
        $GLOBALS['EXEC_TIME'] = $now;
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['calcAge'])->getMock();
        $subject
            ->expects(self::once())
            ->method('calcAge')
            ->with($difference, $conf['age'])
            ->willReturn($return);
        self::assertSame($return, $subject->stdWrap_age($content, $conf));
    }

    /**
     * Check if stdWrap_append works properly.
     *
     * Show:
     *
     * - Delegates to the method cObjGetSingle().
     * - First parameter is $conf['append'].
     * - Second parameter is $conf['append.'].
     * - Third parameter is '/stdWrap/.append'.
     * - Returns the return value appended to $content.
     */
    #[Test]
    public function stdWrap_append(): void
    {
        $debugKey = '/stdWrap/.append';
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'append' => StringUtility::getUniqueId('append'),
            'append.' => [StringUtility::getUniqueId('append.')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['cObjGetSingle'])->getMock();
        $subject
            ->expects(self::once())
            ->method('cObjGetSingle')
            ->with($conf['append'], $conf['append.'], $debugKey)
            ->willReturn($return);
        self::assertSame(
            $content . $return,
            $subject->stdWrap_append($content, $conf)
        );
    }

    /**
     * Data provider for stdWrap_br
     *
     * @return string[][] Order expected, given, config.doctype
     */
    public static function stdWrapBrDataProvider(): array
    {
        return [
            'no xhtml with LF in between' => [
                'one<br>' . LF . 'two',
                'one' . LF . 'two',
                null,
            ],
            'no xhtml with LF in between and around' => [
                '<br>' . LF . 'one<br>' . LF . 'two<br>' . LF,
                LF . 'one' . LF . 'two' . LF,
                null,
            ],
            'xhtml with LF in between' => [
                'one<br />' . LF . 'two',
                'one' . LF . 'two',
                'xhtml_strict',
            ],
            'xhtml with LF in between and around' => [
                '<br />' . LF . 'one<br />' . LF . 'two<br />' . LF,
                LF . 'one' . LF . 'two' . LF,
                'xhtml_strict',
            ],
        ];
    }

    /**
     * Test that stdWrap_br works as expected.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param string|null $doctype document type.
     */
    #[DataProvider('stdWrapBrDataProvider')]
    #[Test]
    public function stdWrap_br(string $expected, string $input, ?string $doctype): void
    {
        $pageRenderer = $this->getMockBuilder(PageRenderer::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $pageRenderer->setLanguage(new Locale());
        $pageRenderer->setDocType(DocType::createFromConfigurationKey($doctype));
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRenderer);
        self::assertSame($expected, $this->subject->stdWrap_br($input));
    }

    /**
     * Data provider for stdWrap_brTag
     */
    public static function stdWrapBrTagDataProvider(): array
    {
        $noConfig = [];
        $config1 = ['brTag' => '<br/>'];
        $config2 = ['brTag' => '<br>'];
        return [
            'no config: one break at the beginning' => [LF . 'one' . LF . 'two', 'onetwo', $noConfig],
            'no config: multiple breaks at the beginning' => [LF . LF . 'one' . LF . 'two', 'onetwo', $noConfig],
            'no config: one break at the end' => ['one' . LF . 'two' . LF, 'onetwo', $noConfig],
            'no config: multiple breaks at the end' => ['one' . LF . 'two' . LF . LF, 'onetwo', $noConfig],

            'config1: one break at the beginning' => [LF . 'one' . LF . 'two', '<br/>one<br/>two', $config1],
            'config1: multiple breaks at the beginning' => [
                LF . LF . 'one' . LF . 'two',
                '<br/><br/>one<br/>two',
                $config1,
            ],
            'config1: one break at the end' => ['one' . LF . 'two' . LF, 'one<br/>two<br/>', $config1],
            'config1: multiple breaks at the end' => ['one' . LF . 'two' . LF . LF, 'one<br/>two<br/><br/>', $config1],

            'config2: one break at the beginning' => [LF . 'one' . LF . 'two', '<br>one<br>two', $config2],
            'config2: multiple breaks at the beginning' => [
                LF . LF . 'one' . LF . 'two',
                '<br><br>one<br>two',
                $config2,
            ],
            'config2: one break at the end' => ['one' . LF . 'two' . LF, 'one<br>two<br>', $config2],
            'config2: multiple breaks at the end' => ['one' . LF . 'two' . LF . LF, 'one<br>two<br><br>', $config2],
        ];
    }

    /**
     * Check if brTag works properly
     */
    #[DataProvider('stdWrapBrTagDataProvider')]
    #[Test]
    public function stdWrap_brTag(string $input, string $expected, array $config): void
    {
        self::assertEquals($expected, $this->subject->stdWrap_brTag($input, $config));
    }

    /**
     * Check if stdWrap_cObject works properly.
     *
     * Show:
     *
     * - Delegates to the method cObjGetSingle().
     * - Parameter 1 is $conf['cObject'].
     * - Parameter 2 is $conf['cObject.'].
     * - Parameter 3 is '/stdWrap/.cObject'.
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_cObject(): void
    {
        $debugKey = '/stdWrap/.cObject';
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'cObject' => StringUtility::getUniqueId('cObject'),
            'cObject.' => [StringUtility::getUniqueId('cObject.')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['cObjGetSingle'])->getMock();
        $subject
            ->expects(self::once())
            ->method('cObjGetSingle')
            ->with($conf['cObject'], $conf['cObject.'], $debugKey)
            ->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_cObject($content, $conf)
        );
    }

    public static function stdWrap_orderedStdWrapDataProvider(): array
    {
        return [
            'standard case: given order 1, 2' => [
                [
                    'orderedStdWrap.' => [
                        '1.' => [
                            'wrap' => '<inner>|</inner>',
                        ],
                        '2.' => [
                            'wrap' => '<outer>|</outer>',
                        ],
                    ],
                ],
                '<outer><inner>someContent</inner></outer>',
            ],
            'inverted: given order 2, 1' => [
                [
                    'orderedStdWrap.' => [
                        '2.' => [
                            'wrap' => '<outer>|</outer>',
                        ],
                        '1.' => [
                            'wrap' => '<inner>|</inner>',
                        ],
                    ],
                ],
                '<outer><inner>someContent</inner></outer>',
            ],
            '0 as integer: given order 0, 2' => [
                [
                    'orderedStdWrap.' => [
                        '0.' => [
                            'wrap' => '<inner>|</inner>',
                        ],
                        '2.' => [
                            'wrap' => '<outer>|</outer>',
                        ],
                    ],
                ],
                '<outer><inner>someContent</inner></outer>',
            ],
            'negative integers: given order 2, -2' => [
                [
                    'orderedStdWrap.' => [
                        '2.' => [
                            'wrap' => '<outer>|</outer>',
                        ],
                        '-2.' => [
                            'wrap' => '<inner>|</inner>',
                        ],
                    ],
                ],
                '<outer><inner>someContent</inner></outer>',
            ],
            'chars are casted to key 0, that is not in the array' => [
                [
                    'orderedStdWrap.' => [
                        '2.' => [
                            'wrap' => '<inner>|</inner>',
                        ],
                        'xxx.' => [
                            'wrap' => '<invalid>|</invalid>',
                        ],
                    ],
                ],
                '<inner>someContent</inner>',
            ],
        ];
    }

    #[DataProvider('stdWrap_orderedStdWrapDataProvider')]
    #[Test]
    public function stdWrap_orderedStdWrap(array $config, string $expected): void
    {
        self::assertSame($expected, (new ContentObjectRenderer())->stdWrap_orderedStdWrap('someContent', $config));
    }

    /**
     * Data provider for stdWrap_cacheRead
     *
     * @return array Order: expect, input, conf, times, with, will
     */
    public static function stdWrap_cacheReadDataProvider(): array
    {
        $cacheConf = [StringUtility::getUniqueId('cache.')];
        $conf = ['cache.' => $cacheConf];
        return [
            'no conf' => [
                'content',
                'content',
                [],
                0,
                null,
                null,
            ],
            'no cache. conf' => [
                'content',
                'content',
                ['otherConf' => 1],
                0,
                null,
                null,
            ],
            'non-cached simulation' => [
                'content',
                'content',
                $conf,
                1,
                $cacheConf,
                false,
            ],
            'cached simulation' => [
                'cachedContent',
                'content',
                $conf,
                1,
                $cacheConf,
                'cachedContent',
            ],
        ];
    }

    /**
     * Check if stdWrap_cacheRead works properly.
     *
     * - the method branches correctly
     * - getFromCache is called to fetch from cache
     * - $conf['cache.'] is passed on as parameter
     *
     * @param string $expect Expected result.
     * @param string $input Given input string.
     * @param array $conf Property 'cache.'
     * @param int $times Times called mocked method.
     * @param array|null $with Parameter passed to mocked method.
     * @param string|false $will Return value of mocked method.
     */
    #[DataProvider('stdWrap_cacheReadDataProvider')]
    #[Test]
    public function stdWrap_cacheRead(
        string $expect,
        string $input,
        array $conf,
        int $times,
        ?array $with,
        string|bool|null $will
    ): void {
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            ['getFromCache']
        );
        $subject
            ->expects(self::exactly($times))
            ->method('getFromCache')
            ->with($with)
            ->willReturn($will);
        self::assertSame(
            $expect,
            $subject->stdWrap_cacheRead($input, $conf)
        );
    }

    /**
     * Data provider for stdWrap_cacheStore.
     *
     * @return array [$confCache, $timesCCK, $key, $times]
     */
    public static function stdWrap_cacheStoreDataProvider(): array
    {
        return [
            'Return immediate with no conf' => [
                null,
                0,
                null,
            ],
            'Return immediate with empty key' => [
                [StringUtility::getUniqueId('cache.')],
                1,
                '0',
                0,
            ],
        ];
    }

    /**
     * Check if stdWrap_cacheStore works properly.
     *
     * Show:
     *
     * - Returns $content as is.
     * - Returns immediate if $conf['cache.'] is not set.
     * - Returns immediate if calculateCacheKey returns an empty value.
     *
     * @param array|null $confCache Configuration of 'cache.'
     * @param int $times Times calculateCacheKey is called.
     * @param mixed $key The return value of calculateCacheKey.
     */
    #[DataProvider('stdWrap_cacheStoreDataProvider')]
    #[Test]
    public function stdWrap_cacheStore(
        ?array $confCache,
        int $times,
        mixed $key,
    ): void {
        $content = StringUtility::getUniqueId('content');
        $conf = [];
        $conf['cache.'] = $confCache;
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            [
                'calculateCacheKey',
                'calculateCacheTags',
                'calculateCacheLifetime',
                'getTypoScriptFrontendController',
            ]
        );
        $subject->expects(self::exactly($times))->method('calculateCacheKey')->with($confCache)->willReturn($key);
        self::assertSame(
            $content,
            $subject->stdWrap_cacheStore($content, $conf)
        );
    }

    /**
     * Make sure the PSR-14 Event in get stdWrap_cacheStore is called
     *
     * Additionally, following is ensured:
     *
     *  - Calls calculateCacheKey with $conf['cache.'].
     *  - Calls calculateCacheTags with $conf['cache.'].
     *  - Calls calculateCacheLifetime with $conf['cache.'].
     *  - Calls all configured user functions with $params, $this.
     *  - Calls set on the cache frontend with $key, $content, $tags, $lifetime.
     */
    #[Test]
    public function beforeStdWrapContentStoredInCacheEventIsCalled(): void
    {
        $beforeStdWrapContentStoredInCacheEvent = null;
        $modifiedContent = '---modified-content---';

        /** @var Container $container */
        $container = GeneralUtility::getContainer();
        $container->set(
            'before-stdWrap-content-stored-in-cache-listener',
            static function (BeforeStdWrapContentStoredInCacheEvent $event) use (&$beforeStdWrapContentStoredInCacheEvent, $modifiedContent) {
                $beforeStdWrapContentStoredInCacheEvent = $event;
                $event->setContent($modifiedContent);
            }
        );

        $listenerProvider = new ListenerProvider($container);
        $listenerProvider->addListener(BeforeStdWrapContentStoredInCacheEvent::class, 'before-stdWrap-content-stored-in-cache-listener');
        $container->set(ListenerProvider::class, $listenerProvider);
        $container->set(EventDispatcherInterface::class, new EventDispatcher($listenerProvider));

        $content = StringUtility::getUniqueId('content');
        $tags = [StringUtility::getUniqueId('tags')];
        $key = StringUtility::getUniqueId('key');
        $lifetime = 100;
        $cacheConfig = [
            StringUtility::getUniqueId('cache.'),
        ];
        $configuration = [
            'cache.' => $cacheConfig,
        ];

        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            [
                'calculateCacheKey',
                'calculateCacheTags',
                'calculateCacheLifetime',
                'getRequest',
            ]
        );
        $subject->expects(self::once())->method('calculateCacheKey')->with($cacheConfig)->willReturn($key);
        $subject->expects(self::once())->method('calculateCacheTags')->with($cacheConfig)->willReturn($tags);
        $subject->expects(self::once())->method('calculateCacheLifetime')->with($cacheConfig)->willReturn($lifetime);
        $cacheDataCollector = new CacheDataCollector();
        $request = (new ServerRequest())->withAttribute('frontend.cache.collector', $cacheDataCollector);
        $subject->expects(self::once())->method('getRequest')->willReturn($request);
        $cacheFrontend = $this->createMock(CacheFrontendInterface::class);
        $cacheFrontend
            ->expects(self::once())
            ->method('set')
            ->with($key, ['content' => $modifiedContent, 'cacheTags' => $tags], $tags, $lifetime)
            ->willReturn(null);
        $cacheManager = $this->createMock(CacheManager::class);
        $cacheManager
            ->method('getCache')
            ->willReturn($cacheFrontend);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager);

        $result = $subject->stdWrap_cacheStore($content, $configuration);

        self::assertSame($modifiedContent, $result);
        self::assertInstanceOf(BeforeStdWrapContentStoredInCacheEvent::class, $beforeStdWrapContentStoredInCacheEvent);
        self::assertSame($modifiedContent, $beforeStdWrapContentStoredInCacheEvent->getContent());
        self::assertSame($tags, $beforeStdWrapContentStoredInCacheEvent->getTags());
        self::assertSame($key, $beforeStdWrapContentStoredInCacheEvent->getKey());
        self::assertSame($lifetime, $beforeStdWrapContentStoredInCacheEvent->getLifetime());
        self::assertSame($configuration, $beforeStdWrapContentStoredInCacheEvent->getConfiguration());
        self::assertSame($subject, $beforeStdWrapContentStoredInCacheEvent->getContentObjectRenderer());
        self::assertCount(count($tags), $cacheDataCollector->getCacheTags());
    }

    /**
     * Check if stdWrap_case works properly.
     *
     * Show:
     *
     * - Delegates to method HTMLcaseshift.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['case'].
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_case(): void
    {
        $content = StringUtility::getUniqueId();
        $conf = [
            'case' => StringUtility::getUniqueId('used'),
            'case.' => [StringUtility::getUniqueId('discarded')],
        ];
        $return = StringUtility::getUniqueId();
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['HTMLcaseshift'])->getMock();
        $subject
            ->expects(self::once())
            ->method('HTMLcaseshift')
            ->with($content, $conf['case'])
            ->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_case($content, $conf)
        );
    }

    /**
     * Check if stdWrap_char works properly.
     */
    #[Test]
    public function stdWrap_char(): void
    {
        $input = 'discarded';
        $expected = 'C';
        self::assertEquals($expected, $this->subject->stdWrap_char($input, ['char' => '67']));
    }

    /**
     * Check if stdWrap_crop works properly.
     *
     * Show:
     *
     * - Delegates to method listNum.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['crop'].
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_crop(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'crop' => StringUtility::getUniqueId('crop'),
            'crop.' => StringUtility::getUniqueId('not used'),
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['crop'])->getMock();
        $subject
            ->expects(self::once())
            ->method('crop')
            ->with($content, $conf['crop'])
            ->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_crop($content, $conf)
        );
    }

    /**
     * Check if stdWrap_cropHTML works properly.
     *
     * Show:
     *
     * - Delegates to method cropHTML.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['cropHTML'].
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_cropHTML(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'cropHTML' => StringUtility::getUniqueId('cropHTML'),
            'cropHTML.' => StringUtility::getUniqueId('not used'),
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['cropHTML'])->getMock();
        $subject
            ->expects(self::once())
            ->method('cropHTML')
            ->with($content, $conf['cropHTML'])
            ->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_cropHTML($content, $conf)
        );
    }

    public static function stdWrap_formattedDateProvider(): \Generator
    {
        yield 'regular formatting - no locale' => [
            '2023.02.02 AD at 13:05:00 UTC',
            "yyyy.MM.dd G 'at' HH:mm:ss zzz",
        ];
        yield 'full - no locale' => [
            'Thursday, February 2, 2023 at 13:05:00 Coordinated Universal Time',
            'FULL',
        ];
        yield 'long - no locale' => [
            'February 2, 2023 at 13:05:00 UTC',
            'LONG',
        ];
        yield 'medium - no locale' => [
            'Feb 2, 2023, 13:05:00',
            'MEDIUM',
        ];
        yield 'medium with int - no locale' => [
            'Feb 2, 2023, 13:05:00',
            \IntlDateFormatter::MEDIUM,
        ];
        yield 'short - no locale' => [
            '2/2/23, 13:05',
            'SHORT',
        ];
        yield 'regular formatting - german locale' => [
            '2023.02.02 n. Chr. um 13:05:00 UTC',
            "yyyy.MM.dd G 'um' HH:mm:ss zzz",
            'de-DE',
        ];
        yield 'full - german locale' => [
            'Donnerstag, 2. Februar 2023 um 13:05:00 Koordinierte Weltzeit',
            'FULL',
            'de-DE',
        ];
        yield 'long - german locale' => [
            '2. Februar 2023 um 13:05:00 UTC',
            'LONG',
            'de-DE',
        ];
        yield 'medium - german locale' => [
            '02.02.2023, 13:05:00',
            'MEDIUM',
            'de-DE',
        ];
        yield 'short - german locale' => [
            '02.02.23, 13:05',
            'SHORT',
            'de-DE',
        ];
        yield 'custom date only - german locale' => [
            '02. Februar 2023',
            'dd. MMMM yyyy',
            'de-DE',
        ];
        yield 'custom time only - german locale' => [
            '13:05:00',
            'HH:mm:ss',
            'de-DE',
        ];
        yield 'given date and time - german locale' => [
            'Freitag, 20. Februar 1998 um 03:00:00 Koordinierte Weltzeit',
            'FULL',
            'de-DE',
            '1998-02-20 3:00:00',
        ];
    }

    #[DataProvider('stdWrap_formattedDateProvider')]
    #[Test]
    public function stdWrap_formattedDate(string $expected, mixed $pattern, ?string $locale = null, ?string $givenDate = null): void
    {
        $context = new Context();
        $context->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('2023-02-02 13:05:00')));
        GeneralUtility::setSingletonInstance(Context::class, $context);
        $subject = new ContentObjectRenderer();
        $site = $this->createSiteWithLanguage([
            'base' => '/',
            'languageId' => 2,
            'locale' => 'en_UK',
        ]);
        $request = (new ServerRequest())->withAttribute('language', $site->getLanguageById(2));
        $subject->setRequest($request);
        $conf = ['formattedDate' => $pattern];
        if ($locale !== null) {
            $conf['formattedDate.']['locale'] = $locale;
        }
        self::assertEquals($expected, $subject->stdWrap_formattedDate((string)$givenDate, $conf));
    }

    /**
     * Data provider for stdWrap_csConv
     *
     * @return array Order expected, input, conf
     */
    public static function stdWrap_csConvDataProvider(): array
    {
        return [
            'empty string from ISO-8859-15' => [
                '',
                mb_convert_encoding('', 'ISO-8859-15', 'UTF-8'),
                ['csConv' => 'ISO-8859-15'],
            ],
            'empty string from BIG-5' => [
                '',
                mb_convert_encoding('', 'BIG-5'),
                ['csConv' => 'BIG-5'],
            ],
            '"0" from ISO-8859-15' => [
                '0',
                mb_convert_encoding('0', 'ISO-8859-15', 'UTF-8'),
                ['csConv' => 'ISO-8859-15'],
            ],
            '"0" from BIG-5' => [
                '0',
                mb_convert_encoding('0', 'BIG-5'),
                ['csConv' => 'BIG-5'],
            ],
            'euro symbol from ISO-88859-15' => [
                '€',
                mb_convert_encoding('€', 'ISO-8859-15', 'UTF-8'),
                ['csConv' => 'ISO-8859-15'],
            ],
            'good morning from BIG-5' => [
                '早安',
                mb_convert_encoding('早安', 'BIG-5'),
                ['csConv' => 'BIG-5'],
            ],
        ];
    }

    /**
     * Check if stdWrap_csConv works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Property: csConv
     */
    #[DataProvider('stdWrap_csConvDataProvider')]
    #[Test]
    public function stdWrap_csConv(string $expected, string $input, array $conf): void
    {
        self::assertSame(
            $expected,
            $this->subject->stdWrap_csConv($input, $conf)
        );
    }

    /**
     * Check if stdWrap_current works properly.
     *
     * Show:
     *
     * - current is returned from $this->data
     * - the key is stored in $this->currentValKey
     * - the key defaults to 'currentValue_kidjls9dksoje'
     */
    #[Test]
    public function stdWrap_current(): void
    {
        $data = [
            'currentValue_kidjls9dksoje' => 'default',
            'currentValue_new' => 'new',
        ];
        $this->subject->_set('data', $data);
        self::assertSame(
            'currentValue_kidjls9dksoje',
            $this->subject->_get('currentValKey')
        );
        self::assertSame(
            'default',
            $this->subject->stdWrap_current('discarded', ['discarded'])
        );
        $this->subject->_set('currentValKey', 'currentValue_new');
        self::assertSame(
            'new',
            $this->subject->stdWrap_current('discarded', ['discarded'])
        );
    }

    /**
     * Data provider for stdWrap_data.
     *
     * @return array [$expect, $data]
     */
    public static function stdWrap_dataDataProvider(): array
    {
        $data = [StringUtility::getUniqueId('data')];
        return [
            'default' => [$data, $data, ''],
        ];
    }

    /**
     * Checks that stdWrap_data works properly.
     *
     * Show:
     * - Delegates to method getData.
     * - Parameter 1 is $conf['data'].
     * - Parameter 2 is property data by default.
     * - Returns the return value.
     *
     * @param array $expect Expect either $data
     * @param array $data The data.
     */
    #[DataProvider('stdWrap_dataDataProvider')]
    #[Test]
    public function stdWrap_data(array $expect, array $data): void
    {
        $conf = ['data' => StringUtility::getUniqueId('conf.data')];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            ['getData']
        );
        $subject->_set('data', $data);
        $subject
            ->expects(self::once())
            ->method('getData')
            ->with($conf['data'], $expect)
            ->willReturn($return);
        self::assertSame($return, $subject->stdWrap_data('discard', $conf));
    }

    /**
     * Check that stdWrap_dataWrap works properly.
     *
     * Show:
     *
     *  - Delegates to method dataWrap.
     *  - Parameter 1 is $content.
     *  - Parameter 2 is $conf['dataWrap'].
     *  - Returns the return value.
     */
    #[Test]
    public function stdWrap_dataWrap(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'dataWrap' => StringUtility::getUniqueId('dataWrap'),
            'dataWrap.' => [StringUtility::getUniqueId('not used')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['dataWrap'])->getMock();
        $subject
            ->expects(self::once())
            ->method('dataWrap')
            ->with($content, $conf['dataWrap'])
            ->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_dataWrap($content, $conf)
        );
    }

    /**
     * Data provider for the stdWrap_date test
     *
     * @return array [$expect, $content, $conf, $now]
     */
    public static function stdWrap_dateDataProvider(): array
    {
        // Fictive execution time: 2015-10-02 12:00
        $now = 1443780000;
        return [
            'given timestamp' => [
                '02.10.2015',
                $now,
                ['date' => 'd.m.Y'],
                $now,
            ],
            'empty string' => [
                '02.10.2015',
                '',
                ['date' => 'd.m.Y'],
                $now,
            ],
            'testing null' => [
                '02.10.2015',
                null,
                ['date' => 'd.m.Y'],
                $now,
            ],
            'given timestamp return GMT' => [
                '02.10.2015 10:00:00',
                $now,
                [
                    'date' => 'd.m.Y H:i:s',
                    'date.' => ['GMT' => true],
                ],
                $now,
            ],
        ];
    }

    /**
     * Check if stdWrap_date works properly.
     *
     * @param string $expected The expected output.
     * @param mixed $content The given input.
     * @param array $conf The given configuration.
     * @param int $now Fictive execution time.
     */
    #[DataProvider('stdWrap_dateDataProvider')]
    #[Test]
    public function stdWrap_date(string $expected, mixed $content, array $conf, int $now): void
    {
        $GLOBALS['EXEC_TIME'] = $now;
        self::assertEquals(
            $expected,
            $this->subject->stdWrap_date($content, $conf)
        );
    }

    /**
     * Check if stdWrap_debug works properly.
     */
    #[Test]
    public function stdWrap_debug(): void
    {
        $expect = '<pre>&lt;p class=&quot;class&quot;&gt;&lt;br/&gt;'
            . '&lt;/p&gt;</pre>';
        $content = '<p class="class"><br/></p>';
        self::assertSame($expect, $this->subject->stdWrap_debug($content));
    }

    /**
     * Check if stdWrap_debug works properly.
     *
     * Show:
     * - Calls the function debug.
     * - Parameter 1 is $this->data.
     * - Parameter 2 is the string '$cObj->data:'.
     * - Returns $content as is.
     *
     * Note 1:
     *   As PHPUnit can't mock PHP function calls, the call to debug can't be
     *   easily intercepted. The test is done indirectly by catching the
     *   frontend output of debug.
     *
     * Note 2:
     *   The second parameter to the debug function isn't used by the current
     *   implementation at all. It can't even indirectly be tested.
     */
    #[Test]
    public function stdWrap_debugData(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $content = StringUtility::getUniqueId('content');
        $key = StringUtility::getUniqueId('key');
        $value = StringUtility::getUniqueId('value');
        $altValue = StringUtility::getUniqueId('value alt');
        $this->subject->data = [$key => $value];
        ob_start();
        $result = $this->subject->stdWrap_debugData($content);
        $out = ob_get_clean();
        self::assertSame($result, $content);
        self::assertStringContainsString('$cObj->data', $out);
        self::assertStringContainsString($value, $out);
        self::assertStringNotContainsString($altValue, $out);
    }

    /**
     * Data provider for stdWrap_debugFunc.
     *
     * @return array [$expectArray, $confDebugFunc]
     */
    public static function stdWrap_debugFuncDataProvider(): array
    {
        return [
            'expect array by string' => [true, '2'],
            'expect array by integer' => [true, 2],
            'do not expect array' => [false, ''],
        ];
    }

    /**
     * Check if stdWrap_debugFunc works properly.
     *
     * Show:
     *
     * - Calls the function debug with one parameter.
     * - The parameter is the given $content string.
     * - The string is casted to array before, if (int)$conf['debugFunc'] is 2.
     * - Returns $content as is.
     *
     * Note 1:
     *
     *   As PHPUnit can't mock PHP function calls, the call to debug can't be
     *   easily intercepted. The test is done indirectly by catching the
     *   frontend output of debug.
     *
     * @param bool $expectArray If cast to array is expected.
     * @param mixed $confDebugFunc The configuration for $conf['debugFunc'].
     */
    #[DataProvider('stdWrap_debugFuncDataProvider')]
    #[Test]
    public function stdWrap_debugFunc(bool $expectArray, mixed $confDebugFunc): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $content = StringUtility::getUniqueId('content');
        $conf = ['debugFunc' => $confDebugFunc];
        ob_start();
        $result = $this->subject->stdWrap_debugFunc($content, $conf);
        $out = ob_get_clean();
        self::assertSame($result, $content);
        self::assertStringContainsString($content, $out);
        if ($expectArray) {
            self::assertStringContainsString('=>', $out);
        } else {
            self::assertStringNotContainsString('=>', $out);
        }
    }

    /**
     * Data provider for stdWrap_doubleBrTag
     *
     * @return array Order expected, input, config
     */
    public static function stdWrapDoubleBrTagDataProvider(): array
    {
        return [
            'no config: void input' => [
                '',
                '',
                [],
            ],
            'no config: single break' => [
                'one' . LF . 'two',
                'one' . LF . 'two',
                [],
            ],
            'no config: double break' => [
                'onetwo',
                'one' . LF . LF . 'two',
                [],
            ],
            'no config: double break with whitespace' => [
                'onetwo',
                'one' . LF . "\t" . ' ' . "\t" . ' ' . LF . 'two',
                [],
            ],
            'no config: single break around' => [
                LF . 'one' . LF,
                LF . 'one' . LF,
                [],
            ],
            'no config: double break around' => [
                'one',
                LF . LF . 'one' . LF . LF,
                [],
            ],
            'empty string: double break around' => [
                'one',
                LF . LF . 'one' . LF . LF,
                ['doubleBrTag' => ''],
            ],
            'br tag: double break' => [
                'one<br/>two',
                'one' . LF . LF . 'two',
                ['doubleBrTag' => '<br/>'],
            ],
            'br tag: double break around' => [
                '<br/>one<br/>',
                LF . LF . 'one' . LF . LF,
                ['doubleBrTag' => '<br/>'],
            ],
            'double br tag: double break around' => [
                '<br/><br/>one<br/><br/>',
                LF . LF . 'one' . LF . LF,
                ['doubleBrTag' => '<br/><br/>'],
            ],
        ];
    }

    /**
     * Check if doubleBrTag works properly
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $config The property 'doubleBrTag'.
     */
    #[DataProvider('stdWrapDoubleBrTagDataProvider')]
    #[Test]
    public function stdWrap_doubleBrTag(string $expected, string $input, array $config): void
    {
        self::assertEquals($expected, $this->subject->stdWrap_doubleBrTag($input, $config));
    }

    /**
     * Check if stdWrap_encapsLines works properly.
     *
     * Show:
     *
     * - Delegates to method encaps_lineSplit.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['encapsLines'].
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_encapsLines(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'encapsLines' => [StringUtility::getUniqueId('not used')],
            'encapsLines.' => [StringUtility::getUniqueId('encapsLines.')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['encaps_lineSplit'])->getMock();
        $subject
            ->expects(self::once())
            ->method('encaps_lineSplit')
            ->with($content, $conf['encapsLines.'])
            ->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_encapsLines($content, $conf)
        );
    }

    /**
     * Check if stdWrap_encapsLines uses self closing tags
     * only for allowed tags according to
     * @see https://www.w3.org/TR/html5/syntax.html#void-elements
     */
    #[DataProvider('html5SelfClosingTagsDataprovider')]
    #[Test]
    public function stdWrap_encapsLines_HTML5SelfClosingTags(string $input, string $expected): void
    {
        $rteParseFunc = self::getLibParseFunc_RTE();

        $conf = [
            'encapsLines' => $rteParseFunc['parseFunc.']['nonTypoTagStdWrap.']['encapsLines'] ?? null,
            'encapsLines.' => $rteParseFunc['parseFunc.']['nonTypoTagStdWrap.']['encapsLines.'] ?? null,
        ];
        // don't add an &nbsp; to tag without content
        $conf['encapsLines.']['innerStdWrap_all.']['ifBlank'] = '';
        $additionalEncapsTags = ['a', 'b', 'span'];

        // We want to allow any tag to be an encapsulating tag
        // since this is possible and we don't want an additional tag to be wrapped around.
        $conf['encapsLines.']['encapsTagList'] .= ',' . implode(',', $additionalEncapsTags);
        $conf['encapsLines.']['encapsTagList'] .= ',' . implode(',', [$input]);

        // Check if we get a self-closing tag for
        // empty tags where this is allowed according to HTML5
        $content = '<' . $input . ' id="myId" class="bodytext" />';
        $result = $this->subject->stdWrap_encapsLines($content, $conf);
        self::assertSame($expected, $result);
    }

    public static function html5SelfClosingTagsDataprovider(): array
    {
        return [
            'areaTag_selfclosing' => [
                'input' => 'area',
                'expected' => '<area id="myId" class="bodytext" />',
            ],
            'base_selfclosing' => [
                'input' => 'base',
                'expected' => '<base id="myId" class="bodytext" />',
            ],
            'br_selfclosing' => [
                'input' => 'br',
                'expected' => '<br id="myId" class="bodytext" />',
            ],
            'col_selfclosing' => [
                'input' => 'col',
                'expected' => '<col id="myId" class="bodytext" />',
            ],
            'embed_selfclosing' => [
                'input' => 'embed',
                'expected' => '<embed id="myId" class="bodytext" />',
            ],
            'hr_selfclosing' => [
                'input' => 'hr',
                'expected' => '<hr id="myId" class="bodytext" />',
            ],
            'img_selfclosing' => [
                'input' => 'img',
                'expected' => '<img id="myId" class="bodytext" />',
            ],
            'input_selfclosing' => [
                'input' => 'input',
                'expected' => '<input id="myId" class="bodytext" />',
            ],
            'keygen_selfclosing' => [
                'input' => 'keygen',
                'expected' => '<keygen id="myId" class="bodytext" />',
            ],
            'link_selfclosing' => [
                'input' => 'link',
                'expected' => '<link id="myId" class="bodytext" />',
            ],
            'meta_selfclosing' => [
                'input' => 'meta',
                'expected' => '<meta id="myId" class="bodytext" />',
            ],
            'param_selfclosing' => [
                'input' => 'param',
                'expected' => '<param id="myId" class="bodytext" />',
            ],
            'source_selfclosing' => [
                'input' => 'source',
                'expected' => '<source id="myId" class="bodytext" />',
            ],
            'track_selfclosing' => [
                'input' => 'track',
                'expected' => '<track id="myId" class="bodytext" />',
            ],
            'wbr_selfclosing' => [
                'input' => 'wbr',
                'expected' => '<wbr id="myId" class="bodytext" />',
            ],
            'p_notselfclosing' => [
                'input' => 'p',
                'expected' => '<p id="myId" class="bodytext"></p>',
            ],
            'a_notselfclosing' => [
                'input' => 'a',
                'expected' => '<a id="myId" class="bodytext"></a>',
            ],
            'strong_notselfclosing' => [
                'input' => 'strong',
                'expected' => '<strong id="myId" class="bodytext"></strong>',
            ],
            'span_notselfclosing' => [
                'input' => 'span',
                'expected' => '<span id="myId" class="bodytext"></span>',
            ],
        ];
    }

    /**
     * Data provider for stdWrap_encodeForJavaScriptValue.
     *
     * @return array[]
     */
    public static function stdWrap_encodeForJavaScriptValueDataProvider(): array
    {
        return [
            'double quote in string' => [
                '\'double\u0020quote\u0022\'',
                'double quote"',
            ],
            'backslash in string' => [
                '\'backslash\u0020\u005C\'',
                'backslash \\',
            ],
            'exclamation mark' => [
                '\'exclamation\u0021\'',
                'exclamation!',
            ],
            'whitespace tab, newline and carriage return' => [
                '\'white\u0009space\u000As\u000D\'',
                "white\tspace\ns\r",
            ],
            'single quote in string' => [
                '\'single\u0020quote\u0020\u0027\'',
                'single quote \'',
            ],
            'tag' => [
                '\'\u003Ctag\u003E\'',
                '<tag>',
            ],
            'ampersand in string' => [
                '\'amper\u0026sand\'',
                'amper&sand',
            ],
        ];
    }

    /**
     * Check if encodeForJavaScriptValue works properly.
     *
     * @param string $expect The expected output.
     * @param string $content The given input.
     */
    #[DataProvider('stdWrap_encodeForJavaScriptValueDataProvider')]
    #[Test]
    public function stdWrap_encodeForJavaScriptValue(string $expect, string $content): void
    {
        self::assertSame(
            $expect,
            $this->subject->stdWrap_encodeForJavaScriptValue($content)
        );
    }

    /**
     * Data provider for expandList
     *
     * @return array [$expect, $content]
     */
    public static function stdWrap_expandListDataProvider(): array
    {
        return [
            'numbers' => ['1,2,3', '1,2,3'],
            'range' => ['3,4,5', '3-5'],
            'numbers and range' => ['1,3,4,5,7', '1,3-5,7'],
        ];
    }

    /**
     * Test for the stdWrap function "expandList"
     *
     * The method simply delegates to GeneralUtility::expandList. There is no
     * need to repeat the full set of tests of this method here. As PHPUnit
     * can't mock static methods, to prove they are called, all we do here
     * is to provide a few smoke tests.
     *
     * @param string $expected The expected output.
     * @param string $content The given content.
     */
    #[DataProvider('stdWrap_expandListDataProvider')]
    #[Test]
    public function stdWrap_expandList(string $expected, string $content): void
    {
        self::assertEquals(
            $expected,
            $this->subject->stdWrap_expandList($content)
        );
    }

    /**
     * Check if stdWrap_field works properly.
     *
     * Show:
     *
     * - calls getFieldVal
     * - passes conf['field'] as parameter
     */
    #[Test]
    public function stdWrap_field(): void
    {
        $expect = StringUtility::getUniqueId('expect');
        $conf = ['field' => StringUtility::getUniqueId('field')];
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['getFieldVal'])->getMock();
        $subject
            ->expects(self::once())
            ->method('getFieldVal')
            ->with($conf['field'])
            ->willReturn($expect);
        self::assertSame(
            $expect,
            $subject->stdWrap_field('discarded', $conf)
        );
    }

    /**
     * Data provider for stdWrap_fieldRequired.
     *
     * @return array [$expect, $stop, $content, $conf]
     */
    public static function stdWrap_fieldRequiredDataProvider(): array
    {
        $content = StringUtility::getUniqueId('content');
        return [
            // resulting in boolean false
            'false is false' => [
                '',
                true,
                $content,
                ['fieldRequired' => 'false'],
            ],
            'null is false' => [
                '',
                true,
                $content,
                ['fieldRequired' => 'null'],
            ],
            'empty string is false' => [
                '',
                true,
                $content,
                ['fieldRequired' => 'empty'],
            ],
            'whitespace is false' => [
                '',
                true,
                $content,
                ['fieldRequired' => 'whitespace'],
            ],
            'string zero is false' => [
                '',
                true,
                $content,
                ['fieldRequired' => 'stringZero'],
            ],
            'string zero with whitespace is false' => [
                '',
                true,
                $content,
                ['fieldRequired' => 'stringZeroWithWhiteSpace'],
            ],
            'zero is false' => [
                '',
                true,
                $content,
                ['fieldRequired' => 'zero'],
            ],
            // resulting in boolean true
            'true is true' => [
                $content,
                false,
                $content,
                ['fieldRequired' => 'true'],
            ],
            'string is true' => [
                $content,
                false,
                $content,
                ['fieldRequired' => 'string'],
            ],
            'one is true' => [
                $content,
                false,
                $content,
                ['fieldRequired' => 'one'],
            ],
        ];
    }

    /**
     * Check if stdWrap_fieldRequired works properly.
     *
     * Show:
     *
     *  - The value is taken from property array data.
     *  - The key is taken from $conf['fieldRequired'].
     *  - The value is casted to string by trim() and trimmed.
     *  - It is further casted to boolean by if().
     *  - False triggers a stop of further rendering.
     *  - False returns '', true the given content as is.
     *
     * @param string $expect The expected output.
     * @param bool $stop Expect stop further rendering.
     * @param string $content The given input.
     * @param array $conf The given configuration.
     */
    #[DataProvider('stdWrap_fieldRequiredDataProvider')]
    #[Test]
    public function stdWrap_fieldRequired(string $expect, bool $stop, string $content, array $conf): void
    {
        $data = [
            'null' => null,
            'false' => false,
            'empty' => '',
            'whitespace' => "\t" . ' ',
            'stringZero' => '0',
            'stringZeroWithWhiteSpace' => "\t" . ' 0 ' . "\t",
            'zero' => 0,
            'string' => 'string',
            'true' => true,
            'one' => 1,
        ];
        $subject = $this->subject;
        $subject->_set('data', $data);
        $subject->_set('stdWrapRecursionLevel', 1);
        $subject->_set('stopRendering', [1 => false]);
        self::assertSame(
            $expect,
            $subject->stdWrap_fieldRequired($content, $conf)
        );
        self::assertSame($stop, $subject->_get('stopRendering')[1]);
    }

    /**
     * Data provider for the hash test
     *
     * @return array [$expect, $content, $conf]
     */
    public static function hashDataProvider(): array
    {
        return [
            'md5' => [
                'bacb98acf97e0b6112b1d1b650b84971',
                'joh316',
                ['hash' => 'md5'],
            ],
            'sha1' => [
                '063b3d108bed9f88fa618c6046de0dccadcf3158',
                'joh316',
                ['hash' => 'sha1'],
            ],
            'stdWrap capability' => [
                'bacb98acf97e0b6112b1d1b650b84971',
                'joh316',
                [
                    'hash' => '5',
                    'hash.' => ['wrap' => 'md|'],
                ],
            ],
            'non-existing hashing algorithm' => [
                '',
                'joh316',
                ['hash' => 'non-existing'],
            ],
        ];
    }

    /**
     * Check if stdWrap_hash works properly.
     *
     * Show:
     *
     *  - Algorithms: sha1, md5
     *  - Returns '' for invalid algorithm.
     *  - Value can be processed by stdWrap.
     *
     * @param string $expect The expected output.
     * @param string $content The given content.
     * @param array $conf The given configuration.
     */
    #[DataProvider('hashDataProvider')]
    #[Test]
    public function stdWrap_hash(string $expect, string $content, array $conf): void
    {
        self::assertSame(
            $expect,
            $this->subject->stdWrap_hash($content, $conf)
        );
    }

    /**
     * Data provider for stdWrap_htmlSpecialChars
     *
     * @return array Order: expected, input, conf
     */
    public static function stdWrap_htmlSpecialCharsDataProvider(): array
    {
        return [
            'void conf' => [
                '&lt;span&gt;1 &amp;lt; 2&lt;/span&gt;',
                '<span>1 &lt; 2</span>',
                [],
            ],
            'void preserveEntities' => [
                '&lt;span&gt;1 &amp;lt; 2&lt;/span&gt;',
                '<span>1 &lt; 2</span>',
                ['htmlSpecialChars.' => []],
            ],
            'false preserveEntities' => [
                '&lt;span&gt;1 &amp;lt; 2&lt;/span&gt;',
                '<span>1 &lt; 2</span>',
                ['htmlSpecialChars.' => ['preserveEntities' => 0]],
            ],
            'true preserveEntities' => [
                '&lt;span&gt;1 &lt; 2&lt;/span&gt;',
                '<span>1 &lt; 2</span>',
                ['htmlSpecialChars.' => ['preserveEntities' => 1]],
            ],
        ];
    }

    /**
     * Check if stdWrap_htmlSpecialChars works properly
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf htmlSpecialChars.preserveEntities
     */
    #[DataProvider('stdWrap_htmlSpecialCharsDataProvider')]
    #[Test]
    public function stdWrap_htmlSpecialChars(string $expected, string $input, array $conf): void
    {
        self::assertSame(
            $expected,
            $this->subject->stdWrap_htmlSpecialChars($input, $conf)
        );
    }

    /**
     * Data provider for stdWrap_if.
     *
     * @return array [$expect, $stop, $content, $conf, $times, $will]
     */
    public static function stdWrap_ifDataProvider(): array
    {
        $content = StringUtility::getUniqueId('content');
        $conf = ['if.' => [StringUtility::getUniqueId('if.')]];
        return [
            // evals to true
            'empty config' => [
                $content,
                false,
                $content,
                [],
                0,
                false,
            ],
            'if. is empty array' => [
                $content,
                false,
                $content,
                ['if.' => []],
                0,
                false,
            ],
            'if. is null' => [
                $content,
                false,
                $content,
                ['if.' => null],
                0,
                false,
            ],
            'if. is false' => [
                $content,
                false,
                $content,
                ['if.' => false],
                0,
                false,
            ],
            'if. is 0' => [
                $content,
                false,
                $content,
                ['if.' => false],
                0,
                false,
            ],
            'if. is "0"' => [
                $content,
                false,
                $content,
                ['if.' => '0'],
                0,
                false,
            ],
            'checkIf returning true' => [
                $content,
                false,
                $content,
                $conf,
                1,
                true,
            ],
            // evals to false
            'checkIf returning false' => [
                '',
                true,
                $content,
                $conf,
                1,
                false,
            ],
        ];
    }

    /**
     * Check if stdWrap_if works properly.
     *
     * Show:
     *
     *  - Delegates to the method checkIf to check for 'true'.
     *  - The parameter to checkIf is $conf['if.'].
     *  - Is also 'true' if $conf['if.'] is empty (PHP method empty).
     *  - 'False' triggers a stop of further rendering.
     *  - Returns the content as is or '' if false.
     *
     * @param string $expect The expected output.
     * @param bool $stop Expect stop further rendering.
     * @param string $content The given content.
     * @param array $conf
     * @param int $times Times checkIf is called (0 or 1).
     * @param bool $will Return of checkIf (null if not called).
     */
    #[DataProvider('stdWrap_ifDataProvider')]
    #[Test]
    public function stdWrap_if(string $expect, bool $stop, string $content, array $conf, int $times, bool $will): void
    {
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            ['checkIf']
        );
        $subject->_set('stdWrapRecursionLevel', 1);
        $subject->_set('stopRendering', [1 => false]);
        $subject
            ->expects(self::exactly($times))
            ->method('checkIf')
            ->with($conf['if.'] ?? null)
            ->willReturn($will);
        self::assertSame($expect, $subject->stdWrap_if($content, $conf));
        self::assertSame($stop, $subject->_get('stopRendering')[1]);
    }

    /**
     * Data provider for checkIf.
     *
     * @return array [$expect, $conf]
     */
    public static function checkIfDataProvider(): array
    {
        return [
            'true bitAnd the same' => [true, ['bitAnd' => '4', 'value' => '4']],
            'true bitAnd included' => [true, ['bitAnd' => '6', 'value' => '4']],
            'false bitAnd' => [false, ['bitAnd' => '4', 'value' => '3']],
            'negate true bitAnd the same' => [false, ['bitAnd' => '4', 'value' => '4', 'negate' => '1']],
            'negate true bitAnd included' => [false, ['bitAnd' => '6', 'value' => '4', 'negate' => '1']],
            'negate false bitAnd' => [true, ['bitAnd' => '3', 'value' => '4', 'negate' => '1']],
            'contains matches' => [true, ['contains' => 'long text', 'value' => 'this is a long text']],
            'contains does not match' => [false, ['contains' => 'short text', 'value' => 'this is a long text']],
            'negate contains does not match' => [false, ['contains' => 'long text', 'value' => 'this is a long text', 'negate' => '1']],
            'negate contains does not match but matches' => [true, ['contains' => 'short text', 'value' => 'this is a long text', 'negate' => '1']],
            'startsWith matches' => [true, ['startsWith' => 'this is', 'value' => 'this is a long text']],
            'startsWith does not match' => [false, ['startsWith' => 'a long text', 'value' => 'this is a long text']],
            'negate startsWith does not match' => [false, ['startsWith' => 'this is', 'value' => 'this is a long text', 'negate' => '1']],
            'negate startsWith does not match but matches' => [true, ['startsWith' => 'a long text', 'value' => 'this is a long text', 'negate' => '1']],
            'endsWith matches' => [true, ['endsWith' => 'a long text', 'value' => 'this is a long text']],
            'endsWith does not match' => [false, ['endsWith' => 'this is', 'value' => 'this is a long text']],
            'negate endsWith does not match' => [false, ['endsWith' => 'a long text', 'value' => 'this is a long text', 'negate' => '1']],
            'negate endsWith does not match but matches' => [true, ['endsWith' => 'this is', 'value' => 'this is a long text', 'negate' => '1']],
        ];
    }

    /**
     * Check if checkIf works properly.
     *
     * @param bool $expect Whether result should be true or false.
     * @param array $conf TypoScript configuration to pass into checkIf
     */
    #[DataProvider('checkIfDataProvider')]
    #[Test]
    public function checkIf(bool $expect, array $conf): void
    {
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            ['stdWrap']
        );
        self::assertSame($expect, $subject->checkIf($conf));
    }

    /**
     * Data provider for stdWrap_ifBlank.
     *
     * @return array [$expect, $content, $conf]
     */
    public static function stdWrap_ifBlankDataProvider(): array
    {
        $alt = StringUtility::getUniqueId('alternative content');
        $conf = ['ifBlank' => $alt];
        return [
            // blank cases
            'null is blank' => [$alt, null, $conf],
            'false is blank' => [$alt, false, $conf],
            'empty string is blank' => [$alt, '', $conf],
            'whitespace is blank' => [$alt, "\t" . '', $conf],
            // non-blank cases
            'string is not blank' => ['string', 'string', $conf],
            'zero is not blank' => [0, 0, $conf],
            'zero string is not blank' => ['0', '0', $conf],
            'zero float is not blank' => [0.0, 0.0, $conf],
            'true is not blank' => [true, true, $conf],
        ];
    }

    /**
     * Check that stdWrap_ifBlank works properly.
     *
     * Show:
     *
     * - The content is returned if not blank.
     * - Otherwise $conf['ifBlank'] is returned.
     * - The check for blank is done by comparing the trimmed content
     *   with the empty string for equality.
     *
     * @param mixed $expect
     * @param mixed $content The given input.
     * @param array $conf The given configuration.
     */
    #[DataProvider('stdWrap_ifBlankDataProvider')]
    #[Test]
    public function stdWrap_ifBlank(mixed $expect, mixed $content, array $conf): void
    {
        $result = $this->subject->stdWrap_ifBlank($content, $conf);
        self::assertSame($expect, $result);
    }

    /**
     * Data provider for stdWrap_ifEmpty.
     *
     * @return array [$expect, $content, $conf]
     */
    public static function stdWrap_ifEmptyDataProvider(): array
    {
        $alt = StringUtility::getUniqueId('alternative content');
        $conf = ['ifEmpty' => $alt];
        return [
            // empty cases
            'null is empty' => [$alt, null, $conf],
            'false is empty' => [$alt, false, $conf],
            'zero is empty' => [$alt, 0, $conf],
            'float zero is empty' => [$alt, 0.0, $conf],
            'whitespace is empty' => [$alt, "\t" . ' ', $conf],
            'empty string is empty' => [$alt, '', $conf],
            'zero string is empty' => [$alt, '0', $conf],
            'zero string is empty with whitespace' => [
                $alt,
                "\t" . ' 0 ' . "\t",
                $conf,
            ],
            // non-empty cases
            'string is not empty' => ['string', 'string', $conf],
            '1 is not empty' => [1, 1, $conf],
            '-1 is not empty' => [-1, -1, $conf],
            '0.1 is not empty' => [0.1, 0.1, $conf],
            '-0.1 is not empty' => [-0.1, -0.1, $conf],
            'true is not empty' => [true, true, $conf],
        ];
    }

    /**
     * Check that stdWrap_ifEmpty works properly.
     *
     * Show:
     *
     * - Returns the content, if not empty.
     * - Otherwise returns $conf['ifEmpty'].
     * - Empty is checked by cast to boolean after trimming.
     *
     * @param mixed $expect The expected output.
     * @param mixed $content The given content.
     * @param array $conf The given configuration.
     */
    #[DataProvider('stdWrap_ifEmptyDataProvider')]
    #[Test]
    public function stdWrap_ifEmpty(mixed $expect, mixed $content, array $conf): void
    {
        $result = $this->subject->stdWrap_ifEmpty($content, $conf);
        self::assertSame($expect, $result);
    }

    /**
     * Data provider for stdWrap_ifNull.
     *
     * @return array [$expect, $content, $conf]
     */
    public static function stdWrap_ifNullDataProvider(): array
    {
        $alt = StringUtility::getUniqueId('alternative content');
        $conf = ['ifNull' => $alt];
        return [
            'only null is null' => [$alt, null, $conf],
            'zero is not null' => [0, 0, $conf],
            'float zero is not null' => [0.0, 0.0, $conf],
            'false is not null' => [false, false, $conf],
            'zero string is not null' => ['0', '0', $conf],
            'empty string is not null' => ['', '', $conf],
            'whitespace is not null' => ["\t" . '', "\t" . '', $conf],
        ];
    }

    /**
     * Check that stdWrap_ifNull works properly.
     *
     * Show:
     *
     * - Returns the content, if not null.
     * - Otherwise returns $conf['ifNull'].
     * - Null is strictly checked by identity with null.
     *
     * @param mixed $expect
     * @param mixed $content The given input.
     * @param array $conf The given configuration.
     */
    #[DataProvider('stdWrap_ifNullDataProvider')]
    #[Test]
    public function stdWrap_ifNull(mixed $expect, mixed $content, array $conf): void
    {
        $result = $this->subject->stdWrap_ifNull($content, $conf);
        self::assertSame($expect, $result);
    }

    /**
     * Data provider for stdWrap_innerWrap
     *
     * @return array Order expected, input, conf
     */
    public static function stdWrap_innerWrapDataProvider(): array
    {
        return [
            'no conf' => [
                'XXX',
                'XXX',
                [],
            ],
            'simple' => [
                '<wrap>XXX</wrap>',
                'XXX',
                ['innerWrap' => '<wrap>|</wrap>'],
            ],
            'missing pipe puts wrap before' => [
                '<pre>XXX',
                'XXX',
                ['innerWrap' => '<pre>'],
            ],
            'trims whitespace' => [
                '<wrap>XXX</wrap>',
                'XXX',
                ['innerWrap' => '<wrap>' . "\t" . ' | ' . "\t" . '</wrap>'],
            ],
            'split char change is not possible' => [
                '<wrap> # </wrap>XXX',
                'XXX',
                [
                    'innerWrap' => '<wrap> # </wrap>',
                    'innerWrap.' => ['splitChar' => '#'],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_innerWrap works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Property: innerWrap
     */
    #[DataProvider('stdWrap_innerWrapDataProvider')]
    #[Test]
    public function stdWrap_innerWrap(string $expected, string $input, array $conf): void
    {
        self::assertSame(
            $expected,
            $this->subject->stdWrap_innerWrap($input, $conf)
        );
    }

    /**
     * Data provider for stdWrap_innerWrap2
     *
     * @return array Order expected, input, conf
     */
    public static function stdWrap_innerWrap2DataProvider(): array
    {
        return [
            'no conf' => [
                'XXX',
                'XXX',
                [],
            ],
            'simple' => [
                '<wrap>XXX</wrap>',
                'XXX',
                ['innerWrap2' => '<wrap>|</wrap>'],
            ],
            'missing pipe puts wrap before' => [
                '<pre>XXX',
                'XXX',
                ['innerWrap2' => '<pre>'],
            ],
            'trims whitespace' => [
                '<wrap>XXX</wrap>',
                'XXX',
                ['innerWrap2' => '<wrap>' . "\t" . ' | ' . "\t" . '</wrap>'],
            ],
            'split char change is not possible' => [
                '<wrap> # </wrap>XXX',
                'XXX',
                [
                    'innerWrap2' => '<wrap> # </wrap>',
                    'innerWrap2.' => ['splitChar' => '#'],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_innerWrap2 works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Property: innerWrap2
     */
    #[DataProvider('stdWrap_innerWrap2DataProvider')]
    #[Test]
    public function stdWrap_innerWrap2(string $expected, string $input, array $conf): void
    {
        self::assertSame(
            $expected,
            $this->subject->stdWrap_innerWrap2($input, $conf)
        );
    }

    /**
     * Check if stdWrap_insertData works properly.
     *
     * Show:
     *
     *  - Delegates to method insertData.
     *  - Parameter 1 is $content.
     *  - Returns the return value.
     */
    #[Test]
    public function stdWrap_insertData(): void
    {
        $content = StringUtility::getUniqueId('content');
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['insertData'])->getMock();
        $subject->expects(self::once())->method('insertData')
            ->with($content)->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_insertData($content)
        );
    }

    /**
     * Data provider for stdWrap_insertData
     *
     * @return array [$expect, $content]
     */
    public static function stdWrap_insertDataProvider(): array
    {
        return [
            'empty' => ['', ''],
            'notFoundData' => ['any=1', 'any{$string}=1'],
            'queryParameter' => ['any{#string}=1', 'any{#string}=1'],
        ];
    }

    /**
     * Check that stdWrap_insertData works properly with given input.
     *
     * @param mixed $expect The expected output.
     * @param string $content The given input.
     */
    #[DataProvider('stdWrap_insertDataProvider')]
    #[Test]
    public function stdWrap_insertDataAndInputExamples(mixed $expect, string $content): void
    {
        self::assertSame($expect, $this->subject->stdWrap_insertData($content));
    }

    /**
     * Data provider for stdWrap_intval
     *
     * @return array [$expect, $content]
     */
    public static function stdWrap_intvalDataProvider(): array
    {
        return [
            // numbers
            'int' => [123, 123],
            'float' => [123, 123.45],
            'float does not round up' => [123, 123.55],
            // negative numbers
            'negative int' => [-123, -123],
            'negative float' => [-123, -123.45],
            'negative float does not round down' => [-123, -123.55],
            // strings
            'word string' => [0, 'string'],
            'empty string' => [0, ''],
            'zero string' => [0, '0'],
            'int string' => [123, '123'],
            'float string' => [123, '123.55'],
            'negative float string' => [-123, '-123.55'],
            // other types
            'null' => [0, null],
            'true' => [1, true],
            'false' => [0, false],
        ];
    }

    /**
     * Check that stdWrap_intval works properly.
     *
     * Show:
     *
     * - It does not round up.
     * - All types of input is casted to int:
     *   - null: 0
     *   - false: 0
     *   - true: 1
     *   -
     *
     *
     *
     * @param int $expect The expected output.
     * @param mixed $content The given input.
     */
    #[DataProvider('stdWrap_intvalDataProvider')]
    #[Test]
    public function stdWrap_intval(int $expect, mixed $content): void
    {
        self::assertSame($expect, $this->subject->stdWrap_intval($content));
    }

    /**
     * Data provider for stdWrap_keywords
     *
     * @return string[][] Order expected, input
     */
    public static function stdWrapKeywordsDataProvider(): array
    {
        return [
            'empty string' => ['', ''],
            'blank' => ['', ' '],
            'tab' => ['', "\t"],
            'single semicolon' => [',', ' ; '],
            'single comma' => [',', ' , '],
            'single nl' => [',', ' ' . PHP_EOL . ' '],
            'double semicolon' => [',,', ' ; ; '],
            'double comma' => [',,', ' , , '],
            'double nl' => [',,', ' ' . PHP_EOL . ' ' . PHP_EOL . ' '],
            'simple word' => ['one', ' one '],
            'simple word trimmed' => ['one', 'one'],
            ', separated' => ['one,two', ' one , two '],
            '; separated' => ['one,two', ' one ; two '],
            'nl separated' => ['one,two', ' one ' . PHP_EOL . ' two '],
            ', typical' => ['one,two,three', 'one, two, three'],
            '; typical' => ['one,two,three', ' one; two; three'],
            'nl typical' => [
                'one,two,three',
                'one' . PHP_EOL . 'two' . PHP_EOL . 'three',
            ],
            ', sourounded' => [',one,two,', ' , one , two , '],
            '; sourounded' => [',one,two,', ' ; one ; two ; '],
            'nl sourounded' => [
                ',one,two,',
                ' ' . PHP_EOL . ' one ' . PHP_EOL . ' two ' . PHP_EOL . ' ',
            ],
            'mixed' => [
                'one,two,three,four',
                ' one, two; three' . PHP_EOL . 'four',
            ],
            'keywods with blanks in words' => [
                'one plus,two minus',
                ' one plus , two minus ',
            ],
        ];
    }

    /**
     * Check if stdWrap_keywords works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     */
    #[DataProvider('stdWrapKeywordsDataProvider')]
    #[Test]
    public function stdWrap_keywords(string $expected, string $input): void
    {
        self::assertSame($expected, $this->subject->stdWrap_keywords($input));
    }

    /**
     * Data provider for stdWrap_lang
     *
     * @return array Order expected, input, conf, language
     */
    public static function stdWrap_langDataProvider(): array
    {
        return [
            'empty conf' => [
                'original',
                'original',
                [],
                'de_DE',
            ],
            'translation de' => [
                'Übersetzung',
                'original',
                [
                    'lang.' => [
                        'de' => 'Übersetzung',
                        'it' => 'traduzione',
                    ],
                ],
                'de_DE',
            ],
            'translation it' => [
                'traduzione',
                'original',
                [
                    'lang.' => [
                        'de' => 'Übersetzung',
                        'it' => 'traduzione',
                    ],
                ],
                'it_IT',
            ],
            'no translation' => [
                'original',
                'original',
                [
                    'lang.' => [
                        'de' => 'Übersetzung',
                        'it' => 'traduzione',
                    ],
                ],
                'en',
            ],
            'missing label' => [
                'original',
                'original',
                [
                    'lang.' => [
                        'de' => 'Übersetzung',
                        'it' => 'traduzione',
                    ],
                ],
                'fr_FR',
            ],
        ];
    }

    /**
     * Check if stdWrap_lang works properly with site handling.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Properties: lang.xy.
     * @param string $language For $TSFE->config[config][language].
     */
    #[DataProvider('stdWrap_langDataProvider')]
    #[Test]
    public function stdWrap_langViaSiteLanguage(string $expected, string $input, array $conf, string $language): void
    {
        $site = $this->createSiteWithLanguage([
            'base' => '/',
            'languageId' => 2,
            'locale' => $language,
        ]);
        $request = new ServerRequest();
        $request = $request->withAttribute('language', $site->getLanguageById(2));
        $this->subject->setRequest($request);
        self::assertSame(
            $expected,
            $this->subject->stdWrap_lang($input, $conf)
        );
    }

    /**
     * Check if stdWrap_listNum works properly.
     *
     * Show:
     *
     * - Delegates to method listNum.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['listNum'].
     * - Parameter 3 is $conf['listNum.']['splitChar'].
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_listNum(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'listNum' => StringUtility::getUniqueId('listNum'),
            'listNum.' => [
                'splitChar' => StringUtility::getUniqueId('splitChar'),
            ],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['listNum'])->getMock();
        $subject
            ->expects(self::once())
            ->method('listNum')
            ->with(
                $content,
                $conf['listNum'],
                $conf['listNum.']['splitChar']
            )
            ->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_listNum($content, $conf)
        );
    }

    /**
     * Data provider for stdWrap_noTrimWrap.
     *
     * @return array [$expect, $content, $conf]
     */
    public static function stdWrap_noTrimWrapDataProvider(): array
    {
        return [
            'Standard case' => [
                ' left middle right ',
                'middle',
                [
                    'noTrimWrap' => '| left | right |',
                ],
            ],
            'Tabs as whitespace' => [
                "\t" . 'left' . "\t" . 'middle' . "\t" . 'right' . "\t",
                'middle',
                [
                    'noTrimWrap' =>
                        '|' . "\t" . 'left' . "\t" . '|' . "\t" . 'right' . "\t" . '|',
                ],
            ],
            'Split char is 0' => [
                ' left middle right ',
                'middle',
                [
                    'noTrimWrap' => '0 left 0 right 0',
                    'noTrimWrap.' => ['splitChar' => '0'],
                ],
            ],
            'Split char is pipe (default)' => [
                ' left middle right ',
                'middle',
                [
                    'noTrimWrap' => '| left | right |',
                    'noTrimWrap.' => ['splitChar' => '|'],
                ],
            ],
            'Split char is a' => [
                ' left middle right ',
                'middle',
                [
                    'noTrimWrap' => 'a left a right a',
                    'noTrimWrap.' => ['splitChar' => 'a'],
                ],
            ],
            'Split char is a word (ab)' => [
                ' left middle right ',
                'middle',
                [
                    'noTrimWrap' => 'ab left ab right ab',
                    'noTrimWrap.' => ['splitChar' => 'ab'],
                ],
            ],
            'Split char accepts stdWrap' => [
                ' left middle right ',
                'middle',
                [
                    'noTrimWrap' => 'abc left abc right abc',
                    'noTrimWrap.' => [
                        'splitChar' => 'b',
                        'splitChar.' => ['wrap' => 'a|c'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_noTrimWrap works properly.
     *
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @param array $conf The given configuration.
     */
    #[DataProvider('stdWrap_noTrimWrapDataProvider')]
    #[Test]
    public function stdWrap_noTrimWrap(string $expect, string $content, array $conf): void
    {
        self::assertSame(
            $expect,
            $this->subject->stdWrap_noTrimWrap($content, $conf)
        );
    }

    /**
     * Check if stdWrap_numRows works properly.
     *
     * Show:
     *
     * - Delegates to method numRows.
     * - Parameter is $conf['numRows.'].
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_numRows(): void
    {
        $conf = [
            'numRows' => StringUtility::getUniqueId('numRows'),
            'numRows.' => [StringUtility::getUniqueId('numRows')],
        ];
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['numRows'])->getMock();
        $subject->expects(self::once())->method('numRows')
            ->with($conf['numRows.'])->willReturn('return');
        self::assertSame(
            'return',
            $subject->stdWrap_numRows('discard', $conf)
        );
    }

    /**
     * Check if stdWrap_numberFormat works properly.
     *
     * Show:
     *
     * - Delegates to the method numberFormat.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['numberFormat.'].
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_numberFormat(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'numberFormat' => StringUtility::getUniqueId('not used'),
            'numberFormat.' => [StringUtility::getUniqueId('numberFormat.')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['numberFormat'])->getMock();
        $subject
            ->expects(self::once())
            ->method('numberFormat')
            ->with((float)$content, $conf['numberFormat.'])
            ->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_numberFormat($content, $conf)
        );
    }

    /**
     * Data provider for stdWrap_outerWrap
     *
     * @return array Order expected, input, conf
     */
    public static function stdWrap_outerWrapDataProvider(): array
    {
        return [
            'no conf' => [
                'XXX',
                'XXX',
                [],
            ],
            'simple' => [
                '<wrap>XXX</wrap>',
                'XXX',
                ['outerWrap' => '<wrap>|</wrap>'],
            ],
            'missing pipe puts wrap before' => [
                '<pre>XXX',
                'XXX',
                ['outerWrap' => '<pre>'],
            ],
            'trims whitespace' => [
                '<wrap>XXX</wrap>',
                'XXX',
                ['outerWrap' => '<wrap>' . "\t" . ' | ' . "\t" . '</wrap>'],
            ],
            'split char change is not possible' => [
                '<wrap> # </wrap>XXX',
                'XXX',
                [
                    'outerWrap' => '<wrap> # </wrap>',
                    'outerWrap.' => ['splitChar' => '#'],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_outerWrap works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Property: outerWrap
     */
    #[DataProvider('stdWrap_outerWrapDataProvider')]
    #[Test]
    public function stdWrap_outerWrap(string $expected, string $input, array $conf): void
    {
        self::assertSame(
            $expected,
            $this->subject->stdWrap_outerWrap($input, $conf)
        );
    }

    /**
     * Data provider for stdWrap_csConv
     *
     * @return array Order expected, input, conf
     */
    public static function stdWrap_overrideDataProvider(): array
    {
        return [
            'standard case' => [
                'override',
                'content',
                ['override' => 'override'],
            ],
            'empty conf does not override' => [
                'content',
                'content',
                [],
            ],
            'empty string does not override' => [
                'content',
                'content',
                ['override' => ''],
            ],
            'whitespace does not override' => [
                'content',
                'content',
                ['override' => ' ' . "\t"],
            ],
            'zero does not override' => [
                'content',
                'content',
                ['override' => 0],
            ],
            'false does not override' => [
                'content',
                'content',
                ['override' => false],
            ],
            'null does not override' => [
                'content',
                'content',
                ['override' => null],
            ],
            'one does override' => [
                1,
                'content',
                ['override' => 1],
            ],
            'minus one does override' => [
                -1,
                'content',
                ['override' => -1],
            ],
            'float does override' => [
                -0.1,
                'content',
                ['override' => -0.1],
            ],
            'true does override' => [
                true,
                'content',
                ['override' => true],
            ],
            'the value is not trimmed' => [
                "\t" . 'override',
                'content',
                ['override' => "\t" . 'override'],
            ],
        ];
    }

    /**
     * Check if stdWrap_override works properly.
     *
     * @param array $conf Property: setCurrent
     */
    #[DataProvider('stdWrap_overrideDataProvider')]
    #[Test]
    public function stdWrap_override(mixed $expect, string $content, array $conf): void
    {
        self::assertSame(
            $expect,
            $this->subject->stdWrap_override($content, $conf)
        );
    }

    /**
     * Check if stdWrap_parseFunc works properly.
     *
     * Show:
     *
     * - Delegates to method parseFunc.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['parseFunc.'].
     * - Parameter 3 is $conf['parseFunc'].
     * - Returns the return.
     */
    #[Test]
    public function stdWrap_parseFunc(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'parseFunc' => StringUtility::getUniqueId('parseFunc'),
            'parseFunc.' => [StringUtility::getUniqueId('parseFunc.')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['parseFunc'])->getMock();
        $subject
            ->expects(self::once())
            ->method('parseFunc')
            ->with($content, $conf['parseFunc.'], $conf['parseFunc'])
            ->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_parseFunc($content, $conf)
        );
    }

    /**
     * Check if stdWrap_postCObject works properly.
     *
     * Show:
     *
     * - Delegates to the method cObjGetSingle().
     * - Parameter 1 is $conf['postCObject'].
     * - Parameter 2 is $conf['postCObject.'].
     * - Parameter 3 is '/stdWrap/.postCObject'.
     * - Returns the return value appended by $content.
     */
    #[Test]
    public function stdWrap_postCObject(): void
    {
        $debugKey = '/stdWrap/.postCObject';
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'postCObject' => StringUtility::getUniqueId('postCObject'),
            'postCObject.' => [StringUtility::getUniqueId('postCObject.')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['cObjGetSingle'])->getMock();
        $subject
            ->expects(self::once())
            ->method('cObjGetSingle')
            ->with($conf['postCObject'], $conf['postCObject.'], $debugKey)
            ->willReturn($return);
        self::assertSame(
            $content . $return,
            $subject->stdWrap_postCObject($content, $conf)
        );
    }

    /**
     * Check that stdWrap_postUserFunc works properly.
     *
     * Show:
     *  - Delegates to method callUserFunction.
     *  - Parameter 1 is $conf['postUserFunc'].
     *  - Parameter 2 is $conf['postUserFunc.'].
     *  - Returns the return value.
     */
    #[Test]
    public function stdWrap_postUserFunc(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'postUserFunc' => StringUtility::getUniqueId('postUserFunc'),
            'postUserFunc.' => [StringUtility::getUniqueId('postUserFunc.')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['callUserFunction'])->getMock();
        $subject
            ->expects(self::once())
            ->method('callUserFunction')
            ->with($conf['postUserFunc'], $conf['postUserFunc.'])
            ->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_postUserFunc($content, $conf)
        );
    }

    /**
     * Check if stdWrap_postUserFuncInt works properly.
     *
     * Show:
     *
     * - Calls frontend controller method uniqueHash.
     * - Concatenates "INT_SCRIPT." and the returned hash to $substKey.
     * - Configures the frontend controller for 'INTincScript.$substKey'.
     * - The configuration array contains:
     *   - content: $content
     *   - postUserFunc: $conf['postUserFuncInt']
     *   - conf: $conf['postUserFuncInt.']
     *   - type: 'POSTUSERFUNC'
     *   - cObj: serialized content renderer object
     * - Returns "<!-- $substKey -->".
     */
    #[Test]
    public function stdWrap_postUserFuncInt(): void
    {
        $uniqueHash = StringUtility::getUniqueId('uniqueHash');
        $substKey = 'INT_SCRIPT.' . $uniqueHash;
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'postUserFuncInt' => StringUtility::getUniqueId('function'),
            'postUserFuncInt.' => [StringUtility::getUniqueId('function array')],
        ];
        $expect = '<!--' . $substKey . '-->';
        $frontend = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->disableOriginalConstructor()->onlyMethods(['uniqueHash'])
            ->getMock();
        $frontend->expects(self::once())->method('uniqueHash')
            ->with()->willReturn($uniqueHash);
        $frontend->config = ['INTincScript' => []];
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            null,
            [$frontend]
        );
        self::assertSame(
            $expect,
            $subject->stdWrap_postUserFuncInt($content, $conf)
        );
        $array = [
            'content' => $content,
            'postUserFunc' => $conf['postUserFuncInt'],
            'conf' => $conf['postUserFuncInt.'],
            'type' => 'POSTUSERFUNC',
            'cObj' => serialize($subject),
        ];
        self::assertSame(
            $array,
            $frontend->config['INTincScript'][$substKey]
        );
    }

    /**
     * Check if stdWrap_preCObject works properly.
     *
     * Show:
     *
     * - Delegates to the method cObjGetSingle().
     * - Parameter 1 is $conf['preCObject'].
     * - Parameter 2 is $conf['preCObject.'].
     * - Parameter 3 is '/stdWrap/.preCObject'.
     * - Returns the return value appended by $content.
     */
    #[Test]
    public function stdWrap_preCObject(): void
    {
        $debugKey = '/stdWrap/.preCObject';
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'preCObject' => StringUtility::getUniqueId('preCObject'),
            'preCObject.' => [StringUtility::getUniqueId('preCObject.')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['cObjGetSingle'])->getMock();
        $subject
            ->expects(self::once())
            ->method('cObjGetSingle')
            ->with($conf['preCObject'], $conf['preCObject.'], $debugKey)
            ->willReturn($return);
        self::assertSame(
            $return . $content,
            $subject->stdWrap_preCObject($content, $conf)
        );
    }

    /**
     * Check if stdWrap_preIfEmptyListNum works properly.
     *
     * Show:
     *
     * - Delegates to method listNum.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['preIfEmptyListNum'].
     * - Parameter 3 is $conf['preIfEmptyListNum.']['splitChar'].
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_preIfEmptyListNum(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'preIfEmptyListNum' => StringUtility::getUniqueId('preIfEmptyListNum'),
            'preIfEmptyListNum.' => [
                'splitChar' => StringUtility::getUniqueId('splitChar'),
            ],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['listNum'])->getMock();
        $subject
            ->expects(self::once())
            ->method('listNum')
            ->with(
                $content,
                $conf['preIfEmptyListNum'],
                $conf['preIfEmptyListNum.']['splitChar']
            )
            ->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_preIfEmptyListNum($content, $conf)
        );
    }

    /**
     * Data provider for stdWrap_prefixComment.
     *
     * @return array [$expect, $content, $conf, $disable, $times, $will]
     */
    public static function stdWrap_prefixCommentDataProvider(): array
    {
        $content = StringUtility::getUniqueId('content');
        $will = StringUtility::getUniqueId('will');
        $conf = [];
        $conf['prefixComment'] = StringUtility::getUniqueId('prefixComment');
        $emptyConf1 = [];
        $emptyConf2 = [];
        $emptyConf2['prefixComment'] = '';
        return [
            'standard case' => [$will, $content, $conf, false, 1, $will],
            'emptyConf1' => [$content, $content, $emptyConf1, false, 0, $will],
            'emptyConf2' => [$content, $content, $emptyConf2, false, 0, $will],
            'disabled by bool' => [$content, $content, $conf, true, 0, $will],
            'disabled by int' => [$content, $content, $conf, 1, 0, $will],
        ];
    }

    /**
     * Check that stdWrap_prefixComment works properly.
     *
     * Show:
     *
     *  - Delegates to method prefixComment.
     *  - Parameter 1 is $conf['prefixComment'].
     *  - Parameter 2 is [].
     *  - Parameter 3 is $content.
     *  - Returns the return value.
     *  - Returns $content as is,
     *    - if $conf['prefixComment'] is empty.
     *    - if 'config.disablePrefixComment' is configured by the frontend.
     */
    #[DataProvider('stdWrap_prefixCommentDataProvider')]
    #[Test]
    public function stdWrap_prefixComment(
        string $expect,
        string $content,
        array $conf,
        int|bool $disable,
        int $times,
        string $will
    ): void {
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([
            'disablePrefixComment' => $disable,
        ]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)->onlyMethods(['prefixComment'])->getMock();
        $subject->setRequest($request);
        $subject->expects(self::exactly($times))
            ->method('prefixComment')
            ->with($conf['prefixComment'] ?? null, [], $content)
            ->willReturn($will);
        self::assertSame(
            $expect,
            $subject->stdWrap_prefixComment($content, $conf)
        );
    }

    /**
     * Check if stdWrap_prepend works properly.
     *
     * Show:
     *
     * - Delegates to the method cObjGetSingle().
     * - First parameter is $conf['prepend'].
     * - Second parameter is $conf['prepend.'].
     * - Third parameter is '/stdWrap/.prepend'.
     * - Returns the return value prepended to $content.
     */
    #[Test]
    public function stdWrap_prepend(): void
    {
        $debugKey = '/stdWrap/.prepend';
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'prepend' => StringUtility::getUniqueId('prepend'),
            'prepend.' => [StringUtility::getUniqueId('prepend.')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['cObjGetSingle'])->getMock();
        $subject
            ->expects(self::once())
            ->method('cObjGetSingle')
            ->with($conf['prepend'], $conf['prepend.'], $debugKey)
            ->willReturn($return);
        self::assertSame(
            $return . $content,
            $subject->stdWrap_prepend($content, $conf)
        );
    }

    /**
     * Data provider for stdWrap_prioriCalc
     *
     * @return array [$expect, $content, $conf]
     */
    public static function stdWrap_prioriCalcDataProvider(): array
    {
        return [
            'priority of *' => ['7', '1 + 2 * 3', []],
            'priority of parentheses' => ['9', '(1 + 2) * 3', []],
            'float' => ['1.5', '3/2', []],
            'intval casts to int' => [1, '3/2', ['prioriCalc' => 'intval']],
            'intval does not round' => [2, '2.7', ['prioriCalc' => 'intval']],
        ];
    }

    /**
     * Check if stdWrap_prioriCalc works properly.
     *
     * Show:
     *
     * - If $conf['prioriCalc'] is 'intval' the return is casted to int.
     * - Delegates to MathUtility::calculateWithParentheses.
     *
     * Note: As PHPUnit can't mock static methods, the call to
     *       MathUtility::calculateWithParentheses can't be easily intercepted.
     *       The test is done by testing input/output pairs instead. To not
     *       duplicate the testing of calculateWithParentheses just a few
     *       smoke tests are done here.
     *
     * @param mixed $expect The expected output.
     * @param string $content The given content.
     * @param array $conf The given configuration.
     */
    #[DataProvider('stdWrap_prioriCalcDataProvider')]
    #[Test]
    public function stdWrap_prioriCalc(mixed $expect, string $content, array $conf): void
    {
        $result = $this->subject->stdWrap_prioriCalc($content, $conf);
        self::assertSame($expect, $result);
    }

    /**
     * Check if stdWrap_preUserFunc works properly.
     *
     * Show:
     *
     * - Delegates to method callUserFunction.
     * - Parameter 1 is $conf['preUserFunc'].
     * - Parameter 2 is $conf['preUserFunc.'].
     * - Parameter 3 is $content.
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_preUserFunc(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'preUserFunc' => StringUtility::getUniqueId('preUserFunc'),
            'preUserFunc.' => [StringUtility::getUniqueId('preUserFunc.')],
        ];
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['callUserFunction'])->getMock();
        $subject->expects(self::once())->method('callUserFunction')
            ->with($conf['preUserFunc'], $conf['preUserFunc.'], $content)
            ->willReturn('return');
        self::assertSame(
            'return',
            $subject->stdWrap_preUserFunc($content, $conf)
        );
    }

    /**
     * Data provider for stdWrap_rawUrlEncode
     *
     * @return array [$expect, $content].
     */
    public static function stdWrap_rawUrlEncodeDataProvider(): array
    {
        return [
            'https://typo3.org?id=10' => [
                'https%3A%2F%2Ftypo3.org%3Fid%3D10',
                'https://typo3.org?id=10',
            ],
            'https://typo3.org?id=10&foo=bar' => [
                'https%3A%2F%2Ftypo3.org%3Fid%3D10%26foo%3Dbar',
                'https://typo3.org?id=10&foo=bar',
            ],
        ];
    }

    /**
     * Check if rawUrlEncode works properly.
     *
     * @param string $expect The expected output.
     * @param string $content The given input.
     */
    #[DataProvider('stdWrap_rawUrlEncodeDataProvider')]
    #[Test]
    public function stdWrap_rawUrlEncode(string $expect, string $content): void
    {
        self::assertSame(
            $expect,
            $this->subject->stdWrap_rawUrlEncode($content)
        );
    }

    /**
     * Check if stdWrap_replacement works properly.
     *
     * Show:
     *
     * - Delegates to method replacement.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['replacement.'].
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_replacement(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'replacement' => StringUtility::getUniqueId('not used'),
            'replacement.' => [StringUtility::getUniqueId('replacement.')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['replacement'])->getMock();
        $subject
            ->expects(self::once())
            ->method('replacement')
            ->with($content, $conf['replacement.'])
            ->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_replacement($content, $conf)
        );
    }

    /**
     * Data provider for stdWrap_required.
     *
     * @return array [$expect, $stop, $content]
     */
    public static function stdWrap_requiredDataProvider(): array
    {
        return [
            // empty content
            'empty string is empty' => ['', true, ''],
            'null is empty' => ['', true, null],
            'false is empty' => ['', true, false],

            // non-empty content
            'blank is not empty' => [' ', false, ' '],
            'tab is not empty' => ["\t", false, "\t"],
            'linebreak is not empty' => [PHP_EOL, false, PHP_EOL],
            '"0" is not empty' => ['0', false, '0'],
            '0 is not empty' => [0, false, 0],
            '1 is not empty' => [1, false, 1],
            'true is not empty' => [true, false, true],
        ];
    }

    /**
     * Check if stdWrap_required works properly.
     *
     * Show:
     *
     *  - Content is empty if it equals '' after cast to string.
     *  - Empty content triggers a stop of further rendering.
     *  - Returns the content as is or '' for empty content.
     *
     * @param mixed $expect The expected output.
     * @param bool $stop Expect stop further rendering.
     * @param mixed $content The given input.
     */
    #[DataProvider('stdWrap_requiredDataProvider')]
    #[Test]
    public function stdWrap_required(mixed $expect, bool $stop, mixed $content): void
    {
        $subject = $this->subject;
        $subject->_set('stdWrapRecursionLevel', 1);
        $subject->_set('stopRendering', [1 => false]);
        self::assertSame($expect, $subject->stdWrap_required($content));
        self::assertSame($stop, $subject->_get('stopRendering')[1]);
    }

    /**
     * Check if stdWrap_round works properly
     *
     * Show:
     *
     * - Delegates to method round.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['round.'].
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_round(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'round' => StringUtility::getUniqueId('not used'),
            'round.' => [StringUtility::getUniqueId('round.')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['round'])->getMock();
        $subject
            ->expects(self::once())
            ->method('round')
            ->with($content, $conf['round.'])
            ->willReturn($return);
        self::assertSame($return, $subject->stdWrap_round($content, $conf));
    }

    /**
     * Check if stdWrap_setContentToCurrent works properly.
     */
    #[Test]
    public function stdWrap_setContentToCurrent(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);

        $content = StringUtility::getUniqueId('content');
        self::assertNotSame($content, $this->subject->getData('current'));
        self::assertSame(
            $content,
            $this->subject->stdWrap_setContentToCurrent($content)
        );
        self::assertSame($content, $this->subject->getData('current'));
    }

    /**
     * Data provider for stdWrap_setCurrent
     *
     * @return array Order input, conf
     */
    public static function stdWrap_setCurrentDataProvider(): array
    {
        return [
            'no conf' => [
                'content',
                [],
            ],
            'empty string' => [
                'content',
                ['setCurrent' => ''],
            ],
            'non-empty string' => [
                'content',
                ['setCurrent' => 'xxx'],
            ],
            'integer null' => [
                'content',
                ['setCurrent' => 0],
            ],
            'integer not null' => [
                'content',
                ['setCurrent' => 1],
            ],
            'boolean true' => [
                'content',
                ['setCurrent' => true],
            ],
            'boolean false' => [
                'content',
                ['setCurrent' => false],
            ],
        ];
    }

    /**
     * Check if stdWrap_setCurrent works properly.
     *
     * @param string $input The input value.
     * @param array $conf Property: setCurrent
     */
    #[DataProvider('stdWrap_setCurrentDataProvider')]
    #[Test]
    public function stdWrap_setCurrent(string $input, array $conf): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $this->subject->setRequest($request);

        if (isset($conf['setCurrent'])) {
            self::assertNotSame($conf['setCurrent'], $this->subject->getData('current'));
        }
        self::assertSame($input, $this->subject->stdWrap_setCurrent($input, $conf));
        if (isset($conf['setCurrent'])) {
            self::assertSame($conf['setCurrent'], $this->subject->getData('current'));
        }
    }

    /**
     * Check if stdWrap_split works properly.
     *
     * Show:
     *
     * - Delegates to method splitObj.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['split.'].
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_split(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'split' => StringUtility::getUniqueId('not used'),
            'split.' => [StringUtility::getUniqueId('split.')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['splitObj'])->getMock();
        $subject
            ->expects(self::once())
            ->method('splitObj')
            ->with($content, $conf['split.'])
            ->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_split($content, $conf)
        );
    }

    /**
     * Check that stdWrap_stdWrap works properly.
     *
     * Show:
     *  - Delegates to method stdWrap.
     *  - Parameter 1 is $content.
     *  - Parameter 2 is $conf['stdWrap.'].
     *  - Returns the return value.
     */
    #[Test]
    public function stdWrap_stdWrap(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'stdWrap' => StringUtility::getUniqueId('not used'),
            'stdWrap.' => [StringUtility::getUniqueId('stdWrap.')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['stdWrap'])->getMock();
        $subject
            ->expects(self::once())
            ->method('stdWrap')
            ->with($content, $conf['stdWrap.'])
            ->willReturn($return);
        self::assertSame($return, $subject->stdWrap_stdWrap($content, $conf));
    }

    /**
     * Data provider for stdWrap_stdWrapValue test
     */
    public static function stdWrap_stdWrapValueDataProvider(): array
    {
        return [
            'only key returns value' => [
                'ifNull',
                [
                    'ifNull' => '1',
                ],
                '',
                '1',
            ],
            'array without key returns empty string' => [
                'ifNull',
                [
                    'ifNull.' => '1',
                ],
                '',
                '',
            ],
            'array without key returns default' => [
                'ifNull',
                [
                    'ifNull.' => '1',
                ],
                'default',
                'default',
            ],
            'non existing key returns default' => [
                'ifNull',
                [
                    'noTrimWrap' => 'test',
                    'noTrimWrap.' => '1',
                ],
                'default',
                'default',
            ],
            'default value null is returned' => [
                'ifNull',
                [],
                null,
                null,
            ],
            'existing key and array returns stdWrap' => [
                'test',
                [
                    'test' => 'value',
                    'test.' => ['case' => 'upper'],
                ],
                'default',
                'VALUE',
            ],
            'the string "0" from stdWrap will be returned' => [
                'test',
                [
                    'test' => '',
                    'test.' => [
                        'wrap' => '|0',
                    ],
                ],
                '100',
                '0',
            ],
        ];
    }

    #[DataProvider('stdWrap_stdWrapValueDataProvider')]
    #[Test]
    public function stdWrap_stdWrapValue(
        string $key,
        array $configuration,
        ?string $defaultValue,
        ?string $expected
    ): void {
        $result = $this->subject->stdWrapValue($key, $configuration, $defaultValue);
        self::assertSame($expected, $result);
    }

    /**
     * Data provider for stdWrap_strPad.
     *
     * @return array [$expect, $content, $conf]
     */
    public static function stdWrap_strPadDataProvider(): array
    {
        return [
            'pad string with default settings and length 10' => [
                'Alien     ',
                'Alien',
                [
                    'length' => '10',
                ],
            ],
            'pad string with default settings and length 10 and multibyte character' => [
                'Älien     ',
                'Älien',
                [
                    'length' => '10',
                ],
            ],
            'pad string with padWith -= and type left and length 10' => [
                '-=-=-Alien',
                'Alien',
                [
                    'length' => '10',
                    'padWith' => '-=',
                    'type' => 'left',
                ],
            ],
            'pad string with padWith äö and type left and length 10 and multibyte characters' => [
                'äöäöäÄlien',
                'Älien',
                [
                    'length' => '10',
                    'padWith' => 'äö',
                    'type' => 'left',
                ],
            ],
            'pad string with padWith _ and type both and length 10' => [
                '__Alien___',
                'Alien',
                [
                    'length' => '10',
                    'padWith' => '_',
                    'type' => 'both',
                ],
            ],
            'pad string with padWith 0 and type both and length 10' => [
                '00Alien000',
                'Alien',
                [
                    'length' => '10',
                    'padWith' => '0',
                    'type' => 'both',
                ],
            ],
            'pad string with padWith ___ and type both and length 6' => [
                'Alien_',
                'Alien',
                [
                    'length' => '6',
                    'padWith' => '___',
                    'type' => 'both',
                ],
            ],
            'pad string with padWith _ and type both and length 12, using stdWrap for length' => [
                '___Alien____',
                'Alien',
                [
                    'length' => '1',
                    'length.' => [
                        'wrap' => '|2',
                    ],
                    'padWith' => '_',
                    'type' => 'both',
                ],
            ],
            'pad string with padWith _ and type both and length 12, using stdWrap for padWidth' => [
                '-_=Alien-_=-',
                'Alien',
                [
                    'length' => '12',
                    'padWith' => '_',
                    'padWith.' => [
                        'wrap' => '-|=',
                    ],
                    'type' => 'both',
                ],
            ],
            'pad string with padWith _ and type both and length 12, using stdWrap for type' => [
                '_______Alien',
                'Alien',
                [
                    'length' => '12',
                    'padWith' => '_',
                    'type' => 'both',
                    // make type become "left"
                    'type.' => [
                        'substring' => '2,1',
                        'wrap' => 'lef|',
                    ],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_strPad works properly.
     *
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @param array $conf The configuration of 'strPad.'.
     */
    #[DataProvider('stdWrap_strPadDataProvider')]
    #[Test]
    public function stdWrap_strPad(string $expect, string $content, array $conf): void
    {
        $conf = ['strPad.' => $conf];
        $result = $this->subject->stdWrap_strPad($content, $conf);
        self::assertSame($expect, $result);
    }

    /**
     * Data provider for stdWrap_strftime
     *
     * @return array [$expect, $content, $conf, $now]
     */
    public static function stdWrap_strftimeDataProvider(): array
    {
        // Fictive execution time is 2012-09-01 12:00 in UTC/GMT.
        $now = 1346500800;
        return [
            'given timestamp' => [
                '01-09-2012',
                $now,
                ['strftime' => '%d-%m-%Y'],
                $now,
            ],
            'empty string' => [
                '01-09-2012',
                '',
                ['strftime' => '%d-%m-%Y'],
                $now,
            ],
            'testing null' => [
                '01-09-2012',
                null,
                ['strftime' => '%d-%m-%Y'],
                $now,
            ],
        ];
    }

    /**
     * Check if stdWrap_strftime works properly.
     *
     * @param string $expect The expected output.
     * @param mixed $content The given input.
     * @param array $conf The given configuration.
     * @param int $now Fictive execution time.
     */
    #[DataProvider('stdWrap_strftimeDataProvider')]
    #[Test]
    public function stdWrap_strftime(string $expect, mixed $content, array $conf, int $now): void
    {
        // Save current timezone and set to UTC to make the system under test
        // behave the same in all server timezone settings
        $timezoneBackup = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $GLOBALS['EXEC_TIME'] = $now;
        $result = $this->subject->stdWrap_strftime($content, $conf);

        // Reset timezone
        date_default_timezone_set($timezoneBackup);

        self::assertSame($expect, $result);
    }

    /**
     * Test for the stdWrap_stripHtml
     */
    #[Test]
    public function stdWrap_stripHtml(): void
    {
        $content = '<html><p>Hello <span class="inline">inline tag<span>!</p><p>Hello!</p></html>';
        $expected = 'Hello inline tag!Hello!';
        self::assertSame($expected, $this->subject->stdWrap_stripHtml($content));
    }

    /**
     * Data provider for the stdWrap_strtotime test
     *
     * @return array [$expect, $content, $conf]
     */
    public static function stdWrap_strtotimeDataProvider(): array
    {
        return [
            'date from content' => [
                1417651200,
                '2014-12-04',
                ['strtotime' => '1'],
            ],
            'manipulation of date from content' => [
                1417996800,
                '2014-12-04',
                ['strtotime' => '+ 2 weekdays'],
            ],
            'date from configuration' => [
                1417651200,
                '',
                ['strtotime' => '2014-12-04'],
            ],
            'manipulation of date from configuration' => [
                1417996800,
                '',
                ['strtotime' => '2014-12-04 + 2 weekdays'],
            ],
            'empty input' => [
                false,
                '',
                ['strtotime' => '1'],
            ],
            'date from content and configuration' => [
                false,
                '2014-12-04',
                ['strtotime' => '2014-12-05'],
            ],
        ];
    }

    /**
     * Check if stdWrap_strtotime works properly.
     *
     * @param mixed $expect The expected output.
     * @param string $content The given input.
     * @param array $conf The given configuration.
     */
    #[DataProvider('stdWrap_strtotimeDataProvider')]
    #[Test]
    public function stdWrap_strtotime(mixed $expect, string $content, array $conf): void
    {
        // Set exec_time to a hard timestamp
        $GLOBALS['EXEC_TIME'] = 1417392000;
        // Save current timezone and set to UTC to make the system under test
        // behave the same in all server timezone settings
        $timezoneBackup = date_default_timezone_get();
        date_default_timezone_set('UTC');

        $result = $this->subject->stdWrap_strtotime($content, $conf);

        // Reset timezone
        date_default_timezone_set($timezoneBackup);

        self::assertEquals($expect, $result);
    }

    /**
     * Check if stdWrap_substring works properly.
     *
     * Show:
     *
     * - Delegates to method substring.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['substring'].
     * - Returns the return value.
     */
    #[Test]
    public function stdWrap_substring(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'substring' => StringUtility::getUniqueId('substring'),
            'substring.' => StringUtility::getUniqueId('not used'),
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['substring'])->getMock();
        $subject
            ->expects(self::once())
            ->method('substring')
            ->with($content, $conf['substring'])
            ->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_substring($content, $conf)
        );
    }

    /**
     * Data provider for stdWrap_trim.
     *
     * @return array [$expect, $content]
     */
    public static function stdWrap_trimDataProvider(): array
    {
        return [
            // string not trimmed
            'empty string' => ['', ''],
            'string without whitespace' => ['xxx', 'xxx'],
            'string with whitespace inside' => [
                'xx ' . "\t" . ' xx',
                'xx ' . "\t" . ' xx',
            ],
            'string with newlines inside' => [
                'xx ' . PHP_EOL . ' xx',
                'xx ' . PHP_EOL . ' xx',
            ],
            // string trimmed
            'blanks around' => ['xxx', '  xxx  '],
            'tabs around' => ['xxx', "\t" . 'xxx' . "\t"],
            'newlines around' => ['xxx', PHP_EOL . 'xxx' . PHP_EOL],
            'mixed case' => ['xxx', "\t" . ' xxx ' . PHP_EOL],
            // non strings
            'null' => ['', null],
            'false' => ['', false],
            'true' => ['1', true],
            'zero' => ['0', 0],
            'one' => ['1', 1],
            '-1' => ['-1', -1],
            '0.0' => ['0', 0.0],
            '1.0' => ['1', 1.0],
            '-1.0' => ['-1', -1.0],
            '1.1' => ['1.1', 1.1],
        ];
    }

    /**
     * Check that stdWrap_trim works properly.
     *
     * Show:
     *
     *  - the given string is trimmed like PHP trim
     *  - non-strings are casted to strings:
     *    - null => 'null'
     *    - false => ''
     *    - true => '1'
     *    - 0 => '0'
     *    - -1 => '-1'
     *    - 1.0 => '1'
     *    - 1.1 => '1.1'
     */
    #[DataProvider('stdWrap_trimDataProvider')]
    #[Test]
    public function stdWrap_trim(string $expect, mixed $content): void
    {
        $result = $this->subject->stdWrap_trim($content);
        self::assertSame($expect, $result);
    }

    /**
     * Check that stdWrap_typolink works properly.
     *
     * Show:
     *  - Delegates to method typolink.
     *  - Parameter 1 is $content.
     *  - Parameter 2 is $conf['typolink.'].
     *  - Returns the return value.
     */
    #[Test]
    public function stdWrap_typolink(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'typolink' => StringUtility::getUniqueId('not used'),
            'typolink.' => [StringUtility::getUniqueId('typolink.')],
        ];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['typolink'])->getMock();
        $subject
            ->expects(self::once())
            ->method('typolink')
            ->with($content, $conf['typolink.'])
            ->willReturn($return);
        self::assertSame($return, $subject->stdWrap_typolink($content, $conf));
    }

    /**
     * Data provider for stdWrap_wrap
     *
     * @return array Order expected, input, conf
     */
    public static function stdWrap_wrapDataProvider(): array
    {
        return [
            'no conf' => [
                'XXX',
                'XXX',
                [],
            ],
            'simple' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                ['wrap' => '<wrapper>|</wrapper>'],
            ],
            'trims whitespace' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                ['wrap' => '<wrapper>' . "\t" . ' | ' . "\t" . '</wrapper>'],
            ],
            'missing pipe puts wrap before' => [
                '<pre>XXX',
                'XXX',
                [
                    'wrap' => '<pre>',
                ],
            ],
            'split char change' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                [
                    'wrap' => '<wrapper> # </wrapper>',
                    'wrap.' => ['splitChar' => '#'],
                ],
            ],
            'split by pattern' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                [
                    'wrap' => '<wrapper> ###splitter### </wrapper>',
                    'wrap.' => ['splitChar' => '###splitter###'],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_wrap works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Properties: wrap, wrap.splitChar
     */
    #[DataProvider('stdWrap_wrapDataProvider')]
    #[Test]
    public function stdWrap_wrap(string $expected, string $input, array $conf): void
    {
        self::assertSame(
            $expected,
            $this->subject->stdWrap_wrap($input, $conf)
        );
    }

    /**
     * Data provider for stdWrap_wrap2
     *
     * @return array Order expected, input, conf
     */
    public static function stdWrap_wrap2DataProvider(): array
    {
        return [
            'no conf' => [
                'XXX',
                'XXX',
                [],
            ],
            'simple' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                ['wrap2' => '<wrapper>|</wrapper>'],
            ],
            'trims whitespace' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                ['wrap2' => '<wrapper>' . "\t" . ' | ' . "\t" . '</wrapper>'],
            ],
            'missing pipe puts wrap2 before' => [
                '<pre>XXX',
                'XXX',
                [
                    'wrap2' => '<pre>',
                ],
            ],
            'split char change' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                [
                    'wrap2' => '<wrapper> # </wrapper>',
                    'wrap2.' => ['splitChar' => '#'],
                ],
            ],
            'split by pattern' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                [
                    'wrap2' => '<wrapper> ###splitter### </wrapper>',
                    'wrap2.' => ['splitChar' => '###splitter###'],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_wrap2 works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Properties: wrap2, wrap2.splitChar
     */
    #[DataProvider('stdWrap_wrap2DataProvider')]
    #[Test]
    public function stdWrap_wrap2(string $expected, string $input, array $conf): void
    {
        self::assertSame($expected, $this->subject->stdWrap_wrap2($input, $conf));
    }

    /**
     * Data provider for stdWrap_wrap3
     *
     * @return array Order expected, input, conf
     */
    public static function stdWrap_wrap3DataProvider(): array
    {
        return [
            'no conf' => [
                'XXX',
                'XXX',
                [],
            ],
            'simple' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                ['wrap3' => '<wrapper>|</wrapper>'],
            ],
            'trims whitespace' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                ['wrap3' => '<wrapper>' . "\t" . ' | ' . "\t" . '</wrapper>'],
            ],
            'missing pipe puts wrap3 before' => [
                '<pre>XXX',
                'XXX',
                [
                    'wrap3' => '<pre>',
                ],
            ],
            'split char change' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                [
                    'wrap3' => '<wrapper> # </wrapper>',
                    'wrap3.' => ['splitChar' => '#'],
                ],
            ],
            'split by pattern' => [
                '<wrapper>XXX</wrapper>',
                'XXX',
                [
                    'wrap3' => '<wrapper> ###splitter### </wrapper>',
                    'wrap3.' => ['splitChar' => '###splitter###'],
                ],
            ],
        ];
    }

    /**
     * Check if stdWrap_wrap3 works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Properties: wrap3, wrap3.splitChar
     */
    #[DataProvider('stdWrap_wrap3DataProvider')]
    #[Test]
    public function stdWrap_wrap3(string $expected, string $input, array $conf): void
    {
        self::assertSame($expected, $this->subject->stdWrap_wrap3($input, $conf));
    }

    /**
     * Data provider for stdWrap_wrapAlign.
     *
     * @return array [$expect, $content, $conf]
     */
    public static function stdWrap_wrapAlignDataProvider(): array
    {
        $format = '<div style="text-align:%s;">%s</div>';
        $content = StringUtility::getUniqueId('content');
        $wrapAlign = StringUtility::getUniqueId('wrapAlign');
        $expect = sprintf($format, $wrapAlign, $content);
        return [
            'standard case' => [$expect, $content, $wrapAlign],
            'empty conf' => [$content, $content, null],
            'empty string' => [$content, $content, ''],
            'whitespaced zero string' => [$content, $content, ' 0 '],
        ];
    }

    /**
     * Check if stdWrap_wrapAlign works properly.
     *
     * Show:
     *
     * - Wraps $content with div and style attribute.
     * - The style attribute is taken from $conf['wrapAlign'].
     * - Returns the content as is,
     * - if $conf['wrapAlign'] evals to false after being trimmed.
     *
     * @param string $expect The expected output.
     * @param string $content The given content.
     * @param mixed $wrapAlignConf The given input.
     */
    #[DataProvider('stdWrap_wrapAlignDataProvider')]
    #[Test]
    public function stdWrap_wrapAlign(string $expect, string $content, mixed $wrapAlignConf): void
    {
        $conf = [];
        if ($wrapAlignConf !== null) {
            $conf['wrapAlign'] = $wrapAlignConf;
        }
        self::assertSame(
            $expect,
            $this->subject->stdWrap_wrapAlign($content, $conf)
        );
    }

    /***************************************************************************
     * End of tests of stdWrap in alphabetical order
     ***************************************************************************/
    /***************************************************************************
     * Begin: Mixed tests
     *
     * - Add new tests here that still don't have a better place in this class.
     * - Place tests in alphabetical order.
     * - Place data provider above test method.
     ***************************************************************************/
    /**
     * Check if getCurrentTable works properly.
     */
    #[Test]
    public function getCurrentTable(): void
    {
        self::assertEquals('tt_content', $this->subject->getCurrentTable());
    }

    /**
     * Data provider for prefixComment.
     *
     * @return array [$expect, $comment, $content]
     */
    public static function prefixCommentDataProvider(): array
    {
        $comment = StringUtility::getUniqueId();
        $content = StringUtility::getUniqueId();
        $format = '%s';
        $format .= '%%s<!-- %%s [begin] -->%s';
        $format .= '%%s%s%%s%s';
        $format .= '%%s<!-- %%s [end] -->%s';
        $format .= '%%s%s';
        $format = sprintf($format, LF, LF, "\t", LF, LF, "\t");
        $indent1 = "\t";
        $indent2 = "\t" . "\t";
        return [
            'indent one tab' => [
                sprintf(
                    $format,
                    $indent1,
                    $comment,
                    $indent1,
                    $content,
                    $indent1,
                    $comment,
                    $indent1
                ),
                '1|' . $comment,
                $content,
            ],
            'indent two tabs' => [
                sprintf(
                    $format,
                    $indent2,
                    $comment,
                    $indent2,
                    $content,
                    $indent2,
                    $comment,
                    $indent2
                ),
                '2|' . $comment,
                $content,
            ],
            'htmlspecialchars applies for comment only' => [
                sprintf(
                    $format,
                    $indent1,
                    '&lt;' . $comment . '&gt;',
                    $indent1,
                    '<' . $content . '>',
                    $indent1,
                    '&lt;' . $comment . '&gt;',
                    $indent1
                ),
                '1|<' . $comment . '>',
                '<' . $content . '>',
            ],
        ];
    }

    /**
     * Check if prefixComment works properly.
     *
     * @param string $expect The expected output.
     * @param string $comment The parameter $comment.
     * @param string $content The parameter $content.
     */
    #[DataProvider('prefixCommentDataProvider')]
    #[Test]
    public function prefixComment(string $expect, string $comment, string $content): void
    {
        // The parameter $conf is never used. Just provide null.
        // Consider to improve the signature and deprecate the old one.
        $result = $this->subject->prefixComment($comment, null, $content);
        self::assertEquals($expect, $result);
    }

    /**
     * Check setter and getter of currentFile work properly.
     */
    #[Test]
    public function setCurrentFile_getCurrentFile(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $file = new File(['testfile'], $storageMock);
        $this->subject->setCurrentFile($file);
        self::assertSame($file, $this->subject->getCurrentFile());
    }

    /**
     * Check setter and getter of currentVal work properly.
     *
     * Show it stored to $this->data[$this->currentValKey].
     * (The default value of currentValKey is tested elsewhere.)
     *
     * @see stdWrap_current()
     */
    #[Test]
    public function setCurrentVal_getCurrentVal(): void
    {
        $key = StringUtility::getUniqueId();
        $value = StringUtility::getUniqueId();
        $this->subject->currentValKey = $key;
        $this->subject->setCurrentVal($value);
        self::assertEquals($value, $this->subject->getCurrentVal());
        self::assertEquals($value, $this->subject->data[$key]);
    }

    /**
     * Check setter and getter of userObjectType work properly.
     */
    #[Test]
    public function setUserObjectType_getUserObjectType(): void
    {
        $value = StringUtility::getUniqueId();
        $this->subject->setUserObjectType($value);
        self::assertEquals($value, $this->subject->getUserObjectType());
    }

    public static function getGlobalDataProvider(): array
    {
        return [
            'simple' => [
                'foo',
                'HTTP_SERVER_VARS | something',
                [
                    'HTTP_SERVER_VARS' => [
                        'something' => 'foo',
                    ],
                ],
                null,
            ],
            'simple source fallback' => [
                'foo',
                'HTTP_SERVER_VARS | something',
                null,
                [
                    'HTTP_SERVER_VARS' => [
                        'something' => 'foo',
                    ],
                ],
            ],
            'globals ignored if source given' => [
                '',
                'HTTP_SERVER_VARS | something',
                [
                    'HTTP_SERVER_VARS' => [
                        'something' => 'foo',
                    ],
                ],
                [
                    'HTTP_SERVER_VARS' => [
                        'something-else' => 'foo',
                    ],
                ],
            ],
            'sub array is returned as empty string' => [
                '',
                'HTTP_SERVER_VARS | something',
                [
                    'HTTP_SERVER_VARS' => [
                        'something' => ['foo'],
                    ],
                ],
                null,
            ],
            'does not exist' => [
                '',
                'HTTP_SERVER_VARS | something',
                [
                    'HTTP_SERVER_VARS' => [
                        'something-else' => 'foo',
                    ],
                ],
                null,
            ],
            'does not exist in source' => [
                '',
                'HTTP_SERVER_VARS | something',
                null,
                [
                    'HTTP_SERVER_VARS' => [
                        'something-else' => 'foo',
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('getGlobalDataProvider')]
    #[Test]
    public function getGlobalReturnsExpectedResult(mixed $expected, string $key, ?array $globals, ?array $source): void
    {
        if (isset($globals['HTTP_SERVER_VARS'])) {
            // Note we can't simply $GLOBALS = $globals, since phpunit backupGlobals works on existing array keys.
            $GLOBALS['HTTP_SERVER_VARS'] = $globals['HTTP_SERVER_VARS'];
        }
        self::assertSame(
            $expected,
            (new ContentObjectRenderer())->getGlobal($key, $source)
        );
    }

    /***************************************************************************
     * End: Mixed tests
     ***************************************************************************/

    private function createContentObjectFactoryMock()
    {
        return new class (new Container()) extends ContentObjectFactory {
            /**
             * @var array<string, callable>
             */
            private array $getContentObjectCallbacks = [];

            public function getContentObject(
                string $name,
                ServerRequestInterface $request,
                ContentObjectRenderer $contentObjectRenderer
            ): ?AbstractContentObject {
                if (is_callable($this->getContentObjectCallbacks[$name] ?? null)) {
                    return $this->getContentObjectCallbacks[$name]();
                }
                return null;
            }

            /**
             * @internal This method is just for testing purpose.
             */
            public function addGetContentObjectCallback(string $name, string $className, ServerRequestInterface $request, ContentObjectRenderer $cObj): void
            {
                $this->getContentObjectCallbacks[$name] = static function () use ($className, $request, $cObj) {
                    $contentObject = new $className();
                    $contentObject->setRequest($request);
                    $contentObject->setContentObjectRenderer($cObj);
                    return $contentObject;
                };
            }
        };
    }

    #[Test]
    public function mergeTSRefResolvesRecursive(): void
    {
        $typoScriptString =
            "lib.foo = TEXT\n" .
            "lib.foo.value = foo\n" .
            "lib.bar =< lib.foo\n" .
            "lib.bar.value = bar\n";
        $lineStream = (new LossyTokenizer())->tokenize($typoScriptString);
        $typoScriptAst = (new AstBuilder(new NoopEventDispatcher()))->build($lineStream, new RootNode());
        $typoScriptAttribute = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScriptAttribute->setSetupTree($typoScriptAst);
        $typoScriptAttribute->setSetupArray($typoScriptAst->toArray());
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScriptAttribute);
        $contentObjectRenderer = new ContentObjectRenderer();
        $contentObjectRenderer->setRequest($request);
        $inputArray = [
            'tempKey' => '< lib.bar',
            'tempKey.' => [],
        ];
        $expected = [
            'tempKey' => 'TEXT', // From lib.foo
            'tempKey.' => [
                'value' => 'bar', // From lib.bar
            ],
        ];
        $result = $contentObjectRenderer->mergeTSRef($inputArray, 'tempKey');
        self::assertSame($expected, $result);
    }

    public static function listNumDataProvider(): array
    {
        return [
            'Numeric non-zero $listNum' => [
                'expected' => 'foo',
                'content' => 'hello,foo,bar,',
                'listNum' => '1',
                'delimeter' => ',',
            ],
            'Numeric non-zero $listNum, without passing delimeter' => [
                'expected' => 'foo',
                'content' => 'hello,foo,bar',
                'listNum' => '1',
                'delimeter' => '',
            ],
            '$listNum = last' => [
                'expected' => 'bar',
                'content' => 'hello,foo,bar',
                'listNum' => 'last',
                'delimeter' => ',',
            ],
            '$listNum arithmetic' => [
                'expected' => 'foo',
                'content' => 'hello,foo,bar',
                'listNum' => '3-2',
                'delimeter' => ',',
            ],
        ];

    }

    #[DataProvider('listNumDataProvider')]
    #[Test]
    public function listNum(string $expected, string $content, string $listNum, string $delimeter): void
    {
        $contentObjectRenderer = new ContentObjectRenderer();
        self::assertEquals($expected, $contentObjectRenderer->listNum($content, $listNum, $delimeter));
    }

    #[Test]
    public function listNumWithListNumRandReturnsString(): void
    {
        $contentObjectRenderer = new ContentObjectRenderer();
        $result = $contentObjectRenderer->listNum('hello,foo,bar', 'rand', ',');
        self::assertTrue($result === 'hello' || $result === 'foo' || $result === 'bar');
    }
}
