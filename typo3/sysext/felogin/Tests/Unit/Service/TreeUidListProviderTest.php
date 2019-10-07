<?php
declare(strict_types = 1);

namespace TYPO3\CMS\FrontendLogin\Tests\Unit\Service;

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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\FrontendLogin\Helper\TreeUidListProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class TreeUidListProviderTest
 */
class TreeUidListProviderTest extends UnitTestCase
{
    /**
     * @var ContentObjectRenderer|ObjectProphecy
     */
    protected $cObj;
    /**
     * @var TreeUidListProvider
     */
    protected $subject;

    /**
     * @test
     */
    public function getListForIdListDirectlyReturnsPassedListWhileDepthIsZero(): void
    {
        $uidList = '1,2,3';

        self::assertSame($uidList, $this->subject->getListForIdList($uidList));
        $this->cObj->getTreeList(Argument::any(), 0)->shouldNotHaveBeenCalled();
    }

    /**
     * @test
     */
    public function getListForIdListReturnsListOfAllUidListsWithDuplicatedIdsPossible(): void
    {
        $uidList = '1,5';
        $treeLists = ['1,2,3,4,5,6,7', '3,4'];
        $expected = '1,2,3,4,5,6,7,3,4';

        $this->cObj->getTreeList(Argument::any(), 2)->willReturn(...$treeLists);

        self::assertSame($expected, $this->subject->getListForIdList($uidList, 2, false));
    }

    /**
     * @test
     */
    public function getListForIdListShouldRemoveDuplicatedIdsFromList(): void
    {
        $uidList = '1,5';
        $treeLists = ['1,2,3,4,5,6,7', '3,4'];
        $expected = '1,2,3,4,5,6,7';

        $this->cObj->getTreeList(Argument::any(), 2)->willReturn(...$treeLists);

        self::assertSame($expected, $this->subject->getListForIdList($uidList, 2));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->cObj = $this->prophesize(ContentObjectRenderer::class);
        $this->subject = new TreeUidListProvider($this->cObj->reveal());
    }
}
