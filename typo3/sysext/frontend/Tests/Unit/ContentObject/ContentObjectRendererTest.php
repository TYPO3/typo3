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

use PHPUnit\Framework\Exception;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface as CacheFrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\ExpressionLanguage\ProviderConfigurationLoader;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\CaseContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectArrayInternalContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectGetImageResourceHookInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectStdWrapHookInterface;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;
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
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
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
    protected $resetSingletonInstances = true;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|AccessibleObjectInterface|ContentObjectRenderer
     */
    protected $subject;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TypoScriptFrontendController|AccessibleObjectInterface
     */
    protected $frontendControllerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TemplateService
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

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|CacheManager
     */
    protected $cacheManager;

    protected $backupEnvironment = true;

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

    //////////////////////
    // Utility functions
    //////////////////////

    /**
     * @return TypoScriptFrontendController
     */
    protected function getFrontendController(): TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * Converts the subject and the expected result into utf-8.
     *
     * @param string $subject the subject, will be modified
     * @param string $expected the expected result, will be modified
     */
    protected function handleCharset(string &$subject, string &$expected): void
    {
        $subject = mb_convert_encoding($subject, 'utf-8', 'iso-8859-1');
        $expected = mb_convert_encoding($expected, 'utf-8', 'iso-8859-1');
    }

    /////////////////////////////////////////////
    // Tests concerning the getImgResource hook
    /////////////////////////////////////////////
    /**
     * @test
     */
    public function getImgResourceCallsGetImgResourcePostProcessHook(): void
    {
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheProphecy = $this->prophesize(CacheFrontendInterface::class);
        $cacheManagerProphecy->getCache('imagesizes')->willReturn($cacheProphecy->reveal());
        $cacheProphecy->get(Argument::cetera())->willReturn(false);
        $cacheProphecy->set(Argument::cetera(), null)->willReturn(false);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());

        $resourceFactory = $this->createMock(ResourceFactory::class);
        $this->subject->expects(self::any())->method('getResourceFactory')->willReturn($resourceFactory);

        $className = StringUtility::getUniqueId('tx_coretest');
        $getImgResourceHookMock = $this->getMockBuilder(ContentObjectGetImageResourceHookInterface::class)
            ->onlyMethods(['getImgResourcePostProcess'])
            ->setMockClassName($className)
            ->getMock();
        $getImgResourceHookMock
            ->expects(self::once())
            ->method('getImgResourcePostProcess')
            ->willReturnCallback([$this, 'isGetImgResourceHookCalledCallback']);
        $getImgResourceHookObjects = [$getImgResourceHookMock];
        $this->subject->_set('getImgResourceHookObjects', $getImgResourceHookObjects);
        $this->subject->getImgResource('typo3/sysext/core/Tests/Unit/Utility/Fixtures/clear.gif', []);
    }

    /**
     * Handles the arguments that have been sent to the getImgResource hook.
     *
     * @param string $file
     * @param array $fileArray
     * @param $imageResource
     * @param ContentObjectRenderer $parent
     * @return array
     * @see getImgResourceHookGetsCalled
     */
    public function isGetImgResourceHookCalledCallback(
        string $file,
        array $fileArray,
        $imageResource,
        ContentObjectRenderer $parent
    ): array {
        self::assertEquals('typo3/sysext/core/Tests/Unit/Utility/Fixtures/clear.gif', $file);
        self::assertEquals('typo3/sysext/core/Tests/Unit/Utility/Fixtures/clear.gif', $imageResource['origFile']);
        self::assertIsArray($fileArray);
        self::assertInstanceOf(ContentObjectRenderer::class, $parent);
        return $imageResource;
    }

    //////////////////////////////////////
    // Tests related to getContentObject
    //////////////////////////////////////

    /**
     * Show registration of a class for a TypoScript object name and getting
     * the registered content object is working.
     *
     * Prove is done by successfully creating an object based on the mapping.
     * Note two conditions in contrast to other tests, where the creation
     * fails.
     *
     * 1. The type must be of AbstractContentObject.
     * 2. Registration can only be done by public methods.
     *
     * @test
     */
    public function canRegisterAContentObjectClassForATypoScriptName(): void
    {
        $className = TextContentObject::class;
        $contentObjectName = 'TEST_TEXT';
        $this->subject->registerContentObjectClass(
            $className,
            $contentObjectName
        );
        $object = $this->subject->getContentObject($contentObjectName);
        self::assertInstanceOf($className, $object);
    }

    /**
     * Show that setting of the class map and getting a registered content
     * object is working.
     *
     * @see ContentObjectRendererTest::canRegisterAContentObjectClassForATypoScriptName
     * @test
     */
    public function canSetTheContentObjectClassMapAndGetARegisteredContentObject(): void
    {
        $className = TextContentObject::class;
        $contentObjectName = 'TEST_TEXT';
        $classMap = [$contentObjectName => $className];
        $this->subject->setContentObjectClassMap($classMap);
        $object = $this->subject->getContentObject($contentObjectName);
        self::assertInstanceOf($className, $object);
    }

    /**
     * Show that the map is not set as an externally accessible reference.
     *
     * Prove is done by missing success when trying to use it this way.
     *
     * @see ContentObjectRendererTest::canRegisterAContentObjectClassForATypoScriptName
     * @test
     */
    public function canNotAccessInternalContentObjectMapByReference(): void
    {
        $className = TextContentObject::class;
        $contentObjectName = 'TEST_TEXT';
        $classMap = [];
        $this->subject->setContentObjectClassMap($classMap);
        $classMap[$contentObjectName] = $className;
        $object = $this->subject->getContentObject($contentObjectName);
        self::assertNull($object);
    }

    /**
     * @see ContentObjectRendererTest::canRegisterAContentObjectClassForATypoScriptName
     * @test
     */
    public function willReturnNullForUnregisteredObject(): void
    {
        $object = $this->subject->getContentObject('FOO');
        self::assertNull($object);
    }

    /**
     * @see ContentObjectRendererTest::canRegisterAContentObjectClassForATypoScriptName
     * @test
     */
    public function willThrowAnExceptionForARegisteredNonContentObject(): void
    {
        $this->expectException(ContentRenderingException::class);
        $this->subject->registerContentObjectClass(
            \stdClass::class,
            'STDCLASS'
        );
        $this->subject->getContentObject('STDCLASS');
    }

    /**
     * @return string[][] [[$name, $fullClassName],]
     */
    public function registersAllDefaultContentObjectsDataProvider(): array
    {
        $dataProvider = [];
        foreach ($this->contentObjectMap as $name => $className) {
            $dataProvider[] = [$name, $className];
        }
        return $dataProvider;
    }

    /**
     * Prove that all content objects are registered and a class is available
     * for each of them.
     *
     * @test
     * @dataProvider registersAllDefaultContentObjectsDataProvider
     * @param string $objectName TypoScript name of content object
     * @param string $className Expected class name
     */
    public function registersAllDefaultContentObjects(
        string $objectName,
        string $className
    ): void {
        self::assertTrue(
            is_subclass_of($className, AbstractContentObject::class)
        );
        $object = $this->subject->getContentObject($objectName);
        self::assertInstanceOf($className, $object);
    }

    /////////////////////////////////////////
    // Tests concerning getQueryArguments()
    /////////////////////////////////////////
    /**
     * @test
     */
    public function getQueryArgumentsExcludesParameters(): void
    {
        $_GET = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'key31' => 'value31',
                'key32' => [
                    'key321' => 'value321',
                    'key322' => 'value322'
                ]
            ]
        ];
        $getQueryArgumentsConfiguration = [];
        $getQueryArgumentsConfiguration['exclude'] = [];
        $getQueryArgumentsConfiguration['exclude'][] = 'key1';
        $getQueryArgumentsConfiguration['exclude'][] = 'key3[key31]';
        $getQueryArgumentsConfiguration['exclude'][] = 'key3[key32][key321]';
        $getQueryArgumentsConfiguration['exclude'] = implode(',', $getQueryArgumentsConfiguration['exclude']);
        $expectedResult = $this->rawUrlEncodeSquareBracketsInUrl('&key2=value2&key3[key32][key322]=value322');
        $actualResult = $this->subject->getQueryArguments($getQueryArgumentsConfiguration);
        self::assertEquals($expectedResult, $actualResult);
    }

    /**
     * Encodes square brackets in URL.
     *
     * @param string $string
     * @return string
     */
    private function rawUrlEncodeSquareBracketsInUrl(string $string): string
    {
        return str_replace(['[', ']'], ['%5B', '%5D'], $string);
    }

    //////////////////////////
    // Tests concerning crop
    //////////////////////////
    /**
     * @test
     */
    public function cropIsMultibyteSafe(): void
    {
        self::assertEquals('бла', $this->subject->crop('бла', '3|...'));
    }

    //////////////////////////////

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
    public function cropHTMLDataProvider(): array
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
                '<em data-foo="x">foobar</em>foobaz',
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
        ];
    }

    /**
     * Check if cropHTML works properly.
     *
     * @test
     * @dataProvider cropHTMLDataProvider
     * @param string $expect The expected cropped output.
     * @param string $content The given input.
     * @param string $conf The given configuration.
     */
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
    public function roundDataProvider(): array
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
            'true' => [1.0, true, []]
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
     * @dataProvider roundDataProvider
     * @test
     */
    public function round(float $expect, $content, array $conf): void
    {
        self::assertSame(
            $expect,
            $this->subject->_call('round', $content, $conf)
        );
    }

    /**
     * @test
     */
    public function recursiveStdWrapProperlyRendersBasicString(): void
    {
        $stdWrapConfiguration = [
            'noTrimWrap' => '|| 123|',
            'stdWrap.' => [
                'wrap' => '<b>|</b>'
            ]
        ];
        self::assertSame(
            '<b>Test</b> 123',
            $this->subject->stdWrap('Test', $stdWrapConfiguration)
        );
    }

    /**
     * @test
     */
    public function recursiveStdWrapIsOnlyCalledOnce(): void
    {
        $stdWrapConfiguration = [
            'append' => 'TEXT',
            'append.' => [
                'data' => 'register:Counter'
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
                        ]
                    ]
                ]
            ]
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
    public function numberFormatDataProvider(): array
    {
        return [
            'testing decimals' => [
                '0.80',
                0.8,
                ['decimals' => 2]
            ],
            'testing decimals with input as string' => [
                '0.80',
                '0.8',
                ['decimals' => 2]
            ],
            'testing dec_point' => [
                '0,8',
                0.8,
                ['decimals' => 1, 'dec_point' => ',']
            ],
            'testing thousands_sep' => [
                '1.000',
                999.99,
                [
                    'decimals' => 0,
                    'thousands_sep.' => ['char' => 46]
                ]
            ],
            'testing mixture' => [
                '1.281.731,5',
                1281731.45,
                [
                    'decimals' => 1,
                    'dec_point.' => ['char' => 44],
                    'thousands_sep.' => ['char' => 46]
                ]
            ]
        ];
    }

    /**
     * Check if numberFormat works properly.
     *
     * @dataProvider numberFormatDataProvider
     * @test
     * @param string $expects
     * @param mixed $content
     * @param array $conf
     */
    public function numberFormat(string $expects, $content, array $conf): void
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
    public function replacementDataProvider(): array
    {
        return [
            'multiple replacements, including regex' => [
                'There is an animal, an animal and an animal around the block! Yeah!',
                'There_is_a_cat,_a_dog_and_a_tiger_in_da_hood!_Yeah!',
                [
                    '20.' => [
                        'search' => '_',
                        'replace.' => ['char' => '32']
                    ],
                    '120.' => [
                        'search' => 'in da hood',
                        'replace' => 'around the block'
                    ],
                    '130.' => [
                        'search' => '#a (Cat|Dog|Tiger)#i',
                        'replace' => 'an animal',
                        'useRegExp' => '1'
                    ]
                ]
            ],
            'replacement with optionSplit, normal pattern' => [
                'There1is2a3cat,3a3dog3and3a3tiger3in3da3hood!3Yeah!',
                'There_is_a_cat,_a_dog_and_a_tiger_in_da_hood!_Yeah!',
                [
                    '10.' => [
                        'search' => '_',
                        'replace' => '1 || 2 || 3',
                        'useOptionSplitReplace' => '1',
                        'useRegExp' => '0'
                    ]
                ]
            ],
            'replacement with optionSplit, using regex' => [
                'There is a tiny cat, a midsized dog and a big tiger in da hood! Yeah!',
                'There is a cat, a dog and a tiger in da hood! Yeah!',
                [
                    '10.' => [
                        'search' => '#(a) (Cat|Dog|Tiger)#i',
                        'replace' => '${1} tiny ${2} || ${1} midsized ${2} || ${1} big ${2}',
                        'useOptionSplitReplace' => '1',
                        'useRegExp' => '1'
                    ]
                ]
            ]
        ];
    }

    /**
     * Check if stdWrap.replacement and all of its properties work properly
     *
     * @test
     * @dataProvider replacementDataProvider
     * @param string $content The given input.
     * @param string $expects The expected result.
     * @param array $conf The given configuration.
     */
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
    public function calcAgeDataProvider(): array
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
                '45 yrs',
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
            ]
        ];
    }

    /**
     * Check if calcAge works properly.
     *
     * @test
     * @dataProvider calcAgeDataProvider
     * @param string $expect
     * @param int $timestamp
     * @param string $labels
     */
    public function calcAge(string $expect, int $timestamp, string $labels): void
    {
        self::assertSame(
            $expect,
            $this->subject->calcAge($timestamp, $labels)
        );
    }

    /**
     * @return array
     */
    public function stdWrapReturnsExpectationDataProvider(): array
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

    /**
     * @param string $content
     * @param array $configuration
     * @param string $expectation
     * @dataProvider stdWrapReturnsExpectationDataProvider
     * @test
     */
    public function stdWrapReturnsExpectation(string $content, array $configuration, string $expectation): void
    {
        self::assertSame($expectation, $this->subject->stdWrap($content, $configuration));
    }

    /**
     * Data provider for substring
     *
     * @return array [$expect, $content, $conf]
     */
    public function substringDataProvider(): array
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
     * @test
     * @dataProvider substringDataProvider
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @param string $conf The given configuration.
     */
    public function substring(string $expect, string $content, string $conf): void
    {
        self::assertSame($expect, $this->subject->substring($content, $conf));
    }

    ///////////////////////////////
    // Tests concerning getData()
    ///////////////////////////////

    /**
     * @return array
     */
    public function getDataWithTypeGpDataProvider(): array
    {
        return [
            'Value in get-data' => ['onlyInGet', 'GetValue'],
            'Value in post-data' => ['onlyInPost', 'PostValue'],
            'Value in post-data overriding get-data' => ['inGetAndPost', 'ValueInPost'],
        ];
    }

    /**
     * Checks if getData() works with type "gp"
     *
     * @test
     * @dataProvider getDataWithTypeGpDataProvider
     * @param string $key
     * @param string $expectedValue
     */
    public function getDataWithTypeGp(string $key, string $expectedValue): void
    {
        $_GET = [
            'onlyInGet' => 'GetValue',
            'inGetAndPost' => 'ValueInGet',
        ];
        $_POST = [
            'onlyInPost' => 'PostValue',
            'inGetAndPost' => 'ValueInPost',
        ];
        self::assertEquals($expectedValue, $this->subject->getData('gp:' . $key));
    }

    /**
     * Checks if getData() works with type "tsfe"
     *
     * @test
     */
    public function getDataWithTypeTsfe(): void
    {
        self::assertEquals($GLOBALS['TSFE']->metaCharset, $this->subject->getData('tsfe:metaCharset'));
    }

    /**
     * Checks if getData() works with type "getenv"
     *
     * @test
     */
    public function getDataWithTypeGetenv(): void
    {
        $envName = StringUtility::getUniqueId('frontendtest');
        $value = StringUtility::getUniqueId('someValue');
        putenv($envName . '=' . $value);
        self::assertEquals($value, $this->subject->getData('getenv:' . $envName));
    }

    /**
     * Checks if getData() works with type "getindpenv"
     *
     * @test
     */
    public function getDataWithTypeGetindpenv(): void
    {
        $this->subject->expects(self::once())->method('getEnvironmentVariable')
            ->with(self::equalTo('SCRIPT_FILENAME'))->willReturn('dummyPath');
        self::assertEquals('dummyPath', $this->subject->getData('getindpenv:SCRIPT_FILENAME'));
    }

    /**
     * Checks if getData() works with type "field"
     *
     * @test
     */
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
     *
     * @test
     */
    public function getDataWithTypeFieldAndFieldIsMultiDimensional(): void
    {
        $key = 'somekey|level1|level2';
        $value = 'somevalue';
        $field = ['somekey' => ['level1' => ['level2' => 'somevalue']]];

        self::assertEquals($value, $this->subject->getData('field:' . $key, $field));
    }

    public function getDataWithTypeFileReturnsUidOfFileObjectDataProvider(): array
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
     *
     * @test
     * @dataProvider getDataWithTypeFileReturnsUidOfFileObjectDataProvider
     */
    public function getDataWithTypeFileReturnsUidOfFileObject(string $typoScriptPath): void
    {
        $uid = StringUtility::getUniqueId();
        $file = $this->createMock(File::class);
        $file->expects(self::once())->method('getUid')->willReturn($uid);
        $this->subject->setCurrentFile($file);
        self::assertEquals($uid, $this->subject->getData($typoScriptPath));
    }

    /**
     * Checks if getData() works with type "parameters"
     *
     * @test
     */
    public function getDataWithTypeParameters(): void
    {
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $this->subject->parameters[$key] = $value;

        self::assertEquals($value, $this->subject->getData('parameters:' . $key));
    }

    /**
     * Checks if getData() works with type "register"
     *
     * @test
     */
    public function getDataWithTypeRegister(): void
    {
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $GLOBALS['TSFE']->register[$key] = $value;

        self::assertEquals($value, $this->subject->getData('register:' . $key));
    }

    /**
     * Checks if getData() works with type "session"
     *
     * @test
     */
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
        $GLOBALS['TSFE']->fe_user = $frontendUser;

        self::assertEquals(42, $this->subject->getData('session:myext|mydata|someValue'));
    }

    /**
     * Checks if getData() works with type "level"
     *
     * @test
     */
    public function getDataWithTypeLevel(): void
    {
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => 'title3'],
        ];

        $GLOBALS['TSFE']->tmpl->rootLine = $rootline;
        self::assertEquals(2, $this->subject->getData('level'));
    }

    /**
     * Checks if getData() works with type "global"
     *
     * @test
     */
    public function getDataWithTypeGlobal(): void
    {
        self::assertEquals($GLOBALS['TSFE']->metaCharset, $this->subject->getData('global:TSFE|metaCharset'));
    }

    /**
     * Checks if getData() works with type "leveltitle"
     *
     * @test
     */
    public function getDataWithTypeLeveltitle(): void
    {
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => ''],
        ];

        $GLOBALS['TSFE']->tmpl->rootLine = $rootline;
        self::assertEquals('', $this->subject->getData('leveltitle:-1'));
        // since "title3" is not set, it will slide to "title2"
        self::assertEquals('title2', $this->subject->getData('leveltitle:-1,slide'));
    }

    /**
     * Checks if getData() works with type "levelmedia"
     *
     * @test
     */
    public function getDataWithTypeLevelmedia(): void
    {
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1', 'media' => 'media1'],
            1 => ['uid' => 2, 'title' => 'title2', 'media' => 'media2'],
            2 => ['uid' => 3, 'title' => 'title3', 'media' => ''],
        ];

        $GLOBALS['TSFE']->tmpl->rootLine = $rootline;
        self::assertEquals('', $this->subject->getData('levelmedia:-1'));
        // since "title3" is not set, it will slide to "title2"
        self::assertEquals('media2', $this->subject->getData('levelmedia:-1,slide'));
    }

    /**
     * Checks if getData() works with type "leveluid"
     *
     * @test
     */
    public function getDataWithTypeLeveluid(): void
    {
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => 'title3'],
        ];

        $GLOBALS['TSFE']->tmpl->rootLine = $rootline;
        self::assertEquals(3, $this->subject->getData('leveluid:-1'));
        // every element will have a uid - so adding slide doesn't really make sense, just for completeness
        self::assertEquals(3, $this->subject->getData('leveluid:-1,slide'));
    }

    /**
     * Checks if getData() works with type "levelfield"
     *
     * @test
     */
    public function getDataWithTypeLevelfield(): void
    {
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1', 'testfield' => 'field1'],
            1 => ['uid' => 2, 'title' => 'title2', 'testfield' => 'field2'],
            2 => ['uid' => 3, 'title' => 'title3', 'testfield' => ''],
        ];

        $GLOBALS['TSFE']->tmpl->rootLine = $rootline;
        self::assertEquals('', $this->subject->getData('levelfield:-1,testfield'));
        self::assertEquals('field2', $this->subject->getData('levelfield:-1,testfield,slide'));
    }

    /**
     * Checks if getData() works with type "fullrootline"
     *
     * @test
     */
    public function getDataWithTypeFullrootline(): void
    {
        $rootline1 = [
            0 => ['uid' => 1, 'title' => 'title1', 'testfield' => 'field1'],
        ];
        $rootline2 = [
            0 => ['uid' => 1, 'title' => 'title1', 'testfield' => 'field1'],
            1 => ['uid' => 2, 'title' => 'title2', 'testfield' => 'field2'],
            2 => ['uid' => 3, 'title' => 'title3', 'testfield' => 'field3'],
        ];

        $GLOBALS['TSFE']->tmpl->rootLine = $rootline1;
        $GLOBALS['TSFE']->rootLine = $rootline2;
        self::assertEquals('field2', $this->subject->getData('fullrootline:-1,testfield'));
    }

    /**
     * Checks if getData() works with type "date"
     *
     * @test
     */
    public function getDataWithTypeDate(): void
    {
        $format = 'Y-M-D';
        $defaultFormat = 'd/m Y';

        self::assertEquals(date($format, $GLOBALS['EXEC_TIME']), $this->subject->getData('date:' . $format));
        self::assertEquals(date($defaultFormat, $GLOBALS['EXEC_TIME']), $this->subject->getData('date'));
    }

    /**
     * Checks if getData() works with type "page"
     *
     * @test
     */
    public function getDataWithTypePage(): void
    {
        $uid = random_int(0, mt_getrandmax());
        $GLOBALS['TSFE']->page['uid'] = $uid;
        self::assertEquals($uid, $this->subject->getData('page:uid'));
    }

    /**
     * Checks if getData() works with type "current"
     *
     * @test
     */
    public function getDataWithTypeCurrent(): void
    {
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $this->subject->data[$key] = $value;
        $this->subject->currentValKey = $key;
        self::assertEquals($value, $this->subject->getData('current'));
    }

    /**
     * Checks if getData() works with type "db"
     *
     * @test
     */
    public function getDataWithTypeDb(): void
    {
        $dummyRecord = ['uid' => 5, 'title' => 'someTitle'];

        $GLOBALS['TSFE']->sys_page->expects(self::atLeastOnce())->method('getRawRecord')->with(
            'tt_content',
            '106'
        )->willReturn($dummyRecord);
        self::assertEquals($dummyRecord['title'], $this->subject->getData('db:tt_content:106:title'));
    }

    /**
     * Checks if getData() works with type "lll"
     *
     * @test
     */
    public function getDataWithTypeLll(): void
    {
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $GLOBALS['TSFE']->expects(self::once())->method('sL')->with('LLL:' . $key)->willReturn($value);
        self::assertEquals($value, $this->subject->getData('lll:' . $key));
    }

    /**
     * Checks if getData() works with type "path"
     *
     * @test
     */
    public function getDataWithTypePath(): void
    {
        $filenameIn = 'typo3/sysext/frontend/Public/Icons/Extension.svg';
        self::assertEquals($filenameIn, $this->subject->getData('path:' . $filenameIn));
    }

    /**
     * Checks if getData() works with type "context"
     *
     * @test
     */
    public function getDataWithTypeContext(): void
    {
        $context = new Context([
            'workspace' => new WorkspaceAspect(3),
            'frontend.user' => new UserAspect(new FrontendUserAuthentication(), [0, -1])
        ]);
        GeneralUtility::setSingletonInstance(Context::class, $context);
        self::assertEquals(3, $this->subject->getData('context:workspace:id'));
        self::assertEquals('0,-1', $this->subject->getData('context:frontend.user:groupIds'));
        self::assertFalse($this->subject->getData('context:frontend.user:isLoggedIn'));
        self::assertSame('', $this->subject->getData('context:frontend.user:foozball'));
    }

    /**
     * Checks if getData() works with type "site"
     *
     * @test
     */
    public function getDataWithTypeSite(): void
    {
        $site = new Site('my-site', 123, [
            'base' => 'http://example.com',
            'custom' => [
                'config' => [
                    'nested' => 'yeah'
                ]
            ]
        ]);
        $this->frontendControllerMock->_set('site', $site);
        self::assertEquals('http://example.com', $this->subject->getData('site:base'));
        self::assertEquals('yeah', $this->subject->getData('site:custom.config.nested'));
    }

    /**
     * Checks if getData() works with type "site" and base variants
     *
     * @test
     */
    public function getDataWithTypeSiteWithBaseVariants(): void
    {
        $packageManager = new PackageManager(new DependencyOrderingService());
        GeneralUtility::addInstance(ProviderConfigurationLoader::class, new ProviderConfigurationLoader(
            $packageManager,
            new NullFrontend('core')
        ));
        putenv('LOCAL_DEVELOPMENT=1');

        $site = new Site('my-site', 123, [
            'base' => 'http://prod.com',
            'baseVariants' => [
                [
                    'base' => 'http://staging.com',
                    'condition' => 'applicationContext == "Production/Staging"'
                ],
                [
                    'base' => 'http://dev.com',
                    'condition' => 'getenv("LOCAL_DEVELOPMENT") == 1'
                ],
            ]
        ]);

        $this->frontendControllerMock->_set('site', $site);
        self::assertEquals('http://dev.com', $this->subject->getData('site:base'));
    }

    /**
     * Checks if getData() works with type "siteLanguage"
     *
     * @test
     */
    public function getDataWithTypeSiteLanguage(): void
    {
        $site = $this->createSiteWithLanguage([
            'base' => '/',
            'languageId' => 1,
            'locale' => 'de_DE',
            'title' => 'languageTitle',
            'navigationTitle' => 'German'
        ]);
        $language = $site->getLanguageById(1);
        $this->frontendControllerMock->_set('language', $language);
        self::assertEquals('German', $this->subject->getData('siteLanguage:navigationTitle'));
    }

    /**
     * Checks if getData() works with type "parentRecordNumber"
     *
     * @test
     */
    public function getDataWithTypeParentRecordNumber(): void
    {
        $recordNumber = random_int(0, mt_getrandmax());
        $this->subject->parentRecordNumber = $recordNumber;
        self::assertEquals($recordNumber, $this->subject->getData('cobj:parentRecordNumber'));
    }

    /**
     * Checks if getData() works with type "debug:rootLine"
     *
     * @test
     */
    public function getDataWithTypeDebugRootline(): void
    {
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => ''],
        ];
        $expectedResult = 'array(3items)0=>array(2items)uid=>1(integer)title=>"title1"(6chars)1=>array(2items)uid=>2(integer)title=>"title2"(6chars)2=>array(2items)uid=>3(integer)title=>""(0chars)';
        $GLOBALS['TSFE']->tmpl->rootLine = $rootline;

        DebugUtility::useAnsiColor(false);
        $result = $this->subject->getData('debug:rootLine');
        $cleanedResult = str_replace(["\r", "\n", "\t", ' '], '', $result);

        self::assertEquals($expectedResult, $cleanedResult);
    }

    /**
     * Checks if getData() works with type "debug:fullRootLine"
     *
     * @test
     */
    public function getDataWithTypeDebugFullRootline(): void
    {
        $rootline = [
            0 => ['uid' => 1, 'title' => 'title1'],
            1 => ['uid' => 2, 'title' => 'title2'],
            2 => ['uid' => 3, 'title' => ''],
        ];
        $expectedResult = 'array(3items)0=>array(2items)uid=>1(integer)title=>"title1"(6chars)1=>array(2items)uid=>2(integer)title=>"title2"(6chars)2=>array(2items)uid=>3(integer)title=>""(0chars)';
        $GLOBALS['TSFE']->rootLine = $rootline;

        DebugUtility::useAnsiColor(false);
        $result = $this->subject->getData('debug:fullRootLine');
        $cleanedResult = str_replace(["\r", "\n", "\t", ' '], '', $result);

        self::assertEquals($expectedResult, $cleanedResult);
    }

    /**
     * Checks if getData() works with type "debug:data"
     *
     * @test
     */
    public function getDataWithTypeDebugData(): void
    {
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
     *
     * @test
     */
    public function getDataWithTypeDebugRegister(): void
    {
        $key = StringUtility::getUniqueId('someKey');
        $value = StringUtility::getUniqueId('someValue');
        $GLOBALS['TSFE']->register = [$key => $value];

        $expectedResult = 'array(1item)' . $key . '=>"' . $value . '"(' . strlen($value) . 'chars)';

        DebugUtility::useAnsiColor(false);
        $result = $this->subject->getData('debug:register');
        $cleanedResult = str_replace(["\r", "\n", "\t", ' '], '', $result);

        self::assertEquals($expectedResult, $cleanedResult);
    }

    /**
     * Checks if getData() works with type "data:page"
     *
     * @test
     */
    public function getDataWithTypeDebugPage(): void
    {
        $uid = random_int(0, mt_getrandmax());
        $GLOBALS['TSFE']->page = ['uid' => $uid];

        $expectedResult = 'array(1item)uid=>' . $uid . '(integer)';

        DebugUtility::useAnsiColor(false);
        $result = $this->subject->getData('debug:page');
        $cleanedResult = str_replace(["\r", "\n", "\t", ' '], '', $result);

        self::assertEquals($expectedResult, $cleanedResult);
    }

    /**
     * @test
     */
    public function aTagParamsHasLeadingSpaceIfNotEmpty(): void
    {
        $aTagParams = $this->subject->getATagParams(['ATagParams' => 'data-test="testdata"']);
        self::assertEquals(' data-test="testdata"', $aTagParams);
    }

    /**
     * @test
     */
    public function aTagParamsHaveSpaceBetweenLocalAndGlobalParams(): void
    {
        $GLOBALS['TSFE']->ATagParams = 'data-global="dataglobal"';
        $aTagParams = $this->subject->getATagParams(['ATagParams' => 'data-test="testdata"']);
        self::assertEquals(' data-global="dataglobal" data-test="testdata"', $aTagParams);
    }

    /**
     * @test
     */
    public function aTagParamsHasNoLeadingSpaceIfEmpty(): void
    {
        // make sure global ATagParams are empty
        $GLOBALS['TSFE']->ATagParams = '';
        $aTagParams = $this->subject->getATagParams(['ATagParams' => '']);
        self::assertEquals('', $aTagParams);
    }

    /**
     * @test
     */
    public function renderingContentObjectThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1414513947);
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();
        $this->subject->render($contentObjectFixture, []);
    }

    /**
     * @test
     */
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
            Environment::getBackendPath() . '/index.php',
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();
        $this->subject->render($contentObjectFixture, []);
    }

    /**
     * @test
     */
    public function renderingContentObjectDoesNotThrowExceptionIfExceptionHandlerIsConfiguredLocally(): void
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

        $configuration = [
            'exceptionHandler' => '1'
        ];
        $this->subject->render($contentObjectFixture, $configuration);
    }

    /**
     * @test
     */
    public function renderingContentObjectDoesNotThrowExceptionIfExceptionHandlerIsConfiguredGlobally(): void
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

        $this->frontendControllerMock->config['config']['contentObjectExceptionHandler'] = '1';
        $this->subject->render($contentObjectFixture, []);
    }

    /**
     * @test
     */
    public function globalExceptionHandlerConfigurationCanBeOverriddenByLocalConfiguration(): void
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1414513947);
        $this->frontendControllerMock->config['config']['contentObjectExceptionHandler'] = '1';
        $configuration = [
            'exceptionHandler' => '0'
        ];
        $this->subject->render($contentObjectFixture, $configuration);
    }

    /**
     * @test
     */
    public function renderedErrorMessageCanBeCustomized(): void
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

        $configuration = [
            'exceptionHandler' => '1',
            'exceptionHandler.' => [
                'errorMessage' => 'New message for testing',
            ]
        ];

        self::assertSame('New message for testing', $this->subject->render($contentObjectFixture, $configuration));
    }

    /**
     * @test
     */
    public function localConfigurationOverridesGlobalConfiguration(): void
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

        $this->frontendControllerMock
            ->config['config']['contentObjectExceptionHandler.'] = [
            'errorMessage' => 'Global message for testing',
        ];
        $configuration = [
            'exceptionHandler' => '1',
            'exceptionHandler.' => [
                'errorMessage' => 'New message for testing',
            ]
        ];

        self::assertSame('New message for testing', $this->subject->render($contentObjectFixture, $configuration));
    }

    /**
     * @test
     */
    public function specificExceptionsCanBeIgnoredByExceptionHandler(): void
    {
        $contentObjectFixture = $this->createContentObjectThrowingExceptionFixture();

        $configuration = [
            'exceptionHandler' => '1',
            'exceptionHandler.' => [
                'ignoreCodes.' => ['10.' => '1414513947'],
            ]
        ];
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1414513947);
        $this->subject->render($contentObjectFixture, $configuration);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|AbstractContentObject
     */
    protected function createContentObjectThrowingExceptionFixture()
    {
        $contentObjectFixture = $this->getMockBuilder(AbstractContentObject::class)
            ->setConstructorArgs([$this->subject])
            ->getMock();
        $contentObjectFixture->expects(self::once())
            ->method('render')
            ->willReturnCallback(function () {
                throw new \LogicException('Exception during rendering', 1414513947);
            });
        return $contentObjectFixture;
    }

    /**
     * @return array
     */
    protected function getLibParseFunc(): array
    {
        return [
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
            'tags' => [
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
    }

    /**
     * @return array
     */
    public function typolinkReturnsCorrectLinksForEmailsAndUrlsDataProvider(): array
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
                '<a href="http://typo3.org" title="Open new window" target="_blank" class="url-class" rel="noreferrer">TYPO3</a>',
            ],
            'Link to url with attributes and custom target name' => [
                'TYPO3',
                [
                    'parameter' => 'http://typo3.org',
                    'ATagParams' => 'class="url-class"',
                    'extTarget' => 'someTarget',
                    'title' => 'Open new window',
                ],
                '<a href="http://typo3.org" title="Open new window" target="someTarget" class="url-class" rel="noreferrer">TYPO3</a>',
            ],
            'Link to url with attributes in parameter' => [
                'TYPO3',
                [
                    'parameter' => 'http://typo3.org _blank url-class "Open new window"',
                ],
                '<a href="http://typo3.org" title="Open new window" target="_blank" class="url-class" rel="noreferrer">TYPO3</a>',
            ],
            'Link to url with attributes in parameter and custom target name' => [
                'TYPO3',
                [
                    'parameter' => 'http://typo3.org someTarget url-class "Open new window"',
                ],
                '<a href="http://typo3.org" title="Open new window" target="someTarget" class="url-class" rel="noreferrer">TYPO3</a>',
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
                    'parameter' => 'foo@bar.org',
                ],
                '<a href="mailto:foo@bar.org">Email address</a>',
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
                '<a href="mailto:foo@bar.org" title="Write an email" class="email-class">Email address</a>',
            ],
            'Link to email with attributes in parameter' => [
                'Email address',
                [
                    'parameter' => 'foo@bar.org - email-class "Write an email"',
                ],
                '<a href="mailto:foo@bar.org" title="Write an email" class="email-class">Email address</a>',
            ],
        ];
    }

    /**
     * @test
     * @param string $linkText
     * @param array $configuration
     * @param string $expectedResult
     * @dataProvider typolinkReturnsCorrectLinksForEmailsAndUrlsDataProvider
     */
    public function typolinkReturnsCorrectLinksForEmailsAndUrls($linkText, $configuration, $expectedResult): void
    {
        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $templateServiceObjectMock = $this->getMockBuilder(TemplateService::class)
            ->setConstructorArgs([null, $packageManagerMock])
            ->addMethods(['dummy'])
            ->getMock();
        $templateServiceObjectMock->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];
        $typoScriptFrontendControllerMockObject = $this->createMock(TypoScriptFrontendController::class);
        $typoScriptFrontendControllerMockObject->config = [
            'config' => [],
        ];
        $typoScriptFrontendControllerMockObject->tmpl = $templateServiceObjectMock;
        $GLOBALS['TSFE'] = $typoScriptFrontendControllerMockObject;

        $this->cacheManager->getCache('runtime')->willReturn(new NullFrontend('dummy'));
        $this->cacheManager->getCache('core')->willReturn(new NullFrontend('runtime'));

        GeneralUtility::setSingletonInstance(
            SiteConfiguration::class,
            new SiteConfiguration(Environment::getConfigPath() . '/sites', new NullFrontend('dummy'))
        );

        $this->subject->_set('typoScriptFrontendController', $typoScriptFrontendControllerMockObject);

        self::assertEquals($expectedResult, $this->subject->typoLink($linkText, $configuration));
    }

    /**
     * @param array $settings
     * @param string $linkText
     * @param string $mailAddress
     * @param string $expected
     * @dataProvider typoLinkEncodesMailAddressForSpamProtectionDataProvider
     * @test
     */
    public function typoLinkEncodesMailAddressForSpamProtection(
        array $settings,
        $linkText,
        $mailAddress,
        $expected
    ): void {
        $this->getFrontendController()->spamProtectEmailAddresses = $settings['spamProtectEmailAddresses'];
        $this->getFrontendController()->config['config'] = $settings;
        $typoScript = ['parameter' => $mailAddress];

        self::assertEquals($expected, $this->subject->typoLink($linkText, $typoScript));
    }

    /**
     * @return array
     */
    public function typoLinkEncodesMailAddressForSpamProtectionDataProvider(): array
    {
        return [
            'plain mail without mailto scheme' => [
                [
                    'spamProtectEmailAddresses' => '',
                    'spamProtectEmailAddresses_atSubst' => '',
                    'spamProtectEmailAddresses_lastDotSubst' => '',
                ],
                'some.body@test.typo3.org',
                'some.body@test.typo3.org',
                '<a href="mailto:some.body@test.typo3.org">some.body@test.typo3.org</a>',
            ],
            'plain mail with mailto scheme' => [
                [
                    'spamProtectEmailAddresses' => '',
                    'spamProtectEmailAddresses_atSubst' => '',
                    'spamProtectEmailAddresses_lastDotSubst' => '',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="mailto:some.body@test.typo3.org">some.body@test.typo3.org</a>',
            ],
            'plain with at and dot substitution' => [
                [
                    'spamProtectEmailAddresses' => '0',
                    'spamProtectEmailAddresses_atSubst' => '(at)',
                    'spamProtectEmailAddresses_lastDotSubst' => '(dot)',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="mailto:some.body@test.typo3.org">some.body@test.typo3.org</a>',
            ],
            'mono-alphabetic substitution offset +1' => [
                [
                    'spamProtectEmailAddresses' => '1',
                    'spamProtectEmailAddresses_atSubst' => '',
                    'spamProtectEmailAddresses_lastDotSubst' => '',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="javascript:linkTo_UnCryptMailto(%27nbjmup%2Btpnf%5C%2FcpezAuftu%5C%2Fuzqp4%5C%2Fpsh%27);">some.body(at)test.typo3.org</a>',
            ],
            'mono-alphabetic substitution offset +1 with at substitution' => [
                [
                    'spamProtectEmailAddresses' => '1',
                    'spamProtectEmailAddresses_atSubst' => '@',
                    'spamProtectEmailAddresses_lastDotSubst' => '',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="javascript:linkTo_UnCryptMailto(%27nbjmup%2Btpnf%5C%2FcpezAuftu%5C%2Fuzqp4%5C%2Fpsh%27);">some.body@test.typo3.org</a>',
            ],
            'mono-alphabetic substitution offset +1 with at and dot substitution' => [
                [
                    'spamProtectEmailAddresses' => '1',
                    'spamProtectEmailAddresses_atSubst' => '(at)',
                    'spamProtectEmailAddresses_lastDotSubst' => '(dot)',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="javascript:linkTo_UnCryptMailto(%27nbjmup%2Btpnf%5C%2FcpezAuftu%5C%2Fuzqp4%5C%2Fpsh%27);">some.body(at)test.typo3(dot)org</a>',
            ],
            'mono-alphabetic substitution offset -1 with at and dot substitution' => [
                [
                    'spamProtectEmailAddresses' => '-1',
                    'spamProtectEmailAddresses_atSubst' => '(at)',
                    'spamProtectEmailAddresses_lastDotSubst' => '(dot)',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="javascript:linkTo_UnCryptMailto(%27lzhksn9rnld-ancxZsdrs-sxon2-nqf%27);">some.body(at)test.typo3(dot)org</a>',
            ],
            'mono-alphabetic substitution offset 2 with at and dot substitution and encoded subject' => [
                [
                    'spamProtectEmailAddresses' => '2',
                    'spamProtectEmailAddresses_atSubst' => '(at)',
                    'spamProtectEmailAddresses_lastDotSubst' => '(dot)',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org?subject=foo%20bar',
                '<a href="javascript:linkTo_UnCryptMailto(%27ocknvq%2Cuqog0dqfaBvguv0varq50qti%3Fuwdlgev%3Dhqq%2542dct%27);">some.body@test.typo3.org</a>',
            ],
            'entity substitution with at and dot substitution' => [
                [
                    'spamProtectEmailAddresses' => 'ascii',
                    'spamProtectEmailAddresses_atSubst' => '',
                    'spamProtectEmailAddresses_lastDotSubst' => '',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;&#115;&#111;&#109;&#101;&#46;&#98;&#111;&#100;&#121;&#64;&#116;&#101;&#115;&#116;&#46;&#116;&#121;&#112;&#111;&#51;&#46;&#111;&#114;&#103;">some.body(at)test.typo3.org</a>',
            ],
            'entity substitution with at and dot substitution with at and dot substitution' => [
                [
                    'spamProtectEmailAddresses' => 'ascii',
                    'spamProtectEmailAddresses_atSubst' => '(at)',
                    'spamProtectEmailAddresses_lastDotSubst' => '(dot)',
                ],
                'some.body@test.typo3.org',
                'mailto:some.body@test.typo3.org',
                '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;&#115;&#111;&#109;&#101;&#46;&#98;&#111;&#100;&#121;&#64;&#116;&#101;&#115;&#116;&#46;&#116;&#121;&#112;&#111;&#51;&#46;&#111;&#114;&#103;">some.body(at)test.typo3(dot)org</a>',
            ],
        ];
    }

    /**
     * @return array
     */
    public function typolinkReturnsCorrectLinksFilesDataProvider(): array
    {
        return [
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
                '<a href="fileadmin/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
            'Link to file with attributes and additional href' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'href="foo-bar"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '<a href="fileadmin/foo.bar" title="Title of the file" target="_blank">My file</a>',
            ],
            'Link to file with attributes and additional href and class' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'href="foo-bar" class="file-class"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '<a href="fileadmin/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
            'Link to file with attributes and additional class and href' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class" href="foo-bar"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '<a href="fileadmin/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
            'Link to file with attributes and additional class and href and title' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class" href="foo-bar" title="foo-bar"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '<a href="fileadmin/foo.bar" title="foo-bar" target="_blank" class="file-class">My file</a>',
            ],
            'Link to file with attributes and empty ATagParams' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => '',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '<a href="fileadmin/foo.bar" title="Title of the file" target="_blank">My file</a>',
            ],
            'Link to file with attributes in parameter' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar _blank file-class "Title of the file"',
                ],
                '<a href="fileadmin/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
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

    /**
     * @test
     * @param string $linkText
     * @param array $configuration
     * @param string $expectedResult
     * @dataProvider typolinkReturnsCorrectLinksFilesDataProvider
     */
    public function typolinkReturnsCorrectLinksFiles($linkText, $configuration, $expectedResult): void
    {
        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $templateServiceObjectMock = $this->getMockBuilder(TemplateService::class)
            ->setConstructorArgs([null, $packageManagerMock])
            ->addMethods(['dummy'])
            ->getMock();
        $templateServiceObjectMock->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];
        $typoScriptFrontendControllerMockObject = $this->createMock(TypoScriptFrontendController::class);
        $typoScriptFrontendControllerMockObject->config = [
            'config' => [],
        ];
        $typoScriptFrontendControllerMockObject->tmpl = $templateServiceObjectMock;
        $GLOBALS['TSFE'] = $typoScriptFrontendControllerMockObject;

        $resourceFactory = $this->prophesize(ResourceFactory::class);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory->reveal());

        $this->subject->_set('typoScriptFrontendController', $typoScriptFrontendControllerMockObject);

        self::assertEquals($expectedResult, $this->subject->typoLink($linkText, $configuration));
    }

    /**
     * @return array
     */
    public function typolinkReturnsCorrectLinksForFilesWithAbsRefPrefixDataProvider(): array
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
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '/',
                '<a href="/fileadmin/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
            'Link to file with attributes with longer absRefPrefix' => [
                'My file',
                [
                    'parameter' => 'fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '/sub/',
                '<a href="/sub/fileadmin/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
            'Link to absolute file with attributes with absRefPrefix' => [
                'My file',
                [
                    'parameter' => '/images/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '/',
                '<a href="/images/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
            'Link to absolute file with attributes with longer absRefPrefix' => [
                'My file',
                [
                    'parameter' => '/images/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '/sub/',
                '<a href="/images/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
            'Link to absolute file with attributes with identical longer absRefPrefix' => [
                'My file',
                [
                    'parameter' => '/sub/fileadmin/foo.bar',
                    'ATagParams' => 'class="file-class"',
                    'fileTarget' => '_blank',
                    'title' => 'Title of the file',
                ],
                '/sub/',
                '<a href="/sub/fileadmin/foo.bar" title="Title of the file" target="_blank" class="file-class">My file</a>',
            ],
        ];
    }

    /**
     * @test
     * @param string $linkText
     * @param array $configuration
     * @param string $absRefPrefix
     * @param string $expectedResult
     * @dataProvider typolinkReturnsCorrectLinksForFilesWithAbsRefPrefixDataProvider
     */
    public function typolinkReturnsCorrectLinksForFilesWithAbsRefPrefix(
        $linkText,
        $configuration,
        $absRefPrefix,
        $expectedResult
    ): void {
        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $templateServiceObjectMock = $this->getMockBuilder(TemplateService::class)
            ->setConstructorArgs([null, $packageManagerMock])
            ->addMethods(['dummy'])
            ->getMock();
        $templateServiceObjectMock->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];
        $resourceFactory = $this->prophesize(ResourceFactory::class);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory->reveal());

        $typoScriptFrontendControllerMockObject = $this->createMock(TypoScriptFrontendController::class);
        $typoScriptFrontendControllerMockObject->config = [
            'config' => [],
        ];
        $typoScriptFrontendControllerMockObject->tmpl = $templateServiceObjectMock;
        $GLOBALS['TSFE'] = $typoScriptFrontendControllerMockObject;
        $GLOBALS['TSFE']->absRefPrefix = $absRefPrefix;
        $this->subject->_set('typoScriptFrontendController', $typoScriptFrontendControllerMockObject);

        self::assertEquals($expectedResult, $this->subject->typoLink($linkText, $configuration));
    }

    /**
     * @test
     */
    public function typolinkOpensInNewWindow()
    {
        $this->cacheManager->getCache('runtime')->willReturn(new NullFrontend('runtime'));
        $this->cacheManager->getCache('core')->willReturn(new NullFrontend('runtime'));
        GeneralUtility::setSingletonInstance(
            SiteConfiguration::class,
            new SiteConfiguration(Environment::getConfigPath() . '/sites', new NullFrontend('dummy'))
        );
        $linkText = 'Nice Text';
        $configuration = [
            'parameter' => 'https://example.com 13x84:target=myexample'
        ];
        $expectedResult = '<a href="https://example.com" target="myexample" onclick="openPic(\'https:\/\/example.com\',\'myexample\',\'width=13,height=84\');return false;" rel="noreferrer">Nice Text</a>';
        self::assertEquals($expectedResult, $this->subject->typoLink($linkText, $configuration));
        $linkText = 'Nice Text with default window name';
        $configuration = [
            'parameter' => 'https://example.com 13x84'
        ];
        $expectedResult = '<a href="https://example.com" target="FEopenLink" onclick="openPic(\'https:\/\/example.com\',\'FEopenLink\',\'width=13,height=84\');return false;" rel="noreferrer">Nice Text with default window name</a>';
        self::assertEquals($expectedResult, $this->subject->typoLink($linkText, $configuration));

        $linkText = 'Nice Text with default window name';
        $configuration = [
            'parameter' => 'https://example.com 13x84'
        ];
        $expectedResult = '<a href="https://example.com" target="FEopenLink" onclick="openPic(\'https:\/\/example.com\',\'FEopenLink\',\'width=13,height=84\');return false;" rel="noreferrer">Nice Text with default window name</a>';
        self::assertEquals($expectedResult, $this->subject->typoLink($linkText, $configuration));

        $GLOBALS['TSFE']->xhtmlDoctype = 'xhtml_strict';
        $linkText = 'Nice Text with default window name';
        $configuration = [
            'parameter' => 'https://example.com 13x84'
        ];
        $expectedResult = '<a href="https://example.com" onclick="openPic(\'https:\/\/example.com\',\'FEopenLink\',\'width=13,height=84\');return false;" rel="noreferrer">Nice Text with default window name</a>';
        self::assertEquals($expectedResult, $this->subject->typoLink($linkText, $configuration));
    }

    /**
     * @test
     */
    public function typoLinkReturnsOnlyLinkTextIfNoLinkResolvingIsPossible(): void
    {
        $linkService = $this->prophesize(LinkService::class);
        GeneralUtility::setSingletonInstance(LinkService::class, $linkService->reveal());
        $linkService->resolve('foo')->willThrow(InvalidPathException::class);

        self::assertSame('foo', $this->subject->typoLink('foo', ['parameter' => 'foo']));
    }

    /**
     * @test
     */
    public function typoLinkLogsErrorIfNoLinkResolvingIsPossible(): void
    {
        $linkService = $this->prophesize(LinkService::class);
        GeneralUtility::setSingletonInstance(LinkService::class, $linkService->reveal());
        $linkService->resolve('foo')->willThrow(InvalidPathException::class);

        $logger = $this->prophesize(Logger::class);
        $logger->warning('The link could not be generated', Argument::any())->shouldBeCalled();
        $this->subject->setLogger($logger->reveal());
        $this->subject->typoLink('foo', ['parameter' => 'foo']);
    }

    /**
     * @test
     */
    public function typolinkLinkResult(): void
    {
        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $templateServiceObjectMock = $this->getMockBuilder(TemplateService::class)
            ->setConstructorArgs([null, $packageManagerMock])
            ->addMethods(['dummy'])
            ->getMock();
        $templateServiceObjectMock->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];
        $typoScriptFrontendControllerMockObject = $this->createMock(TypoScriptFrontendController::class);
        $typoScriptFrontendControllerMockObject->config = [
            'config' => [],
        ];
        $typoScriptFrontendControllerMockObject->tmpl = $templateServiceObjectMock;
        $GLOBALS['TSFE'] = $typoScriptFrontendControllerMockObject;

        $resourceFactory = $this->prophesize(ResourceFactory::class);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory->reveal());

        $this->cacheManager->getCache('runtime')->willReturn(new NullFrontend('dummy'));
        $this->cacheManager->getCache('core')->willReturn(new NullFrontend('runtime'));

        GeneralUtility::setSingletonInstance(
            SiteConfiguration::class,
            new SiteConfiguration(Environment::getConfigPath() . '/sites', new NullFrontend('dummy'))
        );

        $this->subject->_set('typoScriptFrontendController', $typoScriptFrontendControllerMockObject);

        $linkResult = $this->subject->typoLink('', [
            'parameter' => 'https://example.tld',
            'returnLast' => 'result']);

        self::assertTrue(is_a($linkResult, LinkResultInterface::class, true));
        self::assertEquals(json_encode([
            'href' => 'https://example.tld',
            'target' => null,
            'class' => null,
            'title' => null,
            'linkText' => 'https://example.tld',
            'additionalAttributes' => [], ]), json_encode($linkResult));
        self::assertEquals(json_encode([
            'href' => 'https://example.tld',
            'target' => null,
            'class' => null,
            'title' => null,
            'linkText' => 'https://example.tld',
            'additionalAttributes' => [], ]), (string)$linkResult);
    }

    /**
     * @param $linkText
     * @param $configuration
     * @param $expectedResult
     * @dataProvider typoLinkProperlyEncodesLinkResultDataProvider
     * @test
     */
    public function typoLinkProperlyEncodesLinkResult($linkText, $configuration, $expectedResult): void
    {
        $packageManagerMock = $this->getMockBuilder(PackageManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $templateServiceObjectMock = $this->getMockBuilder(TemplateService::class)
            ->setConstructorArgs([null, $packageManagerMock])
            ->addMethods(['dummy'])
            ->getMock();
        $templateServiceObjectMock->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];
        $typoScriptFrontendControllerMockObject = $this->createMock(TypoScriptFrontendController::class);
        $typoScriptFrontendControllerMockObject->config = [
            'config' => [],
        ];
        $typoScriptFrontendControllerMockObject->tmpl = $templateServiceObjectMock;
        $GLOBALS['TSFE'] = $typoScriptFrontendControllerMockObject;

        $resourceFactory = $this->prophesize(ResourceFactory::class);
        GeneralUtility::setSingletonInstance(ResourceFactory::class, $resourceFactory->reveal());

        $this->cacheManager->getCache('runtime')->willReturn(new NullFrontend('dummy'));
        $this->cacheManager->getCache('core')->willReturn(new NullFrontend('runtime'));

        GeneralUtility::setSingletonInstance(
            SiteConfiguration::class,
            new SiteConfiguration(Environment::getConfigPath() . '/sites', new NullFrontend('dummy'))
        );

        $this->subject->_set('typoScriptFrontendController', $typoScriptFrontendControllerMockObject);

        self::assertEquals($expectedResult, (string)$this->subject->typoLink($linkText, $configuration));
    }

    /**
     * @return array
     */
    public function typoLinkProperlyEncodesLinkResultDataProvider(): array
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
                    'additionalAttributes' => []
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
                    'additionalAttributes' => []
                ]),
            ],
            'Link to file with attributes' => [
                'My file',
                [
                    'parameter' => '/fileadmin/foo.bar',
                    'fileTarget' => '_blank',
                    'returnLast' => 'result',
                ],
                json_encode([
                    'href' => '/fileadmin/foo.bar',
                    'target' => '_blank',
                    'class' => null,
                    'title' => null,
                    'linkText' => 'My file',
                    'additionalAttributes' => []
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
                    'additionalAttributes' => ['rel' => 'noreferrer']
                ]),
            ],
        ];
    }

    /**
     * @test
     */
    public function stdWrap_splitObjReturnsCount(): void
    {
        $conf = [
            'token' => ',',
            'returnCount' => 1
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
    public function calculateCacheKeyDataProvider(): array
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
                null
            ],
            'value conf only' => [
                $value,
                $valueConf,
                0,
                null,
                null,
                null
            ],
            'wrap conf only' => [
                $will,
                $wrapConf,
                1,
                '',
                $wrap,
                $will
            ],
            'full conf' => [
                $will,
                $conf,
                1,
                $value,
                $wrap,
                $will
            ],
        ];
    }

    /**
     * Check if calculateCacheKey works properly.
     *
     * - takes key from $conf['key']
     * - processes key with stdWrap based on $conf['key.']
     *
     * @test
     * @dataProvider calculateCacheKeyDataProvider
     * @param string $expect Expected result.
     * @param array $conf Properties 'key', 'key.'
     * @param int $times Times called mocked method.
     * @param string|null $with Parameter passed to mocked method.
     * @param string|null $withWrap
     * @param string|null $will Return value of mocked method.
     */
    public function calculateCacheKey(string $expect, array $conf, int $times, $with, $withWrap, $will): void
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
    public function getFromCacheDtataProvider(): array
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
     * @test
     * @dataProvider getFromCacheDtataProvider
     * @param string $expect Expected result.
     * @param array $conf Configuration to pass to calculateCacheKey mock.
     * @param string $cacheKey Return from calculateCacheKey mock.
     * @param int $times Times the cache is expected to be called (0 or 1).
     * @param string $cached Return from cacheFrontend mock.
     */
    public function getFromCache($expect, $conf, $cacheKey, $times, $cached): void
    {
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            ['calculateCacheKey']
        );
        $subject
            ->expects(self::once())
            ->method('calculateCacheKey')
            ->with($conf)
            ->willReturn($cacheKey);
        $cacheFrontend = $this->createMock(CacheFrontendInterface::class);
        $cacheFrontend
            ->expects(self::exactly($times))
            ->method('get')
            ->with($cacheKey)
            ->willReturn($cached);
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
    public function getFieldValDataProvider(): array
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
     * @test
     * @dataProvider getFieldValDataProvider
     * @param string|null $expect The expected string.
     * @param string $fields Field names divides by //.
     */
    public function getFieldVal($expect, string $fields): void
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

    /**
     * Data provider for caseshift.
     *
     * @return array [$expect, $content, $case]
     */
    public function caseshiftDataProvider(): array
    {
        return [
            'lower' => ['x y', 'X Y', 'lower'],
            'upper' => ['X Y', 'x y', 'upper'],
            'capitalize' => ['One Two', 'one two', 'capitalize'],
            'ucfirst' => ['One two', 'one two', 'ucfirst'],
            'lcfirst' => ['oNE TWO', 'ONE TWO', 'lcfirst'],
            'uppercamelcase' => ['CamelCase', 'camel_case', 'uppercamelcase'],
            'lowercamelcase' => ['camelCase', 'camel_case', 'lowercamelcase'],
        ];
    }

    /**
     * Check if caseshift works properly.
     *
     * @test
     * @dataProvider caseshiftDataProvider
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @param string $case The given type of conversion.
     */
    public function caseshift(string $expect, string $content, string $case): void
    {
        self::assertSame(
            $expect,
            $this->subject->caseshift($content, $case)
        );
    }

    /**
     * Data provider for HTMLcaseshift.
     *
     * @return array [$expect, $content, $case, $with, $will]
     */
    public function HTMLcaseshiftDataProvider(): array
    {
        $case = StringUtility::getUniqueId('case');
        return [
            'simple text' => [
                'TEXT',
                'text',
                $case,
                [['text', $case]],
                ['TEXT']
            ],
            'simple tag' => [
                '<i>TEXT</i>',
                '<i>text</i>',
                $case,
                [['', $case], ['text', $case]],
                ['', 'TEXT']
            ],
            'multiple nested tags with classes' => [
                '<div class="typo3">'
                . '<p>A <b>BOLD<\b> WORD.</p>'
                . '<p>AN <i>ITALIC<\i> WORD.</p>'
                . '</div>',
                '<div class="typo3">'
                . '<p>A <b>bold<\b> word.</p>'
                . '<p>An <i>italic<\i> word.</p>'
                . '</div>',
                $case,
                [
                    ['', $case],
                    ['', $case],
                    ['A ', $case],
                    ['bold', $case],
                    [' word.', $case],
                    ['', $case],
                    ['An ', $case],
                    ['italic', $case],
                    [' word.', $case],
                    ['', $case],
                ],
                ['', '', 'A ', 'BOLD', ' WORD.', '', 'AN ', 'ITALIC', ' WORD.', '']
            ],
        ];
    }

    /**
     * Check if HTMLcaseshift works properly.
     *
     * Show:
     *
     * - Only shifts the case of characters not part of tags.
     * - Delegates to the method caseshift.
     *
     * @test
     * @dataProvider HTMLcaseshiftDataProvider
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @param string $case The given type of conversion.
     * @param array $with Consecutive args expected by caseshift.
     * @param array $will Consecutive return values of caseshfit.
     */
    public function HTMLcaseshift(string $expect, string $content, string $case, array $with, array $will): void
    {
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['caseshift'])->getMock();
        $subject
            ->expects(self::exactly(count($with)))
            ->method('caseshift')
            ->withConsecutive(...$with)
            ->will(self::onConsecutiveCalls(...$will));
        self::assertSame(
            $expect,
            $subject->HTMLcaseshift($content, $case)
        );
    }

    /***************************************************************************
     * General tests for stdWrap_
     ***************************************************************************/

    /**
     * Check that all registered stdWrap processors are callable.
     *
     * Show:
     *
     * - The given invalidProcessor is counted as not callable.
     * - All stdWrap processors are counted as callable.
     * - Their amount is 91.
     *
     * @test
     */
    public function allStdWrapProcessorsAreCallable(): void
    {
        $callable = 0;
        $notCallable = 0;
        $processors = ['invalidProcessor'];
        foreach (array_keys($this->subject->_get('stdWrapOrder')) as $key) {
            $processors[] = strtr($key, ['.' => '']);
        }
        foreach (array_unique($processors) as $processor) {
            $method = [$this->subject, 'stdWrap_' . $processor];
            if (is_callable($method)) {
                $callable += 1;
            } else {
                $notCallable += 1;
            }
        }
        self::assertSame(1, $notCallable);
        self::assertSame(83, $callable);
    }

    /**
     * Check which stdWrap functions are callable with empty parameters.
     *
     * Show:
     *
     * - Almost all stdWrap_[type] are callable if called with 2 parameters:
     *   - string $content Empty string.
     *   - array $conf ['type' => '', 'type.' => []].
     * - Exceptions: stdWrap_numRows, stdWrap_split
     * - The overall count is 91.
     *
     *  Note:
     *
     *  The two exceptions break, if the configuration is empty. This test just
     *  tracks the different behaviour to gain information. It doesn't mean
     *  that it is an issue.
     *
     * @test
     */
    public function notAllStdWrapProcessorsAreCallableWithEmptyConfiguration(): void
    {
        $timeTrackerProphecy = $this->prophesize(TimeTracker::class);
        GeneralUtility::setSingletonInstance(TimeTracker::class, $timeTrackerProphecy->reveal());

        // `parseFunc` issues deprecation in case `htmlSanitize` is not given
        $expectExceptions = ['numRows', 'parseFunc', 'split', 'bytes'];
        $count = 0;
        $processors = [];
        $exceptions = [];
        foreach (array_keys($this->subject->_get('stdWrapOrder')) as $key) {
            $processors[] = strtr($key, ['.' => '']);
        }
        foreach (array_unique($processors) as $processor) {
            $count += 1;
            try {
                $conf = [$processor => '', $processor . '.' => ['table' => 'tt_content']];
                $method = 'stdWrap_' . $processor;
                $this->subject->$method('', $conf);
            } catch (\Exception $e) {
                $exceptions[] = $processor;
            }
        }
        self::assertSame($expectExceptions, $exceptions);
        self::assertSame(83, $count);
    }

    /***************************************************************************
     * End general tests for stdWrap_
     ***************************************************************************/

    /***************************************************************************
     * Tests for stdWrap_ in alphabetical order (all uppercase before lowercase)
     ***************************************************************************/

    /**
     * Data provider for fourTypesOfStdWrapHookObjectProcessors
     *
     * @return array Order: stdWrap, hookObjectCall
     */
    public function fourTypesOfStdWrapHookObjectProcessorsDataProvider(): array
    {
        return [
            'preProcess' => [
                'stdWrap_stdWrapPreProcess',
                'stdWrapPreProcess'
            ],
            'override' => [
                'stdWrap_stdWrapOverride',
                'stdWrapOverride'
            ],
            'process' => [
                'stdWrap_stdWrapProcess',
                'stdWrapProcess'
            ],
            'postProcess' => [
                'stdWrap_stdWrapPostProcess',
                'stdWrapPostProcess'
            ],
        ];
    }

    /**
     * Check if stdWrapHookObject processors work properly.
     *
     * Checks:
     *
     * - stdWrap_stdWrapPreProcess
     * - stdWrap_stdWrapOverride
     * - stdWrap_stdWrapProcess
     * - stdWrap_stdWrapPostProcess
     *
     * @test
     * @dataProvider fourTypesOfStdWrapHookObjectProcessorsDataProvider
     * @param string $stdWrapMethod : The method to cover.
     * @param string $hookObjectCall : The expected hook object call.
     */
    public function fourTypesOfStdWrapHookObjectProcessors(
        string $stdWrapMethod,
        string $hookObjectCall
    ): void {
        $conf = [StringUtility::getUniqueId('conf')];
        $content = StringUtility::getUniqueId('content');
        $processed1 = StringUtility::getUniqueId('processed1');
        $processed2 = StringUtility::getUniqueId('processed2');
        $hookObject1 = $this->createMock(
            ContentObjectStdWrapHookInterface::class
        );
        $hookObject1->expects(self::once())
            ->method($hookObjectCall)
            ->with($content, $conf)
            ->willReturn($processed1);
        $hookObject2 = $this->createMock(
            ContentObjectStdWrapHookInterface::class
        );
        $hookObject2->expects(self::once())
            ->method($hookObjectCall)
            ->with($processed1, $conf)
            ->willReturn($processed2);
        $this->subject->_set(
            'stdWrapHookObjects',
            [$hookObject1, $hookObject2]
        );
        $result = $this->subject->$stdWrapMethod($content, $conf);
        self::assertSame($processed2, $result);
    }

    /**
     * Data provider for stdWrap_HTMLparser
     *
     * @return array [$expect, $content, $conf, $times, $will].
     */
    public function stdWrap_HTMLparserDataProvider(): array
    {
        $content = StringUtility::getUniqueId('content');
        $parsed = StringUtility::getUniqueId('parsed');
        return [
            'no config' => [
                $content,
                $content,
                [],
                0,
                $parsed
            ],
            'no array' => [
                $content,
                $content,
                ['HTMLparser.' => 1],
                0,
                $parsed
            ],
            'empty array' => [
                $parsed,
                $content,
                ['HTMLparser.' => []],
                1,
                $parsed
            ],
            'non-empty array' => [
                $parsed,
                $content,
                ['HTMLparser.' => [true]],
                1,
                $parsed
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
     * @test
     * @dataProvider stdWrap_HTMLparserDataProvider
     * @param string $expect The expected output.
     * @param string $content The given content.
     * @param array $conf The given configuration.
     * @param int $times Times HTMLparser_TSbridge is called (0 or 1).
     * @param string $will Return of HTMLparser_TSbridge.
     */
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

    /**
     * @return array
     */
    public function stdWrap_addPageCacheTagsAddsPageTagsDataProvider(): array
    {
        return [
            'No Tag' => [
                [],
                ['addPageCacheTags' => ''],
            ],
            'Two expectedTags' => [
                ['tag1', 'tag2'],
                ['addPageCacheTags' => 'tag1,tag2'],
            ],
            'Two expectedTags plus one with stdWrap' => [
                ['tag1', 'tag2', 'tag3'],
                [
                    'addPageCacheTags' => 'tag1,tag2',
                    'addPageCacheTags.' => ['wrap' => '|,tag3']
                ],
            ],
        ];
    }

    /**
     * @param array $expectedTags
     * @param array $configuration
     * @test
     * @dataProvider stdWrap_addPageCacheTagsAddsPageTagsDataProvider
     */
    public function stdWrap_addPageCacheTagsAddsPageTags(array $expectedTags, array $configuration): void
    {
        $this->subject->stdWrap_addPageCacheTags('', $configuration);
        self::assertEquals($expectedTags, $this->frontendControllerMock->_get('pageCacheTags'));
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     * @return string[][] Order expected, given, xhtmlDoctype
     */
    public function stdWrapBrDataProvider(): array
    {
        return [
            'no xhtml with LF in between' => [
                'one<br>' . LF . 'two',
                'one' . LF . 'two',
                null
            ],
            'no xhtml with LF in between and around' => [
                '<br>' . LF . 'one<br>' . LF . 'two<br>' . LF,
                LF . 'one' . LF . 'two' . LF,
                null
            ],
            'xhtml with LF in between' => [
                'one<br />' . LF . 'two',
                'one' . LF . 'two',
                'xhtml_strict'
            ],
            'xhtml with LF in between and around' => [
                '<br />' . LF . 'one<br />' . LF . 'two<br />' . LF,
                LF . 'one' . LF . 'two' . LF,
                'xhtml_strict'
            ],
        ];
    }

    /**
     * Test that stdWrap_br works as expected.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param string $xhtmlDoctype Xhtml document type.
     * @test
     * @dataProvider stdWrapBrDataProvider
     */
    public function stdWrap_br($expected, $input, $xhtmlDoctype): void
    {
        $GLOBALS['TSFE']->xhtmlDoctype = $xhtmlDoctype;
        self::assertSame($expected, $this->subject->stdWrap_br($input));
    }

    /**
     * Data provider for stdWrap_brTag
     *
     * @return array
     */
    public function stdWrapBrTagDataProvider(): array
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
                $config1
            ],
            'config1: one break at the end' => ['one' . LF . 'two' . LF, 'one<br/>two<br/>', $config1],
            'config1: multiple breaks at the end' => ['one' . LF . 'two' . LF . LF, 'one<br/>two<br/><br/>', $config1],

            'config2: one break at the beginning' => [LF . 'one' . LF . 'two', '<br>one<br>two', $config2],
            'config2: multiple breaks at the beginning' => [
                LF . LF . 'one' . LF . 'two',
                '<br><br>one<br>two',
                $config2
            ],
            'config2: one break at the end' => ['one' . LF . 'two' . LF, 'one<br>two<br>', $config2],
            'config2: multiple breaks at the end' => ['one' . LF . 'two' . LF . LF, 'one<br>two<br><br>', $config2],
        ];
    }

    /**
     * Check if brTag works properly
     *
     * @test
     * @dataProvider stdWrapBrTagDataProvider
     * @param string $input
     * @param string $expected
     * @param array $config
     */
    public function stdWrap_brTag(string $input, string $expected, array $config): void
    {
        self::assertEquals($expected, $this->subject->stdWrap_brTag($input, $config));
    }

    /**
     * Data provider for stdWrap_bytes.
     *
     * @return array [$expect, $content, $conf]
     */
    public function stdWrap_bytesDataProvider(): array
    {
        return [
            'value 1234 default' => [
                '1.21 Ki',
                '1234',
                ['labels' => '', 'base' => 0],
            ],
            'value 1234 si' => [
                '1.23 k',
                '1234',
                ['labels' => 'si', 'base' => 0],
            ],
            'value 1234 iec' => [
                '1.21 Ki',
                '1234',
                ['labels' => 'iec', 'base' => 0],
            ],
            'value 1234 a-i' => [
                '1.23b',
                '1234',
                ['labels' => 'a|b|c|d|e|f|g|h|i', 'base' => 1000],
            ],
            'value 1234 a-i invalid base' => [
                '1.21b',
                '1234',
                ['labels' => 'a|b|c|d|e|f|g|h|i', 'base' => 54],
            ],
            'value 1234567890 default' => [
                '1.15 Gi',
                '1234567890',
                ['labels' => '', 'base' => 0],
            ]
        ];
    }

    /**
     * Check if stdWrap_bytes works properly.
     *
     * Show:
     *
     * - Delegates to GeneralUtility::formatSize
     * - Parameter 1 is $conf['bytes.'][labels'].
     * - Parameter 2 is $conf['bytes.'][base'].
     * - Returns the return value.
     *
     * Note: As PHPUnit can't mock static methods, the call to
     *       GeneralUtility::formatSize can't be easily intercepted. The test
     *       is done by testing input/output pairs instead. To not duplicate
     *       the testing of formatSize just a few smoke tests are done here.
     *
     * @test
     * @dataProvider stdWrap_bytesDataProvider
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @param array $conf The given configuration for 'bytes.'.
     */
    public function stdWrap_bytes(string $expect, string $content, array $conf): void
    {
        $locale = 'en_US.UTF-8';
        try {
            $this->setLocale(LC_NUMERIC, $locale);
        } catch (Exception $e) {
            self::markTestSkipped('Locale ' . $locale . ' is not available.');
        }
        $conf = ['bytes.' => $conf];
        self::assertSame(
            $expect,
            $this->subject->stdWrap_bytes($content, $conf)
        );
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
     *
     * @test
     */
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

    /**
     * Data provider for stdWrap_orderedStdWrap.
     *
     * @return array [$firstConf, $secondConf, $conf]
     */
    public function stdWrap_orderedStdWrapDataProvider(): array
    {
        $confA = [StringUtility::getUniqueId('conf A')];
        $confB = [StringUtility::getUniqueId('conf B')];
        return [
            'standard case: order 1, 2' => [
                $confA,
                $confB,
                ['1.' => $confA, '2.' => $confB]
            ],
            'inverted: order 2, 1' => [
                $confB,
                $confA,
                ['2.' => $confA, '1.' => $confB]
            ],
            '0 as integer: order 0, 2' => [
                $confA,
                $confB,
                ['0.' => $confA, '2.' => $confB]
            ],
            'negative integers: order 2, -2' => [
                $confB,
                $confA,
                ['2.' => $confA, '-2.' => $confB]
            ],
            'chars are casted to key 0, that is not in the array' => [
                null,
                $confB,
                ['2.' => $confB, 'xxx.' => $confA]
            ],
        ];
    }

    /**
     * Check if stdWrap_orderedStdWrap works properly.
     *
     * Show:
     *
     * - For each entry of $conf['orderedStdWrap.'] stdWrap is applied
     *   to $content.
     * - The order is defined by the keys, after they have been casted
     *   to integers.
     * - Returns the processed $content after all entries have been applied.
     *
     * Each test calls stdWrap two times. First $content is processed to
     * $between, second $between is processed to $expect, the final return
     * value. It is checked, if the expected parameters are given in the right
     * consecutive order to stdWrap.
     *
     * @test
     * @dataProvider stdWrap_orderedStdWrapDataProvider
     * @param array|null $firstConf Parameter 2 expected by first call to stdWrap.
     * @param array $secondConf Parameter 2 expected by second call to stdWrap.
     * @param array $conf The given configuration.
     */
    public function stdWrap_orderedStdWrap($firstConf, array $secondConf, array $conf): void
    {
        $content = StringUtility::getUniqueId('content');
        $between = StringUtility::getUniqueId('between');
        $expect = StringUtility::getUniqueId('expect');
        $conf['orderedStdWrap.'] = $conf;
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['stdWrap'])->getMock();
        $subject
            ->expects(self::exactly(2))
            ->method('stdWrap')
            ->withConsecutive([$content, $firstConf], [$between, $secondConf])
            ->will(self::onConsecutiveCalls($between, $expect));
        self::assertSame(
            $expect,
            $subject->stdWrap_orderedStdWrap($content, $conf)
        );
    }

    /**
     * Data provider for stdWrap_cacheRead
     *
     * @return array Order: expect, input, conf, times, with, will
     */
    public function stdWrap_cacheReadDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_cacheReadDataProvider
     * @param string $expect Expected result.
     * @param string $input Given input string.
     * @param array $conf Property 'cache.'
     * @param int $times Times called mocked method.
     * @param string|null $with Parameter passed to mocked method.
     * @param string|false $will Return value of mocked method.
     */
    public function stdWrap_cacheRead(
        string $expect,
        string $input,
        array $conf,
        int $times,
        $with,
        $will
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
    public function stdWrap_cacheStoreDataProvider(): array
    {
        $confCache = [StringUtility::getUniqueId('cache.')];
        $key = [StringUtility::getUniqueId('key')];
        return [
            'Return immediate with no conf' => [
                null,
                0,
                null,
                0,
            ],
            'Return immediate with empty key' => [
                $confCache,
                1,
                '0',
                0,
            ],
            'Call all methods' => [
                $confCache,
                1,
                $key,
                1,
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
     * - Calls calculateCacheKey with $conf['cache.'].
     * - Calls calculateCacheTags with $conf['cache.'].
     * - Calls calculateCacheLifetime with $conf['cache.'].
     * - Calls all configured user functions with $params, $this.
     * - Calls set on the cache frontend with $key, $content, $tags, $lifetime.
     *
     * @test
     * @dataProvider stdWrap_cacheStoreDataProvider
     * @param array|null $confCache Configuration of 'cache.'
     * @param int $timesCCK Times calculateCacheKey is called.
     * @param string|null $key The return value of calculateCacheKey.
     * @param int $times Times the other methods are called.
     */
    public function stdWrap_cacheStore(
        $confCache,
        int $timesCCK,
        $key,
        int $times
    ): void {
        $content = StringUtility::getUniqueId('content');
        $conf = [];
        $conf['cache.'] = $confCache;
        $tags = [StringUtility::getUniqueId('tags')];
        $lifetime = StringUtility::getUniqueId('lifetime');
        $params = [
            'key' => $key,
            'content' => $content,
            'lifetime' => $lifetime,
            'tags' => $tags
        ];
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            [
                'calculateCacheKey',
                'calculateCacheTags',
                'calculateCacheLifetime'
            ]
        );
        $subject
            ->expects(self::exactly($timesCCK))
            ->method('calculateCacheKey')
            ->with($confCache)
            ->willReturn($key);
        $subject
            ->expects(self::exactly($times))
            ->method('calculateCacheTags')
            ->with($confCache)
            ->willReturn($tags);
        $subject
            ->expects(self::exactly($times))
            ->method('calculateCacheLifetime')
            ->with($confCache)
            ->willReturn($lifetime);
        $cacheFrontend = $this->createMock(CacheFrontendInterface::class);
        $cacheFrontend
            ->expects(self::exactly($times))
            ->method('set')
            ->with($key, $content, $tags, $lifetime)
            ->willReturn(null);
        $cacheManager = $this->createMock(CacheManager::class);
        $cacheManager
            ->method('getCache')
            ->willReturn($cacheFrontend);
        GeneralUtility::setSingletonInstance(
            CacheManager::class,
            $cacheManager
        );
        [$countCalls, $test] = [0, $this];
        $closure = function ($par1, $par2) use (
            $test,
            $subject,
            $params,
            &$countCalls
        ) {
            $test->assertSame($params, $par1);
            $test->assertSame($subject, $par2);
            $countCalls++;
        };
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['stdWrap_cacheStore'] = [
            $closure,
            $closure,
            $closure
        ];
        self::assertSame(
            $content,
            $subject->stdWrap_cacheStore($content, $conf)
        );
        self::assertSame($times * 3, $countCalls);
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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

    /**
     * Data provider for stdWrap_csConv
     *
     * @return array Order expected, input, conf
     */
    public function stdWrap_csConvDataProvider(): array
    {
        return [
            'empty string from ISO-8859-15' => [
                '',
                mb_convert_encoding('', 'ISO-8859-15', 'UTF-8'),
                ['csConv' => 'ISO-8859-15']
            ],
            'empty string from BIG-5' => [
                '',
                mb_convert_encoding('', 'BIG-5'),
                ['csConv' => 'BIG-5']
            ],
            '"0" from ISO-8859-15' => [
                '0',
                mb_convert_encoding('0', 'ISO-8859-15', 'UTF-8'),
                ['csConv' => 'ISO-8859-15']
            ],
            '"0" from BIG-5' => [
                '0',
                mb_convert_encoding('0', 'BIG-5'),
                ['csConv' => 'BIG-5']
            ],
            'euro symbol from ISO-88859-15' => [
                '€',
                mb_convert_encoding('€', 'ISO-8859-15', 'UTF-8'),
                ['csConv' => 'ISO-8859-15']
            ],
            'good morning from BIG-5' => [
                '早安',
                mb_convert_encoding('早安', 'BIG-5'),
                ['csConv' => 'BIG-5']
            ],
        ];
    }

    /**
     * Check if stdWrap_csConv works properly.
     *
     * @test
     * @dataProvider stdWrap_csConvDataProvider
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf Property: csConv
     */
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
     *
     * @test
     */
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
     * @return array [$expect, $data, $alt]
     */
    public function stdWrap_dataDataProvider(): array
    {
        $data = [StringUtility::getUniqueId('data')];
        $alt = [StringUtility::getUniqueId('alternativeData')];
        return [
            'default' => [$data, $data, ''],
            'alt is array' => [$alt, $data, $alt],
            'alt is empty array' => [[], $data, []],
            'alt null' => [$data, $data, null],
            'alt string' => [$data, $data, 'xxx'],
            'alt int' => [$data, $data, 1],
            'alt bool' => [$data, $data, true],
        ];
    }

    /**
     * Checks that stdWrap_data works properly.
     *
     * Show:
     *
     * - Delegates to method getData.
     * - Parameter 1 is $conf['data'].
     * - Parameter 2 is property data by default.
     * - Parameter 2 is property alternativeData, if set as array.
     * - Property alternativeData is always unset to ''.
     * - Returns the return value.
     *
     * @test
     * @dataProvider stdWrap_dataDataProvider
     * @param array $expect Expect either $data or $alternativeData.
     * @param array $data The data.
     * @param mixed $alt The alternativeData.
     */
    public function stdWrap_data(array $expect, array $data, $alt): void
    {
        $conf = ['data' => StringUtility::getUniqueId('conf.data')];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getAccessibleMock(
            ContentObjectRenderer::class,
            ['getData']
        );
        $subject->_set('data', $data);
        $subject->_set('alternativeData', $alt);
        $subject
            ->expects(self::once())
            ->method('getData')
            ->with($conf['data'], $expect)
            ->willReturn($return);
        self::assertSame($return, $subject->stdWrap_data('discard', $conf));
        self::assertSame('', $subject->_get('alternativeData'));
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
     *
     * @test
     */
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
    public function stdWrap_dateDataProvider(): array
    {
        // Fictive execution time: 2015-10-02 12:00
        $now = 1443780000;
        return [
            'given timestamp' => [
                '02.10.2015',
                $now,
                ['date' => 'd.m.Y'],
                $now
            ],
            'empty string' => [
                '02.10.2015',
                '',
                ['date' => 'd.m.Y'],
                $now
            ],
            'testing null' => [
                '02.10.2015',
                null,
                ['date' => 'd.m.Y'],
                $now
            ],
            'given timestamp return GMT' => [
                '02.10.2015 10:00:00',
                $now,
                [
                    'date' => 'd.m.Y H:i:s',
                    'date.' => ['GMT' => true],
                ],
                $now
            ]
        ];
    }

    /**
     * Check if stdWrap_date works properly.
     *
     * @test
     * @dataProvider stdWrap_dateDataProvider
     * @param string $expected The expected output.
     * @param mixed $content The given input.
     * @param array $conf The given configuration.
     * @param int $now Fictive execution time.
     */
    public function stdWrap_date(string $expected, $content, array $conf, int $now): void
    {
        $GLOBALS['EXEC_TIME'] = $now;
        self::assertEquals(
            $expected,
            $this->subject->stdWrap_date($content, $conf)
        );
    }

    /**
     * Check if stdWrap_debug works properly.
     *
     * @test
     */
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
     *
     * - Calls the function debug.
     * - Parameter 1 is $this->data.
     * - Parameter 2 is the string '$cObj->data:'.
     * - If $this->alternativeData is an array the same is repeated with:
     * - Parameter 1 is $this->alternativeData.
     * - Parameter 2 is the string '$cObj->alternativeData:'.
     * - Returns $content as is.
     *
     * Note 1:
     *
     *   As PHPUnit can't mock PHP function calls, the call to debug can't be
     *   easily intercepted. The test is done indirectly by catching the
     *   frontend output of debug.
     *
     * Note 2:
     *
     *   The second parameter to the debug function isn't used by the current
     *   implementation at all. It can't even indirectly be tested.
     *
     * @test
     */
    public function stdWrap_debugData(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['devIPmask'] = '*';
        $content = StringUtility::getUniqueId('content');
        $key = StringUtility::getUniqueId('key');
        $value = StringUtility::getUniqueId('value');
        $altValue = StringUtility::getUniqueId('value alt');
        $this->subject->data = [$key => $value];
        // Without alternative data only data is returned.
        ob_start();
        $result = $this->subject->stdWrap_debugData($content);
        $out = ob_get_clean();
        self::assertSame($result, $content);
        self::assertStringContainsString('$cObj->data', $out);
        self::assertStringContainsString($value, $out);
        self::assertStringNotContainsString($altValue, $out);
        // By adding alternative data both are returned together.
        $this->subject->alternativeData = [$key => $altValue];
        ob_start();
        $this->subject->stdWrap_debugData($content);
        $out = ob_get_clean();
        self::assertStringNotContainsString('$cObj->alternativeData', $out);
        self::assertStringContainsString($value, $out);
        self::assertStringContainsString($altValue, $out);
    }

    /**
     * Data provider for stdWrap_debugFunc.
     *
     * @return array [$expectArray, $confDebugFunc]
     */
    public function stdWrap_debugFuncDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_debugFuncDataProvider
     * @param bool $expectArray If cast to array is expected.
     * @param mixed $confDebugFunc The configuration for $conf['debugFunc'].
     */
    public function stdWrap_debugFunc(bool $expectArray, $confDebugFunc): void
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
    public function stdWrapDoubleBrTagDataProvider(): array
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
     * @test
     * @dataProvider stdWrapDoubleBrTagDataProvider
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $config The property 'doubleBrTag'.
     */
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
     *
     * @test
     */
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
     *
     * @test
     * @dataProvider html5SelfClosingTagsDataprovider
     * @param string $input
     * @param string $expected
     */
    public function stdWrap_encapsLines_HTML5SelfClosingTags(string $input, string $expected): void
    {
        $rteParseFunc = $this->getLibParseFunc_RTE();

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

    /**
     * @return array
     */
    public function html5SelfClosingTagsDataprovider(): array
    {
        return [
            'areaTag_selfclosing' => [
                'input' => 'area',
                'expected' => '<area id="myId" class="bodytext" />'
            ],
            'base_selfclosing' => [
                'input' => 'base',
                'expected' => '<base id="myId" class="bodytext" />'
            ],
            'br_selfclosing' => [
                'input' => 'br',
                'expected' => '<br id="myId" class="bodytext" />'
            ],
            'col_selfclosing' => [
                'input' => 'col',
                'expected' => '<col id="myId" class="bodytext" />'
            ],
            'embed_selfclosing' => [
                'input' => 'embed',
                'expected' => '<embed id="myId" class="bodytext" />'
            ],
            'hr_selfclosing' => [
                'input' => 'hr',
                'expected' => '<hr id="myId" class="bodytext" />'
            ],
            'img_selfclosing' => [
                'input' => 'img',
                'expected' => '<img id="myId" class="bodytext" />'
            ],
            'input_selfclosing' => [
                'input' => 'input',
                'expected' => '<input id="myId" class="bodytext" />'
            ],
            'keygen_selfclosing' => [
                'input' => 'keygen',
                'expected' => '<keygen id="myId" class="bodytext" />'
            ],
            'link_selfclosing' => [
                'input' => 'link',
                'expected' => '<link id="myId" class="bodytext" />'
            ],
            'meta_selfclosing' => [
                'input' => 'meta',
                'expected' => '<meta id="myId" class="bodytext" />'
            ],
            'param_selfclosing' => [
                'input' => 'param',
                'expected' => '<param id="myId" class="bodytext" />'
            ],
            'source_selfclosing' => [
                'input' => 'source',
                'expected' => '<source id="myId" class="bodytext" />'
            ],
            'track_selfclosing' => [
                'input' => 'track',
                'expected' => '<track id="myId" class="bodytext" />'
            ],
            'wbr_selfclosing' => [
                'input' => 'wbr',
                'expected' => '<wbr id="myId" class="bodytext" />'
            ],
            'p_notselfclosing' => [
                'input' => 'p',
                'expected' => '<p id="myId" class="bodytext"></p>'
            ],
            'a_notselfclosing' => [
                'input' => 'a',
                'expected' => '<a id="myId" class="bodytext"></a>'
            ],
            'strong_notselfclosing' => [
                'input' => 'strong',
                'expected' => '<strong id="myId" class="bodytext"></strong>'
            ],
            'span_notselfclosing' => [
                'input' => 'span',
                'expected' => '<span id="myId" class="bodytext"></span>'
            ],
        ];
    }

    /**
     * Data provider for stdWrap_encodeForJavaScriptValue.
     *
     * @return array[]
     */
    public function stdWrap_encodeForJavaScriptValueDataProvider(): array
    {
        return [
            'double quote in string' => [
                '\'double\u0020quote\u0022\'',
                'double quote"'
            ],
            'backslash in string' => [
                '\'backslash\u0020\u005C\'',
                'backslash \\'
            ],
            'exclamation mark' => [
                '\'exclamation\u0021\'',
                'exclamation!'
            ],
            'whitespace tab, newline and carriage return' => [
                '\'white\u0009space\u000As\u000D\'',
                "white\tspace\ns\r"
            ],
            'single quote in string' => [
                '\'single\u0020quote\u0020\u0027\'',
                'single quote \''
            ],
            'tag' => [
                '\'\u003Ctag\u003E\'',
                '<tag>'
            ],
            'ampersand in string' => [
                '\'amper\u0026sand\'',
                'amper&sand'
            ]
        ];
    }

    /**
     * Check if encodeForJavaScriptValue works properly.
     *
     * @test
     * @dataProvider stdWrap_encodeForJavaScriptValueDataProvider
     * @param string $expect The expected output.
     * @param string $content The given input.
     */
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
    public function stdWrap_expandListDataProvider(): array
    {
        return [
            'numbers' => ['1,2,3', '1,2,3'],
            'range' => ['3,4,5', '3-5'],
            'numbers and range' => ['1,3,4,5,7', '1,3-5,7']
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
     * @test
     * @dataProvider stdWrap_expandListDataProvider
     * @param string $expected The expected output.
     * @param string $content The given content.
     */
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
     *
     * @test
     */
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
    public function stdWrap_fieldRequiredDataProvider(): array
    {
        $content = StringUtility::getUniqueId('content');
        return [
            // resulting in boolean false
            'false is false' => [
                '',
                true,
                $content,
                ['fieldRequired' => 'false']
            ],
            'null is false' => [
                '',
                true,
                $content,
                ['fieldRequired' => 'null']
            ],
            'empty string is false' => [
                '',
                true,
                $content,
                ['fieldRequired' => 'empty']
            ],
            'whitespace is false' => [
                '',
                true,
                $content,
                ['fieldRequired' => 'whitespace']
            ],
            'string zero is false' => [
                '',
                true,
                $content,
                ['fieldRequired' => 'stringZero']
            ],
            'string zero with whitespace is false' => [
                '',
                true,
                $content,
                ['fieldRequired' => 'stringZeroWithWhiteSpace']
            ],
            'zero is false' => [
                '',
                true,
                $content,
                ['fieldRequired' => 'zero']
            ],
            // resulting in boolean true
            'true is true' => [
                $content,
                false,
                $content,
                ['fieldRequired' => 'true']
            ],
            'string is true' => [
                $content,
                false,
                $content,
                ['fieldRequired' => 'string']
            ],
            'one is true' => [
                $content,
                false,
                $content,
                ['fieldRequired' => 'one']
            ]
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
     * @test
     * @dataProvider stdWrap_fieldRequiredDataProvider
     * @param string $expect The expected output.
     * @param bool $stop Expect stop further rendering.
     * @param string $content The given input.
     * @param array $conf The given configuration.
     */
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
            'one' => 1
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
    public function hashDataProvider(): array
    {
        return [
            'md5' => [
                'bacb98acf97e0b6112b1d1b650b84971',
                'joh316',
                ['hash' => 'md5']
            ],
            'sha1' => [
                '063b3d108bed9f88fa618c6046de0dccadcf3158',
                'joh316',
                ['hash' => 'sha1']
            ],
            'stdWrap capability' => [
                'bacb98acf97e0b6112b1d1b650b84971',
                'joh316',
                [
                    'hash' => '5',
                    'hash.' => ['wrap' => 'md|']
                ]
            ],
            'non-existing hashing algorithm' => [
                '',
                'joh316',
                ['hash' => 'non-existing']
            ]
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
     * @test
     * @dataProvider hashDataProvider
     * @param string $expect The expected output.
     * @param string $content The given content.
     * @param array $conf The given configuration.
     */
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
    public function stdWrap_htmlSpecialCharsDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_htmlSpecialCharsDataProvider
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @param array $conf htmlSpecialChars.preserveEntities
     */
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
    public function stdWrap_ifDataProvider(): array
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
                null
            ],
            'if. is empty array' => [
                $content,
                false,
                $content,
                ['if.' => []],
                0,
                null
            ],
            'if. is null' => [
                $content,
                false,
                $content,
                ['if.' => null],
                0,
                null
            ],
            'if. is false' => [
                $content,
                false,
                $content,
                ['if.' => false],
                0,
                null
            ],
            'if. is 0' => [
                $content,
                false,
                $content,
                ['if.' => false],
                0,
                null
            ],
            'if. is "0"' => [
                $content,
                false,
                $content,
                ['if.' => '0'],
                0,
                null
            ],
            'checkIf returning true' => [
                $content,
                false,
                $content,
                $conf,
                1,
                true
            ],
            // evals to false
            'checkIf returning false' => [
                '',
                true,
                $content,
                $conf,
                1,
                false
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
     * @test
     * @dataProvider stdWrap_ifDataProvider
     * @param string $expect The expected output.
     * @param bool $stop Expect stop further rendering.
     * @param mixed $content The given content.
     * @param array $conf
     * @param int $times Times checkIf is called (0 or 1).
     * @param bool|null $will Return of checkIf (null if not called).
     */
    public function stdWrap_if(string $expect, bool $stop, string $content, array $conf, int $times, $will): void
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
    public function checkIfDataProvider(): array
    {
        return [
            'true bitAnd the same' => [true, ['bitAnd' => '4', 'value' => '4']],
            'true bitAnd included' => [true, ['bitAnd' => '6', 'value' => '4']],
            'false bitAnd' => [false, ['bitAnd' => '4', 'value' => '3']],
            'negate true bitAnd the same' => [false, ['bitAnd' => '4', 'value' => '4', 'negate' => '1']],
            'negate true bitAnd included' => [false, ['bitAnd' => '6', 'value' => '4', 'negate' => '1']],
            'negate false bitAnd' => [true, ['bitAnd' => '3', 'value' => '4', 'negate' => '1']],
        ];
    }

    /**
     * Check if checkIf works properly.
     *
     * @test
     * @dataProvider checkIfDataProvider
     * @param bool $expect Whether result should be true or false.
     * @param array $conf TypoScript configuration to pass into checkIf
     */
    public function checkIf(bool $expect, array $conf)
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
    public function stdWrap_ifBlankDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_ifBlankDataProvider
     * @param mixed $expect
     * @param mixed $content The given input.
     * @param array $conf The given configuration.
     */
    public function stdWrap_ifBlank($expect, $content, array $conf): void
    {
        $result = $this->subject->stdWrap_ifBlank($content, $conf);
        self::assertSame($expect, $result);
    }

    /**
     * Data provider for stdWrap_ifEmpty.
     *
     * @return array [$expect, $content, $conf]
     */
    public function stdWrap_ifEmptyDataProvider(): array
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
                $conf
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
     * @test
     * @dataProvider stdWrap_ifEmptyDataProvider
     * @param mixed $expect The expected output.
     * @param mixed $content The given content.
     * @param array $conf The given configuration.
     */
    public function stdWrap_ifEmpty($expect, $content, array $conf): void
    {
        $result = $this->subject->stdWrap_ifEmpty($content, $conf);
        self::assertSame($expect, $result);
    }

    /**
     * Data provider for stdWrap_ifNull.
     *
     * @return array [$expect, $content, $conf]
     */
    public function stdWrap_ifNullDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_ifNullDataProvider
     * @param mixed $expect
     * @param mixed $content The given input.
     * @param array $conf The given configuration.
     */
    public function stdWrap_ifNull($expect, $content, array $conf): void
    {
        $result = $this->subject->stdWrap_ifNull($content, $conf);
        self::assertSame($expect, $result);
    }

    /**
     * Data provider for stdWrap_innerWrap
     *
     * @return array Order expected, input, conf
     */
    public function stdWrap_innerWrapDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_innerWrapDataProvider
     */
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
    public function stdWrap_innerWrap2DataProvider(): array
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
     * @test
     * @dataProvider stdWrap_innerWrap2DataProvider
     */
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
     *
     * @test
     */
    public function stdWrap_insertData(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [StringUtility::getUniqueId('conf not used')];
        $return = StringUtility::getUniqueId('return');
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['insertData'])->getMock();
        $subject->expects(self::once())->method('insertData')
            ->with($content)->willReturn($return);
        self::assertSame(
            $return,
            $subject->stdWrap_insertData($content, $conf)
        );
    }

    /**
     * Data provider for stdWrap_insertData
     *
     * @return array [$expect, $content]
     */
    public function stdWrap_insertDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_insertDataProvider
     * @param mixed $expect The expected output.
     * @param string $content The given input.
     */
    public function stdWrap_insertDataAndInputExamples($expect, string $content): void
    {
        self::assertSame($expect, $this->subject->stdWrap_insertData($content));
    }

    /**
     * Data provider for stdWrap_intval
     *
     * @return array [$expect, $content]
     */
    public function stdWrap_intvalDataProvider(): array
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
            'false' => [0, false]
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
     * @test
     * @dataProvider stdWrap_intvalDataProvider
     * @param int $expect The expected output.
     * @param mixed $content The given input.
     */
    public function stdWrap_intval(int $expect, $content): void
    {
        self::assertSame($expect, $this->subject->stdWrap_intval($content));
    }

    /**
     * Data provider for stdWrap_keywords
     *
     * @return string[][] Order expected, input
     */
    public function stdWrapKeywordsDataProvider(): array
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
                'one' . PHP_EOL . 'two' . PHP_EOL . 'three'
            ],
            ', sourounded' => [',one,two,', ' , one , two , '],
            '; sourounded' => [',one,two,', ' ; one ; two ; '],
            'nl sourounded' => [
                ',one,two,',
                ' ' . PHP_EOL . ' one ' . PHP_EOL . ' two ' . PHP_EOL . ' '
            ],
            'mixed' => [
                'one,two,three,four',
                ' one, two; three' . PHP_EOL . 'four'
            ],
            'keywods with blanks in words' => [
                'one plus,two minus',
                ' one plus , two minus ',
            ]
        ];
    }

    /**
     * Check if stdWrap_keywords works properly.
     *
     * @param string $expected The expected value.
     * @param string $input The input value.
     * @test
     * @dataProvider stdWrapKeywordsDataProvider
     */
    public function stdWrap_keywords(string $expected, string $input): void
    {
        self::assertSame($expected, $this->subject->stdWrap_keywords($input));
    }

    /**
     * Data provider for stdWrap_lang
     *
     * @return array Order expected, input, conf, language
     */
    public function stdWrap_langDataProvider(): array
    {
        return [
            'empty conf' => [
                'original',
                'original',
                [],
                'de',
            ],
            'translation de' => [
                'Übersetzung',
                'original',
                [
                    'lang.' => [
                        'de' => 'Übersetzung',
                        'it' => 'traduzione',
                    ]
                ],
                'de',
            ],
            'translation it' => [
                'traduzione',
                'original',
                [
                    'lang.' => [
                        'de' => 'Übersetzung',
                        'it' => 'traduzione',
                    ]
                ],
                'it',
            ],
            'no translation' => [
                'original',
                'original',
                [
                    'lang.' => [
                        'de' => 'Übersetzung',
                        'it' => 'traduzione',
                    ]
                ],
                '',
            ],
            'missing label' => [
                'original',
                'original',
                [
                    'lang.' => [
                        'de' => 'Übersetzung',
                        'it' => 'traduzione',
                    ]
                ],
                'fr',
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
     * @test
     * @dataProvider stdWrap_langDataProvider
     */
    public function stdWrap_langViaSiteLanguage(string $expected, string $input, array $conf, string $language): void
    {
        $site = $this->createSiteWithLanguage([
            'base' => '/',
            'languageId' => 2,
            'locale' => 'en_UK',
            'typo3Language' => $language,
        ]);
        $this->frontendControllerMock->_set('language', $site->getLanguageById(2));
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
     *
     * @test
     */
    public function stdWrap_listNum(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'listNum' => StringUtility::getUniqueId('listNum'),
            'listNum.' => [
                'splitChar' => StringUtility::getUniqueId('splitChar')
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
    public function stdWrap_noTrimWrapDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_noTrimWrapDataProvider
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @param array $conf The given configuration.
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
    public function stdWrap_outerWrapDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_outerWrapDataProvider
     */
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
    public function stdWrap_overrideDataProvider(): array
    {
        return [
            'standard case' => [
                'override',
                'content',
                ['override' => 'override']
            ],
            'empty conf does not override' => [
                'content',
                'content',
                []
            ],
            'empty string does not override' => [
                'content',
                'content',
                ['override' => '']
            ],
            'whitespace does not override' => [
                'content',
                'content',
                ['override' => ' ' . "\t"]
            ],
            'zero does not override' => [
                'content',
                'content',
                ['override' => 0]
            ],
            'false does not override' => [
                'content',
                'content',
                ['override' => false]
            ],
            'null does not override' => [
                'content',
                'content',
                ['override' => null]
            ],
            'one does override' => [
                1,
                'content',
                ['override' => 1]
            ],
            'minus one does override' => [
                -1,
                'content',
                ['override' => -1]
            ],
            'float does override' => [
                -0.1,
                'content',
                ['override' => -0.1]
            ],
            'true does override' => [
                true,
                'content',
                ['override' => true]
            ],
            'the value is not trimmed' => [
                "\t" . 'override',
                'content',
                ['override' => "\t" . 'override']
            ],
        ];
    }

    /**
     * Check if stdWrap_override works properly.
     *
     * @test
     * @dataProvider stdWrap_overrideDataProvider
     * @param mixed $expect
     * @param string $content
     * @param array $conf Property: setCurrent
     */
    public function stdWrap_override($expect, string $content, array $conf): void
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @test
     */
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
        $frontend->config = [];
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
            'cObj' => serialize($subject)
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
     *
     * @test
     */
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
     *
     * @test
     */
    public function stdWrap_preIfEmptyListNum(): void
    {
        $content = StringUtility::getUniqueId('content');
        $conf = [
            'preIfEmptyListNum' => StringUtility::getUniqueId('preIfEmptyListNum'),
            'preIfEmptyListNum.' => [
                'splitChar' => StringUtility::getUniqueId('splitChar')
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
    public function stdWrap_prefixCommentDataProvider(): array
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
     *
     * @test
     * @dataProvider stdWrap_prefixCommentDataProvider
     * @param string $expect
     * @param string $content
     * @param array $conf
     * @param $disable
     * @param int $times
     * @param string $will
     */
    public function stdWrap_prefixComment(
        string $expect,
        string $content,
        array $conf,
        $disable,
        int $times,
        string $will
    ): void {
        $this->frontendControllerMock
            ->config['config']['disablePrefixComment'] = $disable;
        $subject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['prefixComment'])->getMock();
        $subject
            ->expects(self::exactly($times))
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
     *
     * @test
     */
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
    public function stdWrap_prioriCalcDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_prioriCalcDataProvider
     * @param mixed $expect The expected output.
     * @param string $content The given content.
     * @param array $conf The given configuration.
     */
    public function stdWrap_prioriCalc($expect, string $content, array $conf): void
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
     *
     * @test
     */
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
    public function stdWrap_rawUrlEncodeDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_rawUrlEncodeDataProvider
     * @param string $expect The expected output.
     * @param string $content The given input.
     */
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
     *
     * @test
     */
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
    public function stdWrap_requiredDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_requiredDataProvider
     * @param mixed $expect The expected output.
     * @param bool $stop Expect stop further rendering.
     * @param mixed $content The given input.
     */
    public function stdWrap_required($expect, bool $stop, $content): void
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
     *
     * @test
     */
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
     *
     * @test
     */
    public function stdWrap_setContentToCurrent(): void
    {
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
    public function stdWrap_setCurrentDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_setCurrentDataProvider
     * @param string $input The input value.
     * @param array $conf Property: setCurrent
     */
    public function stdWrap_setCurrent(string $input, array $conf): void
    {
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
     *
     * @test
     */
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
     *
     * @test
     */
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
     *
     * @return array
     */
    public function stdWrap_stdWrapValueDataProvider(): array
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
                null
            ],
            'existing key and array returns stdWrap' => [
                'test',
                [
                    'test' => 'value',
                    'test.' => ['case' => 'upper'],
                ],
                'default',
                'VALUE'
            ],
            'the string "0" from stdWrap will be returned' => [
                'test',
                [
                    'test' => '',
                    'test.' => [
                        'wrap' => '|0'
                    ]
                ],
                '100',
                '0'
            ]
        ];
    }

    /**
     * @param string $key
     * @param array $configuration
     * @param string|null $defaultValue
     * @param string|null $expected
     * @dataProvider stdWrap_stdWrapValueDataProvider
     * @test
     */
    public function stdWrap_stdWrapValue(
        string $key,
        array $configuration,
        $defaultValue,
        $expected
    ): void {
        $result = $this->subject->stdWrapValue($key, $configuration, $defaultValue);
        self::assertSame($expected, $result);
    }

    /**
     * Data provider for stdWrap_strPad.
     *
     * @return array [$expect, $content, $conf]
     */
    public function stdWrap_strPadDataProvider(): array
    {
        return [
            'pad string with default settings and length 10' => [
                'Alien     ',
                'Alien',
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
     * @test
     * @dataProvider stdWrap_strPadDataProvider
     * @param string $expect The expected output.
     * @param string $content The given input.
     * @param array $conf The configuration of 'strPad.'.
     */
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
    public function stdWrap_strftimeDataProvider(): array
    {
        // Fictive execution time is 2012-09-01 12:00 in UTC/GMT.
        $now = 1346500800;
        return [
            'given timestamp' => [
                '01-09-2012',
                $now,
                ['strftime' => '%d-%m-%Y'],
                $now
            ],
            'empty string' => [
                '01-09-2012',
                '',
                ['strftime' => '%d-%m-%Y'],
                $now
            ],
            'testing null' => [
                '01-09-2012',
                null,
                ['strftime' => '%d-%m-%Y'],
                $now
            ]
        ];
    }

    /**
     * Check if stdWrap_strftime works properly.
     *
     * @test
     * @dataProvider stdWrap_strftimeDataProvider
     * @param string $expect The expected output.
     * @param mixed $content The given input.
     * @param array $conf The given configuration.
     * @param int $now Fictive execution time.
     */
    public function stdWrap_strftime(string $expect, $content, array $conf, int $now): void
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
     *
     * @test
     */
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
    public function stdWrap_strtotimeDataProvider(): array
    {
        return [
            'date from content' => [
                1417651200,
                '2014-12-04',
                ['strtotime' => '1']
            ],
            'manipulation of date from content' => [
                1417996800,
                '2014-12-04',
                ['strtotime' => '+ 2 weekdays']
            ],
            'date from configuration' => [
                1417651200,
                '',
                ['strtotime' => '2014-12-04']
            ],
            'manipulation of date from configuration' => [
                1417996800,
                '',
                ['strtotime' => '2014-12-04 + 2 weekdays']
            ],
            'empty input' => [
                false,
                '',
                ['strtotime' => '1']
            ],
            'date from content and configuration' => [
                false,
                '2014-12-04',
                ['strtotime' => '2014-12-05']
            ]
        ];
    }

    /**
     * Check if stdWrap_strtotime works properly.
     *
     * @test
     * @dataProvider stdWrap_strtotimeDataProvider
     * @param int|null $expect The expected output.
     * @param string $content The given input.
     * @param array $conf The given configuration.
     */
    public function stdWrap_strtotime($expect, string $content, array $conf): void
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
     *
     * @test
     */
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
    public function stdWrap_trimDataProvider(): array
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
     *
     * @test
     * @dataProvider stdWrap_trimDataProvider
     * @param string $expect
     * @param mixed $content The given content.
     */
    public function stdWrap_trim(string $expect, $content): void
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
     *
     * @test
     */
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
    public function stdWrap_wrapDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_wrapDataProvider
     */
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
    public function stdWrap_wrap2DataProvider(): array
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
     * @test
     * @dataProvider stdWrap_wrap2DataProvider
     */
    public function stdWrap_wrap2(string $expected, string $input, array $conf): void
    {
        self::assertSame($expected, $this->subject->stdWrap_wrap2($input, $conf));
    }

    /**
     * Data provider for stdWrap_wrap3
     *
     * @return array Order expected, input, conf
     */
    public function stdWrap_wrap3DataProvider(): array
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
     * @test
     * @dataProvider stdWrap_wrap3DataProvider
     */
    public function stdWrap_wrap3(string $expected, string $input, array $conf): void
    {
        self::assertSame($expected, $this->subject->stdWrap_wrap3($input, $conf));
    }

    /**
     * Data provider for stdWrap_wrapAlign.
     *
     * @return array [$expect, $content, $conf]
     */
    public function stdWrap_wrapAlignDataProvider(): array
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
     * @test
     * @dataProvider stdWrap_wrapAlignDataProvider
     * @param string $expect The expected output.
     * @param string $content The given content.
     * @param mixed $wrapAlignConf The given input.
     */
    public function stdWrap_wrapAlign(string $expect, string $content, $wrapAlignConf): void
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
     *
     * @test
     */
    public function getCurrentTable(): void
    {
        self::assertEquals('tt_content', $this->subject->getCurrentTable());
    }

    /**
     * Data provider for prefixComment.
     *
     * @return array [$expect, $comment, $content]
     */
    public function prefixCommentDataProvider(): array
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
                $content
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
                $content
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
                '<' . $content . '>'
            ],
        ];
    }

    /**
     * Check if prefixComment works properly.
     *
     * @test
     * @dataProvider prefixCommentDataProvider
     * @param string $expect The expected output.
     * @param string $comment The parameter $comment.
     * @param string $content The parameter $content.
     */
    public function prefixComment(string $expect, string $comment, string $content): void
    {
        // The parameter $conf is never used. Just provide null.
        // Consider to improve the signature and deprecate the old one.
        $result = $this->subject->prefixComment($comment, null, $content);
        self::assertEquals($expect, $result);
    }

    /**
     * Check setter and getter of currentFile work properly.
     *
     * @test
     */
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
     * @test
     * @see stdWrap_current()
     */
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
     *
     * @test
     */
    public function setUserObjectType_getUserObjectType(): void
    {
        $value = StringUtility::getUniqueId();
        $this->subject->setUserObjectType($value);
        self::assertEquals($value, $this->subject->getUserObjectType());
    }

    /**
     * Data provider for emailSpamProtectionWithTypeAscii
     *
     * @return array [$content, $expect]
     */
    public function emailSpamProtectionWithTypeAsciiDataProvider(): array
    {
        return [
            'Simple email address' => [
                'test@email.tld',
                '&#116;&#101;&#115;&#116;&#64;&#101;&#109;&#97;&#105;&#108;&#46;&#116;&#108;&#100;'
            ],
            'Simple email address with unicode characters' => [
                'matthäus@email.tld',
                '&#109;&#97;&#116;&#116;&#104;&#228;&#117;&#115;&#64;&#101;&#109;&#97;&#105;&#108;&#46;&#116;&#108;&#100;'
            ],
            'Susceptible email address' => [
                '"><script>alert(\'emailSpamProtection\')</script>',
                '&#34;&#62;&#60;&#115;&#99;&#114;&#105;&#112;&#116;&#62;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#101;&#109;&#97;&#105;&#108;&#83;&#112;&#97;&#109;&#80;&#114;&#111;&#116;&#101;&#99;&#116;&#105;&#111;&#110;&#39;&#41;&#60;&#47;&#115;&#99;&#114;&#105;&#112;&#116;&#62;'

            ],
            'Susceptible email address with unicode characters' => [
                '"><script>alert(\'ȅmǡilSpamProtȅction\')</script>',
                '&#34;&#62;&#60;&#115;&#99;&#114;&#105;&#112;&#116;&#62;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#517;&#109;&#481;&#105;&#108;&#83;&#112;&#97;&#109;&#80;&#114;&#111;&#116;&#517;&#99;&#116;&#105;&#111;&#110;&#39;&#41;&#60;&#47;&#115;&#99;&#114;&#105;&#112;&#116;&#62;'
            ],
        ];
    }

    /**
     * Check if email spam protection processes all UTF-8 characters properly
     *
     * @test
     * @dataProvider emailSpamProtectionWithTypeAsciiDataProvider
     * @param string $content The parameter $content.
     * @param string $expected The expected output.
     */
    public function mailSpamProtectionWithTypeAscii(string $content, string $expected): void
    {
        self::assertSame(
            $expected,
            $this->subject->_call('encryptEmail', $content, 'ascii')
        );
    }

    public function getGlobalDataProvider(): array
    {
        return [
            'simple' => [
                'foo',
                'HTTP_SERVER_VARS | something',
                [
                    'HTTP_SERVER_VARS' => [
                        'something' => 'foo',
                    ]
                ],
                null
            ],
            'simple source fallback' => [
                'foo',
                'HTTP_SERVER_VARS | something',
                null,
                [
                    'HTTP_SERVER_VARS' => [
                        'something' => 'foo',
                    ]
                ],
            ],
            'globals ignored if source given' => [
                '',
                'HTTP_SERVER_VARS | something',
                [
                    'HTTP_SERVER_VARS' => [
                        'something' => 'foo',
                    ]
                ],
                [
                    'HTTP_SERVER_VARS' => [
                        'something-else' => 'foo',
                    ]
                ],
            ],
            'sub array is returned as empty string' => [
                '',
                'HTTP_SERVER_VARS | something',
                [
                    'HTTP_SERVER_VARS' => [
                        'something' => ['foo'],
                    ]
                ],
                null
            ],
            'does not exist' => [
                '',
                'HTTP_SERVER_VARS | something',
                [
                    'HTTP_SERVER_VARS' => [
                        'something-else' => 'foo',
                    ]
                ],
                null
            ],
            'does not exist in source' => [
                '',
                'HTTP_SERVER_VARS | something',
                null,
                [
                    'HTTP_SERVER_VARS' => [
                        'something-else' => 'foo',
                    ]
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getGlobalDataProvider
     * @param mixed $expected
     * @param string $key
     * @param array $globals
     * @param null $source
     */
    public function getGlobalReturnsExpectedResult($expected, string $key, ?array $globals, ?array $source): void
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
}
