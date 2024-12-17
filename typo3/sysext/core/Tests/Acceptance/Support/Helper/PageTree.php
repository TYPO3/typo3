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

namespace TYPO3\CMS\Core\Tests\Acceptance\Support\Helper;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

final class PageTree extends AbstractTree
{
    public static $treeSelector = '#typo3-pagetree-treeContainer';

    /**
     * Inject our core AcceptanceTester actor into PageTree
     */
    public function __construct(ApplicationTester $I)
    {
        $this->tester = $I;
    }

    /**
     * Get node identifier of given page.
     */
    public function getPageXPathByPageName(string $pageName): string
    {
        return '//*[@class="node-name" and text()=\'' . $pageName . '\']/..';
    }
}
