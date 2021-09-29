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

namespace TYPO3\CMS\Core\Serializer;

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Encodes PHP array to XML string.
 *
 * A dedicated set of entry properties is stored in XML during conversion:
 * - XML node attribute "index" stores original entry key if XML node name differs from entry
 *   key
 * - XML node attribute "type" stores entry value type ("bool", "int", "double", ...)
 * - XML node attribute "base64" specifies if entry value is binary (for example an image)
 * These attributes are interpreted during decoding of the XML string with XmlDecoder::decode().
 *
 * Specific encoding configuration can be set by $additionalOptions - for the full array or array paths.
 * For example
 * ```php
 * $input = [
 *      'numeric' => [
 *          'value1',
 *          'value2'
 *      ],
 *      'numeric-n-index' => [
 *          'value1',
 *          'value2'
 *      ],
 *      'nested' => [
 *          'node1' => 'value1',
 *          'node2' => [
 *              'node' => 'value'
 *          ]
 *      ]
 * ];
 * $additionalOptions = [
 *      'useIndexTagForNum' => 'numbered-index'
 *      'alt_options' => [
 *          '/numeric-n-index' => [
 *              'useNindex' => true
 *          ],
 *          '/nested' => [
 *              'useIndexTagForAssoc' => 'nested-outer',
 *              'clearStackPath' => true,
 *              'alt_options' => [
 *                  '/nested-outer' => [
 *                      'useIndexTagForAssoc' => 'nested-inner'
 *                  ]
 *              ]
 *          ]
 *      ]
 * ];
 * ```
 * =>
 * ```xml
 * <phparray>
 *      <numeric type="array">
 *          <numbered-index index="0">value1</numbered-index>
 *          <numbered-index index="1">value2</numbered-index>
 *      </numeric>
 *      <numeric-n-index type="array">
 *          <n0>value1</n0>
 *          <n1>value2</n1>
 *      </numeric-n-index>
 *      <nested type="array">
 *          <nested-outer index="node1">value1</nested-outer>
 *          <nested-outer index="node2" type="array">
 *              <nested-inner index="node">value</nested-inner>
 *          </nested-outer>
 *      </nested>
 * </phparray>
 * ```
 * Available options are:
 * - grandParentTagMap[grandParentTagName/parentTagName] [string]
 *      Convert array key X to XML node name "{grandParentTagMap}" with node attribute "index=X"
 *      - if grand-parent is "{grandParentTagName}" and parent node is "{parentTagName}".
 * - parentTagMap[parentTagName:_IS_NUM] [string]
 *      Convert array key X to XML node name "{parentTagMap}" with node attribute "index=X"
 *      - if parent node is "{parentTagName}" and current node is number-indexed.
 * - parentTagMap[parentTagName:nodeName] [string]
 *      Convert array key X to XML node name "{parentTagMap}" with node attribute "index=X"
 *      - if parent node is "{parentTagName}" and current node is "{nodeName}".
 * - parentTagMap[parentTagName] [string]
 *      Convert array key X to XML node name "{parentTagMap}" with node attribute "index=X"
 *      - if parent node is "{parentTagName}".
 * - useNindex [bool]
 *      Convert number-indexed array key X to XML node name "nX".
 * - useIndexTagForNum [string]
 *      Convert number-indexed array key X to XML node name "{useIndexTagForNum}" with node
 *      attribute "index=X".
 * - useIndexTagForAssoc [string]
 *      Convert associative array key X to XML node name "{useIndexTagForAssoc}" with node
 *      attribute "index=X".
 * - disableTypeAttrib [bool|int]
 *      Disable node attribute "type" for all value types
 *      (true = disable for all except arrays, 2 = disable for all).
 * - useCDATA [bool]
 *      Wrap node value with <![CDATA[{node value}]]> - if text contains special characters.
 * - alt_options[/.../nodeName] [array]
 *      Set new options for specific array path.
 * - clearStackPath [bool]
 *      Resetting internal counter when descending the array hierarchy: Allows using relative
 *      array path in nested "alt_options" instead of absolute path.
 *
 * @internal still experimental
 */
class Typo3XmlSerializer
{
    /**
     * This method serves as a wrapper for encode() and is used to replace
     * GeneralUtility::array2xml(), which returns an exception as a string instead of throwing it.
     * In perspective, all uses of this method should be replaced by encode() and the exceptions
     * should be handled locally.
     *
     * @param array $input PHP array
     * @param Typo3XmlParserOptions|null $options Encoding configuration - see encode() for details
     * @param array $additionalOptions Encoding options - see encode() for details
     * @return string XML or exception
     */
    public function encodeWithReturningExceptionAsString(
        array $input,
        Typo3XmlParserOptions $options = null,
        array $additionalOptions = []
    ): string {
        try {
            return $this->encode($input, $options, $additionalOptions);
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param array $input PHP array
     * @param Typo3XmlParserOptions|null $options Apply specific encoding configuration - XML format, namespace prefix and root node name
     * @param array $additionalOptions Apply specific encoding options - for the full array or specific array paths.
     * @return string XML string
     */
    public function encode(
        array $input,
        Typo3XmlParserOptions $options = null,
        array $additionalOptions = []
    ): string {
        $options = $options ?? new Typo3XmlParserOptions();
        return $this->parseArray(
            $input,
            $options,
            $additionalOptions
        );
    }

    protected function parseArray(
        array $input,
        Typo3XmlParserOptions $options,
        array $additionalOptions,
        int $level = 0,
        array $stackData = []
    ): string {
        $xml = '';

        $rootNodeName = $options->getRootNodeName();
        if (empty($rootNodeName)) {
            $indentation = str_repeat($options->getIndentationStep(), $level);
        } else {
            $indentation = str_repeat($options->getIndentationStep(), $level + 1);
        }

        foreach ($input as $key => $value) {
            // Construct the node name + attributes
            $nodeName = $key = (string)$key;
            $nodeAttributes = '';
            if (isset(
                $stackData['grandParentTagName'],
                $stackData['parentTagName'],
                $additionalOptions['grandParentTagMap'][$stackData['grandParentTagName'] . '/' . $stackData['parentTagName']]
            )) {
                // ... based on grand-parent + parent node name
                $nodeName = (string)$additionalOptions['grandParentTagMap'][$stackData['grandParentTagName'] . '/' . $stackData['parentTagName']];
                $nodeAttributes = ' index="' . htmlspecialchars($key) . '"';
            } elseif (isset(
                $stackData['parentTagName'],
                $additionalOptions['parentTagMap'][$stackData['parentTagName'] . ':_IS_NUM']
            ) && MathUtility::canBeInterpretedAsInteger($nodeName)
            ) {
                // ... based on parent node name + if current node name is numeric
                $nodeName = (string)$additionalOptions['parentTagMap'][$stackData['parentTagName'] . ':_IS_NUM'];
                $nodeAttributes = ' index="' . htmlspecialchars($key) . '"';
            } elseif (isset(
                $stackData['parentTagName'],
                $additionalOptions['parentTagMap'][$stackData['parentTagName'] . ':' . $nodeName]
            )) {
                // ... based on parent node name + current node name
                $nodeName = (string)$additionalOptions['parentTagMap'][$stackData['parentTagName'] . ':' . $nodeName];
                $nodeAttributes = ' index="' . htmlspecialchars($key) . '"';
            } elseif (isset(
                $stackData['parentTagName'],
                $additionalOptions['parentTagMap'][$stackData['parentTagName']]
            )) {
                // ... based on parent node name
                $nodeName = (string)$additionalOptions['parentTagMap'][$stackData['parentTagName']];
                $nodeAttributes = ' index="' . htmlspecialchars($key) . '"';
            } elseif (MathUtility::canBeInterpretedAsInteger($nodeName)) {
                // ... if current node name is numeric
                if ($additionalOptions['useNindex'] ?? false) {
                    $nodeName = 'n' . $nodeName;
                } else {
                    $nodeName = ($additionalOptions['useIndexTagForNum'] ?? false) ?: 'numIndex';
                    $nodeAttributes = ' index="' . $key . '"';
                }
            } elseif (!empty($additionalOptions['useIndexTagForAssoc'])) {
                // ... if current node name is string
                $nodeName = $additionalOptions['useIndexTagForAssoc'];
                $nodeAttributes = ' index="' . htmlspecialchars($key) . '"';
            }
            $nodeName = $this->cleanUpNodeName($nodeName);

            // Construct the node value
            if (is_array($value)) {
                // ... if has sub elements
                if (isset($additionalOptions['alt_options'])
                    && ($additionalOptions['alt_options'][($stackData['path'] ?? '') . '/' . $nodeName] ?? false)
                ) {
                    $subOptions = $additionalOptions['alt_options'][($stackData['path'] ?? '') . '/' . $nodeName];
                    $clearStackPath = (bool)($subOptions['clearStackPath'] ?? false);
                } else {
                    $subOptions = $additionalOptions;
                    $clearStackPath = false;
                }
                if (empty($value)) {
                    $nodeValue = '';
                } else {
                    $nodeValue = $options->getNewlineChar();
                    $nodeValue .= $this->parseArray(
                        $value,
                        $options,
                        $subOptions,
                        $level + 1,
                        [
                            'parentTagName' => $nodeName,
                            'grandParentTagName' => $stackData['parentTagName'] ?? '',
                            'path' => $clearStackPath ? '' : ($stackData['path'] ?? '') . '/' . $nodeName,
                        ]
                    );
                    $nodeValue .= $indentation;
                }
                // Dropping the "type=array" attribute makes the XML prettier, but means that empty
                // arrays are not restored with XmlDecoder::decode().
                if (($additionalOptions['disableTypeAttrib'] ?? false) !== 2) {
                    $nodeAttributes .= ' type="array"';
                }
            } else {
                // ... if is simple value
                if ($this->isBinaryValue($value)) {
                    $nodeValue = $options->getNewlineChar() . chunk_split(base64_encode($value));
                    $nodeAttributes .= ' base64="1"';
                } else {
                    $type = gettype($value);
                    if ($type === 'string') {
                        $nodeValue = htmlspecialchars($value);
                        if (($additionalOptions['useCDATA'] ?? false) && $nodeValue !== $value) {
                            $nodeValue = '<![CDATA[' . $value . ']]>';
                        }
                    } else {
                        $nodeValue = $value;
                        if (($additionalOptions['disableTypeAttrib'] ?? false) === false) {
                            $nodeAttributes .= ' type="' . $type . '"';
                        }
                    }
                }
            }

            // Construct the node
            if ($nodeName !== '') {
                $xml .= $indentation;
                $xml .= '<' . $options->getNamespacePrefix() . $nodeName . $nodeAttributes . '>';
                $xml .= $nodeValue;
                $xml .= '</' . $options->getNamespacePrefix() . $nodeName . '>';
                $xml .= $options->getNewlineChar();
            }
        }

        // Wrap with the root node if it is on the outermost level.
        if ($level === 0 && !empty($rootNodeName)) {
            $xml = '<' . $rootNodeName . '>' . $options->getNewlineChar() . $xml . '</' . $rootNodeName . '>';
        }

        return $xml;
    }

    /**
     * The node name is cleaned so that it contains only alphanumeric characters (plus - and _) and
     * is no longer than 100 characters.
     *
     * @param string $nodeName
     * @return string Cleaned node name
     */
    protected function cleanUpNodeName(string $nodeName): string
    {
        return substr((string)preg_replace('/[^[:alnum:]_-]/', '', $nodeName), 0, 100);
    }

    /**
     * Is $value the content of a binary file, for example an image? If so, this value must be
     * stored in a binary-safe manner so that it can be decoded correctly later.
     *
     * @param mixed $value
     * @return bool
     */
    protected function isBinaryValue(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $binaryChars = "\0" . chr(1) . chr(2) . chr(3) . chr(4) . chr(5)
            . chr(6) . chr(7) . chr(8) . chr(11) . chr(12)
            . chr(14) . chr(15) . chr(16) . chr(17) . chr(18)
            . chr(19) . chr(20) . chr(21) . chr(22) . chr(23)
            . chr(24) . chr(25) . chr(26) . chr(27) . chr(28)
            . chr(29) . chr(30) . chr(31);

        $length = strlen($value);

        return $length && strcspn($value, $binaryChars) !== $length;
    }
}
