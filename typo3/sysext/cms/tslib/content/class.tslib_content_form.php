<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010 Steffen Kamper <steffen@typo3.org>
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
 * Contains FORM class object.
 *
 * $Id: class.tslib_content.php 7905 2010-06-13 14:42:33Z ohader $
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class tslib_content_Form extends tslib_content_Abstract {

	/**
	 * Rendering the cObject, FORM
	 *
	 * Note on $formData:
	 * In the optional $formData array each entry represents a line in the ordinary setup.
	 * In those entries each entry (0,1,2...) represents a space normally divided by the '|' line.
	 *
	 * $formData [] = array('Name:', 'name=input, 25 ', 'Default value....');
	 * $formData [] = array('Email:', 'email=input, 25 ', 'Default value for email....');
	 *
	 * - corresponds to the $conf['data'] value being :
	 * Name:|name=input, 25 |Default value....||Email:|email=input, 25 |Default value for email....
	 *
	 * If $formData is an array the value of $conf['data'] is ignored.
	 *
	 * @param	array		Array of TypoScript properties
	 * @param	array		Alternative formdata overriding whatever comes from TypoScript
	 * @return	string		Output
	 */
	public function render($conf = array(), $formData = '') {
		$content = '';
		if (is_array($formData)) {
			$dataArr = $formData;
		} else {
			$data = $this->cObj->stdWrap($conf['data'], $conf['data.']);
				// Clearing dataArr
			$dataArr = array();
				// Getting the original config
			if (trim($data)) {
				$data = str_replace(LF, '||', $data);
				$dataArr = explode('||', $data);
			}
				// Adding the new dataArray config form:
			if (is_array($conf['dataArray.'])) { // dataArray is supplied
				$sKeyArray = t3lib_TStemplate::sortedKeyList($conf['dataArray.'], TRUE);
				foreach ($sKeyArray as $theKey) {
					$dAA = $conf['dataArray.'][$theKey . '.'];
					if (is_array($dAA)) {
						$temp = array();
						list ($temp[0]) = explode('|', $dAA['label.'] ? $this->cObj->stdWrap($dAA['label'], $dAA['label.']) : $dAA['label']);
						list ($temp[1]) = explode('|', $dAA['type']);
						if ($dAA['required']) {
							$temp[1] = '*' . $temp[1];
						}
						list ($temp[2]) = explode('|', $dAA['value.'] ? $this->cObj->stdWrap($dAA['value'], $dAA['value.']) : $dAA['value']);
							// If value array is set, then implode those values.
						if (is_array($dAA['valueArray.'])) {
							$temp_accum = array();
							foreach ($dAA['valueArray.'] as $dAKey_vA => $dAA_vA) {
								if (is_array($dAA_vA) && !strcmp(intval($dAKey_vA) . '.', $dAKey_vA)) {
									$temp_vA = array();
									list ($temp_vA[0]) = explode('=', $dAA_vA['label.'] ? $this->cObj->stdWrap($dAA_vA['label'], $dAA_vA['label.']) : $dAA_vA['label']);
									if ($dAA_vA['selected']) {
										$temp_vA[0] = '*' . $temp_vA[0];
									}
									list ($temp_vA[1]) = explode(',', $dAA_vA['value']);
								}
								$temp_accum[] = implode('=', $temp_vA);
							}
							$temp[2] = implode(',', $temp_accum);
						}
						list ($temp[3]) = explode('|', $dAA['specialEval.'] ? $this->cObj->stdWrap($dAA['specialEval'], $dAA['specialEval.']) : $dAA['specialEval']);

							// adding the form entry to the dataArray
						$dataArr[] = implode('|', $temp);
					}
				}
			}
		}

		$attachmentCounter = '';
		$hiddenfields = '';
		$fieldlist = array();
		$propertyOverride = array();
		$fieldname_hashArray = array();
		$cc = 0;

		$xhtmlStrict = t3lib_div::inList('xhtml_strict,xhtml_11,xhtml_2', $GLOBALS['TSFE']->xhtmlDoctype);
			// Formname
		if ($conf['formName']) {
			$formname = $this->cObj->cleanFormName($conf['formName']);
		} else {
			$formname = $GLOBALS['TSFE']->uniqueHash();
			$formname = 'a' . $formname; // form name has to start with a letter to reach XHTML compliance
		}

		if (isset($conf['fieldPrefix'])) {
			if ($conf['fieldPrefix']) {
				$prefix = $this->cObj->cleanFormName($conf['fieldPrefix']);
			} else {
				$prefix = '';
			}
		} else {
			$prefix = $formname;
		}

		foreach ($dataArr as $val) {

			$cc++;
			$confData = array();
			if (is_array($formData)) {
				$parts = $val;
				$val = 1; // TRUE...
			} else {
				$val = trim($val);
				$parts = explode('|', $val);
			}
			if ($val && strcspn($val, '#/')) {
					// label:
				$confData['label'] = trim($parts[0]);
					// field:
				$fParts = explode(',', $parts[1]);
				$fParts[0] = trim($fParts[0]);
				if (substr($fParts[0], 0, 1) == '*') {
					$confData['required'] = 1;
					$fParts[0] = substr($fParts[0], 1);
				}
				$typeParts = explode('=', $fParts[0]);
				$confData['type'] = trim(strtolower(end($typeParts)));
				if (count($typeParts) == 1) {
					$confData['fieldname'] = $this->cObj->cleanFormName($parts[0]);
					if (strtolower(preg_replace('/[^[:alnum:]]/', '', $confData['fieldname'])) == 'email') {
						$confData['fieldname'] = 'email';
					}
						// Duplicate fieldnames resolved
					if (isset($fieldname_hashArray[md5($confData['fieldname'])])) {
						$confData['fieldname'] .= '_' . $cc;
					}
					$fieldname_hashArray[md5($confData['fieldname'])] = $confData['fieldname'];
						// Attachment names...
					if ($confData['type'] == 'file') {
						$confData['fieldname'] = 'attachment' . $attachmentCounter;
						$attachmentCounter = intval($attachmentCounter) + 1;
					}
				} else {
					$confData['fieldname'] = str_replace(' ', '_', trim($typeParts[0]));
				}
				$fieldCode = '';

				if ($conf['wrapFieldName']) {
					$confData['fieldname'] = $this->cObj->wrap($confData['fieldname'], $conf['wrapFieldName']);
				}

					// Set field name as current:
				$this->cObj->setCurrentVal($confData['fieldname']);

					// Additional parameters
				if (trim($confData['type'])) {
					$addParams = trim($conf['params']);
					if (is_array($conf['params.']) && isset($conf['params.'][$confData['type']])) {
						$addParams = trim($conf['params.'][$confData['type']]);
					}
					if (strcmp('', $addParams)) {
						$addParams = ' ' . $addParams;
					}
				} else
					$addParams = '';

				if ($conf['dontMd5FieldNames']) {
					$fName = $confData['fieldname'];
				} else {
					$fName = md5($confData['fieldname']);
				}

					// Accessibility: Set id = fieldname attribute:
				if ($conf['accessibility'] || $xhtmlStrict) {
					$elementIdAttribute = ' id="' . $prefix . $fName . '"';
				} else {
					$elementIdAttribute = '';
				}

					// Create form field based on configuration/type:
				switch ($confData['type']) {
					case 'textarea' :
						$cols = trim($fParts[1]) ? intval($fParts[1]) : 20;
						$compWidth = doubleval($conf['compensateFieldWidth']
										? $conf['compensateFieldWidth']
										: $GLOBALS['TSFE']->compensateFieldWidth
									);
						$compWidth = $compWidth ? $compWidth : 1;
						$cols = t3lib_div::intInRange($cols * $compWidth, 1, 120);

						$rows = trim($fParts[2]) ? t3lib_div::intInRange($fParts[2], 1, 30) : 5;
						$wrap = trim($fParts[3]);
						if ($conf['noWrapAttr'] || $wrap === 'disabled') {
							$wrap = '';
						} else {
							$wrap = $wrap ? ' wrap="' . $wrap . '"' : ' wrap="virtual"';
						}
						$default = $this->cObj->getFieldDefaultValue(
							$conf['noValueInsert'],
							$confData['fieldname'],
							str_replace('\n', LF, trim($parts[2]))
						);
						$fieldCode = sprintf(
							'<textarea name="%s"%s cols="%s" rows="%s"%s%s>%s</textarea>',
							$confData['fieldname'],
							$elementIdAttribute,
							$cols,
							$rows,
							$wrap,
							$addParams,
							t3lib_div::formatForTextarea($default)
						);
					break;
					case 'input' :
					case 'password' :
						$size = trim($fParts[1]) ? intval($fParts[1]) : 20;
						$compWidth = doubleval($conf['compensateFieldWidth']
										? $conf['compensateFieldWidth']
										: $GLOBALS['TSFE']->compensateFieldWidth
									);
						$compWidth = $compWidth ? $compWidth : 1;
						$size = t3lib_div::intInRange($size * $compWidth, 1, 120);
						$default = $this->cObj->getFieldDefaultValue($conf['noValueInsert'], $confData['fieldname'], trim($parts[2]));

						if ($confData['type'] == 'password') {
							$default = '';
						}

						$max = trim($fParts[2]) ? ' maxlength="' . t3lib_div::intInRange($fParts[2], 1, 1000) . '"' : "";
						$theType = $confData['type'] == 'input' ? 'text' : 'password';

						$fieldCode = sprintf(
							'<input type="%s" name="%s"%s size="%s"%s value="%s"%s />',
							$theType,
							$confData['fieldname'],
							$elementIdAttribute,
							$size,
							$max,
							htmlspecialchars($default),
							$addParams
						);

					break;
					case 'file' :
						$size = trim($fParts[1]) ? t3lib_div::intInRange($fParts[1], 1, 60) : 20;
						$fieldCode = sprintf(
							'<input type="file" name="%s"%s size="%s"%s />',
							$confData['fieldname'],
							$elementIdAttribute,
							$size,
							$addParams
						);
					break;
					case 'check' :
							// alternative default value:
						$default = $this->cObj->getFieldDefaultValue($conf['noValueInsert'], $confData['fieldname'], trim($parts[2]));
						$checked = $default ? ' checked="checked"' : '';
						$fieldCode = sprintf(
							'<input type="checkbox" value="%s" name="%s"%s%s%s />',
							1,
							$confData['fieldname'],
							$elementIdAttribute,
							$checked,
							$addParams
						);
					break;
					case 'select' :
						$option = '';
						$valueParts = explode(',', $parts[2]);
							// size
						if (strtolower(trim($fParts[1])) == 'auto') {
							$fParts[1] = count($valueParts);
						} // Auto size set here. Max 20
						$size = trim($fParts[1]) ? t3lib_div::intInRange($fParts[1], 1, 20) : 1;
							// multiple
						$multiple = strtolower(trim($fParts[2])) == 'm' ? ' multiple="multiple"' : '';

						$items = array(); // Where the items will be
						$defaults = array(); //RTF
						$pCount = count($valueParts);
						for ($a = 0; $a < $pCount; $a++) {
							$valueParts[$a] = trim($valueParts[$a]);
							if (substr($valueParts[$a], 0, 1) == '*') { // Finding default value
								$sel = 'selected';
								$valueParts[$a] = substr($valueParts[$a], 1);
							} else
								$sel = '';
								// Get value/label
							$subParts = explode('=', $valueParts[$a]);
							$subParts[1] = (isset($subParts[1]) ? trim($subParts[1]) : trim($subParts[0])); // Sets the value
							$items[] = $subParts; // Adds the value/label pair to the items-array
							if ($sel) {
								$defaults[] = $subParts[1];
							} // Sets the default value if value/label pair is marked as default.
						}
							// alternative default value:
						$default = $this->cObj->getFieldDefaultValue($conf['noValueInsert'], $confData['fieldname'], $defaults);
						if (!is_array($default)) {
							$defaults = array();
							$defaults[] = $default;
						} else {
							$defaults = $default;
						}
							// Create the select-box:
						$iCount = count($items);
						for ($a = 0; $a < $iCount; $a++) {
							$option .= '<option value="' . $items[$a][1] . '"' . (in_array($items[$a][1], $defaults) ? ' selected="selected"' : '') . '>' . trim($items[$a][0]) . '</option>'; //RTF
						}

						if ($multiple) {
								// The fieldname must be prepended '[]' if multiple select. And the reason why it's prepended is, because the required-field list later must also have [] prepended.
							$confData['fieldname'] .= '[]';
						}
						$fieldCode = sprintf(
							'<select name="%s"%s size="%s"%s%s>%s</select>',
							$confData['fieldname'],
							$elementIdAttribute,
							$size,
							$multiple,
							$addParams,
							$option
						); //RTF
					break;
					case 'radio' :
						$option = '';

						$valueParts = explode(',', $parts[2]);
						$items = array(); // Where the items will be
						$default = '';
						$pCount = count($valueParts);
						for ($a = 0; $a < $pCount; $a++) {
							$valueParts[$a] = trim($valueParts[$a]);
							if (substr($valueParts[$a], 0, 1) == '*') {
								$sel = 'checked';
								$valueParts[$a] = substr($valueParts[$a], 1);
							} else
								$sel = '';
								// Get value/label
							$subParts = explode('=', $valueParts[$a]);
							$subParts[1] = (isset($subParts[1]) ? trim($subParts[1]) : trim($subParts[0])); // Sets the value
							$items[] = $subParts; // Adds the value/label pair to the items-array
							if ($sel) {
								$default = $subParts[1];
							} // Sets the default value if value/label pair is marked as default.
						}
							// alternative default value:
						$default = $this->cObj->getFieldDefaultValue($conf['noValueInsert'], $confData['fieldname'], $default);
							// Create the select-box:
						$iCount = count($items);
						for ($a = 0; $a < $iCount; $a++) {
							$optionParts = '';
							$radioId = $prefix . $fName . $this->cObj->cleanFormName($items[$a][0]);
							if ($conf['accessibility']) {
								$radioLabelIdAttribute = ' id="' . $radioId . '"';
							} else {
								$radioLabelIdAttribute = '';
							}
							$optionParts .= '<input type="radio" name="' . $confData['fieldname'] . '"' .
									$radioLabelIdAttribute . ' value="' . $items[$a][1] . '"' .
									(!strcmp($items[$a][1], $default) ? ' checked="checked"' : '') . $addParams . ' />';
							if ($conf['accessibility']) {
								$optionParts .= '<label for="' . $radioId . '">' . $this->cObj->stdWrap(trim($items[$a][0]),
									$conf['radioWrap.']) . '</label>';
							} else {
								$optionParts .= $this->cObj->stdWrap(trim($items[$a][0]), $conf['radioWrap.']);
							}
							$option .= $this->cObj->stdWrap($optionParts, $conf['radioInputWrap.']);
						}

						if ($conf['accessibility']) {
							$accessibilityWrap = $conf['radioWrap.']['accessibilityWrap'];

							$search = array(
								'###RADIO_FIELD_ID###', '###RADIO_GROUP_LABEL###'
							);
							$replace = array(
								$elementIdAttribute, $confData['label']
							);
							$accessibilityWrap = str_replace($search, $replace, $accessibilityWrap);

							$option = $this->cObj->wrap($option, $accessibilityWrap);
						}

						$fieldCode = $option;
					break;
					case 'hidden' :
						$value = trim($parts[2]);

							// If this form includes an auto responder message, include a HMAC checksum field
							// in order to verify potential abuse of this feature.
						if (strlen($value) && t3lib_div::inList($confData['fieldname'], 'auto_respond_msg')) {
							$hmacChecksum = t3lib_div::hmac($value);
							$hiddenfields .= sprintf(
								'<input type="hidden" name="auto_respond_checksum" id="%sauto_respond_checksum" value="%s" />',
								$prefix,
								$hmacChecksum
							);
						}

						if (strlen($value) && t3lib_div::inList('recipient_copy,recipient',
							$confData['fieldname']) && $GLOBALS['TYPO3_CONF_VARS']['FE']['secureFormmail']) {
							break;
						}
						if (strlen($value) && t3lib_div::inList('recipient_copy,recipient', $confData['fieldname'])) {
							$value = $GLOBALS['TSFE']->codeString($value);
						}
						$hiddenfields .= sprintf(
							'<input type="hidden" name="%s"%s value="%s" />',
							$confData['fieldname'],
							$elementIdAttribute,
							htmlspecialchars($value)
						);
					break;
					case 'property' :
						if (t3lib_div::inList('type,locationData,goodMess,badMess,emailMess', $confData['fieldname'])) {
							$value = trim($parts[2]);
							$propertyOverride[$confData['fieldname']] = $value;
							$conf[$confData['fieldname']] = $value;
						}
					break;
					case 'submit' :
						$value = trim($parts[2]);
						if ($conf['image.']) {
							$this->cObj->data[$this->cObj->currentValKey] = $value;
							$image = $this->cObj->IMG_RESOURCE($conf['image.']);
							$params = $conf['image.']['params'] ? ' ' . $conf['image.']['params'] : '';
							$params .= $this->cObj->getAltParam($conf['image.'], FALSE);
							$params .= $addParams;
						} else {
							$image = '';
						}
						if ($image) {
							$fieldCode = sprintf(
								'<input type="image" name="%s"%s src="%s"%s />',
								$confData['fieldname'],
								$elementIdAttribute,
								$image,
								$params
							);
						} else {
							$fieldCode = sprintf(
								'<input type="submit" name="%s"%s value="%s"%s />',
								$confData['fieldname'],
								$elementIdAttribute,
								t3lib_div::deHSCentities(htmlspecialchars($value)),
								$addParams
							);
						}
					break;
					case 'reset' :
						$value = trim($parts[2]);
						$fieldCode = sprintf(
							'<input type="reset" name="%s"%s value="%s"%s />',
							$confData['fieldname'],
							$elementIdAttribute,
							t3lib_div::deHSCentities(htmlspecialchars($value)),
							$addParams
						);
					break;
					case 'label' :
						$fieldCode = nl2br(htmlspecialchars(trim($parts[2])));
					break;
					default :
						$confData['type'] = 'comment';
						$fieldCode = trim($parts[2]) . '&nbsp;';
					break;
				}
				if ($fieldCode) {

						// Checking for special evaluation modes:
					if (t3lib_div::inList('textarea,input,password', $confData['type']) && strlen(trim($parts[3]))) {
						$modeParameters = t3lib_div::trimExplode(':', $parts[3]);
					} else {
						$modeParameters = array();
					}

					// Adding evaluation based on settings:
					switch ((string) $modeParameters[0]) {
						case 'EREG' :
							$fieldlist[] = '_EREG';
							$fieldlist[] = $modeParameters[1];
							$fieldlist[] = $modeParameters[2];
							$fieldlist[] = $confData['fieldname'];
							$fieldlist[] = $confData['label'];
							$confData['required'] = 1; // Setting this so "required" layout is used.
						break;
						case 'EMAIL' :
							$fieldlist[] = '_EMAIL';
							$fieldlist[] = $confData['fieldname'];
							$fieldlist[] = $confData['label'];
							$confData['required'] = 1; // Setting this so "required" layout is used.
						break;
						default :
							if ($confData['required']) {
								$fieldlist[] = $confData['fieldname'];
								$fieldlist[] = $confData['label'];
							}
						break;
					}

						// Field:
					$fieldLabel = $confData['label'];
					if ($conf['accessibility'] && trim($fieldLabel) && !preg_match('/^(label|hidden|comment)$/', $confData['type'])) {
						$fieldLabel = '<label for="' . $prefix . $fName . '">' . $fieldLabel . '</label>';
					}

						// Getting template code:
					$fieldCode = $this->cObj->stdWrap($fieldCode, $conf['fieldWrap.']);
					$labelCode = $this->cObj->stdWrap($fieldLabel, $conf['labelWrap.']);
					$commentCode = $this->cObj->stdWrap($confData['label'], $conf['commentWrap.']); // RTF
					$result = $conf['layout'];
					if ($conf['REQ'] && $confData['required']) {
						if (is_array($conf['REQ.']['fieldWrap.']))
							$fieldCode = $this->cObj->stdWrap($fieldCode, $conf['REQ.']['fieldWrap.']);
						if (is_array($conf['REQ.']['labelWrap.']))
							$labelCode = $this->cObj->stdWrap($fieldLabel, $conf['REQ.']['labelWrap.']);
						if ($conf['REQ.']['layout']) {
							$result = $conf['REQ.']['layout'];
						}
					}
					if ($confData['type'] == 'comment' && $conf['COMMENT.']['layout']) {
						$result = $conf['COMMENT.']['layout'];
					}
					if ($confData['type'] == 'check' && $conf['CHECK.']['layout']) {
						$result = $conf['CHECK.']['layout'];
					}
					if ($confData['type'] == 'radio' && $conf['RADIO.']['layout']) {
						$result = $conf['RADIO.']['layout'];
					}
					if ($confData['type'] == 'label' && $conf['LABEL.']['layout']) {
						$result = $conf['LABEL.']['layout'];
					}
					$result = str_replace('###FIELD###', $fieldCode, $result);
					$result = str_replace('###LABEL###', $labelCode, $result);
					$result = str_replace('###COMMENT###', $commentCode, $result); //RTF
					$content .= $result;
				}
			}
		}
		if ($conf['stdWrap.']) {
			$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
		}


			// redirect (external: where to go afterwards. internal: where to submit to)
		$theRedirect = $this->cObj->stdWrap($conf['redirect'], $conf['redirect.']); // redirect should be set to the page to redirect to after an external script has been used. If internal scripts is used, and if no 'type' is set that dictates otherwise, redirect is used as the url to jump to as long as it's an integer (page)
		$page = $GLOBALS['TSFE']->page;
		if (!$theRedirect) { // Internal: Just submit to current page
			$LD = $GLOBALS['TSFE']->tmpl->linkData(
				$page,
				$conf['target'],
				$conf['no_cache'],
				'index.php',
				'',
				$this->cObj->getClosestMPvalueForPage($page['uid'])
			);
		} elseif (t3lib_div::testInt($theRedirect)) { // Internal: Submit to page with ID $theRedirect
			$page = $GLOBALS['TSFE']->sys_page->getPage_noCheck($theRedirect);
			$LD = $GLOBALS['TSFE']->tmpl->linkData(
				$page,
				$conf['target'],
				$conf['no_cache'],
				'index.php',
				'',
				$this->cObj->getClosestMPvalueForPage($page['uid'])
			);
		} else { // External URL, redirect-hidden field is rendered!
			$LD = $GLOBALS['TSFE']->tmpl->linkData(
				$page,
				$conf['target'],
				$conf['no_cache'],
				'',
				'',
				$this->cObj->getClosestMPvalueForPage($page['uid'])
			);
			$LD['totalURL'] = $theRedirect;
			$hiddenfields .= '<input type="hidden" name="redirect" value="' . htmlspecialchars($LD['totalURL']) . '" />'; // 18-09-00 added
		}

			// Formtype (where to submit to!):
		$formtype = $propertyOverride['type'] ? $propertyOverride['type'] : $this->cObj->stdWrap($conf['type'], $conf['type.']);
		if (t3lib_div::testInt($formtype)) { // Submit to a specific page
			$page = $GLOBALS['TSFE']->sys_page->getPage_noCheck($formtype);
			$LD_A = $GLOBALS['TSFE']->tmpl->linkData(
				$page,
				$conf['target'],
				$conf['no_cache'],
				'',
				'',
				$this->cObj->getClosestMPvalueForPage($page['uid'])
			);
			$action = $LD_A['totalURL'];
		} elseif ($formtype) { // Submit to external script
			$LD_A = $LD;
			$action = $formtype;
		} elseif (t3lib_div::testInt($theRedirect)) {
			$LD_A = $LD;
			$action = $LD_A['totalURL'];
		} else { // Submit to "nothing" - which is current page
			$LD_A = $GLOBALS['TSFE']->tmpl->linkData(
				$GLOBALS['TSFE']->page,
				$conf['target'],
				$conf['no_cache'],
				'',
				'',
				$this->cObj->getClosestMPvalueForPage($page['uid'])
			);
			$action = $LD_A['totalURL'];
		}

			// Recipient:
		$theEmail = $this->cObj->stdWrap($conf['recipient'], $conf['recipient.']);
		if ($theEmail && !$GLOBALS['TYPO3_CONF_VARS']['FE']['secureFormmail']) {
			$theEmail = $GLOBALS['TSFE']->codeString($theEmail);
			$hiddenfields .= '<input type="hidden" name="recipient" value="' . htmlspecialchars($theEmail) . '" />';
		}

			// location data:
		if ($conf['locationData']) {
			if ($conf['locationData'] == 'HTTP_POST_VARS' && isset($_POST['locationData'])) {
				$locationData = t3lib_div::_POST('locationData');
			} else {
					// locationData is [hte page id]:[tablename]:[uid of record]. Indicates on which page the record (from tablename with uid) is shown. Used to check access.
				$locationData = $GLOBALS['TSFE']->id . ':' . $this->cObj->currentRecord;
			}
			$hiddenfields .= '<input type="hidden" name="locationData" value="' . htmlspecialchars($locationData) . '" />';
		}

			// hidden fields:
		if (is_array($conf['hiddenFields.'])) {
			foreach ($conf['hiddenFields.'] as $hF_key => $hF_conf) {
				if (substr($hF_key, -1) != '.') {
					$hF_value = $this->cObj->cObjGetSingle($hF_conf, $conf['hiddenFields.'][$hF_key . '.'], 'hiddenfields');
					if (strlen($hF_value) && t3lib_div::inList('recipient_copy,recipient', $hF_key)) {
						if ($GLOBALS['TYPO3_CONF_VARS']['FE']['secureFormmail']) {
							continue;
						}
						$hF_value = $GLOBALS['TSFE']->codeString($hF_value);
					}
					$hiddenfields .= '<input type="hidden" name="' . $hF_key . '" value="' . htmlspecialchars($hF_value) . '" />';
				}
			}
		}

			// Wrap all hidden fields in a div tag (see http://bugs.typo3.org/view.php?id=678)
		$hiddenfields = '<div style="display:none;">' . $hiddenfields . '</div>';

		if ($conf['REQ']) {
			$validateForm = ' onsubmit="return validateForm(\'' . $formname . '\',\'' . implode(',', $fieldlist)
				. '\',' . t3lib_div::quoteJSvalue($conf['goodMess']) . ',' .
				t3lib_div::quoteJSvalue($conf['badMess']) . ',' .
				t3lib_div::quoteJSvalue($conf['emailMess']) . ')"';
			$GLOBALS['TSFE']->additionalHeaderData['JSFormValidate'] = '<script type="text/javascript" src="' .
				t3lib_div::createVersionNumberedFilename($GLOBALS['TSFE']->absRefPrefix .
				't3lib/jsfunc.validateform.js') . '"></script>';
		} else {
			$validateForm = '';
		}

			// Create form tag:
		$theTarget = ($theRedirect ? $LD['target'] : $LD_A['target']);
		$content = array(
			'<form' . ' action="' . htmlspecialchars($action) . '"' . ' id="' .
			$formname . '"' . ($xhtmlStrict ? '' : ' name="' . $formname . '"') .
			' enctype="' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'] . '"' .
			' method="' . ($conf['method'] ? $conf['method'] : 'post') . '"' .
			($theTarget ? ' target="' . $theTarget . '"' : '') .
			$validateForm . '>', $hiddenfields . $content,
			'</form>'
		);

		if ($conf['arrayReturnMode']) {
			$content['validateForm'] = $validateForm;
			$content['formname'] = $formname;
			return $content;
		} else {
			return implode('', $content);
		}
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_form.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_form.php']);
}

?>
