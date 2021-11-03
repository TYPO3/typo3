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

namespace TYPO3\CMS\Core\Page;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class JavaScriptRenderer
{
    protected string $handlerUri;
    protected ?RequireJS $requireJS = null;

    /**
     * @var list<array>
     */
    protected array $globalAssignments = [];

    /**
     * @var list<JavaScriptModuleInstruction>
     */
    protected array $javaScriptModuleInstructions = [];

    public static function create(string $uri = null): self
    {
        $uri ??= PathUtility::getAbsoluteWebPath(
            GeneralUtility::getFileAbsFileName('EXT:core/Resources/Public/JavaScript/JavaScriptHandler.js')
        );
        return GeneralUtility::makeInstance(static::class, $uri);
    }

    public function __construct(string $handlerUri)
    {
        $this->handlerUri = $handlerUri;
    }

    public function loadRequireJS(RequireJS $requireJS): void
    {
        $this->requireJS = $requireJS;
    }

    public function addGlobalAssignment(array $payload): void
    {
        if (empty($payload)) {
            return;
        }
        $this->globalAssignments[] = $payload;
    }

    public function addJavaScriptModuleInstruction(JavaScriptModuleInstruction $instruction): void
    {
        $this->javaScriptModuleInstructions[] = $instruction;
    }

    /**
     * @return list<array{type: string, payload: mixed}>
     * @internal
     */
    public function toArray(): array
    {
        if ($this->isEmpty()) {
            return [];
        }
        $items = [];
        if ($this->requireJS !== null) {
            $items[] = [
                'type' => 'loadRequireJS',
                'payload' => $this->requireJS,
            ];
        }
        foreach ($this->globalAssignments as $item) {
            $items[] = [
                'type' => 'globalAssignment',
                'payload' => $item,
            ];
        }
        foreach ($this->javaScriptModuleInstructions as $item) {
            $items[] = [
                'type' => 'javaScriptModuleInstruction',
                'payload' => $item,
            ];
        }
        return $items;
    }

    public function render(): string
    {
        if ($this->isEmpty()) {
            return '';
        }
        return $this->createScriptElement([
            'src' => $this->handlerUri,
            'data-process-type' => 'processItems',
        ], $this->jsonEncode($this->toArray()));
    }

    protected function isEmpty(): bool
    {
        return $this->requireJS === null
            && $this->globalAssignments === []
            && empty($this->javaScriptModuleInstructions);
    }

    protected function createScriptElement(array $attributes, string $textContent = ''): string
    {
        if (empty($attributes)) {
            return '';
        }
        $attributesPart = GeneralUtility::implodeAttributes($attributes, true);
        // actual JSON payload is stored as comment in `script.textContent`
        return sprintf('<script %s>/* %s */</script>', $attributesPart, $textContent);
    }

    protected function jsonEncode($value): string
    {
        return (string)json_encode($value, JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG);
    }
}
