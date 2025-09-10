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
        return $catalogue;
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
