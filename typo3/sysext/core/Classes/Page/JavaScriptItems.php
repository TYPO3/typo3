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

class JavaScriptItems implements \JsonSerializable
{
    /**
     * @var list<array>
     */
    protected array $globalAssignments = [];

    /**
     * @var list<JavaScriptModuleInstruction>
     */
    protected array $javaScriptModuleInstructions = [];

    public function jsonSerialize(): array
    {
        return $this->toArray();
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

    public function isEmpty(): bool
    {
        return $this->globalAssignments === []
            && empty($this->javaScriptModuleInstructions);
    }
}
