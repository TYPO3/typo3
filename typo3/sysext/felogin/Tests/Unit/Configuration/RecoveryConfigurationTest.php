<?php

declare(strict_types=1);

namespace TYPO3\CMS\FrontendLogin\Tests\Unit\Configuration;

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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Fluid\View\StandaloneView;
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
        'email_plainTemplatePath' => '/some/path/to/a/plain/text/file',
        'email_htmlTemplatePath' => '/some/path/to/a/html/file',
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
        $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS)->willReturn($this->settings);

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
    public function hasHtmlMailTemplateShouldReturnFalseAndLogIfNoHtmlTemplatePathIsConfigured(): void
    {
        $this->settings['email_htmlTemplatePath'] = '';
        $this->setupSubject();

        self::assertFalse($this->subject->hasHtmlMailTemplate());

        $this->logger
            ->warning(
                'Key "plugin.tx_felogin_login.settings.email_htmlTemplatePath" is empty or unset.',
                [$this->subject]
            )
            ->shouldHaveBeenCalledTimes(1);
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
    public function getHtmlMailTemplateShouldNotCreateMailTemplateWhilePathIsEmpty(): void
    {
        $this->settings['email_htmlTemplatePath'] = '';
        $this->setupSubject();

        $this->subject->getHtmlMailTemplate();
    }

    /**
     * @test
     */
    public function getPlainMailTemplateThrowsExceptionIfPlainMailTemplatePathIsEmpty(): void
    {
        $this->settings['email_plainTemplatePath'] = '';
        $this->expectException(IncompleteConfigurationException::class);
        $this->expectExceptionCode(1562665945);
        $this->setupSubject();
        $this->subject->getPlainMailTemplate();
    }

    /**
     * @test
     */
    public function getPlainMailTemplateCreatesMailTemplateOnce(): void
    {
        $mailTemplate = $this->prophesize(StandaloneView::class);
        GeneralUtility::addInstance(StandaloneView::class, $mailTemplate->reveal());
        $this->setupSubject();

        $this->subject->getPlainMailTemplate();
        $this->subject->getPlainMailTemplate();

        $mailTemplate->setTemplatePathAndFilename($this->settings['email_plainTemplatePath'])->shouldHaveBeenCalledTimes(1);
    }

    /**
     * @test
     */
    public function getHtmlMailTemplateCreatesMailTemplateOnce(): void
    {
        $mailTemplate = $this->prophesize(StandaloneView::class);
        GeneralUtility::addInstance(StandaloneView::class, $mailTemplate->reveal());
        $this->setupSubject();

        $this->subject->getHtmlMailTemplate();
        $this->subject->getHtmlMailTemplate();

        $mailTemplate->setTemplatePathAndFilename($this->settings['email_htmlTemplatePath'])->shouldHaveBeenCalledTimes(1);
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
}
