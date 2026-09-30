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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\Wizard;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\Wizard\SuggestWizardDefaultReceiver;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SuggestWizardDefaultReceiverTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function pidListWorksRecursively(): void
    {
        $subject = new SuggestWizardDefaultReceiver('pages', [
            'pidList' => '1,110',
            'pidDepth' => 3,
        ]);
        $parameters = ['value' => 'Dummy'];
        $result = $subject->queryTable($parameters);
        self::assertCount(5, $result);
    }

    #[Test]
    public function labelIsNotHtmlEncoded(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages_special_titles.csv');
        $subject = new SuggestWizardDefaultReceiver('pages', [
            'pidList' => '1',
        ]);
        $parameters = ['value' => 'quoted'];
        $result = $subject->queryTable($parameters);
        self::assertSame('Tom & Jerry "quoted" \'single\'', $result['pages_200']['label']);
    }

    #[Test]
    public function labelIsCroppedToTitleLength(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages_special_titles.csv');
        $subject = new SuggestWizardDefaultReceiver('pages', [
            'pidList' => '1',
        ]);
        $parameters = ['value' => 'very long'];
        $result = $subject->queryTable($parameters);
        self::assertSame('Tom & Jerry with a very long title that exceeds th...', $result['pages_201']['label']);
    }

    #[Test]
    public function labelWithLessThanSignIsNotStripped(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages_special_titles.csv');
        $subject = new SuggestWizardDefaultReceiver('pages', [
            'pidList' => '1',
        ]);
        $parameters = ['value' => 'Kids'];
        $result = $subject->queryTable($parameters);
        self::assertSame('Kids <12 years, a<b and c>d', $result['pages_203']['label']);
    }

    #[Test]
    public function emptyLabelFallsBackToNoTitle(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages_special_titles.csv');
        $subject = new SuggestWizardDefaultReceiver('pages', [
            'pidList' => '1',
        ]);
        $parameters = ['value' => '202'];
        $result = $subject->queryTable($parameters);
        self::assertSame('[No title]', $result['pages_202']['label']);
    }
}
