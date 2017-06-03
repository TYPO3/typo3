<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Status;

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
 * Status interface
 */
interface StatusInterface extends \JsonSerializable
{
    /**
     * Get severity
     *
     * @return string The severity
     */
    public function getSeverity(): string;

    /**
     * Get severity as integer
     *
     * @return int The severity as int
     */
    public function getSeverityNumber(): int;

    /**
     * Get title
     *
     * @return string The title
     */
    public function getTitle(): string;

    /**
     * Set title
     *
     * @param string $title The title
     */
    public function setTitle(string $title);

    /**
     * Get status message
     *
     * @return string Status message
     */
    public function getMessage(): string;

    /**
     * Set status message
     *
     * @param string $message Status message
     */
    public function setMessage(string $message);
}
