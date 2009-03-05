<?php

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/View/TX_EXTMVC_View_ViewInterface.php');

/**
 * An abstract View
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class TX_EXTMVC_View_AbstractView implements TX_EXTMVC_View_ViewInterface {

	/**
	 * @var TX_EXTMVC_Request
	 */
	protected $request;

	/**
	 * @var array of TX_EXTMVC_View_Helper_HelperInterface
	 */
	protected $viewHelpers;

	/**
	 * @var array
	 */
	protected $contextVariables = array();

	/**
	 * @var string
	 */
	protected $languagePath = 'Resources/Language/';

	/**
	 * Local Language content
	 *
	 * @var string
	 **/
	protected $LOCAL_LANG = array();

	/**
	 * Local Language content charset for individual labels (overriding)
	 *
	 * @var string
	 **/
	protected $LOCAL_LANG_charset = array();

	/**
	 * Key of the language to use
	 *
	 * @var string
	 **/
	protected $languageKey = 'default';

	/**
	 * Pointer to alternative fall-back language to use
	 *
	 * @var string
	 **/
	protected $LLkey = 'default';
	var $altLLkey='';			// .
	var $LLtestPrefix='';			// You can set this during development to some value that makes it easy for you to spot all labels that ARe delivered by the getLL function.
	var $LLtestPrefixAlt='';		// Save as LLtestPrefix, but additional prefix for the alternative value in getLL() function calls

	/**
	 * Constructs the view
	 */
	public function __construct() {
	}

	/**
	 * Initializes the view after all dependencies have been injected
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->initializeView();
		$this->initializeLocalization();
	}

	/**
	 * Sets the current request
	 *
	 * @param TX_EXTMVC_Request $request
	 * @return void
	 */
	public function setRequest(TX_EXTMVC_Request $request) {
		$this->request = $request;
	}

	/**
	 * Returns an View Helper instance.
	 * View Helpers must implement the interface TX_EXTMVC_View_Helper_HelperInterface
	 *
	 * @param string $viewHelperClassName the full name of the View Helper Class including 
	 * @return TX_EXTMVC_View_Helper_HelperInterface The View Helper instance
	 */
	public function getViewHelper($viewHelperClassName) {
		if (!isset($this->viewHelpers[$viewHelperClassName])) {
			$viewHelper = $this->objectManager->getObject($viewHelperClassName);
			if (!$viewHelper instanceof TX_EXTMVC_View_Helper_HelperInterface) {
				throw new TX_EXTMVC_Exception_InvalidViewHelper('View Helpers must implement interface "TX_EXTMVC_View_Helper_HelperInterface"', 1222895456);
			}
			$viewHelper->setRequest($this->request);
			$this->viewHelpers[$viewHelperClassName] = $viewHelper;
		}
		return $this->viewHelpers[$viewHelperClassName];
	}

	/**
	 * Initializes this view.
	 *
	 * Override this method for initializing your concrete view implementation.
	 *
	 * @return void
	 */
	protected function initializeView() {
	}
	
	/**
	 * Loads local-language values by looking for a "locallang.php" file in the plugin class directory ($this->scriptRelPath) and if found includes it.
	 * Also locallang values set in the TypoScript property "_LOCAL_LANG" are merged onto the values found in the "locallang.php" file.
	 *
	 * @return	void
	 */
	protected function initializeLocalization()	{
			$languageFilePath = t3lib_extMgm::extPath(strtolower($this->request->getControllerExtensionKey())) . $this->languagePath . 'locallang.php';

			if ($GLOBALS['TSFE']->config['config']['language'])	{
				$this->languageKey = $GLOBALS['TSFE']->config['config']['language'];
				if ($GLOBALS['TSFE']->config['config']['language_alt'])	{
					$this->alternativeLanguageKey = $GLOBALS['TSFE']->config['config']['language_alt'];
				}
			}

			// Read the strings in the required charset (since TYPO3 4.2)
			$this->LOCAL_LANG = t3lib_div::readLLfile($languageFilePath, $this->languageKey, $GLOBALS['TSFE']->renderCharset);
			if ($this->alternativeLanguageKey)	{
				$tempLOCAL_LANG = t3lib_div::readLLfile($languageFilePath, $this->alternativeLanguageKey);
				$this->LOCAL_LANG = array_merge(is_array($this->LOCAL_LANG) ? $this->LOCAL_LANG : array(), $tempLOCAL_LANG);
			}

			// TODO Overlaying labels from TypoScript (including fictitious language keys for non-system languages!):
			if (is_array($this->conf['_LOCAL_LANG.']))	{
				reset($this->conf['_LOCAL_LANG.']);
				while(list($k,$lA)=each($this->conf['_LOCAL_LANG.']))	{
					if (is_array($lA))	{
						$k = substr($k,0,-1);
						foreach($lA as $llK => $llV)	{
							if (!is_array($llV))	{
								$this->LOCAL_LANG[$k][$llK] = $llV;
									// For labels coming from the TypoScript (database) the charset is assumed to be "forceCharset" and if that is not set, assumed to be that of the individual system languages
								$this->LOCAL_LANG_charset[$k][$llK] = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : $GLOBALS['TSFE']->csConvObj->charSetArray[$k];
							}
						}
					}
				}
			}
	}
	
	/**
	 * Returns the localized label of the LOCAL_LANG key, $key
	 * Notice that for debugging purposes prefixes for the output values can be set with the internal vars ->LLtestPrefixAlt and ->LLtestPrefix
	 *
	 * @param	string		The key from the LOCAL_LANG array for which to return the value.
	 * @param	string		Alternative string to return IF no value is found set for the key, neither for the local language nor the default.
	 * @param	boolean		If true, the output label is passed through htmlspecialchars()
	 * @return	string		The value from LOCAL_LANG.
	 */
	function translate($key, $default = '', $hsc=FALSE)	{
		// The "from" charset of csConv() is only set for strings from TypoScript via _LOCAL_LANG
		if (isset($this->LOCAL_LANG[$this->languageKey][$key]))	{
			$translation = $GLOBALS['TSFE']->csConv($this->LOCAL_LANG[$this->languageKey][$key], $this->LOCAL_LANG_charset[$this->languageKey][$key]);
		} elseif ($this->alternativeLanguageKey && isset($this->LOCAL_LANG[$this->alternativeLanguageKey][$key]))	{
			$translation = $GLOBALS['TSFE']->csConv($this->LOCAL_LANG[$this->alternativeLanguageKey][$key], $this->LOCAL_LANG_charset[$this->alternativeLanguageKey][$key]);
		} elseif (isset($this->LOCAL_LANG['default'][$key]))	{
			$translation = $this->LOCAL_LANG['default'][$key];	// No charset conversion because default is english and thereby ASCII
		} else {
			$translation = $default;
		}

		return $hsc ? htmlspecialchars($translation) : $translation;
	}
	

	/**
	 * Assigns domain models (single objects or aggregates) or values to the view
	 *
	 * @param string $valueName The name of the value
	 * @param mixed $value the value to assign
	 * @return void
	 */
	public function assign($key, $value) {
		$this->contextVariables[strtolower($key)] = $value;
	}

}

?>
