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
 * Soft Reference parsing interface
 *
 * "Soft References" are references to database elements, files, email addresses, URLs etc.
 * which are found in-text in content. The <a href="t3://page?[page_id]> tag from typical bodytext fields
 * is an example of this.
 * This interface defines the "parse" method, which parsers have to implement.
 * TYPO3 has already implemented parsers for the most well-known types. Soft Reference Parsers can also be user-defined.
 * The Soft Reference Parsers are used by the system to find these references and process them accordingly in import/export actions and copy operations.
 */
interface SoftReferenceParserInterface
{
    /**
     * Main function through which can parse content for a specific field.
     *
     * @param string $table Database table name
     * @param string $field Field name for which processing occurs
     * @param int $uid UID of the record
     * @param string $content The content/value of the field
     * @param string $structurePath If running from inside a FlexForm structure, this is the path of the tag.
     * @return SoftReferenceParserResult Result object on positive matches, see description above.
     * @see SoftReferenceParserResult
     */
    public function parse(string $table, string $field, int $uid, string $content, string $structurePath = ''): SoftReferenceParserResult;

    /**
     * The two properties parserKey and parameters may be set to generate a unique token ID from them.
     * This is not needed for every parser, but useful if a parser can deal with multiple parser keys.
     *
     * @param string $parserKey The softref parser key.
     * @param array $parameters Parameters of the softlink parser. Basically this is the content inside optional []-brackets after the softref keys. Parameters are exploded by ";
     */
    public function setParserKey(string $parserKey, array $parameters): void;

    /**
     * Returns the parser key, which was previously set by "setParserKey"
     *
     * @return string
     */
    public function getParserKey(): string;
}
