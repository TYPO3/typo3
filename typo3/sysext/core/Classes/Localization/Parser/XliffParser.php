<?php
namespace TYPO3\CMS\Core\Localization\Parser;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Dominique Feyer <dfeyer@reelpeek.net>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Parser for XLIFF file.
 *
 * @author Dominique Feyer <dfeyer@reelpeek.net>
 */
class XliffParser extends \TYPO3\CMS\Core\Localization\Parser\AbstractXmlParser {

	/**
	 * Returns array representation of XML data, starting from a root node.
	 *
	 * @param SimpleXMLElement $root A root node
	 * @return array An array representing the parsed XML file
	 */
	protected function doParsingFromRoot(\SimpleXMLElement $root) {
		$parsedData = array();
		$bodyOfFileTag = $root->file->body;
		if ($bodyOfFileTag instanceof \SimpleXMLElement) {
			foreach ($bodyOfFileTag->children() as $translationElement) {
				if ($translationElement->getName() === 'trans-unit' && !isset($translationElement['restype'])) {
					// If restype would be set, it could be metadata from Gettext to XLIFF conversion (and we don't need this data)
					if ($this->languageKey === 'default') {
						// Default language coming from an XLIFF template (no target element)
						$parsedData[(string) $translationElement['id']][0] = array(
							'source' => (string) $translationElement->source,
							'target' => (string) $translationElement->source
						);
					} else {
						// @todo Support "approved" attribute
						$parsedData[(string) $translationElement['id']][0] = array(
							'source' => (string) $translationElement->source,
							'target' => (string) $translationElement->target
						);
					}
				} elseif ($translationElement->getName() === 'group' && isset($translationElement['restype']) && (string) $translationElement['restype'] === 'x-gettext-plurals') {
					// This is a translation with plural forms
					$parsedTranslationElement = array();
					foreach ($translationElement->children() as $translationPluralForm) {
						if ($translationPluralForm->getName() === 'trans-unit') {
							// When using plural forms, ID looks like this: 1[0], 1[1] etc
							$formIndex = substr((string) $translationPluralForm['id'], strpos((string) $translationPluralForm['id'], '[') + 1, -1);
							if ($this->languageKey === 'default') {
								// Default language come from XLIFF template (no target element)
								$parsedTranslationElement[(int) $formIndex] = array(
									'source' => (string) $translationPluralForm->source,
									'target' => (string) $translationPluralForm->source
								);
							} else {
								// @todo Support "approved" attribute
								$parsedTranslationElement[(int) $formIndex] = array(
									'source' => (string) $translationPluralForm->source,
									'target' => (string) $translationPluralForm->target
								);
							}
						}
					}
					if (!empty($parsedTranslationElement)) {
						if (isset($translationElement['id'])) {
							$id = (string) $translationElement['id'];
						} else {
							$id = (string) $translationElement->{'trans-unit'}[0]['id'];
							$id = substr($id, 0, strpos($id, '['));
						}
						$parsedData[$id] = $parsedTranslationElement;
					}
				}
			}
		}
		return $parsedData;
	}

}


?>