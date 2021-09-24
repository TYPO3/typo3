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
 * Just report if a value is found, nothing more.
 */
class NotifySoftReferenceParser implements SoftReferenceParserInterface
{
    protected string $parserKey = '';
    protected array $parameters = [];

    /**
     * @param string $parserKey The softref parser key.
     * @param array $parameters Parameters of the softlink parser. Basically this is the content inside optional []-brackets after the softref keys. Parameters are exploded by ";
     */
    public function setParserKey(string $parserKey, array $parameters): void
    {
        $this->parserKey = $parserKey;
        $this->parameters = $parameters;
    }

    public function getParserKey(): string
    {
        return $this->parserKey;
    }

    public function parse(string $table, string $field, int $uid, string $content, string $structurePath = ''): SoftReferenceParserResult
    {
        // Simple notification
        return SoftReferenceParserResult::create(
            '',
            [
                [
                    'matchString' => $content,
                ],
            ]
        );
    }

    /**
     * @internal will be removed in favor of ->parse() in TYPO3 v12.0.
     */
    public function findRef(string $table, string $field, int $uid, string $content, string $spKey, array $spParams, string $structurePath = '')
    {
        return $this->parse($table, $field, $uid, $content, $structurePath)->toNullableArray();
    }
}
