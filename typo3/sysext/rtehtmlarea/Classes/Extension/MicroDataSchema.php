<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;

/**
 * Microdata Schema extension for htmlArea RTE
 */
class MicroDataSchema extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'MicrodataSchema';

    /**
     * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = 'showmicrodata';

    /**
     * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'showmicrodata' => 'ShowMicrodata'
    ];

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins
     */
    public function buildJavascriptConfiguration()
    {
        $schema = [
            'types' => [],
            'properties' => []
        ];
        // Parse configured schemas
        if (is_array($this->configuration['thisConfig']['schema.']) && is_array($this->configuration['thisConfig']['schema.']['sources.'])) {
            foreach ($this->configuration['thisConfig']['schema.']['sources.'] as $source) {
                $fileName = trim($source);
                $absolutePath = GeneralUtility::getFileAbsFileName($fileName);
                // Fallback to default schema file if configured file does not exists or is of zero size
                if (!$fileName || !file_exists($absolutePath) || !filesize($absolutePath)) {
                    $fileName = 'EXT:' . $this->extensionKey . '/Resources/Public/Rdf/MicrodataSchema/SchemaOrgAll.rdf';
                }
                $fileName = $this->getFullFileName($fileName);
                $rdf = GeneralUtility::getUrl($fileName);
                if ($rdf) {
                    $this->parseSchema($rdf, $schema);
                }
            }
        }
        uasort($schema['types'], [$this, 'compareLabels']);
        uasort($schema['properties'], [$this, 'compareLabels']);
        // Insert no type and no property entries
        $languageService = $this->getLanguageService();
        $noSchema = $languageService->sL('LLL:EXT:rtehtmlarea/Resources/Private/Language/Plugins/MicrodataSchema/locallang.xlf:No type');
        $noProperty = $languageService->sL('LLL:EXT:rtehtmlarea/Resources/Private/Language/Plugins/MicrodataSchema/locallang.xlf:No property');
        array_unshift($schema['types'], ['name' => 'none', 'label' => $noSchema]);
        array_unshift($schema['properties'], ['name' => 'none', 'label' => $noProperty]);
        // Store json encoded array in temporary file
        return 'RTEarea[editornumber].schemaUrl = "' . $this->writeTemporaryFile('schema_' . $this->configuration['language'], 'js', json_encode($schema)) . '";';
    }

    /**
     * Compare the labels of two schema types or properties for localized sort purposes
     *
     * @param array $a: first type/property definition array
     * @param array $b: second type/property definition array
     * @return int
     */
    protected function compareLabels($a, $b)
    {
        return strcoll($a['label'], $b['label']);
    }

    /**
     * Convert the xml rdf schema into an array
     *
     * @param string $string XML rdf schema to convert into an array
     * @param array	$schema: reference to the array to be filled
     * @return void
     */
    protected function parseSchema($string, &$schema)
    {
        $types = [];
        $properties = [];
        // Disables the functionality to allow external entities to be loaded when parsing the XML, must be kept
        $previousValueOfEntityLoader = libxml_disable_entity_loader(true);
        // Load the document
        $document = new \DOMDocument();
        $document->loadXML($string);
        libxml_disable_entity_loader($previousValueOfEntityLoader);
        if ($document) {
            // Scan resource descriptions
            $items = $document->getElementsByTagName('Description');
            foreach ($items as $item) {
                $name = $item->getAttribute('rdf:about');
                $type = $item->getElementsByTagName('type');
                if ($name && $type->length) {
                    $type = $type->item(0)->getAttribute('rdf:resource');
                    $resource = [];
                    $resource['name'] = $name;
                    $labels = $item->getElementsByTagName('label');
                    if ($labels->length) {
                        foreach ($labels as $label) {
                            $language = $label->getAttribute('xml:lang');
                            if ($language === $this->language) {
                                $resource['label'] = $label->nodeValue;
                            } elseif ($language === 'en') {
                                $defaultLabel = $label->nodeValue;
                            }
                        }
                        if (!$resource['label']) {
                            $resource['label'] = $defaultLabel;
                        }
                    }
                    $comments = $item->getElementsByTagName('comment');
                    if ($comments->length) {
                        foreach ($comments as $comment) {
                            $language = $comment->getAttribute('xml:lang');
                            if ($language === $this->language) {
                                $resource['comment'] = $comment->nodeValue;
                            } elseif ($language === 'en') {
                                $defaultComment = $comment->nodeValue;
                            }
                        }
                        if (!$resource['comment']) {
                            $resource['comment'] = $defaultComment;
                        }
                    }
                    switch ($type) {
                        case 'http://www.w3.org/2000/01/rdf-schema#Class':
                            $subClassOfs = $item->getElementsByTagName('subClassOf');
                            if ($subClassOfs->length) {
                                foreach ($subClassOfs as $subClassOf) {
                                    $resource['subClassOf'] = $subClassOf->getAttribute('rdf:resource');
                                }
                            }
                            // schema.rdfs.org/all.rdf may contain duplicates!!
                            if (!in_array($resource['name'], $types)) {
                                $schema['types'][] = $resource;
                                $types[] = $resource['name'];
                            }
                            break;
                        case 'http://www.w3.org/1999/02/22-rdf-syntax-ns#Property':
                            // Keep only the last level of the name
                            // This is the value we want in the itemprop attribute
                            $pos = strrpos($resource['name'], '/');
                            if ($pos) {
                                $resource['name'] = substr($resource['name'], $pos + 1);
                            }
                            $domains = $item->getElementsByTagName('domain');
                            if ($domains->length) {
                                foreach ($domains as $domain) {
                                    $resource['domain'] = $domain->getAttribute('rdf:resource');
                                }
                            }
                            $ranges = $item->getElementsByTagName('range');
                            if ($ranges->length) {
                                foreach ($ranges as $range) {
                                    $resource['range'] = $range->getAttribute('rdf:resource');
                                }
                            }
                            // schema.rdfs.org/all.rdf may contain duplicates!!
                            if (!in_array($resource['name'], $properties)) {
                                $schema['properties'][] = $resource;
                                $properties[] = $resource['name'];
                            }
                            break;
                        default:
                            // Do nothing
                    }
                }
            }
        }
    }
}
