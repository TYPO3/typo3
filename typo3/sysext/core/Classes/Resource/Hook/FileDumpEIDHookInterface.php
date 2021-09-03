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

namespace TYPO3\CMS\Core\Resource\Hook;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;

/**
 * Interface for FileDumpEID Hook to perform some custom security/access checks
 * when accessing file thought FileDumpEID
 * @deprecated since TYPO3 v11 LTS, will be removed in TYPO3 v12.0. Use the PSR-14-based ModifyFileDumpEvent instead.
 */
interface FileDumpEIDHookInterface
{
    /**
     * Perform custom security/access when accessing file
     * Method should issue 403 if access is rejected
     * or 401 if authentication is required via an authorized HTTP authorization scheme.
     * A 401 header must be accompanied by a www-authenticate header!
     *
     * @param \TYPO3\CMS\Core\Resource\ResourceInterface $file
     * @return ResponseInterface|null
     */
    public function checkFileAccess(ResourceInterface $file);
}
