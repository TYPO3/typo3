<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Middleware;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Utility\CompressionUtility;

/**
 * Sets up output compression
 *
 * @internal
 */
class OutputCompression implements MiddlewareInterface
{
    /**
     * Clears all output and checks if a compression level is set
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Throw away all output that may have happened during bootstrapping by weird extensions
        ob_clean();
        // Initialize output compression if configured
        $this->initializeOutputCompression();
        return $handler->handle($request);
    }

    /**
     * Initialize output compression if configured
     */
    protected function initializeOutputCompression()
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel'] && extension_loaded('zlib')) {
            if (MathUtility::canBeInterpretedAsInteger($GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel'])) {
                @ini_set('zlib.output_compression_level', (string)$GLOBALS['TYPO3_CONF_VARS']['FE']['compressionLevel']);
            }
            ob_start([GeneralUtility::makeInstance(CompressionUtility::class), 'compressionOutputHandler']);
        }
    }
}
