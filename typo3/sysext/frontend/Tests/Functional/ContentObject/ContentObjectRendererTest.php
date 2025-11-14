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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Platform\PlatformHelper;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\ExpressionLanguage\DefaultProvider;
use TYPO3\CMS\Core\ExpressionLanguage\ProviderConfigurationLoader;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LossyTokenizer;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterContentObjectRendererInitializedEvent;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterGetDataResolvedEvent;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterImageResourceResolvedEvent;
use TYPO3\CMS\Frontend\ContentObject\Event\EnhanceStdWrapEvent;
use TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler;
use TYPO3\CMS\Frontend\ContentObject\RegisterStack;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Fixtures\TestSanitizerBuilder;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\LinkResult;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ContentObjectRendererTest extends FunctionalTestCase
{
    protected array $pathsToProvideInTestInstance = ['typo3/sysext/frontend/Tests/Functional/Fixtures/Images' => 'fileadmin/user_upload'];

    private function getPreparedRequest(): ServerRequestInterface
    {
        $request = new ServerRequest('http://example.com/en/', 'GET', null, [], ['HTTP_HOST' => 'example.com', 'REQUEST_URI' => '/en/']);
        return $request->withQueryParams(['id' => 1])->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
    }

    private function createContentObjectThrowingExceptionFixture(ContentObjectRenderer $subject, bool $addProductionExceptionHandlerInstance = true): AbstractContentObject&MockObject
    {
        $contentObjectFixture = $this->getMockBuilder(AbstractContentObject::class)->getMock();
        $contentObjectFixture->expects($this->once())
            ->method('render')
            ->willReturnCallback(static function (array $_ = []): string {
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

    #[Test]
    public function stdWrap(): void
    {
        $configuration = [
            'prioriCalc.' => [
                'wrap' => '|',
            ],
        ];
        self::assertSame('1+1', $this->get(ContentObjectRenderer::class)->stdWrap('1+1', $configuration));
    }

    #[Test]
    public function stdWrapWithRecursiveConfigRendersBasicString(): void
    {
        $stdWrapConfiguration = [
            'noTrimWrap' => '|| 123|',
            'stdWrap.' => [
                'wrap' => '<b>|</b>',
            ],
        ];
        self::assertSame('<b>Test</b> 123', $this->get(ContentObjectRenderer::class)->stdWrap('Test', $stdWrapConfiguration));
    }

    #[Test]
    public function stdWrapWithRecursiveCallsObjectOnlyCalledOnce(): void
    {
        $request = new ServerRequest();
        $request = $request->withAttribute('frontend.register.stack', new RegisterStack());
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
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertSame('Counter:1', $subject->stdWrap('Counter:', $stdWrapConfiguration));
    }

    public static function stdWrap_replacementDataProvider(): array
    {
        return [
            'multiple replacements, including regex' => [
                'There is an animal, an animal and an animal around the block! Yeah!',
                'There_is_a_cat,_a_dog_and_a_tiger_in_da_hood!_Yeah!',
                [
                    'replacement' => 'not used',
                    'replacement.' => [
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
            ],
            'replacement with optionSplit, normal pattern' => [
                'There1is2a3cat,3a3dog3and3a3tiger3in3da3hood!3Yeah!',
                'There_is_a_cat,_a_dog_and_a_tiger_in_da_hood!_Yeah!',
                [
                    'replacement.' => [
                        '10.' => [
                            'search' => '_',
                            'replace' => '1 || 2 || 3',
                            'useOptionSplitReplace' => '1',
                            'useRegExp' => '0',
                        ],
                    ],
                ],
            ],
            'replacement with optionSplit, using regex' => [
                'There is a tiny cat, a midsized dog and a big tiger in da hood! Yeah!',
                'There is a cat, a dog and a tiger in da hood! Yeah!',
                [
                    'replacement.' => [
                        '10.' => [
                            'search' => '#(a) (Cat|Dog|Tiger)#i',
                            'replace' => '${1} tiny ${2} || ${1} midsized ${2} || ${1} big ${2}',
                            'useOptionSplitReplace' => '1',
                            'useRegExp' => '1',
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('stdWrap_replacementDataProvider')]
    #[Test]
    public function stdWrap_replacement(string $expected, string $content, array $conf): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_replacement($content, $conf));
    }

    public static function stdWrap_roundDataProvider(): array
    {
        return [
            // floats
            'down' => [1.0, 1.11, []],
            'up' => [2.0, 1.51, []],
            'rounds up from x.50' => [2.0, 1.50, []],
            'down with decimals' => [0.12, 0.1231, ['round.' => ['decimals' => 2]]],
            'up with decimals' => [0.13, 0.1251, ['round.' => ['decimals' => 2]]],
            'ceil' => [1.0, 0.11, ['round.' => ['roundType' => 'ceil']]],
            'ceil does not accept decimals' => [
                1.0,
                0.111,
                [
                    'round.' => [
                        'roundType' => 'ceil',
                        'decimals' => 2,
                    ],
                ],
            ],
            'floor' => [2.0, 2.99, ['round.' => ['roundType' => 'floor']]],
            'floor does not accept decimals' => [
                2.0,
                2.999,
                [
                    'round.' => [
                        'roundType' => 'floor',
                        'decimals' => 2,
                    ],
                ],
            ],
            'round, down' => [1.0, 1.11, ['round.' => ['roundType' => 'round']]],
            'round, up' => [2.0, 1.55, ['round.' => ['roundType' => 'round']]],
            'round does accept decimals' => [
                5.56,
                5.5555,
                [
                    'round.' => [
                        'roundType' => 'round',
                        'decimals' => 2,
                    ],
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

    #[DataProvider('stdWrap_roundDataProvider')]
    #[Test]
    public function stdWrap_round(float $expected, mixed $content, array $conf): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_round($content, $conf));
    }

    public static function stdWrap_substringDataProvider(): array
    {
        return [
            'no config' => ['substring', 'substring', []],
            'sub -1' => ['g', 'substring', ['substring' => '-1', 'substring.' => 'unused']],
            'sub -1,0' => ['g', 'substring', ['substring' => '-1,0']],
            'sub -1,-1' => ['', 'substring', ['substring' => '-1,-1']],
            'sub -1,1' => ['g', 'substring', ['substring' => '-1,1']],
            'sub 0' => ['substring', 'substring', ['substring' => '0']],
            'sub 0,0' => ['substring', 'substring', ['substring' => '0,0']],
            'sub 0,-1' => ['substrin', 'substring', ['substring' => '0,-1']],
            'sub 0,1' => ['s', 'substring', ['substring' => '0,1']],
            'sub 1' => ['ubstring', 'substring', ['substring' => '1']],
            'sub 1,0' => ['ubstring', 'substring', ['substring' => '1,0']],
            'sub 1,-1' => ['ubstrin', 'substring', ['substring' => '1,-1']],
            'sub 1,1' => ['u', 'substring', ['substring' => '1,1']],
            'sub' => ['substring', 'substring', ['substring' => '']],
        ];
    }

    #[DataProvider('stdWrap_substringDataProvider')]
    #[Test]
    public function stdWrap_substring(string $expected, string $content, array $conf): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_substring($content, $conf));
    }

    public static function stdWrap_parseFuncReturnsCorrectHtmlDataProvider(): array
    {
        $defaultParseFuncRteConfig = [
            'parseFunc' => '',
            'parseFunc.' => [
                'allowTags' => 'a, abbr, acronym, address, article, aside, b, bdo, big, blockquote, br, caption, center, cite,'
                    . ' code, col, colgroup, dd, del, dfn, dl, div, dt, em, font, footer, header, h1, h2, h3, h4, h5, h6, hr,'
                    . ' i, img, ins, kbd, label, li, link, meta, nav, ol, p, pre, q, samp, sdfield, section, small, span, strike,'
                    . ' strong, style, sub, sup, table, thead, tbody, tfoot, td, th, tr, title, tt, u, ul, var',
                'constants' => '1',
                'denyTags' => '*',
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
        return [
            'Text without tag is wrapped with <p> tag' => [
                'Text without tag',
                $defaultParseFuncRteConfig,
                '<p class="bodytext">Text without tag</p>',
            ],
            'Text wrapped with <p> tag remains the same' => [
                '<p class="myclass">Text with &lt;p&gt; tag</p>',
                $defaultParseFuncRteConfig,
                '<p class="myclass">Text with &lt;p&gt; tag</p>',
            ],
            'Text with absolute external link' => [
                'Text with <link http://example.com/foo/>external link</link>',
                $defaultParseFuncRteConfig,
                '<p class="bodytext">Text with <a href="http://example.com/foo/">external link</a></p>',
            ],
            'Empty lines are not duplicated' => [
                chr(10),
                $defaultParseFuncRteConfig,
                '<p class="bodytext">&nbsp;</p>',
            ],
            'Multiple empty lines with no text' => [
                chr(10) . chr(10) . chr(10),
                $defaultParseFuncRteConfig,
                '<p class="bodytext">&nbsp;</p>' . chr(10) . '<p class="bodytext">&nbsp;</p>' . chr(10) . '<p class="bodytext">&nbsp;</p>',
            ],
            'Empty lines are not duplicated at the end of content' => [
                'test' . chr(10) . chr(10),
                $defaultParseFuncRteConfig,
                '<p class="bodytext">test</p>' . chr(10) . '<p class="bodytext">&nbsp;</p>',
            ],
            'Empty lines are not trimmed' => [
                chr(10) . 'test' . chr(10),
                $defaultParseFuncRteConfig,
                '<p class="bodytext">&nbsp;</p>' . chr(10) . '<p class="bodytext">test</p>' . chr(10) . '<p class="bodytext">&nbsp;</p>',
            ],
            // @todo: documenting the current behavior of allowTags/denyTags=*
            //        probably denyTags should take precedence, which might be breaking
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
            ],
        ];
    }

    #[DataProvider('stdWrap_parseFuncReturnsCorrectHtmlDataProvider')]
    #[Test]
    public function stdWrap_parseFuncReturnsParsedHtml(string $value, array $configuration, string $expectedResult): void
    {
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals($expectedResult, $subject->stdWrap_parseFunc($value, $configuration));
    }

    public static function stdWrap_parseFuncParsesNestedTagsDataProvider(): array
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

    #[DataProvider('stdWrap_parseFuncParsesNestedTagsDataProvider')]
    #[Test]
    public function stdWrap_parseFuncParsesNestedTags(string $value, array $configuration, string $expectedResult): void
    {
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals($expectedResult, $subject->stdWrap_parseFunc($value, $configuration));
    }

    public static function stdWrap_parseFuncCanHandleTagsAcrossMultipleLinesDataProvider(): iterable
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

    #[DataProvider('stdWrap_parseFuncCanHandleTagsAcrossMultipleLinesDataProvider')]
    #[Test]
    public function stdWrap_parseFuncCanHandleTagsAcrossMultipleLines(string $input, array $configuration, string $expected): void
    {
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals($expected, $subject->stdWrap_parseFunc($input, $configuration));
    }

    #[Test]
    public function stdWrap_splitReturnsCount(): void
    {
        $conf = [
            'split.' => [
                'token' => ',',
                'returnCount' => 1,
            ],
        ];
        $expectedResult = 5;
        $subject = $this->get(ContentObjectRenderer::class);
        $amountOfEntries = $subject->stdWrap_split('1, 2, 3, 4, 5', $conf);
        self::assertSame($expectedResult, $amountOfEntries);
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
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        $subject->stdWrap_addPageCacheTags('', $configuration);
        self::assertEquals($expectedTags, $cacheDataCollector->getCacheTags());
    }

    #[Test]
    public function stdWrap_prepend(): void
    {
        $content = 'myContent';
        $conf = [
            'prepend' => 'TEXT',
            'prepend.' => ['value' => 'foo'],
        ];
        $expected = 'foo' . $content;
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest(new ServerRequest());
        self::assertSame($expected, $subject->stdWrap_prepend($content, $conf));
    }

    #[Test]
    public function stdWrap_append(): void
    {
        $content = 'myContent';
        $conf = [
            'append' => 'TEXT',
            'append.' => ['value' => 'foo'],
        ];
        $expected = $content . 'foo';
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest(new ServerRequest());
        self::assertSame($expected, $subject->stdWrap_append($content, $conf));
    }

    public static function stdWrap_brDataProvider(): array
    {
        return [
            'no xhtml with LF in between' => [
                'one<br>' . chr(10) . 'two',
                'one' . chr(10) . 'two',
                null,
            ],
            'no xhtml with LF in between and around' => [
                '<br>' . chr(10) . 'one<br>' . chr(10) . 'two<br>' . chr(10),
                chr(10) . 'one' . chr(10) . 'two' . chr(10),
                null,
            ],
            'xhtml with LF in between' => [
                'one<br />' . chr(10) . 'two',
                'one' . chr(10) . 'two',
                'xhtml_strict',
            ],
            'xhtml with LF in between and around' => [
                '<br />' . chr(10) . 'one<br />' . chr(10) . 'two<br />' . chr(10),
                chr(10) . 'one' . chr(10) . 'two' . chr(10),
                'xhtml_strict',
            ],
        ];
    }

    #[DataProvider('stdWrap_brDataProvider')]
    #[Test]
    public function stdWrap_br(string $expected, string $input, ?string $doctype): void
    {
        $pageRenderer = $this->get(PageRenderer::class);
        $pageRenderer->setLanguage(new Locale());
        $pageRenderer->setDocType(DocType::createFromConfigurationKey($doctype));
        $subject = $this->get(ContentObjectRenderer::class);
        self::assertSame($expected, $subject->stdWrap_br($input));
    }

    public static function stdWrap_brTagDataProvider(): array
    {
        $noConfig = [];
        $config1 = ['brTag' => '<br/>'];
        $config2 = ['brTag' => '<br>'];
        return [
            'no config: one break at the beginning' => [chr(10) . 'one' . chr(10) . 'two', 'onetwo', $noConfig],
            'no config: multiple breaks at the beginning' => [chr(10) . chr(10) . 'one' . chr(10) . 'two', 'onetwo', $noConfig],
            'no config: one break at the end' => ['one' . chr(10) . 'two' . chr(10), 'onetwo', $noConfig],
            'no config: multiple breaks at the end' => ['one' . chr(10) . 'two' . chr(10) . chr(10), 'onetwo', $noConfig],
            'config1: one break at the beginning' => [chr(10) . 'one' . chr(10) . 'two', '<br/>one<br/>two', $config1],
            'config1: multiple breaks at the beginning' => [
                chr(10) . chr(10) . 'one' . chr(10) . 'two',
                '<br/><br/>one<br/>two',
                $config1,
            ],
            'config1: one break at the end' => ['one' . chr(10) . 'two' . chr(10), 'one<br/>two<br/>', $config1],
            'config1: multiple breaks at the end' => ['one' . chr(10) . 'two' . chr(10) . chr(10), 'one<br/>two<br/><br/>', $config1],
            'config2: one break at the beginning' => [chr(10) . 'one' . chr(10) . 'two', '<br>one<br>two', $config2],
            'config2: multiple breaks at the beginning' => [
                chr(10) . chr(10) . 'one' . chr(10) . 'two',
                '<br><br>one<br>two',
                $config2,
            ],
            'config2: one break at the end' => ['one' . chr(10) . 'two' . chr(10), 'one<br>two<br>', $config2],
            'config2: multiple breaks at the end' => ['one' . chr(10) . 'two' . chr(10) . chr(10), 'one<br>two<br><br>', $config2],
        ];
    }

    #[DataProvider('stdWrap_brTagDataProvider')]
    #[Test]
    public function stdWrap_brTag(string $input, string $expected, array $config): void
    {
        self::assertEquals($expected, $this->get(ContentObjectRenderer::class)->stdWrap_brTag($input, $config));
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
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_orderedStdWrap('someContent', $config));
    }

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
            ],
        ];
    }

    #[DataProvider('stdWrap_cacheStoreDataProvider')]
    #[Test]
    public function stdWrap_cacheStore(?array $confCache, int $times, mixed $key): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'cache.' => $confCache,
        ];
        $subject = $this->getAccessibleMock(ContentObjectRenderer::class, ['calculateCacheKey', 'calculateCacheTags', 'calculateCacheLifetime'], [], '', false);
        $subject->expects($this->exactly($times))->method('calculateCacheKey')->with($confCache)->willReturn($key);
        self::assertSame($content, $subject->stdWrap_cacheStore($content, $conf));
    }

    public static function stdWrap_caseDataProvider(): array
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

    #[DataProvider('stdWrap_caseDataProvider')]
    #[Test]
    public function stdWrap_case(string $content, string $expected): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_case($content, ['case' => 'upper']));
    }

    #[Test]
    public function stdWrap_char(): void
    {
        self::assertEquals('C', $this->get(ContentObjectRenderer::class)->stdWrap_char('', ['char' => '67']));
    }

    #[Test]
    public function stdWrap_cropIsMultibyteSafe(): void
    {
        self::assertEquals('бла', $this->get(ContentObjectRenderer::class)->stdWrap_crop('бла', ['crop' => '3|...']));
    }

    public static function stdWrap_cropHTMLDataProvider(): array
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
        $textWith2000Chars = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa.'
            . ' Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec,'
            . ' pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate'
            . ' eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium.'
            . ' Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula,'
            . ' porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus.'
            . ' Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet. Etiam ultricies nisi vel augue.'
            . ' Curabitur ullamcorper ultricies nisi. Nam eget dui. Etiam rhoncus. Maecenas tempus, tellus eget condimentum rhoncus,'
            . ' sem quam semper libero, sit amet adipiscing sem neque sed ips &amp;. Nam quam nunc, blandit vel, luctus pulvinar, hendrerit'
            . ' id, lorem. Maecenas nec odio et ante tincidunt tempus. Donec vitae sapien ut libero venenatis faucibus. Nullam quis ante.'
            . ' Etiam sit amet orci eget eros faucibus tincidunt. Duis leo. Sed fringilla mauris sit amet nibh. Donec sodales sagittis magna.'
            . ' Sed consequat, leo eget bibendum sodales, augue velit cursus nunc, quis gravida magna mi a libero. Fusce vulputate eleifend'
            . ' sapien. Vestibulum purus quam, scelerisque ut, mollis sed, nonummy id, metus. Nullam accumsan lorem in dui. Cras ultricies'
            . ' mi eu turpis hendrerit fringilla. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae;'
            . ' In ac dui quis mi consectetuer lacinia. Nam pretium turpis et arcu. Duis arcu tortor, suscipit eget, imperdiet nec, imperdiet'
            . ' iaculis, ipsum. Sed aliquam ultrices mauris. Integer ante arcu, accumsan a, consectetuer eget, posuere ut, mauris. Praesent'
            . ' adipiscing. Phasellus ullamcorper ipsum rutrum nunc. Nunc nonummy metus. Vesti&amp;';
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
     * Tests are kept to ensure parameter splitting works, although they are largely
     * duplicates of HtmlCropper class tests.
     */
    #[DataProvider('stdWrap_cropHTMLDataProvider')]
    #[Test]
    public function stdWrap_cropHTML(string $expected, string $content, string $conf): void
    {
        // Convert subject and expected result to utf-8.
        $content = mb_convert_encoding($content, 'utf-8', 'iso-8859-1');
        $expected = mb_convert_encoding($expected, 'utf-8', 'iso-8859-1');
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_cropHTML($content, ['cropHTML' => $conf]));
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
        $context = $this->get(Context::class);
        $context->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('2023-02-02 13:05:00')));
        $site = new Site('test', 1, [
            'identifier' => 'test',
            'rootPageId' => 1,
            'base' => '/',
            'languages' => [
                [
                    'base' => '/',
                    'languageId' => 2,
                    'locale' => 'en_UK',
                ],
            ],
        ]);
        $request = (new ServerRequest())->withAttribute('language', $site->getLanguageById(2));
        $conf = ['formattedDate' => $pattern];
        if ($locale !== null) {
            $conf['formattedDate.']['locale'] = $locale;
        }
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals($expected, $subject->stdWrap_formattedDate((string)$givenDate, $conf));
    }

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

    #[DataProvider('stdWrap_csConvDataProvider')]
    #[Test]
    public function stdWrap_csConv(string $expected, string $input, array $conf): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_csConv($input, $conf));
    }

    #[Test]
    public function stdWrap_current(): void
    {
        $data = [
            'currentValue_kidjls9dksoje' => 'default',
            'currentValue_new' => 'new',
        ];
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->data = $data;
        self::assertSame('currentValue_kidjls9dksoje', $subject->currentValKey);
        self::assertSame('default', $subject->stdWrap_current('discarded', ['discarded']));
        $subject->currentValKey = 'currentValue_new';
        self::assertSame('new', $subject->stdWrap_current('discarded', ['discarded']));
    }

    #[Test]
    public function stdWrap_data(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->parameters['someKey'] = 'someValue';
        self::assertEquals('someValue', $subject->stdWrap_data('', ['data' => 'parameters:someKey']));
    }

    #[Test]
    public function stdWrap_dataWrap(): void
    {
        $conf = [
            'dataWrap' => '<div id="{parameters:someKey}"> | </div>',
        ];
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->parameters['someKey'] = 'someValue';
        self::assertSame('<div id="someValue">myContent</div>', $subject->stdWrap_dataWrap('myContent', $conf));
    }

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

    #[DataProvider('stdWrap_dateDataProvider')]
    #[Test]
    public function stdWrap_date(string $expected, mixed $content, array $conf, int $now): void
    {
        $GLOBALS['EXEC_TIME'] = $now;
        self::assertEquals($expected, $this->get(ContentObjectRenderer::class)->stdWrap_date($content, $conf));
    }

    #[Test]
    public function stdWrap_debug(): void
    {
        $expect = '<pre>&lt;p class=&quot;class&quot;&gt;&lt;br/&gt;&lt;/p&gt;</pre>';
        $content = '<p class="class"><br/></p>';
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_debug($content));
    }

    #[Test]
    public function stdWrap_debugData(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $content = StringUtility::getUniqueId('content');
        $key = StringUtility::getUniqueId('key');
        $value = StringUtility::getUniqueId('value');
        $altValue = StringUtility::getUniqueId('value alt');
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->data = [$key => $value];
        ob_start();
        $result = $subject->stdWrap_debugData($content);
        $out = ob_get_clean();
        self::assertSame($result, $content);
        self::assertStringContainsString('$cObj->data', $out);
        self::assertStringContainsString($value, $out);
        self::assertStringNotContainsString($altValue, $out);
    }

    public static function stdWrap_debugFuncDataProvider(): array
    {
        return [
            'expect array by string' => [true, '2'],
            'expect array by integer' => [true, 2],
            'do not expect array' => [false, ''],
        ];
    }

    #[DataProvider('stdWrap_debugFuncDataProvider')]
    #[Test]
    public function stdWrap_debugFunc(bool $expectArray, mixed $confDebugFunc): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $content = StringUtility::getUniqueId('content');
        $conf = ['debugFunc' => $confDebugFunc];
        ob_start();
        $subject = $this->get(ContentObjectRenderer::class);
        $result = $subject->stdWrap_debugFunc($content, $conf);
        $out = ob_get_clean();
        self::assertSame($result, $content);
        self::assertStringContainsString($content, $out);
        if ($expectArray) {
            self::assertStringContainsString('=>', $out);
        } else {
            self::assertStringNotContainsString('=>', $out);
        }
    }

    public static function stdWrap_doubleBrTagDataProvider(): array
    {
        return [
            'no config: void input' => [
                '',
                '',
                [],
            ],
            'no config: single break' => [
                'one' . chr(10) . 'two',
                'one' . chr(10) . 'two',
                [],
            ],
            'no config: double break' => [
                'onetwo',
                'one' . chr(10) . chr(10) . 'two',
                [],
            ],
            'no config: double break with whitespace' => [
                'onetwo',
                'one' . chr(10) . "\t" . ' ' . "\t" . ' ' . chr(10) . 'two',
                [],
            ],
            'no config: single break around' => [
                chr(10) . 'one' . chr(10),
                chr(10) . 'one' . chr(10),
                [],
            ],
            'no config: double break around' => [
                'one',
                chr(10) . chr(10) . 'one' . chr(10) . chr(10),
                [],
            ],
            'empty string: double break around' => [
                'one',
                chr(10) . chr(10) . 'one' . chr(10) . chr(10),
                ['doubleBrTag' => ''],
            ],
            'br tag: double break' => [
                'one<br/>two',
                'one' . chr(10) . chr(10) . 'two',
                ['doubleBrTag' => '<br/>'],
            ],
            'br tag: double break around' => [
                '<br/>one<br/>',
                chr(10) . chr(10) . 'one' . chr(10) . chr(10),
                ['doubleBrTag' => '<br/>'],
            ],
            'double br tag: double break around' => [
                '<br/><br/>one<br/><br/>',
                chr(10) . chr(10) . 'one' . chr(10) . chr(10),
                ['doubleBrTag' => '<br/><br/>'],
            ],
        ];
    }

    #[DataProvider('stdWrap_doubleBrTagDataProvider')]
    #[Test]
    public function stdWrap_doubleBrTag(string $expected, string $input, array $config): void
    {
        self::assertEquals($expected, $this->get(ContentObjectRenderer::class)->stdWrap_doubleBrTag($input, $config));
    }

    #[Test]
    public function stdWrap_encapsLines(): void
    {
        $content = "Some <div>text</div>\n<p>Some text</p>";
        $conf = [
            'encapsLines.' => [
                'encapsTagList' => 'div, p',
                'remapTag.' => [
                    'P' => 'DIV',
                ],
            ],
        ];
        $subject = $this->get(ContentObjectRenderer::class);
        self::assertSame("Some <div>text</div>\n<div>Some text</div>", $subject->stdWrap_encapsLines($content, $conf));
    }

    public static function stdWrap_encapsLines_HTML5SelfClosingTagsDataProvider(): array
    {
        return [
            'areaTag_selfclosing' => [
                'tagName' => 'area',
                'expected' => '<area id="myId" class="bodytext" />',
            ],
            'base_selfclosing' => [
                'tagName' => 'base',
                'expected' => '<base id="myId" class="bodytext" />',
            ],
            'br_selfclosing' => [
                'tagName' => 'br',
                'expected' => '<br id="myId" class="bodytext" />',
            ],
            'col_selfclosing' => [
                'tagName' => 'col',
                'expected' => '<col id="myId" class="bodytext" />',
            ],
            'embed_selfclosing' => [
                'tagName' => 'embed',
                'expected' => '<embed id="myId" class="bodytext" />',
            ],
            'hr_selfclosing' => [
                'tagName' => 'hr',
                'expected' => '<hr id="myId" class="bodytext" />',
            ],
            'img_selfclosing' => [
                'tagName' => 'img',
                'expected' => '<img id="myId" class="bodytext" />',
            ],
            'input_selfclosing' => [
                'tagName' => 'input',
                'expected' => '<input id="myId" class="bodytext" />',
            ],
            'keygen_selfclosing' => [
                'tagName' => 'keygen',
                'expected' => '<keygen id="myId" class="bodytext" />',
            ],
            'link_selfclosing' => [
                'tagName' => 'link',
                'expected' => '<link id="myId" class="bodytext" />',
            ],
            'meta_selfclosing' => [
                'tagName' => 'meta',
                'expected' => '<meta id="myId" class="bodytext" />',
            ],
            'param_selfclosing' => [
                'tagName' => 'param',
                'expected' => '<param id="myId" class="bodytext" />',
            ],
            'source_selfclosing' => [
                'tagName' => 'source',
                'expected' => '<source id="myId" class="bodytext" />',
            ],
            'track_selfclosing' => [
                'tagName' => 'track',
                'expected' => '<track id="myId" class="bodytext" />',
            ],
            'wbr_selfclosing' => [
                'tagName' => 'wbr',
                'expected' => '<wbr id="myId" class="bodytext" />',
            ],
            'p_notselfclosing' => [
                'tagName' => 'p',
                'expected' => '<p id="myId" class="bodytext"></p>',
            ],
            'a_notselfclosing' => [
                'tagName' => 'a',
                'expected' => '<a id="myId" class="bodytext"></a>',
            ],
            'strong_notselfclosing' => [
                'tagName' => 'strong',
                'expected' => '<strong id="myId" class="bodytext"></strong>',
            ],
            'span_notselfclosing' => [
                'tagName' => 'span',
                'expected' => '<span id="myId" class="bodytext"></span>',
            ],
        ];
    }

    /**
     * Check if stdWrap_encapsLines() uses self-closing tags only for allowed tags
     * according to https://www.w3.org/TR/html5/syntax.html#void-elements
     */
    #[DataProvider('stdWrap_encapsLines_HTML5SelfClosingTagsDataProvider')]
    #[Test]
    public function stdWrap_encapsLines_HTML5SelfClosingTags(string $tagName, string $expected): void
    {
        $conf = [
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
            ],
        ];
        // We want to allow any tag to be an encapsulating tag since this is
        // possible, and we don't want an additional tag to be wrapped around.
        $conf['encapsLines.']['encapsTagList'] .= ',a, b,span,' . $tagName;
        $content = '<' . $tagName . ' id="myId" class="bodytext" />';
        $subject = $this->get(ContentObjectRenderer::class);
        self::assertSame($expected, $subject->stdWrap_encapsLines($content, $conf));
    }

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

    #[DataProvider('stdWrap_encodeForJavaScriptValueDataProvider')]
    #[Test]
    public function stdWrap_encodeForJavaScriptValue(string $expect, string $content): void
    {
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_encodeForJavaScriptValue($content));
    }

    public static function stdWrap_expandListDataProvider(): array
    {
        return [
            'numbers' => ['1,2,3', '1,2,3'],
            'range' => ['3,4,5', '3-5'],
            'numbers and range' => ['1,3,4,5,7', '1,3-5,7'],
        ];
    }

    #[DataProvider('stdWrap_expandListDataProvider')]
    #[Test]
    public function stdWrap_expandList(string $expected, string $content): void
    {
        self::assertEquals($expected, $this->get(ContentObjectRenderer::class)->stdWrap_expandList($content));
    }

    public static function stdWrap_fieldDataProvider(): array
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

    #[DataProvider('stdWrap_fieldDataProvider')]
    #[Test]
    public function stdWrap_field(mixed $expect, string $fields): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->data = [
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
        self::assertSame($expect, $subject->stdWrap_field('', ['field' => $fields]));
    }

    public static function stdWrap_fieldRequiredDataProvider(): array
    {
        $content = StringUtility::getUniqueId('content');
        return [
            // resulting in boolean false
            'false is false' => [
                '',
                $content,
                ['fieldRequired' => 'false'],
            ],
            'null is false' => [
                '',
                $content,
                ['fieldRequired' => 'null'],
            ],
            'empty string is false' => [
                '',
                $content,
                ['fieldRequired' => 'empty'],
            ],
            'whitespace is false' => [
                '',
                $content,
                ['fieldRequired' => 'whitespace'],
            ],
            'string zero is false' => [
                '',
                $content,
                ['fieldRequired' => 'stringZero'],
            ],
            'string zero with whitespace is false' => [
                '',
                $content,
                ['fieldRequired' => 'stringZeroWithWhiteSpace'],
            ],
            'zero is false' => [
                '',
                $content,
                ['fieldRequired' => 'zero'],
            ],
            // resulting in boolean true
            'true is true' => [
                $content,
                $content,
                ['fieldRequired' => 'true'],
            ],
            'string is true' => [
                $content,
                $content,
                ['fieldRequired' => 'string'],
            ],
            'one is true' => [
                $content,
                $content,
                ['fieldRequired' => 'one'],
            ],
        ];
    }

    #[DataProvider('stdWrap_fieldRequiredDataProvider')]
    #[Test]
    public function stdWrap_fieldRequired(string $expect, string $content, array $conf): void
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
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->data = $data;
        self::assertSame($expect, $subject->stdWrap_fieldRequired($content, $conf));
    }

    public static function stdWrap_hashDataProvider(): array
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

    #[DataProvider('stdWrap_hashDataProvider')]
    #[Test]
    public function stdWrap_hash(string $expect, string $content, array $conf): void
    {
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_hash($content, $conf));
    }

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

    #[DataProvider('stdWrap_htmlSpecialCharsDataProvider')]
    #[Test]
    public function stdWrap_htmlSpecialChars(string $expected, string $input, array $conf): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_htmlSpecialChars($input, $conf));
    }

    #[Test]
    public function stdWrap_if(): void
    {
        $conf = [
            'if.' => [
                'value' => 'containsFoo',
                'contains' => 'Foo',
            ],
        ];
        self::assertSame('yes', $this->get(ContentObjectRenderer::class)->stdWrap_if('yes', $conf));
        $conf['if.']['value'] = 'containsBar';
        self::assertSame('', $this->get(ContentObjectRenderer::class)->stdWrap_if('yes', $conf));
    }

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

    #[DataProvider('stdWrap_ifBlankDataProvider')]
    #[Test]
    public function stdWrap_ifBlank(mixed $expect, mixed $content, array $conf): void
    {
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_ifBlank($content, $conf));
    }

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

    #[DataProvider('stdWrap_ifEmptyDataProvider')]
    #[Test]
    public function stdWrap_ifEmpty(mixed $expect, mixed $content, array $conf): void
    {
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_ifEmpty($content, $conf));
    }

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

    #[DataProvider('stdWrap_ifNullDataProvider')]
    #[Test]
    public function stdWrap_ifNull(mixed $expect, mixed $content, array $conf): void
    {
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_ifNull($content, $conf));
    }

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

    #[DataProvider('stdWrap_innerWrapDataProvider')]
    #[Test]
    public function stdWrap_innerWrap(string $expected, string $input, array $conf): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_innerWrap($input, $conf));
    }

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

    #[DataProvider('stdWrap_innerWrap2DataProvider')]
    #[Test]
    public function stdWrap_innerWrap2(string $expected, string $input, array $conf): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_innerWrap2($input, $conf));
    }

    public static function stdWrap_insertDataProvider(): array
    {
        return [
            'empty' => ['', ''],
            'notFoundData' => ['any=1', 'any{$string}=1'],
            'queryParameter' => ['any{#string}=1', 'any{#string}=1'],
        ];
    }

    #[DataProvider('stdWrap_insertDataProvider')]
    #[Test]
    public function stdWrap_insertDataAndInputExamples(mixed $expect, string $content): void
    {
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_insertData($content));
    }

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

    #[DataProvider('stdWrap_intvalDataProvider')]
    #[Test]
    public function stdWrap_intval(int $expect, mixed $content): void
    {
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_intval($content));
    }

    public static function stdWrap_keywordsDataProvider(): array
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

    #[DataProvider('stdWrap_keywordsDataProvider')]
    #[Test]
    public function stdWrap_keywords(string $expected, string $input): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_keywords($input));
    }

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

    #[DataProvider('stdWrap_langDataProvider')]
    #[Test]
    public function stdWrap_langViaSiteLanguage(string $expected, string $input, array $conf, string $language): void
    {
        $site = new Site('test', 1, [
            'identifier' => 'test',
            'rootPageId' => 1,
            'base' => '/',
            'languages' => [
                [
                    'base' => '/',
                    'languageId' => 2,
                    'locale' => $language,
                ],
            ],
        ]);
        $request = new ServerRequest();
        $request = $request->withAttribute('language', $site->getLanguageById(2));
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertSame($expected, $subject->stdWrap_lang($input, $conf));
    }

    #[Test]
    public function stdWrap_listNum(): void
    {
        $conf = [
            'listNum' => 'last - 1',
        ];
        self::assertSame(' item 3', $this->get(ContentObjectRenderer::class)->stdWrap_listNum('tem 1, item 2, item 3, item 4', $conf));
    }

    #[Test]
    public function stdWrap_preIfEmptyListNum(): void
    {
        $conf = [
            'preIfEmptyListNum' => 'last - 1',
        ];
        self::assertSame(' item 3', $this->get(ContentObjectRenderer::class)->stdWrap_preIfEmptyListNum('tem 1, item 2, item 3, item 4', $conf));
    }

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

    #[DataProvider('stdWrap_noTrimWrapDataProvider')]
    #[Test]
    public function stdWrap_noTrimWrap(string $expect, string $content, array $conf): void
    {
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_noTrimWrap($content, $conf));
    }

    #[Test]
    public function stdWrap_numRows(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $conf = [
            'numRows.' => [
                'table' => 'pages',
            ],
        ];
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setContentFromPid(1);
        $request = (new ServerRequest())->withAttribute('frontend.page.information', $pageInformation);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertSame(2, $subject->stdWrap_numRows('unused', $conf));
    }

    public static function stdWrap_numberFormatDataProvider(): array
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

    #[DataProvider('stdWrap_numberFormatDataProvider')]
    #[Test]
    public function stdWrap_numberFormat(string $expected, mixed $content, array $conf): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_numberFormat($content, ['numberFormat.' => $conf]));
    }

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

    #[DataProvider('stdWrap_outerWrapDataProvider')]
    #[Test]
    public function stdWrap_outerWrap(string $expected, string $input, array $conf): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_outerWrap($input, $conf));
    }

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

    #[DataProvider('stdWrap_overrideDataProvider')]
    #[Test]
    public function stdWrap_override(mixed $expect, string $content, array $conf): void
    {
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_override($content, $conf));
    }

    #[Test]
    public function stdWrap_postCObject(): void
    {
        $content = 'myContent';
        $conf = [
            'postCObject' => 'TEXT',
            'postCObject.' => ['value' => 'foo'],
        ];
        $expected = $content . 'foo';
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest(new ServerRequest());
        self::assertSame($expected, $subject->stdWrap_postCObject($content, $conf));
    }

    #[Test]
    public function stdWrap_preCObject(): void
    {
        $content = 'myContent';
        $conf = [
            'preCObject' => 'TEXT',
            'preCObject.' => ['value' => 'foo'],
        ];
        $expected = 'foo' . $content;
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest(new ServerRequest());
        self::assertSame($expected, $subject->stdWrap_preCObject($content, $conf));
    }

    public static function stdWrap_prefixCommentDataProvider(): array
    {
        $content = StringUtility::getUniqueId('content');
        return [
            'standard case' => [
                "\n\t<!-- thePrefixComment [begin] -->\n\t\tfoo\n\t<!-- thePrefixComment [end] -->\n\t\t",
                'foo',
                [
                    'prefixComment' => '1|thePrefixComment',
                ],
                false,
            ],
            'disabled by bool' => [
                'foo',
                'foo',
                [
                    'prefixComment' => '1|thePrefixComment',
                ],
                true,
            ],
            'disabled by int' => [
                'foo',
                'foo',
                [
                    'prefixComment' => '1|thePrefixComment',
                ],
                1,
            ],
        ];
    }

    #[Test]
    #[DataProvider('stdWrap_prefixCommentDataProvider')]
    public function stdWrap_prefixComment(string $expected, string $content, array $conf, int|bool $disable): void
    {
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([
            'disablePrefixComment' => $disable,
        ]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertSame($expected, $subject->stdWrap_prefixComment($content, $conf));
    }

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

    #[DataProvider('stdWrap_prioriCalcDataProvider')]
    #[Test]
    public function stdWrap_prioriCalc(mixed $expect, string $content, array $conf): void
    {
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_prioriCalc($content, $conf));
    }

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

    #[DataProvider('stdWrap_rawUrlEncodeDataProvider')]
    #[Test]
    public function stdWrap_rawUrlEncode(string $expect, string $content): void
    {
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_rawUrlEncode($content));
    }

    public static function stdWrap_requiredDataProvider(): array
    {
        return [
            // empty content
            'empty string is empty' => ['', ''],
            'null is empty' => ['', null],
            'false is empty' => ['', false],
            // non-empty content
            'blank is not empty' => [' ', ' '],
            'tab is not empty' => ["\t", "\t"],
            'linebreak is not empty' => [PHP_EOL, PHP_EOL],
            '"0" is not empty' => ['0', '0'],
            '0 is not empty' => [0, 0],
            '1 is not empty' => [1, 1],
            'true is not empty' => [true, true],
        ];
    }

    #[DataProvider('stdWrap_requiredDataProvider')]
    #[Test]
    public function stdWrap_required(mixed $expect, mixed $content): void
    {
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_required($content));
    }

    #[Test]
    public function stdWrap_setContentToCurrent(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $content = StringUtility::getUniqueId('content');
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertNotSame($content, $subject->getData('current'));
        self::assertSame($content, $subject->stdWrap_setContentToCurrent($content));
        self::assertSame($content, $subject->getData('current'));
    }

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

    #[DataProvider('stdWrap_setCurrentDataProvider')]
    #[Test]
    public function stdWrap_setCurrent(string $input, array $conf): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        if (isset($conf['setCurrent'])) {
            self::assertNotSame($conf['setCurrent'], $subject->getData('current'));
        }
        self::assertSame($input, $subject->stdWrap_setCurrent($input, $conf));
        if (isset($conf['setCurrent'])) {
            self::assertSame($conf['setCurrent'], $subject->getData('current'));
        }
    }

    #[Test]
    public function stdWrap_stdWrap(): void
    {
        $conf = [
            'stdWrap.' => [
                'wrap' => 'bar|bar',
            ],
        ];
        $subject = $this->get(ContentObjectRenderer::class);
        self::assertSame('barfoobar', $subject->stdWrap_stdWrap('foo', $conf));
    }

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
    public function stdWrap_stdWrapValue(string $key, array $configuration, ?string $defaultValue, ?string $expected): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrapValue($key, $configuration, $defaultValue));
    }

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

    #[DataProvider('stdWrap_strPadDataProvider')]
    #[Test]
    public function stdWrap_strPad(string $expected, string $content, array $conf): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_strPad($content, ['strPad.' => $conf]));
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

    #[DataProvider('stdWrap_strftimeDataProvider')]
    #[Test]
    public function stdWrap_strftime(string $expect, mixed $content, array $conf, int $now): void
    {
        // Save current timezone and set to UTC to make the system under test
        // behave the same in all server timezone settings
        $timezoneBackup = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $GLOBALS['EXEC_TIME'] = $now;
        $result = $this->get(ContentObjectRenderer::class)->stdWrap_strftime($content, $conf);
        // Reset timezone
        date_default_timezone_set($timezoneBackup);
        self::assertSame($expect, $result);
    }

    #[Test]
    public function stdWrap_stripHtml(): void
    {
        $content = '<html><p>Hello <span class="inline">inline tag<span>!</p><p>Hello!</p></html>';
        $expected = 'Hello inline tag!Hello!';
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_stripHtml($content));
    }

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
        $result = $this->get(ContentObjectRenderer::class)->stdWrap_strtotime($content, $conf);
        // Reset timezone
        date_default_timezone_set($timezoneBackup);
        self::assertEquals($expect, $result);
    }

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

    #[DataProvider('stdWrap_trimDataProvider')]
    #[Test]
    public function stdWrap_trim(string $expect, mixed $content): void
    {
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_trim($content));
    }

    #[Test]
    public function stdWrap_typolink(): void
    {
        $conf = [
            'typolink.' => [
                'parameter' => 'fileadmin/foo.bar',
                'ATagParams' => 'class="file-class" href="foo-bar"',
                'fileTarget' => '_blank',
                'title' => 'Title of the file',
            ],
        ];
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest(new ServerRequest());
        self::assertSame(
            '<a href="fileadmin/foo.bar" target="_blank" class="file-class" title="Title of the file">my file</a>',
            $subject->stdWrap_typolink('my file', $conf)
        );
    }

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

    #[DataProvider('stdWrap_wrapDataProvider')]
    #[Test]
    public function stdWrap_wrap(string $expected, string $input, array $conf): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_wrap($input, $conf));
    }

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

    #[DataProvider('stdWrap_wrap2DataProvider')]
    #[Test]
    public function stdWrap_wrap2(string $expected, string $input, array $conf): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_wrap2($input, $conf));
    }

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

    #[DataProvider('stdWrap_wrap3DataProvider')]
    #[Test]
    public function stdWrap_wrap3(string $expected, string $input, array $conf): void
    {
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->stdWrap_wrap3($input, $conf));
    }

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

    #[DataProvider('stdWrap_wrapAlignDataProvider')]
    #[Test]
    public function stdWrap_wrapAlign(string $expect, string $content, mixed $wrapAlignConf): void
    {
        $conf = [];
        if ($wrapAlignConf !== null) {
            $conf['wrapAlign'] = $wrapAlignConf;
        }
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->stdWrap_wrapAlign($content, $conf));
    }

    #[Test]
    public function getContentObjectReturnsNullForUnregisteredObject(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($this->getPreparedRequest());
        self::assertNull($subject->getContentObject('FOO'));
    }

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

    #[DataProvider('calcAgeDataProvider')]
    #[Test]
    public function calcAge(string $expect, int $timestamp, string $labels): void
    {
        $GLOBALS['EXEC_TIME'] = 1417392000;
        self::assertSame($expect, $this->get(ContentObjectRenderer::class)->calcAge($timestamp, $labels));
    }

    public static function prefixCommentDataProvider(): array
    {
        $comment = StringUtility::getUniqueId();
        $content = StringUtility::getUniqueId();
        $format = '%s';
        $format .= '%%s<!-- %%s [begin] -->%s';
        $format .= '%%s%s%%s%s';
        $format .= '%%s<!-- %%s [end] -->%s';
        $format .= '%%s%s';
        $format = sprintf($format, chr(10), chr(10), "\t", chr(10), chr(10), "\t");
        $indent1 = "\t";
        $indent2 = "\t" . "\t";
        return [
            'indent one tab' => [
                sprintf($format, $indent1, $comment, $indent1, $content, $indent1, $comment, $indent1),
                '1|' . $comment,
                $content,
            ],
            'indent two tabs' => [
                sprintf($format, $indent2, $comment, $indent2, $content, $indent2, $comment, $indent2),
                '2|' . $comment,
                $content,
            ],
            'htmlspecialchars applies for comment only' => [
                sprintf($format, $indent1, '&lt;' . $comment . '&gt;', $indent1, '<' . $content . '>', $indent1, '&lt;' . $comment . '&gt;', $indent1),
                '1|<' . $comment . '>',
                '<' . $content . '>',
            ],
        ];
    }

    #[DataProvider('prefixCommentDataProvider')]
    #[Test]
    public function prefixComment(string $expect, string $comment, string $content): void
    {
        self::assertEquals($expect, $this->get(ContentObjectRenderer::class)->prefixComment($comment, null, $content));
    }

    #[Test]
    public function setCurrentFile_getCurrentFile(): void
    {
        $storageMock = $this->createMock(ResourceStorage::class);
        $file = new File(['testfile'], $storageMock);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setCurrentFile($file);
        self::assertSame($file, $subject->getCurrentFile());
    }

    #[Test]
    public function setCurrentVal_getCurrentVal(): void
    {
        $key = StringUtility::getUniqueId();
        $value = StringUtility::getUniqueId();
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->currentValKey = $key;
        $subject->setCurrentVal($value);
        self::assertEquals($value, $subject->getCurrentVal());
        self::assertEquals($value, $subject->data[$key]);
    }

    #[Test]
    public function setUserObjectType_getUserObjectType(): void
    {
        $value = StringUtility::getUniqueId();
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setUserObjectType($value);
        self::assertEquals($value, $subject->getUserObjectType());
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
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->getGlobal($key, $source));
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
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
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
        $result = $subject->mergeTSRef($inputArray, 'tempKey');
        self::assertSame($expected, $result);
    }

    public static function listNumDataProvider(): array
    {
        return [
            'Numeric non-zero $listNum' => [
                'expected' => 'foo',
                'content' => 'hello,foo,bar,',
                'listNum' => '1',
                'delimiter' => ',',
            ],
            'Numeric non-zero $listNum, without passing delimiter' => [
                'expected' => 'foo',
                'content' => 'hello,foo,bar',
                'listNum' => '1',
                'delimiter' => '',
            ],
            '$listNum = last' => [
                'expected' => 'bar',
                'content' => 'hello,foo,bar',
                'listNum' => 'last',
                'delimiter' => ',',
            ],
            '$listNum arithmetic' => [
                'expected' => 'foo',
                'content' => 'hello,foo,bar',
                'listNum' => '3-2',
                'delimiter' => ',',
            ],
        ];

    }

    #[DataProvider('listNumDataProvider')]
    #[Test]
    public function listNum(string $expected, string $content, string $listNum, string $delimiter): void
    {
        self::assertEquals($expected, $this->get(ContentObjectRenderer::class)->listNum($content, $listNum, $delimiter));
    }

    #[Test]
    public function listNumWithListNumRandReturnsString(): void
    {
        $result = $this->get(ContentObjectRenderer::class)->listNum('hello,foo,bar', 'rand');
        self::assertTrue($result === 'hello' || $result === 'foo' || $result === 'bar');
    }

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

    #[DataProvider('checkIfDataProvider')]
    #[Test]
    public function checkIf(bool $expect, array $conf): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        self::assertSame($expect, $subject->checkIf($conf));
    }

    public static function http_makeLinksDataProvider(): array
    {
        return [
            'http link' => [
                'Some text with a link http://example.com',
                [],
                'Some text with a link <a href="http://example.com">example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com'))->withLinkText('example.com'),
            ],
            'http link with path' => [
                'Some text with a link http://example.com/path/to/page',
                [],
                'Some text with a link <a href="http://example.com/path/to/page">example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com/path/to/page'))->withLinkText('example.com'),
            ],
            'http link with query parameter' => [
                'Some text with a link http://example.com?foo=bar',
                [],
                'Some text with a link <a href="http://example.com?foo=bar">example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com?foo=bar'))->withLinkText('example.com'),
            ],
            'http link with question mark' => [
                'Some text with a link http://example.com?',
                [],
                'Some text with a link <a href="http://example.com">example.com</a>?',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com'))->withLinkText('example.com'),
            ],
            'http link with period' => [
                'Some text with a link http://example.com.',
                [],
                'Some text with a link <a href="http://example.com">example.com</a>.',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com'))->withLinkText('example.com'),
            ],
            'http link with fragment' => [
                'Some text with a link http://example.com#',
                [],
                'Some text with a link <a href="http://example.com#">example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://example.com#'))->withLinkText('example.com'),
            ],
            'http link with query parameter and fragment' => [
                'Some text with a link http://example.com?foo=bar#top',
                [],
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
                [],
                'Some text with a link <a href="https://example.com">example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'https://example.com'))->withLinkText('example.com'),
            ],
            'http link with www' => [
                'Some text with a link http://www.example.com',
                [],
                'Some text with a link <a href="http://www.example.com">www.example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'http://www.example.com'))->withLinkText('www.example.com'),
            ],
            'https link with www' => [
                'Some text with a link https://www.example.com',
                [],
                'Some text with a link <a href="https://www.example.com">www.example.com</a>',
                (new LinkResult(LinkService::TYPE_URL, 'https://www.example.com'))->withLinkText('www.example.com'),
            ],
        ];
    }

    #[DataProvider('http_makeLinksDataProvider')]
    #[Test]
    public function http_makeLinksReturnsLink(string $data, array $configuration, string $expectedResult, LinkResult $linkResult): void
    {
        $linkFactory = $this->createMock(LinkFactory::class);
        $linkFactory->method('create')->willReturn($linkResult);
        $this->get('service_container')->set(LinkFactory::class, $linkFactory);
        $subject = $this->get(ContentObjectRenderer::class);
        $http_makeLinksReflectionMethod = (new \ReflectionClass($subject))->getMethod('http_makeLinks');
        $result = $http_makeLinksReflectionMethod->invoke($subject, $data, $configuration);
        self::assertSame($expectedResult, $result);
    }

    public static function http_makeLinksReturnsNoLinkDataProvider(): array
    {
        return [
            'only http protocol' => [
                'http://',
                'http://',
            ],
            'only https protocol' => [
                'https://',
                'https://',
            ],
            'ftp link' => [
                'ftp://user@password:example.com',
                'ftp://user@password:example.com',
            ],
        ];
    }

    #[DataProvider('http_makeLinksReturnsNoLinkDataProvider')]
    #[Test]
    public function http_makeLinksReturnsNoLink(string $data, string $expectedResult): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $http_makeLinksReflectionMethod = (new \ReflectionClass($subject))->getMethod('http_makeLinks');
        $result = $http_makeLinksReflectionMethod->invoke($subject, $data, []);
        self::assertSame($expectedResult, $result);
    }

    public static function mailto_makelinksReturnsMailToLinkDataProvider(): array
    {
        return [
            'mailto link' => [
                'Some text with an email address mailto:john@example.com',
                [],
                'Some text with an email address <a href="mailto:john@example.com">john@example.com</a>',
                (new LinkResult(LinkService::TYPE_EMAIL, 'mailto:john@example.com'))->withLinkText('john@example.com'),
            ],
            'mailto link with subject parameter' => [
                'Some text with an email address mailto:john@example.com?subject=hi',
                [],
                'Some text with an email address <a href="mailto:john@example.com?subject=hi">john@example.com</a>',
                (new LinkResult(LinkService::TYPE_EMAIL, 'mailto:john@example.com?subject=hi'))->withLinkText('john@example.com'),
            ],
            'mailto link with multiple parameters' => [
                'Some text with an email address mailto:john@example.com?subject=Greetings&body=Hi+John',
                [],
                'Some text with an email address <a href="mailto:john@example.com?subject=Greetings&amp;body=Hi+John">john@example.com</a>',
                (new LinkResult(LinkService::TYPE_EMAIL, 'mailto:john@example.com?subject=Greetings&body=Hi+John'))->withLinkText('john@example.com'),
            ],
            'mailto link with question mark' => [
                'Some text with an email address mailto:john@example.com?',
                [],
                'Some text with an email address <a href="mailto:john@example.com">john@example.com</a>?',
                (new LinkResult(LinkService::TYPE_EMAIL, 'mailto:john@example.com'))->withLinkText('john@example.com'),
            ],
            'mailto link with period' => [
                'Some text with an email address mailto:john@example.com.',
                [],
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

    #[DataProvider('mailto_makelinksReturnsMailToLinkDataProvider')]
    #[Test]
    public function mailto_makelinksReturnsMailToLink(string $data, array $configuration, string $expectedResult, LinkResult $linkResult): void
    {
        $linkFactory = $this->createMock(LinkFactory::class);
        $linkFactory->method('create')->willReturn($linkResult);
        $this->get('service_container')->set(LinkFactory::class, $linkFactory);
        $subject = $this->get(ContentObjectRenderer::class);
        $mailto_makelinksReflectionMethod = (new \ReflectionClass($subject))->getMethod('mailto_makelinks');
        $result = $mailto_makelinksReflectionMethod->invoke($subject, $data, $configuration);
        self::assertSame($expectedResult, $result);
    }

    #[Test]
    public function mailto_makelinksReturnsNoMailToLink(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $mailto_makelinksReflectionMethod = (new \ReflectionClass($subject))->getMethod('mailto_makelinks');
        $result = $mailto_makelinksReflectionMethod->invoke($subject, 'mailto:', []);
        self::assertSame('mailto:', $result);
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
        self::assertSame($expected, $this->get(ContentObjectRenderer::class)->caseshift($content, $case));
    }

    public static function getQueryDataProvider(): array
    {
        return [
            'testing empty conf' => [
                'tt_content',
                [],
                '*',
            ],
            'testing #17284: adding uid/pid for workspaces' => [
                'tt_content',
                [
                    'selectFields' => 'header,bodytext',
                ],
                'header,bodytext, [tt_content].[uid] AS [uid], [tt_content].[pid] AS [pid], [tt_content].[t3ver_state] AS [t3ver_state]',
            ],
            'testing #17284: no need to add' => [
                'tt_content',
                [
                    'selectFields' => 'tt_content.*',
                ],
                'tt_content.*',
            ],
            'testing #17284: no need to add #2' => [
                'tt_content',
                [
                    'selectFields' => '*',
                ],
                '*',
            ],
            'testing #29783: joined tables, prefix tablename' => [
                'tt_content',
                [
                    'selectFields' => 'tt_content.header,be_users.username',
                    'join' => 'be_users ON tt_content.cruser_id = be_users.uid',
                ],
                'tt_content.header,be_users.username, [tt_content].[uid] AS [uid], [tt_content].[pid] AS [pid], [tt_content].[t3ver_state] AS [t3ver_state]',
            ],
            'testing #34152: single count(*), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'count(*)',
                ],
                'count(*)',
            ],
            'testing #34152: single max(crdate), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'max(crdate)',
                ],
                'max(crdate)',
            ],
            'testing #34152: single min(crdate), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'min(crdate)',
                ],
                'min(crdate)',
            ],
            'testing #34152: single sum(is_siteroot), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'sum(is_siteroot)',
                ],
                'sum(is_siteroot)',
            ],
            'testing #34152: single avg(crdate), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'avg(crdate)',
                ],
                'avg(crdate)',
            ],
            'single distinct, add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'DISTINCT crdate',
                ],
                'DISTINCT crdate',
            ],
            'testing #96321: pidInList=root does not raise PHP 8 warning' => [
                'tt_content',
                [
                    'selectFields' => '*',
                    'recursive' => '5',
                    'pidInList' => 'root',
                ],
                '*',
            ],
        ];
    }

    #[DataProvider('getQueryDataProvider')]
    #[Test]
    public function getQuery(string $table, array $conf, string $expected): void
    {
        $backedupTca = $GLOBALS['TCA'];
        $tca = [
            'pages' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                    ],
                ],
                'columns' => [
                    'hidden' => [
                        'config' => [
                            'type' => 'check',
                        ],
                    ],
                ],
            ],
            'tt_content' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                    ],
                    'versioningWS' => true,
                ],
                'columns' => [
                    'hidden' => [
                        'config' => [
                            'type' => 'check',
                        ],
                    ],
                ],
            ],
        ];
        $tcaSchemaFactory = $this->get(TcaSchemaFactory::class);
        $tcaSchemaFactory->load($tca, true);

        $pageInformation = new PageInformation();
        $pageInformation->setId(0);
        $pageInformation->setContentFromPid(0);
        $request = $this->getPreparedRequest();
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);

        $connection = (new ConnectionPool())->getConnectionForTable('tt_content');
        $result = $subject->getQuery($connection, $table, $conf);

        $databasePlatform = $connection->getDatabasePlatform();
        $identifierQuoteCharacter = (new PlatformHelper())->getIdentifierQuoteCharacter($databasePlatform);
        // strip select * from part between SELECT and FROM
        $selectValue = preg_replace('/SELECT (.*) FROM.*/', '$1', $result);
        // Replace the TYPO3 quote character with the actual quote character for the DBMS
        $quoteChar = $identifierQuoteCharacter;
        $expected = str_replace(['[', ']'], [$quoteChar, $quoteChar], $expected);
        self::assertEquals($expected, $selectValue);
        $tcaSchemaFactory->load($backedupTca, true);
    }

    #[Test]
    public function typolinkLinkResultIsInstanceOfLinkResultInterface(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($this->getPreparedRequest());
        $linkResult = $subject->typoLink('', ['parameter' => 'https://example.tld', 'returnLast' => 'result']);
        self::assertInstanceOf(LinkResultInterface::class, $linkResult);
    }

    #[Test]
    public function typoLinkReturnsOnlyLinkTextIfNoLinkResolvingIsPossible(): void
    {
        $linkService = $this->getMockBuilder(LinkService::class)->disableOriginalConstructor()->getMock();
        $linkService->method('resolve')->with('foo')->willThrowException(new InvalidPathException('', 1666303735));
        $linkFactory = new LinkFactory($linkService, $this->get(EventDispatcherInterface::class), $this->get(TypoLinkCodecService::class), $this->get('cache.runtime'), $this->get(SiteFinder::class), new NullLogger());
        $this->get('service_container')->set(LinkFactory::class, $linkFactory);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($this->getPreparedRequest());
        self::assertSame('foo', $subject->typoLink('foo', ['parameter' => 'foo']));
    }

    #[Test]
    public function typoLinkLogsErrorIfNoLinkResolvingIsPossible(): void
    {
        $linkService = $this->getMockBuilder(LinkService::class)->disableOriginalConstructor()->getMock();
        $linkService->method('resolve')->with('foo')->willThrowException(new InvalidPathException('', 1666303765));
        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $logger->expects($this->atLeastOnce())->method('warning')->with('The link could not be generated', self::anything());
        $linkFactory = new LinkFactory($linkService, $this->get(EventDispatcherInterface::class), $this->get(TypoLinkCodecService::class), $this->get('cache.runtime'), $this->get(SiteFinder::class), $logger);
        $this->get('service_container')->set(LinkFactory::class, $linkFactory);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($this->getPreparedRequest());
        self::assertSame('foo', $subject->typoLink('foo', ['parameter' => 'foo']));
    }

    public static function typolinkReturnsCorrectLinksDataProvider(): array
    {
        return [
            'Link to url' => [
                'TYPO3',
                [
                    'directImageLink' => false,
                    'parameter' => 'http://typo3.org',
                ],
                '<a href="http://typo3.org">TYPO3</a>',
            ],
            'Link to url without schema' => [
                'TYPO3',
                [
                    'directImageLink' => false,
                    'parameter' => 'typo3.org',
                ],
                '<a href="http://typo3.org">TYPO3</a>',
            ],
            'Link to url without link text' => [
                '',
                [
                    'directImageLink' => false,
                    'parameter' => 'http://typo3.org',
                ],
                '<a href="http://typo3.org">http://typo3.org</a>',
            ],
            'Link to url with attributes' => [
                'TYPO3',
                [
                    'parameter' => 'http://typo3.org',
                    'ATagParams' => 'class="url-class"',
                    'extTarget' => '_blank',
                    'title' => 'Open new window',
                ],
                '<a href="http://typo3.org" target="_blank" class="url-class" rel="noreferrer" title="Open new window">TYPO3</a>',
            ],
            'Link to url with attributes and custom target name' => [
                'TYPO3',
                [
                    'parameter' => 'http://typo3.org',
                    'ATagParams' => 'class="url-class"',
                    'extTarget' => 'someTarget',
                    'title' => 'Open new window',
                ],
                '<a href="http://typo3.org" target="someTarget" class="url-class" rel="noreferrer" title="Open new window">TYPO3</a>',
            ],
            'Link to url with attributes in parameter' => [
                'TYPO3',
                [
                    'parameter' => 'http://typo3.org _blank url-class "Open new window"',
                ],
                '<a href="http://typo3.org" target="_blank" rel="noreferrer" title="Open new window" class="url-class">TYPO3</a>',
            ],
            'Link to url with attributes in parameter and custom target name' => [
                'TYPO3',
                [
                    'parameter' => 'http://typo3.org someTarget url-class "Open new window"',
                ],
                '<a href="http://typo3.org" target="someTarget" rel="noreferrer" title="Open new window" class="url-class">TYPO3</a>',
            ],
            'Link to url with script tag' => [
                '',
                [
                    'directImageLink' => false,
                    'parameter' => 'http://typo3.org<script>alert(123)</script>',
                ],
                '<a href="http://typo3.org&lt;script&gt;alert(123)&lt;/script&gt;">http://typo3.org&lt;script&gt;alert(123)&lt;/script&gt;</a>',
            ],
            'Link to email address' => [
                'Email address',
                [
                    'parameter' => 'foo@example.com',
                ],
                '<a href="mailto:foo@example.com">Email address</a>',
            ],
            'Link to email with mailto' => [
                'Send me an email',
                [
                    'parameter' => 'mailto:test@example.com',
                ],
                '<a href="mailto:test@example.com">Send me an email</a>',
            ],
            'Link to email address with subject + cc' => [
                'Email address',
                [
                    'parameter' => 'foo@bar.org?subject=This%20is%20a%20test',
                ],
                '<a href="mailto:foo@bar.org?subject=This%20is%20a%20test">Email address</a>',
            ],
            'Link to email address without link text' => [
                '',
                [
                    'parameter' => 'foo@bar.org',
                ],
                '<a href="mailto:foo@bar.org">foo@bar.org</a>',
            ],
            'Link to email with attributes' => [
                'Email address',
                [
                    'parameter' => 'foo@bar.org',
                    'ATagParams' => 'class="email-class"',
                    'title' => 'Write an email',
                ],
                '<a href="mailto:foo@bar.org" class="email-class" title="Write an email">Email address</a>',
            ],
            'Link to email with attributes in parameter' => [
                'Email address',
                [
                    'parameter' => 'foo@bar.org - email-class "Write an email"',
                ],
                '<a href="mailto:foo@bar.org" title="Write an email" class="email-class">Email address</a>',
            ],
            'Link url using stdWrap' => [
                'TYPO3',
                [
                    'parameter' => 'http://typo3.org',
                    'parameter.' => [
                        'cObject' => 'TEXT',
                        'cObject.' => [
                            'value' => 'http://typo3.com',
                        ],
                    ],
                ],
                '<a href="http://typo3.com">TYPO3</a>',
            ],
            'Link url using stdWrap with class attribute in parameter' => [
                'TYPO3',
                [
                    'parameter' => 'http://typo3.org - url-class',
                    'parameter.' => [
                        'cObject' => 'TEXT',
                        'cObject.' => [
                            'value' => 'http://typo3.com',
                        ],
                    ],
                ],
                '<a href="http://typo3.com" class="url-class">TYPO3</a>',
            ],
            'Link url using stdWrap with class attribute in parameter and overridden target' => [
                'TYPO3',
                [
                    'parameter' => 'http://typo3.org default-target url-class',
                    'parameter.' => [
                        'cObject' => 'TEXT',
                        'cObject.' => [
                            'value' => 'http://typo3.com new-target different-url-class',
                        ],
                    ],
                ],
                '<a href="http://typo3.com" target="new-target" rel="noreferrer" class="different-url-class">TYPO3</a>',
            ],
            'Link url using stdWrap with class attribute in parameter and overridden target and returnLast' => [
                'TYPO3',
                [
                    'parameter' => 'http://typo3.org default-target url-class',
                    'parameter.' => [
                        'cObject' => 'TEXT',
                        'cObject.' => [
                            'value' => 'http://typo3.com new-target different-url-class',
                        ],
                    ],
                    'returnLast' => 'url',
                ],
                'http://typo3.com',
            ],
            'Open in new window' => [
                'Nice Text',
                [
                    'parameter' => 'https://example.com 13x84:target=myexample',
                ],
                '<a href="https://example.com" target="myexample" data-window-url="https://example.com" data-window-target="myexample" data-window-features="width=13,height=84" rel="noreferrer">Nice Text</a>',
            ],
            'Open in new window with window name' => [
                'Nice Text',
                [
                    'parameter' => 'https://example.com 13x84',
                ],
                '<a href="https://example.com" target="FEopenLink" data-window-url="https://example.com" data-window-target="FEopenLink" data-window-features="width=13,height=84" rel="noreferrer">Nice Text</a>',
            ],
            'Link to file' => [
                'My file',
                [
                    'directImageLink' => false,
                    'parameter' => 'fileadmin/foo.bar',
                ],
                '<a href="fileadmin/foo.bar">My file</a>',
            ],
            'Link to file without link text' => [
                '',
                [
                    'directImageLink' => false,
                    'parameter' => 'fileadmin/foo.bar',
                ],
                '<a href="fileadmin/foo.bar">fileadmin/foo.bar</a>',
            ],
            'Link to file with attributes' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '<a href="fileadmin/foo.bar" target="_blank" class="file-class" title="Title of the file">My file</a>',
            ],
            'Link to file with empty attributes' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'download',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '<a href="fileadmin/foo.bar" target="_blank" download="" title="Title of the file">My file</a>',
            ],
            'Link to file with attributes and additional href' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'href="foo-bar"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '<a href="fileadmin/foo.bar" target="_blank" title="Title of the file">My file</a>',
            ],
            'Link to file with attributes and additional href and class' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'href="foo-bar" class="file-class"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '<a href="fileadmin/foo.bar" target="_blank" class="file-class" title="Title of the file">My file</a>',
            ],
            'Link to file with attributes and additional class and href' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class" href="foo-bar"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '<a href="fileadmin/foo.bar" target="_blank" class="file-class" title="Title of the file">My file</a>',
            ],
            'Link to file with attributes and additional class and href and title' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class" href="foo-bar" title="foo-bar"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '<a href="fileadmin/foo.bar" target="_blank" class="file-class" title="Title of the file">My file</a>',
            ],
            'Link to file with attributes and empty ATagParams' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => '',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '<a href="fileadmin/foo.bar" target="_blank" title="Title of the file">My file</a>',
            ],
            'Link to file with attributes in parameter' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar _blank file-class "Title of the file"',
                ],
                '<a href="fileadmin/foo.bar" target="_blank" title="Title of the file" class="file-class">My file</a>',
            ],
            'Link to file with script tag in name' => [
                '',
                [
                    'directImageLink' => false,
                    'parameter' => 'fileadmin/<script>alert(123)</script>',
                ],
                '<a href="fileadmin/&lt;script&gt;alert(123)&lt;/script&gt;">fileadmin/&lt;script&gt;alert(123)&lt;/script&gt;</a>',
            ],
        ];
    }

    #[DataProvider('typolinkReturnsCorrectLinksDataProvider')]
    #[Test]
    public function typolinkReturnsCorrectLinksAndUrls(string $linkText, array $configuration, string $expectedResult): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($this->getPreparedRequest());
        self::assertEquals($expectedResult, $subject->typoLink($linkText, $configuration));
    }

    public static function typolinkReturnsCorrectLinkForSpamEncryptedEmailsDataProvider(): array
    {
        return [
            'plain mail without mailto scheme' => [
                [
                    'spamProtectEmailAddresses' => 0,
                    'spamProtectEmailAddresses_atSubst' => '',
                    'spamProtectEmailAddresses_lastDotSubst' => '',
                ],
                'some.body@test.typo3.org',
                'some.body@test.typo3.org',
                '<a href="mailto:some.body@test.typo3.org">some.body@test.typo3.org</a>',
            ],
            'plain mail with mailto scheme' => [
                [
                    'spamProtectEmailAddresses' => 0,
                    'spamProtectEmailAddresses_atSubst' => '',
                    'spamProtectEmailAddresses_lastDotSubst' => '',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="mailto:some.body@test.typo3.org">some.body@test.typo3.org</a>',
            ],
            'plain with at and dot substitution' => [
                [
                    'spamProtectEmailAddresses' => 0,
                    'spamProtectEmailAddresses_atSubst' => '(at)',
                    'spamProtectEmailAddresses_lastDotSubst' => '(dot)',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="mailto:some.body@test.typo3.org">some.body@test.typo3.org</a>',
            ],
            'mono-alphabetic substitution offset +1' => [
                [
                    'spamProtectEmailAddresses' => 1,
                    'spamProtectEmailAddresses_atSubst' => '',
                    'spamProtectEmailAddresses_lastDotSubst' => '',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="#" data-mailto-token="nbjmup+tpnf/cpezAuftu/uzqp4/psh" data-mailto-vector="1">some.body(at)test.typo3.org</a>',
            ],
            'mono-alphabetic substitution offset +1 with at substitution' => [
                [
                    'spamProtectEmailAddresses' => 1,
                    'spamProtectEmailAddresses_atSubst' => '@',
                    'spamProtectEmailAddresses_lastDotSubst' => '',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="#" data-mailto-token="nbjmup+tpnf/cpezAuftu/uzqp4/psh" data-mailto-vector="1">some.body@test.typo3.org</a>',
            ],
            'mono-alphabetic substitution offset +1 with at and dot substitution' => [
                [
                    'spamProtectEmailAddresses' => 1,
                    'spamProtectEmailAddresses_atSubst' => '(at)',
                    'spamProtectEmailAddresses_lastDotSubst' => '(dot)',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="#" data-mailto-token="nbjmup+tpnf/cpezAuftu/uzqp4/psh" data-mailto-vector="1">some.body(at)test.typo3(dot)org</a>',
            ],
            'mono-alphabetic substitution offset -1 with at and dot substitution' => [
                [
                    'spamProtectEmailAddresses' => -1,
                    'spamProtectEmailAddresses_atSubst' => '(at)',
                    'spamProtectEmailAddresses_lastDotSubst' => '(dot)',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="#" data-mailto-token="lzhksn9rnld-ancxZsdrs-sxon2-nqf" data-mailto-vector="-1">some.body(at)test.typo3(dot)org</a>',
            ],
            'mono-alphabetic substitution offset -1 with at and dot markup substitution' => [
                [
                    'spamProtectEmailAddresses' => -1,
                    'spamProtectEmailAddresses_atSubst' => '<span class="at"></span>',
                    'spamProtectEmailAddresses_lastDotSubst' => '<span class="dot"></span>',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="#" data-mailto-token="lzhksn9rnld-ancxZsdrs-sxon2-nqf" data-mailto-vector="-1">some.body<span class="at"></span>test.typo3<span class="dot"></span>org</a>',
            ],
            'mono-alphabetic substitution offset 2 with at and dot substitution and encoded subject' => [
                [
                    'spamProtectEmailAddresses' => 2,
                    'spamProtectEmailAddresses_atSubst' => '(at)',
                    'spamProtectEmailAddresses_lastDotSubst' => '(dot)',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org?subject=foo%20bar',
                '<a href="#" data-mailto-token="ocknvq,uqog0dqfaBvguv0varq50qti?uwdlgev=hqq%42dct" data-mailto-vector="2">some.body(at)test.typo3(dot)org</a>',
            ],
        ];
    }

    #[DataProvider('typolinkReturnsCorrectLinkForSpamEncryptedEmailsDataProvider')]
    #[Test]
    public function typolinkReturnsCorrectLinkForSpamEncryptedEmails(array $tsfeConfig, string $linkText, string $parameter, string $expected): void
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setConfigArray($tsfeConfig);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $frontendTypoScript);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals($expected, $subject->typoLink($linkText, ['parameter' => $parameter]));
    }

    public static function typolinkReturnsCorrectLinksForFilesWithAbsRefPrefixDataProvider(): array
    {
        return [
            'Link to file' => [
                'My file',
                [
                    'directImageLink' => false,
                    'parameter' => 'fileadmin/foo.bar',
                ],
                '/',
                '<a href="/fileadmin/foo.bar">My file</a>',
            ],
            'Link to file with longer absRefPrefix' => [
                'My file',
                [
                    'directImageLink' => false,
                    'parameter' => 'fileadmin/foo.bar',
                ],
                '/sub/',
                '<a href="/sub/fileadmin/foo.bar">My file</a>',
            ],
            'Link to absolute file' => [
                'My file',
                [
                    'directImageLink' => false,
                    'parameter' => '/images/foo.bar',
                ],
                '/',
                '<a href="/images/foo.bar">My file</a>',
            ],
            'Link to absolute file with longer absRefPrefix' => [
                'My file',
                [
                    'directImageLink' => false,
                    'parameter' => '/images/foo.bar',
                ],
                '/sub/',
                '<a href="/images/foo.bar">My file</a>',
            ],
            'Link to absolute file with identical longer absRefPrefix' => [
                'My file',
                [
                    'directImageLink' => false,
                    'parameter' => '/sub/fileadmin/foo.bar',
                ],
                '/sub/',
                '<a href="/sub/fileadmin/foo.bar">My file</a>',
            ],
            'Link to file with empty absRefPrefix' => [
                'My file',
                [
                    'directImageLink' => false,
                    'parameter' => 'fileadmin/foo.bar',
                ],
                '',
                '<a href="fileadmin/foo.bar">My file</a>',
            ],
            'Link to absolute file with empty absRefPrefix' => [
                'My file',
                [
                    'directImageLink' => false,
                    'parameter' => '/fileadmin/foo.bar',
                ],
                '',
                '<a href="/fileadmin/foo.bar">My file</a>',
            ],
            'Link to file with attributes with absRefPrefix' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'title' => 'Title of the file',
                ],
                '/',
                '<a href="/fileadmin/foo.bar" class="file-class" title="Title of the file">My file</a>',
            ],
            'Link to file with attributes with longer absRefPrefix' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'title' => 'Title of the file',
                ],
                '/sub/',
                '<a href="/sub/fileadmin/foo.bar" class="file-class" title="Title of the file">My file</a>',
            ],
            'Link to absolute file with attributes with absRefPrefix' => [
                'My file',
                [
                    'parameter' => '/images/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'title' => 'Title of the file',
                ],
                '/',
                '<a href="/images/foo.bar" class="file-class" title="Title of the file">My file</a>',
            ],
            'Link to absolute file with attributes with longer absRefPrefix' => [
                'My file',
                [
                    'parameter' => '/images/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'title' => 'Title of the file',
                ],
                '/sub/',
                '<a href="/images/foo.bar" class="file-class" title="Title of the file">My file</a>',
            ],
            'Link to absolute file with attributes with identical longer absRefPrefix' => [
                'My file',
                [
                    'parameter' => '/sub/fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'title' => 'Title of the file',
                ],
                '/sub/',
                '<a href="/sub/fileadmin/foo.bar" class="file-class" title="Title of the file">My file</a>',
            ],
        ];
    }

    #[DataProvider('typolinkReturnsCorrectLinksForFilesWithAbsRefPrefixDataProvider')]
    #[Test]
    public function typolinkReturnsCorrectLinksForFilesWithAbsRefPrefix(string $linkText, array $configuration, string $absRefPrefix, string $expectedResult): void
    {
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray(['absRefPrefix' => $absRefPrefix]);

        $subject = $this->get(ContentObjectRenderer::class);
        $request = new ServerRequest();
        $request = $request->withAttribute('frontend.typoscript', $typoScript);
        $request = $request->withAttribute('currentContentObject', $subject);
        $subject->setRequest($request);
        self::assertEquals($expectedResult, $subject->typoLink($linkText, $configuration));
    }

    public static function typoLinkProperlyEncodesLinkResultDataProvider(): array
    {
        return [
            'Link to file' => [
                'My file',
                [
                    'directImageLink' => false,
                    'parameter' => '/fileadmin/foo.bar',
                    'returnLast' => 'result',
                ],
                json_encode([
                    'href' => '/fileadmin/foo.bar',
                    'target' => null,
                    'class' => null,
                    'title' => null,
                    'linkText' => 'My file',
                    'additionalAttributes' => [],
                ]),
            ],
            'Link example' => [
                'My example',
                [
                    'directImageLink' => false,
                    'parameter' => 'https://example.tld',
                    'returnLast' => 'result',
                ],
                json_encode([
                    'href' => 'https://example.tld',
                    'target' => null,
                    'class' => null,
                    'title' => null,
                    'linkText' => 'My example',
                    'additionalAttributes' => [],
                ]),
            ],
            'Link to file with attributes' => [
                'My file',
                [
                    'parameter' => '/fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'returnLast' => 'result',
                ],
                json_encode([
                    'href' => '/fileadmin/foo.bar',
                    'target' => null,
                    'class' => 'file-class',
                    'title' => null,
                    'linkText' => 'My file',
                    'additionalAttributes' => [],
                ]),
            ],
            'Link parsing' => [
                'Url',
                [
                    'parameter' => 'https://example.com _blank css-class "test title"',
                    'returnLast' => 'result',
                ],
                json_encode([
                    'href' => 'https://example.com',
                    'target' => '_blank',
                    'class' => 'css-class',
                    'title' => 'test title',
                    'linkText' => 'Url',
                    'additionalAttributes' => ['rel' => 'noreferrer'],
                ]),
            ],
        ];
    }

    #[DataProvider('typoLinkProperlyEncodesLinkResultDataProvider')]
    #[Test]
    public function typoLinkProperlyEncodesLinkResult(string $linkText, array $configuration, string $expectedResult): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($this->getPreparedRequest());
        self::assertEquals($expectedResult, $subject->typoLink($linkText, $configuration));
    }

    #[Test]
    public function searchWhereWithTooShortSearchWordWillReturnValidWhereStatement(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($this->getPreparedRequest());
        $subject->start([], 'tt_content');

        $expected = '';
        $actual = $subject->searchWhere('ab', 'header,bodytext', 'tt_content');
        self::assertEquals($expected, $actual);
    }

    #[Test]
    public function libParseFuncProperlyKeepsTagsUnescaped(): void
    {
        $libParseFuncConfig = [
            'htmlSanitize' => '1',
            'makelinks' => '1',
            'makelinks.' => [
                'http.' => [
                    'keep' => '{$styles.content.links.keep}',
                    'extTarget' => '',
                    'mailto.' => [
                        'keep' => 'path',
                    ],
                ],
            ],
            'tags.' => [
                'link' => 'TEXT',
                'link.' => [
                    'current' => '1',
                    'typolink.' => [
                        'parameter.' => [
                            'data' => 'parameters : allParams',
                        ],
                    ],
                    'parseFunc.' => [
                        'constants' => '1',
                    ],
                ],
                'a' => 'TEXT',
                'a.' => [
                    'current' => '1',
                    'typolink.' => [
                        'parameter.' => [
                            'data' => 'parameters:href',
                        ],
                    ],
                ],
            ],
            'allowTags' => 'a, abbr, acronym, address, article, aside, b, bdo, big, blockquote, br, caption, center, cite, code, col, colgroup, dd, del, dfn, dl, div, dt, em, font, footer, header, h1, h2, h3, h4, h5, h6, hr, i, img, ins, kbd, label, li, link, meta, nav, ol, p, pre, q, samp, sdfield, section, small, span, strike, strong, style, sub, sup, table, thead, tbody, tfoot, td, th, tr, title, tt, u, ul, var',
            'denyTags' => '*',
            'sword' => '<span class="csc-sword">|</span>',
            'constants' => '1',
            'nonTypoTagStdWrap.' => [
                'HTMLparser' => '1',
                'HTMLparser.' => [
                    'keepNonMatchedTags' => '1',
                    'htmlSpecialChars' => '2',
                ],
            ],
        ];
        $subject = $this->get(ContentObjectRenderer::class);
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = $this->getPreparedRequest()->withAttribute('frontend.typoscript', $typoScript);
        $subject->setRequest($request);
        $input = 'This is a simple inline text, no wrapping configured';
        $result = $subject->parseFunc($input, $libParseFuncConfig);
        self::assertEquals($input, $result);
        $input = '<p>A one liner paragraph</p>';
        $result = $subject->parseFunc($input, $libParseFuncConfig);
        self::assertEquals($input, $result);
        $input = "A one liner paragraph\nAnd another one";
        $result = $subject->parseFunc($input, $libParseFuncConfig);
        self::assertEquals($input, $result);
        $input = '<p>A one liner paragraph</p><p>And another one and the spacing is kept</p>';
        $result = $subject->parseFunc($input, $libParseFuncConfig);
        self::assertEquals($input, $result);
        $input = '<p>text to a <a href="https://www.example.com">an external page</a>.</p>';
        $result = $subject->parseFunc($input, $libParseFuncConfig);
        self::assertEquals($input, $result);
    }

    public static function checkIfReturnsExpectedValuesDataProvider(): iterable
    {
        yield 'isNull returns true if stdWrap returns null' => [
            'configuration' => [
                'isNull.' => [
                    'field' => 'unknown',
                ],
            ],
            'expected' => true,
        ];
        yield 'isNull returns false if stdWrap returns not null' => [
            'configuration' => [
                'isNull.' => [
                    'field' => 'known',
                ],
            ],
            'expected' => false,
        ];
    }

    #[DataProvider('checkIfReturnsExpectedValuesDataProvider')]
    #[Test]
    public function checkIfReturnsExpectedValues(array $configuration, bool $expected): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->data = [
            'known' => 'somevalue',
        ];
        self::assertSame($expected, $subject->checkIf($configuration));
    }

    public static function imageLinkWrapWrapsTheContentAsConfiguredDataProvider(): iterable
    {
        $width = 900;
        $height = 600;
        $processingWidth = $width . 'm';
        $processingHeight = $height . 'm';
        $defaultConfiguration = [
            'wrap' => '<a href="javascript:close();"> | </a>',
            'width' => $processingWidth,
            'height' => $processingHeight,
            'JSwindow' => '1',
            'JSwindow.' => [
                'newWindow' => '0',
            ],
            'crop.' => [
                'data' => 'file:current:crop',
            ],
            'linkParams.' => [
                'ATagParams.' => [
                    'dataWrap' => 'class="lightbox" rel="lightbox[{field:uid}]"',
                ],
            ],
            'enable' => true,
        ];
        $imageTag = '<img class="image-embed-item" src="/fileadmin/_processed_/team-t3board10-processed.jpg" width="500" height="300" loading="lazy" alt="" />';
        $windowFeatures = 'width=' . $width . ',height=' . $height . ',status=0,menubar=0';

        $configurationEnableFalse = $defaultConfiguration;
        $configurationEnableFalse['enable'] = false;
        yield 'enable => false configuration returns image tag as is.' => [
            'content' => $imageTag,
            'configuration' => $configurationEnableFalse,
            'expected' => [$imageTag => true],
        ];

        yield 'image is wrapped with link tag.' => [
            'content' => $imageTag,
            'configuration' => $defaultConfiguration,
            'expected' => [
                '<a href="index.php?eID=tx_cms_showpic&amp;file=1' => true,
                $imageTag . '</a>' => true,
                'data-window-features="' . $windowFeatures => true,
                'data-window-target="thePicture"' => true,
                ' target="thePicture' => true,
            ],
        ];

        $paramsConfiguration = $defaultConfiguration;
        $windowFeaturesOverrides = 'width=420,status=1,menubar=1,foo=bar';
        $windowFeaturesOverriddenExpected = 'width=420,height=' . $height . ',status=1,menubar=1,foo=bar';
        $paramsConfiguration['JSwindow.']['params'] = $windowFeaturesOverrides;
        yield 'JSWindow.params overrides windowParams' => [
            'content' => $imageTag,
            'configuration' => $paramsConfiguration,
            'expected' => [
                'data-window-features="' . $windowFeaturesOverriddenExpected => true,
            ],
        ];

        $newWindowConfiguration = $defaultConfiguration;
        $newWindowConfiguration['JSwindow.']['newWindow'] = '1';
        yield 'data-window-target is not "thePicture" if newWindow = 1 but an md5 hash of the url.' => [
            'content' => $imageTag,
            'configuration' => $newWindowConfiguration,
            'expected' => [
                'data-window-target="thePicture' => false,
            ],
        ];

        $newWindowConfiguration = $defaultConfiguration;
        $newWindowConfiguration['JSwindow.']['expand'] = '20,40';
        $windowFeaturesExpand = 'width=' . ($width + 20) . ',height=' . ($height + 40) . ',status=0,menubar=0';
        yield 'expand increases the window size by its value' => [
            'content' => $imageTag,
            'configuration' => $newWindowConfiguration,
            'expected' => [
                'data-window-features="' . $windowFeaturesExpand => true,
            ],
        ];

        $directImageLinkConfiguration = $defaultConfiguration;
        $directImageLinkConfiguration['directImageLink'] = '1';
        yield 'Direct image link does not use eID and links directly to the image.' => [
            'content' => $imageTag,
            'configuration' => $directImageLinkConfiguration,
            'expected' => [
                'index.php?eID=tx_cms_showpic&amp;file=1' => false,
                '<a href="fileadmin/_processed_' => true,
                'data-window-url="fileadmin/_processed_' => true,
            ],
        ];

        // @todo Error: Object of class TYPO3\CMS\Core\Resource\FileReference could not be converted to string
        //        $altUrlConfiguration = $defaultConfiguration;
        //        $altUrlConfiguration['JSwindow.']['altUrl'] = '/alternative-url';
        //        yield 'JSwindow.altUrl forces an alternative url.' => [
        //            'content' => $imageTag,
        //            'configuration' => $altUrlConfiguration,
        //            'expected' => [
        //                '<a href="/alternative-url' => true,
        //                'data-window-url="/alternative-url' => true,
        //            ],
        //        ];

        $altUrlConfigurationNoDefault = $defaultConfiguration;
        $altUrlConfigurationNoDefault['JSwindow.']['altUrl'] = '/alternative-url';
        $altUrlConfigurationNoDefault['JSwindow.']['altUrl_noDefaultParams'] = '1';
        yield 'JSwindow.altUrl_noDefaultParams removes the default ?file= params' => [
            'content' => $imageTag,
            'configuration' => $altUrlConfigurationNoDefault,
            'expected' => [
                '<a href="/alternative-url' => true,
                'data-window-url="/alternative-url' => true,
                'data-window-url="/alternative-url?file=' => false,
            ],
        ];

        $targetConfiguration = $defaultConfiguration;
        $targetConfiguration['target'] = 'myTarget';
        yield 'Setting target overrides the default target "thePicture.' => [
            'content' => $imageTag,
            'configuration' => $targetConfiguration,
            'expected' => [
                ' target="myTarget"' => true,
                'data-window-target="thePicture"' => true,
            ],
        ];

        $parameters = [
            'sample' => '1',
            'width' => $processingWidth,
            'height' => $processingHeight,
            'effects' => 'gamma=1.3 | flip | rotate=180',
            'bodyTag' => '<body style="margin:0; background:#fff;">',
            'title' => 'My Title',
            'wrap' => '<div class="my-wrap">|</div>',
            'crop' => '{"default":{"cropArea":{"x":0,"y":0,"width":1,"height":1},"selectedRatio":"NaN","focusArea":null}}',
        ];
        $parameterConfiguration = array_replace($defaultConfiguration, $parameters);
        $expectedParameters = $parameters;
        $expectedParameters['sample'] = 1;
        yield 'Setting one of [width, height, effects, bodyTag, title, wrap, crop, sample] will add them to the parameter list.' => [
            'content' => $imageTag,
            'configuration' => $parameterConfiguration,
            'expected' => [],
            'expectedParams' => $expectedParameters,
        ];

        $stdWrapConfiguration = $defaultConfiguration;
        $stdWrapConfiguration['stdWrap.'] = [
            'append' => 'TEXT',
            'append.' => [
                'value' => 'appendedString',
            ],
        ];
        yield 'stdWrap is called upon the whole content.' => [
            'content' => $imageTag,
            'configuration' => $stdWrapConfiguration,
            'expected' => [
                'appendedString' => true,
            ],
        ];
    }

    #[DataProvider('imageLinkWrapWrapsTheContentAsConfiguredDataProvider')]
    #[Test]
    public function imageLinkWrapWrapsTheContentAsConfigured(string $content, array $configuration, array $expected, array $expectedParams = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/FileReferences.csv');
        $fileReferenceData = [
            'uid' => 1,
            'uid_local' => 1,
            'crop' => '{"default":{"cropArea":{"x":0,"y":0,"width":1,"height":1},"selectedRatio":"NaN","focusArea":null}}',
        ];
        $fileReference = new FileReference($fileReferenceData);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setCurrentFile($fileReference);
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = $this->getPreparedRequest()->withAttribute('frontend.typoscript', $typoScript);
        $subject->setRequest($request);
        $result = $subject->imageLinkWrap($content, $fileReference, $configuration);

        foreach ($expected as $expectedString => $shouldContain) {
            if ($shouldContain) {
                self::assertStringContainsString($expectedString, $result);
            } else {
                self::assertStringNotContainsString($expectedString, $result);
            }
        }

        if ($expectedParams !== []) {
            preg_match('@href="(.*)"@U', $result, $matches);
            self::assertArrayHasKey(1, $matches);
            $url = parse_url(html_entity_decode($matches[1]));
            parse_str($url['query'], $queryResult);
            $base64_string = implode('', $queryResult['parameters']);
            $base64_decoded = base64_decode($base64_string);
            $jsonDecodedArray = json_decode($base64_decoded, true);
            self::assertSame($expectedParams, $jsonDecodedArray);
        }
    }

    #[Test]
    public function getImgResourceRespectsFileReferenceObjectCropData(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/FileReferences.csv');
        $fileReferenceData = [
            'uid' => 1,
            'uid_local' => 1,
            'crop' => '{"default":{"cropArea":{"x":0,"y":0,"width":0.5,"height":0.5},"selectedRatio":"NaN","focusArea":null}}',
        ];
        $fileReference = new FileReference($fileReferenceData);

        $subject = $this->get(ContentObjectRenderer::class);
        $result = $subject->getImgResource($fileReference, []);

        $expectedWidth = 512;
        $expectedHeight = 342;

        self::assertEquals($expectedWidth, $result->getWidth());
        self::assertEquals($expectedHeight, $result->getHeight());
    }

    #[Test]
    public function afterContentObjectRendererInitializedEventIsCalled(): void
    {
        $afterContentObjectRendererInitializedEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-content-object-renderer-initialized-listener',
            static function (AfterContentObjectRendererInitializedEvent $event) use (&$afterContentObjectRendererInitializedEvent) {
                $afterContentObjectRendererInitializedEvent = $event;
                $afterContentObjectRendererInitializedEvent->getContentObjectRenderer()->data['foo'] = 'baz';
                $afterContentObjectRendererInitializedEvent->getContentObjectRenderer()->setCurrentVal('foo current val');
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterContentObjectRendererInitializedEvent::class, 'after-content-object-renderer-initialized-listener');

        $subject = $this->get(ContentObjectRenderer::class);
        $subject->start(['foo' => 'bar'], 'aTable');

        self::assertInstanceOf(AfterContentObjectRendererInitializedEvent::class, $afterContentObjectRendererInitializedEvent);

        $modifiedContentObjectRenderer = $afterContentObjectRendererInitializedEvent->getContentObjectRenderer();

        self::assertEquals($subject, $modifiedContentObjectRenderer);
        self::assertEquals(
            [
                'foo' => 'baz',
                $modifiedContentObjectRenderer->currentValKey => 'foo current val',
            ],
            $modifiedContentObjectRenderer->data
        );
        self::assertEquals('aTable', $modifiedContentObjectRenderer->getCurrentTable());
        self::assertEquals('foo current val', $modifiedContentObjectRenderer->getCurrentVal());
    }

    #[Test]
    public function afterGetDataResolvedEventIsCalled(): void
    {
        $afterGetDataResolvedEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-get-data-resolved-listener',
            static function (AfterGetDataResolvedEvent $event) use (&$afterGetDataResolvedEvent) {
                $afterGetDataResolvedEvent = $event;
                $event->setResult('modified-result');
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterGetDataResolvedEvent::class, 'after-get-data-resolved-listener');

        $subject = $this->get(ContentObjectRenderer::class);
        $subject->start(['foo' => 'bar'], 'aTable');
        $subject->getData('field:title', ['title' => 'title']);

        self::assertInstanceOf(AfterGetDataResolvedEvent::class, $afterGetDataResolvedEvent);
        self::assertEquals($subject, $afterGetDataResolvedEvent->getContentObjectRenderer());
        self::assertEquals('field:title', $afterGetDataResolvedEvent->getParameterString());
        self::assertEquals(['title' => 'title'], $afterGetDataResolvedEvent->getAlternativeFieldArray());
        self::assertEquals('modified-result', $afterGetDataResolvedEvent->getResult());
    }

    #[Test]
    public function afterImageResourceResolvedEventIsCalled(): void
    {
        $afterImageResourceResolvedEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-image-resource-resolved-listener',
            static function (AfterImageResourceResolvedEvent $event) use (&$afterImageResourceResolvedEvent) {
                $afterImageResourceResolvedEvent = $event;
                $modifiedImageResource = $afterImageResourceResolvedEvent->getImageResource()?->withPublicUrl('modified-public-url');
                $event->setImageResource($modifiedImageResource);
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(AfterImageResourceResolvedEvent::class, 'after-image-resource-resolved-listener');

        $subject = $this->get(ContentObjectRenderer::class);
        $subject->start(['foo' => 'bar'], 'aTable');
        $subject->getImgResource('GIFBUILDER', ['foo' => 'bar']);

        self::assertInstanceOf(AfterImageResourceResolvedEvent::class, $afterImageResourceResolvedEvent);
        self::assertEquals('GIFBUILDER', $afterImageResourceResolvedEvent->getFile());
        self::assertEquals(['foo' => 'bar'], $afterImageResourceResolvedEvent->getFileArray());
        self::assertEquals('modified-public-url', $afterImageResourceResolvedEvent->getImageResource()->getPublicUrl());
    }

    #[Test]
    public function enhanceStdWrapEventIsCalled(): void
    {
        $wrap = '<h1>|</h1>';
        $content = 'modified content';
        $enhanceStdWrapEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'enhance-stdWrap-listener',
            static function (EnhanceStdWrapEvent $event) use (&$enhanceStdWrapEvent, $content) {
                $enhanceStdWrapEvent = $event;
                $event->setContent($content);
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(EnhanceStdWrapEvent::class, 'enhance-stdWrap-listener');

        $subject = $this->get(ContentObjectRenderer::class);
        $result = $subject->stdWrap('Test', ['wrap' => $wrap]);

        self::assertInstanceOf(EnhanceStdWrapEvent::class, $enhanceStdWrapEvent);
        self::assertEquals($content, $result);
        self::assertEquals($content, $enhanceStdWrapEvent->getContent());
        self::assertEquals($wrap, $enhanceStdWrapEvent->getConfiguration()['wrap']);
        self::assertEquals($subject, $enhanceStdWrapEvent->getContentObjectRenderer());
    }

    public static function getDataWithTypeGpDataProvider(): array
    {
        return [
            'Value in get-data' => ['onlyInGet', 'GetValue'],
            'Value in post-data' => ['onlyInPost', 'PostValue'],
            'Value in post-data overriding get-data' => ['inGetAndPost', 'ValueInPost'],
        ];
    }

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
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals($expectedValue, $subject->getData('gp:' . $key, []));
    }

    #[Test]
    public function getDataWithTypeGetenv(): void
    {
        $envName = StringUtility::getUniqueId('frontendtest');
        $value = StringUtility::getUniqueId('someValue');
        putenv($envName . '=' . $value);
        $subject = $this->get(ContentObjectRenderer::class);
        self::assertEquals($value, $subject->getData('getenv:' . $envName, []));
    }

    #[Test]
    public function getDataWithTypeGetindpenv(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        GeneralUtility::setIndpEnv('SCRIPT_FILENAME', 'dummyPath');
        self::assertEquals('dummyPath', $subject->getData('getindpenv:SCRIPT_FILENAME', []));
    }

    #[Test]
    public function getDataWithTypeField(): void
    {
        $key = 'someKey';
        $value = 'someValue';
        $field = [$key => $value];
        self::assertEquals($value, $this->get(ContentObjectRenderer::class)->getData('field:' . $key, $field));
    }

    #[Test]
    public function getDataWithTypeFieldAndFieldIsMultiDimensional(): void
    {
        $key = 'somekey|level1|level2';
        $value = 'somevalue';
        $field = ['somekey' => ['level1' => ['level2' => 'somevalue']]];
        self::assertEquals($value, $this->get(ContentObjectRenderer::class)->getData('field:' . $key, $field));
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

    #[DataProvider('getDataWithTypeFileReturnsUidOfFileObjectDataProvider')]
    #[Test]
    public function getDataWithTypeFileReturnsUidOfFileObject(string $typoScriptPath): void
    {
        $uid = rand(10, 100);
        $file = $this->createMock(File::class);
        $file->expects($this->once())->method('getUid')->willReturn($uid);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setCurrentFile($file);
        self::assertEquals($uid, $subject->getData($typoScriptPath, []));
    }

    #[Test]
    public function getDataWithTypeParameters(): void
    {
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->parameters[$key] = $value;
        self::assertEquals($value, $subject->getData('parameters:' . $key, []));
    }

    #[Test]
    public function getDataWithTypeRegister(): void
    {
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $registerStack = new RegisterStack();
        $registerStack->current()->set($key, $value);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.register.stack', $registerStack);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals($value, $subject->getData('register:' . $key, []));
    }

    #[Test]
    public function getDataWithTypeSession(): void
    {
        $frontendUser = $this->createMock(FrontendUserAuthentication::class);
        $frontendUser->expects($this->once())->method('getSessionData')->with('myext')->willReturn([
            'mydata' => [
                'someValue' => 42,
            ],
        ]);
        $request = (new ServerRequest())->withAttribute('frontend.user', $frontendUser);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals(42, $subject->getData('session:myext|mydata|someValue', []));
    }

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
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals(2, $subject->getData('level', []));
    }

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
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals('', $subject->getData('leveltitle:-1'));
        // since "title3" is not set, it will slide to "title2"
        self::assertEquals('title2', $subject->getData('leveltitle:-1,slide'));
    }

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
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals('', $subject->getData('levelmedia:-1'));
        // since "title3" is not set, it will slide to "title2"
        self::assertEquals('media2', $subject->getData('levelmedia:-1,slide'));
    }

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
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals(3, $subject->getData('leveluid:-1'));
        // every element will have a uid - so adding slide doesn't really make sense, just for completeness
        self::assertEquals(3, $subject->getData('leveluid:-1,slide'));
    }

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
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals('', $subject->getData('levelfield:-1,testfield'));
        self::assertEquals('field2', $subject->getData('levelfield:-1,testfield,slide'));
    }

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
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals('field2', $subject->getData('fullrootline:-1,testfield'));
    }

    #[Test]
    public function getDataWithTypeDate(): void
    {
        $pageInformation = new PageInformation();
        $pageInformation->setPageRecord([]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        $format = 'Y-M-D';
        $defaultFormat = 'd/m Y';
        self::assertEquals(date($format, $GLOBALS['EXEC_TIME']), $subject->getData('date:' . $format));
        self::assertEquals(date($defaultFormat, $GLOBALS['EXEC_TIME']), $subject->getData('date', []));
    }

    #[Test]
    public function getDataWithTypePage(): void
    {
        $pageInformation = new PageInformation();
        $uid = random_int(0, mt_getrandmax());
        $pageInformation->setPageRecord(['uid' => $uid]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals($uid, $subject->getData('page:uid'));
    }

    #[Test]
    public function getDataWithTypeCurrent(): void
    {
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->data[$key] = $value;
        $subject->currentValKey = $key;
        self::assertEquals($value, $subject->getData('current', []));
    }

    #[Test]
    public function getDataWithTypeDbReturnsCorrectTitle()
    {
        $dummyRecord = ['uid' => 5, 'title' => 'someTitle'];
        $pageRepository = $this->createMock(PageRepository::class);
        GeneralUtility::addInstance(PageRepository::class, $pageRepository);
        $pageRepository->expects($this->once())->method('getRawRecord')->with('tt_content', '106')->willReturn($dummyRecord);
        $subject = $this->get(ContentObjectRenderer::class);
        self::assertSame('someTitle', $subject->getData('db:tt_content:106:title', []));
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
        self::assertSame('', $this->get(ContentObjectRenderer::class)->getData($identifier, []));
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
        $dummyRecord = ['uid' => 5, 'title' => 'someTitle'];
        $pageRepository = $this->createMock(PageRepository::class);
        GeneralUtility::addInstance(PageRepository::class, $pageRepository);
        $pageRepository->expects($this->once())->method('getRawRecord')->with('tt_content', '106')->willReturn($dummyRecord);
        $subject = $this->get(ContentObjectRenderer::class);
        self::assertSame('', $subject->getData($identifier, []));
    }

    #[Test]
    public function getDataWithTypeLll(): void
    {
        $request = new ServerRequest('https://example.com');
        $language = $this->createMock(SiteLanguage::class);
        $request = $request->withAttribute('language', $language);
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $languageServiceFactory = $this->createMock(LanguageServiceFactory::class);
        $languageServiceMock = $this->createMock(LanguageService::class);
        $languageServiceFactory->expects($this->once())->method('createFromSiteLanguage')->with(self::anything())->willReturn($languageServiceMock);
        $this->get('service_container')->set(LanguageServiceFactory::class, $languageServiceFactory);
        $languageServiceMock->expects($this->once())->method('sL')->with('LLL:' . $key)->willReturn($value);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals($value, $subject->getData('lll:' . $key, []));
    }

    #[Test]
    public function getDataWithTypeContext(): void
    {
        $context = $this->get(Context::class);
        $context->setAspect('workspace', new WorkspaceAspect(3));
        $context->setAspect('frontend.user', new UserAspect(new FrontendUserAuthentication(), [0, -1]));
        $subject = $this->get(ContentObjectRenderer::class);
        self::assertEquals(3, $subject->getData('context:workspace:id', []));
        self::assertEquals('0,-1', $subject->getData('context:frontend.user:groupIds', []));
        self::assertFalse($subject->getData('context:frontend.user:isLoggedIn', []));
        self::assertSame('', $subject->getData('context:frontend.user:foozball', []));
    }

    #[Test]
    public function getDataWithTypeSite(): void
    {
        $site = new Site('my-site', 123, [
            'base' => 'http://example.com',
            'custom' => [
                'config' => [
                    'nested' => 'yeah',
                ],
            ],
        ]);
        $request = (new ServerRequest('https://example.com'))->withAttribute('site', $site);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals('http://example.com', $subject->getData('site:base', []));
        self::assertEquals('yeah', $subject->getData('site:custom.config.nested', []));
    }

    #[Test]
    public function getDataWithTypeSiteWithBaseVariants(): void
    {
        $packageManager = new PackageManager(new DependencyOrderingService());
        GeneralUtility::addInstance(ProviderConfigurationLoader::class, new ProviderConfigurationLoader($packageManager, new NullFrontend('core'), 'ExpressionLanguageProviders'));
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
        $request = (new ServerRequest('https://example.com'))->withAttribute('site', $site);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals('http://dev.com', $subject->getData('site:base', []));
    }

    #[Test]
    public function getDataWithTypeSiteLanguage(): void
    {
        $request = new ServerRequest('https://example.com');
        $site = new Site('test', 1, [
            'identifier' => 'test',
            'rootPageId' => 1,
            'base' => '/',
            'languages' => [
                [
                    'base' => '/',
                    'languageId' => 1,
                    'locale' => 'de_DE',
                    'title' => 'languageTitle',
                    'navigationTitle' => 'German',
                ],
            ],
        ]);
        $language = $site->getLanguageById(1);
        $request = $request->withAttribute('language', $language);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals('German', $subject->getData('siteLanguage:navigationTitle', []));
        self::assertEquals('de', $subject->getData('siteLanguage:twoLetterIsoCode', []));
        self::assertEquals('de', $subject->getData('siteLanguage:locale:languageCode', []));
        self::assertEquals('de-DE', $subject->getData('siteLanguage:hreflang', []));
        self::assertEquals('de-DE', $subject->getData('siteLanguage:locale:full', []));
    }

    #[Test]
    public function getDataWithTypeSiteLanguageForAlternateHrefLang(): void
    {
        $site = new Site('test', 1, [
            'identifier' => 'test',
            'rootPageId' => 1,
            'base' => '/',
            'languages' => [
                [
                    'base' => '/',
                    'languageId' => 1,
                    'locale' => 'de_DE',
                    'title' => 'languageTitle',
                    'hreflang' => 'en-US',
                    'navigationTitle' => 'German',
                ],
            ],
        ]);
        $language = $site->getLanguageById(1);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('language', $language);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        self::assertEquals('en-US', $subject->getData('siteLanguage:hreflang', []));
        self::assertEquals('de-DE', $subject->getData('siteLanguage:locale:full', []));
    }

    #[Test]
    public function getDataWithTypeParentRecordNumber(): void
    {
        $recordNumber = random_int(0, mt_getrandmax());
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->parentRecordNumber = $recordNumber;
        self::assertEquals($recordNumber, $subject->getData('cobj:parentRecordNumber', []));
    }

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
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        $expectedResult = 'array(3items)0=>array(2items)uid=>1(integer)title=>"title1"(6chars)1=>array(2items)uid=>2(integer)title=>"title2"(6chars)2=>array(2items)uid=>3(integer)title=>""(0chars)';
        DebugUtility::useAnsiColor(false);
        $result = $subject->getData('debug:rootLine');
        $cleanedResult = str_replace(["\r", "\n", "\t", ' '], '', $result);
        self::assertEquals($expectedResult, $cleanedResult);
    }

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
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        $expectedResult = 'array(3items)0=>array(2items)uid=>1(integer)title=>"title1"(6chars)1=>array(2items)uid=>2(integer)title=>"title2"(6chars)2=>array(2items)uid=>3(integer)title=>""(0chars)';
        DebugUtility::useAnsiColor(false);
        $result = $subject->getData('debug:fullRootLine');
        $cleanedResult = str_replace(["\r", "\n", "\t", ' '], '', $result);
        self::assertEquals($expectedResult, $cleanedResult);
    }

    #[Test]
    public function getDataWithTypeDebugData(): void
    {
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->data = [$key => $value];
        $expectedResult = 'array(1item)' . $key . '=>"' . $value . '"(' . strlen($value) . 'chars)';
        DebugUtility::useAnsiColor(false);
        $result = $subject->getData('debug:data', []);
        $cleanedResult = str_replace(["\r", "\n", "\t", ' '], '', $result);
        self::assertEquals($expectedResult, $cleanedResult);
    }

    #[Test]
    public function getDataWithTypeDebugRegister(): void
    {
        $request = new ServerRequest('https://example.com');
        $registerStack = new RegisterStack();
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $registerStack->current()->set($key, $value);
        $request = $request->withAttribute('frontend.register.stack', $registerStack);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        DebugUtility::useAnsiColor(false);
        $result = $subject->getData('debug:register', []);
        self::assertStringContainsString('someKey', $result);
        self::assertStringContainsString('someValue', $result);
    }

    #[Test]
    public function getDataWithTypeDebugPage(): void
    {
        $pageInformation = new PageInformation();
        $uid = random_int(0, mt_getrandmax());
        $pageInformation->setPageRecord(['uid' => $uid]);
        $request = new ServerRequest('https://example.com');
        $request = $request->withAttribute('frontend.page.information', $pageInformation);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        $expectedResult = 'array(1item)uid=>' . $uid . '(integer)';
        DebugUtility::useAnsiColor(false);
        $result = $subject->getData('debug:page');
        $cleanedResult = str_replace(["\r", "\n", "\t", ' '], '', $result);
        self::assertEquals($expectedResult, $cleanedResult);
    }

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
        $subject = $this->get(ContentObjectRenderer::class);
        self::assertSame('Production', $subject->getData('applicationContext', []));
    }

    #[Test]
    public function getDataWithTypeAssetReturnsVersionedUri(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $normalizedParams = $this->createMock(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setConfigArray([
            'absRefPrefix' => 'auto',
        ]);
        $request = new ServerRequest('https://www.example.com/', 'GET');
        $subject->setRequest(
            $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
                    ->withAttribute('normalizedParams', $normalizedParams)
                    ->withAttribute('frontend.typoscript', $frontendTypoScript)
        );
        $testAssetName = 'typo3temp/assets/HappyResourceUri.svg';
        $testAsset = Environment::getPublicPath() . '/' . $testAssetName;
        touch($testAsset);
        $mtime = filemtime($testAsset);
        self::assertSame('/' . $testAssetName . '?' . $mtime, $subject->getData('asset:' . $testAssetName, []));
    }

    #[Test]
    public function getDataWithTypePath(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $normalizedParams = $this->createMock(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setConfigArray([
            'absRefPrefix' => 'auto',
        ]);
        $request = new ServerRequest('https://www.example.com/', 'GET');
        $subject->setRequest(
            $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
                    ->withAttribute('normalizedParams', $normalizedParams)
                    ->withAttribute('frontend.typoscript', $frontendTypoScript)
        );
        $filenameIn = 'EXT:frontend/Resources/Public/Icons/Extension.svg';
        $expectedUrl = '/typo3/sysext/frontend/Resources/Public/Icons/Extension.svg';
        self::assertEquals($expectedUrl, $subject->getData('path:' . $filenameIn, []));
    }

    #[Test]
    public function renderThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1414513947);
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($subject, false);
        $subject->render($contentObjectFixture);
    }

    #[Test]
    public function renderHasExceptionHandlerEnabledByDefaultInProductionContext(): void
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
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($request);
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($subject);
        $subject->render($contentObjectFixture);
    }

    #[Test]
    public function renderContentObjectDoesNotThrowExceptionIfExceptionHandlerIsConfiguredLocally(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($subject);
        $configuration = [
            'exceptionHandler' => '1',
        ];
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $subject->setRequest($request);
        $subject->render($contentObjectFixture, $configuration);
    }

    #[Test]
    public function renderContentObjectDoesNotThrowExceptionIfExceptionHandlerIsConfiguredGlobally(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($subject);
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([
            'contentObjectExceptionHandler' => '1',
        ]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $subject->setRequest($request);
        $subject->render($contentObjectFixture);
    }

    #[Test]
    public function renderWithGlobalExceptionHandlerConfigurationCanBeOverriddenByLocalConfiguration(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1414513947);
        $subject = $this->get(ContentObjectRenderer::class);
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($subject, false);
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([
            'contentObjectExceptionHandler' => '1',
        ]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $subject->setRequest($request);
        $configuration = [
            'exceptionHandler' => '0',
        ];
        $subject->render($contentObjectFixture, $configuration);
    }

    #[Test]
    public function renderWithErrorMessageCanBeCustomized(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($subject);
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $subject->setRequest($request);
        $configuration = [
            'exceptionHandler' => '1',
            'exceptionHandler.' => [
                'errorMessage' => 'New message for testing',
            ],
        ];
        self::assertSame('New message for testing', $subject->render($contentObjectFixture, $configuration));
    }

    #[Test]
    public function renderWithLocalConfigurationOverridesGlobalConfiguration(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($subject);
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([
            'contentObjectExceptionHandler.' => [
                'errorMessage' => 'Global message for testing',
            ],
        ]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $subject->setRequest($request);
        $configuration = [
            'exceptionHandler' => '1',
            'exceptionHandler.' => [
                'errorMessage' => 'New message for testing',
            ],
        ];
        self::assertSame('New message for testing', $subject->render($contentObjectFixture, $configuration));
    }

    #[Test]
    public function renderCanIgnoreExceptions(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1414513947);
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture($subject);
        $configuration = [
            'exceptionHandler' => '1',
            'exceptionHandler.' => [
                'ignoreCodes.' => ['10.' => '1414513947'],
            ],
        ];
        $typoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $typoScript->setConfigArray([]);
        $request = (new ServerRequest())->withAttribute('frontend.typoscript', $typoScript);
        $subject->setRequest($request);
        $subject->render($contentObjectFixture, $configuration);
    }
}
