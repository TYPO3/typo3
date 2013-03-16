<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Stanislas Rolland <>
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
 * Contains FlowPlayer class object.
 *
 * @author Stanislas Rolland
 */
class FlowPlayerContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * File extension to mime type
	 */
	public $mimeTypes = array(
		'aif' => array(
			'audio' => 'audio/aiff'
		),
		'au' => array(
			'audio' => 'audio/x-au'
		),
		'avi' => array(
			'audio' => 'video/x-msvideo'
		),
		'asf' => array(
			'video' => 'video/x-ms-asf'
		),
		'class' => array(
			'audio' => 'application/java',
			'video' => 'application/java'
		),
		'dcr' => array(
			'video' => 'application/x-director'
		),
		'flac' => array(
			'audio' => 'audio/flac'
		),
		'flv' => array(
			'video' => 'video/x-flv'
		),
		'mp3' => array(
			'audio' => 'audio/mpeg'
		),
		'mp4' => array(
			'video' => 'video/mp4'
		),
		'oga' => array(
			'audio' => 'audio/ogg'
		),
		'ogg' => array(
			'audio' => 'audio/ogg',
			'video' => 'video/ogg'
		),
		'ogv' => array(
			'video' => 'video/ogg'
		),
		'swa' => array(
			'audio' => 'audio/x-m4a'
		),
		'mov' => array(
			'video' => 'video/quicktime'
		),
		'm4a' => array(
			'audio' => 'audio/mp4a-latm'
		),
		'm4v' => array(
			'video' => 'video/x-m4v'
		),
		'qt' => array(
			'video' => 'video/quicktime'
		),
		'swa' => array(
			'audio' => 'application/x-director'
		),
		'swf' => array(
			'audio' => 'application/x-shockwave-flash',
			'video' => 'application/x-shockwave-flash'
		),
		'wav' => array(
			'audio' => 'audio/wave'
		),
		'webm' => array(
			'audio' => 'audio/webm',
			'video' => 'video/webm'
		),
		'wmv' => array(
			'audio' => 'audio/x-ms-wmv'
		)
	);

	/**
	 * VideoJS options
	 */
	public $videoJsOptions = array(
		// Use the browser's controls (iPhone)
		'useBuiltInControls',
		// Display control bar below video vs. in front of
		'controlsBelow',
		// Make controls visible when page loads
		'controlsAtStart',
		// Hide controls when not over the video
		'controlsHiding',
		// Will be overridden by localStorage volume if available
		'defaultVolume',
		// Players and order to use them
		'playerFallbackOrder'
	);

	/**
	 * htlm5 tag attributes
	 */
	public $html5TagAttributes = array(
		'autoPlay',
		'controls',
		'loop',
		'preload'
	);

	/**
	 * Flowplayer captions plugin configuration
	 */
	public $flowplayerCaptionsConfig = array(
		'plugins' => array(
			// The captions plugin
			'captions' => array(
				'url' => 'flowplayer.captions-3.2.3.swf',
				// Pointer to a content plugin (see below)
				'captionTarget' => 'content'
			),
			// Configure a content plugin so that it looks good for showing captions
			'content' => array(
				'url' => 'flowplayer.content-3.2.0.swf',
				'bottom' => 5,
				'height' => 40,
				'backgroundColor' => 'transparent',
				'backgroundGradient' => 'none',
				'border' => 0,
				'textDecoration' => 'outline',
				'style' => array(
					'body' => array(
						'fontSize' => 14,
						'fontFamily' => 'Arial',
						'textAlign' => 'center',
						'color' => '#ffffff'
					)
				)
			)
		)
	);

	/**
	 * Flowplayer audio configuration
	 */
	public $flowplayerAudioConfig = array(
		'provider' => 'audio',
		'plugins' => array(
			'audio' => array(
				'url' => 'flowplayer.audio-3.2.2.swf'
			),
			'controls' => array(
				'autoHide' => FALSE,
				'fullscreen' => FALSE
			)
		)
	);

	/**
	 * Flowplayer configuration for the audio description
	 */
	public $flowplayerAudioDescriptionConfig = array(
		// The controls plugin
		'plugins' => array(
			'controls' => NULL
		)
	);

	/**
	 * Rendering the cObject, SWFOBJECT
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		$prefix = '';
		if ($GLOBALS['TSFE']->baseUrl) {
			$prefix = $GLOBALS['TSFE']->baseUrl;
		}
		if ($GLOBALS['TSFE']->absRefPrefix) {
			$prefix = $GLOBALS['TSFE']->absRefPrefix;
		}
		// Initialize content
		$replaceElementIdString = uniqid('mmswf');
		$GLOBALS['TSFE']->register['MMSWFID'] = $replaceElementIdString;
		$layout = isset($conf['layout.']) ? $this->cObj->stdWrap($conf['layout'], $conf['layout.']) : $conf['layout'];
		$content = str_replace('###ID###', $replaceElementIdString, $layout);
		$type = isset($conf['type.']) ? $this->cObj->stdWrap($conf['type'], $conf['type.']) : $conf['type'];
		$typeConf = $conf[$type . '.'];
		// Add Flowplayer js-file
		$GLOBALS['TSFE']->getPageRenderer()->addJsFile(TYPO3_mainDir . 'contrib/flowplayer/example/flowplayer-3.2.6.min.js');
		// Add Flowpayer css for exprss install
		$GLOBALS['TSFE']->getPageRenderer()->addCssFile(TYPO3_mainDir . '../t3lib/js/flowplayer/express-install.css');
		// Add videoJS js-file
		$GLOBALS['TSFE']->getPageRenderer()->addJsFile(TYPO3_mainDir . 'contrib/videojs/video-js/video.js');
		// Add videoJS js-file
		$GLOBALS['TSFE']->getPageRenderer()->addJsFile(TYPO3_mainDir . 'contrib/videojs/video-js/video.js');
		// Add videoJS css-file
		$GLOBALS['TSFE']->getPageRenderer()->addCssFile(TYPO3_mainDir . 'contrib/videojs/video-js/video-js.css');
		// Add extended videoJS control bar
		$GLOBALS['TSFE']->getPageRenderer()->addJsFile(TYPO3_mainDir . '../t3lib/js/videojs/control-bar.js');
		$GLOBALS['TSFE']->getPageRenderer()->addCssFile(TYPO3_mainDir . '../t3lib/js/videojs/control-bar.css');
		// Build Flash configuration
		$player = isset($typeConf['player.']) ? $this->cObj->stdWrap($typeConf['player'], $typeConf['player.']) : $typeConf['player'];
		if (!$player) {
			$player = $prefix . TYPO3_mainDir . 'contrib/flowplayer/flowplayer-3.2.7.swf';
		}
		$installUrl = isset($conf['installUrl.']) ? $this->cObj->stdWrap($conf['installUrl'], $conf['installUrl.']) : $conf['installUrl'];
		if (!$installUrl) {
			$installUrl = $prefix . TYPO3_mainDir . 'contrib/flowplayer/expressinstall.swf';
		}
		$flashVersion = isset($conf['flashVersion.']) ? $this->cObj->stdWrap($conf['flashVersion'], $conf['flashVersion.']) : $conf['flashVersion'];
		if (!$flashVersion) {
			$flashVersion = array(9, 115);
		}
		$flashConfiguration = array(
			// Flowplayer component
			'src' => $player,
			// Express install url
			'expressInstall' => $installUrl,
			// Require at least this Flash version
			'version' => $flashVersion,
			// Older versions will see a message
			'onFail' => '###ONFAIL###'
		);
		$flashDownloadUrl = 'http://www.adobe.com/go/getflashplayer';
		$onFail = 'function()  {
			if (!(flashembed.getVersion()[0] > 0)) {
				var message = "<p>" + "' . $GLOBALS['TSFE']->sL('LLL:EXT:cms/locallang_ttc.xlf:media.needFlashPlugin') . '" + "</p>" + "<p>" + "<a href=\\"' . $flashDownloadUrl . '\\">' . $GLOBALS['TSFE']->sL('LLL:EXT:cms/locallang_ttc.xlf:media.downloadFlash') . '</a>" + "</p>";
				document.getElementById("' . $replaceElementIdString . '_flash_install_info").innerHTML = "<div class=\\"message\\">" + message + "</div>";
			}
		}';
		$flashConfiguration = json_encode($flashConfiguration);
		$flashConfiguration = str_replace('"###ONFAIL###"', $onFail, $flashConfiguration);
		$filename = isset($conf['file.']) ? $this->cObj->stdWrap($conf['file'], $conf['file.']) : $conf['file'];
		if ($filename) {
			if (strpos($filename, '://') !== FALSE) {
				$conf['flashvars.']['url'] = $filename;
			} else {
				if ($prefix) {
					$conf['flashvars.']['url'] = $prefix . $filename;
				} else {
					$conf['flashvars.']['url'] = str_repeat('../', substr_count($player, '/')) . $filename;
				}
			}
		}
		if (is_array($conf['sources'])) {
			foreach ($conf['sources'] as $key => $source) {
				if (strpos($source, '://') === FALSE) {
					$conf['sources'][$key] = $prefix . $source;
				}
			}
		}
		if (is_array($conf['audioSources'])) {
			foreach ($conf['audioSources'] as $key => $source) {
				if (strpos($source, '://') === FALSE) {
					$conf['audioSources'][$key] = $prefix . $source;
				}
			}
		}
		if (isset($conf['audioFallback']) && strpos($conf['audioFallback'], '://') === FALSE) {
			$conf['audioFallback'] = $prefix . $conf['audioFallback'];
		}
		if (isset($conf['caption']) && strpos($conf['caption'], '://') === FALSE) {
			$conf['caption'] = $prefix . $conf['caption'];
		}
		// Write calculated values in conf for the hook
		$conf['player'] = $player ? $player : $filename;
		$conf['installUrl'] = $installUrl;
		$conf['filename'] = $conf['flashvars.']['url'];
		$conf['prefix'] = $prefix;
		// merge with default parameters
		$conf['flashvars.'] = array_merge((array) $typeConf['default.']['flashvars.'], (array) $conf['flashvars.']);
		$conf['params.'] = array_merge((array) $typeConf['default.']['params.'], (array) $conf['params.']);
		$conf['attributes.'] = array_merge((array) $typeConf['default.']['attributes.'], (array) $conf['attributes.']);
		$conf['embedParams'] = 'flashvars, params, attributes';
		// Hook for manipulating the conf array, it's needed for some players like flowplayer
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['swfParamTransform'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['swfParamTransform'] as $classRef) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($classRef, $conf, $this);
			}
		}
		// Flowplayer config
		$flowplayerVideoConfig = array();
		$flowplayerAudioConfig = array();
		if (is_array($conf['flashvars.'])) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::remapArrayKeys($conf['flashvars.'], $typeConf['mapping.']['flashvars.']);
		} else {
			$conf['flashvars.'] = array();
		}
		$conf['videoflashvars'] = $conf['flashvars.'];
		$conf['audioflashvars'] = $conf['flashvars.'];
		$conf['audioflashvars']['url'] = $conf['audioFallback'];
		// Render video sources
		$videoSources = '';
		if (is_array($conf['sources'])) {
			foreach ($conf['sources'] as $source) {
				$fileinfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($source);
				$mimeType = $this->mimeTypes[$fileinfo['fileext']]['video'];
				$videoSources .= '<source src="' . $source . '"' . ($mimeType ? ' type="' . $mimeType . '"' : '') . ' />' . LF;
			}
		}
		// Render audio sources
		$audioSources = '';
		if (is_array($conf['audioSources'])) {
			foreach ($conf['audioSources'] as $source) {
				$fileinfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($source);
				$mimeType = $this->mimeTypes[$fileinfo['fileext']]['audio'];
				$audioSources .= '<source src="' . $source . '"' . ($mimeType ? ' type="' . $mimeType . '"' : '') . ' />' . LF;
			}
		}
		// Configure captions
		if ($conf['type'] === 'video' && isset($conf['caption'])) {
			// Assemble captions track tag
			$videoCaptions = '<track id="' . $replaceElementIdString . '_captions_track" kind="captions" src="' . $conf['caption'] . '"></track>' . LF;
			// Add videoJS extension for captions
			$GLOBALS['TSFE']->getPageRenderer()->addJsFile(TYPO3_mainDir . '../t3lib/js/videojs/captions.js');
			// Flowplayer captions
			$conf['videoflashvars']['captionUrl'] = $conf['caption'];
			// Flowplayer captions plugin configuration
			$flowplayerVideoConfig = array_merge_recursive($flowplayerVideoConfig, $this->flowplayerCaptionsConfig);
		}
		// Configure flowplayer audio fallback
		if (isset($conf['audioFallback'])) {
			$flowplayerAudioConfig = array_merge_recursive($flowplayerAudioConfig, $this->flowplayerAudioConfig);
		}
		// Configure audio description
		if ($conf['type'] == 'video') {
			if (is_array($conf['audioSources']) && count($conf['audioSources'])) {
				// Add videoJS audio description toggle
				$GLOBALS['TSFE']->getPageRenderer()->addJsFile(TYPO3_mainDir . '../t3lib/js/videojs/audio-description.js');
			}
			if (isset($conf['audioFallback'])) {
				// Audio description flowplayer config (remove controls)
				$flowplayerAudioConfig = array_merge_recursive($flowplayerAudioConfig, $this->flowplayerAudioDescriptionConfig);
			}
		}
		// Assemble Flowplayer configuration
		if (count($conf['videoflashvars'])) {
			$flowplayerVideoConfig = array_merge_recursive($flowplayerVideoConfig, array('clip' => $conf['videoflashvars']));
		}
		$flowplayerVideoJsonConfig = str_replace(array('"true"', '"false"'), array('true', 'false'), json_encode($flowplayerVideoConfig));
		if (count($conf['audioflashvars'])) {
			$flowplayerAudioConfig = array_merge_recursive($flowplayerAudioConfig, array('clip' => $conf['audioflashvars']));
		}
		$flowplayerAudioJsonConfig = str_replace(array('"true"', '"false"'), array('true', 'false'), json_encode($flowplayerAudioConfig));
		// Assemble param tags (required?)
		if (is_array($conf['params.'])) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::remapArrayKeys($conf['params.'], $typeConf['mapping.']['params.']);
		}
		$videoFlashParams = '';
		if (is_array($conf['params.'])) {
			foreach ($conf['params.'] as $name => $value) {
				$videoFlashParams .= '<param name="' . $name . '" value="' . $value . '" />' . LF;
			}
		}
		$audioFlashParams = $videoFlashParams;
		// Required param tags
		$videoFlashParams .= '<param name="movie" value="' . $player . '" />' . LF;
		$videoFlashParams .= '<param name="flashvars" value=\'config=' . $flowplayerVideoJsonConfig . '\' />' . LF;
		$audioFlashParams .= '<param name="movie" value="' . $player . '" />' . LF;
		$audioFlashParams .= '<param name="flashvars" value=\'config=' . $flowplayerAudioJsonConfig . '\' />' . LF;
		// Assemble audio/video tag attributes
		$attributes = '';
		if (is_array($conf['attributes.'])) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::remapArrayKeys($conf['attributes.'], $typeConf['attributes.']['params.']);
		}
		foreach ($this->html5TagAttributes as $attribute) {
			if ($conf['attributes.'][$attribute] === 'true' || $conf['attributes.'][$attribute] === strToLower($attribute) || $conf['attributes.'][$attribute] === $attribute) {
				$attributes .= strToLower($attribute) . '="' . strToLower($attribute) . '" ';
			}
		}
		// Media dimensions
		$width = isset($conf['width.']) ? $this->cObj->stdWrap($conf['width'], $conf['width.']) : $conf['width'];
		if (!$width) {
			$width = $conf[$type . '.']['defaultWidth'];
		}
		$height = isset($conf['height.']) ? $this->cObj->stdWrap($conf['height'], $conf['height.']) : $conf['height'];
		if (!$height) {
			$height = $conf[$type . '.']['defaultHeight'];
		}
		// Alternate content
		$alternativeContent = isset($conf['alternativeContent.']) ? $this->cObj->stdWrap($conf['alternativeContent'], $conf['alternativeContent.']) : $conf['alternativeContent'];
		// Render video
		if ($conf['type'] === 'video') {
			if ($conf['preferFlashOverHtml5']) {
				// Flash with video tag fallback
				$conf['params.']['playerFallbackOrder'] = array('flash', 'html5');
				$flashDivContent = $videoFlashParams . LF . '<video id="' . $replaceElementIdString . '_video_js" class="video-js" ' . $attributes . 'controls="controls"  mediagroup="' . $replaceElementIdString . '" width="' . $width . '" height="' . $height . '">' . LF . $videoSources . $videoCaptions . $alternativeContent . LF . '</video>' . LF;
				$divContent = '
					<div id="' . $replaceElementIdString . '_flash_install_info" class="flash-install-info"></div>' . LF . '<noscript>' . LF . '<object id="' . $replaceElementIdString . '_vjs_flash" type="application/x-shockwave-flash" data="' . $player . '" width="' . $width . '" height="' . $height . '">' . LF . $flashDivContent . '</object>' . LF . '</noscript>' . LF;
				$content = str_replace('###SWFOBJECT###', '<div id="' . $replaceElementIdString . '_video" class="flashcontainer" style="width:' . $width . 'px; height:' . $height . 'px;">' . LF . $divContent . '</div>', $content);
			} else {
				// Video tag with Flash fallback
				$conf['params.']['playerFallbackOrder'] = array('html5', 'flash');
				$videoTagContent = $videoSources . $videoCaptions;
				if (isset($conf['videoflashvars']['url'])) {
					$videoTagContent .= '
						<noscript>' . LF . '<object class="vjs-flash-fallback" id="' . $replaceElementIdString . '_vjs_flash_fallback" type="application/x-shockwave-flash" data="' . $player . '" width="' . $width . '" height="' . $height . '">' . LF . $videoFlashParams . LF . $alternativeContent . LF . '</object>' . LF . '</noscript>';
				}
				$divContent = '
					<div id="' . $replaceElementIdString . '_flash_install_info" class="flash-install-info"></div>' . LF . '<video id="' . $replaceElementIdString . '_video_js" class="video-js" ' . $attributes . 'controls="controls" mediagroup="' . $replaceElementIdString . '" width="' . $width . '" height="' . $height . '">' . LF . $videoTagContent . '</video>';
				$content = str_replace('###SWFOBJECT###', '<div id="' . $replaceElementIdString . '_video" class="video-js-box" style="width:' . $width . 'px; height:' . $height . 'px;">' . LF . $divContent . '</div>', $content);
			}
		}
		// Render audio
		if ($conf['type'] === 'audio' || $audioSources || isset($conf['audioFallback'])) {
			if ($conf['preferFlashOverHtml5']) {
				// Flash with audio tag fallback
				$flashDivContent = $audioFlashParams . LF . '<audio id="' . $replaceElementIdString . '_audio_element"' . $attributes . ($conf['type'] === 'video' ? ' mediagroup="' . $replaceElementIdString . 'style="position:absolute;left:-10000px;"' : ' controls="controls"') . ' style="width:' . $width . 'px; height:' . $height . 'px;">' . LF . $audioSources . $alternativeContent . LF . '</audio>' . LF;
				$divContent = ($conf['type'] === 'video' ? '' : '<div id="' . $replaceElementIdString . '_flash_install_info" class="flash-install-info"></div>' . LF) . '<noscript>' . LF . '<object id="' . $replaceElementIdString . '_audio_flash" type="application/x-shockwave-flash" data="' . $player . '" width="' . ($conf['type'] === 'video' ? 0 : $width) . '" height="' . ($conf['type'] === 'video' ? 0 : $height) . '">' . LF . $flashDivContent . '</object>' . LF . '</noscript>' . LF;
				$audioContent = '<div id="' . $replaceElementIdString . '_audio_box" class="audio-flash-container" style="width:' . ($conf['type'] === 'video' ? 0 : $width) . 'px; height:' . ($conf['type'] === 'video' ? 0 : $height) . 'px;">' . LF . $divContent . '</div>';
			} else {
				// Audio tag with Flash fallback
				$audioTagContent = $audioSources;
				if (isset($conf['audioflashvars']['url'])) {
					$audioTagContent .= '
						<noscript>' . LF . '<object class="audio-flash-fallback" id="' . $replaceElementIdString . '_audio_flash" type="application/x-shockwave-flash" data="' . $player . '" width="' . $width . '" height="' . $height . '">' . LF . $audioFlashParams . LF . $alternativeContent . LF . '</object>' . LF . '</noscript>';
				}
				$divContent = ($conf['type'] === 'video' ? '' : '<div id="' . $replaceElementIdString . '_flash_install_info" class="flash-install-info"></div>' . LF) . '<audio id="' . $replaceElementIdString . '_audio_element" class="audio-element"' . $attributes . ($conf['type'] === 'video' ? ' mediagroup="' . $replaceElementIdString . '" style="position:absolute;left:-10000px;"' : ' controls="controls"') . '>' . LF . $audioTagContent . '</audio>' . LF . $audioSourcesEmbeddingJsScript;
				$audioContent = '<div id="' . $replaceElementIdString . '_audio_box" class="audio-box" style="width:' . ($conf['type'] === 'video' ? 0 : $width) . 'px; height:' . ($conf['type'] === 'video' ? 0 : $height) . 'px;">' . LF . $divContent . '</div>';
			}
			if ($conf['type'] === 'audio') {
				$content = str_replace('###SWFOBJECT###', $audioContent, $content);
			} else {
				$content .= LF . $audioContent;
			}
		}
		// Assemble inline JS code
		$videoJsSetup = '';
		$flowplayerHandlers = '';
		if ($conf['type'] === 'video') {
			// Assemble videoJS options
			$videoJsOptions = array();
			foreach ($this->videoJsOptions as $videoJsOption) {
				if (isset($conf['params.'][$videoJsOption])) {
					$videoJsOptions[$videoJsOption] = $conf['params.'][$videoJsOption];
				}
			}
			$videoJsOptions = count($videoJsOptions) ? json_encode($videoJsOptions) : '{}';
			// videoJS setup and videoJS listeners for audio description synchronisation
			if ($audioSources || isset($conf['audioFallback'])) {
				$videoJsSetup = '
			var ' . $replaceElementIdString . '_video = VideoJS.setup("' . $replaceElementIdString . '_video_js", ' . $videoJsOptions . ');
			var ' . $replaceElementIdString . '_video_element = document.getElementById("' . $replaceElementIdString . '_video_js");
			var ' . $replaceElementIdString . '_audio_element = document.getElementById("' . $replaceElementIdString . '_audio_element");
			if (!!' . $replaceElementIdString . '_video_element && !!' . $replaceElementIdString . '_audio_element) {
				' . $replaceElementIdString . '_audio_element.muted = true;
				VideoJS.addListener(' . $replaceElementIdString . '_video_element, "pause", function () { document.getElementById("' . $replaceElementIdString . '_audio_element").pause(); });
				VideoJS.addListener(' . $replaceElementIdString . '_video_element, "play", function () { try {document.getElementById("' . $replaceElementIdString . '_audio_element").currentTime = document.getElementById("' . $replaceElementIdString . '_video_js").currentTime} catch(e) {}; document.getElementById("' . $replaceElementIdString . '_audio_element").play(); });
				VideoJS.addListener(' . $replaceElementIdString . '_video_element, "seeked", function () { document.getElementById("' . $replaceElementIdString . '_audio_element").currentTime = document.getElementById("' . $replaceElementIdString . '_video_js").currentTime; });
				VideoJS.addListener(' . $replaceElementIdString . '_video_element, "volumechange", function () { document.getElementById("' . $replaceElementIdString . '_audio_element").volume = document.getElementById("' . $replaceElementIdString . '_video_js").volume; });
			}';
			} else {
				$videoJsSetup = '
			var ' . $replaceElementIdString . '_video = VideoJS.setup("' . $replaceElementIdString . '_video_js", ' . $videoJsOptions . ');
			';
			}
			// Prefer Flash or fallback to Flash
			$videoSourcesEmbedding = '';
			// If we have a video file for Flash
			if (isset($conf['filename'])) {
				// If we prefer Flash
				if ($conf['preferFlashOverHtml5']) {
					$videoTagAssembly = '';
					// Create "source" elements
					if (is_array($conf['sources']) && count($conf['sources'])) {
						foreach ($conf['sources'] as $source) {
							$fileinfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($source);
							$mimeType = $this->mimeTypes[$fileinfo['fileext']]['video'];
							$videoTagAssembly .= '
			' . $replaceElementIdString . '_video_js.appendChild($f.extend(document.createElement("source"), {
				src: "' . $source . '",
				type: "' . $mimeType . '"
			}));';
						}
						// Create "track" elements
						if (isset($conf['caption'])) {
							// Assemble captions track tag
							// It will take a while before the captions are loaded and parsed...
							$videoTagAssembly .= '
			var track  = document.createElement("track");
			track.setAttribute("src", "' . $conf['caption'] . '");
			track.setAttribute("id", "' . $replaceElementIdString . '_captions_track");
			track.setAttribute("kind", "captions");
			' . $replaceElementIdString . '_video_js.appendChild(track);';
						}
						$videoTagAssembly .= '
			$f.extend(' . $replaceElementIdString . '_video_js, {
				id: "' . $replaceElementIdString . '_video_js",
				className: "video-js",
				controls: "controls",
				mediagroup: "' . $replaceElementIdString . '",
				preload: "none",
				width: "' . $width . '",
				height: "' . $height . '"
			});
			' . $replaceElementIdString . '_video.appendChild(' . $replaceElementIdString . '_video_js);
			' . $replaceElementIdString . '_video.className = "video-js-box";';
						$videoTagAssembly .= $videoJsSetup;
					}
					$videoSourcesEmbedding = '
		var ' . $replaceElementIdString . '_video = document.getElementById("' . $replaceElementIdString . '_video");
		var ' . $replaceElementIdString . '_video_js = document.createElement("video");
		if (flashembed.getVersion()[0] > 0) {
				// Flash is available
			var videoPlayer = flowplayer("' . $replaceElementIdString . '_video", ' . $flashConfiguration . ', ' . $flowplayerVideoJsonConfig . ').load();
			videoPlayer.onBeforeUnload(function () { return false; });
		} else if (!!' . $replaceElementIdString . '_video_js.canPlayType) {
				// Flash is not available: fallback to videoJS if video tag is supported
			' . $videoTagAssembly . '
		} else {
				// Neither Flash nor video is available: offer to install Flash
			flashembed("' . $replaceElementIdString . '_video", ' . $flashConfiguration . ');
		}';
				} elseif (is_array($conf['sources'])) {
					// HTML5 is the preferred rendering method
					// Test whether the browser supports any of types of the provided sources
					$supported = array();
					foreach ($conf['sources'] as $source) {
						$fileinfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($source);
						$mimeType = $this->mimeTypes[$fileinfo['fileext']]['video'];
						$supported[] = $replaceElementIdString . '_videoTag.canPlayType("' . $mimeType . '") != ""';
					}
					// Testing whether the browser supports the video tag with any of the provided source types
					// If no support, embed flowplayer
					$videoSourcesEmbedding = '
		var ' . $replaceElementIdString . '_videoTag = document.createElement(\'video\');
		var ' . $replaceElementIdString . '_video_box = document.getElementById("' . $replaceElementIdString . '_video");
		if (' . $replaceElementIdString . '_video_box) {
			if (!' . $replaceElementIdString . '_videoTag || !' . $replaceElementIdString . '_videoTag.canPlayType || !(' . (count($supported) ? implode(' || ', $supported) : 'false') . ')) {
					// Avoid showing an empty video element
				if (document.getElementById("' . $replaceElementIdString . '_video_js")) {
					document.getElementById("' . $replaceElementIdString . '_video_js").style.display = "none";
				}
				if (flashembed.getVersion()[0] > 0) {
						// Flash is available
					var videoPlayer = flowplayer("' . $replaceElementIdString . '_video", ' . $flashConfiguration . ', ' . $flowplayerVideoJsonConfig . ').load();
					videoPlayer.onBeforeUnload(function () { return false; });
				} else {
						// Neither Flash nor video is available: offer to install Flash
					flashembed("' . $replaceElementIdString . '_video", ' . $flashConfiguration . ');
				}
			} else {' . $videoJsSetup . '
			}
		}';
				}
			}
		}
		// Audio fallback to Flash
		$audioSourcesEmbedding = '';
		// If we have an audio file for Flash
		if (isset($conf['audioFallback'])) {
			// If we prefer Flash in
			if ($conf['preferFlashOverHtml5']) {
				$audioTagAssembly = '';
				// Create "source" elements
				if (is_array($conf['audioSources']) && count($conf['audioSources'])) {
					foreach ($conf['audioSources'] as $source) {
						$fileinfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($source);
						$mimeType = $this->mimeTypes[$fileinfo['fileext']]['audio'];
						$audioTagAssembly .= '
		' . $replaceElementIdString . '_audio_element.appendChild($f.extend(document.createElement("source"), {
			src: "' . $source . '",
			type: "' . $mimeType . '"
		}));';
					}
					$audioTagAssembly .= '
		$f.extend(' . $replaceElementIdString . '_audio_element, {
			id: "' . $replaceElementIdString . '_audio_element",
			className: "audio-element",
			controls: "' . ($conf['type'] === 'video' ? '' : 'controls') . '",
			mediagroup: "' . $replaceElementIdString . '",
			preload: "none",
			width: "' . ($conf['type'] === 'video' ? 0 : $width) . 'px",
			height: "' . ($conf['type'] === 'video' ? 0 : $height) . 'px"
		});
		' . $replaceElementIdString . '_audio_box.appendChild(' . $replaceElementIdString . '_audio_element);
		' . $replaceElementIdString . '_audio_box.className = "audio-box";';
				}
				$audioSourcesEmbedding = '
		var ' . $replaceElementIdString . '_audio_box = document.getElementById("' . $replaceElementIdString . '_audio_box");
		var ' . $replaceElementIdString . '_audio_element = document.createElement("audio");
		if (flashembed.getVersion()[0] > 0) {
				// Flash is available
			var audioPlayer = flowplayer("' . $replaceElementIdString . '_audio_box", ' . $flashConfiguration . ', ' . $flowplayerAudioJsonConfig . ').load();
			audioPlayer.onBeforeUnload(function () { return false; });
			' . ($conf['type'] === 'video' ? 'audioPlayer.mute();' : '') . '
		} else if (!!' . $replaceElementIdString . '_audio_element.canPlayType) {
				// Flash is not available: fallback to audio element if audio tag is supported
			' . $audioTagAssembly . '
		} else {
				// Neither Flash nor audio is available: offer to install Flash if this is not an audio description of a video
			' . ($conf['type'] === 'video' ? '' : 'flashembed("' . $replaceElementIdString . '_audio_box", ' . $flashConfiguration . ');') . '
		}';
			} elseif (is_array($conf['audioSources'])) {
				// HTML5 is the preferred rendering method
				// Test whether the browser supports any of types of the provided sources
				$supported = array();
				foreach ($conf['audioSources'] as $source) {
					$fileinfo = \TYPO3\CMS\Core\Utility\GeneralUtility::split_fileref($source);
					$mimeType = $this->mimeTypes[$fileinfo['fileext']]['audio'];
					$supported[] = $replaceElementIdString . '_audioTag.canPlayType("' . $mimeType . '") != ""';
				}
				// Testing whether the browser supports the audio tag with any of the provided source types
				// If no support, embed flowplayer
				$audioSourcesEmbedding = '
		var ' . $replaceElementIdString . '_audioTag = document.createElement(\'audio\');
		var ' . $replaceElementIdString . '_audio_box = document.getElementById("' . $replaceElementIdString . '_audio_box");
		if (' . $replaceElementIdString . '_audio_box) {
			if (!' . $replaceElementIdString . '_audioTag || !' . $replaceElementIdString . '_audioTag.canPlayType || !(' . (count($supported) ? implode(' || ', $supported) : 'false') . ')) {
					// Avoid showing an empty audio element
				if (document.getElementById("' . $replaceElementIdString . '_audio_element")) {
					document.getElementById("' . $replaceElementIdString . '_audio_element").style.display = "none";
				}
				if (flashembed.getVersion()[0] > 0) {
					var audioPlayer = flowplayer("' . $replaceElementIdString . '_audio_box", ' . $flashConfiguration . ', ' . $flowplayerAudioJsonConfig . ').load();
					audioPlayer.onBeforeUnload(function () { return false; });
					' . ($conf['type'] === 'video' ? 'audioPlayer.mute()' : '') . '
				} else {
						// Neither Flash nor audio is available: offer to install Flash if this is not an audio description of a video
					' . ($conf['type'] === 'video' ? '' : 'flashembed("' . $replaceElementIdString . '_audio_box", ' . $flashConfiguration . ');') . '
				}
			}
		}';
			}
			// Flowplayer eventHandlers for audio description synchronisation
			$flowplayerHandlers = '';
			if ($conf['type'] === 'video') {
				$flowplayerHandlers = '
		if (flashembed.getVersion()[0] > 0) {
				// Flash is available
			var videoPlayer = flowplayer("' . $replaceElementIdString . '_video");
			if (videoPlayer) {
					// Control audio description through video control bar
				videoPlayer.onVolume(function (volume) { flowplayer("' . $replaceElementIdString . '_audio_box").setVolume(volume); });
				videoPlayer.onMute(function () { flowplayer("' . $replaceElementIdString . '_audio_box").mute(); });
				videoPlayer.onUnmute(function () { flowplayer("' . $replaceElementIdString . '_audio_box").unmute(); });
				videoPlayer.onPause(function () { flowplayer("' . $replaceElementIdString . '_audio_box").pause(); });
				videoPlayer.onResume(function () { flowplayer("' . $replaceElementIdString . '_audio_box").resume(); });
				videoPlayer.onStart(function () { flowplayer("' . $replaceElementIdString . '_audio_box").play(); });
				videoPlayer.onStop(function () { flowplayer("' . $replaceElementIdString . '_audio_box").stop(); });
				videoPlayer.onSeek(function (clip, seconds) { flowplayer("' . $replaceElementIdString . '_audio_box").seek(seconds); });
					// Mute audio description on start
				flowplayer("' . $replaceElementIdString . '_audio_box").onStart(function () { this.mute()});
					// Audio description toggle
				var videoContainer = document.getElementById("' . $replaceElementIdString . '_video");
				var buttonContainer = document.createElement("div");
				$f.extend(buttonContainer, {
					id: "' . $replaceElementIdString . '_audio_description_toggle",
					className: "vjs-audio-description-control"
				});
				var button = document.createElement("div");
				buttonContainer.appendChild(button);
				buttonContainer.style.position = "relative";
				buttonContainer.style.left = (parseInt(' . $width . ', 10)-27) + "px";
				videoContainer.parentNode.insertBefore(buttonContainer, videoContainer.nextSibling);
				VideoJS.addListener(buttonContainer, "click", function () {
					var buttonContainer = document.getElementById("' . $replaceElementIdString . '_audio_description_toggle");
					var state = buttonContainer.getAttribute("data-state");
					if (state == "enabled") {
						buttonContainer.setAttribute("data-state", "disabled");
						flowplayer("' . $replaceElementIdString . '_audio_box").mute();
					} else {
						buttonContainer.setAttribute("data-state", "enabled");
						flowplayer("' . $replaceElementIdString . '_audio_box").unmute();
					}
				});
			}
		}';
			}
		}
		// Wrap up inline JS code
		$jsInlineCode = $audioSourcesEmbedding . $videoSourcesEmbedding . $flowplayerHandlers;
		if ($jsInlineCode) {
			$jsInlineCode = 'VideoJS.DOMReady(function(){' . $jsInlineCode . LF . '});';
		}
		$GLOBALS['TSFE']->getPageRenderer()->addJsInlineCode($replaceElementIdString, $jsInlineCode);
		if (isset($conf['stdWrap.'])) {
			$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
		}
		return $content;
	}

}


?>