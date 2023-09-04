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

namespace TYPO3\CMS\Core\Tests\Functional\TypoScript\IncludeTree\Event;

use TYPO3\CMS\Core\TypoScript\UserTsConfigFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class BeforeLoadedUserTsConfigEventTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_tsconfig_event',
    ];

    /**
     * @test
     */
    public function globalUserTsconfigIsAddedByEvent(): void
    {
        $subject = $this->get(UserTsConfigFactory::class);
        $this->importCSVDataSet(__DIR__ . '/../../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);

        $pageTsConfig = $subject->create($backendUser);

        self::assertSame('two', $pageTsConfig->getUserTsConfigArray()['number']);
    }
}
