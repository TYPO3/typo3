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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\FrontendLogin\Configuration\IncompleteConfigurationException;
use TYPO3\CMS\FrontendLogin\Configuration\RecoveryConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class RecoveryConfigurationTest extends UnitTestCase
{
    /**
     * @var ObjectProphecy|Context
     */
    protected $context;

    /**
     * @var ObjectProphecy|ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var ObjectProphecy|HashService
     */
    protected $hashService;

    /**
     * @var array
     */
    protected $settings = [
        'email_from' => 'example@example.com',
        'email_fromName' => 'TYPO3 Installation',
        'email' => [
            'layoutRootPaths' => [20 => '/some/path/to/a/layout/folder/'],
            'templateRootPaths' => [20 => '/some/path/to/a/template/folder/'],
            'partialRootPaths' => [20 => '/some/path/to/a/partial/folder/'],
            'templateName' => 'someTemplateFileName',
        ],
        'forgotLinkHashValidTime' => 1,
        'replyTo' => ''
    ];

    /**
     * @var RecoveryConfiguration
     */
    protected $subject;

    /**
     * @var ObjectProphecy|LoggerInterface
     */
    protected $logger;

    protected function setUp(): void
    {
        $this->context = $this->prophesize(Context::class);
        $this->configurationManager = $this->prophesize(ConfigurationManager::class);
        $this->hashService = $this->prophesize(HashService::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $this->hashService->generateHmac(Argument::type('string'))->willReturn('some hash');

        parent::setUp();
    }

    /**
     * @throws InvalidConfigurationTypeException
     * @throws IncompleteConfigurationException
     */
    protected function setupSubject(): void
    {
        $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS)->willReturn(
            $this->settings
        );

        $this->subject = new RecoveryConfiguration(
            $this->context->reveal(),
            $this->configurationManager->reveal(),
            new Random(),
            $this->hashService->reveal()
        );

        $this->subject->setLogger($this->logger->reveal());
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
        $this->context->getPropertyFromAspect('date', 'timestamp')->willReturn($timestamp);
        $this->setupSubject();

        $actual = $this->subject->getLifeTimeTimestamp();

        $this->context->getPropertyFromAspect('date', 'timestamp')->shouldHaveBeenCalledTimes(1);
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
        $this->context->getPropertyFromAspect('date', 'timestamp')->willReturn($timestamp);
        $this->setupSubject();

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
            10 => 'EXT:backend/Resources/Private/Templates/'
        ];
        $this->setupSubject();
        $actualTemplatePaths = $this->subject->getMailTemplatePaths();
        self::assertSame(
            [
                Environment::getPublicPath() . '/typo3/sysext/core/Resources/Private/Templates/',
                Environment::getPublicPath() . '/typo3/sysext/backend/Resources/Private/Templates/',
                '/some/path/to/a/template/folder/'
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
            10 => 'EXT:backend/Resources/Private/Templates/'
        ];
        $this->settings['email']['templateRootPaths'] = [10 => '/some/path/to/a/template/folder/'];
        $this->setupSubject();
        $actualTemplatePaths = $this->subject->getMailTemplatePaths();
        self::assertSame(
            [
                Environment::getPublicPath() . '/typo3/sysext/core/Resources/Private/Templates/',
                '/some/path/to/a/template/folder/'
            ],
            $actualTemplatePaths->getTemplateRootPaths()
        );
    }

    /**
     * @test
     */
    public function getMailTemplateNameWillReturnTemplateNameConfiguredInTypoScript()
    {
        $this->setupSubject();
        self::assertSame($this->settings['email']['templateName'], $this->subject->getMailTemplateName());
    }

    /**
     * @test
     */
    public function recoveryConfigurationWillCreateAnInstanceOfAddressIfDefaultMailReplyToAddressIsSet()
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToAddress'] = 'typo3@example.com';
        $this->setupSubject();
        self::assertInstanceOf(Address::class, $this->subject->getReplyTo());
    }

    /**
     * @test
     */
    public function recoveryConfigurationWillCreateAnInstanceOfAddressWithName()
    {
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToName'] = 'TYPO3';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToAddress'] = 'typo3@example.com';
        $this->setupSubject();

        self::assertSame('typo3@example.com', $this->subject->getReplyTo()->getAddress());
        self::assertSame('TYPO3', $this->subject->getReplyTo()->getName());
    }
}
