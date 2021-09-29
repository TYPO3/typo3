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
class Typo3XmlParserOptions
{
    public const FORMAT = 'format';
    public const FORMAT_INLINE = -1;
    public const FORMAT_PRETTY_WITH_TAB = 0;
    public const NAMESPACE_PREFIX = 'namespace_prefix';
    public const ROOT_NODE_NAME = 'root_node_name';

    protected array $options = [
        // Format XML with
        // - "-1" is inline XML
        // - "0" is pretty XML with tabs
        // - "1...x" is pretty XML with x spaces.
        self::FORMAT => self::FORMAT_PRETTY_WITH_TAB,
        // This XML namespace is prepended to each XML node, for example "T3:".
        self::NAMESPACE_PREFIX => '',
        // Wrap the XML with a root node of that name or set it to '' to skip wrapping.
        self::ROOT_NODE_NAME => 'phparray',
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    public function getRootNodeName(): string
    {
        return $this->options[self::ROOT_NODE_NAME];
    }

    public function getNewlineChar(): string
    {
        return $this->options[self::FORMAT] === self::FORMAT_INLINE ? '' : LF;
    }

    public function getIndentationStep(): string
    {
        return match ($this->options[self::FORMAT]) {
            self::FORMAT_INLINE => '',
            self::FORMAT_PRETTY_WITH_TAB => "\t",
            default => str_repeat(' ', max(0, $this->options[self::FORMAT])),
        };
    }

    public function getNamespacePrefix(): string
    {
        return $this->options[self::NAMESPACE_PREFIX];
    }
}
