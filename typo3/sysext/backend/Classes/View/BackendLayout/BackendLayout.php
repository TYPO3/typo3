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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\Grid;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridRow;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\LanguageColumn;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Backend\View\Drawing\BackendLayoutRenderer;
use TYPO3\CMS\Backend\View\Drawing\DrawingConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class to represent a backend layout.
 */
class BackendLayout
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $iconPath;

    /**
     * @var string
     */
    protected $configuration;

    /**
     * The structured data of the configuration represented as array.
     *
     * @var array
     */
    protected $structure = [];

    /**
     * @var array
     */
    protected $data;

    /**
     * @var DrawingConfiguration
     */
    protected $drawingConfiguration;

    /**
     * @var ContentFetcher
     */
    protected $contentFetcher;

    /**
     * @var LanguageColumn[]
     */
    protected $languageColumns = [];

    /**
     * @var RecordRememberer
     */
    protected $recordRememberer;

    /**
     * @param string $identifier
     * @param string $title
     * @param string|array $configuration
     * @return BackendLayout
     */
    public static function create($identifier, $title, $configuration)
    {
        return GeneralUtility::makeInstance(
            static::class,
            $identifier,
            $title,
            $configuration
        );
    }

    /**
     * @param string $identifier
     * @param string $title
     * @param string|array $configuration
     */
    public function __construct($identifier, $title, $configuration)
    {
        $this->drawingConfiguration = GeneralUtility::makeInstance(DrawingConfiguration::class);
        $this->contentFetcher = GeneralUtility::makeInstance(ContentFetcher::class, $this);
        $this->recordRememberer = GeneralUtility::makeInstance(RecordRememberer::class);
        $this->setIdentifier($identifier);
        $this->setTitle($title);
        if (is_array($configuration)) {
            $this->structure = $configuration;
            $this->configuration = $configuration['config'] ?? '';
        } else {
            $this->setConfiguration($configuration);
        }
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @throws \UnexpectedValueException
     */
    public function setIdentifier($identifier)
    {
        if (strpos($identifier, '__') !== false) {
            throw new \UnexpectedValueException(
                'Identifier "' . $identifier . '" must not contain "__"',
                1381597630
            );
        }

        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getIconPath()
    {
        return $this->iconPath;
    }

    /**
     * @param string $iconPath
     */
    public function setIconPath($iconPath)
    {
        $this->iconPath = $iconPath;
    }

    /**
     * @return string
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param string $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        $this->structure = GeneralUtility::makeInstance(BackendLayoutView::class)->parseStructure($this);
    }

    /**
     * Returns the columns registered for this layout as $key => $value pair where the key is the colPos
     * and the value is the title.
     * "1" => "Left" etc.
     * Please note that the title can contain LLL references ready for translation.
     * @return array
     */
    public function getUsedColumns(): array
    {
        return $this->structure['usedColumns'] ?? [];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function setStructure(array $structure)
    {
        $this->structure = $structure;
    }

    public function getStructure(): array
    {
        return $this->structure;
    }

    /**
     * @return LanguageColumn[]
     */
    public function getLanguageColumns(): iterable
    {
        if (empty($this->languageColumns)) {
            $availableLanguageColumns = $this->getDrawingConfiguration()->getLanguageColumns();
            foreach ($this->getDrawingConfiguration()->getSiteLanguages() as $siteLanguage) {
                if (!isset($availableLanguageColumns[$siteLanguage->getLanguageId()])) {
                    continue;
                }
                $backendLayout = clone $this;
                $backendLayout->getDrawingConfiguration()->setLanguageColumnsPointer($siteLanguage->getLanguageId());
                $this->languageColumns[] = GeneralUtility::makeInstance(LanguageColumn::class, $backendLayout, $siteLanguage);
            }
        }
        return $this->languageColumns;
    }

    public function getGrid(): Grid
    {
        $grid = GeneralUtility::makeInstance(Grid::class, $this);
        foreach ($this->structure['__config']['backend_layout.']['rows.'] ?? [] as $row) {
            $rowObject = GeneralUtility::makeInstance(GridRow::class, $this);
            foreach ($row['columns.'] as $column) {
                $columnObject = GeneralUtility::makeInstance(GridColumn::class, $this, $column);
                $rowObject->addColumn($columnObject);
            }
            $grid->addRow($rowObject);
        }
        $pageId = $this->drawingConfiguration->getPageId();
        $allowInconsistentLanguageHandling = (bool)(BackendUtility::getPagesTSconfig($pageId)['mod.']['web_layout.']['allowInconsistentLanguageHandling'] ?? false);
        if (!$allowInconsistentLanguageHandling && $this->getLanguageModeIdentifier() === 'connected') {
            $grid->setAllowNewContent(false);
        }
        return $grid;
    }

    public function getColumnPositionNumbers(): array
    {
        return $this->structure['__colPosList'];
    }

    public function getContentFetcher(): ContentFetcher
    {
        return $this->contentFetcher;
    }

    public function setContentFetcher(ContentFetcher $contentFetcher): void
    {
        $this->contentFetcher = $contentFetcher;
    }

    public function getDrawingConfiguration(): DrawingConfiguration
    {
        return $this->drawingConfiguration;
    }

    public function setDrawingConfiguration(DrawingConfiguration $drawingConfiguration): void
    {
        $this->drawingConfiguration = $drawingConfiguration;
    }

    public function getBackendLayoutRenderer(): BackendLayoutRenderer
    {
        return GeneralUtility::makeInstance(BackendLayoutRenderer::class, $this);
    }

    public function getRecordRememberer(): RecordRememberer
    {
        return $this->recordRememberer;
    }

    public function getLanguageModeIdentifier(): string
    {
        $contentRecordsPerColumn = $this->contentFetcher->getContentRecordsPerColumn(null, $this->drawingConfiguration->getLanguageColumnsPointer());
        $contentRecords = empty($contentRecordsPerColumn) ? [] : array_merge(...$contentRecordsPerColumn);
        $translationData = $this->contentFetcher->getTranslationData($contentRecords, $this->drawingConfiguration->getLanguageColumnsPointer());
        return $translationData['mode'] ?? '';
    }

    public function __clone()
    {
        $this->drawingConfiguration = clone $this->drawingConfiguration;
        $this->contentFetcher->setBackendLayout($this);
    }
}
