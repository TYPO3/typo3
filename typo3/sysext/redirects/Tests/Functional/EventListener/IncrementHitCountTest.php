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

namespace TYPO3\CMS\Redirects\Tests\Functional\EventListener;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Redirects\Event\RedirectWasHitEvent;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class IncrementHitCountTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/Fixtures/Redirects.xml');
    }

    /**
     * @test
     */
    public function hitCountIsCorrectlyIncremented(): void
    {
        $matchedRecord = BackendUtility::getRecord('sys_redirect', 12);

        // Assert current hit count
        self::assertEquals(3, (int)$matchedRecord['hitcount']);

        $this->getContainer()->get(EventDispatcherInterface::class)->dispatch(
            new RedirectWasHitEvent(
                new ServerRequest('https://acme.com'),
                new RedirectResponse($matchedRecord['target']),
                $matchedRecord,
                new Uri($matchedRecord['target'])
            )
        );

        // Assert hit count is not incremented, due to missing feature flag
        self::assertEquals(3, (int)(BackendUtility::getRecord('sys_redirect', 12)['hitcount']));

        // Set flag
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['redirects.hitCount'] = true;

        // Use another record, which has "disable_hitcount" set
        $matchedRecord = BackendUtility::getRecord('sys_redirect', 13);

        // Assert current hit count
        self::assertEquals(0, (int)$matchedRecord['hitcount']);

        $this->getContainer()->get(EventDispatcherInterface::class)->dispatch(
            new RedirectWasHitEvent(
                new ServerRequest('https://acme.com'),
                new RedirectResponse($matchedRecord['target']),
                $matchedRecord,
                new Uri($matchedRecord['target'])
            )
        );

        // Assert hit count is not incremented, due to records' disable flag
        self::assertEquals(0, (int)(BackendUtility::getRecord('sys_redirect', 13)['hitcount']));

        // Check record=12 again, as the feature flag is now enabled and the hit count is not disabled for the record
        $matchedRecord = BackendUtility::getRecord('sys_redirect', 12);

        // Assert current hit count
        self::assertEquals(3, (int)$matchedRecord['hitcount']);

        $this->getContainer()->get(EventDispatcherInterface::class)->dispatch(
            new RedirectWasHitEvent(
                new ServerRequest('https://acme.com'),
                new RedirectResponse($matchedRecord['target']),
                $matchedRecord,
                new Uri($matchedRecord['target'])
            )
        );

        // Assert incremented hit count
        self::assertEquals(4, (int)(BackendUtility::getRecord('sys_redirect', 12)['hitcount']));
    }
}
