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

namespace TYPO3\CMS\Core\LinkHandling;

/**
 * This class represents an object containing the resolved parameters of a typolink
 */
final readonly class TypolinkParameter implements \JsonSerializable
{
    public function __construct(
        public string $url = '',
        public string $target = '',
        public string $class = '',
        public string $title = '',
        public string $additionalParams = '',
        public array $customParams = [],
    ) {}

    public static function createFromTypolinkParts(array $typoLinkParts): TypolinkParameter
    {
        $url = $typoLinkParts['url'] ?? '';
        $target = $typoLinkParts['target'] ?? '';
        $class = $typoLinkParts['class'] ?? '';
        $title = $typoLinkParts['title'] ?? '';
        $additionalParams = $typoLinkParts['additionalParams'] ?? '';
        unset($typoLinkParts['url'], $typoLinkParts['target'], $typoLinkParts['class'], $typoLinkParts['title'], $typoLinkParts['additionalParams']);

        return new self(
            $url,
            $target,
            $class,
            $title,
            $additionalParams,
            $typoLinkParts
        );
    }

    public function toArray(): array
    {
        return array_merge([
            'url' => $this->url,
            'target' => $this->target,
            'class' => $this->class,
            'title' => $this->title,
            'additionalParams' => $this->additionalParams,
        ], $this->customParams);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
