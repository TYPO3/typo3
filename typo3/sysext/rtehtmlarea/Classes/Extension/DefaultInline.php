<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

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
/**
 * DefaultInline plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class DefaultInline extends \TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi {

	protected $extensionKey = 'rtehtmlarea';

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'DefaultInline';

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = 'extensions/DefaultInline/locallang.xlf';

	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/DefaultInline/skin/htmlarea.css';

	// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;

	// Reference to the invoking object
	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Reference to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array
	protected $pluginButtons = 'bold,italic,strikethrough,subscript,superscript,underline';

	protected $convertToolbarForHtmlAreaArray = array(
		'bold' => 'Bold',
		'italic' => 'Italic',
		'underline' => 'Underline',
		'strikethrough' => 'StrikeThrough',
		'superscript' => 'Superscript',
		'subscript' => 'Subscript'
	);

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param 	integer		Relative id of the RTE editing area in the form
	 * @return string		JS configuration for registered plugins
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		global $TSFE, $LANG;
		$registerRTEinJavascriptString = '';
		return $registerRTEinJavascriptString;
	}

	/**
	 * Return tranformed content
	 *
	 * @param 	string		$content: The content that is about to be sent to the RTE
	 * @return 	string		the transformed content
	 */
	public function transformContent($content) {
		// Change the strong and em tags for gecko browsers
		if ($this->htmlAreaRTE->client['browser'] == 'gecko') {
			// change <strong> to <b>
			$content = preg_replace('/<(\\/?)strong/i', '<$1b', $content);
			// change <em> to <i>
			$content = preg_replace('/<(\\/?)em([^b>]*>)/i', '<$1i$2', $content);
		}
		return $content;
	}

}
