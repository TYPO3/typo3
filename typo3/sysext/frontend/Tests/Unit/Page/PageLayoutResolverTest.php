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

namespace TYPO3\CMS\Frontend\Tests\Unit\Page;

use TYPO3\CMS\Frontend\Page\PageLayoutResolver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PageLayoutResolverTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getLayoutForPageFetchesSelectedPageDirectly(): void
    {
        $subject = new PageLayoutResolver();
        $result = $subject->getLayoutForPage(['backend_layout' => '1'], ['does-not-matter']);
        self::assertEquals($result, '1');
    }

    /**
     * @test
     */
    public function getLayoutForPageTreatsSpecialMinusOneValueAsNone(): void
    {
        $subject = new PageLayoutResolver();
        $result = $subject->getLayoutForPage(['backend_layout' => '-1'], ['does-not-matter']);
        self::assertEquals($result, 'none');
    }

    /**
     * @test
     */
    public function getLayoutForPageTreatsSpecialValueZeroOrEmptyAsDefaultWithEmptyRootLine(): void
    {
        $subject = new PageLayoutResolver();
        $parentPages = [['backend_layout' => '']];
        $page = ['backend_layout' => '0'];
        $result = $subject->getLayoutForPage($page, array_merge([$page], $parentPages));
        self::assertEquals($result, 'default');
        $page = ['backend_layout' => ''];
        $result = $subject->getLayoutForPage($page, array_merge([$page], $parentPages));
        self::assertEquals($result, 'default');
    }

    /**
     * @test
     */
    public function getLayoutForPageTreatsSpecialValueZeroOrEmptyAsDefaultWhenNothingGivenInRootLine(): void
    {
        $subject = new PageLayoutResolver();
        // No layout specified for current page
        $page = ['backend_layout' => ''];
        $parentPages = [['uid' => 13, 'backend_layout' => 'does-not-matter'], ['uid' => 1, 'backend_layout_next_level' => '0']];
        $result = $subject->getLayoutForPage($page, array_merge([$page], $parentPages));
        self::assertEquals($result, 'default');
    }

    /**
     * @test
     */
    public function getLayoutForPageFetchesRootLinePagesUpUntilSomethingWasFound(): void
    {
        $subject = new PageLayoutResolver();
        // No layout specified for current page
        $page = ['backend_layout' => ''];
        $parentPages = [['uid' => 13, 'backend_layout' => 'does-not-matter', 'backend_layout_next_level' => ''], ['uid' => 1, 'backend_layout_next_level' => 'regular']];
        $result = $subject->getLayoutForPage($page, array_merge([$page], $parentPages));
        self::assertEquals($result, 'regular');
    }

    /**
     * @test
     */
    public function getLayoutForPageFetchesRootLinePagesUpWhenNoneWasSelectedExplicitly(): void
    {
        $subject = new PageLayoutResolver();
        // No layout specified for current page
        $page = ['backend_layout' => ''];
        $parentPages = [['uid' => 13, 'backend_layout' => 'does-not-matter'], ['uid' => 15, 'backend_layout_next_level' => '-1'], ['uid' => 1, 'backend_layout_next_level' => 'regular']];
        $result = $subject->getLayoutForPage($page, array_merge([$page], $parentPages));
        self::assertEquals($result, 'none');
    }
}
