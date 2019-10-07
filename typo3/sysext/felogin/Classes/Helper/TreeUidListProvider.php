<?php
declare(strict_types = 1);

namespace TYPO3\CMS\FrontendLogin\Helper;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
final class TreeUidListProvider
{
    /**
     * @var ContentObjectRenderer
     */
    protected $cObj;

    /**
     * TreeUidListProvider constructor.
     * @param ContentObjectRenderer $cObj
     */
    public function __construct(ContentObjectRenderer $cObj)
    {
        $this->cObj = $cObj;
    }

    /**
     * Fetches uid list of pages beneath passed list of page-ids and returns them as a comma separated string.
     *
     * @param string $uidList Comma separated list of ids.
     * @param int $depth The number of levels to descend. If you want to descend infinitely, just set this to 100 or so. Should be at least "1" since zero will just make the function return (no decend...)
     * @param bool $uniqueIds Removes duplicated ids in returned string if set to true.
     * @return string Comma separated uid list
     */
    public function getListForIdList(string $uidList, int $depth = 0, bool $uniqueIds = true): string
    {
        if ($depth === 0) {
            return $uidList;
        }

        $list = GeneralUtility::trimExplode(',', $uidList);

        foreach ($list as $uid) {
            $pidList[] = $this->cObj->getTreeList($uid, $depth);
        }

        $uidTreeList = implode(',', $pidList ?? []);

        if ($uniqueIds) {
            $uidTreeList = $this->removeDuplicatedIds($uidTreeList);
        }

        return $uidTreeList;
    }

    /**
     * Removes duplicated ids from comma separated uid list
     *
     * @param string $uidList
     * @return string
     */
    protected function removeDuplicatedIds(string $uidList): string
    {
        $uniqueUidArray = array_unique(GeneralUtility::trimExplode(',', $uidList));

        return implode(',', $uniqueUidArray);
    }
}
