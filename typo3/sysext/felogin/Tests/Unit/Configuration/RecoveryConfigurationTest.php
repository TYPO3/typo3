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

namespace TYPO3\CMS\FrontendLogin\Tests\Unit\Configuration;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\FrontendLogin\Configuration\IncompleteConfigurationException;
use TYPO3\CMS\FrontendLogin\Configuration\RecoveryConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RecoveryConfigurationTest extends UnitTestCase
{
    protected MockObject&ConfigurationManager $configurationManager;

    protected array $settings = [
        'email_from' => 'example@example.com',
        'email_fromName' => 'TYPO3 Installation',
        'email' => [
            'layoutRootPaths' => [20 => '/some/path/to/a/layout/folder/'],
            'templateRootPaths' => [20 => '/some/path/to/a/template/folder/'],
            'partialRootPaths' => [20 => '/some/path/to/a/partial/folder/'],
            'templateName' => 'someTemplateFileName',
        ],
        'forgotLinkHashValidTime' => 1,
        'replyTo' => '',
    ];

    protected RecoveryConfiguration $subject;
    protected LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->configurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $this->logger = new NullLogger();

        parent::setUp();
    }

    protected function setupSubject(Context $context = null): void
    {
        if (!$context instanceof Context) {
            $context = new Context();
        }

        $this->configurationManager->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS)
            ->willReturn($this->settings);

        $hashService = $this->getMockBuilder(HashService::class)->disableOriginalConstructor()->getMock();
        $hashService->method('generateHmac')->willReturn('some hash');

        $this->subject = new RecoveryConfiguration(
            $context,
            $this->configurationManager,
            new Random(),
            $hashService
        );

        $this->subject->setLogger($this->logger);
    }

    /**
     * @test
     */
    public function getSenderShouldReturnAddressWithFallbackFromGlobals(): void
    {
        $this->settings['email_from'] = null;
        $this->settings['email_fromName'] = null;
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'no-reply@example.com';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'Example Inc.';

        $this->setupSubject();

        $sender = $this->subject->getSender();

        self::assertSame(
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'],
            $sender->getAddress()
        );
        self::assertSame(
            $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'],
            $sender->getName()
        );
    }

    /**
     * @test
     */
    public function getSenderShouldReturnAddressWithConfigFromTypoScript(): void
    {
        $this->setupSubject();

        $sender = $this->subject->getSender();

        self::assertSame(
            $this->settings['email_from'],
            $sender->getAddress()
        );
        self::assertSame(
            $this->settings['email_fromName'],
            $sender->getName()
        );
    }

    /**
     * @test
     */
    public function getEmailTemplateNameThrowsExceptionIfTemplateNameIsEmpty(): void
    {
        $this->settings['email']['templateName'] = '';
        $this->expectException(IncompleteConfigurationException::class);
        $this->expectExceptionCode(1584998393);
        $this->setupSubject();
        $this->subject->getMailTemplateName();
    }

    /**
     * @test
     */
    public function getLifeTimeTimestampShouldReturnTimestamp(): void
    {
        $timestamp = time();
        $expected = $timestamp + 3600 * $this->settings['forgotLinkHashValidTime'];

        $context = new Context(['date' => new DateTimeAspect(new \DateTimeImmutable('@' . $timestamp))]);
        $this->setupSubject($context);

        $actual = $this->subject->getLifeTimeTimestamp();

        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function getForgotHashShouldReturnHashWithLifeTimeTimestamp(): void
    {
        $timestamp = time();
        $expectedTimestamp = $timestamp + 3600 * $this->settings['forgotLinkHashValidTime'];
        $expected = "{$expectedTimestamp}|some hash";

        $context = new Context([
            'date' => new DateTimeAspect(new \DateTimeImmutable('@' . $timestamp)),
        ]);
        $this->setupSubject($context);

        self::assertSame(
            $expected,
            $this->subject->getForgotHash()
        );
    }

    /**
     * @test
     */
    public function getReplyToShouldReturnNullIfNoneAreSet(): void
    {
        $this->setupSubject();

        self::assertNull($this->subject->getReplyTo());
    }

    /**
     * @test
     */
    public function getMailTemplatePathsReturnsAnInstanceOfTemplatePathsObjectWithConfigurationOfTypoScript(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'] = [
            0 => 'EXT:core/Resources/Private/Templates/',
            10 => 'EXT:backend/Resources/Private/Templates/',
        ];
        $this->setupSubject();
        $actualTemplatePaths = $this->subject->getMailTemplatePaths();
        self::assertSame(
            [
                Environment::getPublicPath() . '/typo3/sysext/core/Resources/Private/Templates/',
                Environment::getPublicPath() . '/typo3/sysext/backend/Resources/Private/Templates/',
                '/some/path/to/a/template/folder/',
            ],
            $actualTemplatePaths->getTemplateRootPaths()
        );
    }

    /**
     * @test
     */
    public function getMailTemplatePathsReplacesTemplatePathsWithPathsConfiguredInTypoScript(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'] = [
            0 => 'EXT:core/Resources/Private/Templates/',
            10 => 'EXT:backend/Resources/Private/Templates/',
        ];
        $this->settings['email']['templateRootPaths'] = [10 => '/some/path/to/a/template/folder/'];
        $this->setupSubject();
        $actualTemplatePaths = $this->subject->getMailTemplatePaths();
        self::assertSame(
            [
                Environment::getPublicPath() . '/typo3/sysext/core/Resources/Private/Templates/',
                '/some/path/to/a/template/folder/',
            ],
            $actualTemplatePaths->getTemplateRootPaths()
        );
    }

    /**
     * @test
     */
    public function getMailTemplateNameWillReturnTemplateNameConfiguredInTypoScript(): void
    {
        $this->setupSubject();
        self::assertSame($this->settings['email']['templateName'], $this->subject->getMailTemplateName());
    }

    /**
     * @test
     */
    public function recoveryConfigurationWillCreateAnInstanceOfAddressIfDefaultMailReplyToAddressIsSet(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToAddress'] = 'typo3@example.com';
        $this->setupSubject();
        self::assertInstanceOf(Address::class, $this->subject->getReplyTo());
    }

    /**
     * @test
     */
    public function recoveryConfigurationWillCreateAnInstanceOfAddressWithName(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToName'] = 'TYPO3';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToAddress'] = 'typo3@example.com';
        $this->setupSubject();

        self::assertSame('typo3@example.com', $this->subject->getReplyTo()->getAddress());
        self::assertSame('TYPO3', $this->subject->getReplyTo()->getName());
    }
}
