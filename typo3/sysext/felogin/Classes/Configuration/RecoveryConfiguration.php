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

namespace TYPO3\CMS\FrontendLogin\Configuration;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Fluid\View\TemplatePaths;

/**
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
class RecoveryConfiguration implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var string
     */
    protected $forgotHash;

    /**
     * @var Address|null
     */
    protected $replyTo;

    /**
     * @var Address
     */
    protected $sender;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var string
     */
    protected $mailTemplateName;

    /**
     * @var int
     */
    protected $timestamp;

    /**
     * @param Context $context
     * @param ConfigurationManager $configurationManager
     * @param Random $random
     * @param HashService $hashService
     *
     * @throws IncompleteConfigurationException
     * @throws InvalidConfigurationTypeException
     */
    public function __construct(
        Context $context,
        ConfigurationManager $configurationManager,
        Random $random,
        HashService $hashService
    ) {
        $this->context = $context;
        $this->settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS);
        $this->forgotHash = $this->getLifeTimeTimestamp() . '|' . $this->generateHash($random, $hashService);
        $this->resolveFromTypoScript();
    }

    /**
     * Returns the forgot hash.
     *
     * @return string
     */
    public function getForgotHash(): string
    {
        return $this->forgotHash;
    }

    /**
     * Returns an instance of TemplatePaths with paths configured in felogin TypoScript and
     * paths configured in $GLOBALS['TYPO3_CONF_VARS']['MAIL'].
     *
     * @return TemplatePaths
     */
    public function getMailTemplatePaths(): TemplatePaths
    {
        $pathArray = array_replace_recursive(
            [
                'layoutRootPaths'   => $GLOBALS['TYPO3_CONF_VARS']['MAIL']['layoutRootPaths'],
                'templateRootPaths' => $GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths'],
                'partialRootPaths'  => $GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths'],
            ],
            [
                'layoutRootPaths'   => $this->settings['email']['layoutRootPaths'],
                'templateRootPaths' => $this->settings['email']['templateRootPaths'],
                'partialRootPaths'  => $this->settings['email']['partialRootPaths'],
            ]
        );

        return new TemplatePaths($pathArray);
    }

    /**
     * Returns email template name configured in TypoScript
     *
     * @return string
     */
    public function getMailTemplateName(): string
    {
        return $this->mailTemplateName;
    }

    /**
     * Returns TTL timestamp of the forgot hash
     *
     * @return int
     */
    public function getLifeTimeTimestamp(): int
    {
        if ($this->timestamp === null) {
            $lifetimeInHours = $this->settings['forgotLinkHashValidTime'] ?: 12;
            $currentTimestamp = $this->context->getPropertyFromAspect('date', 'timestamp');
            $this->timestamp = $currentTimestamp + 3600 * $lifetimeInHours;
        }

        return $this->timestamp;
    }

    /**
     * Returns reply-to address if configured otherwise null.
     *
     * @return Address|null
     */
    public function getReplyTo(): ?Address
    {
        return $this->replyTo;
    }

    /**
     * Returns the sender. Normally the current typo3 installation.
     *
     * @return Address
     */
    public function getSender(): Address
    {
        return $this->sender;
    }

    protected function generateHash(Random $random, HashService $hashService): string
    {
        $randomString = $random->generateRandomHexString(16);

        return $hashService->generateHmac($randomString);
    }

    protected function resolveFromTypoScript(): void
    {
        $fromAddress = $this->settings['email_from'] ?: $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
        if (empty($fromAddress)) {
            throw new IncompleteConfigurationException(
                'Either "$GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'defaultMailFromAddress\']" or extension key "plugin.tx_felogin_login.settings.email_from" cannot be empty!',
                1573825624
            );
        }
        $fromName = $this->settings['email_fromName'] ?: $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'];
        if (empty($fromName)) {
            throw new IncompleteConfigurationException(
                'Either "$GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'defaultMailFromName\']" or extension key "plugin.tx_felogin_login.settings.email_fromName" cannot be empty!',
                1573825625
            );
        }
        $this->sender = new Address($fromAddress, $fromName);
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToAddress'])) {
            if ($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToName']) {
                $this->replyTo = new Address(
                    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToAddress'],
                    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToName']
                );
            } else {
                $this->replyTo = new Address(
                    $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailReplyToAddress']
                );
            }
        }
        $this->mailTemplateName = $this->settings['email']['templateName'];
        if (empty($this->mailTemplateName)) {
            throw new IncompleteConfigurationException(
                'Key "plugin.tx_felogin_login.settings.email.templateName" cannot be empty!',
                1584998393
            );
        }
    }
}
