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

namespace TYPO3\CMS\Form\Tests\Unit\Storage;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Storage\FormDefinitionMetadataExtractor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FormDefinitionMetadataExtractorTest extends UnitTestCase
{
    public static function extractReturnsMetadataDataProvider(): iterable
    {
        yield 'plain values' => [
            "identifier: contact\ntype: Form\nlabel: Contact\nprototypeName: standard\n",
            ['identifier' => 'contact', 'type' => 'Form', 'label' => 'Contact', 'prototypeName' => 'standard'],
        ];
        yield 'quoted values' => [
            "identifier: 'contact'\ntype: \"Form\"\nlabel: 'Contact'\n",
            ['identifier' => 'contact', 'type' => 'Form', 'label' => 'Contact'],
        ];
        yield 'escaped quotes' => [
            "identifier: 'it''s'\ntype: Form\nlabel: \"Say \\\"hello\\\"\"\n",
            ['identifier' => 'it\'s', 'type' => 'Form', 'label' => 'Say "hello"'],
        ];
        yield 'trailing comments' => [
            "identifier: contact # main form\ntype: Form # always Form\nlabel: Contact\n",
            ['identifier' => 'contact', 'type' => 'Form', 'label' => 'Contact'],
        ];
        yield 'folded block scalar' => [
            "identifier: contact\ntype: Form\nlabel: >-\n  Contact\n  form\nprototypeName: standard\n",
            ['identifier' => 'contact', 'type' => 'Form', 'label' => 'Contact form', 'prototypeName' => 'standard'],
        ];
        yield 'literal block scalar with empty line' => [
            "identifier: contact\ntype: Form\nlabel: |-\n  Contact\n\n  form\nprototypeName: standard\n",
            ['identifier' => 'contact', 'type' => 'Form', 'label' => "Contact\n\nform", 'prototypeName' => 'standard'],
        ];
        yield 'multi-line quoted value' => [
            "identifier: contact\ntype: Form\nlabel: 'Contact\n  form'\n",
            ['identifier' => 'contact', 'type' => 'Form', 'label' => 'Contact form'],
        ];
        yield 'multi-line plain value' => [
            "identifier: contact\ntype: Form\nlabel: Contact\n  form\n",
            ['identifier' => 'contact', 'type' => 'Form', 'label' => 'Contact form'],
        ];
        yield 'windows line endings' => [
            "identifier: contact\r\ntype: Form\r\nlabel: >-\r\n  Contact\r\n  form\r\n",
            ['identifier' => 'contact', 'type' => 'Form', 'label' => 'Contact form'],
        ];
        yield 'comment lines and document start' => [
            "---\n# Contact form\nidentifier: contact\n# type follows\ntype: Form\nlabel: Contact\n",
            ['identifier' => 'contact', 'type' => 'Form', 'label' => 'Contact'],
        ];
        yield 'numeric identifier is returned as string' => [
            "identifier: 123\ntype: Form\n",
            ['identifier' => '123', 'type' => 'Form'],
        ];
        yield 'nested properties are ignored' => [
            "identifier: contact\ntype: Form\nrenderables:\n  -\n    identifier: page-1\n    type: Page\n    label: Step\n",
            ['identifier' => 'contact', 'type' => 'Form'],
        ];
        yield 'empty, null and non-scalar values are ignored' => [
            "identifier: contact\ntype: Form\nlabel:\n  de: Kontakt\nprototypeName: ~\n",
            ['identifier' => 'contact', 'type' => 'Form'],
        ];
        yield 'invalid value results in empty string' => [
            "identifier: contact\ntype: Form\nlabel: 'Contact\n",
            ['identifier' => 'contact', 'type' => 'Form', 'label' => ''],
        ];
        yield 'no form definition' => [
            "Lorem ipsum\ndolor sit amet\n",
            [],
        ];
    }

    #[DataProvider('extractReturnsMetadataDataProvider')]
    #[Test]
    public function extractReturnsMetadata(string $rawFormDefinition, array $expected): void
    {
        self::assertSame($expected, new FormDefinitionMetadataExtractor()->extract($rawFormDefinition));
    }
}
