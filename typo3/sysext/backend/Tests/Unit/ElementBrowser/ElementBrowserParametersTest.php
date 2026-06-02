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

namespace TYPO3\CMS\Backend\Tests\Unit\ElementBrowser;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\ElementBrowser\ElementBrowserParameters;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ElementBrowserParametersTest extends UnitTestCase
{
    #[Test]
    public function constructorSetsAllProperties(): void
    {
        $params = new ElementBrowserParameters(
            fieldReference: 'data[tt_content][123][image]',
            rteParameters: 'rteParam',
            rteConfiguration: 'rteConfig',
            allowedTypes: 'gif,jpg,png',
            disallowedFileExtensions: 'exe,bat',
            irreObjectId: 'data-4-pages-4-nav_icon'
        );

        self::assertSame('data[tt_content][123][image]', $params->fieldReference);
        self::assertSame('rteParam', $params->rteParameters);
        self::assertSame('rteConfig', $params->rteConfiguration);
        self::assertSame('gif,jpg,png', $params->allowedTypes);
        self::assertSame('exe,bat', $params->disallowedFileExtensions);
        self::assertSame('data-4-pages-4-nav_icon', $params->irreObjectId);
    }

    #[Test]
    public function constructorUsesDefaultValues(): void
    {
        $params = new ElementBrowserParameters();

        self::assertSame('', $params->fieldReference);
        self::assertSame('', $params->rteParameters);
        self::assertSame('', $params->rteConfiguration);
        self::assertSame('', $params->allowedTypes);
        self::assertSame('', $params->disallowedFileExtensions);
        self::assertSame('', $params->irreObjectId);
    }

    #[Test]
    public function fromRequestReadsSeparateParameters(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'fieldReference' => 'data[tt_content][123][image]',
            'allowedTypes' => 'gif,jpg',
            'disallowedFileExtensions' => 'exe,bat',
            'irreObjectId' => 'data-object-id',
        ]);
        $request->method('getParsedBody')->willReturn(null);

        $params = ElementBrowserParameters::fromRequest($request);

        self::assertSame('data[tt_content][123][image]', $params->fieldReference);
        self::assertSame('gif,jpg', $params->allowedTypes);
        self::assertSame('exe,bat', $params->disallowedFileExtensions);
        self::assertSame('data-object-id', $params->irreObjectId);
    }

    public static function getFileExtensionsDataProvider(): \Generator
    {
        yield 'simple comma-separated extensions' => [
            'gif,jpg,png',
            '',
            ['allowed' => ['gif', 'jpg', 'png'], 'disallowed' => []],
        ];

        yield 'allowed and disallowed separately' => [
            'jpg,png',
            'exe,bat',
            ['allowed' => ['jpg', 'png'], 'disallowed' => ['exe', 'bat']],
        ];

        yield 'only disallowed' => [
            '',
            'exe,bat',
            ['allowed' => [], 'disallowed' => ['exe', 'bat']],
        ];

        yield 'empty strings' => [
            '',
            '',
            ['allowed' => [], 'disallowed' => []],
        ];

        yield 'wildcard asterisk' => [
            '*',
            '',
            ['allowed' => [], 'disallowed' => []],
        ];

        yield 'sys_file reference should be ignored' => [
            'sys_file',
            '',
            ['allowed' => [], 'disallowed' => []],
        ];
    }

    #[DataProvider('getFileExtensionsDataProvider')]
    #[Test]
    public function getFileExtensionsParsesCorrectly(string $allowedTypes, string $disallowedFileExtensions, array $expected): void
    {
        $params = new ElementBrowserParameters(
            allowedTypes: $allowedTypes,
            disallowedFileExtensions: $disallowedFileExtensions
        );
        self::assertSame($expected, $params->getFileExtensions());
    }

    public static function getAllowedTablesDataProvider(): \Generator
    {
        yield 'single table' => [
            'tt_content',
            ['tt_content'],
        ];

        yield 'multiple tables' => [
            'tt_content,pages,sys_category',
            ['tt_content', 'pages', 'sys_category'],
        ];

        yield 'empty string' => [
            '',
            [],
        ];

        yield 'wildcard asterisk' => [
            '*',
            [],
        ];
    }

    #[DataProvider('getAllowedTablesDataProvider')]
    #[Test]
    public function getAllowedTablesParsesCorrectly(string $allowedTypes, array $expected): void
    {
        $params = new ElementBrowserParameters(allowedTypes: $allowedTypes);
        self::assertSame($expected, $params->getAllowedTables());
    }

    public static function getFieldReferencePartsDataProvider(): \Generator
    {
        yield 'standard field reference' => [
            'data[tt_content][123][image]',
            ['tableName' => 'tt_content', 'fieldName' => 'image'],
        ];

        yield 'pages table' => [
            'data[pages][79][storage_pid]',
            ['tableName' => 'pages', 'fieldName' => 'storage_pid'],
        ];

        yield 'empty string' => [
            '',
            ['tableName' => '', 'fieldName' => ''],
        ];

        yield 'incomplete format' => [
            'data[tt_content]',
            ['tableName' => '', 'fieldName' => ''],
        ];
    }

    #[DataProvider('getFieldReferencePartsDataProvider')]
    #[Test]
    public function getFieldReferencePartsParsesCorrectly(string $fieldReference, array $expected): void
    {
        $params = new ElementBrowserParameters(fieldReference: $fieldReference);
        self::assertSame($expected, $params->getFieldReferenceParts());
    }

    #[Test]
    public function toDataAttributesReturnsCorrectArray(): void
    {
        $params = new ElementBrowserParameters(
            fieldReference: 'data[tt_content][123][image]',
            rteParameters: 'rteParam',
            rteConfiguration: 'rteConfig',
            irreObjectId: 'data-4-pages-4-nav_icon'
        );

        $expected = [
            'data-form-field-name' => 'data[data[tt_content][123][image]][rteParam][rteConfig]',
            'data-field-reference' => 'data[tt_content][123][image]',
            'data-rte-parameters' => 'rteParam',
            'data-rte-configuration' => 'rteConfig',
            'data-irre-object-id' => 'data-4-pages-4-nav_icon',
            'data-use-events' => null,
        ];

        self::assertSame($expected, $params->toDataAttributes());
    }

    #[Test]
    public function toDataAttributesReturnsNullForEmptyValues(): void
    {
        $params = new ElementBrowserParameters(
            fieldReference: 'data[tt_content][123][image]'
        );

        $attributes = $params->toDataAttributes();

        self::assertSame('data[tt_content][123][image]', $attributes['data-field-reference']);
        self::assertNull($attributes['data-rte-parameters']);
        self::assertNull($attributes['data-rte-configuration']);
        self::assertNull($attributes['data-irre-object-id']);
    }

    #[Test]
    public function toArrayReturnsAllProperties(): void
    {
        $params = new ElementBrowserParameters(
            fieldReference: 'data[tt_content][123][image]',
            rteParameters: 'rteParam',
            rteConfiguration: 'rteConfig',
            allowedTypes: 'gif,jpg',
            disallowedFileExtensions: 'exe,bat',
            irreObjectId: 'data-4-pages',
            useEvents: true,
        );

        $expected = [
            'fieldReference' => 'data[tt_content][123][image]',
            'rteParameters' => 'rteParam',
            'rteConfiguration' => 'rteConfig',
            'allowedTypes' => 'gif,jpg',
            'disallowedFileExtensions' => 'exe,bat',
            'irreObjectId' => 'data-4-pages',
            'useEvents' => true,
        ];

        self::assertSame($expected, $params->toArray());
    }

    #[Test]
    public function toQueryParametersReturnsOnlyNonEmptyValues(): void
    {
        $params = new ElementBrowserParameters(
            fieldReference: 'data[tt_content][123][image]',
            allowedTypes: 'gif,jpg',
            disallowedFileExtensions: 'exe'
        );

        $expected = [
            'fieldReference' => 'data[tt_content][123][image]',
            'allowedTypes' => 'gif,jpg',
            'disallowedFileExtensions' => 'exe',
        ];

        self::assertSame($expected, $params->toQueryParameters());
    }

    #[Test]
    public function jsonSerializeReturnsArrayRepresentation(): void
    {
        $params = new ElementBrowserParameters(
            fieldReference: 'data[tt_content][123][image]',
            allowedTypes: 'gif,jpg'
        );

        $expected = [
            'fieldReference' => 'data[tt_content][123][image]',
            'rteParameters' => '',
            'rteConfiguration' => '',
            'allowedTypes' => 'gif,jpg',
            'disallowedFileExtensions' => '',
            'irreObjectId' => '',
            'useEvents' => false,
        ];

        self::assertSame($expected, $params->jsonSerialize());
        self::assertSame(json_encode($expected), json_encode($params));
    }
}
