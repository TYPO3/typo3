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

namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Menu\Fixtures;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;

final class AbstractMenuContentObjectFixture extends AbstractMenuContentObject
{
    public $conf = [];
    public $mconf = [];
    public $sys_page;
    public $id;
    public $menuArr;
    public ServerRequestInterface $request;

    public function isItemState($kind, $key)
    {
        return parent::isItemState($kind, $key);
    }

    public function menuTypoLink(array $page, string $oTarget, $addParams, $typeOverride, ?int $overridePageId = null): LinkResultInterface
    {
        return parent::menuTypoLink($page, $oTarget, $addParams, $typeOverride, $overridePageId);
    }

    public function sectionIndex($altSortField, $pid = null)
    {
        return parent::sectionIndex($altSortField, $pid);
    }

    protected function getRuntimeCache(): FrontendInterface
    {
        return new NullFrontend('testing');
    }
}
