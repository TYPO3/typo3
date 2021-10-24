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

use TYPO3\CMS\Core\LinkHandling\LinkService;

/**
 * This class represents a created link to a resource (page, email etc), coming from linkService
 * and after it was executed by the LinkBuilders (mostly in Frontend) after it is called from Typolink.
 */
class LinkResult implements LinkResultInterface, \JsonSerializable, \ArrayAccess
{
    protected string $type = LinkService::TYPE_UNKNOWN;
    protected string $url;
    protected string $target = '';
    protected array $additionalAttributes = [];
    protected ?string $linkText = null;
    protected array $linkConfiguration = [];

    public function __construct(string $type, string $url)
    {
        $this->type = $type;
        $this->url = $url;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function withTarget(string $target): self
    {
        $newObject = clone $this;
        $newObject->target = $target;
        return $newObject;
    }

    /**
     * @return array<string, string>
     */
    public function getLinkConfiguration(): array
    {
        return $this->linkConfiguration;
    }

    public function withLinkConfiguration(array $configuration): self
    {
        $newObject = clone $this;
        $newObject->linkConfiguration = $configuration;
        return $newObject;
    }

    public function withLinkText(string $linkText): self
    {
        $newObject = clone $this;
        $newObject->linkText = $linkText;
        return $newObject;
    }

    public function getLinkText(): ?string
    {
        return $this->linkText;
    }

    public function withAttributes(array $additionalAttributes, bool $resetExistingAttributes = false): self
    {
        $newObject = clone $this;
        if ($resetExistingAttributes) {
            $newObject->additionalAttributes = [];
            $newObject->url = '';
            $newObject->target = '';
        }
        foreach ($additionalAttributes as $attributeName => $attributeValue) {
            switch ($attributeName) {
                case 'href':
                    $newObject->url = $attributeValue;
                break;
                case 'target':
                    $newObject->target = $attributeValue;
                break;
            }
            if ($attributeValue !== null) {
                $newObject->additionalAttributes[$attributeName] = $attributeValue;
            } else {
                unset($newObject->additionalAttributes[$attributeName]);
            }
        }
        return $newObject;
    }

    public function withAttribute(string $attributeName, ?string $attributeValue): self
    {
        $newObject = clone $this;
        switch ($attributeName) {
            case 'href':
                $newObject->url = $attributeValue ?? '';
                break;
            case 'target':
                $newObject->target = $attributeValue ?? '';
                break;
            default:
                if ($attributeValue !== null) {
                    $newObject->additionalAttributes[$attributeName] = $attributeValue;
                } else {
                    unset($newObject->additionalAttributes[$attributeName]);
                }
        }
        return $newObject;
    }

    public function jsonSerialize(): array
    {
        $additionalAttrs = $this->additionalAttributes;
        foreach ($additionalAttrs as $attribute => $value) {
            if (in_array($attribute, ['href', 'target', 'class', 'title'], true)) {
                unset($additionalAttrs[$attribute]);
            }
        }

        return [
            'href' => $this->url ?: null,
            'target' => $this->target ?: null,
            'class' => $this->additionalAttributes['class'] ?? null,
            'title' => $this->additionalAttributes['title'] ?? null,
            'linkText' => $this->linkText ?: null,
            'additionalAttributes' => $additionalAttrs,
        ];
    }

    public function hasAttribute(string $attributeName): bool
    {
        switch ($attributeName) {
            case 'href':
                return $this->url !== '';
            case 'target':
                return $this->target !== '';
            default:
                return isset($this->additionalAttributes[$attributeName]);
        }
    }
    public function getAttribute(string $attributeName): ?string
    {
        switch ($attributeName) {
            case 'href':
                return $this->url;
            case 'target':
                return $this->target;
            default:
                return $this->additionalAttributes[$attributeName] ?? null;
        }
    }
    public function getAttributes(): array
    {
        $attributes = [];
        if ($this->url) {
            $attributes['href'] = $this->url;
        }
        if ($this->target) {
            $attributes['target'] = $this->target;
        }
        return array_merge($attributes, $this->additionalAttributes);
    }

    public function __toString(): string
    {
        try {
            return json_encode($this->jsonSerialize(), JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return '';
        }
    }

    /**
     * Kept for legacy reasons, will be removed in TYPO3 v12.0.
     * This is built because the LinkBuilders now return an object instead an array with three items.
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        switch ($offset) {
            case 0:
            case '0':
                return $this->url !== '';
            case 1:
            case '1':
                return $this->linkText !== null;
            case 2:
            case '2':
                return $this->target !== '';
            default:
                return false;
        }
    }

    // @todo Will this also removed in TYPO3 v12.0, like offsetExists ?
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        switch ($offset) {
            case 0:
            case '0':
                return $this->getUrl();
            case 1:
            case '1':
                return $this->getLinkText();
            case 2:
            case '2':
                return $this->getTarget();
            default:
                return null;
        }
    }

    // @todo Will this also removed in TYPO3 v12.0, like offsetExists ?
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        switch ($offset) {
            case 0:
            case '0':
                $this->url = (string)$value;
                break;
            case 1:
            case '1':
                $this->linkText = (string)$value;
                break;
            case 2:
            case '2':
                $this->target = (string)$value;
                break;
            default:
                // do nothing
        }
    }

    // @todo Will this also removed in TYPO3 v12.0, like offsetExists ?
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        switch ($offset) {
            case 0:
            case '0':
                $this->url = '';
                break;
            case 1:
            case '1':
                $this->linkText = null;
                break;
            case 2:
            case '2':
                $this->target = '';
                break;
            default:
                // do nothing
        }
    }
}
