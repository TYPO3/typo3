<?php
namespace TYPO3\CMS\Rtehtmlarea\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Stanislas Rolland <stanislas.rolland@typo3.org>
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
 * Class for processing of the FAL soft references on img tags inserted in RTE content
 *
 * @author Stanislas Rolland <stanislas.rolland@typo3.org>
 */
class SoftReferenceHook extends \TYPO3\CMS\Core\Database\SoftReferenceIndex {

	// Token prefix
	public $tokenID_basePrefix = '';

	/**
	 * Main function through which all processing happens
	 *
	 * @param string Database table name
	 * @param string Field name for which processing occurs
	 * @param integer UID of the record
	 * @param string The content/value of the field
	 * @param string The softlink parser key. This is only interesting if more than one parser is grouped in the same class. That is the case with this parser.
	 * @param array Parameters of the softlink parser. Basically this is the content inside optional []-brackets after the softref keys. Parameters are exploded by ";
	 * @param string If running from inside a FlexForm structure, this is the path of the tag.
	 * @return array Result array on positive matches. Otherwise FALSE
	 * @todo Define visibility
	 */
	public function findRef($table, $field, $uid, $content, $spKey, $spParams, $structurePath = '') {
		$retVal = FALSE;
		$this->tokenID_basePrefix = $table . ':' . $uid . ':' . $field . ':' . $structurePath . ':' . $spKey;
		switch ($spKey) {
		case 'rtehtmlarea_images':
			$retVal = $this->findRef_rtehtmlarea_images($content, $spParams);
			break;
		default:
			$retVal = FALSE;
			break;
		}
		return $retVal;
	}

	/**
	 * Finding image tags with data-htmlarea-file-uid attribute in the content.
	 * All images that have an data-htmlarea-file-uid attribute will be returned with an info text
	 *
	 * @param 	string		The input content to analyse
	 * @param 	array		Parameters set for the softref parser key in TCA/columns
	 * @return 	array		Result array on positive matches, see description above. Otherwise FALSE
	 * @todo Define visibility
	 */
	public function findRef_rtehtmlarea_images($content, $spParams) {
		$retVal = FALSE;
		// Start HTML parser and split content by image tag
		$htmlParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\HtmlParser');
		$imgTags = $htmlParser->splitTags('img', $content);
		$elements = array();
		// Traverse splitted parts
		foreach ($imgTags as $k => $v) {
			if ($k % 2) {
				// Get FAL uid reference
				$attribs = $htmlParser->get_tag_attributes($v);
				$fileUid = $attribs[0]['data-htmlarea-file-uid'];
				$fileTable = $attribs[0]['data-htmlarea-file-table'];
				// If there is a file uid, continue. Otherwise ignore this img tag.
				if ($fileUid) {
					// Initialize the element entry with info text here
					$tokenID = $this->makeTokenID($k);
					$elements[$k] = array();
					$elements[$k]['matchString'] = $v;
					// Token and substitute value
					$imgTags[$k] = str_replace('data-htmlarea-file-uid="' . $fileUid . '"', 'data-htmlarea-file-uid="{softref:' . $tokenID . '}"', $imgTags[$k]);
					$elements[$k]['subst'] = array(
						'type' => 'db',
						'recordRef' => ($fileTable ? $fileTable : 'sys_file') . ':' . $fileUid,
						'tokenID' => $tokenID,
						'tokenValue' => $fileUid
					);
				}
			}
		}
		// Assemble result array
		if (count($elements)) {
			$retVal = array(
				'content' => implode('', $imgTags),
				'elements' => $elements
			);
		}
		return $retVal;
	}

}


?>