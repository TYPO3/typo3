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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class FeatureViewHelperTest extends FunctionalTestCase
{
    public static function renderDataProvider(): array
    {
        return [
            'featureEnabled' => [true, 'enabled'],
            'featureDisabled' => [false, 'disabled'],
            'featureUndefined' => [null, 'disabled'],
        ];
    }

    #[DataProvider('renderDataProvider')]
    #[Test]
    public function render(?bool $featureStatus, string $expected): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['viewHelperTestFeature'] = $featureStatus;
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource(
            '<f:feature name="viewHelperTestFeature"><f:then>enabled</f:then><f:else>disabled</f:else></f:feature>'
        );
        self::assertSame($expected, (new TemplateView($context))->render());
    }
}
