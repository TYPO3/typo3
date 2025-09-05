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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\CMS\FrontendLogin\Configuration\RecoveryConfiguration;
use TYPO3\CMS\FrontendLogin\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\FrontendLogin\Service\RecoveryService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RecoveryServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected MockObject&FrontendUserRepository $userRepository;
    protected MockObject&RecoveryConfiguration $recoveryConfiguration;
    protected MockObject&TemplatePaths $templatePaths;
    protected RequestInterface $extbaseRequest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->createMock(FrontendUserRepository::class);
        $this->recoveryConfiguration = $this->createMock(RecoveryConfiguration::class);
        $this->templatePaths = $this->createMock(TemplatePaths::class);

        $request = new ServerRequest();
        $request = $request->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $normalizedParams = NormalizedParams::createFromRequest($request);
        $request = $request->withAttribute('normalizedParams', $normalizedParams)->withAttribute('extbase', new ExtbaseRequestParameters());
        $this->extbaseRequest = new Request($request);
    }

    public static function configurationDataProvider(): \Generator
    {
        yield 'minimal configuration' => [
            'uid'                 => 1,
            'recoveryConfiguration' => [
                'lifeTimeTimestamp' => 1234567899,
                'forgotHash'        => '0123456789|some hash',
                'sender'            => new Address('typo3@typo3.typo3', 'TYPO3 Installation'),
                'mailTemplateName'  => 'MailTemplateName',
                'replyTo'           => null,
            ],
            'userInformation'       => [
                'uid'         => 1,
                'email'       => 'max@mustermann.de',
                'first_name'  => '',
                'middle_name' => '',
                'last_name'   => '',
                'username'    => 'm.mustermann',
            ],
            'receiver'              => new Address('max@mustermann.de', 'm.mustermann'),
            'settings'              => ['dateFormat' => 'Y-m-d H:i'],
        ];
        yield 'minimal configuration add replyTo Address' => [
            'uid'                 => 1,
            'recoveryConfiguration' => [
                'lifeTimeTimestamp' => 1234567899,
                'forgotHash'        => '0123456789|some hash',
                'sender'            => new Address('typo3@typo3.typo3', 'TYPO3 Installation'),
                'mailTemplateName'  => 'MailTemplateName',
                'replyTo'           => new Address('reply_to@typo3.typo3', 'reply to TYPO3 Installation'),
            ],
            'userInformation'       => [
                'uid'         => 1,
                'email'       => 'max@mustermann.de',
                'first_name'  => '',
                'middle_name' => '',
                'last_name'   => '',
                'username'    => 'm.mustermann',
            ],
            'receiver'              => new Address('max@mustermann.de', 'm.mustermann'),
            'settings'              => ['dateFormat' => 'Y-m-d H:i'],
        ];
        yield 'html mail provided' => [
            'uid'                 => 1,
            'recoveryConfiguration' => [
                'lifeTimeTimestamp' => 123456789,
                'forgotHash'        => '0123456789|some hash',
                'sender'            => new Address('typo3@typo3.typo3', 'TYPO3 Installation'),
                'mailTemplateName'  => 'MailTemplateName',
                'replyTo'           => null,
            ],
            'userInformation'       => [
                'uid'         => 1,
                'email'       => 'max@mustermann.de',
                'first_name'  => '',
                'middle_name' => '',
                'last_name'   => '',
                'username'    => 'm.mustermann',
            ],
            'receiver'              => new Address('max@mustermann.de', 'm.mustermann'),
            'settings'              => ['dateFormat' => 'Y-m-d H:i'],
        ];
        yield 'complex display name instead of username' => [
            'uid'                 => 1,
            'recoveryConfiguration' => [
                'lifeTimeTimestamp' => 123456789,
                'forgotHash'        => '0123456789|some hash',
                'sender'            => new Address('typo3@typo3.typo3', 'TYPO3 Installation'),
                'mailTemplateName'  => 'MailTemplateName',
                'replyTo'           => null,
            ],
            'userInformation'       => [
                'uid'         => 1,
                'email'       => 'max@mustermann.de',
                'first_name'  => 'Max',
                'middle_name' => 'Maximus',
                'last_name'   => 'Mustermann',
                'username'    => 'm.mustermann',
            ],
            'receiver'              => new Address('max@mustermann.de', 'Max Maximus Mustermann (m.mustermann)'),
            'settings'              => ['dateFormat' => 'Y-m-d H:i'],
        ];
        yield 'custom dateFormat and no middle name' => [
            'uid'                 => 1,
            'recoveryConfiguration' => [
                'lifeTimeTimestamp' => 987654321,
                'forgotHash'        => '0123456789|some hash',
                'sender'            => new Address('typo3@typo3.typo3', 'TYPO3 Installation'),
                'mailTemplateName'  => 'MailTemplateName',
                'replyTo'           => null,
            ],
            'userInformation'       => [
                'uid'         => 1,
                'email'       => 'max@mustermann.de',
                'first_name'  => 'Max',
                'middle_name' => '',
                'last_name'   => 'Mustermann',
                'username'    => 'm.mustermann',
            ],
            'receiver'              => new Address('max@mustermann.de', 'Max Mustermann (m.mustermann)'),
            'settings'              => ['dateFormat' => 'Y-m-d'],
        ];
    }

    #[DataProvider('configurationDataProvider')]
    #[Test]
    public function sendRecoveryEmailShouldGenerateMailFromConfiguration(
        int $uid,
        array $recoveryConfiguration,
        array $userInformation,
        Address $receiver,
        array $settings
    ): void {
        $this->mockRecoveryConfigurationAndUserRepository(
            $uid,
            $recoveryConfiguration,
            $userInformation
        );

        $expectedViewVariables = [
            'receiverName' => $receiver->getName(),
            'userData'     => $userInformation,
            'url'          => 'some uri',
            'validUntil'   => date($settings['dateFormat'], $recoveryConfiguration['lifeTimeTimestamp']),
        ];

        $configurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()
            ->getMock();
        $configurationManager->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS)
            ->willReturn($settings);

        $uriBuilder = $this->getMockBuilder(UriBuilder::class)->disableOriginalConstructor()->getMock();
        $uriBuilder->expects($this->once())->method('reset')->willReturn($uriBuilder);
        $uriBuilder->expects($this->once())->method('setRequest')->with($this->extbaseRequest)->willReturn($uriBuilder);
        $uriBuilder->expects($this->once())->method('setCreateAbsoluteUri')->with(true)->willReturn($uriBuilder);
        $uriBuilder->expects($this->once())->method('uriFor')->with(
            'showChangePassword',
            ['hash' => $recoveryConfiguration['forgotHash']],
            'PasswordRecovery',
            'felogin',
            'Login'
        )->willReturn('some uri');

        $fluidEmailMock = $this->setupFluidEmailMock($receiver, $expectedViewVariables, $recoveryConfiguration);

        $mailer = $this->getMockBuilder(MailerInterface::class)->disableOriginalConstructor()->getMock();
        $mailer->expects($this->once())->method('send')->with($fluidEmailMock);

        $eventDispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
        $subject = $this->getMockBuilder(RecoveryService::class)
            ->onlyMethods(['getEmailSubject'])
            ->setConstructorArgs(
                [
                    $mailer,
                    $eventDispatcherMock,
                    $configurationManager,
                    $this->recoveryConfiguration,
                    $uriBuilder,
                    $this->userRepository,
                ]
            )->getMock();
        $subject->method('getEmailSubject')->willReturn('translation');

        $subject->sendRecoveryEmail($this->extbaseRequest, $userInformation, $recoveryConfiguration['forgotHash']);
    }

    private function mockRecoveryConfigurationAndUserRepository(
        int $uid,
        array $recoveryConfiguration,
        array $userInformation
    ): void {
        $this->recoveryConfiguration->method('getForgotHash')->willReturn($recoveryConfiguration['forgotHash']);
        $this->recoveryConfiguration->method('getLifeTimeTimestamp')->willReturn($recoveryConfiguration['lifeTimeTimestamp']);
        $this->recoveryConfiguration->method('getSender')->willReturn($recoveryConfiguration['sender']);
        $this->recoveryConfiguration->method('getMailTemplateName')->willReturn($recoveryConfiguration['mailTemplateName']);
        $this->recoveryConfiguration->method('getReplyTo')->willReturn($recoveryConfiguration['replyTo']);
        $this->recoveryConfiguration->method('getMailTemplatePaths')->willReturn($this->templatePaths);

        $this->userRepository->method('findUserByUsernameOrEmailOnPages')->with($uid, [])->willReturn($userInformation);
    }

    private function setupFluidEmailMock(
        Address $receiver,
        array $expectedViewVariables,
        array $recoveryConfiguration
    ): MockObject&FluidEmail {
        $fluidEmailMock = $this->getMockBuilder(FluidEmail::class)->disableOriginalConstructor()->getMock();
        GeneralUtility::addInstance(FluidEmail::class, $fluidEmailMock);
        $fluidEmailMock->method('subject')->with('translation')->willReturn($fluidEmailMock);
        $fluidEmailMock->method('from')->with($recoveryConfiguration['sender'])->willReturn($fluidEmailMock);
        $fluidEmailMock->method('to')->with($receiver)->willReturn($fluidEmailMock);
        $fluidEmailMock->method('assignMultiple')->with($expectedViewVariables)->willReturn($fluidEmailMock);
        $fluidEmailMock->method('setTemplate')->with($recoveryConfiguration['mailTemplateName'])
            ->willReturn($fluidEmailMock);

        if (!empty($recoveryConfiguration['replyTo'])) {
            $fluidEmailMock->method('addReplyTo')->with($recoveryConfiguration['replyTo'])->willReturn($fluidEmailMock);
        }

        return $fluidEmailMock;
    }
}
