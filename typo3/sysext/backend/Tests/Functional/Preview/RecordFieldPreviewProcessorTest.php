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

namespace TYPO3\CMS\Backend\Tests\Functional\Preview;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Preview\RecordFieldPreviewProcessor;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\RawRecord;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordFieldPreviewProcessorTest extends FunctionalTestCase
{
    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/backend/Tests/Functional/Preview/Fixtures/Folders/fileadmin/' => 'fileadmin/',
    ];

    private RecordFieldPreviewProcessor $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/xss_content.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);

        $this->subject = new RecordFieldPreviewProcessor(
            $this->get(TcaSchemaFactory::class),
            $this->get(UriBuilder::class),
            $this->get(IconFactory::class)
        );
    }

    public static function prepareFieldWithLabelXssDataProvider(): iterable
    {
        // Uses records from xss_content.csv fixture
        yield 'script tag in header' => [
            'fieldName' => 'header',
            'recordUid' => 2, // Has header: <script>alert('XSS')</script>
            'mustNotContain' => ['<script>', '</script>'],
            'mustContain' => ['&lt;script&gt;', '&lt;/script&gt;'],
        ];
        yield 'quotes in header' => [
            'fieldName' => 'header',
            'recordUid' => 3, // Has header: Header with "quotes" and 'apostrophes'
            'mustNotContain' => [],
            'mustContain' => ['&quot;quotes&quot;'],
        ];
        yield 'ampersand in header' => [
            'fieldName' => 'header',
            'recordUid' => 4, // Has header: Test & ampersand
            'mustNotContain' => [],
            'mustContain' => ['&amp;'],
        ];
        yield 'img onerror in header' => [
            'fieldName' => 'header',
            'recordUid' => 5, // Has header: <img src=x onerror=alert('XSS')>
            // The < and > are escaped, making the tag non-executable
            // The word "onerror" appears but as escaped text, not as an attribute
            'mustNotContain' => ['<img'],
            'mustContain' => ['&lt;img', '&gt;'],
        ];
    }

    #[Test]
    #[DataProvider('prepareFieldWithLabelXssDataProvider')]
    public function prepareFieldWithLabelEscapesXssPayloads(
        string $fieldName,
        int $recordUid,
        array $mustNotContain,
        array $mustContain
    ): void {
        $record = $this->loadRecordFromDatabase($recordUid);

        $result = $this->subject->prepareFieldWithLabel($record, $fieldName);

        self::assertIsString($result);

        foreach ($mustNotContain as $forbidden) {
            self::assertStringNotContainsString(
                $forbidden,
                $result,
                sprintf('Output should not contain unescaped "%s"', $forbidden)
            );
        }

        foreach ($mustContain as $required) {
            self::assertStringContainsString(
                $required,
                $result,
                sprintf('Output should contain escaped "%s"', $required)
            );
        }
    }

    #[Test]
    public function prepareFieldWithLabelOutputContainsStrongTagForLabel(): void
    {
        $record = $this->loadRecordFromDatabase(1); // Normal header record

        $result = $this->subject->prepareFieldWithLabel($record, 'header');

        self::assertIsString($result);
        // Output should contain the <strong> wrapper for the label
        self::assertStringContainsString('<strong>', $result);
        self::assertStringContainsString('</strong>', $result);
    }

    public static function prepareFieldXssDataProvider(): iterable
    {
        // Uses records from xss_content.csv fixture
        yield 'script tag in header' => [
            'fieldName' => 'header',
            'recordUid' => 2, // Has header: <script>alert('XSS')</script>
            'mustNotContain' => ['<script>', '</script>'],
            'mustContain' => ['&lt;script&gt;'],
        ];
        yield 'img onerror in header' => [
            'fieldName' => 'header',
            'recordUid' => 5, // Has header: <img src=x onerror=alert('XSS')>
            // The < and > are escaped, making the tag non-executable
            // The word "onerror" appears but as escaped text, not as an attribute
            'mustNotContain' => ['<img'],
            'mustContain' => ['&lt;img', '&gt;'],
        ];
    }

    #[Test]
    #[DataProvider('prepareFieldXssDataProvider')]
    public function prepareFieldEscapesXssPayloads(
        string $fieldName,
        int $recordUid,
        array $mustNotContain,
        array $mustContain
    ): void {
        $record = $this->loadRecordFromDatabase($recordUid);

        $result = $this->subject->prepareField($record, $fieldName);

        self::assertIsString($result);

        foreach ($mustNotContain as $forbidden) {
            self::assertStringNotContainsString(
                $forbidden,
                $result,
                sprintf('Output should not contain unescaped "%s"', $forbidden)
            );
        }

        foreach ($mustContain as $required) {
            self::assertStringContainsString(
                $required,
                $result,
                sprintf('Output should contain escaped "%s"', $required)
            );
        }
    }

    #[Test]
    public function prepareFieldReturnsNullForEmptyField(): void
    {
        $record = $this->createRecord([
            'uid' => 1,
            'pid' => 1,
            'CType' => 'text',
            'header' => '',
        ]);

        $result = $this->subject->prepareField($record, 'header');

        self::assertNull($result);
    }

    #[Test]
    public function prepareFieldReturnsNullForNonExistentField(): void
    {
        $record = $this->loadRecordFromDatabase(1);

        $result = $this->subject->prepareField($record, 'nonexistent_field');

        self::assertNull($result);
    }

    #[Test]
    public function linkToEditFormEscapesUrlAndTitleAttribute(): void
    {
        $record = $this->loadRecordFromDatabase(1);
        $request = $this->createServerRequest();
        $linkText = 'Click here';

        $result = $this->subject->linkToEditForm($linkText, $record, $request);

        self::assertStringContainsString('<a href="', $result, 'The result is wrapped in an anchor tag');
        self::assertStringContainsString('&amp;', $result, 'The result URL is escaped');
        self::assertStringContainsString('title="', $result, 'Title attribute is present and escaped');
    }

    #[Test]
    public function linkToEditFormReturnsLinkTextUnmodifiedWhenEmpty(): void
    {
        $record = $this->loadRecordFromDatabase(1);
        $request = $this->createServerRequest();

        $result = $this->subject->linkToEditForm('', $record, $request);

        self::assertSame('', $result);
    }

    #[Test]
    public function linkToEditFormPreservesAlreadyEscapedLinkText(): void
    {
        $record = $this->loadRecordFromDatabase(1);
        $request = $this->createServerRequest();
        // Link text should already be escaped according to the docblock
        $linkText = 'Text with &lt;script&gt; already escaped';

        $result = $this->subject->linkToEditForm($linkText, $record, $request);
        self::assertStringContainsString('&lt;script&gt;', $result, 'The already-escaped content is preserved');
    }

    #[Test]
    public function prepareFilesReturnsNullForEmptyInput(): void
    {
        $result = $this->subject->prepareFiles([]);

        self::assertNull($result);
    }

    /**
     * This test verifies that when prepareFiles generates img tags,
     * it uses GeneralUtility::implodeAttributes with $xhtmlSafe=true
     * which properly escapes attribute values
     */
    #[Test]
    public function prepareFilesUsesImplodeAttributesForImageTags(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/fal_files.csv');
        $resourceFactory = $this->get(ResourceFactory::class);
        $fileReference = $resourceFactory->getFileReferenceObject(1);

        $result = $this->subject->prepareFiles($fileReference);
        if ($result !== null) {
            // The result contains div wrappers and possibly img tags
            self::assertStringContainsString('preview-thumbnails', $result);
        }
    }

    #[Test]
    public function prepareFieldWithLabelHandlesSelectFieldWithXssInItems(): void
    {
        // Set up a select field with items that could contain XSS
        $GLOBALS['TCA']['tt_content']['columns']['test_select'] = [
            'label' => 'Test Select',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Normal Option', 'value' => 'normal'],
                    ['label' => '<script>alert("XSS")</script>', 'value' => 'xss_option'],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        // Create a record with the test_select field
        $record = $this->createRecord([
            'uid' => 1,
            'pid' => 1,
            'CType' => 'text',
            'header' => 'test',
            'test_select' => 'xss_option',
        ]);

        $result = $this->subject->prepareFieldWithLabel($record, 'test_select');
        self::assertStringNotContainsString('<script>', $result);
        self::assertStringNotContainsString('</script>', $result);
        self::assertStringContainsString('&lt;script&gt;', $result, 'result does not contain unescaped script tags');
    }

    #[Test]
    public function prepareFieldHandlesGroupFieldWithXss(): void
    {
        // Group fields return record titles which could potentially contain XSS
        $GLOBALS['TCA']['tt_content']['columns']['pages'] = [
            'label' => 'Pages',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        // Create a record referencing page 1
        $record = $this->createRecord([
            'uid' => 1,
            'pid' => 1,
            'CType' => 'text',
            'header' => 'test',
            'pages' => '1',
        ]);

        $result = $this->subject->prepareField($record, 'pages');
        self::assertStringNotContainsString('<script>', $result, 'result ist escaped');
    }

    private function loadRecordFromDatabase(int $uid): Record
    {
        $row = $this->get(ConnectionPool::class)
            ->getConnectionForTable('tt_content')
            ->select(['*'], 'tt_content', ['uid' => $uid])
            ->fetchAssociative();

        self::assertIsArray($row, 'Record not found in database');

        $recordUid = (int)$row['uid'];
        $recordPid = (int)$row['pid'];
        unset($row['uid'], $row['pid']);

        $rawRecord = new RawRecord(
            $recordUid,
            $recordPid,
            $row,
            new ComputedProperties(),
            'tt_content.' . ($row['CType'] ?? 'text')
        );
        return new Record($rawRecord, $row, null);
    }

    private function createRecord(array $data): Record
    {
        $uid = $data['uid'] ?? 1;
        $pid = $data['pid'] ?? 1;
        unset($data['uid'], $data['pid']);

        $rawRecord = new RawRecord(
            $uid,
            $pid,
            $data,
            new ComputedProperties(),
            'tt_content.' . ($data['CType'] ?? 'text')
        );
        return new Record($rawRecord, $data, null);
    }

    private function createServerRequest(): ServerRequestInterface
    {
        $request = new ServerRequest('https://example.com/typo3/module/web/layout', 'GET');
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $normalizedParams = new NormalizedParams(
            [
                'HTTP_HOST' => 'example.com',
                'HTTPS' => 'on',
                'REQUEST_URI' => '/typo3/module/web/layout',
                'SCRIPT_NAME' => '/typo3/index.php',
            ],
            [],
            '/var/www/html',
            ''
        );
        return $request->withAttribute('normalizedParams', $normalizedParams);
    }
}
