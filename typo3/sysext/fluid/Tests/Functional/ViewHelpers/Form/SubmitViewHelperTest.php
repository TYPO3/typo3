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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Form;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Tests\Functional\Fixtures\ViewHelpers\UserRoleBackedEnum;
use TYPO3\CMS\Fluid\Tests\Functional\Fixtures\ViewHelpers\UserRoleEnum;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class SubmitViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function renderCorrectlySetsTagNameAndDefaultAttributes(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.submit value="foo" name="bar" />');
        self::assertSame('<input type="submit" value="foo" name="bar" />', (new TemplateView($context))->render());
    }

    public static function renderSetsValueOfEnumValueArgumentDataProvider(): array
    {
        return [
            'backed enum' => [UserRoleBackedEnum::EDITOR, '2'],
            'unit enum' => [UserRoleEnum::EDITOR, 'EDITOR'],
        ];
    }

    #[DataProvider('renderSetsValueOfEnumValueArgumentDataProvider')]
    #[Test]
    public function renderSetsValueOfEnumValueArgument(\UnitEnum $value, string $expectedValue): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:form.submit value="{value}" name="bar" />');
        $view = new TemplateView($context);
        $view->assign('value', $value);
        self::assertSame('<input type="submit" value="' . $expectedValue . '" name="bar" />', $view->render());
    }
}
