<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012/2013 Stanislas Rolland <typo3(arobas)sjbr.ca>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Microdata Schema extension for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class MicroDataSchema extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	// The key of the TYPO3 extension that is extending htmlArea RTE
	protected $extensionKey = 'rtehtmlarea';

	// The name of the plugin registered by the extension
	protected $pluginName = 'MicrodataSchema';

	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToLocallangFile = 'extensions/MicrodataSchema/locallang.xlf';

	// Path to the skin (css) file relative to the extension dir
	protected $relativePathToSkin = 'extensions/MicrodataSchema/skin/htmlarea.css';

	protected $htmlAreaRTE;

	// Reference to the invoking object
	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Reference to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array
	protected $pluginButtons = 'showmicrodata';

	protected $convertToolbarForHtmlAreaArray = array(
		'showmicrodata' => 'ShowMicrodata'
	);

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param 	integer		Relative id of the RTE editing area in the form
	 * @return 	string		JS configuration for registered plugins
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		$registerRTEinJavascriptString = '';
		$schema = array(
			'types' => array(),
			'properties' => array()
		);
		// Parse configured schemas
		if (is_array($this->thisConfig['schema.']) && is_array($this->thisConfig['schema.']['sources.'])) {
			foreach ($this->thisConfig['schema.']['sources.'] as $source) {
				$fileName = $this->htmlAreaRTE->getFullFileName($source);
				$absolutePath = $fileName ? \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath(PATH_site . ($this->htmlAreaRTE->is_FE() || $this->htmlAreaRTE->isFrontendEditActive() ? '' : TYPO3_mainDir) . $fileName) : '';
				// Fallback to default schema file if configured file does not exists or is of zero size
				if (!$fileName || !file_exists($absolutePath) || !filesize($absolutePath)) {
					$fileName = $this->htmlAreaRTE->getFullFileName('EXT:' . $this->ID . '/extensions/MicrodataSchema/res/schemaOrgAll.rdf');
				}
				$rdf = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($fileName);
				if ($rdf) {
					$this->parseSchema($rdf, $schema);
				}
			}
		}
		uasort($schema['types'], array($this, 'compareLabels'));
		uasort($schema['properties'], array($this, 'compareLabels'));
		// Insert no type and no property entries
		if ($this->htmlAreaRTE->is_FE()) {
			$noSchema = $GLOBALS['TSFE']->getLLL('No type', $this->LOCAL_LANG);
			$noProperty = $GLOBALS['TSFE']->getLLL('No property', $this->LOCAL_LANG);
		} else {
			$noSchema = $GLOBALS['LANG']->getLL('No type');
			$noProperty = $GLOBALS['LANG']->getLL('No property');
		}
		array_unshift($schema['types'], array('name' => 'none', 'label' => $noSchema));
		array_unshift($schema['properties'], array('name' => 'none', 'label' => $noProperty));
		// Convert character set
		if ($this->htmlAreaRTE->is_FE()) {
			$GLOBALS['TSFE']->csConvObj->convArray($schema, $this->htmlAreaRTE->outputCharset, 'utf-8');
		}
		// Store json encoded array in temporary file
		$registerRTEinJavascriptString = LF . TAB . 'RTEarea[editornumber].schemaUrl = "' . ($this->htmlAreaRTE->is_FE() && $GLOBALS['TSFE']->absRefPrefix ? $GLOBALS['TSFE']->absRefPrefix : '') . $this->htmlAreaRTE->writeTemporaryFile('', ('schema_' . $this->htmlAreaRTE->language), 'js', json_encode($schema), TRUE) . '";';
		return $registerRTEinJavascriptString;
	}

	/**
	 * Compare the labels of two schema types or properties for localized sort purposes
	 *
	 * @param array $a: first type/property definition array
	 * @param array $b: second type/property definition array
	 * @return int
	 */
	protected function compareLabels($a, $b) {
		return strcoll($a['label'], $b['label']);
	}

	/**
	 * Convert the xml rdf schema into an array
	 *
	 * @param string $string XML rdf schema to convert into an array
	 * @param array	$schema: reference to the array to be filled
	 * @return void
	 */
	protected function parseSchema($string, &$schema) {
		$resources = array();
		$types = array();
		$properties = array();
		// Load the document
		$document = new \DOMDocument();
		$document->loadXML($string);
		if ($document) {
			// Scan resource descriptions
			$items = $document->getElementsByTagName('Description');
			$itemsCount = $items->length;
			foreach ($items as $item) {
				$name = $item->getAttribute('rdf:about');
				$type = $item->getElementsByTagName('type');
				if ($name && $type->length) {
					$type = $type->item(0)->getAttribute('rdf:resource');
					$resource = array();
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
						$break;
					}
				}
			}
		}
	}

}


?>