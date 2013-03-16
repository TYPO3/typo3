<?php
namespace TYPO3\CMS\Frontend\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Dmitry Dulepov <dmitry@typo3.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * This class contains compression functions for the TYPO3 Frontend. It can be
 * used only in EXT:cms/tslib/index_ts.php
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
class CompressionUtility implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Accumulates content length for the compressed content. It is necessary to
	 * replace the Content-length HTTP header after compression if it was added
	 * by TYPO3 before compression.
	 *
	 * @var integer
	 */
	protected $contentLength = 0;

	/**
	 * Corrects HTTP "Content-length" header if it was sent by TYPO3 and compression
	 * is enabled.
	 *
	 * @param string $outputBuffer Output buffer to compress
	 * @param integer $mode One of PHP_OUTPUT_HANDLER_xxx contants
	 * @return string Compressed string
	 * @see ob_start()
	 * @see ob_gzhandler()
	 * @todo Define visibility
	 */
	public function compressionOutputHandler($outputBuffer, $mode) {
		// Compress the content
		$outputBuffer = ob_gzhandler($outputBuffer, $mode);
		if ($outputBuffer !== FALSE) {
			// Save compressed size
			$this->contentLength += strlen($outputBuffer);
			// Check if this was the last content chunk
			if (0 != ($mode & PHP_OUTPUT_HANDLER_END)) {
				// Check if we have content-length header
				foreach (headers_list() as $header) {
					if (0 == strncasecmp('Content-length:', $header, 15)) {
						header('Content-length: ' . $this->contentLength);
						break;
					}
				}
			}
		}
		return $outputBuffer;
	}

}


?>