<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

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

use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;

/**
 * DefaultInline plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
class DefaultInline extends RteHtmlAreaApi {

	/**
	 * The name of the plugin registered by the extension
	 *
	 * @var string
	 */
	protected $pluginName = 'DefaultInline';

	/**
	 * Path to this main locallang file of the extension relative to the extension directory
	 *
	 * @var string
	 */
	protected $relativePathToLocallangFile = 'extensions/DefaultInline/locallang.xlf';

	/**
	 * Path to the skin file relative to the extension directory
	 *
	 * @var string
	 */
	protected $relativePathToSkin = 'Resources/Public/Css/Skin/Plugins/default-inline.css';

	/**
	 * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
	 *
	 * @var string
	 */
	protected $pluginButtons = 'bold,italic,strikethrough,subscript,superscript,underline';

	/**
	 * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
	 *
	 * @var array
	 */
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
	 * @param string $rteNumberPlaceholder A dummy string for JS arrays
	 * @return string JS configuration for registered plugins
	 */
	public function buildJavascriptConfiguration($rteNumberPlaceholder) {
		return '';
	}

	/**
	 * Return tranformed content
	 *
	 * @param string $content: The content that is about to be sent to the RTE
	 * @return string the transformed content
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
