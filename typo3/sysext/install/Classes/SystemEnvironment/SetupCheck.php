<?php
namespace TYPO3\CMS\Install\SystemEnvironment;

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

use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Status;

/**
 * Check TYPO3 setup status
 *
 * This class is a hardcoded requirement check for the TYPO3 setup.
 *
 * The status messages and title *must not* include HTML, use plain
 * text only. The return values of this class are not bound to HTML
 * and can be used in different scopes (eg. as json array).
 */
class SetupCheck implements CheckInterface
{
    /**
     * Get all status information as array with status objects
     *
     * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
     */
    public function getStatus(): array
    {
        $status = [];

        $status[] = $this->checkTrustedHostPattern();
        $status[] = $this->checkDownloadsPossible();
        $status[] = $this->checkSystemLocale();
        $status[] = $this->checkLocaleWithUTF8filesystem();
        $status[] = $this->checkSomePhpOpcodeCacheIsLoaded();
        $status[] = $this->isTrueTypeFontWorking();
        $status[] = $this->checkLibXmlBug();

        return $status;
    }

    /**
     * Checks the status of the trusted hosts pattern check
     *
     * @return Status\StatusInterface
     */
    protected function checkTrustedHostPattern()
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['trustedHostsPattern'] === GeneralUtility::ENV_TRUSTED_HOSTS_PATTERN_ALLOW_ALL) {
            $status = new Status\WarningStatus();
            $status->setTitle('Trusted hosts pattern is insecure');
            $status->setMessage('Trusted hosts pattern is configured to allow all header values. Check the pattern defined in Install Tool -> All configuration -> System -> trustedHostsPattern and adapt it to expected host value(s).');
        } else {
            if (GeneralUtility::hostHeaderValueMatchesTrustedHostsPattern($_SERVER['HTTP_HOST'])) {
                $status = new Status\OkStatus();
                $status->setTitle('Trusted hosts pattern is configured to allow current host value.');
            } else {
                $status = new Status\ErrorStatus();
                $status->setTitle('Trusted hosts pattern mismatch');
                $defaultPort = GeneralUtility::getIndpEnv('TYPO3_SSL') ? '443' : '80';
                $status->setMessage(
                    'The trusted hosts pattern will be configured to allow all header values. This is because your $SERVER_NAME:[defaultPort]'
                        . ' is "' . htmlspecialchars($_SERVER['SERVER_NAME']) . ':' . $defaultPort . '" while your HTTP_HOST:SERVER_PORT is "'
                        . htmlspecialchars($_SERVER['HTTP_HOST']) . ':' . htmlspecialchars($_SERVER['SERVER_PORT'])
                        . '". Check the pattern defined in Install Tool -> All'
                        . ' configuration -> System -> trustedHostsPattern and adapt it to expected host value(s).'
                );
            }
        }

        return $status;
    }

    /**
     * Check if it is possible to download external data (e.g. TER)
     * Either allow_url_fopen must be enabled or curl must be used
     *
     * @return Status\OkStatus|Status\WarningStatus
     */
    protected function checkDownloadsPossible()
    {
        $allowUrlFopen = (bool)ini_get('allow_url_fopen');
        $curlEnabled = function_exists('curl_version');
        if ($allowUrlFopen || $curlEnabled) {
            $status = new Status\OkStatus();
            $status->setTitle('Fetching external URLs is allowed');
        } else {
            $status = new Status\WarningStatus();
            $status->setTitle('Fetching external URLs is not allowed');
            $status->setMessage(
                'Either enable PHP runtime setting "allow_url_fopen"' . LF . 'or compile curl into your PHP with --with-curl.'
            );
        }

        return $status;
    }

    /**
     * Check if systemLocale setting is correct (locale exists in the OS)
     *
     * @return Status\StatusInterface
     */
    protected function checkSystemLocale()
    {
        $currentLocale = setlocale(LC_CTYPE, 0);

        // On Windows an empty locale value uses the regional settings from the Control Panel
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'] === '' && TYPO3_OS !== 'WIN') {
            $status = new Status\InfoStatus();
            $status->setTitle('Empty systemLocale setting');
            $status->setMessage(
                '$GLOBALS[TYPO3_CONF_VARS][SYS][systemLocale] is not set. This is fine as long as no UTF-8' .
                ' file system is used.'
            );
        } elseif (setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']) === false) {
            $status = new Status\ErrorStatus();
            $status->setTitle('Incorrect systemLocale setting');
            $status->setMessage(
                'Current value of the $GLOBALS[TYPO3_CONF_VARS][SYS][systemLocale] is incorrect. A locale with' .
                ' this name doesn\'t exist in the operating system.'
            );
            setlocale(LC_CTYPE, $currentLocale);
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('System locale is correct');
        }

        return $status;
    }

    /**
     * Checks whether we can use file names with UTF-8 characters.
     * Configured system locale must support UTF-8 when UTF8filesystem is set
     *
     * @return Status\StatusInterface
     */
    protected function checkLocaleWithUTF8filesystem()
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
            // On Windows an empty local value uses the regional settings from the Control Panel
            if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'] === '' && TYPO3_OS !== 'WIN') {
                $status = new Status\ErrorStatus();
                $status->setTitle('System locale not set on UTF-8 file system');
                $status->setMessage(
                    '$GLOBALS[TYPO3_CONF_VARS][SYS][UTF8filesystem] is set, but $GLOBALS[TYPO3_CONF_VARS][SYS][systemLocale]' .
                    ' is empty. Make sure a valid locale which supports UTF-8 is set.'
                );
            } else {
                $testString = 'ÖöĄĆŻĘĆćążąęó.jpg';
                $currentLocale = setlocale(LC_CTYPE, 0);
                $quote = TYPO3_OS === 'WIN' ? '"' : '\'';
                setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
                if (escapeshellarg($testString) === $quote . $testString . $quote) {
                    $status = new Status\OkStatus();
                    $status->setTitle('File names with UTF-8 characters can be used.');
                } else {
                    $status = new Status\ErrorStatus();
                    $status->setTitle('System locale setting doesn\'t support UTF-8 file names.');
                    $status->setMessage(
                        'Please check your $GLOBALS[TYPO3_CONF_VARS][SYS][systemLocale] setting.'
                    );
                }
                setlocale(LC_CTYPE, $currentLocale);
            }
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('Skipping test, as UTF8filesystem is not enabled.');
        }

        return $status;
    }

    /**
     * Check if some opcode cache is loaded
     *
     * @return Status\StatusInterface
     */
    protected function checkSomePhpOpcodeCacheIsLoaded()
    {
        // Link to our wiki page, so we can update opcode cache issue information independent of TYPO3 CMS releases.
        $wikiLink = 'For more information take a look in our wiki ' . TYPO3_URL_WIKI_OPCODECACHE . '.';
        $opcodeCaches = GeneralUtility::makeInstance(OpcodeCacheService::class)->getAllActive();
        if (empty($opcodeCaches)) {
            // Set status to notice. It needs to be notice so email won't be triggered.
            $status = new Status\NoticeStatus();
            $status->setTitle('No PHP opcode cache loaded');
            $status->setMessage(
                'PHP opcode caches hold a compiled version of executed PHP scripts in' .
                ' memory and do not require to recompile a script each time it is accessed.' .
                ' This can be a massive performance improvement and can reduce the load on a' .
                ' server in general. A parse time reduction by factor three for fully cached' .
                ' pages can be achieved easily if using an opcode cache.' .
                LF . $wikiLink
            );
        } else {
            $status = new Status\OkStatus();
            $message = '';
            foreach ($opcodeCaches as $opcodeCache => $properties) {
                $message .= 'Name: ' . $opcodeCache . ' Version: ' . $properties['version'];
                $message .= LF;
                if ($properties['error']) {
                    // Set status to error if not already set
                    if ($status->getSeverity() !== 'error') {
                        $status = new Status\ErrorStatus();
                    }
                    $message .= ' This opcode cache is marked as malfunctioning by the TYPO3 CMS Team.';
                } elseif ($properties['canInvalidate']) {
                    $message .= ' This opcode cache should work correctly and has good performance.';
                } else {
                    // Set status to notice if not already error set. It needs to be notice so email won't be triggered.
                    if ($status->getSeverity() !== 'error' || $status->getSeverity() !== 'warning') {
                        $status = new Status\NoticeStatus();
                    }
                    $message .= ' This opcode cache may work correctly but has medium performance.';
                }
                $message .= LF;
            }
            $message .= $wikiLink;
            // Set title of status depending on serverity
            switch ($status->getSeverity()) {
                case 'error':
                    $status->setTitle('A possibly malfunctioning PHP opcode cache is loaded');
                    break;
                case 'warning':
                    $status->setTitle('A PHP opcode cache is loaded which may cause problems');
                    break;
                case 'ok':
                default:
                    $status->setTitle('A PHP opcode cache is loaded');
                    break;
            }
            $status->setMessage($message);
        }

        return $status;
    }

    /**
     * Create true type font test image
     *
     * @return Status\StatusInterface
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
                $status = new Status\OkStatus();
                $status->setTitle('FreeType True Type Font DPI');
                $status->setMessage('Fonts are rendered by FreeType library. ' .
                    'We need to ensure that the final dimensions are as expected. ' .
                    'This server renderes fonts based on 96 DPI correctly');
            } else {
                $status = new Status\NoticeStatus();
                $status->setTitle('FreeType True Type Font DPI');
                $status->setMessage('Fonts are rendered by FreeType library. ' .
                    'This server does not render fonts as expected. ' .
                    'Please check your FreeType 2 module.');
            }
        } else {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP GD library freetype2 support missing');
            $status->setMessage(
                'The core relies on GD library compiled into PHP with freetype2' .
                ' support. This is missing on your system. Please install it.'
            );
        }

        return $status;
    }

    /**
     * Check for bug in libxml
     *
     * @return Status\StatusInterface
     */
    protected function checkLibXmlBug()
    {
        $sampleArray = ['Test>><<Data'];
        $xmlContent = '<numIndex index="0">Test&gt;&gt;&lt;&lt;Data</numIndex>' . LF;
        $xml = GeneralUtility::array2xml($sampleArray, '', -1);

        if ($xmlContent !== $xml) {
            $status = new Status\ErrorStatus();
            $status->setTitle('PHP libxml bug present');
            $status->setMessage(
                'Some hosts have problems saving ">><<" in a flexform.' .
                ' To fix this, enable [BE][flexformForceCDATA] in' .
                ' All Configuration.'
            );
        } else {
            $status = new Status\OkStatus();
            $status->setTitle('PHP libxml bug not present');
        }

        return $status;
    }
}
