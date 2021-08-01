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

namespace TYPO3\CMS\Core\DataHandling\SoftReference;

/**
 * The result object has two properties: "content" and matched elements.
 *
 * content:
 * Is a string containing the input content but possibly with tokens inside.
 * Tokens are strings like {softref:[tokenID]}, which is a placeholder for a value extracted by a softref parser.
 * For each token there MUST be an entry in the "elements" key, which has a "subst" key defining the tokenID and the tokenValue. See below.
 *
 * matched elements:
 * is an array where the keys are insignificant, but the values are arrays with these keys:
 * "matchString" => // The value of the match. This is only for informational purposes to show what was found.
 * "error"       => // An error message can be set here, like "file not found" etc.
 * "subst"       => [ // If this array is found there MUST be a token in the output content as well!
 *     "tokenID"     => // The tokenID string corresponding to the token in output content, {softref:[tokenID]}. This is typically an md5 hash of a string defining uniquely the position of the element.
 *     "tokenValue"  => // The value that the token substitutes in the text. Basically, if this value is inserted instead of the token the content should match what was inputted originally.
 *     "type"        => // file / db / string = the type of substitution. "file" means it is a relative file [automatically mapped], "db" means a database record reference [automatically mapped], "string" means it is manually modified string content (eg. an email address)
 *     "relFileName" => // (for "file" type): Relative filename. May not necessarily exist. This could be noticed in the error key.
 *     "recordRef"   => // (for "db" type) : Reference to DB record on the form [table]:[uid]. May not necessarily exist.
 *     "title"       => // Title of element (for backend information)
 *     "description" => // Description of element (for backend information)
 * ]
 */
final class SoftReferenceParserResult
{
    private string $content = '';
    private array $elements = [];
    private bool $hasMatched = false;

    public static function create(string $content, array $elements): self
    {
        if ($elements === []) {
            return self::createWithoutMatches();
        }

        $obj = new self();
        $obj->content = $content;
        $obj->elements = $elements;
        $obj->hasMatched = true;

        return $obj;
    }

    public static function createWithoutMatches(): self
    {
        return new self();
    }

    public function hasMatched(): bool
    {
        return $this->hasMatched;
    }

    public function hasContent(): bool
    {
        return $this->content !== '';
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getMatchedElements(): array
    {
        return $this->elements;
    }

    /**
     * @internal This method is added for backwards-compatibility of TYPO3 v11, and not part of the official TYPO3 Core API.
     */
    public function toNullableArray(): ?array
    {
        if (!$this->hasMatched()) {
            return null;
        }

        $result = [];
        if ($this->hasContent()) {
            $result['content'] = $this->content;
        }
        $result['elements'] = $this->elements;

        return $result;
    }
}
