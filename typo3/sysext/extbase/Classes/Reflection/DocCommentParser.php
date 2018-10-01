<?php
namespace TYPO3\CMS\Extbase\Reflection;

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

/**
 * A little parser which creates tag objects from doc comments
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class DocCommentParser
{
    /**
     * @var array
     */
    protected static $ignoredTags = ['package', 'subpackage', 'license', 'copyright', 'author', 'version', 'const'];

    /**
     * @var string The description as found in the doc comment
     */
    protected $description = '';

    /**
     * @var array An array of tag names and their values (multiple values are possible)
     */
    protected $tags = [];

    /**
     * @var bool
     */
    private $useIgnoredTags;

    /**
     * @param bool $useIgnoredTags
     */
    public function __construct($useIgnoredTags = false)
    {
        $this->useIgnoredTags = $useIgnoredTags;
    }

    /**
     * Parses the given doc comment and saves the result (description and
     * tags) in the parser's object. They can be retrieved by the
     * getTags() getTagValues() and getDescription() methods.
     *
     * @param string $docComment A doc comment as returned by the reflection getDocComment() method
     */
    public function parseDocComment($docComment)
    {
        $this->description = '';
        $this->tags = [];
        $lines = explode(LF, $docComment);
        foreach ($lines as $line) {
            if ($line !== '' && strpos($line, '@') !== false) {
                $this->parseTag(substr($line, strpos($line, '@')));
            } elseif (empty($this->tags)) {
                $this->description .= preg_replace('#\\s*/?[*/]*(.*)$#', '$1', $line) . LF;
            }
        }
        $this->description = trim($this->description);
    }

    /**
     * Returns the tags which have been previously parsed
     *
     * @return array Array of tag names and their (multiple) values
     */
    public function getTagsValues()
    {
        return $this->tags;
    }

    /**
     * Returns the values of the specified tag. The doc comment
     * must be parsed with parseDocComment() before tags are
     * available.
     *
     * @param string $tagName The tag name to retrieve the values for
     * @throws \RuntimeException
     * @return array The tag's values
     */
    public function getTagValues($tagName)
    {
        if (!$this->isTaggedWith($tagName)) {
            throw new \RuntimeException('Tag "' . $tagName . '" does not exist.', 1169128255);
        }
        return $this->tags[$tagName];
    }

    /**
     * Checks if a tag with the given name exists
     *
     * @param string $tagName The tag name to check for
     * @return bool TRUE the tag exists, otherwise FALSE
     */
    public function isTaggedWith($tagName)
    {
        return isset($this->tags[$tagName]);
    }

    /**
     * Returns the description which has been previously parsed
     *
     * @return string The description which has been parsed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Parses a line of a doc comment for a tag and its value.
     * The result is stored in the interal tags array.
     *
     * @param string $line A line of a doc comment which starts with an @-sign
     */
    protected function parseTag($line)
    {
        $tagAndValue = preg_split('/\\s/', $line, 2);
        $tag = substr($tagAndValue[0], 1);

        if ($this->useIgnoredTags && in_array($tag, static::$ignoredTags, true)) {
            return;
        }

        if (count($tagAndValue) > 1) {
            $this->tags[$tag][] = trim($tagAndValue[1]);
        } else {
            $this->tags[$tag] = [];
        }
    }
}
