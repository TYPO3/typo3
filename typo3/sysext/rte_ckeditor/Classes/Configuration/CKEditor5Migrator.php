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

namespace TYPO3\CMS\RteCKEditor\Configuration;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal
 */
class CKEditor5Migrator
{
    /**
     * Main groups in CKEditor 4 contain subgroups.
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
     * Groups in CKEditor 4 contain buttons.
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
        'Font' => 'fontFamily',
        'FontSize' => 'fontSize',
        // colors
        'TextColor' => 'fontColor',
        'BGColor' => 'fontBackgroundColor',
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
        'image' => [
            'module' => '@ckeditor/ckeditor5-image',
            'exports' => [ 'Image', 'ImageCaption', 'ImageStyle', 'ImageToolbar', 'ImageUpload', 'PictureEditing' ],
        ],
        'Image' => [
            'module' => '@ckeditor/ckeditor5-image',
            'exports' => [ 'Image', 'ImageCaption', 'ImageStyle', 'ImageToolbar', 'ImageUpload', 'PictureEditing' ],
        ],
        'alignment' => [
            'module' => '@ckeditor/ckeditor5-alignment',
            'exports' => [ 'Alignment' ],
        ],
        'Alignment' => [
            'module' => '@ckeditor/ckeditor5-alignment',
            'exports' => [ 'Alignment' ],
        ],
        'autolink' => [
            'module' => '@ckeditor/ckeditor5-link',
            'exports' => [ 'AutoLink' ],
        ],
        'AutoLink' => [
            'module' => '@ckeditor/ckeditor5-link',
            'exports' => [ 'AutoLink' ],
        ],
        'font' => [
            'module' => '@ckeditor/ckeditor5-font',
            'exports' => [ 'Font' ],
        ],
        'Font' => [
            'module' => '@ckeditor/ckeditor5-font',
            'exports' => [ 'Font' ],
        ],
        'justify' => [
            'module' => '@ckeditor/ckeditor5-alignment',
            'exports' => [ 'Alignment' ],
        ],
        'showblocks' => [
            'module' =>  '@ckeditor/ckeditor5-show-blocks',
            'exports' => [ 'ShowBlocks' ],
        ],
        'ShowBlocks' => [
            'module' =>  '@ckeditor/ckeditor5-show-blocks',
            'exports' => [ 'ShowBlocks' ],
        ],
        'softhyphen' => [
            'module' =>  '@typo3/rte-ckeditor/plugin/whitespace.js',
            'exports' => [ 'Whitespace' ],
        ],
        'whitespace' => [
            'module' =>  '@typo3/rte-ckeditor/plugin/whitespace.js',
            'exports' => [ 'Whitespace' ],
        ],
        'Whitespace' => [
            'module' =>  '@typo3/rte-ckeditor/plugin/whitespace.js',
            'exports' => [ 'Whitespace' ],
        ],
        'wordcount' =>  [
            'module' => '@ckeditor/ckeditor5-word-count',
            'exports' => [ 'WordCount' ],
        ],
        'WordCount' =>  [
            'module' => '@ckeditor/ckeditor5-word-count',
            'exports' => [ 'WordCount' ],
        ],
    ];

    /**
     * @param array $configuration Richtext configuration
     */
    public function __construct(protected array $configuration)
    {
        if (isset($this->configuration['editor']['config'])) {
            $this->migrateExtraPlugins();
            $this->migrateRemovePlugins();
            $this->migrateToolbar();
            $this->migrateRemoveButtonsFromToolbar();
            $this->migrateFormatTagsToHeadings();
            $this->migrateStylesSetToStyleDefinitions();
            $this->migrateContentsCssToArray();
            $this->migrateTypo3LinkAdditionalAttributes();
            $this->migrateAllowedContent();
            // configure plugins
            $this->handleAlignmentPlugin();
            $this->handleWhitespacePlugin();
            $this->handleWordCountPlugin();
            $this->handleStyleDefinitions();

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

    protected function migrateExtraPlugins(): void
    {
        if (!isset($this->configuration['editor']['config']['extraPlugins'])) {
            return;
        }

        if (is_string($this->configuration['editor']['config']['extraPlugins'])) {
            $this->configuration['editor']['config']['extraPlugins'] = GeneralUtility::trimExplode(
                ',',
                $this->configuration['editor']['config']['extraPlugins'],
                true
            );
        }

        foreach ($this->configuration['editor']['config']['extraPlugins'] as $entry) {
            $moduleToBeLoaded = self::PLUGIN_MAP[$entry] ?? null;
            if ($moduleToBeLoaded === null) {
                continue;
            }
            $this->configuration['editor']['config']['importModules'][] = $moduleToBeLoaded;
            $this->removeExtraPlugin($entry);
        }
    }

    protected function migrateRemovePlugins(): void
    {
        if (!isset($this->configuration['editor']['config']['removePlugins'])) {
            return;
        }

        if (is_string($this->configuration['editor']['config']['removePlugins'])) {
            $this->configuration['editor']['config']['removePlugins'] = GeneralUtility::trimExplode(
                ',',
                $this->configuration['editor']['config']['removePlugins'],
                true
            );
        }

        foreach ($this->configuration['editor']['config']['removePlugins'] as $key => $entry) {
            $moduleToBeRemoved = self::PLUGIN_MAP[$entry] ?? null;
            if ($moduleToBeRemoved !== null) {
                unset($this->configuration['editor']['config']['removePlugins'][$key]);
                $this->configuration['editor']['config']['removeImportModules'][] = $moduleToBeRemoved;
            }
        }
        if (count($this->configuration['editor']['config']['removePlugins']) === 0) {
            unset($this->configuration['editor']['config']['removePlugins']);
        } else {
            $this->configuration['editor']['config']['removePlugins'] = $this->getUniqueArrayValues($this->configuration['editor']['config']['removePlugins']);
        }
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

        // Migrate CKEditor 4 toolbarGroups
        // There can only be one configuration at a time, if 'toolbarGroups' is set
        // we prefer this definition above the toolbar definition.
        // https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-toolbarGroups
        if (is_array($this->configuration['editor']['config']['toolbarGroups'] ?? null)) {
            $toolbar['items'] = $this->configuration['editor']['config']['toolbarGroups'];
            unset($this->configuration['editor']['config']['toolbar'], $this->configuration['editor']['config']['toolbarGroups']);
        }

        // Migrate CKEditor 4 toolbar templates
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
                // Expand CKEditor 4 preset toolbar groups
                if (is_string($item['name'] ?? null) && count($item) === 1 && isset(self::TOOLBAR_MAIN_GROUPS_MAP[$item['name']])) {
                    $item['groups'] = self::TOOLBAR_MAIN_GROUPS_MAP[$item['name']];
                }
                // Flatten CKEditor 4 arrays that only have strings assigned
                if (count($item) === count(array_filter($item, static fn(mixed $value): bool => is_string($value)))) {
                    $migratedToolbarItems = $item;
                    $migratedToolbarItems = $this->migrateToolbarButtons($migratedToolbarItems);
                    $migratedToolbarItems = $this->migrateToolbarSpacers($migratedToolbarItems);
                    array_push($toolbarItems, ...$migratedToolbarItems);
                    $toolbarItems[] = '|';
                    continue;
                }
                // Flatten CKEditor 4 named groups
                if (is_string($item['name'] ?? null) && is_array($item['items'] ?? null)) {
                    $migratedToolbarItems = $item['items'];
                    $migratedToolbarItems = $this->migrateToolbarButtons($migratedToolbarItems);
                    $migratedToolbarItems = $this->migrateToolbarSpacers($migratedToolbarItems);
                    array_push($toolbarItems, ...$migratedToolbarItems);
                    $toolbarItems[] = '|';
                    continue;
                }
                // Expand CKEditor 4 toolbar groups
                if (is_string($item['name'] ?? null) && is_array($item['groups'] ?? null)) {
                    $itemGroups = array_filter($item['groups'], static fn(mixed $itemGroup): bool => is_string($itemGroup));

                    // Process Main CKEditor 4 Groups
                    $unGroupedToolbarItems = [];
                    foreach ($itemGroups as $itemGroup) {
                        if (isset(self::TOOLBAR_MAIN_GROUPS_MAP[$itemGroup])) {
                            array_push($unGroupedToolbarItems, ...self::TOOLBAR_MAIN_GROUPS_MAP[$itemGroup]);
                            $unGroupedToolbarItems[] = '|';
                            continue;
                        }
                        $unGroupedToolbarItems[] = $itemGroup;
                    }

                    // Process CKEditor 4 Groups
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

    protected function migrateTypo3LinkAdditionalAttributes(): void
    {
        if (!isset($this->configuration['editor']['config']['typo3link']['additionalAttributes'])) {
            return;
        }
        $additionalAttributes = $this->configuration['editor']['config']['typo3link']['additionalAttributes'];
        unset($this->configuration['editor']['config']['typo3link']['additionalAttributes']);
        if ($this->configuration['editor']['config']['typo3link'] === []) {
            unset($this->configuration['editor']['config']['typo3link']);
        }
        if (!is_array($additionalAttributes) || $additionalAttributes === []) {
            return;
        }
        $this->configuration['editor']['config']['htmlSupport']['allow'][] = [
            'name' => 'a',
            'attributes' => array_values($additionalAttributes),
        ];
    }

    protected function parseRuleProperties(string $properties, string $type): ?string
    {
        $groupsPatterns = [
            'styles' => '/{([^}]+)}/',
            'attrs' => '/\[([^\]]+)\]/',
            'classes' => '/\(([^\)]+)\)/',
        ];
        $pattern = $groupsPatterns[$type] ?? null;
        if ($pattern === null) {
            throw new \InvalidArgumentException('Expected type to be styles, attrs or classes', 1696326899);
        }

        $matches = [];
        if (preg_match($pattern, $properties, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Based on https://github.com/ckeditor/ckeditor4/blob/4.23.0-lts/core/filter.js#L1438
     */
    protected function parseRulesString(string $input): array
    {
        $ruleConfig = [];
        do {
            $matches = [];
            $res = preg_match(
                // Based on https://github.com/ckeditor/ckeditor4/blob/4.23.0-lts/core/filter.js#L1431
                // <   elements   ><                       styles, attributes and classes                        >< separator >
                '/^([a-z0-9\-*\s]+)((?:\s*\{[!\w\-,\s\*]+\}\s*|\s*\[[!\w\-,\s\*]+\]\s*|\s*\([!\w\-,\s\*]+\)\s*){0,3})(?:;\s*|$)/i',
                $input,
                $matches
            );
            if ($res === false || $res === 0) {
                return $ruleConfig;
            }
            $name = $matches[1];
            $properties = $matches[2];
            $config = [];
            $config['styles'] = $this->parseRuleProperties($properties, 'styles');
            $config['attributes'] = $this->parseRuleProperties($properties, 'attrs');
            $config['classes'] = $this->parseRuleProperties($properties, 'classes');
            $ruleConfig[$name] = $config;

            $input = substr($input, strlen($matches[0]));
        } while ($input !== '');
        return $ruleConfig;
    }

    protected function migrateAllowedContent(): void
    {
        $types = [
            'allowedContent' => 'allow',
            'extraAllowedContent' => 'allow',
            'disallowedContent' => 'disallow',
        ];

        foreach ($types as $option4 => $option5) {
            if (!isset($this->configuration['editor']['config'][$option4])) {
                continue;
            }

            if ($option4 === 'allowedContent') {
                if ($this->configuration['editor']['config']['allowedContent'] === true || $this->configuration['editor']['config']['allowedContent'] === '1') {
                    $this->configuration['editor']['config']['htmlSupport']['allow'][] = [
                        // Allow *any* tag (even custom elements)
                        'name' => [
                            'pattern' => '.+',
                        ],
                        'attributes' => true,
                        'classes' => true,
                        'styles' => true,
                    ];
                    unset($this->configuration['editor']['config']['allowedContent']);
                    continue;
                }
            }

            $config4 = $this->configuration['editor']['config'][$option4];
            if (is_string($config4)) {
                $config4 = $this->parseRulesString($config4);
            }

            foreach ($config4 as $name => $options) {
                $config = [];
                if ($name === '*') {
                    $config['name'] = [ 'pattern' => '^[a-z]+$' ];
                } else {
                    $name = (string)$name;
                    $config['name'] = str_contains($name, '*') || str_contains($name, ' ') ?
                        [ 'pattern' => str_replace(['*', ' '], ['.+', '|'], $name) ] :
                        $name;
                }

                if (is_bool($options)) {
                    if ($options) {
                        $this->configuration['editor']['config']['htmlSupport'][$option5][] = $config;
                    }
                    continue;
                }

                if (!is_array($options)) {
                    continue;
                }

                $wildcardToRegex = fn(string $v): string|array => str_contains($v, '*') ? [ 'pattern' => str_replace('*', '.+', $v) ] : $v;
                if (isset($options['classes'])) {
                    if ($options['classes'] === '*') {
                        $config['classes'] = true;
                    } else {
                        $config['classes'] = array_map($wildcardToRegex, explode(',', $options['classes']));
                    }
                }

                if (isset($options['attributes'])) {
                    if ($options['attributes'] === '*') {
                        $config['attributes'] = true;
                    } else {
                        $config['attributes'] = array_map($wildcardToRegex, explode(',', $options['attributes']));
                    }
                }

                if (isset($options['styles'])) {
                    if ($options['styles'] === '*') {
                        $config['styles'] = true;
                    } else {
                        $config['styles'] = array_map($wildcardToRegex, explode(',', $options['styles']));
                    }
                }
                $this->configuration['editor']['config']['htmlSupport'][$option5][] = $config;
            }
            unset($this->configuration['editor']['config'][$option4]);
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
        if (in_array(
            '@ckeditor/ckeditor5-alignment',
            array_column($this->configuration['editor']['config']['removeImportModules'] ?? [], 'module'),
            true
        )) {
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
        if (in_array(
            '@typo3/rte-ckeditor/plugin/whitespace.js',
            array_column($this->configuration['editor']['config']['removeImportModules'] ?? [], 'module'),
            true
        )) {
            // Remove toolbar items
            $this->removeToolbarItem('softhyphen');
        }
    }

    protected function handleWordCountPlugin(): void
    {
        // Migrate legacy configuration
        //
        // CKEditor 4 used `wordcount` (lowercase), which is `wordCount` in CKEditor 5.
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
        if (in_array(
            '@ckeditor/ckeditor5-word-count',
            array_column($this->configuration['editor']['config']['removeImportModules'] ?? [], 'module'),
            true
        )) {
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

    protected function handleStyleDefinitions(): void
    {
        if (isset($this->configuration['editor']['config']['style']['definitions'])
            && is_array($this->configuration['editor']['config']['style']['definitions'])
        ) {
            foreach ($this->configuration['editor']['config']['style']['definitions'] as $definitionIndex => $definition) {
                $classes = $definition['classes'] ?? [];
                if ($classes === []) {
                    // See CKEditor5Migrator::migrateStylesSetToStyleDefinitions - an empty array is not allowed.
                    // The "classes" attribute must always either be a string (even using `true` will lead to class="true"),
                    // or "['']" (array with empty string, leading to class=""). CKEditor 5 requires this attribute to
                    // be set, see https://ckeditor.com/docs/ckeditor5/latest/api/module_style_styleconfig-StyleDefinition.html
                    $this->configuration['editor']['config']['style']['definitions'][$definitionIndex]['classes'] = [''];
                }
            }
        }
    }

    protected function addLinkClassesToStyleSets(): void
    {
        if (!isset($this->configuration['buttons']['link']['properties']['class']['allowedClasses'])) {
            return;
        }

        // Ensure editor.config.style.definitions exists
        $this->configuration['editor']['config']['style']['definitions'] ??= [];

        $allowedClassSets = is_array($this->configuration['buttons']['link']['properties']['class']['allowedClasses'])
            ? $this->configuration['buttons']['link']['properties']['class']['allowedClasses']
            : GeneralUtility::trimExplode(',', $this->configuration['buttons']['link']['properties']['class']['allowedClasses'], true);

        // Determine index where link classes should be added at to keep styles grouped
        $indexToInsertElementsAt = array_key_last($this->configuration['editor']['config']['style']['definitions']) + 1;
        foreach ($this->configuration['editor']['config']['style']['definitions'] as $index => $styleSetDefinition) {
            if ($styleSetDefinition['element'] === 'a') {
                $indexToInsertElementsAt = $index + 1;
            }
        }

        foreach ($allowedClassSets as $classSet) {
            $allowedClasses = GeneralUtility::trimExplode(' ', $classSet);
            foreach ($this->configuration['editor']['config']['style']['definitions'] as $styleSetDefinition) {
                if ($styleSetDefinition['element'] === 'a' && $styleSetDefinition['classes'] === $allowedClasses) {
                    // allowedClasses is already configured, continue with next one
                    continue 2;
                }
            }

            // We're still here, this means $allowedClasses wasn't found
            array_splice($this->configuration['editor']['config']['style']['definitions'], $indexToInsertElementsAt, 0, [[
                'classes' => $allowedClasses,
                'element' => 'a',
                'name' => implode(' ', $allowedClasses), // we lack a human-readable name here...
            ]]);
            $indexToInsertElementsAt++;
        }
    }

    private function removeToolbarItem(string $name): void
    {
        $this->configuration['editor']['config']['toolbar']['removeItems'][] = $name;
        $this->configuration['editor']['config']['toolbar']['removeItems'] = $this->getUniqueArrayValues($this->configuration['editor']['config']['toolbar']['removeItems']);
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
