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

namespace TYPO3\CMS\Core\Localization\Loader;

use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use TYPO3\CMS\Core\Localization\Exception\InvalidXmlFileException;

/**
 * TYPO3-specific XLIFF file loader that implements Symfony's LoaderInterface
 * while maintaining TYPO3's specific features like approval states and plurals.
 *
 * This loader only converts ONE SINGLE FILE. It is not responsible for detecting the file location of
 * translation! This is done - currently - by the LocalizationFactory and should be moved to a dedicated class.
 *
 * This loader expects that the source is always english, and the translation is something else.
 *
 * @internal This class is not part of the TYPO3 Core API.
 */
final readonly class XliffLoader implements LoaderInterface
{
    public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        if (!is_string($resource)) {
            throw new InvalidXmlFileException('XliffLoader only accepts string types (file names, XML content).', 1757588428);
        }

        if (!$this->isXmlString($resource)) {
            if (!file_exists($resource)) {
                throw new InvalidXmlFileException('File "' . $resource . '" not found.', 1757588429);
            }

            if (!is_file($resource)) {
                throw new InvalidResourceException('The given resource is neither a file nor an XLIFF string "' . $resource . '".', 1757588430);
            }
        }

        $rootXmlNode = $this->convertResourceToXml($resource);
        return $this->parseXliffFromRoot($rootXmlNode, $locale, $domain);
    }

    /**
     * Parse XLIFF file and return TYPO3-compatible data structure
     */
    private function convertResourceToXml(string $resource): \SimpleXMLElement
    {
        if ($this->isXmlString($resource)) {
            $xmlContent = $resource;
        } else {
            $xmlContent = @file_get_contents($resource);
            if ($xmlContent === false) {
                throw new InvalidXmlFileException('The path provided does not point to an existing and accessible file.', 1757537341);
            }
        }
        $rootXmlNode = @simplexml_load_string($xmlContent, \SimpleXMLElement::class, LIBXML_NOWARNING);
        if ($rootXmlNode === false) {
            $xmlError = libxml_get_last_error();
            throw new InvalidXmlFileException(
                'The path provided does not point to an existing and accessible well-formed XML file. Reason: ' . $xmlError->message . ' in ' . $resource . ', line ' . $xmlError->line,
                1757537342
            );
        }
        return $rootXmlNode;
    }

    /**
     * Parse XLIFF content from root element and build a catalogue
     */
    private function parseXliffFromRoot(\SimpleXMLElement $root, string $locale, string $domain): MessageCatalogue
    {
        $catalogue = new MessageCatalogue($locale);
        $version = $this->getXliffVersion($root);

        if ($version === '2.0') {
            $this->parseXliff2($root, $catalogue, $locale, $domain);
        } else {
            // Default to XLIFF 1.2 parsing
            $this->parseXliff1($root, $catalogue, $locale, $domain);
        }

        return $catalogue;
    }

    /**
     * Detect XLIFF version from the root element
     */
    private function getXliffVersion(\SimpleXMLElement $root): string
    {
        $namespaces = $root->getNamespaces(true);

        // Check if XLIFF 2.x namespace is present (matches 2.0, 2.1, 2.2, etc.)
        foreach ($namespaces as $namespace) {
            if (str_starts_with($namespace, 'urn:oasis:names:tc:xliff:document:2.')) {
                return '2.0';
            }
        }

        // Check version attribute
        $version = (string)$root['version'];
        if (str_starts_with($version, '2.')) {
            return '2.0';
        }

        // Default to 1.2
        return '1.2';
    }

    /**
     * Parse XLIFF 1.2 format
     */
    private function parseXliff1(\SimpleXMLElement $root, MessageCatalogue $catalogue, string $locale, string $domain): void
    {
        $bodyOfFileTag = $root->file->body;
        $requireApprovedLocalizations = (bool)($GLOBALS['TYPO3_CONF_VARS']['LANG']['requireApprovedLocalizations'] ?? true);

        if ($bodyOfFileTag instanceof \SimpleXMLElement) {
            foreach ($bodyOfFileTag->children() as $translationElement) {
                /** @var \SimpleXMLElement $translationElement */
                if ($translationElement->getName() === 'trans-unit' && !isset($translationElement['restype'])) {
                    // Regular translation unit
                    $id = (string)$translationElement['id'];
                    if ($locale === 'en') {
                        // Default language from XLIFF template (no target element)
                        $translation = (string)($translationElement->target) ?: (string)$translationElement->source;
                        $catalogue->set($id, $translation, $domain);
                    } else {
                        $approved = (string)($translationElement['approved'] ?? 'yes');
                        if (!$requireApprovedLocalizations || $approved === 'yes') {
                            $catalogue->set($id, (string)$translationElement->target, $domain);
                        }
                    }
                } elseif ($translationElement->getName() === 'group' && isset($translationElement['restype']) && (string)$translationElement['restype'] === 'x-gettext-plurals') {
                    // Translation with plural forms
                    $parsedTranslationElement = [];
                    foreach ($translationElement->children() as $translationPluralForm) {
                        /** @var \SimpleXMLElement $translationPluralForm */
                        if ($translationPluralForm->getName() === 'trans-unit') {
                            // Extract plural form index from ID like "1[0]", "1[1]"
                            $formIndex = substr((string)$translationPluralForm['id'], strpos((string)$translationPluralForm['id'], '[') + 1, -1);
                            if ($locale === 'en') {
                                // Default language from XLIFF template (no target element)
                                $translation = (string)$translationPluralForm->target ?: (string)$translationPluralForm->source;
                                $parsedTranslationElement[(int)$formIndex] = $translation;
                            } else {
                                $approved = (string)($translationPluralForm['approved'] ?? 'yes');
                                if (!$requireApprovedLocalizations || $approved === 'yes') {
                                    $parsedTranslationElement[(int)$formIndex] = (string)$translationPluralForm->target;
                                }
                            }
                        }
                    }
                    if ($parsedTranslationElement !== []) {
                        if (isset($translationElement['id'])) {
                            $id = (string)$translationElement['id'];
                        } else {
                            $id = (string)$translationElement->{'trans-unit'}[0]['id'];
                            $id = substr($id, 0, (int)strpos($id, '['));
                        }
                        // Handle plurals - Symfony uses ICU format
                        $catalogue->set($id, $this->convertToIcuPlural(array_values($parsedTranslationElement)), $domain);
                    }
                }
            }
        }
    }

    /**
     * Parse XLIFF 2.0 format
     */
    private function parseXliff2(\SimpleXMLElement $root, MessageCatalogue $catalogue, string $locale, string $domain): void
    {
        $requireApprovedLocalizations = (bool)($GLOBALS['TYPO3_CONF_VARS']['LANG']['requireApprovedLocalizations'] ?? true);

        $ns = $root->getDocNamespaces();
        $ns = reset($ns) ?: 'urn:oasis:names:tc:xliff:document:2.0';
        // Register the XLIFF 2.0 namespace
        $root->registerXPathNamespace('xliff', $ns);

        // Get all file elements
        $files = $root->xpath('//xliff:file');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $file->registerXPathNamespace('xliff', $ns);

            // Get all unit elements within this file
            $units = $file->xpath('.//xliff:unit');
            if ($units === false) {
                continue;
            }

            foreach ($units as $unit) {
                $unit->registerXPathNamespace('xliff', $ns);
                $unitId = (string)$unit['id'];

                // Check if this is a plural unit (contains multiple segments)
                $segments = $unit->xpath('.//xliff:segment');
                if ($segments === false) {
                    continue;
                }

                if (count($segments) === 1) {
                    // Regular translation unit
                    $segment = $segments[0];
                    $segment->registerXPathNamespace('xliff', $ns);

                    $source = $segment->xpath('.//xliff:source');
                    $target = $segment->xpath('.//xliff:target');

                    if ($locale === 'en') {
                        // Default language from XLIFF template
                        $translation = ($target !== false && isset($target[0])) ? (string)$target[0] : (string)$source[0];
                        $catalogue->set($unitId, $translation, $domain);
                    } else {
                        // Check approval state (XLIFF 2.0 uses 'state' attribute on target)
                        $approved = 'yes';
                        if ($target !== false && isset($target[0])) {
                            $state = (string)$target[0]['state'];
                            // XLIFF 2.0 states: initial, translated, reviewed, final
                            // We consider 'final' as approved, others depend on config
                            if ($state === 'initial' || $state === 'translated') {
                                $approved = 'no';
                            }
                        }

                        if (!$requireApprovedLocalizations || $approved === 'yes') {
                            if ($target !== false && isset($target[0])) {
                                $catalogue->set($unitId, (string)$target[0], $domain);
                            }
                        }
                    }
                } else {
                    // Plural forms (multiple segments)
                    $parsedTranslationElement = [];
                    $formIndex = 0;

                    foreach ($segments as $segment) {
                        $segment->registerXPathNamespace('xliff', $ns);

                        $source = $segment->xpath('.//xliff:source');
                        $target = $segment->xpath('.//xliff:target');

                        if ($locale === 'en') {
                            $translation = ($target !== false && isset($target[0])) ? (string)$target[0] : (string)$source[0];
                            $parsedTranslationElement[$formIndex] = $translation;
                        } else {
                            $approved = 'yes';
                            if ($target !== false && isset($target[0])) {
                                $state = (string)$target[0]['state'];
                                if ($state === 'initial' || $state === 'translated') {
                                    $approved = 'no';
                                }
                            }

                            if (!$requireApprovedLocalizations || $approved === 'yes') {
                                if ($target !== false && isset($target[0])) {
                                    $parsedTranslationElement[$formIndex] = (string)$target[0];
                                }
                            }
                        }
                        $formIndex++;
                    }

                    if ($parsedTranslationElement !== []) {
                        $catalogue->set($unitId, $this->convertToIcuPlural(array_values($parsedTranslationElement)), $domain);
                    }
                }
            }
        }
    }

    /**
     * Convert plural forms to ICU plural format
     * This is a simplified conversion - could be enhanced based on actual usage
     */
    private function convertToIcuPlural(array $pluralValues): string
    {
        if (count($pluralValues) === 1) {
            return $pluralValues[0];
        }

        // Simple mapping: [0] = one, [1] = other
        $icuFormat = '';
        if (isset($pluralValues[0])) {
            $icuFormat .= '{0, plural, one {' . $pluralValues[0] . '}';
        }
        if (isset($pluralValues[1])) {
            $icuFormat .= ' other {' . $pluralValues[1] . '}';
        }
        $icuFormat .= '}';

        return $icuFormat;
    }

    private function isXmlString(string $resource): bool
    {
        return str_starts_with($resource, '<?xml');
    }
}
