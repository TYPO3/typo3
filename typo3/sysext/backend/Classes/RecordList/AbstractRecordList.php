<?php
namespace TYPO3\CMS\Backend\RecordList;

/**
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Library with a single function addElement that returns table
 * rows based on some input.
 *
 * Base for class listing of database records and files in the
 * modules Web>List and File>Filelist
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @see typo3/db_list.php
 * @see typo3/sysext/filelist/mod1/index.php
 */
abstract class AbstractRecordList {

	/**
	 * default Max items shown
	 *
	 * @var int
	 */
	public $iLimit = 10;

	/**
	 * OBSOLETE - NOT USED ANYMORE. leftMargin
	 *
	 * @var int
	 */
	public $leftMargin = 0;

	/**
	 * @var int
	 */
	public $showIcon = 1;

	/**
	 * @var int
	 */
	public $no_noWrap = 0;

	/**
	 * If set this is <td> CSS-classname for odd columns in addElement. Used with db_layout / pages section
	 *
	 * @var string
	 */
	public $oddColumnsCssClass = '';

	/**
	 * @var string
	 */
	public $backPath = '';

	/**
	 * Decides the columns shown. Filled with values that refers to the keys of the data-array. $this->fieldArray[0] is the title column.
	 *
	 * @var array
	 */
	public $fieldArray = array();

	/**
	 * Keys are fieldnames and values are td-parameters to add in addElement(), please use $addElement_tdCSSClass for CSS-classes;
	 *
	 * @var array
	 */
	public $addElement_tdParams = array();

	/**
	 * Keys are fieldnames and values are td-css-classes to add in addElement();
	 *
	 * @var array
	 */
	public $addElement_tdCssClass = array();

	/**
	 * Not used in this class - but maybe extension classes...
	 * Max length of strings
	 *
	 * @var int
	 */
	public $fixedL = 30;

	/**
	 * Script URL
	 *
	 * @var string
	 */
	public $thisScript = '';

	/**
	 * Set to zero, if you don't want a left-margin with addElement function
	 *
	 * @var int
	 */
	public $setLMargin = 1;

	/**
	 * Counter increased for each element. Used to index elements for the JavaScript-code that transfers to the clipboard
	 *
	 * @var int
	 */
	public $counter = 0;

	/**
	 * This could be set to the total number of items. Used by the fwd_rew_navigation...
	 *
	 * @var string
	 */
	public $totalItems = '';

	/**
	 * Internal (used in this class.)
	 *
	 * @var int
	 */
	public $firstElementNumber = 0;

	/**
	 * @var int
	 */
	public $eCounter = 0;

	/**
	 * String with accumulated HTML content
	 *
	 * @var string
	 */
	public $HTMLcode = '';

	/**
	 * Contains page translation languages
	 *
	 * @var array
	 */
	public $pageOverlays = array();

	/**
	 * Contains sys language icons and titles
	 *
	 * @var array
	 */
	public $languageIconTitles = array();

	/**
	 * @var \TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider
	 */
	public $translateTools;

	/**
	 * Constructor
	 */
	public function __construct() {
		if (isset($GLOBALS['BE_USER']->uc['titleLen']) && $GLOBALS['BE_USER']->uc['titleLen'] > 0) {
			$this->fixedL = $GLOBALS['BE_USER']->uc['titleLen'];
		}
		$this->getTranslateTools();
	}

	/**
	 * Sets the script url depending on being a module or script request
	 */
	protected function determineScriptUrl() {
		if ($moduleName = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('M')) {
			$this->thisScript = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl($moduleName);
		} else {
			$this->thisScript = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME');
		}
	}

	/**
	 * @return string
	 */
	protected function getThisScript() {
		return strpos($this->thisScript, '?') === FALSE ? $this->thisScript . '?' : $this->thisScript . '&';
	}

	/**
	 * Returns a table-row with the content from the fields in the input data array.
	 * OBS: $this->fieldArray MUST be set! (represents the list of fields to display)
	 *
	 * @param int $h Is an integer >=0 and denotes how tall a element is. Set to '0' makes a halv line, -1 = full line, set to 1 makes a 'join' and above makes 'line'
	 * @param string $icon Is the <img>+<a> of the record. If not supplied the first 'join'-icon will be a 'line' instead
	 * @param array $data Is the dataarray, record with the fields. Notice: These fields are (currently) NOT htmlspecialchar'ed before being wrapped in <td>-tags
	 * @param string $tdParams Is insert in the <td>-tags. Must carry a ' ' as first character
	 * @param int OBSOLETE - NOT USED ANYMORE. $lMargin is the leftMargin (int)
	 * @param string $altLine Is the HTML <img>-tag for an alternative 'gfx/ol/line.gif'-icon (used in the top)
	 * @return string HTML content for the table row
	 */
	public function addElement($h, $icon, $data, $trParams = '', $lMargin = '', $altLine = '') {
		$noWrap = $this->no_noWrap ? '' : ' nowrap="nowrap"';
		// Start up:
		$out = '
		<!-- Element, begin: -->
		<tr ' . $trParams . '>';
		// Show icon and lines
		if ($this->showIcon) {
			$out .= '
			<td nowrap="nowrap" class="col-icon">';
			if (!$h) {
				$out .= '<img src="clear.gif" width="1" height="8" alt="" />';
			} else {
				for ($a = 0; $a < $h; $a++) {
					if (!$a) {
						if ($icon) {
							$out .= $icon;
						}
					} else {

					}
				}
			}
			$out .= '</td>
			';
		}
		// Init rendering.
		$colsp = '';
		$lastKey = '';
		$c = 0;
		$ccount = 0;
		// Traverse field array which contains the data to present:
		foreach ($this->fieldArray as $vKey) {
			if (isset($data[$vKey])) {
				if ($lastKey) {
					$cssClass = $this->addElement_tdCssClass[$lastKey];
					if ($this->oddColumnsCssClass && $ccount % 2 == 0) {
						$cssClass = implode(' ', array($this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass));
					}
					$out .= '
						<td' . $noWrap . ' class="' . $cssClass . '"' . $colsp . $this->addElement_tdParams[$lastKey] . '>' . $data[$lastKey] . '</td>';
				}
				$lastKey = $vKey;
				$c = 1;
				$ccount++;
			} else {
				if (!$lastKey) {
					$lastKey = $vKey;
				}
				$c++;
			}
			if ($c > 1) {
				$colsp = ' colspan="' . $c . '"';
			} else {
				$colsp = '';
			}
		}
		if ($lastKey) {
			$cssClass = $this->addElement_tdCssClass[$lastKey];
			if ($this->oddColumnsCssClass) {
				$cssClass = implode(' ', array($this->addElement_tdCssClass[$lastKey], $this->oddColumnsCssClass));
			}
			$out .= '
				<td' . $noWrap . ' class="' . $cssClass . '"' . $colsp . $this->addElement_tdParams[$lastKey] . '>' . $data[$lastKey] . '</td>';
		}
		// End row
		$out .= '
		</tr>';
		// Return row.
		return $out;
	}

	/**
	 * Dummy function, used to write the top of a table listing.
	 *
	 * @return void
	 */
	public function writeTop() {

	}

	/**
	 * Creates a forward/reverse button based on the status of ->eCounter, ->firstElementNumber, ->iLimit
	 *
	 * @param string $table Table name
	 * @return array array([boolean], [HTML]) where [boolean] is 1 for reverse element, [HTML] is the table-row code for the element
	 */
	public function fwd_rwd_nav($table = '') {
		$code = '';
		if ($this->eCounter >= $this->firstElementNumber && $this->eCounter < $this->firstElementNumber + $this->iLimit) {
			if ($this->firstElementNumber && $this->eCounter == $this->firstElementNumber) {
				// 	Reverse
				$theData = array();
				$titleCol = $this->fieldArray[0];
				$theData[$titleCol] = $this->fwd_rwd_HTML('fwd', $this->eCounter, $table);
				$code = $this->addElement(1, '', $theData, 'class="fwd_rwd_nav"');
			}
			return array(1, $code);
		} else {
			if ($this->eCounter == $this->firstElementNumber + $this->iLimit) {
				// 	Forward
				$theData = array();
				$titleCol = $this->fieldArray[0];
				$theData[$titleCol] = $this->fwd_rwd_HTML('rwd', $this->eCounter, $table);
				$code = $this->addElement(1, '', $theData, 'class="fwd_rwd_nav"');
			}
			return array(0, $code);
		}
	}

	/**
	 * Creates the button with link to either forward or reverse
	 *
	 * @param string $type Type: "fwd" or "rwd
	 * @param int $pointer Pointer
	 * @param string $table Table name
	 * @return string
	 * @access private
	 */
	public function fwd_rwd_HTML($type, $pointer, $table = '') {
		$content = '';
		$tParam = $table ? '&table=' . rawurlencode($table) : '';
		switch ($type) {
			case 'fwd':
				$href = $this->listURL() . '&pointer=' . ($pointer - $this->iLimit) . $tParam;
				$content = '<a href="' . htmlspecialchars($href) . '">' . IconUtility::getSpriteIcon('actions-move-up') . '</a> <i>[1 - ' . $pointer . ']</i>';
				break;
			case 'rwd':
				$href = $this->listURL() . '&pointer=' . $pointer . $tParam;
				$content = '<a href="' . htmlspecialchars($href) . '">' . IconUtility::getSpriteIcon('actions-move-down') . '</a> <i>[' . ($pointer + 1) . ' - ' . $this->totalItems . ']</i>';
				break;
		}
		return $content;
	}

	/**
	 * Creates the URL to this script, including all relevant GPvars
	 *
	 * @param string $altId Alternative id value. Enter blank string for the current id ($this->id)
	 * @return string URL
	 */
	public function listURL($altId = '') {
		return $this->getThisScript() . 'id=' . ($altId !== '' ? $altId : $this->id);
	}

	/**
	 * Returning JavaScript for ClipBoard functionality.
	 *
	 * @return string
	 */
	public function CBfunctions() {
		return '
		// checkOffCB()
	function checkOffCB(listOfCBnames, link) {	//
		var checkBoxes, flag, i;
		var checkBoxes = listOfCBnames.split(",");
		if (link.rel === "") {
			link.rel = "allChecked";
			flag = true;
		} else {
			link.rel = "";
			flag = false;
		}
		for (i = 0; i < checkBoxes.length; i++) {
			setcbValue(checkBoxes[i], flag);
		}
	}
		// cbValue()
	function cbValue(CBname) {	//
		var CBfullName = "CBC["+CBname+"]";
		return (document.dblistForm[CBfullName] && document.dblistForm[CBfullName].checked ? 1 : 0);
	}
		// setcbValue()
	function setcbValue(CBname,flag) {	//
		CBfullName = "CBC["+CBname+"]";
		if(document.dblistForm[CBfullName]) {
			document.dblistForm[CBfullName].checked = flag ? "on" : 0;
		}
	}

		';
	}

	/**
	 * Initializes page languages and icons
	 *
	 * @return void
	 */
	public function initializeLanguages() {
		// Look up page overlays:
		$this->pageOverlays = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'pages_language_overlay', 'pid=' . (int)$this->id . BackendUtility::deleteClause('pages_language_overlay') . BackendUtility::versioningPlaceholderClause('pages_language_overlay'), '', '', '', 'sys_language_uid');
		$this->languageIconTitles = $this->getTranslateTools()->getSystemLanguages($this->id, $this->backPath);
	}

	/**
	 * Return the icon for the language
	 *
	 * @param int $sys_language_uid Sys language uid
	 * @param bool $addAsAdditionalText If set to true, only the flag is returned
	 * @return string Language icon
	 */
	public function languageFlag($sys_language_uid, $addAsAdditionalText = TRUE) {
		$out = '';
		$title = htmlspecialchars($this->languageIconTitles[$sys_language_uid]['title']);
		if ($this->languageIconTitles[$sys_language_uid]['flagIcon']) {
			$out .= IconUtility::getSpriteIcon($this->languageIconTitles[$sys_language_uid]['flagIcon'], array('title' => $title));
			if (!$addAsAdditionalText) {
				return $out;
			}
			$out .= '&nbsp;';
		}
		$out .= $title;
		return $out;
	}

	/**
	 * Gets an instance of TranslationConfigurationProvider
	 *
	 * @return \TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider
	 */
	protected function getTranslateTools() {
		if (!isset($this->translateTools)) {
			$this->translateTools = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider::class);
		}
		return $this->translateTools;
	}

	/**
	 * Generates HTML code for a Reference tooltip out of
	 * sys_refindex records you hand over
	 *
	 * @param array $references array of records from sys_refindex table
	 * @param string $launchViewParameter JavaScript String, which will be passed as parameters to top.launchView
	 * @return string
	 */
	protected function generateReferenceToolTip(array $references, $launchViewParameter = '') {
		$result = array();
		foreach ($references as $reference) {
			$result[] = $reference['tablename'] . ':' . $reference['recuid'] . ':' . $reference['field'];
			if (strlen(implode(' / ', $result)) >= 100) {
				break;
			}
		}
		if (empty($result)) {
			$htmlCode = '-';
		} else {
			$htmlCode = '<a href="#"';
			if ($launchViewParameter !== '') {
				$htmlCode .= ' onclick="' . htmlspecialchars(('top.launchView(' . $launchViewParameter . '); return false;')) . '"';
			}
			$htmlCode .= ' title="' . htmlspecialchars(GeneralUtility::fixed_lgd_cs(implode(' / ', $result), 100)) . '">';
			$htmlCode .= count($references);
			$htmlCode .= '</a>';
		}
		return $htmlCode;
	}

}
