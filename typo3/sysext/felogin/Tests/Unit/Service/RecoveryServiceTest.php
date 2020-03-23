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

namespace TYPO3\CMS\FrontendLogin\Tests\Unit\Service;

use Generator;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\CMS\FrontendLogin\Configuration\RecoveryConfiguration;
use TYPO3\CMS\FrontendLogin\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\FrontendLogin\Service\RecoveryService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RecoveryServiceTest extends UnitTestCase
{
    /**
     * @var bool
     */
    protected $resetSingletonInstances = true;

    /**
     * @var FrontendUserRepository|ObjectProphecy
     */
    protected $userRepository;

    /**
     * @var RecoveryConfiguration|ObjectProphecy
     */
    protected $recoveryConfiguration;

    /**
     * @var TemplatePaths|ObjectProphecy
     */
    protected $templatePathsProphecy;

    protected function setUp(): void
    {
        $this->userRepository = $this->prophesize(FrontendUserRepository::class);
        $this->recoveryConfiguration = $this->prophesize(RecoveryConfiguration::class);
        $this->templatePathsProphecy = $this->prophesize(TemplatePaths::class);
    }

    /**
     * @test
     * @dataProvider configurationDataProvider
     *
     * @param string $emailAddress
     * @param array $recoveryConfiguration
     * @param array $userInformation
     * @param Address $receiver
     * @param array $settings
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    public function sendRecoveryEmailShouldGenerateMailFromConfiguration(
        string $emailAddress,
        array $recoveryConfiguration,
        array $userInformation,
        Address $receiver,
        array $settings
    ): void {
        $this->mockRecoveryConfigurationAndUserRepository(
            $emailAddress,
            $recoveryConfiguration,
            $userInformation
        );

        $expectedViewVariables = [
            'receiverName' => $receiver->getName(),
            'url'          => 'some uri',
            'validUntil'   => date($settings['dateFormat'], $recoveryConfiguration['lifeTimeTimestamp'])
        ];

        $configurationManager = $this->prophesize(ConfigurationManager::class);
        $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS)->willReturn(
            $settings
        );

        $languageService = $this->prophesize(LanguageService::class);
        $languageService->sL(Argument::containingString('password_recovery_mail_header'))->willReturn('translation');

        $uriBuilder = $this->prophesize(UriBuilder::class);
        $uriBuilder->setCreateAbsoluteUri(true)->willReturn($uriBuilder->reveal());
        $uriBuilder->uriFor(
            'showChangePassword',
            ['hash' => $recoveryConfiguration['forgotHash']],
            'PasswordRecovery',
            'felogin',
            'Login'
        )->willReturn('some uri');

        $fluidEmailProphecy = $this->setupFluidEmailProphecy($receiver, $expectedViewVariables, $recoveryConfiguration);

        $mailer = $this->prophesize(Mailer::class);
        $mailer->send($fluidEmailProphecy)->shouldBeCalledOnce();

        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $subject = $this->getMockBuilder(RecoveryService::class)
            ->onlyMethods(['getEmailSubject'])
            ->setConstructorArgs(
                [
                    $mailer->reveal(),
                    $eventDispatcherProphecy->reveal(),
                    $configurationManager->reveal(),
                    $this->recoveryConfiguration->reveal(),
                    $uriBuilder->reveal(),
                    $this->userRepository->reveal()
                ]
            )->getMock();
        $subject->method('getEmailSubject')->willReturn('translation');

        $subject->sendRecoveryEmail($emailAddress);
    }

    public function configurationDataProvider(): Generator
    {
        yield 'minimal configuration' => [
            'email'                 => 'max@mustermann.de',
            'recoveryConfiguration' => [
                'lifeTimeTimestamp' => 1234567899,
                'forgotHash'        => '0123456789|some hash',
                'sender'            => new Address('typo3@typo3.typo3', 'TYPO3 Installation'),
                'mailTemplateName'  => 'MailTemplateName',
                'replyTo'           => null
            ],
            'userInformation'       => [
                'first_name'  => '',
                'middle_name' => '',
                'last_name'   => '',
                'username'    => 'm.mustermann'
            ],
            'receiver'              => new Address('max@mustermann.de', 'm.mustermann'),
            'settings'              => ['dateFormat' => 'Y-m-d H:i']
        ];
        yield 'minimal configuration add replyTo Address' => [
            'email'                 => 'max@mustermann.de',
            'recoveryConfiguration' => [
                'lifeTimeTimestamp' => 1234567899,
                'forgotHash'        => '0123456789|some hash',
                'sender'            => new Address('typo3@typo3.typo3', 'TYPO3 Installation'),
                'mailTemplateName'  => 'MailTemplateName',
                'replyTo'           => new Address('reply_to@typo3.typo3', 'reply to TYPO3 Installation'),
            ],
            'userInformation'       => [
                'first_name'  => '',
                'middle_name' => '',
                'last_name'   => '',
                'username'    => 'm.mustermann'
            ],
            'receiver'              => new Address('max@mustermann.de', 'm.mustermann'),
            'settings'              => ['dateFormat' => 'Y-m-d H:i']
        ];
        yield 'html mail provided' => [
            'email'                 => 'max@mustermann.de',
            'recoveryConfiguration' => [
                'lifeTimeTimestamp' => 123456789,
                'forgotHash'        => '0123456789|some hash',
                'sender'            => new Address('typo3@typo3.typo3', 'TYPO3 Installation'),
                'mailTemplateName'  => 'MailTemplateName',
                'replyTo'           => null
            ],
            'userInformation'       => [
                'first_name'  => '',
                'middle_name' => '',
                'last_name'   => '',
                'username'    => 'm.mustermann'
            ],
            'receiver'              => new Address('max@mustermann.de', 'm.mustermann'),
            'settings'              => ['dateFormat' => 'Y-m-d H:i']
        ];
        yield 'complex display name instead of username' => [
            'email'                 => 'max@mustermann.de',
            'recoveryConfiguration' => [
                'lifeTimeTimestamp' => 123456789,
                'forgotHash'        => '0123456789|some hash',
                'sender'            => new Address('typo3@typo3.typo3', 'TYPO3 Installation'),
                'mailTemplateName'  => 'MailTemplateName',
                'replyTo'           => null
            ],
            'userInformation'       => [
                'first_name'  => 'Max',
                'middle_name' => 'Maximus',
                'last_name'   => 'Mustermann',
                'username'    => 'm.mustermann'
            ],
            'receiver'              => new Address('max@mustermann.de', 'Max Maximus Mustermann'),
            'settings'              => ['dateFormat' => 'Y-m-d H:i']
        ];
        yield 'custom dateFormat and no middle name' => [
            'email'                 => 'max@mustermann.de',
            'recoveryConfiguration' => [
                'lifeTimeTimestamp' => 987654321,
                'forgotHash'        => '0123456789|some hash',
                'sender'            => new Address('typo3@typo3.typo3', 'TYPO3 Installation'),
                'mailTemplateName'  => 'MailTemplateName',
                'replyTo'           => null
            ],
            'userInformation'       => [
                'first_name'  => 'Max',
                'middle_name' => '',
                'last_name'   => 'Mustermann',
                'username'    => 'm.mustermann'
            ],
            'receiver'              => new Address('max@mustermann.de', 'Max Mustermann'),
            'settings'              => ['dateFormat' => 'Y-m-d']
        ];
    }

    /**
     * @param string $emailAddress
     * @param array $recoveryConfiguration
     * @param array $userInformation
     */
    protected function mockRecoveryConfigurationAndUserRepository(
        string $emailAddress,
        array $recoveryConfiguration,
        array $userInformation
    ): void {
        $this->recoveryConfiguration->getForgotHash()->willReturn($recoveryConfiguration['forgotHash']);
        $this->recoveryConfiguration->getLifeTimeTimestamp()->willReturn($recoveryConfiguration['lifeTimeTimestamp']);
        $this->recoveryConfiguration->getSender()->willReturn($recoveryConfiguration['sender']);
        $this->recoveryConfiguration->getMailTemplateName()->willReturn($recoveryConfiguration['mailTemplateName']);
        $this->recoveryConfiguration->getReplyTo()->willReturn($recoveryConfiguration['replyTo']);

        $this->templatePathsProphecy->setTemplateRootPaths(['/some/path/to/a/template/folder/']);
        $this->recoveryConfiguration->getMailTemplatePaths()->willReturn($this->templatePathsProphecy->reveal());

        $this->userRepository->updateForgotHashForUserByEmail(
            $emailAddress,
            GeneralUtility::hmac($recoveryConfiguration['forgotHash'])
        )->shouldBeCalledOnce();

        $this->userRepository->fetchUserInformationByEmail($emailAddress)->willReturn($userInformation);
    }

    /**
     * @param Address $receiver
     * @param array $expectedViewVariables
     * @param array $recoveryConfiguration
     *
     * @return ObjectProphecy|FluidEmail
     */
    private function setupFluidEmailProphecy(
        Address $receiver,
        array $expectedViewVariables,
        array $recoveryConfiguration
    ) {
        $fluidEmailProphecy = $this->prophesize(FluidEmail::class);
        GeneralUtility::addInstance(FluidEmail::class, $fluidEmailProphecy->reveal());
        $fluidEmailProphecy->subject('translation')->willReturn($fluidEmailProphecy);
        $fluidEmailProphecy->to($receiver)->willReturn($fluidEmailProphecy);
        $fluidEmailProphecy->assignMultiple($expectedViewVariables)->willReturn($fluidEmailProphecy);
        $fluidEmailProphecy->setTemplate($recoveryConfiguration['mailTemplateName'])->willReturn($fluidEmailProphecy);

        if (!empty($recoveryConfiguration['replyTo'])) {
            $fluidEmailProphecy->addReplyTo($recoveryConfiguration['replyTo'])->willReturn($fluidEmailProphecy);
        }

        return $fluidEmailProphecy;
    }
}
