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

namespace TYPO3\CMS\Core\Domain\Repository;

/**
 * Interface for classes which hook into \TYPO3\CMS\Core\Domain\Repository\PageRepository
 */
interface PageRepositoryGetRecordOverlayHookInterface
{
    /**
     * Enables to preprocess a record overlay
     *
     * @param string $table
     * @param array $row
     * @param int $sys_language_content
     * @param string $OLmode
     * @param PageRepository $parent
     */
    public function getRecordOverlay_preProcess($table, &$row, &$sys_language_content, $OLmode, PageRepository $parent);

    /**
     * Enables to postprocess a record overlay
     *
     * @param string $table
     * @param array $row
     * @param int $sys_language_content
     * @param string $OLmode
     * @param PageRepository $parent
     */
    public function getRecordOverlay_postProcess($table, &$row, &$sys_language_content, $OLmode, PageRepository $parent);
}
