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

namespace TYPO3\CMS\Core\Localization\Parser;

/**
 * Parser for XLIFF file.
 * @internal This class is a concrete implementation and is not part of the TYPO3 Core API.
 */
class XliffParser extends AbstractXmlParser
{
    /**
     * Returns array representation of XML data, starting from a root node.
     *
     * @param \SimpleXMLElement $root A root node
     * @return array An array representing the parsed XML file
     */
    protected function doParsingFromRoot(\SimpleXMLElement $root)
    {
        $parsedData = [];
        $bodyOfFileTag = $root->file->body;
        if ($bodyOfFileTag instanceof \SimpleXMLElement) {
            foreach ($bodyOfFileTag->children() as $translationElement) {
                /** @var \SimpleXMLElement $translationElement */
                if ($translationElement->getName() === 'trans-unit' && !isset($translationElement['restype'])) {
                    // If restype would be set, it could be metadata from Gettext to XLIFF conversion (and we don't need this data)
                    if ($this->languageKey === 'default') {
                        // Default language coming from an XLIFF template (no target element)
                        $parsedData[(string)$translationElement['id']][0] = [
                            'source' => (string)$translationElement->source,
                            'target' => (string)$translationElement->source,
                        ];
                    } else {
                        // @todo Support "approved" attribute
                        $parsedData[(string)$translationElement['id']][0] = [
                            'source' => (string)$translationElement->source,
                            'target' => (string)$translationElement->target,
                        ];
                    }
                } elseif ($translationElement->getName() === 'group' && isset($translationElement['restype']) && (string)$translationElement['restype'] === 'x-gettext-plurals') {
                    // This is a translation with plural forms
                    $parsedTranslationElement = [];
                    foreach ($translationElement->children() as $translationPluralForm) {
                        /** @var \SimpleXMLElement $translationPluralForm */
                        if ($translationPluralForm->getName() === 'trans-unit') {
                            // When using plural forms, ID looks like this: 1[0], 1[1] etc
                            $formIndex = substr((string)$translationPluralForm['id'], strpos((string)$translationPluralForm['id'], '[') + 1, -1);
                            if ($this->languageKey === 'default') {
                                // Default language come from XLIFF template (no target element)
                                $parsedTranslationElement[(int)$formIndex] = [
                                    'source' => (string)$translationPluralForm->source,
                                    'target' => (string)$translationPluralForm->source,
                                ];
                            } else {
                                // @todo Support "approved" attribute
                                $parsedTranslationElement[(int)$formIndex] = [
                                    'source' => (string)$translationPluralForm->source,
                                    'target' => (string)$translationPluralForm->target,
                                ];
                            }
                        }
                    }
                    if (!empty($parsedTranslationElement)) {
                        if (isset($translationElement['id'])) {
                            $id = (string)$translationElement['id'];
                        } else {
                            $id = (string)$translationElement->{'trans-unit'}[0]['id'];
                            $id = substr($id, 0, (int)strpos($id, '['));
                        }
                        $parsedData[$id] = $parsedTranslationElement;
                    }
                }
            }
        }
        return $parsedData;
    }
}
