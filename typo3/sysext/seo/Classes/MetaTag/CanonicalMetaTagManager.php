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

namespace TYPO3\CMS\Seo\MetaTag;

use TYPO3\CMS\Core\Attribute\AsMetaTagManager;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerInterface;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Holds the canonical URL of the page and renders it as <link rel="canonical">.
 *
 * The URL is part of the meta tag state, which is stored in the page cache and
 * rendered after uncached content objects have been processed. This allows
 * uncached plugins to replace or remove the canonical URL via the property "canonical".
 *
 * @internal this class is not part of TYPO3's Core API, use the MetaTagManagerRegistry with the property "canonical".
 */
#[AsMetaTagManager(identifier: 'canonical')]
final class CanonicalMetaTagManager implements MetaTagManagerInterface
{
    private const PROPERTY = 'canonical';

    private string $url = '';

    /**
     * A page has a single canonical URL, so any added value replaces the previous one.
     */
    public function addProperty(string $property, string $content, array $subProperties = [], bool $replace = false, string $type = ''): void
    {
        if (!$this->canHandleProperty($property)) {
            throw new \UnexpectedValueException(
                sprintf('This MetaTagManager can\'t handle property "%s"', $property),
                1795073742
            );
        }
        $this->url = $content;
    }

    public function getProperty(string $property, string $type = ''): array
    {
        if (!$this->canHandleProperty($property) || $this->url === '') {
            return [];
        }
        return [['content' => $this->url, 'subProperties' => []]];
    }

    public function canHandleProperty(string $property): bool
    {
        return strtolower($property) === self::PROPERTY;
    }

    public function getAllHandledProperties(): array
    {
        return [self::PROPERTY => []];
    }

    public function renderAllProperties(?DocType $docType = null): string
    {
        return $this->renderProperty(self::PROPERTY, $docType);
    }

    public function renderProperty(string $property, ?DocType $docType = null): string
    {
        if (!$this->canHandleProperty($property) || $this->url === '') {
            return '';
        }
        return '<link ' . GeneralUtility::implodeAttributes([
            'rel' => 'canonical',
            'href' => $this->url,
        ], true) . ($docType?->isXmlCompliant() ? '/' : '') . '>';
    }

    public function removeProperty(string $property, string $type = ''): void
    {
        if ($this->canHandleProperty($property)) {
            $this->url = '';
        }
    }

    public function removeAllProperties(): void
    {
        $this->url = '';
    }

    public function getState(): array
    {
        return ['url' => $this->url];
    }

    public function updateState(array $state): void
    {
        $this->url = (string)($state['url'] ?? '');
    }
}
