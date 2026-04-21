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

namespace TYPO3\CMS\Backend\Tests\Functional\Wizard;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Wizard\DTO\SubmissionResult;
use TYPO3\CMS\Backend\Wizard\PageWizardProvider;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageWizardProviderTest extends FunctionalTestCase
{
    private PageWizardProvider $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users_with_editor.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->subject = $this->get(PageWizardProvider::class);
    }

    #[Test]
    public function handleSubmitCreatesPageInsideReferencePageWhenInsertPositionIsInside(): void
    {
        $request = (new ServerRequest('https://example.com/typo3/', 'POST'))->withParsedBody([
            'position' => [
                'pageUid' => 2,
                'insertPosition' => 'inside',
            ],
            'doktype' => '1',
            'data' => [
                'pages' => [
                    'NEW_inside' => [
                        'title' => 'Inside child page',
                    ],
                ],
            ],
        ]);

        $result = $this->subject->handleSubmit($request);

        $newPage = BackendUtility::getRecord('pages', $this->getNewPageUidFromResult($result));
        self::assertSame(2, (int)$newPage['pid']);
    }

    #[Test]
    public function handleSubmitCreatesPageAsSiblingAfterReferencePageWhenInsertPositionIsAfter(): void
    {
        $request = (new ServerRequest('https://example.com/typo3/', 'POST'))->withParsedBody([
            'position' => [
                'pageUid' => 2,
                'insertPosition' => 'after',
            ],
            'doktype' => '1',
            'data' => [
                'pages' => [
                    'NEW_after' => [
                        'title' => 'Sibling page',
                    ],
                ],
            ],
        ]);

        $result = $this->subject->handleSubmit($request);

        // Reference page 2 has pid=1, so inserting "after page 2" must land on pid=1 as a sibling.
        $newPage = BackendUtility::getRecord('pages', $this->getNewPageUidFromResult($result));
        self::assertSame(1, (int)$newPage['pid']);
    }

    private function getNewPageUidFromResult(SubmissionResult $result): int
    {
        $serialized = $result->jsonSerialize();
        self::assertTrue($serialized['success'], 'handleSubmit failed: ' . json_encode($serialized));
        parse_str(parse_url($serialized['finisher']['data']['url'], PHP_URL_QUERY) ?? '', $params);
        return (int)($params['id'] ?? 0);
    }
}
