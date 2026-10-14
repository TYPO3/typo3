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

namespace TYPO3\CMS\Form\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RenderViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['form'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/form/Tests/Functional/Fixtures/Extensions/form_caching_tests',
    ];

    #[Test]
    public function renderThrowsWithoutExtbaseRequest(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->writeSiteConfiguration('render_view_helper', $this->buildSiteConfiguration(1, '/'));
        $this->setUpFrontendRootPage(1, ['EXT:form/Tests/Functional/ViewHelpers/Fixtures/RenderWithoutExtbaseContext.typoscript']);

        $this->expectException(RenderingException::class);
        $this->expectExceptionCode(1791965474);
        $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1));
    }
}
