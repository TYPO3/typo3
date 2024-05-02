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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Used to hold and prepare data for form testing purpose.
 * @see \TYPO3\CMS\Form\Tests\Functional\RequestHandling\RequestHandlingTest
 */
final class FormData
{
    private array $with = [];
    private array $withNoPrefix = [];
    private array $without = [];
    private array $withoutNoPrefix = [];
    private bool $withChash = true;

    public function __construct(private array $formData) {}

    public function with(string $identifier, string $value): FormData
    {
        $this->with[$identifier] = $value;
        return $this;
    }

    public function withNoPrefix(string $identifier, string $value): FormData
    {
        $this->withNoPrefix[$identifier] = $value;
        return $this;
    }

    public function without(string $identifier): FormData
    {
        $this->without[$identifier] = $identifier;
        return $this;
    }

    public function withoutNoPrefix(string $identifier): FormData
    {
        $this->withoutNoPrefix[$identifier] = $identifier;
        return $this;
    }

    public function withChash(bool $withChash): FormData
    {
        $this->withChash = $withChash;
        return $this;
    }

    public function toPostRequest(InternalRequest $request): InternalRequest
    {
        $parsedBody = [];
        $postStructure = $this->getPostStructure();
        parse_str($postStructure, $parsedBody);
        $request->getBody()->write($postStructure);
        return $request
                  ->withMethod('POST')
                  ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                  ->withQueryParameters($this->getQueryStructure())
                  ->withParsedBody($parsedBody);
    }

    public function toGetRequest(InternalRequest $request, bool $withPostData = true): InternalRequest
    {
        $postStructure = [];
        if ($withPostData) {
            foreach (explode('&', urldecode($this->getPostStructure())) as $queryPart) {
                [$key, $value] = explode('=', $queryPart, 2);
                $postStructure[$key] = $value;
            }
        }
        $queryParameters = array_replace_recursive(
            $this->getQueryStructure(),
            $postStructure
        );

        $request->getBody()->write($this->getPostStructure());
        return $request
                  ->withMethod('GET')
                  ->withQueryParameters($queryParameters);
    }

    public function toArray(): array
    {
        return $this->formData;
    }

    public function getFormMarkup(): string
    {
        return $this->formData['DOMDocument']->saveHTML();
    }

    public function getHoneypotId(): ?string
    {
        return array_values(
            array_filter(
                $this->formData['elementData'],
                fn($elementData) => $elementData['__isHoneypot']
            )
        )[0]['data-id'] ?? null;
    }

    public function getSessionId(): ?string
    {
        return array_values(
            array_filter(
                $this->formData['elementData'],
                fn($elementData) => str_ends_with($elementData['name'], '[__session]')
            )
        )[0]['value'] ?? null;
    }

    private function getQueryStructure(): array
    {
        $queryStructure = [];
        $actionQueryData = $this->formData['actionQueryData'];
        if ($this->withChash === false) {
            unset($actionQueryData['cHash']);
        }
        $actionQuery = http_build_query($actionQueryData);

        foreach (explode('&', urldecode($actionQuery)) as $queryPart) {
            [$key, $value] = explode('=', $queryPart, 2);
            $queryStructure[$key] = $value;
        }

        return $queryStructure;
    }

    private function getPostStructure(): string
    {
        $dataPrefix = '';
        $postStructure = [];
        foreach ($this->formData['elementData'] as $elementData) {
            $nameStruct = [];
            parse_str(sprintf('%s=%s', $elementData['name'], $elementData['value'] ?? ''), $nameStruct);
            $postStructure = array_replace_recursive($postStructure, $nameStruct);

            if (str_ends_with($elementData['name'], '[__state]')) {
                $prefix = key(ArrayUtility::flatten($nameStruct));
                $prefixItems = explode('.', $prefix);
                array_pop($prefixItems);
                $dataPrefix = implode('.', $prefixItems) . '.';
            }
        }

        foreach ($this->with as $identifier => $value) {
            $postStructure = ArrayUtility::setValueByPath($postStructure, $dataPrefix . $identifier, $value, '.');
        }

        foreach ($this->without as $identifier) {
            $postStructure = ArrayUtility::removeByPath($postStructure, $dataPrefix . $identifier, '.');
        }

        $postStructure = array_replace_recursive(
            $postStructure,
            $this->withNoPrefix
        );

        foreach ($this->withoutNoPrefix as $identifier) {
            unset($postStructure[$identifier]);
        }

        return http_build_query($postStructure);
    }
}
