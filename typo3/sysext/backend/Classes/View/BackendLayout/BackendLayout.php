<?php
namespace TYPO3\CMS\Backend\View\BackendLayout;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\Grid;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridRow;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\LanguageColumn;
use TYPO3\CMS\Backend\View\Drawing\BackendLayoutRenderer;
use TYPO3\CMS\Backend\View\Drawing\DrawingConfiguration;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
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
     * @var array
     */
    protected $configurationArray;

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
     * @var LanguageColumn
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
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
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
            $this->setConfigurationArray($configuration);
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
     * @param array $configurationArray
     */
    public function setConfigurationArray(array $configurationArray): void
    {
        if (!isset($configurationArray['__colPosList'], $configurationArray['__items'])) {
            // Backend layout configuration is unprocessed, process it now to extract counts and column item lists
            $colPosList = [];
            $items = [];
            $rowIndex = 0;
            foreach ($configurationArray['backend_layout.']['rows.'] as $row) {
                $index = 0;
                $colCount = 0;
                $columns = [];
                foreach ($row['columns.'] as $column) {
                    if (!isset($column['colPos'])) {
                        continue;
                    }
                    $colPos = $column['colPos'];
                    $colPos = (int)$colPos;
                    $colPosList[$colPos] = $colPos;
                    $key = ($index + 1) . '.';
                    $columns[$key] = $column;
                    $items[$colPos] = [
                        (string)$this->getLanguageService()->sL($column['name']),
                        $colPos,
                        $column['icon']
                    ];
                    $colCount += $column['colspan'] ? $column['colspan'] : 1;
                    ++ $index;
                }
                ++ $rowIndex;
            }

            $configurationArray['__config'] = $configurationArray;
            $configurationArray['__colPosList'] = $colPosList;
            $configurationArray['__items'] = $items;
        }
        $this->configurationArray = $configurationArray;
    }

    /**
     * @return array
     */
    public function getConfigurationArray(): array
    {
        return $this->configurationArray;
    }

    /**
     * @param string $configuration
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
        $this->parseConfigurationStringAndSetConfigurationArray($configuration);
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

    /**
     * @return LanguageColumn[]
     */
    public function getLanguageColumns(): iterable
    {
        if (empty($this->languageColumns)) {
            $defaultLanguageElements = [];
            $contentByColumn = $this->getContentFetcher()->getContentRecordsPerColumn(null, 0);
            if (!empty($contentByColumn)) {
                $defaultLanguageElements = array_merge(...$contentByColumn);
            }
            foreach ($this->getDrawingConfiguration()->getSiteLanguages() as $siteLanguage) {
                if (!in_array($siteLanguage->getLanguageId(), $this->getDrawingConfiguration()->getLanguageColumns())) {
                    continue;
                }
                $backendLayout = clone $this;
                $backendLayout->getDrawingConfiguration()->setLanguageColumnsPointer($siteLanguage->getLanguageId());
                $this->languageColumns[] = GeneralUtility::makeInstance(LanguageColumn::class, $backendLayout, $siteLanguage, $defaultLanguageElements);
            }
        }
        return $this->languageColumns;
    }

    public function getGrid(): Grid
    {
        $grid = GeneralUtility::makeInstance(Grid::class, $this);
        foreach ($this->getConfigurationArray()['__config']['backend_layout.']['rows.'] as $row) {
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
        return $this->getConfigurationArray()['__colPosList'];
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

    protected function parseConfigurationStringAndSetConfigurationArray(string $configuration): void
    {
        $parser = GeneralUtility::makeInstance(TypoScriptParser::class);
        $conditionMatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher::class);
        $parser->parse(TypoScriptParser::checkIncludeLines($configuration), $conditionMatcher);
        $this->setConfigurationArray($parser->setup);
    }

    public function __clone()
    {
        $this->drawingConfiguration = clone $this->drawingConfiguration;
        $this->contentFetcher->setBackendLayout($this);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
