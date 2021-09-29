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

/**
 * @internal still experimental
 */
class Typo3XmlSerializerOptions
{
    public const INCLUDE_ROOT_NODE = 'include_root_node';
    public const IGNORED_NODE_TYPES = 'ignored_node_types';
    public const LOAD_OPTIONS = 'load_options';
    public const NAMESPACE_PREFIX = 'namespace_prefix';
    public const ALLOW_UNDEFINED_NAMESPACES = 'allow_undefined_namespaces';
    public const RETURN_ROOT_NODE_NAME = 'return_root_node_name';

    protected array $options = [
        // Ignore XML node types when converting to a PHP array.
        self::IGNORED_NODE_TYPES => [\XML_PI_NODE, \XML_COMMENT_NODE],
        // Use the XML root node or its children as the first level of the PHP array.
        self::INCLUDE_ROOT_NODE => false,
        // Apply these libxml2 options when loading the XML.
        self::LOAD_OPTIONS => \LIBXML_NONET | \LIBXML_NOBLANKS,
        // Remove this XML namespace from each XML node, for example "T3:".
        self::NAMESPACE_PREFIX => '',
        // Gracefully handle missing namespace declarations, for example <T3:T3FlexForms> without xmlns attribute.
        self::ALLOW_UNDEFINED_NAMESPACES => false,
        // Append the name of the XML root node to the PHP array key "_DOCUMENT_TAG".
        self::RETURN_ROOT_NODE_NAME => false,
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }
    public function getLoadOptions(): int
    {
        return $this->options[self::LOAD_OPTIONS];
    }
    public function getIgnoredNodeTypes(): array
    {
        return $this->options[self::IGNORED_NODE_TYPES];
    }
    public function includeRootNode(): bool
    {
        return $this->options[self::INCLUDE_ROOT_NODE];
    }
    public function hasNamespacePrefix(): bool
    {
        return $this->options[self::NAMESPACE_PREFIX] !== '';
    }
    public function getNamespacePrefix(): string
    {
        return $this->options[self::NAMESPACE_PREFIX];
    }
    public function allowUndefinedNamespaces(): bool
    {
        return $this->options[self::ALLOW_UNDEFINED_NAMESPACES];
    }
    public function returnRootNodeName(): bool
    {
        return $this->options[self::RETURN_ROOT_NODE_NAME];
    }
}
