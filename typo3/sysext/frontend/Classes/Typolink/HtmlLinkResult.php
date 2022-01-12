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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Decorator for LinkResult, using htmlspecialchars() for all attributes.
 * It is recommended to use the HtmlLinkResult when working with HTML pages,
 * but for JSON-based renderings the pure LinkResult might be more useful.
 *
 * @internal Marked as internal for now, as this class might change at any time.
 */
class HtmlLinkResult implements LinkResultInterface, \Stringable
{
    protected LinkResultInterface $linkResult;

    public function __construct(LinkResultInterface $linkResult)
    {
        $this->linkResult = $linkResult;
        // Ensure to never double HSC anything by wrapping a HtmlLinkResult in another HtmlLinkResult
        while ($this->linkResult instanceof self) {
            $this->linkResult = $this->linkResult->getOriginalResult();
        }
    }

    public function getUrl(): string
    {
        return $this->linkResult->getUrl();
    }

    public function getType(): string
    {
        return $this->linkResult->getType();
    }

    public function getTarget(): string
    {
        return $this->linkResult->getTarget();
    }

    public function getLinkConfiguration(): array
    {
        return $this->linkResult->getLinkConfiguration();
    }

    public function getLinkText(): ?string
    {
        return $this->linkResult->getLinkText();
    }

    public function withLinkText(string $linkText): self
    {
        $this->linkResult = $this->linkResult->withLinkText($linkText);
        return $this;
    }

    public function withTarget(string $target): self
    {
        $this->linkResult = $this->linkResult->withTarget($target);
        return $this;
    }

    public function withAttributes(array $additionalAttributes, bool $resetExistingAttributes = false): self
    {
        $this->linkResult = $this->linkResult->withAttributes($additionalAttributes, $resetExistingAttributes);
        return $this;
    }

    public function withAttribute(string $attributeName, ?string $attributeValue): self
    {
        $this->linkResult = $this->linkResult->withAttribute($attributeName, $attributeValue);
        return $this;
    }
    public function hasAttribute(string $attributeName): bool
    {
        return $this->linkResult->hasAttribute($attributeName);
    }

    public function getAttribute(string $attributeName): ?string
    {
        $attributeValue = $this->linkResult->getAttribute($attributeName);
        if ($attributeValue === null) {
            return null;
        }
        return htmlspecialchars($attributeValue);
    }

    public function getAttributes(): array
    {
        $attributeValues = $this->linkResult->getAttributes();
        return array_map('htmlspecialchars', $attributeValues);
    }

    public function getOriginalResult(): LinkResultInterface
    {
        return $this->linkResult;
    }

    public function __toString(): string
    {
        return '<a ' . GeneralUtility::implodeAttributes($this->linkResult->getAttributes(), true) . '>' . $this->linkResult->getLinkText() . '</a>';
    }
}
