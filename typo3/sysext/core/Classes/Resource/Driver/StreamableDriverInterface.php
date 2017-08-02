<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Resource\Driver;

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

/**
 * An interface FAL drivers have to implement to fulfil the needs
 * of streaming files using PSR-7 Response objects.
 *
 * @internal
 */
interface StreamableDriverInterface
{
    /**
     * Streams a file using a PSR-7 Response object.
     *
     * @param string $identifier
     * @param array $properties
     * @return ResponseInterface
     */
    public function streamFile(string $identifier, array $properties): ResponseInterface;
}
