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

namespace TYPO3\CMS\Frontend\Tests\UnitDeprecated\ContentObject;

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\ContentObject\CaseContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayInternalContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\EditPanelContentObject;
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
use TYPO3\CMS\Frontend\Tests\Unit\ContentObject\ContentObjectRendererTestTrait;
use TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Fixtures\TestSanitizerBuilder;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ContentObjectRendererTest extends UnitTestCase
{
    use ProphecyTrait;
    use ContentObjectRendererTestTrait;

    /**
     * @var bool Reset singletons created by subject
     */
    protected bool $resetSingletonInstances = true;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|AccessibleObjectInterface|ContentObjectRenderer
     */
    private $subject;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TypoScriptFrontendController|AccessibleObjectInterface
     */
    private $frontendControllerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TemplateService
     */
    private $templateServiceMock;

    /**
     * Default content object name -> class name map, shipped with TYPO3 CMS
     *
     * @var array
     */
    private $contentObjectMap = [
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
        'EDITPANEL' => EditPanelContentObject::class,
    ];

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|CacheManager
     */
    private $cacheManager;

    protected bool $backupEnvironment = true;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();

        $site = $this->createSiteWithLanguage([
            'base' => '/',
            'languageId' => 2,
            'locale' => 'en_UK',
            'typo3Language' => 'default',
        ]);

        $GLOBALS['SIM_ACCESS_TIME'] = 1534278180;
        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->templateServiceMock =
            $this->getMockBuilder(TemplateService::class)
                ->setConstructorArgs([null, $packageManagerMock])
                ->addMethods(['linkData'])
                ->getMock();
        $pageRepositoryMock =
            $this->getAccessibleMock(PageRepository::class, ['getRawRecord', 'getMountPointInfo']);
        $this->frontendControllerMock =
            $this->getAccessibleMock(
                TypoScriptFrontendController::class,
                ['sL'],
                [],
                '',
                false
            );
        $this->frontendControllerMock->_set('context', GeneralUtility::makeInstance(Context::class));
        $this->frontendControllerMock->tmpl = $this->templateServiceMock;
        $this->frontendControllerMock->config = [];
        $this->frontendControllerMock->page = [];
        $this->frontendControllerMock->sys_page = $pageRepositoryMock;
        $this->frontendControllerMock->_set('language', $site->getLanguageById(2));
        $GLOBALS['TSFE'] = $this->frontendControllerMock;

        $this->cacheManager = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $this->cacheManager->reveal());

        $this->subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            ['getResourceFactory', 'getEnvironmentVariable'],
            [$this->frontendControllerMock]
        );

        $logger = $this->prophesize(Logger::class);
        $this->subject->setLogger($logger->reveal());
        $request = $this->prophesize(ServerRequestInterface::class);
        $this->subject->setRequest($request->reveal());
        $this->subject->setContentObjectClassMap($this->contentObjectMap);
        $this->subject->start([], 'tt_content');
    }

    /**
     * @return array
     */
    public function _parseFuncReturnsCorrectHtmlDataProvider(): array
    {
        return [
            'Text without tag is wrapped with <p> tag' => [
                'Text without tag',
                $this->getLibParseFunc_RTE(),
                '<p class="bodytext">Text without tag</p>',
            ],
            'Text wrapped with <p> tag remains the same' => [
                '<p class="myclass">Text with &lt;p&gt; tag</p>',
                $this->getLibParseFunc_RTE(),
                '<p class="myclass">Text with &lt;p&gt; tag</p>',
            ],
            'Text with absolute external link' => [
                'Text with <link http://example.com/foo/>external link</link>',
                $this->getLibParseFunc_RTE(),
                '<p class="bodytext">Text with <a href="http://example.com/foo/">external link</a></p>',
            ],
            'Empty lines are not duplicated' => [
                LF,
                $this->getLibParseFunc_RTE(),
                '<p class="bodytext">&nbsp;</p>',
            ],
            'Multiple empty lines with no text' => [
                LF . LF . LF,
                $this->getLibParseFunc_RTE(),
                '<p class="bodytext">&nbsp;</p>' . LF . '<p class="bodytext">&nbsp;</p>' . LF . '<p class="bodytext">&nbsp;</p>',
            ],
            'Empty lines are not duplicated at the end of content' => [
                'test' . LF . LF,
                $this->getLibParseFunc_RTE(),
                '<p class="bodytext">test</p>' . LF . '<p class="bodytext">&nbsp;</p>',
            ],
            'Empty lines are not trimmed' => [
                LF . 'test' . LF,
                $this->getLibParseFunc_RTE(),
                '<p class="bodytext">&nbsp;</p>' . LF . '<p class="bodytext">test</p>' . LF . '<p class="bodytext">&nbsp;</p>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider _parseFuncReturnsCorrectHtmlDataProvider
     * @param string $value
     * @param array $configuration
     * @param string $expectedResult
     */
    public function stdWrap_parseFuncReturnsParsedHtml($value, $configuration, $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->subject->stdWrap_parseFunc($value, $configuration));
    }

    /**
     * Data provider for the parseFuncParsesNestedTagsProperly test
     *
     * @return array multi-dimensional array with test data
     * @see parseFuncParsesNestedTagsProperly
     */
    public function _parseFuncParsesNestedTagsProperlyDataProvider(): array
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

    /**
     * @test
     * @dataProvider _parseFuncParsesNestedTagsProperlyDataProvider
     * @param string $value
     * @param array $configuration
     * @param string $expectedResult
     */
    public function parseFuncParsesNestedTagsProperly(string $value, array $configuration, string $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->subject->stdWrap_parseFunc($value, $configuration));
    }

    /**
     * Data provider for stdWrap_editIcons.
     *
     * @return array
     */
    public function stdWrap_editIconsDataProvider(): array
    {
        $content = StringUtility::getUniqueId('content');
        $editIcons = StringUtility::getUniqueId('editIcons');
        $editIconsArray = [StringUtility::getUniqueId('editIcons.')];
        $will = StringUtility::getUniqueId('will');
        return [
            'standard case calls edit icons' => [
                $will,
                $content,
                ['editIcons' => $editIcons, 'editIcons.' => $editIconsArray],
                true,
                1,
                $editIconsArray,
                $will,
            ],
            'null in editIcons. repalaced by []' => [
                $will,
                $content,
                ['editIcons' => $editIcons, 'editIcons.' => null],
                true,
                1,
                [],
                $will,
            ],
            'missing editIcons. replaced by []' => [
                $will,
                $content,
                ['editIcons' => $editIcons],
                true,
                1,
                [],
                $will,
            ],
            'no user login disables call' => [
                $content,
                $content,
                ['editIcons' => $editIcons, 'editIcons.' => $editIconsArray],
                false,
                0,
                $editIconsArray,
                $will,
            ],
            'empty string in editIcons disables call' => [
                $content,
                $content,
                ['editIcons' => '', 'editIcons.' => $editIconsArray],
                true,
                0,
                $editIconsArray,
                $will,
            ],
            'zero string in editIcons disables call' => [
                $content,
                $content,
                ['editIcons' => '0', 'editIcons.' => $editIconsArray],
                true,
                0,
                $editIconsArray,
                $will,
            ],
        ];
    }

    /**
     * Check if stdWrap_editIcons works properly.
     *
     * Show:
     *
     * - Returns $content as is if:
     *   - beUserLogin is not set
     *   - (bool)$conf['editIcons'] is false
     * - Otherwise:
     *   - Delegates to method editIcons.
     *   - Parameter 1 is $content.
     *   - Parameter 2 is $conf['editIcons'].
     *   - Parameter 3 is $conf['editIcons.'].
     *   - If $conf['editIcons.'] is no array at all, the empty array is used.
     *   - Returns the return value.
     *
     * @test
     * @dataProvider stdWrap_editIconsDataProvider
     * @param string $expect The expected output.
     * @param string $content The given content.
     * @param array $conf The given configuration.
     * @param bool $login Simulate backend user login.
     * @param int $times Times editIcons is called (0 or 1).
     * @param array $param3 The expected third parameter.
     * @param string $will Return value of editIcons.
     */
    public function stdWrap_editIcons(
        string $expect,
        string $content,
        array $conf,
        bool $login,
        int $times,
        array $param3,
        string $will
    ): void {
        if ($login) {
            $backendUser = new BackendUserAuthentication();
            $backendUser->user['uid'] = 13;
            GeneralUtility::makeInstance(Context::class)->setAspect('backend.user', new UserAspect($backendUser));
        } else {
            GeneralUtility::makeInstance(Context::class)->setAspect('backend.user', new UserAspect());
        }
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['editIcons'])->getMock();
        $subject
            ->expects(self::exactly($times))
            ->method('editIcons')
            ->with($content, $conf['editIcons'], $param3)
            ->willReturn($will);
        self::assertSame(
            $expect,
            $subject->stdWrap_editIcons($content, $conf)
        );
    }

    /**
     * Data provider for stdWrap_editPanel.
     *
     * @return array [$expect, $content, $login, $times, $will]
     */
    public function stdWrap_editPanelDataProvider(): array
    {
        $content = StringUtility::getUniqueId('content');
        $will = StringUtility::getUniqueId('will');
        return [
            'standard case calls edit icons' => [
                $will,
                $content,
                true,
                1,
                $will,
            ],
            'no user login disables call' => [
                $content,
                $content,
                false,
                0,
                $will,
            ],
        ];
    }

    /**
     * Check if stdWrap_editPanel works properly.
     *
     * Show:
     *
     * - Returns $content as is if:
     *   - beUserLogin is not set
     * - Otherwise:
     *   - Delegates to method editPanel.
     *   - Parameter 1 is $content.
     *   - Parameter 2 is $conf['editPanel'].
     *   - Returns the return value.
     *
     * @test
     * @dataProvider stdWrap_editPanelDataProvider
     * @param string $expect The expected output.
     * @param string $content The given content.
     * @param bool $login Simulate backend user login.
     * @param int $times Times editPanel is called (0 or 1).
     * @param string $will Return value of editPanel.
     */
    public function stdWrap_editPanel(
        string $expect,
        string $content,
        bool $login,
        int $times,
        string $will
    ): void {
        if ($login) {
            $backendUser = new BackendUserAuthentication();
            $backendUser->user['uid'] = 13;
            GeneralUtility::makeInstance(Context::class)->setAspect('backend.user', new UserAspect($backendUser));
        } else {
            GeneralUtility::makeInstance(Context::class)->setAspect('backend.user', new UserAspect());
        }
        $conf = ['editPanel.' => [StringUtility::getUniqueId('editPanel.')]];
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['editPanel'])->getMock();
        $subject
            ->expects(self::exactly($times))
            ->method('editPanel')
            ->with($content, $conf['editPanel.'])
            ->willReturn($will);
        self::assertSame(
            $expect,
            $subject->stdWrap_editPanel($content, $conf)
        );
    }
}
