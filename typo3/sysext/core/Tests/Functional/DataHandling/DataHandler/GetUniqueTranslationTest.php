<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;

class GetUniqueTranslationTest extends AbstractDataHandlerActionTestCase
{
    /**
     * @var int
     */
    const PAGE_DATAHANDLER = 88;

    /**
     * @var string
     */
    protected $scenarioDataSetDirectory = 'typo3/sysext/core/Tests/Functional/DataHandling/DataHandler/DataSet/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importScenarioDataSet('LiveDefaultPages');
        $this->importScenarioDataSet('LiveDefaultElements');
        $this->backendUser->workspace = 0;
    }

    /**
     * @test
     */
    public function valueOfUniqueFieldExcludedInTranslationIsUntouchedInTranslation(): void
    {
        // Mis-using the "keywords" field in the scenario data-set to check for uniqueness
        $GLOBALS['TCA']['pages']['columns']['keywords']['l10n_mode'] = 'exclude';
        $GLOBALS['TCA']['pages']['columns']['keywords']['config']['eval'] = 'unique';
        $map = $this->actionService->localizeRecord('pages', self::PAGE_DATAHANDLER, 1);
        $newPageId = $map['pages'][self::PAGE_DATAHANDLER];
        $originalLanguageRecord = BackendUtility::getRecord('pages', self::PAGE_DATAHANDLER);
        $translatedRecord = BackendUtility::getRecord('pages', $newPageId);

        $this->assertEquals('datahandler', $originalLanguageRecord['keywords']);
        $this->assertEquals('datahandler', $translatedRecord['keywords']);
    }
}
