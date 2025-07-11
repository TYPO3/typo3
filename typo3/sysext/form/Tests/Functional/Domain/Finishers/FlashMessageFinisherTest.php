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

namespace TYPO3\CMS\Form\Tests\Functional\Domain\Finishers;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Finishers\FlashMessageFinisher;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Service\FormValueResolver;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FlashMessageFinisherTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['form'];

    #[Test]
    public function messageBodyAndTitleUseTheTranslatedOptionLabel(): void
    {
        $request = $this->buildExtbaseRequest();

        $subject = new FlashMessageFinisher();
        $subject->setFinisherIdentifier('FlashMessage');
        $subject->injectTranslationService($this->get(TranslationService::class));
        $subject->injectFormValueResolver($this->get(FormValueResolver::class));
        $subject->injectFlashMessageService($this->get(FlashMessageService::class));
        $subject->injectExtensionService($this->get(ExtensionService::class));
        $subject->setOptions([
            'messageBody' => 'Thank you, {single-select}',
            'messageTitle' => '{single-select}',
            'messageCode' => 1,
        ]);

        $subject->execute(new FinisherContext($this->buildFormRuntime($request), $request));

        $messages = $this->get(FlashMessageService::class)
            ->getMessageQueueByIdentifier('extbase.flashmessages.tx_form_formframework')
            ->getAllMessages();
        self::assertCount(1, $messages);
        self::assertSame('Thank you, Mister', $messages[0]->getMessage());
        self::assertSame('Mister', $messages[0]->getTitle());
    }

    private function buildExtbaseRequest(): Request
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $this->get(ExtbaseConfigurationManagerInterface::class)->setRequest(
            new ServerRequest()
                ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
                ->withAttribute('frontend.typoscript', $frontendTypoScript)
                ->withAttribute('language', new SiteLanguage(0, 'en_US.UTF-8', new Uri('/'), []))
        );

        $frontendUser = new FrontendUserAuthentication();
        $frontendUser->initializeUserSessionManager();
        $serverRequest = new ServerRequest()
            ->withAttribute('extbase', new ExtbaseRequestParameters())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.user', $frontendUser)
            ->withAttribute('language', new SiteLanguage(0, 'en_US.UTF-8', new Uri('/'), []));
        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;

        return new Request($serverRequest)
            ->withControllerExtensionName('Form')
            ->withPluginName('Formframework');
    }

    private function buildFormRuntime(Request $request): FormRuntime
    {
        $formDefinition = $this->get(ArrayFormFactory::class)->build([
            'type' => 'Form',
            'identifier' => 'test-form',
            'label' => 'Test form',
            'prototypeName' => 'standard',
            'renderables' => [
                [
                    'type' => 'Page',
                    'identifier' => 'page-1',
                    'label' => 'Page 1',
                    'renderables' => [
                        [
                            'type' => 'SingleSelect',
                            'identifier' => 'single-select',
                            'label' => 'Single',
                            'properties' => [
                                'options' => [
                                    'mr' => 'Mister',
                                    'mrs' => 'Missis',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], null, new ServerRequest());
        $formRuntime = $formDefinition->bind($request);
        $formRuntime->getFormState()->setFormValue('single-select', 'mr');
        return $formRuntime;
    }
}
