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

class JavaScriptModuleInstruction implements \JsonSerializable
{
    /**
     * Indicates a requireJS module shall be loaded.
     */
    public const FLAG_LOAD_REQUIRE_JS = 1;

    /**
     * Indicates a ES6/11 module shall be loaded (paths mapped by an importmap)
     */
    public const FLAG_LOAD_IMPORTMAP = 2;

    /**
     * Indicates all actions shall be applied globally to `top.window`.
     */
    public const FLAG_USE_TOP_WINDOW = 16;

    public const ITEM_ASSIGN = 'assign';
    public const ITEM_INVOKE = 'invoke';
    public const ITEM_INSTANCE = 'instance';

    protected string $name;
    protected ?string $exportName;
    protected int $flags;
    protected array $items = [];

    /**
     * @param string $name Module name mapped by an importmap or absolute specifier
     * @param string|null $exportName (optional) name used internally to export the module
     * @return self
     */
    public static function create(string $name, string $exportName = null): self
    {
        $target = GeneralUtility::makeInstance(static::class, $name, self::FLAG_LOAD_IMPORTMAP);
        $target->exportName = $exportName;
        return $target;
    }

    /**
     * @param string $name RequireJS module name
     * @param string|null $exportName (optional) name used internally to export the module
     * @return self
     */
    public static function forRequireJS(string $name, string $exportName = null): self
    {
        $target = GeneralUtility::makeInstance(static::class, $name, self::FLAG_LOAD_REQUIRE_JS);
        $target->exportName = $exportName;
        return $target;
    }

    /**
     * @param array $state
     * @return self
     * @internal
     */
    public static function fromState(array $state)
    {
        $target = GeneralUtility::makeInstance(static::class, $state['name'], $state['flags'] ?? 0);
        $target->exportName = $state['exportName'] ?? null;
        $target->items = $state['items'];
        return $target;
    }

    /**
     * @param string $name Module name
     * @param int $flags
     */
    public function __construct(string $name, int $flags)
    {
        $this->name = $name;
        $this->flags = $flags;
    }

    /**
     * @internal
     */
    public function getState(): array
    {
        return [
            'name' => $this->name,
            'exportName' => $this->exportName,
            'flags' => $this->flags,
            'items' => $this->items,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->getState();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getExportName(): ?string
    {
        return $this->exportName;
    }

    public function getFlags(): int
    {
        return $this->flags;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param int ...$flags
     * @return $this
     */
    public function addFlags(int ...$flags): self
    {
        foreach ($flags as $flag) {
            $this->flags |= $flag;
        }
        return $this;
    }

    /**
     * @param array $assignments key-value assignments
     * @return static
     */
    public function assign(array $assignments): self
    {
        $this->items[] = [
            'type' => static::ITEM_ASSIGN,
            'assignments' => $assignments,
        ];
        return $this;
    }

    /**
     * @param string|null $method method of JavaScript module to be invoked
     * @param mixed ...$args corresponding method arguments
     * @return static
     */
    public function invoke(?string $method = null, ...$args): self
    {
        $this->items[] = [
            'type' => static::ITEM_INVOKE,
            'method' => $method,
            'args' => $args,
        ];
        return $this;
    }

    /**
     * @param mixed ...$args new instance arguments
     * @return static
     */
    public function instance(...$args): self
    {
        $this->items[] = [
            'type' => static::ITEM_INSTANCE,
            'args' => $args,
        ];
        return $this;
    }

    public function shallLoadRequireJs(): bool
    {
        return ($this->flags & self::FLAG_LOAD_REQUIRE_JS) === self::FLAG_LOAD_REQUIRE_JS;
    }

    public function shallLoadImportMap(): bool
    {
        return ($this->flags & self::FLAG_LOAD_IMPORTMAP) === self::FLAG_LOAD_IMPORTMAP;
    }

    public function shallUseTopWindow(): bool
    {
        return ($this->flags & self::FLAG_USE_TOP_WINDOW) === self::FLAG_USE_TOP_WINDOW;
    }
}
