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

namespace TYPO3\CMS\Form\Tests\Functional\Framework\FormHandling;

/**
 * Used to extract data from retrieved html markup for form testing purpose.
 * @see \TYPO3\CMS\Form\Tests\Functional\RequestHandling\RequestHandlingTest
 */
final class FormDataFactory
{
    public function fromHtmlMarkupAndXpath(string $html, string $query = '//form'): FormData
    {
        return new FormData(
            $this->buildFormData(
                $this->extractFormFragment($html, $query)
            )
        );
    }

    private function buildFormData(\DOMDocument $document): array
    {
        $data = [
            'actionQueryData' => [],
            'actionUrl' => '',
            'elementData' => [],
            'DOMDocument' => $document,
        ];
        foreach ($document->getElementsByTagName('form') as $node) {
            $action = $node->getAttribute('action');
            $actionQuery = parse_url($action, PHP_URL_QUERY);
            $queryArray = [];
            parse_str($actionQuery, $queryArray);
            $data['actionQueryData'] = $queryArray;

            [$actionUrl, ] = explode('?', $action);
            $data['actionUrl'] = $actionUrl;

            break;
        }

        $xpath = new \DomXPath($document);
        $nodesWithName = $xpath->query('//*[@name]');
        foreach ($nodesWithName as $node) {
            $name = $node->getAttribute('name');
            foreach ($node->attributes ?? [] as $attribute) {
                $data['elementData'][$name][$attribute->nodeName] = $attribute->nodeValue;
            }
            $data['elementData'][$name]['__isHoneypot'] = $this->isHoneypot($node);
        }

        return $data;
    }

    private function extractFormFragment(string $html, string $query): \DOMDocument
    {
        $document = new \DOMDocument();
        $document->loadHTML($html, LIBXML_NOERROR);

        $xpath = new \DomXPath($document);
        $fragment = new \DOMDocument();
        foreach ($xpath->query($query) as $node) {
            $fragment->appendChild($fragment->importNode($node, true));
        }

        return $fragment;
    }

    private function isHoneypot(\DOMElement $node): bool
    {
        if (!$node->hasAttribute('id')) {
            return false;
        }
        if (!$node->hasAttribute('autocomplete')) {
            return false;
        }

        return str_ends_with($node->getAttribute('id'), $node->getAttribute('autocomplete'));
    }
}
