<?php
namespace TYPO3\CMS\Core\Resource\Rendering;

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;

/**
 * Vimeo renderer class
 */
class VimeoRenderer implements FileRendererInterface {

	/**
	 * @var OnlineMediaHelperInterface
	 */
	protected $onlineMediaHelper;

	/**
	 * Returns the priority of the renderer
	 * This way it is possible to define/overrule a renderer
	 * for a specific file type/context.
	 * For example create a video renderer for a certain storage/driver type.
	 * Should be between 1 and 100, 100 is more important than 1
	 *
	 * @return int
	 */
	public function getPriority() {
		return 1;
	}

	/**
	 * Check if given File(Reference) can be rendered
	 *
	 * @param FileInterface $file File of FileReference to render
	 * @return bool
	 */
	public function canRender(FileInterface $file) {
		return ($file->getMimeType() === 'video/vimeo' || $file->getExtension() === 'vimeo') && $this->getOnlineMediaHelper($file) !== FALSE;
	}

	/**
	 * Get online media helper
	 *
	 * @param FileInterface $file
	 * @return bool|OnlineMediaHelperInterface
	 */
	protected function getOnlineMediaHelper(FileInterface $file) {
		if ($this->onlineMediaHelper === NULL) {
			$orgFile = $file;
			if ($orgFile instanceof FileReference) {
				$orgFile = $orgFile->getOriginalFile();
			}
			if ($orgFile instanceof File) {
				$this->onlineMediaHelper = OnlineMediaHelperRegistry::getInstance()->getOnlineMediaHelper($orgFile);
			} else {
				$this->onlineMediaHelper = FALSE;
			}
		}
		return $this->onlineMediaHelper;
	}

	/**
	 * Render for given File(Reference) html output
	 *
	 * @param FileInterface $file
	 * @param int|string $width TYPO3 known format; examples: 220, 200m or 200c
	 * @param int|string $height TYPO3 known format; examples: 220, 200m or 200c
	 * @param array $options
	 * @param bool $usedPathsRelativeToCurrentScript See $file->getPublicUrl()
	 * @return string
	 */
	public function render(FileInterface $file, $width, $height, array $options = NULL, $usedPathsRelativeToCurrentScript = FALSE) {

		if ($file instanceof FileReference) {
			$autoplay = $file->getProperty('autoplay');
			if ($autoplay !== NULL) {
				$options['autoplay'] = $autoplay;
			}
		}

		$urlParams = array();
		if (!empty($options['autoplay'])) {
			$urlParams[] = 'autoplay=1';
		}
		if (!empty($options['loop'])) {
			$urlParams[] = 'loop=1';
		}
		$urlParams[] = 'title=' . (int)!empty($options['showinfo']);
		$urlParams[] = 'byline=' . (int)!empty($options['showinfo']);
		$urlParams[] = 'portrait=0';

		if ($file instanceof FileReference) {
			$orgFile = $file->getOriginalFile();
		} else {
			$orgFile = $file;
		}

		$videoId = $this->getOnlineMediaHelper($file)->getOnlineMediaId($orgFile);
		$attributes = array(
			'src' => sprintf('//player.vimeo.com/video/%s?%s', $videoId, implode('&amp;', $urlParams)),
		);
		$width = (int)$width;
		if (!empty($width)) {
			$attributes['width'] = $width;
		}
		$height = (int)$height;
		if (!empty($height)) {
			$attributes['height'] = $height;
		}
		if (is_object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->config['config']['doctype'] !== 'html5') {
			$attributes['frameborder'] = '0';
		}
		$output = '';
		foreach ($attributes as $key => $value) {
			$output .= $key . '="' . $value . '" ';
		}

		// wrap in div so you can make it responsive
		return '<div class="video-container"><iframe ' . $output . 'allowfullscreen></iframe></div>';
	}

}
