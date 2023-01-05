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
    private const TOOLBAR_GROUPS_MAP = [
        'basicstyles' => ['bold', 'italic', 'underline', 'strikethrough', 'subscript', 'superscript'],
        'format' => ['heading'],
        'styles' => ['style'],
        'list' => ['numberedList', 'bulletedList'],
        'indent' => ['indent', 'outdent'],
        'blocks' => ['blockQuote'], // `CreateDiv` missing
        'align' => ['alignment'], // + separate `alignment: { options: ['left', 'right', 'center', 'justify'] }`
        'links' => ['link'],
        'unlink' => [],
        'clipboard' => [], // @todo no sure yet how/whether this is visualized https://ckeditor.com/docs/ckeditor5/latest/api/clipboard.html
        'cleanup' => ['removeFormat'], // CopyFormat dropped: https://github.com/ckeditor/ckeditor5/issues/1901
        'undo' => ['undo', 'redo'],
        'spellchecker' => [], // dropped: https://github.com/ckeditor/ckeditor5/issues/1458
        'insert' => ['horizontalLine'],
        'table' => ['insertTable'],
        'specialchar' => ['specialCharacters'],
        'mode' => ['sourceEditing'],
        'tools' => [], // Maximize dropped: https://github.com/ckeditor/ckeditor5/issues/1235
    ];

    // List of "old" button names vs the replacement(s)
    private const BUTTON_MAP = [
        'Bold' => ['bold'],
        'Italic' => ['italic'],
        'Strike' => ['strikethrough'],
        'Underline' => ['underline'],
        'Subscript' => ['subscript'],
        'Superscript' => ['superscript'],
        'Link' => ['link'],
        'Anchor' => [],
        'list' => ['numberedList', 'bulletedList'],
        'Indent' => ['indent', 'outdent'],
        'Format' => ['heading'],
        'BasicStyle' => ['heading'],
        'Table' => ['insertTable'],
        'specialcharacters' => ['specialCharacters'],
        'specialchar' => ['specialCharacters'],
    ];

    /**
     * @param array $configuration `editor.config` configuration
     */
    public function __construct(protected array $configuration)
    {
        $this->migrateToolbar();
        $this->migrateRemoveButtonsFromToolbar();
        $this->migrateFormatTagsToHeadings();
        $this->migrateStylesSetToStyleDefinitions();
        // plugin specific
        $this->migrateWordCountPlugin();
    }

    public function get(): array
    {
        return $this->configuration;
    }

    /**
     * CE4: https://ckeditor.com/latest/samples/toolbarconfigurator/index.html#basic
     * CE5: https://ckeditor.com/docs/ckeditor5/latest/features/toolbar/toolbar.html#extended-toolbar-configuration-format
     */
    protected function migrateToolbar(): void
    {
        if (!isset($this->configuration['toolbar']) && !isset($this->configuration['toolbarGroups'])) {
            return;
        }
        $toolbar = [
            'items' => [],
            'removeItems' => [],
            'shouldNotGroupWhenFull' => true,
        ];

        if (is_array($this->configuration['toolbar'] ?? null)) {
            $toolbarItems = array_filter(
                $this->configuration['toolbar']['items'] ?? $this->configuration['toolbar'],
                static fn ($item) => is_string($item)
            );
            if (is_array($this->configuration['toolbar']['items'] ?? null)) {
                $toolbar['items'] = array_merge($toolbar['items'], $toolbarItems);
            } else {
                $toolbar['items'] = array_merge($toolbar['items'], $toolbarItems);
            }
        }

        // @todo https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-toolbar

        // https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html#cfg-toolbarGroups
        // CE4: `[ { name: 'document',    groups: [ 'mode', 'document', 'doctools' ] }, '/', { name: 'other',  ... } ]`
        // CE5: `[ [ 'mode', 'document', 'doctools' ], '|', [ ... ] ]`
        if (is_array($this->configuration['toolbarGroups'] ?? null)) {
            $i = 0;
            $toolbarItems = [];
            $toolbarSize = count($this->configuration['toolbarGroups']);

            foreach ($this->configuration['toolbarGroups'] as $item) {
                $previousToolbarItem = array_slice($toolbarItems, -1, 1);
                if ($item === '/') {
                    // @todo check `toolbarview-line-break-ignored-when-grouping-items`
                    if ($previousToolbarItem === ['|']) {
                        array_splice($toolbarItems, -1, 1, '-');
                    } elseif ($previousToolbarItem !== ['-']) {
                        $toolbarItems[] = '-'; // new line
                    }
                } elseif (is_array($item['groups'] ?? null)) {
                    $groupedToolbarItems = [];
                    foreach ($item['groups'] as $itemGroup) {
                        if (!is_string($itemGroup)) {
                            continue;
                        }
                        if (isset(self::TOOLBAR_GROUPS_MAP[$itemGroup])) {
                            array_push($groupedToolbarItems, ...self::TOOLBAR_GROUPS_MAP[$itemGroup]);
                        }
                        // @todo warning/deprecation
                    }
                    array_push($toolbarItems, ...$groupedToolbarItems);
                    if ($i < $toolbarSize && $groupedToolbarItems !== []) {
                        $toolbarItems[] = '|'; // separator
                    }
                }
            }
            $previousToolbarItem = array_slice($toolbarItems, -1, 1);
            if ($previousToolbarItem === ['-'] || $previousToolbarItem === ['|']) {
                array_pop($toolbarItems);
            }

            unset($this->configuration['toolbarGroups']);
            if (!empty($toolbarItems)) {
                $toolbar['items'] = array_merge($toolbar['items'], $toolbarItems);
            }
        }
        $this->configuration['toolbar'] = $toolbar;
    }

    protected function migrateRemoveButtonsFromToolbar(): void
    {
        if (!isset($this->configuration['removeButtons'])) {
            return;
        }

        $removeItems = [];
        foreach ($this->configuration['removeButtons'] as $buttonName) {
            if (isset(self::TOOLBAR_GROUPS_MAP[$buttonName])) {
                // all buttons within a group
                $removeItems = array_merge($removeItems, self::TOOLBAR_GROUPS_MAP[$buttonName]);
            } elseif (isset(self::TOOLBAR_GROUPS_MAP[lcfirst($buttonName)])) {
                // all buttons within a group
                $removeItems = array_merge($removeItems, self::TOOLBAR_GROUPS_MAP[lcfirst($buttonName)]);
            } elseif (isset(self::BUTTON_MAP[$buttonName])) {
                // a single item
                $removeItems = array_merge($removeItems, self::BUTTON_MAP[$buttonName]);
            } else {
                $removeItems[] = lcfirst($buttonName);
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
                if (!isset($styleSet['name']) || !isset($styleSet['element'])) {
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

    /**
     * CKEditor4 used `wordcount` (lowercase), which is `wordCount` in CKEditor5.
     * The amount of properties has been reduced.
     *
     * see https://ckeditor.com/docs/ckeditor5/latest/features/word-count.html
     */
    protected function migrateWordCountPlugin(): void
    {
        if (!isset($this->configuration['wordcount'])) {
            return;
        }
        $legacyConfig = $this->configuration['wordcount'];
        $migratedConfig = [];
        if (isset($legacyConfig['showCharCount'])) {
            $migratedConfig['displayCharacters'] = !empty($legacyConfig['showCharCount']);
        }
        if (isset($legacyConfig['showWordCount'])) {
            $migratedConfig['displayWords'] = !empty($legacyConfig['showWordCount']);
        }
        if ($migratedConfig !== []) {
            $this->configuration['wordCount'] = $migratedConfig;
        }
        unset($this->configuration['wordcount']);
    }

    private function removeToolbarItem(string $name): void
    {
        $this->configuration['toolbar']['removeItems'][] = $name;
        $this->configuration['toolbar']['removeItems'] = $this->getUniqueArrayValues($this->configuration['toolbar']['removeItems']);
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
