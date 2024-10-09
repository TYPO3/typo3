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

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class JavaScriptRenderer
{
    protected string $handlerUri;
    protected JavaScriptItems $items;
    protected ImportMap $importMap;
    protected int $javaScriptModuleInstructionFlags = 0;

    /**
     * @internal Only to be used by PageRenderer
     */
    public static function create(?string $uri = null): self
    {
        $uri ??= PathUtility::getAbsoluteWebPath(
            GeneralUtility::getFileAbsFileName('EXT:core/Resources/Public/JavaScript/java-script-item-handler.js')
        );
        return GeneralUtility::makeInstance(static::class, $uri);
    }

    /**
     * @internal
     */
    public function __construct(string $handlerUri)
    {
        $this->handlerUri = $handlerUri;
        $this->items = new JavaScriptItems();
        $this->importMap = GeneralUtility::makeInstance(ImportMapFactory::class)->create();
    }

    public function addGlobalAssignment(array $payload): void
    {
        $this->items->addGlobalAssignment($payload);
    }

    public function addJavaScriptModuleInstruction(JavaScriptModuleInstruction $instruction): void
    {
        if ($instruction->shallLoadImportMap()) {
            $this->importMap->includeImportsFor($instruction->getName());
        }
        $this->javaScriptModuleInstructionFlags |= $instruction->getFlags();
        $this->items->addJavaScriptModuleInstruction($instruction);
    }

    public function hasImportMap(): bool
    {
        return ($this->javaScriptModuleInstructionFlags & JavaScriptModuleInstruction::FLAG_LOAD_IMPORTMAP) === JavaScriptModuleInstruction::FLAG_LOAD_IMPORTMAP;
    }

    /**
     * HEADS UP: Do only use in authenticated mode as this discloses as installed extensions
     */
    public function includeAllImports(): void
    {
        $this->importMap->includeAllImports();
    }

    public function includeTaggedImports(string $tag): void
    {
        $this->importMap->includeTaggedImports($tag);
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
        return $this->items->toArray();
    }

    public function render(null|string|ConsumableNonce $nonce = null): string
    {
        if ($this->isEmpty()) {
            return '';
        }
        $attributes = [
            'src' => $this->handlerUri,
            'async' => 'async',
        ];
        if ($nonce !== null) {
            $attributes['nonce'] = (string)$nonce;
        }
        return $this->createScriptElement(
            $attributes,
            $this->jsonEncode($this->toArray())
        );
    }

    public function renderImportMap(string $sitePath, null|string|ConsumableNonce $nonce = null): string
    {
        if (!$this->isEmpty()) {
            $this->importMap->includeImportsFor('@typo3/core/java-script-item-handler.js');
        }
        return $this->importMap->render($sitePath, $nonce);
    }

    protected function isEmpty(): bool
    {
        return $this->items->isEmpty();
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

    /**
     * @internal
     */
    public function updateState(array $state): void
    {
        foreach ($state as $var => $value) {
            switch ($var) {
                case 'items':
                    $this->items->updateState($value);
                    break;
                case 'importMap':
                    $this->importMap->updateState($value);
                    break;
                default:
                    $this->{$var} = $value;
                    break;
            }
        }
    }

    /**
     * @internal
     */
    public function getState(): array
    {
        $state = [];
        foreach (get_object_vars($this) as $var => $value) {
            switch ($var) {
                case 'items':
                    $state[$var] = $this->items->getState();
                    break;
                case 'importMap':
                    $state[$var] = $this->importMap->getState();
                    break;
                default:
                    $state[$var] = $value;
                    break;
            }
        }
        return $state;
    }
}
