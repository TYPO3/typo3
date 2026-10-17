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
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Fluid\Tests\Functional\Fixtures\ViewHelpers\UserRoleBackedEnum;
use TYPO3\CMS\Fluid\Tests\Functional\Fixtures\ViewHelpers\UserRoleEnum;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class HiddenViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function renderCorrectlySetsTagNameAndDefaultAttributes(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.hidden name="foo" value="bar" />');
        self::assertSame('<input type="hidden" name="foo" value="bar" />', (new TemplateView($context))->render());
    }

    public static function renderSetsValueOfBoundEnumPropertyDataProvider(): array
    {
        return [
            'backed enum' => [UserRoleBackedEnum::EDITOR, '2'],
            'unit enum' => [UserRoleEnum::EDITOR, 'EDITOR'],
        ];
    }

    #[DataProvider('renderSetsValueOfBoundEnumPropertyDataProvider')]
    #[Test]
    public function renderSetsValueOfBoundEnumProperty(\UnitEnum $propertyValue, string $expectedValue): void
    {
        $formObject = new \stdClass();
        $formObject->someProperty = $propertyValue;
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form object="{formObject}" fieldNamePrefix="myFieldPrefix" objectName="myObjectName"><f:form.hidden property="someProperty" /></f:form>');
        $view = new TemplateView($context);
        $view->assign('formObject', $formObject);
        self::assertStringContainsString('<input type="hidden" name="myFieldPrefix[myObjectName][someProperty]" value="' . $expectedValue . '" />', $view->render());
    }
}
