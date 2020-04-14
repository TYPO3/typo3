<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Reports;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface for classes which provide a status report entry using information from the current request
 */
interface RequestAwareStatusProviderInterface extends StatusProviderInterface
{
    /**
     * Returns the status of an extension or (sub)system
     *
     * @param ServerRequestInterface|null $request the currently handled request
     * @return array An array of \TYPO3\CMS\Reports\Status objects
     */
    public function getStatus(ServerRequestInterface $request = null);
}
