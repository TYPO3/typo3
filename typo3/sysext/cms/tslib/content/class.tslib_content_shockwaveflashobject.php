<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010-2011 Steffen Kamper <steffen@typo3.org>
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
 * Contains SWFOBJECT class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class tslib_content_ShockwaveFlashObject extends tslib_content_Abstract {

	/**
	 * File extension to mime type
	 */
	public $mimeTypes = array(
		'aif' => array(
			'audio' => 'audio/aiff',
		),
		'au' => array(
			'audio' => 'audio/x-au',
		),
		'avi' => array(
			'audio' => 'video/x-msvideo',
		),
		'asf' => array( 
			'video' => 'video/x-ms-asf',
		),
		'class' => array(
			'audio' => 'application/java',
			'video' =>  'application/java',
		),
		'dcr' => array(
			'video' =>  'application/x-director',
		),
		'flac' => array(
			'audio' => 'audio/flac',
		),
		'flv' => array(
			'video' => 'video/x-flv',
		),
		'mp3' => array(
			'audio' => 'audio/mpeg',
		),
		'mp4' => array(
			'video' => 'video/mp4',
		),
		'oga' => array(
			'audio' => 'audio/ogg',
		),
		'ogg' => array(
			'video' => 'video/ogg',
		),
		'ogv' => array(
			'video' => 'video/ogg',
		),
		'swa' => array(
			'audio' => 'audio/x-m4a',
		),
		'mov' => array(
			'video' => 'video/quicktime',
		),
		'm4a' => array(
			'audio' => 'audio/mp4a-latm',
		),
		'm4v' => array(
			'video' => 'video/x-m4v',
		),
		'qt' => array(
			'video' => 'video/quicktime',
		),
		'swa' => array(
			'audio' => 'application/x-director',
		),
		'swf' => array(
			'audio' => 'application/x-shockwave-flash',
			'video' => 'application/x-shockwave-flash',
		),
		'wav' => array(
			'audio' => 'audio/wave',
		),
		'webm' => array(
			'audio' => 'audio/webm',
			'video' => 'video/webm',
		),
		'wmv' => array(
			'audio' => 'audio/x-ms-wmv',
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
		'playerFallbackOrder',
	);

	/**
	 * htlm5 tag attributes
	 */
	public $html5TagAttributes = array(
		'autoPlay',
		'controls',
		'loop',
		'preload',
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
				// Configure a content plugin so that it looks good for showing subtitles
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
			),
		),
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
				'fullscreen' => FALSE,
			),
		),
	);

	/**
	 * Flowplayer controls plugin configuration for the audio description
	 */
	public $flowplayerAudioDescriptionConfig = array(
			// The controls plugin
		'plugins' => array(
			'controls' => NULL,
		),
	);

	/**
	 * Rendering the cObject, SWFOBJECT
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 */
	public function render($conf = array()) {
		$prefix = '';
		if ($GLOBALS['TSFE']->baseUrl) {
			$prefix = $GLOBALS['TSFE']->baseUrl;
		}
		if ($GLOBALS['TSFE']->absRefPrefix) {
			$prefix = $GLOBALS['TSFE']->absRefPrefix;
		}
		;

		$type = isset($conf['type.'])
			? $this->cObj->stdWrap($conf['type'], $conf['type.'])
			: $conf['type'];
		$typeConf = $conf[$type . '.'];

			//add SWFobject js-file
		$GLOBALS['TSFE']->getPageRenderer()->addJsFile(TYPO3_mainDir . 'contrib/flashmedia/swfobject/swfobject.js');

		$player = isset($typeConf['player.'])
			? $this->cObj->stdWrap($typeConf['player'], $typeConf['player.'])
			: $typeConf['player'];

		$installUrl = isset($conf['installUrl.'])
			? $this->cObj->stdWrap($conf['installUrl'], $conf['installUrl.'])
			: $conf['installUrl'];
		if(!$installUrl) {
			$installUrl = $prefix . TYPO3_mainDir . 'contrib/flashmedia/swfobject/expressInstall.swf';
		}

		$filename = isset($conf['file.'])
			? $this->cObj->stdWrap($conf['file'], $conf['file.'])
			: $conf['file'];
		$forcePlayer = isset($conf['forcePlayer.'])
			? $this->cObj->stdWrap($conf['forcePlayer'], $conf['forcePlayer.'])
			: $conf['forcePlayer'];

		if ($filename && $forcePlayer) {
			if (strpos($filename, '://') !== FALSE) {
				$conf['flashvars.']['file'] = $filename;
			} else {
				if ($prefix) {
					$conf['flashvars.']['file'] = $prefix . $filename;
				} else {
					$conf['flashvars.']['file'] = str_repeat('../', substr_count($player, '/')) . $filename;
				}

			}
		} else {
			$player = $filename;
		}
			// Write calculated values in conf for the hook
		$conf['player'] = $player;
		$conf['installUrl'] = $installUrl;
		$conf['filename'] = $filename;
		$conf['prefix'] = $prefix;

			// merge with default parameters
		$conf['flashvars.'] = array_merge((array) $typeConf['default.']['flashvars.'], (array) $conf['flashvars.']);
		$conf['params.'] = array_merge((array) $typeConf['default.']['params.'], (array) $conf['params.']);
		$conf['attributes.'] = array_merge((array) $typeConf['default.']['attributes.'], (array) $conf['attributes.']);
		$conf['embedParams'] = 'flashvars, params, attributes';

			// Hook for manipulating the conf array, it's needed for some players like flowplayer
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['swfParamTransform'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/hooks/class.tx_cms_mediaitems.php']['swfParamTransform'] as $classRef) {
				t3lib_div::callUserFunction($classRef, $conf, $this);
			}
		}
			// Initialize content
		$replaceElementIdString = uniqid('mmswf');
		$GLOBALS['TSFE']->register['MMSWFID'] = $replaceElementIdString;

		$layout = isset($conf['layout.'])
			? $this->cObj->stdWrap($conf['layout'], $conf['layout.'])
			: $conf['layout'];
		$content = str_replace('###ID###', $replaceElementIdString, $layout);

			// Flowplayer config
		$flowplayerVideoConfig = array();
		$flowplayerAudioConfig = array();
		if (is_array($conf['flashvars.'])) {
			t3lib_div::remapArrayKeys($conf['flashvars.'], $typeConf['mapping.']['flashvars.']);
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
				$fileinfo = t3lib_div::split_fileref($source);
				$mimeType = $this->mimeTypes[$fileinfo['fileext']]['video'];
				$videoSources .= '<source src="' . $source . '"' . ($mimeType ? ' type="' . $mimeType . '"' : '') . ' />' . LF;
			}
		}

			// Render audio sources
		$audioSources = '';
		if (is_array($conf['audioSources'])) {
			foreach ($conf['audioSources'] as $source) {
				$fileinfo = t3lib_div::split_fileref($source);
				$mimeType = $this->mimeTypes[$fileinfo['fileext']]['audio'];
				$audioSources .= '<source src="' . $source . '"' . ($mimeType ? ' type="' . $mimeType . '"' : '') . ' />' . LF;
			}
		}

			// Configure captions
		if ($conf['type'] === 'video' && isset($conf['caption'])) {
				// Assemble subtitles track tag
			$videoSubtitles = '<track id="' . $replaceElementIdString . '_track" kind="subtitles" src="' . $conf['caption'] . '"></track>' . LF;
				// Flowplayer captions
			$conf['videoflashvars']['captionUrl'] = $conf['caption'];
				// Flowplayer captions plugin configuration
			$flowplayerVideoConfig = array_merge_recursive($flowplayerVideoConfig, $this->flowplayerCaptionsConfig);
		}

			// Configure flowplayer audio fallback
		if (isset($conf['audioFallback'])) {
			$flowplayerAudioConfig = array_merge_recursive($flowplayerAudioConfig, $this->flowplayerAudioConfig);
		}

			// Configure flowplayer audio description
		if ($conf['type'] == 'video' && isset($conf['audioFallback'])) {
				// Audio description config (remove controls)
			$flowplayerAudioConfig = array_merge_recursive($flowplayerAudioConfig, $this->flowplayerAudioDescriptionConfig);
		}

			// Assemble Flowplayer configuration
		if (count($conf['videoflashvars'])) {
			$flowplayerVideoConfig = array_merge_recursive($flowplayerVideoConfig, array('clip' => $conf['videoflashvars']));
		}
		if (count($conf['audioflashvars'])) {
			$flowplayerAudioConfig = array_merge_recursive($flowplayerAudioConfig, array('clip' => $conf['audioflashvars']));
		}
			// Assemble param tags (required?)
		if (is_array($conf['params.'])) {
			t3lib_div::remapArrayKeys($conf['params.'], $typeConf['mapping.']['params.']);
		}
		$videoFlashParams = '';
		if (is_array($conf['params.'])) {
			foreach ($conf['params.'] as $name => $value) {
				$videoFlashParams .= '<param name="' . $name . '" value="' . $value . '" />' . LF;
			}
		}
		$audioFlashParams = $videoFlashParams;

			// Required param tags
		$videoFlashParams .= '<param name="movie" value="' . $installUrl . '" />' . LF;
		$videoFlashParams .= '<param name="flashvars" value=\'config=' . json_encode($flowplayerVideoConfig) . '\' />' . LF;
		$audioFlashParams .= '<param name="movie" value="' . $installUrl . '" />' . LF;
		$audioFlashParams .= '<param name="flashvars" value=\'config=' . json_encode($flowplayerAudioConfig) . '\' />' . LF;

			// Render javascript for audio fallback
		if (is_array($conf['audioSources']) && isset($conf['audioFallback'])) {
			$conditions = array();
			foreach ($conf['audioSources'] as $source) {
				$fileinfo = t3lib_div::split_fileref($source);
				$mimeType = $this->mimeTypes[$fileinfo['fileext']]['audio'];
				$conditions[] = '(' . $replaceElementIdString . '_audioTag.canPlayType("' . $mimeType . '") != "no")';
				$conditions[] = '(' . $replaceElementIdString . '_audioTag.canPlayType("' . $mimeType . '") != "")';
			}
			$audioSourcesEmbeddingJsScript = LF .
			'<script type="text/javascript">' . LF .
				'var ' . $replaceElementIdString . '_audioTag = document.createElement(\'audio\');' . LF .
				'if (' . $replaceElementIdString . '_audioTag && ' . $replaceElementIdString . '_audioTag.canPlayType) {' . LF .
					'if (!(' . (count($conditions) ? implode( ' && ', $conditions) : 'false') . ')) {' . LF .
						'flowplayer("' . $replaceElementIdString . '_audio", "' . $installUrl . '", ' . json_encode($flowplayerAudioConfig) . ').load();' . LF .
					'}' . LF .
				'}' . LF .	
			'</script>';
		}

			// Assemble audio/video tag attributes
		$attributes = '';
		if (is_array($conf['attributes.'])) {
			t3lib_div::remapArrayKeys($conf['attributes.'], $typeConf['attributes.']['params.']);
		}
		$attributes = 'var attributes = ' . (count($conf['attributes.']) ? json_encode($conf['attributes.']) : '{}') . ';';

		$flashVersion = isset($conf['flashVersion.'])
			? $this->cObj->stdWrap($conf['flashVersion'], $conf['flashVersion.'])
			:  $conf['flashVersion'];

		if (!$flashVersion) {
			$flashVersion = '9';
		}
			// Media dimensions
		$width = isset($conf['width.'])
			? $this->cObj->stdWrap($conf['width'], $conf['width.'])
			: $conf['width'];
		if(!$width) {
			$width = $conf[$type . '.']['defaultWidth'];
		}

        $height = isset($conf['height.'])
			? $this->cObj->stdWrap($conf['height'], $conf['height.'])
			: $conf['height'];
		if(!$height) {
			$height = $conf[$type . '.']['defaultHeight'];
		}


			// Render video
		if ($conf['type'] === 'video') {
			if ($conf['preferFlashOverHtml5']) {
					// Flash with video tag fallback
				$conf['params.']['playerFallbackOrder'] = array('flash', 'html5');
				$flashDivContent = $videoFlashParams . LF .
					'<video id="' . $replaceElementIdString . '_video_js" class="video-js" ' . $attributes . 'controls="controls"  mediagroup="' . $replaceElementIdString . '" width="' . $width . '" height="' . $height . '">' . LF .
					$videoSources .
					$videoSubtitles .
					$alternativeContent . LF .
					'</video>' . LF;
				$divContent = '<object id="' . $replaceElementIdString . '_vjs_flash" type="application/x-shockwave-flash" data="' . $installUrl . '" width="' . $width . '" height="' . $height . '">' . LF .
					$flashDivContent .
					'</object>' . LF;
				$content = str_replace('###SWFOBJECT###', '<div id="' . $replaceElementIdString . '_videoflash" class="flashcontainer">' . LF . $divContent . '</div>', $content);
			} else {
					// Video tag with Flash fallback
				$conf['params.']['playerFallbackOrder'] = array('html5', 'flash');
				$videoTagContent = $videoSources . $videoSubtitles;
				if (isset($conf['videoflashvars']['url'])) {
					$videoTagContent .= '<object class="vjs-flash-fallback" id="' . $replaceElementIdString . '_vjs_flash_fallback" type="application/x-shockwave-flash" data="' . $installUrl . '" width="' . $width . '" height="' . $height . '">' . LF .
						$videoFlashParams . LF .
						$alternativeContent . LF .
						'</object>';
				}
				$divContent = '<video id="' . $replaceElementIdString . '_video_js" class="video-js" ' . $attributes . 'controls="controls" mediagroup="' . $replaceElementIdString . '" width="' . $width . '" height="' . $height . '">' . LF .
					$videoTagContent . 
					'</video>' . LF;
				$content = str_replace('###SWFOBJECT###', '<div id="' . $replaceElementIdString . '_video" class="video-js-box">' . LF . $divContent . '</div>', $content);
			}
		}
			// Render audio
		if ($conf['type'] === 'audio' || $audioSources || isset($conf['audioFallback'])) {
			if ($conf['preferFlashOverHtml5']) {
					// Flash with audio tag fallback
				$flashDivContent = $audioFlashParams . LF .
				'<audio id="' . $replaceElementIdString . '_audio_element"' . $attributes . ($conf['type'] === 'video' ? ' mediagroup="' . $replaceElementIdString . '"' : ' controls="controls"') . ' style="width:' . $width . 'px; height:' . $height . 'px;">' . LF .
					$audioSources .
					$alternativeContent . LF .
					'</audio>' . LF;
				$divContent = '<object id="' . $replaceElementIdString . '_audio_flash" type="application/x-shockwave-flash" data="' . $installUrl . '" width="' . ($conf['type'] === 'video' ? 0 : $width) . '" height="' . ($conf['type'] === 'video' ? 0 : $height) . '">' . LF .
					$flashDivContent .
					'</object>' . LF;
				$audioContent = '<div id="' . $replaceElementIdString . '_audioflash" class="audio-flash-container">' . LF . $divContent . '</div>';
			} else {
					// Audio tag with Flash fallback
				$audioTagContent = $audioSources;
				if (isset($conf['audioflashvars']['url'])) {
					$audioTagContent .= '<noscript><object class="audio-flash-fallback" id="' . $replaceElementIdString . '_audio_flash" type="application/x-shockwave-flash" data="' . $installUrl . '" width="' . $width . '" height="' . $height . '">' . LF .
						$audioFlashParams . LF .
						$alternativeContent . LF .
						'</object></noscript>';
				}
				$divContent = '<audio id="' . $replaceElementIdString . '_audio_element" class="audio-element"' . $attributes . ($conf['type'] === 'video' ? ' mediagroup="' . $replaceElementIdString . '"' : ' controls="controls"') . ' style="width:' . $width . 'px; height:' . $height . 'px;">' . LF .
					$audioTagContent . 
					'</audio>' . LF .
					$audioSourcesEmbeddingJsScript;
				$audioContent = '<div id="' . $replaceElementIdString . '_audio" class="audio-box" style="width:' . ($conf['type'] === 'video' ? 0 : $width) . 'px; height:' . ($conf['type'] === 'video' ? 0 : $height) . 'px;">' . LF . $divContent . '</div>';
			}
			if ($conf['type'] === 'audio') {
				$content = str_replace('###SWFOBJECT###', $audioContent, $content);
			} else {
				$content .= LF . $audioContent;
			}
		}

			// Assemble videoJS options and listeners and Flowplayer listeners
		if ($conf['type'] == 'video') {
			$videoJsOptions = array();
			foreach ($this->videoJsOptions as $videoJsOption) {
				if (isset($conf['params.'][$videoJsOption])) {
					$videoJsOptions[$videoJsOption] = $conf['params.'][$videoJsOption];
				}
			}
			$videoJsOptions = count($videoJsOptions) ? json_encode($videoJsOptions) : '{}';
	
				// Add videoJS setup
				// Problem when videoJS is setup with flash as first player: only audio is heard, no video shown
			if (!$conf['preferFlashOverHtml5']) {
				if ($audioSources || isset($conf['audioFallback'])) {
					$videoJsSetup = '
						var ' . $replaceElementIdString . '_video = VideoJS.setup("' . $replaceElementIdString . '_video_js", ' . $videoJsOptions . ');
						var ' . $replaceElementIdString . '_video = document.getElementById("' . $replaceElementIdString . '_video_js");
						var ' . $replaceElementIdString . '_audio = document.getElementById("' . $replaceElementIdString . '_audio_element");
						VideoJS.addListener(' . $replaceElementIdString . '_video, "pause", function () { ' . $replaceElementIdString . '_audio.pause(); });
						VideoJS.addListener(' . $replaceElementIdString . '_video, "play", function () { ' . $replaceElementIdString . '_audio.currentTime = ' . $replaceElementIdString . '_video.currentTime; ' . $replaceElementIdString . '_audio.play(); });
						VideoJS.addListener(' . $replaceElementIdString . '_video, "seeked", function () { ' . $replaceElementIdString . '_audio.currentTime = ' . $replaceElementIdString . '_video.currentTime; });
						VideoJS.addListener(' . $replaceElementIdString . '_video, "volumechange", function () { ' . $replaceElementIdString . '_audio.volume = ' . $replaceElementIdString . '_video.volume; });
						';
				} else {
					$videoJsSetup = '
						var ' . $replaceElementIdString . '_video = VideoJS.setup("' . $replaceElementIdString . '_video_js", ' . $videoJsOptions . ');
						';
					$videoJsSetup = 'VideoJS.setupAllWhenReady();';
				}
			}
				// Add Flowplayer eventHandlers for audio description synchronisation
			if (isset($conf['audioFallback'])) {
				$flowplayerHandlers = '
					var videoPlayer = flowplayer("' . $replaceElementIdString . ($conf['preferFlashOverHtml5'] ? '_videoflash' : '_video_js') . '");
					videoPlayer.onVolume(function (volume) { $f("' . $replaceElementIdString . '_audioflash").setVolume(volume); });
					videoPlayer.onPause(function () { $f("' . $replaceElementIdString . '_audioflash").pause(); });
					videoPlayer.onResume(function () { $f("' . $replaceElementIdString . '_audioflash").resume(); });
					videoPlayer.onStart(function () { $f("' . $replaceElementIdString . '_audioflash").start(); });
					videoPlayer.onStop(function () { $f("' . $replaceElementIdString . '_audioflash").stop(); });
					videoPlayer.onSeek(function (clip, seconds) { $f("' . $replaceElementIdString . '_audioflash").seek(seconds); });
					';
			}
			$jsInlineCode = 'VideoJS.DOMReady(function(){' . 
				$videoJsSetup .
				$flowplayerHandlers .
			'});';
			$GLOBALS['TSFE']->getPageRenderer()->addJsInlineCode($replaceElementIdString, $jsInlineCode);
		}

		if (isset($conf['stdWrap.'])) {
			$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
		}

		return $content;
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_shockwaveflashobject.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_shockwaveflashobject.php']);
}

?>