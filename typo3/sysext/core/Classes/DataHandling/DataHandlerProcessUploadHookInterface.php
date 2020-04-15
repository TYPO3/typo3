<?php

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

namespace TYPO3\CMS\Core\DataHandling;

/**
 * Interface for classes which hook into DataHandler and do additional processing
 * after the upload of a file.
 */
interface DataHandlerProcessUploadHookInterface
{
    /**
     * Post-process a file upload.
     *
     * @param string $filename The uploaded file
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $parentObject
     */
    public function processUpload_postProcessAction(&$filename, DataHandler $parentObject);
}
