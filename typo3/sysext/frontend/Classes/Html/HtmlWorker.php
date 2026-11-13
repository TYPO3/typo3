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

namespace TYPO3\CMS\Frontend\Html;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;

/**
 * @internal API still might change
 */
#[Autoconfigure(public: true)]
class HtmlWorker
{
    /**
     * Removes corresponding tag in case there's a failure
     * e.g. `<a href="t3://!!INVALID!!">value</a>` --> ``
     */
    public const REMOVE_TAG_ON_FAILURE = 1;

    /**
     * Removes corresponding attribute in case there's a failure
     * e.g. `<a href="t3://!!INVALID!!">value</a>` --> `<a>value</a>`
     */
    public const REMOVE_ATTR_ON_FAILURE = 2;

    /**
     * Removes corresponding enclosure in case there's a failure
     * e.g. `<a href="t3://!!INVALID!!">value</a>` --> `value`
     */
    public const REMOVE_ENCLOSURE_ON_FAILURE = 4;

    protected ?\Dom\Element $mount = null;

    public function __construct(
        protected readonly LinkFactory $linkFactory,
    ) {}

    public function __toString(): string
    {
        return $this->mount === null ? '' : $this->mount->innerHTML;
    }

    public function parse(string $html): self
    {
        // fragment parsing with a `<div>` context element, keeps markup apart from the default structure (html, body, ...)
        $this->mount = \Dom\HTMLDocument::createEmpty()->createElement('div');
        $this->mount->innerHTML = $html;
        return $this;
    }

    /**
     * @param string|ConsumableNonce $nonce none value to be added
     * @param string ...$nodeNames element node names to be processed (e.g. `style`)
     */
    public function addNonceAttribute(string|ConsumableNonce $nonce, string ...$nodeNames): self
    {
        if ($nodeNames === [] || $this->mount === null) {
            return $this;
        }
        foreach ($nodeNames as $nodeName) {
            foreach ($this->findElements($nodeName) as $element) {
                if ($element->attributes->length === 0) {
                    $element->setAttribute('nonce', (string)$nonce);
                }
            }
        }
        return $this;
    }

    public function transformUri(string $selector, int $flags = 0): self
    {
        if ($this->mount === null) {
            return $this;
        }
        foreach ($this->parseSelector($selector) as $subject) {
            $attrName = $subject['attr'];
            foreach ($this->findElements($subject['node']) as $element) {
                if (!$element->hasAttribute($attrName)) {
                    continue;
                }
                $elementAttrValue = (string)$element->getAttribute($attrName);
                $scheme = parse_url($elementAttrValue, PHP_URL_SCHEME);
                // skip values not having a URI-scheme
                if (empty($scheme)) {
                    continue;
                }
                try {
                    $linkResult = $this->linkFactory->createUri($elementAttrValue);
                } catch (UnableToLinkException) {
                    $this->onTransformUriFailure($element, $subject, $flags);
                    continue;
                }
                $linkResultAttrValues = array_filter($linkResult->getAttributes());
                // usually link results contain `href` attr value, which needs to be assigned
                // to a different value in case selector (e.g. `img.src` instead f `a.href`)
                if (isset($linkResultAttrValues['href']) && $attrName !== 'href') {
                    $element->setAttribute($attrName, $linkResultAttrValues['href']);
                    unset($linkResultAttrValues['href']);
                }
                foreach ($linkResultAttrValues as $name => $value) {
                    $element->setAttribute($name, (string)$value);
                }
            }
        }
        return $this;
    }

    /**
     * @param \Dom\Element $element current element encountered failure
     * @param array{node: string, attr: string} $subject node-attr combination
     */
    protected function onTransformUriFailure(\Dom\Element $element, array $subject, int $flags): void
    {
        if (($flags & self::REMOVE_TAG_ON_FAILURE) === self::REMOVE_TAG_ON_FAILURE) {
            $element->remove();
        } elseif (($flags & self::REMOVE_ATTR_ON_FAILURE) === self::REMOVE_ATTR_ON_FAILURE) {
            $element->removeAttribute($subject['attr']);
        } elseif (($flags & self::REMOVE_ENCLOSURE_ON_FAILURE) === self::REMOVE_ENCLOSURE_ON_FAILURE) {
            // copies children in front of the element, then removes the element
            foreach ($element->childNodes as $child) {
                $element->before($child->cloneNode(true));
            }
            $element->remove();
        }
    }

    /**
     * @return array{node: string, attr: string}[]
     */
    protected function parseSelector(string $selector): array
    {
        $items = GeneralUtility::trimExplode(',', $selector, true);
        $items = array_map(
            static function (string $item): ?array {
                $parts = explode('.', $item);
                if (count($parts) !== 2) {
                    return null;
                }
                return [
                    'node' => $parts[0] ?: '*',
                    'attr' => $parts[1],
                ];
            },
            $items
        );
        return array_filter($items);
    }

    /**
     * Elements are collected upfront, since modifications during
     * iteration would affect the live `HTMLCollection`.
     *
     * @return list<\Dom\Element>
     */
    protected function findElements(string $nodeName): array
    {
        if ($this->mount === null) {
            return [];
        }
        return iterator_to_array($this->mount->getElementsByTagName($nodeName), false);
    }
}
