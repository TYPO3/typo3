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

namespace TYPO3\CMS\Fluid\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Service\TemplateFinder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TemplateFinderTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/fluid_test',
    ];

    #[Test]
    public function findTemplatesInAllPackages(): void
    {
        $subject = $this->get(TemplateFinder::class);
        $foundTemplates = $subject->findTemplatesInAllPackages();
        self::assertContains($this->instancePath . '/typo3/sysext/core/Resources/Private/Layouts/SystemEmail.fluid.html', $foundTemplates);
        self::assertContains($this->instancePath . '/typo3/sysext/core/Resources/Private/Layouts/SystemEmail.fluid.txt', $foundTemplates);
        self::assertContains($this->instancePath . '/typo3/sysext/backend/Resources/Private/Layouts/Module.fluid.html', $foundTemplates);
        self::assertContains($this->instancePath . '/typo3conf/ext/fluid_test/Resources/Private/FileExtension/Both/Test.fluid.html', $foundTemplates);
        self::assertContains($this->instancePath . '/typo3conf/ext/fluid_test/Resources/Private/FileExtension/Both/Test.fluid.txt', $foundTemplates);
        self::assertNotContains($this->instancePath . '/typo3/sysext/core/Resources/Private/Templates/PageRenderer.html', $foundTemplates);
        self::assertNotContains($this->instancePath . '/typo3/sysext/core/Documentation/guides.xml', $foundTemplates);
        self::assertNotContains($this->instancePath . '/typo3conf/ext/fluid_test/Resources/Private/FileExtension/Both/Test.html', $foundTemplates);
        self::assertNotContains($this->instancePath . '/typo3conf/ext/fluid_test/Resources/Private/FileExtension/Both/Test.txt', $foundTemplates);
        // Tests/ folders are excluded,
        // also paths are added based on the symlinked position (= the way TYPO3 would resolve the file)
        self::assertNotContains($this->instancePath . '/typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/fluid_test/Resources/Private/FileExtension/Both/Test.fluid.html', $foundTemplates);
    }

    #[Test]
    public function findTemplatesInAllPackagesSkipFramework(): void
    {
        $subject = $this->get(TemplateFinder::class);
        $foundTemplates = $subject->findTemplatesInAllPackages(false);
        self::assertContains($this->instancePath . '/typo3conf/ext/fluid_test/Resources/Private/FileExtension/Both/Test.fluid.html', $foundTemplates);
        self::assertNotContains($this->instancePath . '/typo3/sysext/core/Resources/Private/Layouts/SystemEmail.fluid.html', $foundTemplates);
    }
}
