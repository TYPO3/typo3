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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class represents a created link to a resource (page, email etc.), coming from LinkService.
 * After it was executed by the LinkBuilders (mostly in Frontend) after it is called from Typolink.
 */
class LinkResult implements LinkResultInterface, \Stringable, \JsonSerializable
{
    public const STRING_CAST_HTML = 1;
    public const STRING_CAST_JSON = 2;

    protected string $type = LinkService::TYPE_UNKNOWN;
    protected string $url;
    protected string $target = '';
    protected array $additionalAttributes = [];
    protected ?string $linkText = null;
    protected array $linkConfiguration = [];
    protected int $flags = self::STRING_CAST_HTML;

    /**
     * Use this method to create a new LinkResult for a specific output format (HTML or JSON)
     */
    public static function adapt(LinkResultInterface $other, int $flags = self::STRING_CAST_HTML): self
    {
        $target = $other;
        if (!$target instanceof self) {
            $target = GeneralUtility::makeInstance(self::class, $other->getType(), $other->getUrl());
            $target->target = $other->getTarget();
            $target->additionalAttributes = $target->filterAdditionalAttributes($other->getAttributes());
            $target->linkText = $other->getLinkText();
            $target->linkConfiguration = $other->getLinkConfiguration();
        }
        return $target->withFlags($flags);
    }

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

    public function withFlags(int $flags): self
    {
        if ($flags !== self::STRING_CAST_HTML && $flags !== self::STRING_CAST_JSON) {
            $flags = self::STRING_CAST_HTML;
        }
        if ($this->flags === $flags) {
            return $this;
        }
        $target = clone $this;
        $target->flags = $flags;
        return $target;
    }

    protected function filterAdditionalAttributes(array $attributes): array
    {
        return array_filter(
            $attributes,
            static fn(string $key) => !in_array($key, ['href', 'target', 'class', 'title'], true),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array{href: ?string, target: ?string, class: ?string, title: ?string, linkText: ?string, additionalAttributes: array}
     */
    public function toArray(): array
    {
        return [
            'href' => $this->url ?: null,
            'target' => $this->target ?: null,
            'class' => $this->getAttribute('class') ?: null,
            'title' => $this->getAttribute('title') ?: null,
            'linkText' => $this->linkText ?: null,
            'additionalAttributes' => $this->filterAdditionalAttributes($this->getAttributes()),
        ];
    }

    public function getHtml(): string
    {
        return sprintf(
            '<a %s>%s</a>',
            GeneralUtility::implodeAttributes($this->getAttributes(), true),
            $this->linkText
        );
    }

    public function getJson(): string
    {
        try {
            return json_encode($this, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return '';
        }
    }

    public function __toString(): string
    {
        if ($this->flags === self::STRING_CAST_HTML) {
            return $this->getHtml();
        }
        if ($this->flags === self::STRING_CAST_JSON) {
            return $this->getJson();
        }
        throw new \LogicException('Unsupported flags assignment', 1666024513);
    }
}
