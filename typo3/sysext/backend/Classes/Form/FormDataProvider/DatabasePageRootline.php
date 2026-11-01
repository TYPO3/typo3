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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Domain\PageAncestryResolver;

/**
 * Set rootline
 */
class DatabasePageRootline implements FormDataProviderInterface
{
    public function __construct(
        private readonly PageAncestryResolver $pageAncestryResolver,
    ) {}

    /**
     * Fetch rootline
     *
     * @return array
     */
    public function addData(array $result)
    {
        $result['rootline'] = $this->pageAncestryResolver
            ->resolve((int)$result['effectivePid'], (int)($GLOBALS['BE_USER']->workspace ?? 0))
            ->toLegacyRootLine();
        return $result;
    }
}
