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
        'wordcount' => 'WordCount',
    ];

    /**
     * @param array $configuration `editor.config` configuration
     */
    public function __construct(protected array $configuration)
    {
        $this->migrateRemovePlugins();
        $this->migrateToolbar();
        $this->migrateRemoveButtonsFromToolbar();
        $this->migrateFormatTagsToHeadings();
        $this->migrateStylesSetToStyleDefinitions();
        // configure plugins
        $this->handleWordCountPlugin();
        // sort by key
        ksort($this->configuration);
    }

    public function get(): array
    {
        return $this->configuration;
    }

    protected function migrateRemovePlugins(): void
    {
        if (!isset($this->configuration['removePlugins'])) {
            $this->configuration['removePlugins'] = [];
            return;
        }

        // Handle custom plugin names to ckeditor
        $this->configuration['removePlugins'] = array_map(function ($entry) {
            if (isset(self::PLUGIN_MAP[$entry])) {
                return self::PLUGIN_MAP[$entry];
            }
            return $entry;
        }, $this->configuration['removePlugins']);

        $this->configuration['removePlugins'] = $this->getUniqueArrayValues($this->configuration['removePlugins']);
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
            'removeItems' => [],
            'shouldNotGroupWhenFull' => true,
        ];

        // Migrate CKEditor4 toolbarGroups
        // There can only be one configuration at a time, if 'toolbarGroups' is set
        // we prefer this definition above the toolbar definition.
        // https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-toolbarGroups
        if (is_array($this->configuration['toolbarGroups'] ?? null)) {
            $toolbar['items'] = $this->configuration['toolbarGroups'];
            unset($this->configuration['toolbar'], $this->configuration['toolbarGroups']);
        }

        // Migrate CKEditor4 toolbar templates
        // Resolve toolbar template and override current toolbar
        // https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-toolbar
        if (is_string($this->configuration['toolbar'] ?? null)) {
            $toolbarName = 'toolbar_' . trim($this->configuration['toolbar']);
            if (is_array($this->configuration[$toolbarName] ?? null)) {
                $toolbar['items'] = $this->configuration[$toolbarName];
                unset($this->configuration['toolbar'], $this->configuration[$toolbarName]);
            }
        }

        // Collect toolbar items
        if (is_array($this->configuration['toolbar'] ?? null)) {
            $toolbar['items'] = $this->configuration['toolbar']['items'] ?? $this->configuration['toolbar'];
        }

        $toolbar['items'] = $this->migrateToolbarItems($toolbar['items']);
        $this->configuration['toolbar'] = $toolbar;
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
                if (count($item) === count(array_filter($item, static fn ($value) => is_string($value)))) {
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
                    $itemGroups = array_filter($item['groups'], static fn ($itemGroup) => is_string($itemGroup));

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
        if (!isset($this->configuration['removeButtons'])) {
            return;
        }

        $removeItems = [];
        foreach ($this->configuration['removeButtons'] as $buttonName) {
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
        unset($this->configuration['removeButtons']);
    }

    protected function migrateFormatTagsToHeadings(): void
    {
        // new definition is in place, no migration is done
        if (isset($this->configuration['heading']['options'])) {
            // discard legacy configuration if new configuration exists
            unset($this->configuration['format_tags']);
            return;
        }
        // migrate format_tags to custom buttons
        if (isset($this->configuration['format_tags'])) {
            $formatTags = explode(';', $this->configuration['format_tags']);
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
            unset($this->configuration['format_tags']);
            $this->configuration['heading']['options'] = $allowedHeadings;
        }
    }

    protected function migrateStylesSetToStyleDefinitions(): void
    {
        // new definition is in place, no migration is done
        if (isset($this->configuration['style']['definitions'])) {
            // discard legacy configuration if new configuration exists
            unset($this->configuration['stylesSet']);
            return;
        }
        // Migrate 'stylesSet' to 'styles' => 'definitions'
        if (isset($this->configuration['stylesSet'])) {
            $styleDefinitions = [];
            foreach ($this->configuration['stylesSet'] as $styleSet) {
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
            unset($this->configuration['stylesSet']);
            $this->configuration['style']['definitions'] = $styleDefinitions;
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
        if (isset($this->configuration['wordcount'])) {
            if (!isset($this->configuration['wordCount'])) {
                $legacyConfig = $this->configuration['wordcount'];
                if (isset($legacyConfig['showCharCount'])) {
                    $this->configuration['wordCount']['displayCharacters'] = !empty($legacyConfig['showCharCount']);
                }
                if (isset($legacyConfig['showWordCount'])) {
                    $this->configuration['wordCount']['displayWords'] = !empty($legacyConfig['showWordCount']);
                }
            }
            unset($this->configuration['wordcount']);
        }

        // Remove related configuration if plugin should not be loaded
        if (in_array('WordCount', $this->configuration['removePlugins'], true)) {
            // Remove all related plugins
            $this->removePlugin('WordCount');

            // Remove config
            if (isset($this->configuration['wordCount'])) {
                unset($this->configuration['wordCount']);
            }

            return;
        }

        // Default config
        $this->configuration['wordCount'] = [
            'displayCharacters' => $this->configuration['wordCount']['displayCharacters'] ?? true,
            'displayWords' => $this->configuration['wordCount']['displayWords'] ?? true,
        ];
    }

    private function removeToolbarItem(string $name): void
    {
        $this->configuration['toolbar']['removeItems'][] = $name;
        $this->configuration['toolbar']['removeItems'] = $this->getUniqueArrayValues($this->configuration['toolbar']['removeItems']);
    }

    private function removePlugin(string $name): void
    {
        $this->configuration['removePlugins'][] = $name;
        $this->configuration['removePlugins'] = $this->getUniqueArrayValues($this->configuration['removePlugins']);
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
