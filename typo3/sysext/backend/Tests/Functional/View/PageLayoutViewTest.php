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

namespace TYPO3\CMS\Backend\Tests\Functional\View;

use Prophecy\Argument;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PageLayoutViewTest extends FunctionalTestCase
{
    /**
     * @var PageLayoutView|AccessibleObjectInterface
     */
    private $subject;

    /**
     * Sets up this test case.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::cetera())->willReturnArgument(0);

        $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $site = new Site('test', 1, [
            'identifier' => 'test',
            'rootPageId' => 1,
            'base' => '/',
            'languages' => [
                [
                    'languageId' => 0,
                    'locale' => '',
                    'base' => '/',
                    'title' => 'default',
                ],
                [
                    'languageId' => 1,
                    'locale' => '',
                    'base' => '/',
                    'title' => 'german',
                ],
                [
                    'languageId' => 2,
                    'locale' => '',
                    'base' => '/',
                    'title' => 'french',
                ],
                [
                    'languageId' => 3,
                    'locale' => '',
                    'base' => '/',
                    'title' => 'polish',
                ],
            ],
        ]);
        $this->subject = $this->getAccessibleMock(PageLayoutView::class, ['dummy'], [$eventDispatcher->reveal()]);
        $this->subject->_set('siteLanguages', $site->getLanguages());
    }

    /**
     * @test
     */
    public function languageSelectorShowsAllAvailableLanguagesForTranslation()
    {
        $this->importCSVDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/View/Fixtures/LanguageSelectorScenarioDefault.csv');

        $result = $this->subject->languageSelector(17);

        $matches = [];

        preg_match_all('/<option value=.+<\/option>/', $result, $matches);
        $resultingOptions = GeneralUtility::trimExplode('</option>', $matches[0][0], true);
        self::assertCount(4, $resultingOptions);
        // first entry is the empty option
        self::assertStringEndsWith('german', $resultingOptions[1]);
        self::assertStringEndsWith('french', $resultingOptions[2]);
        self::assertStringEndsWith('polish', $resultingOptions[3]);
    }

    /**
     * @test
     */
    public function languageSelectorDoesNotOfferLanguageIfTranslationHasBeenDoneAlready()
    {
        $this->importCSVDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/View/Fixtures/LanguageSelectorScenarioTranslationDone.csv');
        $result = $this->subject->languageSelector(17);

        $matches = [];

        preg_match_all('/<option value=.+<\/option>/', $result, $matches);
        $resultingOptions = GeneralUtility::trimExplode('</option>', $matches[0][0], true);
        self::assertCount(3, $resultingOptions);
        // first entry is the empty option
        self::assertStringEndsWith('german', $resultingOptions[1]);
        self::assertStringEndsWith('french', $resultingOptions[2]);
    }
}
