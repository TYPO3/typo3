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

namespace TYPO3\CMS\Core\Configuration;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
class CKEditor5Migrator
{
    /**
     * Main groups in CKEditor4 contain subgroups.
     * These groups are expanded during migration.
     */
    private const TOOLBAR_MAIN_GROUPS_MAP = [
        'document' => ['mode', 'document', 'doctools'],
        'clipboard' => ['clipboard', 'undo'],
        'editing' => ['find', 'selection', 'spellchecker', 'editing'],
        'forms' => ['forms'],
        'basicstyles' => ['basicstyles', 'cleanup'],
        'paragraph' => ['list', 'indent', 'blocks', 'align', 'bidi', 'paragraph'],
        'links' => ['links'],
        'insert' => ['insert'],
        'styles' => ['styles'],
        'colors' => ['colors'],
        'tools' => ['tools'],
        'others' => ['others'],
        'about' => ['about'],
        'blocks' => ['blocks'],
        'table' => ['table'],
        'tabletools' => [],
    ];

    /**
     * Groups in CKEditor4 contain buttons.
     */
    private const TOOLBAR_GROUPS_MAP = [
        'mode' => ['Source'],
        'document' => ['Save', 'NewPage', 'Preview', 'Print'],
        'doctools' => ['Templates'],
        'clipboard' => ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord'],
        'undo' => ['Undo', 'Redo'],
        'find' => ['Find', 'Replace'],
        'selection' => ['SelectAll'],
        'spellchecker' => ['Scayt'],
        'forms' => ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'],
        'basicstyles' => ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', 'SoftHyphen'],
        'cleanup' => ['CopyFormatting', 'RemoveFormat'],
        'list' => ['NumberedList', 'BulletedList'],
        'indent' => ['Indent', 'Outdent'],
        'blocks' => ['Blockquote', 'CreateDiv'],
        'align' => ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
        'bidi' => ['BidiLtr', 'BidiRtl', 'Language'],
        'links' => ['Link', 'Unlink', 'Anchor'],
        'insert' => ['Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe'],
        'styles' => ['Styles', 'Format', 'Font', 'FontSize'],
        'format' => ['Format'],
        'table' => ['Table'],
        'specialchar' => ['SpecialChar'],
        'colors' => ['TextColor', 'BGColor'],
        'tools' => ['Maximize', 'ShowBlocks'],
        'about' => ['About'],
        'others' => [],
    ];

    // List of "old" button names vs the replacement(s)
    private const BUTTON_MAP = [
        // mode
        'Source' => 'sourceEditing',
        // document
        'Save' => null,
        'NewPage' => null,
        'Preview' => null,
        'Print' => null,
        // doctools
        'Templates' => null,
        // clipboard
        'Cut' => null,
        'Copy' => null,
        'Paste' => null,
        'PasteText' => null,
        'PasteFromWord' => null,
        // undo
        'Undo' => 'undo',
        'Redo' => 'redo',
        // find
        'Find' => null,
        'Replace' => 'findAndReplace',
        // selection
        'SelectAll' => 'selectAll',
        // spellchecker
        'Scayt' => null,
        // forms
        'Form' => null,
        'Checkbox' => null,
        'Radio' => null,
        'TextField' => null,
        'Textarea' => null,
        'Select' => null,
        'Button' => null,
        'ImageButton' => null,
        'HiddenField' => null,
        // basicstyles
        'Bold' => 'bold',
        'Italic' => 'italic',
        'Underline' => 'underline',
        'Strike' => 'strikethrough',
        'Subscript' => 'subscript',
        'Superscript' => 'superscript',
        // cleanup
        'CopyFormatting' => null,
        'RemoveFormat' => 'removeFormat',
        // list
        'NumberedList' => 'numberedList',
        'BulletedList' => 'bulletedList',
        // indent
        'Outdent' => 'outdent',
        'Indent' => 'indent',
        // blocks
        'Blockquote' => 'blockQuote',
        'CreateDiv' => null,
        // align
        'JustifyLeft' => 'alignment:left',
        'JustifyCenter' => 'alignment:center',
        'JustifyRight' => 'alignment:right',
        'JustifyBlock' => 'alignment:justify',
        // bidi
        'BidiLtr' => null,
        'BidiRtl' => null,
        'Language' => 'textPartLanguage',
        // links
        'Link' => 'link',
        'Unlink' => null,
        'Anchor' => null,
        // insert
        'Image' => 'insertImage',
        'Flash' => null,
        'Table' => 'insertTable',
        'HorizontalRule' => 'horizontalLine',
        'Smiley' => null,
        'SpecialChar' => 'specialCharacters',
        'PageBreak' => 'pageBreak',
        'Iframe' => null,
        // styles
        'Styles' => 'style',
        'Format' => 'heading',
        'Font' => null,
        'FontSize' => null,
        // colors
        'TextColor' => null,
        'BGColor' => null,
        // tools
        'Maximize' => null,
        'ShowBlocks' => null,
        // about
        'About' => null,
        // typo3
        'SoftHyphen' => 'softhyphen',
    ];

    /**
     * Mapping of plugins
     */
    private const PLUGIN_MAP = [
        'image' => 'Image',
        'alignment' => 'Alignment',
        'justify' => 'Alignment',
        'softhyphen' => 'Whitespace',
        'whitespace' => 'Whitespace',
        'wordcount' => 'WordCount',
    ];

    /**
     * @param array $configuration Richtext configuration
     */
    public function __construct(protected array $configuration)
    {
        if (isset($this->configuration['editor']['config'])) {
            $this->migrateRemovePlugins();
            $this->migrateToolbar();
            $this->migrateRemoveButtonsFromToolbar();
            $this->migrateFormatTagsToHeadings();
            $this->migrateStylesSetToStyleDefinitions();
            $this->migrateContentsCssToArray();
            // configure plugins
            $this->handleAlignmentPlugin();
            $this->handleWhitespacePlugin();
            $this->handleWordCountPlugin();

            // sort by key
            ksort($this->configuration['editor']['config']);
        }

        if (isset($this->configuration['buttons']['link'])) {
            $this->addLinkClassesToStyleSets();
        }
    }

    public function get(): array
    {
        return $this->configuration;
    }

    protected function migrateRemovePlugins(): void
    {
        if (!isset($this->configuration['editor']['config']['removePlugins'])) {
            $this->configuration['editor']['config']['removePlugins'] = [];
            return;
        }

        // Handle custom plugin names to ckeditor
        $this->configuration['editor']['config']['removePlugins'] = array_map(static function (string $entry): string {
            if (isset(self::PLUGIN_MAP[$entry])) {
                return self::PLUGIN_MAP[$entry];
            }
            return $entry;
        }, $this->configuration['editor']['config']['removePlugins']);

        $this->configuration['editor']['config']['removePlugins'] = $this->getUniqueArrayValues($this->configuration['editor']['config']['removePlugins']);
    }

    /**
     * CE4: https://ckeditor.com/latest/samples/toolbarconfigurator/index.html#basic
     * CE5: https://ckeditor.com/docs/ckeditor5/latest/features/toolbar/toolbar.html#extended-toolbar-configuration-format
     */
    protected function migrateToolbar(): void
    {
        /**
         * Collection of the final toolbar configuration
         * @var array{items: string[], removeItems: string[], shouldNotGroupWhenFull: bool} $toolbar
         */
        $toolbar = [
            'items' => [],
            'removeItems' => $this->configuration['editor']['config']['toolbar']['removeItems'] ?? [],
            'shouldNotGroupWhenFull' => $this->configuration['editor']['config']['toolbar']['shouldNotGroupWhenFull'] ?? true,
        ];

        // Migrate CKEditor4 toolbarGroups
        // There can only be one configuration at a time, if 'toolbarGroups' is set
        // we prefer this definition above the toolbar definition.
        // https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-toolbarGroups
        if (is_array($this->configuration['editor']['config']['toolbarGroups'] ?? null)) {
            $toolbar['items'] = $this->configuration['editor']['config']['toolbarGroups'];
            unset($this->configuration['editor']['config']['toolbar'], $this->configuration['editor']['config']['toolbarGroups']);
        }

        // Migrate CKEditor4 toolbar templates
        // Resolve toolbar template and override current toolbar
        // https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-toolbar
        if (is_string($this->configuration['editor']['config']['toolbar'] ?? null)) {
            $toolbarName = 'toolbar_' . trim($this->configuration['editor']['config']['toolbar']);
            if (is_array($this->configuration['editor']['config'][$toolbarName] ?? null)) {
                $toolbar['items'] = $this->configuration['editor']['config'][$toolbarName];
                unset($this->configuration['editor']['config']['toolbar'], $this->configuration['editor']['config'][$toolbarName]);
            }
        }

        // Collect toolbar items
        if (is_array($this->configuration['editor']['config']['toolbar'] ?? null)) {
            $toolbar['items'] = $this->configuration['editor']['config']['toolbar']['items'] ?? $this->configuration['editor']['config']['toolbar'];
        }

        $toolbar['items'] = $this->migrateToolbarItems($toolbar['items']);
        $this->configuration['editor']['config']['toolbar'] = $toolbar;
    }

    protected function migrateToolbarItems(array $items): array
    {
        $toolbarItems = [];
        foreach ($items as $item) {
            if (is_string($item)) {
                $toolbarItems[] = $this->migrateToolbarButton($item);
                continue;
            }
            if (is_array($item)) {
                // Expand CKEditor4 preset toolbar groups
                if (is_string($item['name'] ?? null) && count($item) === 1 && isset(self::TOOLBAR_MAIN_GROUPS_MAP[$item['name']])) {
                    $item['groups'] = self::TOOLBAR_MAIN_GROUPS_MAP[$item['name']];
                }
                // Flatten CKEditor4 arrays that only have strings assigned
                if (count($item) === count(array_filter($item, static fn (mixed $value): bool => is_string($value)))) {
                    $migratedToolbarItems = $item;
                    $migratedToolbarItems = $this->migrateToolbarButtons($migratedToolbarItems);
                    $migratedToolbarItems = $this->migrateToolbarSpacers($migratedToolbarItems);
                    array_push($toolbarItems, ...$migratedToolbarItems);
                    $toolbarItems[] = '|';
                    continue;
                }
                // Flatten CKEditor4 named groups
                if (is_string($item['name'] ?? null) && is_array($item['items'] ?? null)) {
                    $migratedToolbarItems = $item['items'];
                    $migratedToolbarItems = $this->migrateToolbarButtons($migratedToolbarItems);
                    $migratedToolbarItems = $this->migrateToolbarSpacers($migratedToolbarItems);
                    array_push($toolbarItems, ...$migratedToolbarItems);
                    $toolbarItems[] = '|';
                    continue;
                }
                // Expand CKEditor4 toolbar groups
                if (is_string($item['name'] ?? null) && is_array($item['groups'] ?? null)) {
                    $itemGroups = array_filter($item['groups'], static fn (mixed $itemGroup): bool => is_string($itemGroup));

                    // Process Main CKEditor4 Groups
                    $unGroupedToolbarItems = [];
                    foreach ($itemGroups as $itemGroup) {
                        if (isset(self::TOOLBAR_MAIN_GROUPS_MAP[$itemGroup])) {
                            array_push($unGroupedToolbarItems, ...self::TOOLBAR_MAIN_GROUPS_MAP[$itemGroup]);
                            $unGroupedToolbarItems[] = '|';
                            continue;
                        }
                        $unGroupedToolbarItems[] = $itemGroup;
                    }

                    // Process CKEditor4 Groups
                    $groupedToolbarItems = [];
                    foreach ($itemGroups as $itemGroup) {
                        if (isset(self::TOOLBAR_GROUPS_MAP[$itemGroup])) {
                            array_push($groupedToolbarItems, ...self::TOOLBAR_GROUPS_MAP[$itemGroup]);
                            $groupedToolbarItems[] = '|';
                            continue;
                        }
                        $groupedToolbarItems[] = $itemGroup;
                    }

                    $migratedToolbarItems = $groupedToolbarItems;
                    $migratedToolbarItems = $this->migrateToolbarButtons($migratedToolbarItems);
                    $migratedToolbarItems = $this->migrateToolbarSpacers($migratedToolbarItems);
                    array_push($toolbarItems, ...$migratedToolbarItems);
                    $toolbarItems[] = '|';
                    continue;
                }

                $toolbarItems[] = $item;
            }
        }

        $toolbarItems = $this->migrateToolbarLinebreaks($toolbarItems);
        $toolbarItems = $this->migrateToolbarCleanup($toolbarItems);

        return array_values($toolbarItems);
    }

    protected function migrateToolbarButton(string $buttonName): ?string
    {
        if (array_key_exists($buttonName, self::BUTTON_MAP)) {
            return self::BUTTON_MAP[$buttonName];
        }
        return $buttonName;
    }

    protected function migrateToolbarButtons(array $toolbarItems): array
    {
        $processedItems = [];
        foreach ($toolbarItems as $toolbarItem) {
            if (is_string($toolbarItem)) {
                if (($toolbarItem = $this->migrateToolbarButton($toolbarItem)) !== null) {
                    $processedItems[] = $this->migrateToolbarButton($toolbarItem);
                }
            } else {
                $processedItems[] = $toolbarItem;
            }
        }

        return $processedItems;
    }

    protected function migrateToolbarSpacers(array $toolbarItems): array
    {
        $processedItems = [];
        foreach ($toolbarItems as $toolbarItem) {
            if (is_string($toolbarItem)) {
                $toolbarItem = str_replace('-', '|', $toolbarItem);
            }
            $processedItems[] = $toolbarItem;
        }

        return $processedItems;
    }

    protected function migrateToolbarLinebreaks(array $toolbarItems): array
    {
        $processedItems = [];
        foreach ($toolbarItems as $toolbarItem) {
            if (is_string($toolbarItem)) {
                $toolbarItem = str_replace('/', '-', $toolbarItem);
            }
            $processedItems[] = $toolbarItem;
        }

        return $processedItems;
    }

    protected function migrateToolbarCleanup(array $toolbarItems): array
    {
        // Ensure buttons are only added once to the toolbar.
        $searchValues = [];
        foreach ($toolbarItems as $toolbarKey => $toolbarItem) {
            if (is_string($toolbarItem) && !in_array($toolbarItem, ['|', '-'])) {
                if (array_key_exists($toolbarItem, $searchValues)) {
                    unset($toolbarItems[$toolbarKey]);
                } else {
                    $searchValues[$toolbarItem] = true;
                }
            }
        }

        $previousItem = null;
        $previousKey = null;
        foreach ($toolbarItems as $toolbarKey => $toolbarItem) {
            if ($previousItem === null && ($toolbarItem === '|' || $toolbarItem === '-')) {
                unset($toolbarItems[$toolbarKey]);
                continue;
            }

            if ($previousItem === '|' && ($toolbarItem === '|' || $toolbarItem === '-')) {
                unset($toolbarItems[$previousKey]);
            }

            $previousKey = $toolbarKey;
            $previousItem = $toolbarItem;
        }

        $lastToolbarItem = array_slice($toolbarItems, -1, 1);
        if ($lastToolbarItem === ['-'] || $lastToolbarItem === ['|']) {
            array_pop($toolbarItems);
        }

        return array_values($toolbarItems);
    }

    protected function migrateRemoveButtonsFromToolbar(): void
    {
        if (!isset($this->configuration['editor']['config']['removeButtons'])) {
            return;
        }

        if (is_string($this->configuration['editor']['config']['removeButtons'])) {
            $this->configuration['editor']['config']['removeButtons'] = GeneralUtility::trimExplode(
                ',',
                $this->configuration['editor']['config']['removeButtons'],
                true
            );
        }

        $removeItems = [];
        foreach ($this->configuration['editor']['config']['removeButtons'] as $buttonName) {
            if (array_key_exists($buttonName, self::BUTTON_MAP)) {
                if (self::BUTTON_MAP[$buttonName] !== null) {
                    $removeItems[] = self::BUTTON_MAP[$buttonName];
                }
            } else {
                $removeItems[] = $buttonName;
            }
        }

        foreach ($removeItems as $name) {
            $this->removeToolbarItem($name);
        }

        // Cleanup final configuration after migration
        unset($this->configuration['editor']['config']['removeButtons']);
    }

    protected function migrateFormatTagsToHeadings(): void
    {
        // new definition is in place, no migration is done
        if (isset($this->configuration['editor']['config']['heading']['options'])) {
            // discard legacy configuration if new configuration exists
            unset($this->configuration['editor']['config']['format_tags']);
            return;
        }
        // migrate format_tags to custom buttons
        if (isset($this->configuration['editor']['config']['format_tags'])) {
            $formatTags = explode(';', $this->configuration['editor']['config']['format_tags']);
            $allowedHeadings = [];
            foreach ($formatTags as $paragraphTag) {
                switch (strtolower($paragraphTag)) {
                    case 'p':
                        $allowedHeadings[] = [
                            'model' => 'paragraph',
                            'title' => 'Paragraph',
                        ];
                        break;
                    case 'h1':
                    case 'h2':
                    case 'h3':
                    case 'h4':
                    case 'h5':
                    case 'h6':
                        $headingNumber = substr($paragraphTag, -1);
                        $allowedHeadings[] = [
                            'model' => 'heading' . $headingNumber,
                            'view' => 'h' . $headingNumber,
                            'title' => 'Heading ' . $headingNumber,
                        ];
                        break;
                    case 'pre':
                        $allowedHeadings[] = [
                            'model' => 'formatted',
                            'view' => 'pre',
                            'title' => 'Formatted',
                        ];
                }
            }

            // remove legacy configuration after migration
            unset($this->configuration['editor']['config']['format_tags']);
            $this->configuration['editor']['config']['heading']['options'] = $allowedHeadings;
        }
    }

    protected function migrateStylesSetToStyleDefinitions(): void
    {
        // new definition is in place, no migration is done
        if (isset($this->configuration['editor']['config']['style']['definitions'])) {
            // discard legacy configuration if new configuration exists
            unset($this->configuration['editor']['config']['stylesSet']);
            return;
        }
        // Migrate 'stylesSet' to 'styles' => 'definitions'
        if (isset($this->configuration['editor']['config']['stylesSet'])) {
            $styleDefinitions = [];
            foreach ($this->configuration['editor']['config']['stylesSet'] as $styleSet) {
                if (!isset($styleSet['name'], $styleSet['element'])) {
                    // @todo: log
                    continue;
                }
                $class = $styleSet['attributes']['class'] ?? null;
                $definition = [
                    'name' => $styleSet['name'],
                    'element' => $styleSet['element'],
                    'classes' => [''],
                ];
                if ($class) {
                    $definition['classes'] = explode(' ', $class);
                }
                $styleDefinitions[] = $definition;
            }

            // remove legacy configuration after migration
            unset($this->configuration['editor']['config']['stylesSet']);
            $this->configuration['editor']['config']['style']['definitions'] = $styleDefinitions;
        }
    }

    protected function migrateContentsCssToArray(): void
    {
        if (isset($this->configuration['editor']['config']['contentsCss'])) {
            if (!is_array($this->configuration['editor']['config']['contentsCss'])) {
                if (empty($this->configuration['editor']['config']['contentsCss'])) {
                    unset($this->configuration['editor']['config']['contentsCss']);
                    return;
                }
                $this->configuration['editor']['config']['contentsCss'] = (array)$this->configuration['editor']['config']['contentsCss'];
            }

            $this->configuration['editor']['config']['contentsCss'] = array_map(static function (mixed $styleSrc): mixed {
                // Trim values, if input is a string, otherwise leave as-is (will be filtered out)
                return is_string($styleSrc) ? trim($styleSrc) : $styleSrc;
            }, $this->configuration['editor']['config']['contentsCss']);
            $this->configuration['editor']['config']['contentsCss'] = array_values(
                array_filter($this->configuration['editor']['config']['contentsCss'], static function (mixed $styleSrc): bool {
                    // We care for non-empty strings only
                    return is_string($styleSrc) && $styleSrc !== '';
                })
            );
        }
    }

    protected function handleAlignmentPlugin(): void
    {
        // Migrate legacy configuration
        // https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-justifyClasses
        if (isset($this->configuration['editor']['config']['justifyClasses'])) {
            if (!isset($this->configuration['editor']['config']['alignment'])) {
                $legacyConfig = $this->configuration['editor']['config']['justifyClasses'];
                $indexMap = [
                    0 => 'left',
                    1 => 'center',
                    2 => 'right',
                    3 => 'justify',
                ];
                foreach ($legacyConfig as $index => $class) {
                    $itemConfig = [];
                    if (isset($indexMap[$index])) {
                        $itemConfig['name'] = $indexMap[$index];
                    }
                    $itemConfig['className'] = $class;
                    $this->configuration['editor']['config']['alignment']['options'][] = $itemConfig;
                }
            }
            unset($this->configuration['editor']['config']['justifyClasses']);
        }
        $this->removeExtraPlugin('justify');

        // Remove related configuration if plugin should not be loaded
        if (array_search('Alignment', $this->configuration['editor']['config']['removePlugins']) !== false) {
            // Remove all related plugins
            $this->removePlugin('Alignment');

            // Remove toolbar items
            $this->removeToolbarItem('alignment');
            $this->removeToolbarItem('alignment:left');
            $this->removeToolbarItem('alignment:right');
            $this->removeToolbarItem('alignment:center');
            $this->removeToolbarItem('alignment:justify');

            // Remove config
            if (isset($this->configuration['editor']['config']['alignment'])) {
                unset($this->configuration['editor']['config']['alignment']);
            }

            return;
        }

        if (is_array($this->configuration['editor']['config']['alignment']['options'] ?? null)) {
            $classMap = [];
            foreach ($this->configuration['editor']['config']['alignment']['options'] as $option) {
                if (is_string($option['name'] ?? null)
                    && is_string($option['className'] ?? null)
                    && in_array($option['name'], ['left', 'center', 'right', 'justify'])) {
                    $classMap[$option['name']] = $option['className'];
                }
            }
        }

        // Default config
        $this->configuration['editor']['config']['alignment'] = [
            'options' => [
                ['name' => 'left', 'className' => $classMap['left'] ?? 'text-start'],
                ['name' => 'center', 'className' => $classMap['center'] ?? 'text-center'],
                ['name' => 'right', 'className' => $classMap['right'] ?? 'text-end'],
                ['name' => 'justify', 'className' => $classMap['justify'] ?? 'text-justify'],
            ],
        ];
    }

    protected function handleWhitespacePlugin(): void
    {
        // Remove related configuration if plugin should not be loaded
        if (in_array('Whitespace', $this->configuration['editor']['config']['removePlugins'], true)) {
            // Remove all related plugins
            $this->removePlugin('Whitespace');

            // Remove toolbar items
            $this->removeToolbarItem('softhyphen');

            return;
        }

        // Add button if missing
        if (!in_array('softhyphen', $this->configuration['editor']['config']['toolbar']['items'], true)) {
            $this->configuration['editor']['config']['toolbar']['items'][] = 'softhyphen';
        }
    }

    protected function handleWordCountPlugin(): void
    {
        // Migrate legacy configuration
        //
        // CKEditor4 used `wordcount` (lowercase), which is `wordCount` in CKEditor5.
        // The amount of properties has been reduced.
        //
        // see https://ckeditor.com/docs/ckeditor5/latest/features/word-count.html
        if (isset($this->configuration['editor']['config']['wordcount'])) {
            if (!isset($this->configuration['editor']['config']['wordCount'])) {
                $legacyConfig = $this->configuration['editor']['config']['wordcount'];
                if (isset($legacyConfig['showCharCount'])) {
                    $this->configuration['editor']['config']['wordCount']['displayCharacters'] = !empty($legacyConfig['showCharCount']);
                }
                if (isset($legacyConfig['showWordCount'])) {
                    $this->configuration['editor']['config']['wordCount']['displayWords'] = !empty($legacyConfig['showWordCount']);
                }
            }
            unset($this->configuration['editor']['config']['wordcount']);
        }

        // Remove related configuration if plugin should not be loaded
        if (in_array('WordCount', $this->configuration['editor']['config']['removePlugins'], true)) {
            // Remove all related plugins
            $this->removePlugin('WordCount');

            // Remove config
            if (isset($this->configuration['editor']['config']['wordCount'])) {
                unset($this->configuration['editor']['config']['wordCount']);
            }

            return;
        }

        // Default config
        $this->configuration['editor']['config']['wordCount'] = [
            'displayCharacters' => $this->configuration['editor']['config']['wordCount']['displayCharacters'] ?? true,
            'displayWords' => $this->configuration['editor']['config']['wordCount']['displayWords'] ?? true,
        ];
    }

    protected function addLinkClassesToStyleSets(): void
    {
        if (!isset($this->configuration['buttons']['link']['properties']['class']['allowedClasses'])) {
            return;
        }

        // Ensure editor.config.style.definitions exists
        $this->configuration['editor']['config']['style']['definitions'] ??= [];

        $allowedClasses = is_array($this->configuration['buttons']['link']['properties']['class']['allowedClasses'])
            ? $this->configuration['buttons']['link']['properties']['class']['allowedClasses']
            : GeneralUtility::trimExplode(',', $this->configuration['buttons']['link']['properties']['class']['allowedClasses'], true);

        // Determine index where link classes should be added at to keep styles grouped
        $indexToInsertElementsAt = array_key_last($this->configuration['editor']['config']['style']['definitions']) + 1;
        foreach ($this->configuration['editor']['config']['style']['definitions'] as $index => $styleSetDefinition) {
            if ($styleSetDefinition['element'] === 'a') {
                $indexToInsertElementsAt = $index + 1;
            }
        }

        foreach ($allowedClasses as $allowedClass) {
            foreach ($this->configuration['editor']['config']['style']['definitions'] as $styleSetDefinition) {
                if ($styleSetDefinition['element'] === 'a' && $styleSetDefinition['classes'] === [$allowedClass]) {
                    // allowedClass is already configured, continue with next one
                    continue 2;
                }
            }

            // We're still here, this means $allowedClass wasn't found
            array_splice($this->configuration['editor']['config']['style']['definitions'], $indexToInsertElementsAt, 0, [[
                'classes' => [$allowedClass],
                'element' => 'a',
                'name' => $allowedClass, // we lack a human-readable name here...
            ]]);
            $indexToInsertElementsAt++;
        }
    }

    private function removeToolbarItem(string $name): void
    {
        $this->configuration['editor']['config']['toolbar']['removeItems'][] = $name;
        $this->configuration['editor']['config']['toolbar']['removeItems'] = $this->getUniqueArrayValues($this->configuration['editor']['config']['toolbar']['removeItems']);
    }

    private function removePlugin(string $name): void
    {
        $this->configuration['editor']['config']['removePlugins'][] = $name;
        $this->configuration['editor']['config']['removePlugins'] = $this->getUniqueArrayValues($this->configuration['editor']['config']['removePlugins']);
    }

    private function removeExtraPlugin(string $name): void
    {
        if (!isset($this->configuration['editor']['config']['extraPlugins'])) {
            return;
        }

        $this->configuration['editor']['config']['extraPlugins'] = array_filter($this->configuration['editor']['config']['extraPlugins'], static function (string $value) use ($name) {
            return $value !== $name;
        });

        if (empty($this->configuration['editor']['config']['extraPlugins'])) {
            unset($this->configuration['editor']['config']['extraPlugins']);
            return;
        }

        $this->configuration['editor']['config']['extraPlugins'] = $this->getUniqueArrayValues($this->configuration['editor']['config']['extraPlugins']);
    }

    /**
     * Ensure to have clean array with incrementing identifiers
     * to avoid JavaScript casting this to an object
     */
    private function getUniqueArrayValues(array $array)
    {
        return array_values(array_unique($array));
    }
}
