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

namespace TYPO3\CMS\Fluid\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Service\TemplateFinder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Validation\TemplateValidator;
use TYPO3Fluid\Fluid\Validation\TemplateValidatorResult;

final class TemplateAnalyzerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    // Populated in setUp() because it needs Environment prefixes
    private array $templateFixtures = [];

    protected function setUp(): void
    {
        // Load all system extensions, only then fluid can
        // chew through them.
        $this->coreExtensionsToLoad = array_merge(
            array_values($this->coreExtensionsToLoad),
            array_values($this->fetchAllSystemExtensions())
        );
        parent::setUp();
        $prefix = Environment::getFrameworkBasePath() . '/fluid/Tests/Functional';
        $this->templateFixtures = [
            $prefix . '/Fixtures/InvalidFluidTemplates/invalidTemplateDueToInternalVariable.fluid.html',
            $prefix . '/Fixtures/InvalidFluidTemplates/invalidTemplateDueToSyntaxError.fluid.html',
            $prefix . '/Fixtures/InvalidFluidTemplates/invalidTemplateDueToViewHelper.fluid.html',
        ];
    }

    #[Test]
    public function allCoreTemplatesContainNoAnalysableFluidErrors(): void
    {
        $templateFinder = $this->get(TemplateFinder::class);
        $renderingContextFactory = GeneralUtility::makeInstance(RenderingContextFactory::class);
        $templates = $templateFinder->findTemplatesInAllPackages();
        $results = (new TemplateValidator())->validateTemplateFiles(
            $templates,
            $renderingContextFactory->create(),
        );

        $foundErrors = $this->gatherFluidErrors($results);
        self::assertCount(0, $foundErrors, 'Fluid templates found with errors: ' . implode("\n", $foundErrors));
    }

    #[Test]
    public function templateFailureWillBeReportedAsError(): void
    {
        $renderingContextFactory = GeneralUtility::makeInstance(RenderingContextFactory::class);
        $templates = array_values($this->templateFixtures);
        $results = (new TemplateValidator())->validateTemplateFiles(
            $templates,
            $renderingContextFactory->create(),
        );
        $foundErrors = $this->gatherFluidErrors($results);
        $expectedErrors = [
            'typo3/sysext/fluid/Tests/Functional/Fixtures/InvalidFluidTemplates/invalidTemplateDueToInternalVariable.fluid.html: Fluid parse error in template /typo3/sysext/fluid/Tests/Functional/Fixtures/InvalidFluidTemplates/invalidTemplateDueToInternalVariable.fluid.html, line 3 at character 1. Error: Variable identifiers cannot start with a "_": _somethingInternal (error code 1765900762). Template source chunk: <!-- this template file will be renamed to .fluid.html under test -->' . "\n" . 'Invalid variable {_somethingInternal}' . "\n",
            'typo3/sysext/fluid/Tests/Functional/Fixtures/InvalidFluidTemplates/invalidTemplateDueToSyntaxError.fluid.html: Fluid parse error in template /typo3/sysext/fluid/Tests/Functional/Fixtures/InvalidFluidTemplates/invalidTemplateDueToSyntaxError.fluid.html, line 6 at character 2. Error: You closed a templating tag which you never opened! (error code 1224485838). Template source chunk: </f:notif>',
            'typo3/sysext/fluid/Tests/Functional/Fixtures/InvalidFluidTemplates/invalidTemplateDueToViewHelper.fluid.html: Fluid parse error in template /typo3/sysext/fluid/Tests/Functional/Fixtures/InvalidFluidTemplates/invalidTemplateDueToViewHelper.fluid.html, line 2 at character 2. Error: Unknown Namespace: invalid (error code 0). Template source chunk: <invalid:viewHelper />',
        ];
        self::assertEquals($expectedErrors, $foundErrors);
    }

    private function fetchAllSystemExtensions(): array
    {
        $systemExtensions = [];
        $iterator = new \DirectoryIterator(ORIGINAL_ROOT . '/typo3/sysext');
        foreach ($iterator as $item) {
            if ($item->isDot() || !$item->isDir()) {
                continue;
            }
            $extensionName = $item->getBasename();
            $systemExtensions[] = $extensionName;
        }
        return $systemExtensions;
    }

    /**
     * Helper method to insert template files and error messages found
     * while analyzing into an array, to be compared against expectation(s).
     *
     * @param TemplateValidatorResult[] $results
     * @return string[]
     */
    private function gatherFluidErrors(array $results): array
    {
        $foundErrors = [];
        foreach ($results as $result) {
            $templateFile = $result->path;
            if (str_starts_with($templateFile, Environment::getProjectPath())) {
                $templateFile = substr($templateFile, strlen(Environment::getProjectPath()) + 1);
            }

            foreach ($result->errors as $error) {
                $foundErrors[] = $templateFile . ': ' . str_replace(Environment::getProjectPath(), '', $error->getMessage());
            }
        }

        return $foundErrors;
    }
}
