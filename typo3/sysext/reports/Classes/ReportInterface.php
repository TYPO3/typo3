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

namespace TYPO3\CMS\Reports;

/**
 * Interface for classes which provide a report.
 */
interface ReportInterface
{
    /**
     * Returns the content for a report
     */
    public function getReport(): string;

    /**
     * Returns unique identifier of the report
     */
    public function getIdentifier(): string;

    /**
     * Returns title of the report
     */
    public function getTitle(): string;

    /**
     * Returns description of the report
     */
    public function getDescription(): string;

    /**
     * Returns the identifier of the icon used for the report
     */
    public function getIconIdentifier(): string;
}
