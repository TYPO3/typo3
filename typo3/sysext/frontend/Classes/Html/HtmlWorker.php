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

use Masterminds\HTML5;
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

    protected ?\DOMNode $mount = null;
    protected ?\DOMDocument $document = null;

    public function __construct(
        protected readonly LinkFactory $linkFactory,
        protected readonly HTML5 $parser
    ) {}

    public function __toString(): string
    {
        if (!$this->mount instanceof \DOMNode || !$this->document instanceof \DOMDocument) {
            return '';
        }
        return $this->parser->saveHTML($this->mount->childNodes);
    }

    public function parse(string $html): self
    {
        // use document fragment to separate markup from default structure (html, body, ...)
        $fragment = $this->parser->parseFragment($html);
        // mount fragment to make it accessible in current document
        $this->mount = $this->mountFragment($fragment);
        $this->document = $this->mount->ownerDocument;
        return $this;
    }

    /**
     * @param string|ConsumableNonce $nonce none value to be added
     * @param string ...$nodeNames element node names to be processed (e.g. `style`)
     */
    public function addNonceAttribute(string|ConsumableNonce $nonce, string ...$nodeNames): self
    {
        if ($nodeNames === []) {
            return $this;
        }
        $xpath = new \DOMXPath($this->document);
        foreach ($nodeNames as $nodeName) {
            $expression = sprintf('//%s[not(@*)]', $nodeName);
            /** @var \DOMElement $element */
            foreach ($xpath->query($expression, $this->mount) as $element) {
                $element->setAttribute('nonce', (string)$nonce);
            }
        }
        return $this;
    }

    public function transformUri(string $selector, int $flags = 0): self
    {
        if (!$this->mount instanceof \DOMNode || !$this->document instanceof \DOMDocument) {
            return $this;
        }
        $subjects = $this->parseSelector($selector);
        // use xpath to traverse potential candidates having "links"
        $xpath = new \DOMXPath($this->document);
        foreach ($subjects as $subject) {
            $attrName = $subject['attr'];
            $expression = sprintf('//%s[@%s]', $subject['node'], $attrName);
            /** @var \DOMElement $element */
            foreach ($xpath->query($expression, $this->mount) as $element) {
                $elementAttrValue = $element->getAttribute($attrName);
                $scheme = parse_url($elementAttrValue, PHP_URL_SCHEME);
                // skip values not having a URI-scheme
                if (empty($scheme)) {
                    continue;
                }
                try {
                    $linkResult = $this->linkFactory->createUri($elementAttrValue);
                } catch (UnableToLinkException $exception) {
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
     * @param \DOMElement $element current element encountered failure
     * @param array{node: string, attr: string} $subject node-attr combination
     */
    protected function onTransformUriFailure(\DOMElement $element, array $subject, int $flags): void
    {
        if (($flags & self::REMOVE_TAG_ON_FAILURE) === self::REMOVE_TAG_ON_FAILURE) {
            $element->parentNode->removeChild($element);
        } elseif (($flags & self::REMOVE_ATTR_ON_FAILURE) === self::REMOVE_ATTR_ON_FAILURE) {
            $attrName = $subject['attr'];
            $element->removeAttribute($attrName);
        } elseif (($flags & self::REMOVE_ENCLOSURE_ON_FAILURE) === self::REMOVE_ENCLOSURE_ON_FAILURE) {
            // moves children out of element's enclosure, then removes (empty) element
            // eg `<ELEMENT><a><b><c></ELEMENT><NEXT>`
            // 1) `<ELEMENT><b><c></ELEMENT><a><NEXT>`
            // 2) `<ELEMENT><c></ELEMENT><a><b><NEXT>`
            // 3) `<ELEMENT></ELEMENT><a><b><c><NEXT>`
            // rm `<a><b><c><NEXT>`
            $parentNode = $element->parentNode;
            foreach ($element->childNodes as $child) {
                $cloned = $child->cloneNode(true);
                $parentNode->insertBefore($cloned, $element);
            }
            $parentNode->removeChild($element);
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

    protected function mountFragment(\DOMDocumentFragment $fragment): \DOMNode
    {
        $document = $fragment->ownerDocument;
        $mount = $document->createElement('div');
        $document->appendChild($mount);
        if ($fragment->hasChildNodes()) {
            $mount->appendChild($fragment);
        }
        return $mount;
    }
}
