<?php

declare(strict_types=1);

namespace TYPO3\CMS\Backend\Tests\Functional\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendUtilityTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->importDataSet('PACKAGE:typo3/testing-framework/Resources/Core/Functional/Fixtures/pages.xml');
        $this->setUpBackendUserFromFixture(1);
    }

    /**
     * @test
     */
    public function givenPageIdCanBeExpanded(): void
    {
        $backendUser = $this->getBackendUser();
        $backendUser->groupData['webmounts'] = '1';

        BackendUtility::openPageTree(5, false);

        $expectedSiteHash = [
            '1_5' => '1',
            '1_1' => '1',
            '1_0' => '1'
        ];
        $actualSiteHash = $backendUser->uc['BackendComponents']['States']['Pagetree']['stateHash'];
        self::assertSame($expectedSiteHash, $actualSiteHash);
    }

    /**
     * @test
     */
    public function otherBranchesCanBeClosedWhenOpeningPage(): void
    {
        $backendUser = $this->getBackendUser();
        $backendUser->groupData['webmounts'] = '1';

        BackendUtility::openPageTree(5, false);
        BackendUtility::openPageTree(4, true);

        //the complete branch of uid => 5 should be closed here
        $expectedSiteHash = [
            '1_4' => '1',
            '1_3' => '1',
            '1_2' => '1',
            '1_1' => '1',
            '1_0' => '1'
        ];
        $actualSiteHash = $backendUser->uc['BackendComponents']['States']['Pagetree']['stateHash'];
        self::assertSame($expectedSiteHash, $actualSiteHash);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
