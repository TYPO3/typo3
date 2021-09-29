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

namespace TYPO3\CMS\Core\Tests\Unit\Serializer;

use TYPO3\CMS\Core\Serializer\Typo3XmlParserOptions;
use TYPO3\CMS\Core\Serializer\Typo3XmlSerializer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class Typo3XmlSerializerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function encodeReturnsRootNodeIfArrayIsEmpty(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode([]);
        self::assertEquals('<phparray>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeCanPreventWrappingByRootNode(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            ['node' => 'value'],
            new Typo3XmlParserOptions([Typo3XmlParserOptions::ROOT_NODE_NAME => ''])
        );
        self::assertEquals('<node>value</node>
', $xml);
    }

    /**
     * @test
     */
    public function encodeSupportsInlineXml(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            ['node' => 'value'],
            new Typo3XmlParserOptions([Typo3XmlParserOptions::FORMAT => Typo3XmlParserOptions::FORMAT_INLINE])
        );
        self::assertEquals('<phparray><node>value</node></phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeSupportsPrettyPrintWithTabIndentation(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            ['node' => 'value'],
            new Typo3XmlParserOptions([Typo3XmlParserOptions::FORMAT => Typo3XmlParserOptions::FORMAT_PRETTY_WITH_TAB])
        );
        self::assertEquals('<phparray>
	<node>value</node>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeSupportsPrettyPrintWith4SpacesIndentation(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            ['node' => 'value'],
            new Typo3XmlParserOptions([Typo3XmlParserOptions::FORMAT => 4])
        );
        self::assertEquals('<phparray>
    <node>value</node>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeEnsuresAlphaNumericCharactersAndMinusAndUnderscoreInNodeName(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(['node-åæøöäüßąćęłńóśźżàâçèéêëîïôœùûÿ!§$%&/()=?_' => 'value']);
        self::assertEquals('<phparray>
	<node-_>value</node-_>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeCanPrependNamespaceToNodeName(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            ['node' => 'value'],
            new Typo3XmlParserOptions([Typo3XmlParserOptions::NAMESPACE_PREFIX => 'namespace:'])
        );
        self::assertEquals('<phparray>
	<namespace:node>value</namespace:node>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeCanPrependNToNodeNameIfNodeNameIsNumber(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            ['value'],
            null,
            ['useNindex' => true]
        );
        self::assertEquals('<phparray>
	<n0>value</n0>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeCanReplaceNodeNameAndAddAsIndexIfNodeNameIsNumber(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            [
                'value-1',
                'value-2',
            ],
            null,
            [
                'useIndexTagForNum' => 'node-of-normal-array',
            ]
        );
        self::assertEquals('<phparray>
	<node-of-normal-array index="0">value-1</node-of-normal-array>
	<node-of-normal-array index="1">value-2</node-of-normal-array>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeCanReplaceNodeNameAndAddAsIndexIfNodeNameIsString(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            [
                'node-1' => 'value-1',
                'node-2' => 'value-2',
            ],
            null,
            [
                'useIndexTagForAssoc' => 'node-of-associative-array',
            ]
        );
        self::assertEquals('<phparray>
	<node-of-associative-array index="node-1">value-1</node-of-associative-array>
	<node-of-associative-array index="node-2">value-2</node-of-associative-array>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeCanReplaceNodeNameAndAddAsIndexIfParentMatchesName(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            [
                'grandParent' => [
                    'parent' => [
                        'child' => [
                            'grandChild' => 'value',
                        ],
                    ],
                ],
            ],
            null,
            [
                'parentTagMap' => [
                    'parent' => 'child-renamed',
                ],
            ]
        );
        self::assertEquals('<phparray>
	<grandParent type="array">
		<parent type="array">
			<child-renamed index="child" type="array">
				<grandChild>value</grandChild>
			</child-renamed>
		</parent>
	</grandParent>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeCanReplaceNodeNameAndAddAsIndexIfParentAndNodeMatchNames(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            [
                'grandParent' => [
                    'parent' => [
                        'child-1' => [
                            'grandChild' => 'value',
                        ],
                        'child-2' => 'value-2',
                    ],
                ],
            ],
            null,
            [
                'parentTagMap' => [
                    'parent:child-1' => 'child-1-renamed',
                ],
            ]
        );
        self::assertEquals('<phparray>
	<grandParent type="array">
		<parent type="array">
			<child-1-renamed index="child-1" type="array">
				<grandChild>value</grandChild>
			</child-1-renamed>
			<child-2>value-2</child-2>
		</parent>
	</grandParent>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeCanReplaceNodeNameAndAddAsIndexIfParentMatchesNameAndNodeNameIsNumber(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            [
                'grandParent' => [
                    'parent' => [
                        [
                            'grandChild' => 'value',
                        ],
                    ],
                ],
            ],
            null,
            [
                'parentTagMap' => [
                    'parent:_IS_NUM' => 'child-renamed',
                ],
            ]
        );
        self::assertEquals('<phparray>
	<grandParent type="array">
		<parent type="array">
			<child-renamed index="0" type="array">
				<grandChild>value</grandChild>
			</child-renamed>
		</parent>
	</grandParent>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeCanReplaceNodeNameAndAddAsIndexIfGrandParentAndParentAndNodeMatchNames(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            [
                'grandGrandParent' => [
                    'grandParent' => [
                        'parent' => [
                            'child' => [
                                'grandChild' => 'value',
                            ],
                        ],
                    ],
                ],
            ],
            null,
            [
                'grandParentTagMap' => [
                    'grandParent/parent' => 'child-renamed',
                ],
            ]
        );
        self::assertEquals('<phparray>
	<grandGrandParent type="array">
		<grandParent type="array">
			<parent type="array">
				<child-renamed index="child" type="array">
					<grandChild>value</grandChild>
				</child-renamed>
			</parent>
		</grandParent>
	</grandGrandParent>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeCanWrapStringWithCDATAIfStringContainsSpecialCharacters(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            [
                'node-1' => 'value without special character',
                'node-2' => 'value with special character &',
            ],
            null,
            [
                'useCDATA' => true,
            ]
        );
        self::assertEquals('<phparray>
	<node-1>value without special character</node-1>
	<node-2><![CDATA[value with special character &]]></node-2>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeAddsTypeAttributeToNodeIfValueIsNotString(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode([
            'string' => 'value',
            'integer' => 1,
            'double' => 0.2,
            'boolean' => true,
            'object' => null,
            'array' => [],
        ]);
        self::assertEquals('<phparray>
	<string>value</string>
	<integer type="integer">1</integer>
	<double type="double">0.2</double>
	<boolean type="boolean">1</boolean>
	<object type="NULL"></object>
	<array type="array"></array>
</phparray>', $xml);
    }

    public function encodeCanDisableAddingTypeAttributeToNodeExceptIfValueIsArrayDataProvider(): array
    {
        return [
            ['disableTypeAttrib' => true],
            ['disableTypeAttrib' => 1],
        ];
    }

    /**
     * @test
     * @dataProvider encodeCanDisableAddingTypeAttributeToNodeExceptIfValueIsArrayDataProvider
     */
    public function encodeCanDisableAddingTypeAttributeToNodeExceptIfValueIsArray(mixed $disableTypeAttrib): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            [
                'string' => 'value',
                'integer' => 1,
                'double' => 0.2,
                'boolean' => true,
                'object' => null,
                'array' => [],
            ],
            null,
            [
                'disableTypeAttrib' => $disableTypeAttrib,
            ]
        );
        self::assertEquals('<phparray>
	<string>value</string>
	<integer>1</integer>
	<double>0.2</double>
	<boolean>1</boolean>
	<object></object>
	<array type="array"></array>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeCanDisableAddingTypeAttributeToNode(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            [
                'string' => 'value',
                'integer' => 1,
                'double' => 0.2,
                'boolean' => true,
                'object' => null,
                'array' => [],
            ],
            null,
            [
                'disableTypeAttrib' => 2,
            ]
        );
        self::assertEquals('<phparray>
	<string>value</string>
	<integer>1</integer>
	<double>0.2</double>
	<boolean>1</boolean>
	<object></object>
	<array></array>
</phparray>', $xml);
    }

    /**
     * @test
     */
    public function encodeAddsBase64AttributeAndEncodesWithBase64IfValueIsBinaryData(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $content = file_get_contents(__DIR__ . '/Fixtures/file.gif');
        $contentBase64Encoded = chunk_split(base64_encode($content));
        $xml = $xmlEncoder->encode([
            'binary' => $content,
        ]);
        self::assertEquals("<phparray>
	<binary base64=\"1\">
$contentBase64Encoded</binary>
</phparray>", $xml);
    }

    /**
     * @test
     */
    public function encodeCanSetAlternativeOptionsPerNestingLevel(): void
    {
        $xmlEncoder = new Typo3XmlSerializer();
        $xml = $xmlEncoder->encode(
            [
                'grandParent1' => [
                    'parent1' => [
                        'value1',
                    ],
                    'parent2' => [
                        'value2',
                    ],
                ],
                'grandParent2' => [
                    'parent3' => [
                        'child3' => 'value3',
                    ],
                    'parent4' => [
                        'child4' => 'value4',
                    ],
                ],
            ],
            null,
            [
                'useNindex' => false,
                'useIndexTagForNum' => null,
                'useIndexTagForAssoc' => null,
                'alt_options' => [
                    '/grandParent1/parent1' => [
                        'useIndexTagForNum' => 'numbered-index',
                    ],
                    '/grandParent1/parent2' => [
                        'useNindex' => true,
                    ],
                    '/grandParent2' => [
                        'clearStackPath' => true,
                        'alt_options' => [
                            '/parent4' => [
                                'useIndexTagForAssoc' => 'named-index',
                            ],
                        ],
                    ],
                ],
            ]
        );
        self::assertEquals('<phparray>
	<grandParent1 type="array">
		<parent1 type="array">
			<numbered-index index="0">value1</numbered-index>
		</parent1>
		<parent2 type="array">
			<n0>value2</n0>
		</parent2>
	</grandParent1>
	<grandParent2 type="array">
		<parent3 type="array">
			<child3>value3</child3>
		</parent3>
		<parent4 type="array">
			<named-index index="child4">value4</named-index>
		</parent4>
	</grandParent2>
</phparray>', $xml);
    }
}
