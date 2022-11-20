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
use TYPO3\TestingFramework\Core\Acceptance\Helper\AbstractPageTree;

class FileTree extends AbstractPageTree
{
    // Selectors
    public static $pageTreeFrameSelector = '#typo3-filestoragetree';
    public static $pageTreeSelector = '#navigation-tree-container';
    public static $treeItemSelector = 'g.nodes > .node';
    public static $treeItemAnchorSelector = 'text.node-name';

    /**
     * Inject our core AcceptanceTester actor into PageTree
     */
    public function __construct(ApplicationTester $I)
    {
        $this->tester = $I;
    }
}
