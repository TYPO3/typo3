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

use TYPO3\CMS\Core\Serializer\Exception\InvalidDataException;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Decodes XML string to PHP array.
 *
 * A dedicated set of node attributes is considered during conversion:
 * - attribute "index" specifies the final node name which is used as key in the PHP array
 * - attribute "type" specifies the node value type which is used for casting
 * - attribute "base64" specifies the node value type being binary and requiring a
 *   base64-decoding
 * These attributes were applied during encoding of the PHP array with XmlEncoder::encode().
 *
 * The node name "n{number}" is converted to a number-indexed array key "{number}".
 *
 * @internal still experimental
 */
class Typo3XmlParser
{
    /**
     * This method serves as a wrapper for decode() and is used to replace
     * GeneralUtility::xml2array(), which returns an exception as a string instead of throwing it.
     * In perspective, all uses of this method should be replaced by decode() and the exceptions
     * should be handled locally.
     *
     * @param string $xml XML string
     * @param Typo3XmlSerializerOptions|null $options Decoding configuration - see decode() for details
     * @return array|string PHP array - or a string if the XML root node is empty or an exception
     */
    public function decodeWithReturningExceptionAsString(
        string $xml,
        Typo3XmlSerializerOptions $options = null
    ): array|string {
        try {
            return $this->decode($xml, $options);
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    /**
     * @param string $xml XML string
     * @param Typo3XmlSerializerOptions|null $options Apply specific decoding configuration - Ignored node types, libxml2 options, ...
     * @return array|string PHP array - or a string if the XML root node is empty
     * @throws InvalidDataException
     */
    public function decode(
        string $xml,
        Typo3XmlSerializerOptions $options = null
    ): array|string {
        $xml = trim($xml);
        if ($xml === '') {
            throw new InvalidDataException(
                'Invalid XML data, it can not be empty.',
                1630773210
            );
        }

        $options = $options ?? new Typo3XmlSerializerOptions();

        if ($options->allowUndefinedNamespaces()) {
            $xml = $this->disableNamespaceInNodeNames($xml);
        }

        $internalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = new \DOMDocument();
        $dom->loadXML($xml, $options->getLoadOptions());

        libxml_use_internal_errors($internalErrors);

        if ($error = libxml_get_last_error()) {
            libxml_clear_errors();
            throw new InvalidDataException(
                'Line ' . $error->line . ': ' . xml_error_string($error->code),
                1630773230
            );
        }

        $rootNode = null;
        foreach ($dom->childNodes as $child) {
            if ($child->nodeType === \XML_DOCUMENT_TYPE_NODE) {
                throw new InvalidDataException(
                    'Document types are not allowed.',
                    1630773261
                );
            }
            if (in_array($child->nodeType, $options->getIgnoredNodeTypes(), true)) {
                continue;
            }
            $rootNode = $child;
            break;
        }
        if ($rootNode === null) {
            throw new InvalidDataException(
                'Root node cannot be determined.',
                1630773276
            );
        }

        $rootNodeName = $rootNode->nodeName;
        if ($options->allowUndefinedNamespaces()) {
            $rootNodeName = $this->reactivateNamespaceInNodeName($rootNodeName);
        }
        if (!$rootNode->hasChildNodes()) {
            if ($options->includeRootNode()) {
                $result = [$rootNodeName => $rootNode->nodeValue];
            } else {
                $result = $rootNode->nodeValue;
            }
        } else {
            if ($options->includeRootNode()) {
                $result = [$rootNodeName => $this->parseXml($rootNode, $options)];
            } else {
                $result = $this->parseXml($rootNode, $options);
            }
        }
        if ($options->returnRootNodeName() && is_array($result)) {
            $result['_DOCUMENT_TAG'] = $rootNodeName;
        }

        return $result;
    }

    /**
     * DOMDocument::loadXML() breaks if prefixes of undefined namespaces are used in node names:
     * Replace namespace divider ":" by temporary "___" string before parsing the XML.
     */
    protected function disableNamespaceInNodeNames(string $value): string
    {
        return preg_replace(
            ['#<([/]?)([[:alnum:]_-]*):([[:alnum:]_-]*)([ >]?)#'],
            ['<$1$2___$3$4'],
            $value
        );
    }

    /**
     * Re-insert the namespace divider ":" into all node names again after parsing the XML.
     */
    protected function reactivateNamespaceInNodeNames(string $value): string
    {
        if (!str_contains($value, '___')) {
            return $value;
        }

        return preg_replace(
            ['#<([/]?)([[:alnum:]_-]*)___([[:alnum:]_-]*)([ >]?)#'],
            ['<$1$2:$3$4'],
            $value
        );
    }

    /**
     * Re-insert the namespace divider ":" into single node name again after parsing the XML.
     */
    protected function reactivateNamespaceInNodeName(string $value): string
    {
        return str_replace('___', ':', $value);
    }

    protected function parseXml(\DOMNode $node, Typo3XmlSerializerOptions $options): array|string|null
    {
        if (!$node->hasChildNodes()) {
            return $node->nodeValue;
        }

        if ($node->childNodes->length === 1
            && in_array($node->firstChild->nodeType, [\XML_TEXT_NODE, \XML_CDATA_SECTION_NODE])
        ) {
            $value = $node->firstChild->nodeValue;
            if ($options->allowUndefinedNamespaces()) {
                $value = $this->reactivateNamespaceInNodeNames($value);
            }
            return $value;
        }

        $result = [];
        foreach ($node->childNodes as $child) {
            if (in_array($child->nodeType, $options->getIgnoredNodeTypes(), true)) {
                continue;
            }

            $value = $this->parseXml($child, $options);

            if ($child instanceof \DOMElement && $child->hasAttribute('index')) {
                $key = $child->getAttribute('index');
            } else {
                $key = $child->nodeName;
                if ($options->allowUndefinedNamespaces()) {
                    $key = $this->reactivateNamespaceInNodeName($key);
                }
                if ($options->hasNamespacePrefix()
                    && str_starts_with($key, $options->getNamespacePrefix())
                ) {
                    $key = substr($key, strlen($options->getNamespacePrefix()));
                }
                if (str_starts_with($key, 'n')
                    && MathUtility::canBeInterpretedAsInteger($index = substr($key, 1))
                ) {
                    $key = (int)$index;
                }
            }

            if ($child instanceof \DOMElement && $child->hasAttribute('base64') && is_string($value)) {
                $value = base64_decode($value);
            } elseif ($child instanceof \DOMElement && $child->hasAttribute('type')) {
                switch ($child->getAttribute('type')) {
                    case 'integer':
                        $value = (int)$value;
                        break;
                    case 'double':
                        $value = (float)$value;
                        break;
                    case 'boolean':
                        $value = (bool)$value;
                        break;
                    case 'NULL':
                        $value = null;
                        break;
                    case 'array':
                        $value = is_array($value) ? $value : (empty(trim($value)) ? [] : (array)$value);
                        break;
                }
            }
            $result[$key] = $value;
        }
        return $result;
    }
}
