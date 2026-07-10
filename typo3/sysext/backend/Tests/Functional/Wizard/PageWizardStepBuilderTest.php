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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Wizard\PageWizardStepBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageWizardStepBuilderTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet('typo3/sysext/backend/Tests/Functional/Authentication/Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    protected function getRequest(): ServerRequestInterface
    {
        return (new ServerRequest())
            ->withAttribute('site', new NullSite())
            ->withAttribute('normalizedParams', NormalizedParams::createFromRequest(new ServerRequest()));
    }

    #[Test]
    public function getStepsForDokTypeThrowsExceptionIfDokTypeDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1773673880);

        $subject = $this->get(PageWizardStepBuilder::class);
        $subject->getStepsForDokType('non-existing-doktype', 0, $this->getRequest());
    }

    #[Test]
    public function getStepsForDokTypeBuildsSteps(): void
    {
        $GLOBALS['TCA']['pages']['types']['999'] = [
            'showitem' => 'title, nav_title',
            'wizardSteps' => [
                'step1' => [
                    'title' => 'Step 1',
                    'fields' => ['title'],
                    'after' => ['step2'],
                ],
                'step2' => [
                    'title' => 'Step 2',
                    'fields' => ['nav_title'],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $subject = $this->get(PageWizardStepBuilder::class);
        $steps = $subject->getStepsForDokType('999', 0, $this->getRequest());

        self::assertCount(2, $steps);
        self::assertEquals('step2', $steps[0]->jsonSerialize()['configurationData']['key']);
        self::assertEquals('step1', $steps[1]->jsonSerialize()['configurationData']['key']);
    }

    #[Test]
    public function getStepsForDokTypeAddsRequiredStepIfFieldsAreMissing(): void
    {
        $GLOBALS['TCA']['pages']['columns']['title']['config']['required'] = true;
        $GLOBALS['TCA']['pages']['types']['999'] = [
            'showitem' => 'title, nav_title',
            'wizardSteps' => [
                'step1' => [
                    'title' => 'Step 1',
                    'fields' => ['nav_title'],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $subject = $this->get(PageWizardStepBuilder::class);
        $steps = $subject->getStepsForDokType('999', 0, $this->getRequest());

        self::assertCount(2, $steps);
        self::assertEquals('step1', $steps[0]->jsonSerialize()['configurationData']['key']);
        self::assertEquals('requiredFields', $steps[1]->jsonSerialize()['configurationData']['key']);
        self::assertArrayHasKey('title', $steps[1]->jsonSerialize()['configurationData']['labels']);
    }

    #[Test]
    public function getStepsForDokTypeSkipsRequiredStepWhenRequiredFieldsAreHiddenViaDisplayCondition(): void
    {
        $GLOBALS['TCA']['pages']['columns']['only_in_siteroot'] = [
            'label' => 'Only in siteroot',
            'displayCond' => 'FIELD:is_siteroot:=:1',
            'config' => [
                'type' => 'input',
                'required' => true,
            ],
        ];
        $GLOBALS['TCA']['pages']['types']['999'] = [
            'showitem' => 'title, only_in_siteroot',
            'wizardSteps' => [
                'step1' => [
                    'title' => 'Step 1',
                    'fields' => ['title'],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $subject = $this->get(PageWizardStepBuilder::class);
        // A new page has is_siteroot=0, so the display condition does not match and the
        // only required field is hidden. Therefore no "requiredFields" step must be added.
        $steps = $subject->getStepsForDokType('999', 0, $this->getRequest());

        self::assertCount(1, $steps);
        self::assertEquals('step1', $steps[0]->jsonSerialize()['configurationData']['key']);
    }

    #[Test]
    public function getStepsForDokTypeAddsRequiredStepWithOnlyVisibleRequiredFields(): void
    {
        // A required field which is always visible ...
        $GLOBALS['TCA']['pages']['columns']['title']['config']['required'] = true;
        // ... and a required field which is hidden via display condition for a new page.
        $GLOBALS['TCA']['pages']['columns']['only_in_siteroot'] = [
            'label' => 'Only in siteroot',
            'displayCond' => 'FIELD:is_siteroot:=:1',
            'config' => [
                'type' => 'input',
                'required' => true,
            ],
        ];
        $GLOBALS['TCA']['pages']['types']['999'] = [
            'showitem' => 'title, nav_title, only_in_siteroot',
            'wizardSteps' => [
                'step1' => [
                    'title' => 'Step 1',
                    'fields' => ['nav_title'],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $subject = $this->get(PageWizardStepBuilder::class);
        $steps = $subject->getStepsForDokType('999', 0, $this->getRequest());

        // The "requiredFields" step must be added since "title" is still required and visible,
        // but the display-condition-hidden "only_in_siteroot" must not be part of it.
        self::assertCount(2, $steps);
        self::assertEquals('step1', $steps[0]->jsonSerialize()['configurationData']['key']);
        self::assertEquals('requiredFields', $steps[1]->jsonSerialize()['configurationData']['key']);
        self::assertArrayHasKey('title', $steps[1]->jsonSerialize()['configurationData']['labels']);
        self::assertArrayNotHasKey('only_in_siteroot', $steps[1]->jsonSerialize()['configurationData']['labels']);
    }
}
