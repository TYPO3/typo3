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

namespace TYPO3\CMS\Styleguide\UserFunctions\FormEngine;

/**
 * A user function used in select_33
 *
 * @internal
 */
final class TypeSelect33ItemsProcFunc
{
    /**
     * Add two items to existing ones
     *
     * @param array $params
     */
    public function itemsProcFunc(&$params): void
    {
        $params['items'][] = ['item 1 from itemProcFunc()', 'val1'];
        $params['items'][] = ['item 2 from itemProcFunc()', 'val2'];
    }
}
