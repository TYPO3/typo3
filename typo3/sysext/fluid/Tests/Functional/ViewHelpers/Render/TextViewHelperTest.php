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
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Page\PageInformation;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\Parser\UnsafeHTML;
use TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentValueException;
use TYPO3Fluid\Fluid\View\TemplateView;
use TYPO3Tests\BlogExample\Domain\Model\Blog;
use TYPO3Tests\BlogExample\Domain\Model\TtContent;
use TYPO3Tests\BlogExample\Domain\Model\TtContentWithCType;

final class TextViewHelperTest extends FunctionalTestCase
{
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
    }

    #[Test]
    #[DataProvider('exceptionsDataProvider')]
    public function exceptions(mixed $record, string $templateSource, string $exception, int $exceptionCode, string $exceptionMessage): void
    {
        $context = $this->get(RenderingContextFactory::class)->create([], $this->createRequest());
        $context->getTemplatePaths()->setTemplateSource($templateSource);

        $view = new TemplateView($context);
        $view->assign('record', $record);

        $this->expectException($exception);
        $this->expectExceptionCode($exceptionCode);
        $this->expectExceptionMessage($exceptionMessage);

        $view->render();
    }

    public static function exceptionsDataProvider(): \Generator
    {
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

        return (new ServerRequest())
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
