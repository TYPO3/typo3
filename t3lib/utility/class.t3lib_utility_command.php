<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2011 Steffen Kamper <steffen@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class to handle system commands.
 *
 * @author	Steffen Kamper <steffen@typo3.org>
 */
final class t3lib_utility_Command {


	/**
	 * Wrapper function for php exec function
	 * Needs to be central to have better control and possible fix for issues
	 *
	 * @static
	 * @param  string  $command
	 * @param  null|array $output
	 * @param  integer $returnValue
	 * @return null|array
	 */
	public static function exec($command, &$output = NULL, &$returnValue = 0) {
		if (TYPO3_OS == 'WIN' && version_compare(phpversion(), '5.3.0', '<')) {
			$command = '"' . $command . '"';
		}
		$lastLine = exec($command, $output, $returnValue);
		return $lastLine;
	}

	/**
	 * Compile the command for running ImageMagick/GraphicsMagick.
	 *
	 * @param	string		Command to be run: identify, convert or combine/composite
	 * @param	string		The parameters string
	 * @param	string		Override the default path (e.g. used by the install tool)
	 * @return	string		Compiled command that deals with IM6 & GraphicsMagick
	 */
	public static function imageMagickCommand($command, $parameters, $path = '') {
		$gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
		$isExt = (TYPO3_OS == 'WIN' ? '.exe' : '');
		$switchCompositeParameters = FALSE;

		if (!$path) {
			$path = $gfxConf['im_path'];
		}
		$path = t3lib_div::fixWindowsFilePath($path);

		$im_version = strtolower($gfxConf['im_version_5']);
		$combineScript = $gfxConf['im_combine_filename'] ? trim($gfxConf['im_combine_filename']) : 'combine';

		if ($command === 'combine') { // This is only used internally, has no effect outside
			$command = 'composite';
		}

			// Compile the path & command
		if ($im_version === 'gm') {
			$switchCompositeParameters = TRUE;
			$path = escapeshellarg($path . 'gm' . $isExt) . ' ' . $command;
		} else {
			if ($im_version === 'im6') {
				$switchCompositeParameters = TRUE;
			}
			$path = escapeshellarg($path . (($command == 'composite') ? $combineScript : $command) . $isExt);
		}

			// strip profile information for thumbnails and reduce their size
		if ($parameters && $command != 'identify' && $gfxConf['im_useStripProfileByDefault'] && $gfxConf['im_stripProfileCommand'] != '') {
			if (strpos($parameters, $gfxConf['im_stripProfileCommand']) === FALSE) {
					// Determine whether the strip profile action has be disabled by TypoScript:
				if ($parameters !== '-version' && strpos($parameters, '###SkipStripProfile###') === FALSE) {
					$parameters = $gfxConf['im_stripProfileCommand'] . ' ' . $parameters;
				} else {
					$parameters = str_replace('###SkipStripProfile###', '', $parameters);
				}
			}
		}

		$cmdLine = $path . ' ' . $parameters;

		if ($command == 'composite' && $switchCompositeParameters) { // Because of some weird incompatibilities between ImageMagick 4 and 6 (plus GraphicsMagick), it is needed to change the parameters order under some preconditions
			$paramsArr = t3lib_div::unQuoteFilenames($parameters);

			if (count($paramsArr) > 5) { // The mask image has been specified => swap the parameters
				$tmp = $paramsArr[count($paramsArr) - 3];
				$paramsArr[count($paramsArr) - 3] = $paramsArr[count($paramsArr) - 4];
				$paramsArr[count($paramsArr) - 4] = $tmp;
			}

			$cmdLine = $path . ' ' . implode(' ', $paramsArr);
		}

		return $cmdLine;
	}

}

?>