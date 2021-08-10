<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\UnitDeprecated\ContentObject;

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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\CaseContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayInternalContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\EditPanelContentObject;
use TYPO3\CMS\Frontend\ContentObject\FileContentObject;
use TYPO3\CMS\Frontend\ContentObject\FilesContentObject;
use TYPO3\CMS\Frontend\ContentObject\FluidTemplateContentObject;
use TYPO3\CMS\Frontend\ContentObject\HierarchicalMenuContentObject;
use TYPO3\CMS\Frontend\ContentObject\ImageContentObject;
use TYPO3\CMS\Frontend\ContentObject\ImageResourceContentObject;
use TYPO3\CMS\Frontend\ContentObject\LoadRegisterContentObject;
use TYPO3\CMS\Frontend\ContentObject\RecordsContentObject;
use TYPO3\CMS\Frontend\ContentObject\RestoreRegisterContentObject;
use TYPO3\CMS\Frontend\ContentObject\ScalableVectorGraphicsContentObject;
use TYPO3\CMS\Frontend\ContentObject\TemplateContentObject;
use TYPO3\CMS\Frontend\ContentObject\TextContentObject;
use TYPO3\CMS\Frontend\ContentObject\UserContentObject;
use TYPO3\CMS\Frontend\ContentObject\UserInternalContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Frontend\Tests\Unit\ContentObject\ContentObjectRendererTestTrait;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ContentObjectRendererTest extends UnitTestCase
{
    use ContentObjectRendererTestTrait;

    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContentObjectRenderer
     */
    protected $subject;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TypoScriptFrontendController
     */
    protected $frontendControllerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TemplateService
     */
    protected $templateServiceMock;

    /**
     * Default content object name -> class name map, shipped with TYPO3 CMS
     *
     * @var array
     */
    protected $contentObjectMap = [
        'TEXT' => TextContentObject::class,
        'CASE' => CaseContentObject::class,
        'COBJ_ARRAY' => ContentObjectArrayContentObject::class,
        'COA' => ContentObjectArrayContentObject::class,
        'COA_INT' => ContentObjectArrayInternalContentObject::class,
        'USER' => UserContentObject::class,
        'USER_INT' => UserInternalContentObject::class,
        'FILE' => FileContentObject::class,
        'FILES' => FilesContentObject::class,
        'IMAGE' => ImageContentObject::class,
        'IMG_RESOURCE' => ImageResourceContentObject::class,
        'CONTENT' => ContentContentObject::class,
        'RECORDS' => RecordsContentObject::class,
        'HMENU' => HierarchicalMenuContentObject::class,
        'CASEFUNC' => CaseContentObject::class,
        'LOAD_REGISTER' => LoadRegisterContentObject::class,
        'RESTORE_REGISTER' => RestoreRegisterContentObject::class,
        'TEMPLATE' => TemplateContentObject::class,
        'FLUIDTEMPLATE' => FluidTemplateContentObject::class,
        'SVG' => ScalableVectorGraphicsContentObject::class,
        'EDITPANEL' => EditPanelContentObject::class
    ];

    /**
     * Set up
     */
    protected function setUp(): void
    {
        $GLOBALS['SIM_ACCESS_TIME'] = 1534278180;
        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->templateServiceMock =
            $this->getMockBuilder(TemplateService::class)
                ->setConstructorArgs([null, $packageManagerMock])
                ->setMethods(['linkData'])
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
        $GLOBALS['TSFE'] = $this->frontendControllerMock;

        $this->subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            ['getResourceFactory', 'getEnvironmentVariable'],
            [$this->frontendControllerMock]
        );

        $logger = $this->prophesize(Logger::class);
        $this->subject->setLogger($logger->reveal());
        $this->subject->setContentObjectClassMap($this->contentObjectMap);
        $this->subject->start([], 'tt_content');
    }

    ////////////////////////////////////
    // Test concerning link generation
    ////////////////////////////////////

    /**
     * @test
     */
    public function filelinkCreatesCorrectUrlForFileWithUrlEncodedSpecialChars(): void
    {
        $fileNameAndPath = Environment::getPublicPath() . '/typo3temp/var/tests/phpunitJumpUrlTestFile with spaces & amps.txt';
        file_put_contents($fileNameAndPath, 'Some test data');
        $relativeFileNameAndPath = substr($fileNameAndPath, strlen(Environment::getPublicPath()) + 1);
        $fileName = substr($fileNameAndPath, strlen(Environment::getPublicPath() . '/typo3temp/var/tests/'));

        $expectedLink = str_replace('%2F', '/', rawurlencode($relativeFileNameAndPath));
        $result = $this->subject->filelink($fileName, ['path' => 'typo3temp/var/tests/'], true);
        $this->assertEquals('<a href="' . $expectedLink . '">' . $fileName . '</a>', $result);

        GeneralUtility::unlink_tempfile($fileNameAndPath);
    }

    /**
     * Check that stdWrap_addParams works properly.
     *
     * Show:
     *
     *  - Delegates to method addParams.
     *  - Parameter 1 is $content.
     *  - Parameter 2 is $conf['addParams.'].
     *  - Returns the return value.
     *
     * @test
     */
    public function stdWrap_addParams(): void
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'addParams' => $this->getUniqueId('not used'),
            'addParams.' => [$this->getUniqueId('addParams.')],
        ];
        $return = $this->getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['addParams'])->getMock();
        $subject
            ->expects($this->once())
            ->method('addParams')
            ->with($content, $conf['addParams.'])
            ->willReturn($return);
        $this->assertSame(
            $return,
            $subject->stdWrap_addParams($content, $conf)
        );
    }

    /**
     * Check if stdWrap_filelink works properly.
     *
     * Show:
     *
     * - Delegates to method filelink.
     * - Parameter 1 is $content.
     * - Parameter 2 is $conf['filelink.'].
     * - Returns the return value.
     *
     * @test
     */
    public function stdWrap_filelink(): void
    {
        $content = $this->getUniqueId('content');
        $conf = [
            'filelink' => $this->getUniqueId('not used'),
            'filelink.' => [$this->getUniqueId('filelink.')],
        ];
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['filelink'])->getMock();
        $subject->expects($this->once())->method('filelink')
            ->with($content, $conf['filelink.'])->willReturn('return');
        $this->assertSame(
            'return',
            $subject->stdWrap_filelink($content, $conf)
        );
    }

    /**
     * Check if stdWrap_filelist works properly.
     *
     * Show:
     *
     * - Delegates to method filelist.
     * - Parameter is $conf['filelist'].
     * - Returns the return value.
     *
     * @test
     */
    public function stdWrap_filelist(): void
    {
        $conf = [
            'filelist' => $this->getUniqueId('filelist'),
            'filelist.' => [$this->getUniqueId('not used')],
        ];
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['filelist'])->getMock();
        $subject->expects($this->once())->method('filelist')
            ->with($conf['filelist'])->willReturn('return');
        $this->assertSame(
            'return',
            $subject->stdWrap_filelist('discard', $conf)
        );
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
        $this->assertEquals($expectedResult, $this->subject->stdWrap_parseFunc($value, $configuration));
    }
}
