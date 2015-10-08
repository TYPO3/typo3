<?php
namespace TYPO3\CMS\Frontend\Utility;

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

/**
 * This class contains compression functions for the TYPO3 Frontend. It can be
 * used only in EXT:frontend/Classes/Http/RequestHandler.php
 */
class CompressionUtility implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Accumulates content length for the compressed content. It is necessary to
     * replace the Content-length HTTP header after compression if it was added
     * by TYPO3 before compression.
     *
     * @var int
     */
    protected $contentLength = 0;

    /**
     * Corrects HTTP "Content-length" header if it was sent by TYPO3 and compression
     * is enabled.
     *
     * @param string $outputBuffer Output buffer to compress
     * @param int $mode One of PHP_OUTPUT_HANDLER_xxx constants
     * @return string Compressed string
     * @see ob_start()
     * @see ob_gzhandler()
     */
    public function compressionOutputHandler($outputBuffer, $mode)
    {
        // Compress the content
        $outputBuffer = ob_gzhandler($outputBuffer, $mode);
        if ($outputBuffer !== false) {
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
