<?php

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

namespace TYPO3\CMS\Install\SystemEnvironment;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Middleware\VerifyHostHeader;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Check TYPO3 setup status
 *
 * This class is a hardcoded requirement check for the TYPO3 setup.
 *
 * The status messages and title *must not* include HTML, use plain
 * text only. The return values of this class are not bound to HTML
 * and can be used in different scopes (eg. as json array).
 *
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class SetupCheck implements CheckInterface
{
    /**
     * @var FlashMessageQueue
     */
    protected $messageQueue;

    /**
     * Get all status information as array with status objects
     */
    public function getStatus(): FlashMessageQueue
    {
        $this->messageQueue = new FlashMessageQueue('install');

        $this->checkTrustedHostPattern();
        $this->checkDownloadsPossible();
        $this->checkSomePhpOpcodeCacheIsLoaded();
        $this->isTrueTypeFontWorking();

        return $this->messageQueue;
    }

    /**
     * Checks the status of the trusted hosts pattern check
     */
    protected function checkTrustedHostPattern()
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] === VerifyHostHeader::ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL) {
            $this->messageQueue->enqueue(new FlashMessage(
                'Trusted hosts pattern is configured to allow all header values. Check the pattern defined in Admin'
                    . ' Tools -> Settings -> Configure Installation-Wide Options -> System -> trustedHostsPattern'
                    . ' and adapt it to expected host value(s).',
                'Trusted hosts pattern is insecure',
                ContextualFeedbackSeverity::WARNING
            ));
        } else {
            if (Environment::isCli()) {
                return;
            }
            $verifyHostHeader = new VerifyHostHeader($GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] ?? '');
            if ($verifyHostHeader->isAllowedHostHeaderValue($_SERVER['HTTP_HOST'], $_SERVER)) {
                $this->messageQueue->enqueue(new FlashMessage(
                    '',
                    'Trusted hosts pattern is configured to allow current host value.'
                ));
            } else {
                $this->messageQueue->enqueue(new FlashMessage(
                    'The trusted hosts pattern will be configured to allow all header values. This is because your $SERVER_NAME:$SERVER_PORT'
                        . ' is "' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . '" while your HTTP_HOST is "'
                        . $_SERVER['HTTP_HOST'] . '", which can happen in specific proxy-routing setups.'
                        . ' After installation, you may want to adapt the pattern defined in Admin'
                        . ' Tools -> Settings -> Configure Installation-Wide Options -> System -> trustedHostsPattern'
                        . ' to reflect the expected host value(s).',
                    'Trusted hosts pattern mismatch',
                    ContextualFeedbackSeverity::ERROR
                ));
            }
        }
    }

    /**
     * Check if it is possible to download external data (e.g. TER)
     * Either allow_url_fopen must be enabled or curl must be used
     */
    protected function checkDownloadsPossible()
    {
        $allowUrlFopen = (bool)ini_get('allow_url_fopen');
        $curlEnabled = function_exists('curl_version');
        if ($allowUrlFopen || $curlEnabled) {
            $this->messageQueue->enqueue(new FlashMessage(
                '',
                'Fetching external URLs is allowed'
            ));
        } else {
            $this->messageQueue->enqueue(new FlashMessage(
                'Either enable PHP runtime setting "allow_url_fopen"' . LF . 'or compile curl into your PHP with --with-curl.',
                'Fetching external URLs is not allowed',
                ContextualFeedbackSeverity::WARNING
            ));
        }
    }

    /**
     * Check if some opcode cache is loaded
     */
    protected function checkSomePhpOpcodeCacheIsLoaded()
    {
        $opcodeCaches = GeneralUtility::makeInstance(OpcodeCacheService::class)->getAllActive();
        if (empty($opcodeCaches)) {
            // Set status to notice. It needs to be notice so email won't be triggered.
            $this->messageQueue->enqueue(new FlashMessage(
                'PHP opcode caches hold a compiled version of executed PHP scripts in'
                    . ' memory and do not require to recompile a script each time it is accessed.'
                    . ' This can be a massive performance improvement and can reduce the load on a'
                    . ' server in general. A parse time reduction by factor three for fully cached'
                    . ' pages can be achieved easily if using an opcode cache.',
                'No PHP opcode cache loaded',
                ContextualFeedbackSeverity::NOTICE
            ));
        } else {
            $status = ContextualFeedbackSeverity::OK;
            $message = '';
            foreach ($opcodeCaches as $opcodeCache => $properties) {
                $message .= 'Name: ' . $opcodeCache . ' Version: ' . $properties['version'];
                $message .= LF;
                if ($properties['warning']) {
                    $status = ContextualFeedbackSeverity::WARNING;
                    $message .= ' ' . $properties['warning'];
                } else {
                    $message .= ' This opcode cache should work correctly and has good performance.';
                }
                $message .= LF;
            }
            // Set title of status depending on severity
            switch ($status) {
                case ContextualFeedbackSeverity::WARNING:
                    $title = 'A possibly malfunctioning PHP opcode cache is loaded';
                    break;
                case ContextualFeedbackSeverity::OK:
                default:
                    $title = 'A PHP opcode cache is loaded';
                    break;
            }
            $this->messageQueue->enqueue(new FlashMessage(
                $message,
                $title,
                $status
            ));
        }
    }

    /**
     * Create true type font test image
     */
    protected function isTrueTypeFontWorking()
    {
        if (function_exists('imageftbbox')) {
            // 20 Pixels at 96 DPI
            $fontSize = (20 / 96 * 72);
            $textDimensions = @imageftbbox(
                $fontSize,
                0,
                __DIR__ . '/../../Resources/Private/Font/vera.ttf',
                'Testing true type support'
            );
            $fontBoxWidth = $textDimensions[2] - $textDimensions[0];
            if ($fontBoxWidth < 300 && $fontBoxWidth > 200) {
                $this->messageQueue->enqueue(new FlashMessage(
                    'Fonts are rendered by FreeType library. '
                        . 'We need to ensure that the final dimensions are as expected. '
                        . 'This server renderes fonts based on 96 DPI correctly',
                    'FreeType True Type Font DPI'
                ));
            } else {
                $this->messageQueue->enqueue(new FlashMessage(
                    'Fonts are rendered by FreeType library. '
                        . 'This server does not render fonts as expected. '
                        . 'Please check your FreeType 2 module.',
                    'FreeType True Type Font DPI',
                    ContextualFeedbackSeverity::NOTICE
                ));
            }
        } else {
            $this->messageQueue->enqueue(new FlashMessage(
                'The core relies on GD library compiled into PHP with freetype2'
                    . ' support. This is missing on your system. Please install it.',
                'PHP GD library freetype2 support missing',
                ContextualFeedbackSeverity::ERROR
            ));
        }
    }
}
