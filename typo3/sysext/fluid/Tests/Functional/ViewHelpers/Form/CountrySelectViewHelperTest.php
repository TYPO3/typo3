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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

final class CountrySelectViewHelperTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function renderCorrectlySetsTagNameAndDefaultAttributes(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.countrySelect name="myCountry" value="KW" prependOptionLabel="Please choose" />');
        $result = (new TemplateView($context))->render();
        self::assertStringContainsString('<select name="myCountry"><option value="">Please choose</option>', $result);
        self::assertStringContainsString('<option value="ES">Spain</option>', $result);
    }

    #[Test]
    public function renderCorrectlyPreselectsAValidValue(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.countrySelect name="myCountry" value="KW" alternativeLanguage="fr" prependOptionLabel="Please choose" />');
        $result = (new TemplateView($context))->render();
        self::assertStringContainsString('<option value="KW" selected="selected">Koweït</option>', $result);
    }

    #[Test]
    public function renderCorrectlyUsesLocalizedNames(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.countrySelect name="myCountry" value="KW" alternativeLanguage="fr" prependOptionLabel="Please choose" />');
        $result = (new TemplateView($context))->render();
        self::assertStringContainsString('<option value="ES">Espagne</option>', $result);
    }

    #[Test]
    public function renderShowsPrioritizedCountriesFirst(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.countrySelect required="true" prioritizedCountries="{0: \'GB\', 1: \'US\', 2: \'CA\'}" name="myCountry" value="US" alternativeLanguage="en" prependOptionLabel="Please choose" />');
        $result = (new TemplateView($context))->render();
        self::assertStringContainsString('<select required="required" name="myCountry"><option value="">Please choose</option>
<option value="GB">United Kingdom</option><option value="US" selected="selected">United States</option><option value="CA">Canada</option><option value="AD">Andorra</option>', $result);
    }

    #[Test]
    public function rendersSortsByOptionLabel(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.countrySelect sortByOptionLabel="true" name="myCountry" />');
        $result = (new TemplateView($context))->render();
        self::assertStringContainsString('<option value="DE">Germany</option><option value="GH">Ghana</option>', $result);
    }

    #[Test]
    public function rendersSortsByOptionLabelWithLocalizedOfficialName(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.countrySelect optionLabelField="localizedOfficialName" alternativeLanguage="de" sortByOptionLabel="true" name="myCountry" />');
        $result = (new TemplateView($context))->render();
        self::assertStringContainsString('<option value="BD">Volksrepublik Bangladesh</option><option value="CN">Volksrepublik China</option>', $result);
    }

    #[Test]
    public function renderExcludesCountries(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.countrySelect excludeCountries="{0: \'RU\', 1: \'CN\'}" optionLabelField="localizedOfficialName" alternativeLanguage="de" sortByOptionLabel="true" name="myCountry" />');
        $result = (new TemplateView($context))->render();
        self::assertStringNotContainsString('<option value="CN">Volksrepublik China</option>', $result);
        self::assertStringNotContainsString('<option value="RU">', $result);
    }
    #[Test]
    public function renderOnlyListsWantedCountries(): void
    {
        $serverRequest = (new ServerRequest())->withAttribute('extbase', new ExtbaseRequestParameters());
        $context = $this->get(RenderingContextFactory::class)->create([], new Request($serverRequest));
        $context->getTemplatePaths()->setTemplateSource('<f:form.countrySelect onlyCountries="{0: \'CH\', 1: \'AT\'}" optionLabelField="localizedOfficialName" alternativeLanguage="de" sortByOptionLabel="true" name="myCountry" />');
        $result = (new TemplateView($context))->render();
        self::assertEquals('<select name="myCountry"><option value="AT">Republik Österreich</option><option value="CH">Schweizerische Eidgenossenschaft</option></select>', $result);
    }
}
