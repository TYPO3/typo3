<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Context;

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

use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;

/**
 * The aspect contains whether to show hidden pages, records (content) or even deleted records.
 *
 * Allowed properties:
 * - includeHiddenPages
 * - includeHiddenContent
 * - includeDeletedRecords
 */
class VisibilityAspect implements AspectInterface
{
    /**
     * @var bool
     */
    protected $includeHiddenPages;

    /**
     * @var bool
     */
    protected $includeHiddenContent;

    /**
     * @var bool
     */
    protected $includeDeletedRecords;

    /**
     * @param bool $includeHiddenPages whether to include hidden=1 in pages tables
     * @param bool $includeHiddenContent whether to include hidden=1 in tables except for pages
     * @param bool $includeDeletedRecords whether to include deleted=1 records (only for use in recycler)
     */
    public function __construct(bool $includeHiddenPages = false, bool $includeHiddenContent = false, bool $includeDeletedRecords = false)
    {
        $this->includeHiddenPages = $includeHiddenPages;
        $this->includeHiddenContent = $includeHiddenContent;
        $this->includeDeletedRecords = $includeDeletedRecords;
    }

    /**
     * Fetch the values
     *
     * @param string $name
     * @return int|bool
     * @throws AspectPropertyNotFoundException
     */
    public function get(string $name)
    {
        switch ($name) {
            case 'includeHiddenPages':
                return $this->includeHiddenPages;
            case 'includeHiddenContent':
                return $this->includeHiddenContent;
            case 'includeDeletedRecords':
                return $this->includeDeletedRecords;
        }
        throw new AspectPropertyNotFoundException('Property "' . $name . '" not found in Aspect "' . __CLASS__ . '".', 1527780439);
    }

    public function includeHiddenPages(): bool
    {
        return $this->includeHiddenPages;
    }

    public function includeHiddenContent(): bool
    {
        return $this->includeHiddenContent;
    }

    public function includeDeletedRecords(): bool
    {
        return $this->includeDeletedRecords;
    }
}
