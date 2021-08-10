<?php

declare(strict_types = 1);

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

namespace TYPO3\CMS\Core\Html;

/**
 * Simple HTML node parser. The main focus is to determine "runaway nodes"
 * like `<span attribute="<runaway attribute="other">` and better nod boundaries.
 *
 * (Most of) the behavior is similar to Mozilla's behavior on handling those nodes.
 * (e.g. `div.innerHTML = 'x =<y>= z';` - but without creating closing node blocks)
 *
 * This parser does not resolve nested nodes - it just provides a flat node sequence.
 *
 * @internal
 */
class SimpleParser
{
    /**
     * @var string|null
     */
    protected $attribute;

    /**
     * @var SimpleNode[]
     */
    protected $nodes = [];

    /**
     * @var int
     */
    protected $currentType = SimpleNode::TYPE_TEXT;

    /**
     * @var string
     */
    protected $currentData = '';

    public static function fromString(string $string): self
    {
        return new self($string);
    }

    public function __construct(string $string)
    {
        $this->process($string);
    }

    /**
     * @param int ...$types using `Node::TYPE_*`
     * @return SimpleNode[]
     */
    public function getNodes(int ...$types): array
    {
        if (empty($types)) {
            return $this->nodes;
        }
        $nodes = array_filter(
            $this->nodes,
            function (SimpleNode $node) use ($types): bool {
                return in_array(
                    $node->getType(),
                    $types,
                    true
                );
            }
        );
        // reindex nodes
        return array_values($nodes);
    }

    /**
     * @param int|null $type using `Node::TYPE_*`
     * @return SimpleNode|null
     */
    public function getFirstNode(int $type = null): ?SimpleNode
    {
        foreach ($this->nodes as $node) {
            if ($type === null || $type === $node->getType()) {
                return $node;
            }
        }
        return null;
    }

    /**
     * @param int|null $type using `Node::TYPE_*`
     * @return SimpleNode|null
     */
    public function getLastNode(int $type = null): ?SimpleNode
    {
        foreach (array_reverse($this->nodes) as $node) {
            if ($type === null || $type === $node->getType()) {
                return $node;
            }
        }
        return null;
    }

    /**
     * Processes token sequence and creates corresponding `Node` instances.
     *
     * @param string $string
     */
    protected function process(string $string): void
    {
        $skip = 0;
        $characters = str_split($string, 1);
        foreach ($characters as $i => $character) {
            // skip tokens that already haven been processed
            if ($skip > 0 && $skip-- > 0) {
                continue;
            }
            // CDATA start
            if ($character === '<'
                && $this->isType(SimpleNode::TYPE_TEXT) && substr($string, $i, 9) === '<![CDATA['
            ) {
                $this->next(SimpleNode::TYPE_CDATA);
                $this->append('<![CDATA[');
                $skip = 8;
            // comment start
            } elseif ($character === '<'
                && $this->isType(SimpleNode::TYPE_TEXT) && substr($string, $i, 4) === '<!--'
            ) {
                $this->next(SimpleNode::TYPE_COMMENT);
                $this->append('<!--');
                $skip = 3;
            // element start
            } elseif ($character === '<'
                && $this->isType(SimpleNode::TYPE_TEXT)
                && preg_match('#^</?[a-z]#i', substr($string, $i, 3))
            ) {
                $this->next(SimpleNode::TYPE_ELEMENT);
                $this->append($character);
            // CDATA end
            } elseif ($character === ']'
                && $this->isType(SimpleNode::TYPE_CDATA) && substr($string, $i, 3) === ']]>'
            ) {
                $this->append(']]>');
                $this->next(SimpleNode::TYPE_TEXT);
                $skip = 2;
            // comment end
            } elseif ($character === '-'
                && $this->isType(SimpleNode::TYPE_COMMENT) && substr($string, $i, 3) === '-->'
            ) {
                $this->append('-->');
                $this->next(SimpleNode::TYPE_TEXT);
                $skip = 2;
            // element end
            } elseif ($character === '>'
                && $this->isType(SimpleNode::TYPE_ELEMENT) && !$this->inAttribute()
            ) {
                $this->append($character);
                $this->next(SimpleNode::TYPE_TEXT);
            // element attribute start
            } elseif (($character === '"' || $character === "'")
                && $this->isType(SimpleNode::TYPE_ELEMENT) && !$this->inAttribute()
            ) {
                $this->attribute = $character;
                $this->append($character);
            // element attribute end
            } elseif (($character === '"' || $character === "'")
                && $this->isType(SimpleNode::TYPE_ELEMENT) && $this->attribute === $character
            ) {
                $this->append($character);
                $this->attribute = null;
            // anything else (put to current type)
            } else {
                $this->append($character);
            }
        }
        $this->finish();
    }

    /**
     * Triggers creating "next" node instance, resets current state.
     *
     * @param int $nextType
     */
    protected function next(int $nextType): void
    {
        if ($this->currentData !== '') {
            $this->nodes[] = SimpleNode::fromString(
                $this->currentType,
                count($this->nodes),
                $this->currentData
            );
        }
        $this->currentType = $nextType;
        $this->currentData = '';
    }

    /**
     * Finishes missing text node instance - anything else (all of those are
     * tag-like "runaway" scenarios e.g. `<anything<!-- anything...` without being
     * closed correctly - those nodes are ignored on purpose!
     */
    protected function finish(): void
    {
        if ($this->currentData === '') {
            return;
        }
        if ($this->isType(SimpleNode::TYPE_TEXT)) {
            $this->nodes[] = SimpleNode::fromString(
                $this->currentType,
                count($this->nodes),
                $this->currentData
            );
        }
        // either unfinished element or comment
        // (ignored on purpose)
    }

    protected function append(string $string): void
    {
        $this->currentData .= $string;
    }

    protected function isType(int $type): bool
    {
        return $this->currentType === $type;
    }

    protected function inAttribute(): bool
    {
        return $this->attribute !== null;
    }
}
