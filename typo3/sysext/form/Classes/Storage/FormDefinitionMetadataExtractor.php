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

namespace TYPO3\CMS\Form\Storage;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads the metadata of a form definition file, such as identifier and
 * label, without parsing the whole, potentially large, file.
 *
 * Each metadata property is a top-level key. Its line and all following
 * indented lines are parsed as YAML, so any scalar style is supported,
 * including multi-line values, quoting and comments.
 *
 * @internal
 */
final readonly class FormDefinitionMetadataExtractor
{
    private const PROPERTIES = ['identifier', 'type', 'label', 'prototypeName'];

    /**
     * @return array<string, string>
     */
    public function extract(string $maybeRawFormDefinition): array
    {
        $metaData = [];
        $block = [];
        foreach (explode(LF, $maybeRawFormDefinition) as $line) {
            if ($line === '' || $line[0] === ' ' || $line[0] === '#' || trim($line) === '') {
                if ($block !== []) {
                    $block[] = $line;
                }
                continue;
            }
            $metaData = $this->parseBlock($block, $metaData);
            $block = in_array(trim(explode(':', $line, 2)[0]), self::PROPERTIES, true) ? [$line] : [];
        }
        return $this->parseBlock($block, $metaData);
    }

    /**
     * @param list<string> $block
     * @param array<string, string> $metaData
     * @return array<string, string>
     */
    private function parseBlock(array $block, array $metaData): array
    {
        if ($block === []) {
            return $metaData;
        }
        $key = trim(explode(':', $block[0], 2)[0]);
        try {
            $value = Yaml::parse(implode(LF, $block))[$key] ?? null;
        } catch (ParseException) {
            $value = '';
        }
        if (is_scalar($value)) {
            $metaData[$key] = (string)$value;
        }
        return $metaData;
    }
}
