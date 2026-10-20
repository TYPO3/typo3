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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Render;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Event\BeforeRenderTextEvent;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\Parser\UnsafeHTML;
use TYPO3Fluid\Fluid\Core\Parser\UnsafeHTMLString;
use TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentValueException;
use TYPO3Fluid\Fluid\View\TemplateView;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\Enum\ContentType;
use TYPO3Tests\BlogExample\Domain\Model\TtContent;
use TYPO3Tests\BlogExample\Domain\Model\TtContentWithCType;

final class TextViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    #[Test]
    #[DataProvider('renderingDataProvider')]
    public function render(mixed $record, string $templateSource, ?string $expected): void
    {
        $context = $this->get(RenderingContextFactory::class)->create([], $this->createRequest());
        $context->getTemplatePaths()->setTemplateSource($templateSource);

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $result = $view->render();
        if ($expected === null) {
            self::assertNull($result);
        } else {
            self::assertInstanceOf(UnsafeHTML::class, $result);
            self::assertSame($expected, (string)$result);
        }

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $result = $view->render();
        if ($expected === null) {
            self::assertNull($result);
        } else {
            self::assertInstanceOf(UnsafeHTML::class, $result);
            self::assertSame($expected, (string)$result);
        }
    }

    public static function renderingDataProvider(): \Generator
    {
        yield 'Record' => [
            'record' => self::createRecord([
                'header' => '<b>Content</b>',
            ]),
            'templateSource' => '<f:render.text record="{record}" field="header" />',
            'expected' => '&lt;b&gt;Content&lt;/b&gt;',
        ];
        yield 'Record inline notation' => [
            'record' => self::createRecord([
                'header' => '<i>Inline</i>',
            ]),
            'templateSource' => '{record -> f:render.text(field: "header")}',
            'expected' => '&lt;i&gt;Inline&lt;/i&gt;',
        ];
        yield 'TextField' => [
            'record' => self::createRecord([
                'rowDescription' => "Line 1\nLine 2",
            ]),
            'templateSource' => '<f:render.text record="{record}" field="rowDescription" />',
            'expected' => "Line 1<br />\nLine 2",
        ];
        yield 'RichText' => [
            'record' => self::createRecord([
                'bodytext' => '<p>
    Line <sup>1</sup><br>
    Line 2
</p><script>alert("script escaped")</script>',
            ]),
            'templateSource' => '<f:render.text record="{record}" field="bodytext" />',
            'expected' => '<p>
    Line <sup>1</sup><br>
    Line 2
</p>&lt;script&gt;alert("script escaped")&lt;/script&gt;',
        ];
        yield 'Input field from PageInformation' => [
            'record' => self::createPageInformation([
                'title' => '<b>My Page</b>',
            ]),
            'templateSource' => '<f:render.text record="{record}" field="title" />',
            'expected' => '&lt;b&gt;My Page&lt;/b&gt;',
        ];
        yield 'extbaseModel' => [
            'record' => self::createExtbaseModel('<b>My Page</b>'),
            'templateSource' => '<f:render.text record="{record}" field="title" />',
            'expected' => '&lt;b&gt;My Page&lt;/b&gt;',
        ];
        yield 'with optional flag, not available record property' => [
            'record' => self::createRecord([]),
            'templateSource' => '{record -> f:render.text(field: "notAvailable", optional: "{true}")}',
            'expected' => null,
        ];
        yield 'with optional flag, not available field in extbaseModel' => [
            'record' => self::createExtbaseModel('<b>My Page</b>'),
            'templateSource' => '<f:render.text record="{record}" field="header" optional="{true}" />',
            'expected' => null,
        ];
        $ttContent = new TtContentWithCType();
        $ttContent->setHeader('<b>My Page</b>');
        $ttContent->setCtype('text');
        yield 'TtContentWithCType' => [
            'record' => $ttContent,
            'templateSource' => '<f:render.text record="{record}" field="header" />',
            'expected' => '&lt;b&gt;My Page&lt;/b&gt;',
        ];
        $ttContent = new TtContentWithCType();
        $ttContent->setHeader('<b>My Page</b>');
        $ttContent->setCtype(ContentType::TEXT);
        yield 'TtContentWithCTypeBackedEnum' => [
            'record' => $ttContent,
            'templateSource' => '<f:render.text record="{record}" field="header" />',
            'expected' => '&lt;b&gt;My Page&lt;/b&gt;',
        ];
    }

    #[Test]
    #[DataProvider('renderTextEventDataProvider')]
    public function eventCanTakeOverRendering(
        RecordInterface|DomainObjectInterface|PageInformation $record,
        string $field,
        string $fullType,
        string $value,
        string $html,
        ?int $applicationType = SystemEnvironmentBuilder::REQUESTTYPE_FE,
    ): void {
        $request = $applicationType === null ? null : $this->createRequest()->withAttribute('applicationType', $applicationType);
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource('<f:render.text record="{record}" field="{field}" />');
        $result = new UnsafeHTMLString($html);
        $events = [];
        $this->addRenderTextListener('inspect', static function (BeforeRenderTextEvent $event) use (&$events): void {
            self::assertNull($event->getRenderedText());
            self::assertFalse($event->isPropagationStopped());
            $events[] = $event;
        });
        $this->addRenderTextListener('render', static fn(BeforeRenderTextEvent $event) => $event->setRenderedText($result));
        $this->addRenderTextListener('skipped', static fn() => self::fail('Propagation must stop after a result is provided.'));

        for ($iteration = 0; $iteration < 2; $iteration++) {
            $view = new TemplateView($context);
            $view->assignMultiple(['record' => $record, 'field' => $field]);
            self::assertSame($result, $view->render());
        }

        self::assertCount(2, $events);
        foreach ($events as $event) {
            if ($record instanceof PageInformation) {
                self::assertInstanceOf(RecordInterface::class, $event->getRecord());
                self::assertSame($record->getId(), $event->getRecord()->getUid());
            } else {
                self::assertSame($record, $event->getRecord());
            }
            self::assertSame(explode('.', $fullType)[0], $event->getTable());
            self::assertSame($fullType, $event->getFullType());
            self::assertSame($field, $event->getFieldName());
            self::assertSame($this->get(TcaSchemaFactory::class)->get($fullType)->getField($field), $event->getFieldSchema());
            self::assertSame($value, $event->getValue());
            self::assertSame($request, $event->getRequest());
            self::assertSame($result, $event->getRenderedText());
            self::assertTrue($event->isPropagationStopped());
        }
    }

    public static function renderTextEventDataProvider(): \Generator
    {
        yield 'input' => [self::createRecord(['header' => '<b>Title</b>']), 'header', 'tt_content.text', '<b>Title</b>', '<ve-editable>Title</ve-editable>'];
        yield 'plain text' => [self::createRecord(['rowDescription' => "Line 1\nLine 2"]), 'rowDescription', 'tt_content.text', "Line 1\nLine 2", '<ve-editable>Lines</ve-editable>'];
        yield 'rich text' => [self::createRecord(['bodytext' => '<p>Text</p>']), 'bodytext', 'tt_content.text', '<p>Text</p>', '<ve-editable><p>Text</p></ve-editable>'];
        yield 'backend rich text' => [self::createRecord(['bodytext' => '<p>Text</p>']), 'bodytext', 'tt_content.text', '<p>Text</p>', '<ve-editable>Text</ve-editable>', SystemEnvironmentBuilder::REQUESTTYPE_BE];
        yield 'without request' => [self::createRecord(['header' => 'Title']), 'header', 'tt_content.text', 'Title', '<ve-editable>Title</ve-editable>', null];
        yield 'empty result' => [self::createRecord(['header' => 'Title']), 'header', 'tt_content.text', 'Title', ''];
        yield 'page information' => [self::createPageInformation(['title' => 'Page']), 'title', 'pages.1', 'Page', '<ve-editable>Page</ve-editable>'];
        yield 'extbase model' => [self::createExtbaseModel('Blog'), 'title', 'tx_blogexample_domain_model_blog', 'Blog', '<ve-editable>Blog</ve-editable>'];
    }

    #[Test]
    #[DataProvider('renderingDataProvider')]
    public function eventWithoutResultPreservesDefaultRendering(mixed $record, string $templateSource, ?string $expected): void
    {
        $events = [];
        $this->addRenderTextListener('observe', static function (BeforeRenderTextEvent $event) use (&$events): void {
            $events[] = $event;
        });
        $this->render($record, $templateSource, $expected);
        self::assertCount($expected === null ? 0 : 2, $events);
    }

    #[Test]
    #[DataProvider('backendRichTextRenderingDataProvider')]
    public function richTextIsRenderedInBackendContext(string $value, string $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'typo3-localhost',
            $this->buildSiteConfiguration(1, 'https://typo3.localhost/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')]
        );

        $request = new ServerRequest('https://typo3-2.localhost/', 'GET', 'php://input', [], ['HTTP_HOST' => 'typo3-2.localhost']);
        $request = $request
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request))
            ->withAttribute('language', new NullSite()->getDefaultLanguage());
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $context = $this->get(RenderingContextFactory::class)->create([], $request);
        $context->getTemplatePaths()->setTemplateSource("{record -> f:render.text(field: 'bodytext')}");

        $view = new TemplateView($context);
        $view->assign('record', self::createRecord(['bodytext' => $value]));

        $result = $view->render();
        self::assertInstanceOf(UnsafeHTML::class, $result);
        self::assertSame($expected, (string)$result);
    }

    public static function backendRichTextRenderingDataProvider(): \Generator
    {
        yield 'sanitizes unsafe attributes' => [
            'value' => '<img src="/image.png" onerror="alert(1)">',
            'expected' => '<img src="/image.png">',
        ];
        yield 'transforms internal links' => [
            'value' => '<a href="t3://page?uid=1">Root</a>',
            'expected' => '<a href="https://typo3.localhost/">Root</a>',
        ];
    }

    #[Test]
    #[DataProvider('exceptionsDataProvider')]
    public function exceptions(mixed $record, string $templateSource, string $exception, int $exceptionCode, string $exceptionMessage): void
    {
        $this->addRenderTextListener('invalid-input', static fn() => self::fail('Invalid input must not dispatch the event.'));
        $context = $this->get(RenderingContextFactory::class)->create([], $this->createRequest());
        $context->getTemplatePaths()->setTemplateSource($templateSource);

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $this->expectException($exception);
        $this->expectExceptionCode($exceptionCode);
        $this->expectExceptionMessageIsOrContains($exceptionMessage);

        $view->render();
    }

    public static function exceptionsDataProvider(): \Generator
    {
        yield 'throws for unsupported field type' => [
            'record' => self::createRecord(['CType' => 'text']),
            'templateSource' => '<f:render.text record="{record}" field="CType" />',
            'exception' => InvalidArgumentValueException::class,
            'exceptionCode' => 1770618219,
            'exceptionMessage' => 'The field "tt_content.CType" is not supported.',
        ];
        yield 'throwsForNonStringField' => [
            'record' => self::createRecord([
                'bodytext' => ['not-a-string'],
            ]),
            'templateSource' => '<f:render.text record="{record}" field="bodytext" />',
            'exception' => InvalidArgumentValueException::class,
            'exceptionCode' => 1770321858,
            'exceptionMessage' => 'The value of the field "tt_content.bodytext" must be a string. Given: array',
        ];
        yield 'throwsForInvalidRecordObject' => [
            'record' => new \stdClass(),
            'templateSource' => '<f:render.text record="{record}" field="bodytext" />',
            'exception' => InvalidArgumentValueException::class,
            'exceptionCode' => 1256475113,
            'exceptionMessage' => 'The argument "record" was registered with type',
        ];
        yield 'throwsForInvalidRecordObjectInline' => [
            'record' => new \stdClass(),
            'templateSource' => '{record -> f:render.text(field: "bodytext")}',
            'exception' => InvalidArgumentValueException::class,
            'exceptionCode' => 1770539910,
            'exceptionMessage' => 'The record argument must be an instance of',
        ];
        yield 'throws for record property not found' => [
            'record' => self::createRecord([]),
            'templateSource' => '{record -> f:render.text(field: "notAvailable")}',
            'exception' => InvalidArgumentValueException::class,
            'exceptionCode' => 1775553111,
            'exceptionMessage' => 'Record property "notAvailable" is not available.',
        ];
        $ttContent = new TtContent();
        $ttContent->setHeader('<b>My Page</b>');
        yield 'extbaseModel without type information' => [
            'record' => $ttContent,
            'templateSource' => '<f:render.text record="{record}" field="header" />',
            'exception' => InvalidArgumentValueException::class,
            'exceptionCode' => 1771507212,
            'exceptionMessage' => 'The record type field "CType" does not exist in the given model TYPO3Tests\BlogExample\Domain\Model\TtContent',
        ];
        yield 'extbaseModel without the given field' => [
            'record' => self::createExtbaseModel('<b>My Page</b>'),
            'templateSource' => '<f:render.text record="{record}" field="header" />',
            'exception' => InvalidArgumentValueException::class,
            'exceptionCode' => 1775553111,
            'exceptionMessage' => 'Could not find the field "header" in the given model TYPO3Tests\BlogExample\Domain\Model\Blog.',
        ];
    }

    private function addRenderTextListener(string $identifier, \Closure $listener): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set($identifier, $listener);
        $this->get(ListenerProvider::class)->addListener(BeforeRenderTextEvent::class, $identifier);
    }

    private function createRequest(): ServerRequest
    {
        $typoScriptSetup = [
            'lib.' => [
                'parseFunc_RTE.' => [
                    'htmlSanitize' => '1',
                ],
            ],
        ];

        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupTree(new RootNode());
        $frontendTypoScript->setSetupArray($typoScriptSetup);
        $frontendTypoScript->setConfigArray([]);

        $contentObject = $this->get(ContentObjectRenderer::class);

        return new ServerRequest()
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('currentContentObject', $contentObject);
    }

    private static function createRecord(array $properties): RawRecord
    {
        return new RawRecord(
            uid: 1,
            pid: 1,
            properties: $properties,
            computedProperties: new ComputedProperties(),
            fullType: 'tt_content.text'
        );
    }

    private static function createPageInformation(array $fields): PageInformation
    {
        $pageInformation = new PageInformation();
        $pageInformation->setId(1);
        $pageInformation->setPageRecord([
            'uid' => 1,
            'pid' => 0,
            'doktype' => 1,
            'sys_language_uid' => 0,
            'l10n_parent' => 0,
            't3ver_wsid' => 0,
            't3ver_oid' => 0,
            't3ver_state' => 0,
            't3ver_stage' => 0,
            'crdate' => 1770620184,
            'tstamp' => 1770620184,
            'starttime' => 0,
            'endtime' => 0,
            'deleted' => false,
            'hidden' => false,
            'editlock' => false,
            'rowDescription' => '',
            'sorting' => 0,
            'fe_group' => '',
            ...$fields,
        ]);
        return $pageInformation;
    }

    private static function createExtbaseModel(string $title): DomainObjectInterface
    {
        $blog = new Blog();
        $blog->setTitle($title);
        return $blog;
    }
}
