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

namespace TYPO3\CMS\Frontend\Typolink;

/**
 * Interface representing a created link to any type (page, file etc).
 */
interface LinkResultInterface
{
    public function getUrl(): string;

    public function getType(): string;

    public function getTarget(): string;

    public function getLinkConfiguration(): array;

    public function getLinkText(): ?string;

    public function withLinkText(string $linkText): self;

    public function withTarget(string $target): self;

    public function withAttributes(array $additionalAttributes, bool $resetExistingAttributes = false): self;
    public function withAttribute(string $attributeName, ?string $attributeValue): self;
    public function hasAttribute(string $attributeName): bool;
    public function getAttribute(string $attributeName): ?string;
    public function getAttributes(): array;
}
