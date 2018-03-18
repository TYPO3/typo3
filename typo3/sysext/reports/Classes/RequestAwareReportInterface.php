<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Reports;

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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface for classes which provide a report using information from the current request
 */
interface RequestAwareReportInterface extends ReportInterface
{
    /**
     * Returns the content for a report
     *
     * @param ServerRequestInterface|null $request the currently handled request
     * @return string A reports rendered HTML
     */
    public function getReport(ServerRequestInterface $request = null);
}
