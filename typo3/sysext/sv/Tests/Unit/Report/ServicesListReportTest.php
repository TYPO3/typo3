<?php
declare(strict_types=1);
namespace TYPO3\sv\Tests\Unit\Report;

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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Reports\Controller\ReportController;
use TYPO3\CMS\Sv\Report\ServicesListReport;

/**
 * Test case for class ServicesListReport
 */
class ServicesListReportTest extends UnitTestCase
{
    /**
     * @var ServicesListReport
     */
    protected $subject;

    /**
     * SetUp
     */
    public function setUp()
    {
        $GLOBALS['LANG'] = $this->languageServiceProphecy()->reveal();
        $this->subject = new ServicesListReport(
            $this->reportControllerProphecy()->reveal()
        );
    }

    /**
     * @test
     */
    public function getReportCollectsRelevantDataToAssignThemToTemplateForResponse()
    {
        $standaloneViewProphecy = $this->standaloneViewProphecy();

        $this->subject->getReport();

        $standaloneViewProphecy
            ->assignMultiple(Argument::withEntry('servicesList', []))
            ->shouldHaveBeenCalled();
        $standaloneViewProphecy
            ->assignMultiple(Argument::withKey('searchPaths'))
            ->shouldHaveBeenCalled();
    }

    /**
     * @return ObjectProphecy
     * @internal param $templatePath
     */
    private function standaloneViewProphecy(): ObjectProphecy
    {
        $templatePath = GeneralUtility::getFileAbsFileName(
            'EXT:sv/Resources/Private/Templates/ServicesListReport.html'
        );
        $standaloneViewProphecy = $this->prophesize(StandaloneView::class);
        $standaloneViewProphecy->setTemplatePathAndFilename($templatePath)->shouldBeCalled();
        $standaloneViewProphecy->assignMultiple(Argument::any())->willReturn($standaloneViewProphecy->reveal());
        $standaloneViewProphecy->render()->willReturn('<p>Template output</p>');
        GeneralUtility::addInstance(StandaloneView::class, $standaloneViewProphecy->reveal());

        return $standaloneViewProphecy;
    }

    /**
     * @return ObjectProphecy
     */
    private function languageServiceProphecy(): ObjectProphecy
    {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy
            ->includeLLFile('EXT:sv/Resources/Private/Language/locallang.xlf')
            ->willReturn(null)
            ->shouldBeCalled();
        $languageServiceProphecy->getLL(Argument::any())->willReturn('translation string');
        return $languageServiceProphecy;
    }

    /**
     * @return ObjectProphecy
     */
    private function reportControllerProphecy(): ObjectProphecy
    {
        $reportControllerProphecy = $this->prophesize(ReportController::class);
        return $reportControllerProphecy;
    }
}
