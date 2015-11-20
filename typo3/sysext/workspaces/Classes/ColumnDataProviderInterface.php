<?php
namespace TYPO3\CMS\Workspaces;

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
 * Interface for (additional) columns.
 */
interface ColumnDataProviderInterface
{
    /**
     * @return array
     */
    public function getDefinition();

    /**
     * @param Domain\Model\CombinedRecord $combinedRecord
     * @return string|int|array
     */
    public function getData(\TYPO3\CMS\Workspaces\Domain\Model\CombinedRecord $combinedRecord);
}
