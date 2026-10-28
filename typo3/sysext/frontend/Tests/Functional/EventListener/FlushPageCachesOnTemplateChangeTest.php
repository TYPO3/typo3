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

namespace TYPO3\CMS\Frontend\Tests\Functional\EventListener;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Flush\CacheFlushDemand;
use TYPO3\CMS\Core\Cache\Flush\CacheFlusher;
use TYPO3\CMS\Core\Cache\Flush\CacheFlushingContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FlushPageCachesOnTemplateChangeTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FlushPageCachesOnTemplateChange.csv');
    }

    #[Test]
    public function changedTemplateFlushesPagesGroupEvenIfTheContextDoesNotPermitIt(): void
    {
        $plan = $this->get(CacheFlusher::class)->resolve(
            CacheFlushDemand::forRecord('sys_template', 1),
            new CacheFlushingContext(canFlushPagesGroup: false),
        );

        self::assertSame(['pages'], $plan->getGroups());
    }

    #[Test]
    public function changedContentDoesNotFlushPagesGroup(): void
    {
        $plan = $this->get(CacheFlusher::class)->resolve(CacheFlushDemand::forRecord('tt_content', 1));

        self::assertSame([], $plan->getGroups());
    }
}
