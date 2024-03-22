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

namespace TYPO3\CMS\Core\Tests\Functional\Page;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Page\PageLayoutResolver;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageLayoutResolverTest extends FunctionalTestCase
{
    #[Test]
    public function getLayoutIdentifierForPageFetchesSelectedPageDirectly(): void
    {
        $subject = $this->get(PageLayoutResolver::class);
        $result = $subject->getLayoutIdentifierForPage(['backend_layout' => '1'], ['does-not-matter']);
        self::assertEquals('1', $result);
    }

    #[Test]
    public function getLayoutIdentifierForPageTreatsSpecialMinusOneValueAsNone(): void
    {
        $subject = $this->get(PageLayoutResolver::class);
        $result = $subject->getLayoutIdentifierForPage(['backend_layout' => '-1'], ['does-not-matter']);
        self::assertEquals('none', $result);
    }

    #[Test]
    public function getLayoutIdentifierForPageTreatsSpecialValueZeroOrEmptyAsDefaultWithEmptyRootLine(): void
    {
        $subject = $this->get(PageLayoutResolver::class);
        $parentPages = [['backend_layout' => '']];
        $page = ['backend_layout' => '0', 'uid' => 123];
        $result = $subject->getLayoutIdentifierForPage($page, array_merge([$page], $parentPages));
        self::assertEquals('default', $result);
        $page = ['backend_layout' => '', 'uid' => 123];
        $result = $subject->getLayoutIdentifierForPage($page, array_merge([$page], $parentPages));
        self::assertEquals('default', $result);
    }

    #[Test]
    public function getLayoutIdentifierForPageTreatsSpecialValueZeroOrEmptyAsDefaultWhenNothingGivenInRootLine(): void
    {
        $subject = $this->get(PageLayoutResolver::class);
        // No layout specified for current page
        $page = ['backend_layout' => '', 'uid' => 123];
        $parentPages = [['uid' => 13, 'backend_layout' => 'does-not-matter'], ['uid' => 1, 'backend_layout_next_level' => '0']];
        $result = $subject->getLayoutIdentifierForPage($page, array_merge([$page], $parentPages));
        self::assertEquals('default', $result);
    }

    #[Test]
    public function getLayoutIdentifierForPageFetchesRootLinePagesUpUntilSomethingWasFound(): void
    {
        $subject = $this->get(PageLayoutResolver::class);
        // No layout specified for current page
        $page = ['backend_layout' => '', 'uid' => 123];
        $parentPages = [['uid' => 13, 'backend_layout' => 'does-not-matter', 'backend_layout_next_level' => ''], ['uid' => 1, 'backend_layout_next_level' => 'regular']];
        $result = $subject->getLayoutIdentifierForPage($page, array_merge([$page], $parentPages));
        self::assertEquals('regular', $result);
    }

    #[Test]
    public function getLayoutIdentifierForPageFetchesRootLinePagesUpWhenNoneWasSelectedExplicitly(): void
    {
        $subject = $this->get(PageLayoutResolver::class);
        // No layout specified for current page
        $page = ['backend_layout' => '', 'uid' => 123];
        $parentPages = [['uid' => 13, 'backend_layout' => 'does-not-matter'], ['uid' => 15, 'backend_layout_next_level' => '-1'], ['uid' => 1, 'backend_layout_next_level' => 'regular']];
        $result = $subject->getLayoutIdentifierForPage($page, array_merge([$page], $parentPages));
        self::assertEquals('none', $result);
    }
}
