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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Serializer\Typo3XmlParser;
use TYPO3\CMS\Core\Serializer\Typo3XmlParserOptions;
use TYPO3\CMS\Core\Serializer\Typo3XmlSerializer;
use TYPO3\CMS\Core\Serializer\Typo3XmlSerializerOptions;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class Typo3XmlParserTest extends UnitTestCase
{
    public static function decodeReturnsStringOrArrayDataProvider(): array
    {
        return [
            'EmptyRootNode' => [
                '<phparray></phparray>',
                '',
            ],
            'RootNodeContainsText' => [
                '<phparray>content</phparray>',
                'content',
            ],
            'RootNodeContainsSubNode' => [
                '<phparray><node>content</node></phparray>',
                ['node' => 'content'],
            ],
        ];
    }

    #[DataProvider('decodeReturnsStringOrArrayDataProvider')]
    #[Test]
    public function decodeReturnsStringOrArray(string $data, mixed $expected): void
    {
        $xmlDecoder = new Typo3XmlParser();
        $result = $xmlDecoder->decode($data);
        self::assertEquals($expected, $result);
    }

    public static function decodeHandlesCommentsDataProvider(): array
    {
        return [
            'IgnoreComments' => [
                [],
                ['node' => 'content'],
            ],
            'IgnoreCommentsToo' => [
                [Typo3XmlSerializerOptions::IGNORED_NODE_TYPES => [\XML_COMMENT_NODE]],
                ['node' => 'content'],
            ],
            'DoNotIgnoreComments' => [
                [Typo3XmlSerializerOptions::IGNORED_NODE_TYPES => []],
                ['node' => 'content', '#comment' => ' Comment '],
            ],
        ];
    }

    #[DataProvider('decodeHandlesCommentsDataProvider')]
    #[Test]
    public function decodeHandlesComments(array $config, array $expected): void
    {
        $xmlDecoder = new Typo3XmlParser();
        $result = $xmlDecoder->decode('<phparray attribute="ignored">
    <!-- Comment -->
    <node>content</node>
</phparray>', new Typo3XmlSerializerOptions($config));
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function decodeIgnoresNodeAttributes(): void
    {
        $xmlDecoder = new Typo3XmlParser();
        $result = $xmlDecoder->decode('<phparray attribute="ignored">
    <node attribute="ignored">content</node>
</phparray>');
        self::assertEquals(['node' => 'content'], $result);
    }

    /**
     * @return string[][]
     */
    public static function decodeHandlesWhitespacesDataProvider(): array
    {
        $headerVariants = [
            'utf-8' => '<?xml version="1.0" encoding="utf-8" standalone="yes"?>',
            'UTF-8' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
            'no-encoding' => '<?xml version="1.0" standalone="yes"?>',
            'iso-8859-1' => '<?xml version="1.0" encoding="iso-8859-1" standalone="yes"?>',
            'ISO-8859-1' => '<?xml version="1.0" encoding="ISO-8859-1" standalone="yes"?>',
        ];
        $data = [];
        foreach ($headerVariants as $identifier => $headerVariant) {
            $data += [
                'inputWithoutWhitespaces-' . $identifier => [
                    $headerVariant . '<T3FlexForms>
                        <data>
                            <field index="settings.persistenceIdentifier">
                                <value index="vDEF">egon</value>
                            </field>
                        </data>
                    </T3FlexForms>',
                ],
                'inputWithPrecedingWhitespaces-' . $identifier => [
                    CR . ' ' . $headerVariant . '<T3FlexForms>
                        <data>
                            <field index="settings.persistenceIdentifier">
                                <value index="vDEF">egon</value>
                            </field>
                        </data>
                    </T3FlexForms>',
                ],
                'inputWithTrailingWhitespaces-' . $identifier => [
                    $headerVariant . '<T3FlexForms>
                        <data>
                            <field index="settings.persistenceIdentifier">
                                <value index="vDEF">egon</value>
                            </field>
                        </data>
                    </T3FlexForms>' . CR . ' ',
                ],
                'inputWithPrecedingAndTrailingWhitespaces-' . $identifier => [
                    CR . ' ' . $headerVariant . '<T3FlexForms>
                        <data>
                            <field index="settings.persistenceIdentifier">
                                <value index="vDEF">egon</value>
                            </field>
                        </data>
                    </T3FlexForms>' . CR . ' ',
                ],
            ];
        }
        return $data;
    }

    /**
     * @param string $input
     */
    #[DataProvider('decodeHandlesWhitespacesDataProvider')]
    #[Test]
    public function decodeHandlesWhitespaces(string $input): void
    {
        $xmlDecoder = new Typo3XmlParser();
        $expected = [
            'data' => [
                'settings.persistenceIdentifier' => [
                    'vDEF' => 'egon',
                ],
            ],
        ];
        self::assertSame($expected, $xmlDecoder->decode($input));
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeHandlesTagNamespacesDataProvider(): array
    {
        return [
            'inputWithNameSpaceOnRootLevel' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3:T3FlexForms xmlns:T3="https://typo3.org/ns/T3">
                    <data>
                        <field>
                            <value index="vDEF1">egon</value>
                            <value index="vDEF2"><![CDATA[egon<CDATA:tag>olsen]]></value>
                        </field>
                    </data>
                </T3:T3FlexForms>',
                [
                    'data' => [
                        'field' => [
                            'vDEF1' => 'egon',
                            'vDEF2' => 'egon<CDATA:tag>olsen',
                        ],
                    ],
                ],
            ],
            'inputWithNameSpaceOnNonRootLevel' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3FlexForms xmlns:T3="https://typo3.org/ns/T3">
                    <data>
                        <T3:field>
                            <value index="vDEF1">egon</value>
                            <value index="vDEF2"><![CDATA[egon<CDATA:tag>olsen]]></value>
                        </T3:field>
                    </data>
                </T3FlexForms>',
                [
                    'data' => [
                        'field' => [
                            'vDEF1' => 'egon',
                            'vDEF2' => 'egon<CDATA:tag>olsen',
                        ],
                    ],
                ],
            ],
            'inputWithNameSpaceOnRootAndNonRootLevel' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3:T3FlexForms xmlns:T3="https://typo3.org/ns/T3">
                    <data>
                        <T3:field>
                            <value index="vDEF1">egon</value>
                            <value index="vDEF2"><![CDATA[egon<CDATA:tag>olsen]]></value>
                        </T3:field>
                    </data>
                </T3:T3FlexForms>',
                [
                    'data' => [
                        'field' => [
                            'vDEF1' => 'egon',
                            'vDEF2' => 'egon<CDATA:tag>olsen',
                        ],
                    ],
                ],
            ],
            'inputWithUndefinedNamespace' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3:T3FlexForms>
                    <data>
                        <T3:field>
                            <value index="vDEF1">egon</value>
                            <value index="vDEF2"><![CDATA[egon<CDATA:tag>olsen]]></value>
                        </T3:field>
                    </data>
                </T3:T3FlexForms>',
                [
                    'data' => [
                        'field' => [
                            'vDEF1' => 'egon',
                            'vDEF2' => 'egon<CDATA:tag>olsen',
                        ],
                    ],
                ],
                [Typo3XmlSerializerOptions::ALLOW_UNDEFINED_NAMESPACES => true],
            ],
        ];
    }

    #[DataProvider('decodeHandlesTagNamespacesDataProvider')]
    #[Test]
    public function decodeHandlesTagNamespaces(string $input, array $expected, array $options = []): void
    {
        $xmlDecoder = new Typo3XmlParser();
        self::assertSame(
            $expected,
            $xmlDecoder->decode($input, new Typo3XmlSerializerOptions($options + [Typo3XmlSerializerOptions::NAMESPACE_PREFIX => 'T3:']))
        );
    }

    /**
     * @return array[]
     */
    public static function decodeReturnsRootNodeNameDataProvider(): array
    {
        return [
            'input' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3FlexForms>
                    <data>
                        <field index="settings.persistenceIdentifier">
                            <value index="vDEF">egon</value>
                        </field>
                    </data>
                </T3FlexForms>',
                'T3FlexForms',
            ],
            'input-with-root-namespace' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3:T3FlexForms xmlns:T3="https://typo3.org/ns/T3">
                    <data>
                        <field index="settings.persistenceIdentifier">
                            <value index="vDEF">egon</value>
                        </field>
                    </data>
                </T3:T3FlexForms>',
                'T3:T3FlexForms',
            ],
            'input-with-namespace' => [
                '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
                <T3FlexForms xmlns:T3="https://typo3.org/ns/T3">
                    <data>
                        <T3:field index="settings.persistenceIdentifier">
                            <value index="vDEF">egon</value>
                        </T3:field>
                    </data>
                </T3FlexForms>',
                'T3FlexForms',
            ],
        ];
    }

    #[DataProvider('decodeReturnsRootNodeNameDataProvider')]
    #[Test]
    public function decodeReturnsRootNodeName(string $input, string $rootNodeName): void
    {
        $xmlDecoder = new Typo3XmlParser();
        $expected = [
            'data' => [
                'settings.persistenceIdentifier' => [
                    'vDEF' => 'egon',
                ],
            ],
            '_DOCUMENT_TAG' => $rootNodeName,
        ];
        self::assertSame(
            $expected,
            $xmlDecoder->decode($input, new Typo3XmlSerializerOptions([Typo3XmlSerializerOptions::RETURN_ROOT_NODE_NAME => true]))
        );
    }

    #[DataProvider('decodeReturnsRootNodeNameDataProvider')]
    #[Test]
    public function decodeCanIncludeRootNode(string $input, string $rootNodeName, array $options = []): void
    {
        $xmlDecoder = new Typo3XmlParser();
        $expected = [
            $rootNodeName => [
                'data' => [
                    'settings.persistenceIdentifier' => [
                        'vDEF' => 'egon',
                    ],
                ],
            ],
        ];
        self::assertSame(
            $expected,
            $xmlDecoder->decode($input, new Typo3XmlSerializerOptions($options + [Typo3XmlSerializerOptions::INCLUDE_ROOT_NODE => true]))
        );
    }

    public static function decodeHandlesBigXmlContentDataProvider(): array
    {
        return [
            '1mb' => [1],
            '5mb' => [5],
            '10mb' => [10],
        ];
    }

    #[DataProvider('decodeHandlesBigXmlContentDataProvider')]
    #[Test]
    public function decodeHandlesBigXmlContent(int $megabytes): void
    {
        $input = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
            <T3FlexForms>
                <data>
                    <field index="settings.persistenceIdentifier">
                        <value index="vDEF">' . str_repeat('1', $megabytes * 1024 * 1024) . '</value>
                    </field>
                </data>
            </T3FlexForms>';
        $testValue = str_repeat('1', $megabytes * 1024 * 1024);
        $xmlDecoder = new Typo3XmlParser();
        $expected = [
            'data' => [
                'settings.persistenceIdentifier' => [
                    'vDEF' => $testValue,
                ],
            ],
        ];
        self::assertSame($expected, $xmlDecoder->decode($input));
    }

    /**
     * @return array[]
     */
    public static function decodeHandlesAttributeTypesDataProvider(): array
    {
        $prefix = '<?xml version="1.0" encoding="utf-8" standalone="yes"?><T3FlexForms><field index="index">';
        $suffix = '</field></T3FlexForms>';
        return [
            'no-type string' => [
                $prefix . '<value index="vDEF">foo bar</value>' . $suffix,
                'foo bar',
            ],
            'no-type string with blank line' => [
                $prefix . '<value index="vDEF">foo bar' . PHP_EOL . '</value>' . $suffix,
                'foo bar' . PHP_EOL,
            ],
            'no-type integer' => [
                $prefix . '<value index="vDEF">123</value>' . $suffix,
                '123',
            ],
            'no-type double' => [
                $prefix . '<value index="vDEF">1.23</value>' . $suffix,
                '1.23',
            ],
            'integer integer' => [
                $prefix . '<value index="vDEF" type="integer">123</value>' . $suffix,
                123,
            ],
            'integer double' => [
                $prefix . '<value index="vDEF" type="integer">1.23</value>' . $suffix,
                1,
            ],
            'double integer' => [
                $prefix . '<value index="vDEF" type="double">123</value>' . $suffix,
                123.0,
            ],
            'double double' => [
                $prefix . '<value index="vDEF" type="double">1.23</value>' . $suffix,
                1.23,
            ],
            'boolean 0' => [
                $prefix . '<value index="vDEF" type="boolean">0</value>' . $suffix,
                false,
            ],
            'boolean 1' => [
                $prefix . '<value index="vDEF" type="boolean">1</value>' . $suffix,
                true,
            ],
            'boolean true' => [
                $prefix . '<value index="vDEF" type="boolean">true</value>' . $suffix,
                true,
            ],
            'boolean false' => [
                $prefix . '<value index="vDEF" type="boolean">false</value>' . $suffix,
                true, // sic(!)
            ],
            'NULL' => [
                $prefix . '<value index="vDEF" type="NULL"></value>' . $suffix,
                null,
            ],
            'NULL string' => [
                $prefix . '<value index="vDEF" type="NULL">foo bar</value>' . $suffix,
                null,
            ],
            'NULL integer' => [
                $prefix . '<value index="vDEF" type="NULL">123</value>' . $suffix,
                null,
            ],
            'NULL double' => [
                $prefix . '<value index="vDEF" type="NULL">1.23</value>' . $suffix,
                null,
            ],
            'array' => [
                $prefix . '<value index="vDEF" type="array"></value>' . $suffix,
                [],
            ],
            'array with blank line' => [
                $prefix . '<value index="vDEF" type="array">' . PHP_EOL . '</value>' . $suffix,
                [],
            ],
        ];
    }

    #[DataProvider('decodeHandlesAttributeTypesDataProvider')]
    #[Test]
    public function decodeHandlesAttributeTypes(string $input, mixed $expected): void
    {
        $xmlDecoder = new Typo3XmlParser();
        $result = $xmlDecoder->decode($input);
        self::assertSame($expected, $result['index']['vDEF']);
    }

    #[Test]
    public function decodeHandlesBase64Attribute(): void
    {
        $xmlDecoder = new Typo3XmlParser();
        $content = file_get_contents(__DIR__ . '/Fixtures/file.gif');
        $contentBase64Encoded = chunk_split(base64_encode($content));
        $input = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<T3FlexForms>
    <field index="index">
        <value index="image" base64="1">
' . $contentBase64Encoded . '</value>
    </field>
</T3FlexForms>
        ';
        $result = $xmlDecoder->decode($input);
        self::assertSame($content, $result['index']['image']);
    }

    public static function decodeThrowsExceptionOnXmlParsingErrorDataProvider(): array
    {
        return [
            'emptyXml' => [
                '',
                [],
                1630773210,
            ],
            'invalidXml' => [
                '<node>content',
                [],
                1630773230,
            ],
            'invalidNodeDocumentType' => [
                '<!DOCTYPE dummy SYSTEM "dummy.dtd"><dummy/>',
                [],
                1630773261,
            ],
            'noValidRootNode' => [
                '<phparray></phparray>',
                [Typo3XmlSerializerOptions::IGNORED_NODE_TYPES => [\XML_ELEMENT_NODE]],
                1630773276,
            ],
        ];
    }

    #[DataProvider('decodeThrowsExceptionOnXmlParsingErrorDataProvider')]
    #[Test]
    public function decodeThrowsExceptionOnXmlParsingError(
        string $data,
        array $config,
        int $expected
    ): void {
        $this->expectException(Exception::class);
        $this->expectExceptionCode($expected);
        $xmlDecoder = new Typo3XmlParser();
        $xmlDecoder->decode($data, new Typo3XmlSerializerOptions($config));
    }

    #[Test]
    public function encodeDecodePingPongSucceeds(): void
    {
        $input = [
            'types' => [
                'string' => 'text',
                'string-with-special-character' => 'text & image',
                'int' => 3,
                'bool' => false,
                'double' => 4.2,
                'null' => null,
            ],
            'binary' => file_get_contents(__DIR__ . '/Fixtures/file.gif'),
            'empty' => [],
            'associative' => [
                'node1' => 'value1',
                'node2' => 'value2',
            ],
            'numeric' => [
                'value1',
                'value2',
            ],
            'numeric-n-index' => [
                'value1',
                'value2',
            ],
            'nested' => [
                'node1' => 'value1',
                'node2' => [
                    'node' => 'value',
                ],
            ],
        ];
        $additionalOptions = [
            'useIndexTagForNum' => 'numbered-index',
            'alt_options' => [
                '/numeric-n-index' => [
                    'useNindex' => true,
                ],
                '/nested' => [
                    'useIndexTagForAssoc' => 'nested-outer',
                    'clearStackPath' => true,
                    'alt_options' => [
                        '/nested-outer' => [
                            'useIndexTagForAssoc' => 'nested-inner',
                        ],
                    ],
                ],
            ],
        ];
        $encodingOptions = [
            Typo3XmlParserOptions::NAMESPACE_PREFIX => 'T3:',
        ];
        $decodingOptions = [
            Typo3XmlSerializerOptions::NAMESPACE_PREFIX => 'T3:',
            Typo3XmlSerializerOptions::ALLOW_UNDEFINED_NAMESPACES => true,
        ];
        $xmlEncoder = new Typo3XmlSerializer();
        $xmlDecoder = new Typo3XmlParser();
        $arrayEncoded = $xmlEncoder->encode($input, new Typo3XmlParserOptions($encodingOptions), $additionalOptions);
        $arrayEncodedDecoded = $xmlDecoder->decode($arrayEncoded, new Typo3XmlSerializerOptions($decodingOptions));
        self::assertEquals($input, $arrayEncodedDecoded);
    }

    #[Test]
    public function encodeDecodePingPongFailsForEmptyArray(): void
    {
        // @todo: decoding an encoded empty array returns '\n'. This is probably
        //        not intended and not required for backward compatibility.
        $xmlEncoder = new Typo3XmlSerializer();
        $xmlDecoder = new Typo3XmlParser();
        $arrayEncoded = $xmlEncoder->encode([]);
        $arrayEncodedDecoded = $xmlDecoder->decode($arrayEncoded);
        self::assertEquals(chr(10), $arrayEncodedDecoded);
    }
}
