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

namespace TYPO3\CMS\Reactions\Model;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Reactions\Reaction\CreateRecordReaction;

/**
 * The request the backend module shows for a reaction, so an
 * integrator reads what to send rather than what a generic example.
 *
 * @internal This is a specific Backend module implementation and is not considered part of the Public TYPO3 API.
 */
final readonly class ReactionExample
{
    private const string SECRET_PLACEHOLDER = '<your-secret>';
    private const string SAMPLE_VALUE = 'value';

    private function __construct(
        private string $url,
        private array $payload,
    ) {}

    /**
     * The payload is composed of the placeholders the field map reads, so the example names
     * the keys this reaction resolves. A map carrying none of them, every field left unset
     * or fixed to a value, still needs a body, and gets a minimal one.
     *
     * Placeholders are found with the pattern the reaction resolves them with, and paths
     * are read with the same utility, so what the example offers and what the reaction
     * accepts cannot drift apart. Where one path is the parent of another, "${a}" and
     * "${a.b}", the parent is kept whichever comes first, since only it offers both.
     */
    public static function forFieldMap(string $url, array $fields): self
    {
        $payload = [];
        foreach ($fields as $value) {
            if (!is_string($value)) {
                continue;
            }
            preg_match_all(CreateRecordReaction::PLACEHOLDER_PATTERN, $value, $matches);
            foreach ($matches[1] as $path) {
                if ($path === '' || ArrayUtility::isValidPath($payload, $path, '.')) {
                    continue;
                }
                try {
                    $payload = ArrayUtility::setValueByPath($payload, $path, self::SAMPLE_VALUE, '.');
                } catch (\RuntimeException) {
                    // The path names no segment the payload could carry, "${a..b}". The
                    // reaction cannot resolve it either, so the example does not offer it.
                }
            }
        }

        return new self($url, $payload !== [] ? $payload : ['key' => self::SAMPLE_VALUE]);
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getCurlCommand(): string
    {
        return implode(" \\\n", [
            'curl -X POST ' . $this->quote($this->url),
            '  -H ' . $this->quote('Content-Type: application/json'),
            '  -H ' . $this->quote('x-api-key: ' . self::SECRET_PLACEHOLDER),
            '  -d ' . $this->quote($this->getPayload()),
        ]);
    }

    public function getFormattedPayload(): string
    {
        return json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function getPayload(): string
    {
        return json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Single quotes, since the body is JSON and carries double quotes of its own. A single
     * quote in a configured path would end that quoting, so it is spelled the only way a
     * single quoted string can carry one.
     */
    private function quote(string $value): string
    {
        return "'" . str_replace("'", "'\\''", $value) . "'";
    }
}
