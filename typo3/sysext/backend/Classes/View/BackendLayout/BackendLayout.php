<?php

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

namespace TYPO3\CMS\Backend\View\BackendLayout;

use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class to represent a backend layout.
 *
 * # @deprecated: put the following line in TYPO3 v14.0, see #104099 and #103365
 * # declare(strict_types=1);
 */
class BackendLayout
{
    protected string $identifier;
    protected string $title;
    protected string $description = '';
    protected string $iconPath = '';
    protected string $configuration = '';

    /**
     * The structured data of the configuration represented as array.
     */
    protected array $structure = [];
    protected array $data = [];

    public static function create(string $identifier, string $title, string|array $configuration): BackendLayout
    {
        return GeneralUtility::makeInstance(
            static::class,
            $identifier,
            $title,
            $configuration
        );
    }

    public function __construct(string $identifier, string $title, string|array $configuration)
    {
        $this->setIdentifier($identifier);
        $this->setTitle($title);
        if (is_array($configuration)) {
            $this->structure = $configuration;
            $this->configuration = $configuration['config'] ?? '';
        } else {
            $this->setConfiguration($configuration);
        }
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getIdentifierCleaned(): string
    {
        return strtolower((string)preg_replace('/[^a-zA-Z0-9_-]/', '', $this->identifier));
    }

    public function setIdentifier(string $identifier): void
    {
        if (str_contains($identifier, '__')) {
            throw new \UnexpectedValueException(
                'Identifier "' . $identifier . '" must not contain "__"',
                1381597630
            );
        }

        $this->identifier = $identifier;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getIconPath(): string
    {
        return $this->iconPath;
    }

    public function setIconPath(string $iconPath): void
    {
        $this->iconPath = $iconPath;
    }

    public function getConfiguration(): string
    {
        return $this->configuration;
    }

    public function setConfiguration(string $configuration): void
    {
        $this->configuration = $configuration;
        $this->structure = GeneralUtility::makeInstance(BackendLayoutView::class)->parseStructure($this);
    }

    /**
     * Returns the columns registered for this layout as $key => $value pair where the key is the colPos
     * and the value is the title.
     * "1" => "Left" etc.
     * Please note that the title can contain LLL references ready for translation.
     */
    public function getUsedColumns(): array
    {
        return $this->structure['usedColumns'] ?? [];
    }

    public function getColCount(): int
    {
        return $this->structure['colCount'] ?? 0;
    }

    public function getRowCount(): int
    {
        return $this->structure['rowCount'] ?? 0;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function setStructure(array $structure): void
    {
        $this->structure = $structure;
    }

    public function getStructure(): array
    {
        return $this->structure;
    }

    /**
     * @return string[]
     */
    public function getColumnPositionNumbers(): array
    {
        return $this->structure['__colPosList'] ?? [];
    }
}
