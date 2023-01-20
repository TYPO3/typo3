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

namespace TYPO3\CMS\Frontend\Typolink;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Page\DefaultJavaScriptAssetTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Builds a TypoLink to an email address, also takes care of additional functionality for the time being
 * such as the infamous config.spamProtectedEmailAddresses option.
 */
class EmailLinkBuilder extends AbstractTypolinkBuilder implements LoggerAwareInterface
{
    use DefaultJavaScriptAssetTrait;
    use LoggerAwareTrait;

    public function build(array &$linkDetails, string $linkText, string $target, array $conf): LinkResultInterface
    {
        [$url, $linkText, $attributes] = $this->processEmailLink($linkDetails['email'], $linkText, $linkDetails);
        return (new LinkResult(LinkService::TYPE_EMAIL, $url))
            ->withTarget($target)
            ->withLinkConfiguration($conf)
            ->withLinkText($linkText)
            ->withAttributes($attributes);
    }

    /**
     * Creates a href attibute for given $mailAddress.
     * The function uses spamProtectEmailAddresses for encoding the mailto statement.
     * If spamProtectEmailAddresses is disabled, it'll just return a string like "mailto:user@example.tld".
     *
     * Returns an array with three items (numeric index)
     *   #0: $mailToUrl (string), ready to be inserted into the href attribute of the <a> tag
     *   #1: $linkText (string), content between starting and ending `<a>` tag
     *   #2: $attributes (array<string, string>), additional attributes for `<a>` tag
     *
     * @param string $mailAddress Email address
     * @param string $linkText Link text, default will be the email address.
     * @return array{0: string, 1: string, 2: array<string, string>} A numerical array with three items
     * @internal this method is not part of TYPO3's public API
     */
    public function processEmailLink(string $mailAddress, string $linkText, array $linkDetails = []): array
    {
        $linkText = $linkText ?: htmlspecialchars($mailAddress);
        $attributes = [];
        if ($linkDetails !== []) {
            // Ensure to add also additional query parameters to the string
            $mailToUrl = GeneralUtility::makeInstance(LinkService::class)->asString($linkDetails);
        } else {
            $mailToUrl = 'mailto:' . $mailAddress;
        }

        // no processing happened, therefore, the default processing kicks in
        $tsfe = $this->getTypoScriptFrontendController();
        $spamProtectEmailAddresses = (int)($tsfe->config['config']['spamProtectEmailAddresses'] ?? 0);
        $spamProtectEmailAddresses = MathUtility::forceIntegerInRange($spamProtectEmailAddresses, -10, 10, 0);

        if ($spamProtectEmailAddresses) {
            $mailToUrl = $this->encryptEmail($mailToUrl, $spamProtectEmailAddresses);
            $attributes = [
                'data-mailto-token' => $mailToUrl,
                'data-mailto-vector' => $spamProtectEmailAddresses,
            ];
            $mailToUrl = '#';
            $this->addDefaultFrontendJavaScript();
            $atLabel = '(at)';
            if (($atLabelFromConfig = trim($tsfe->config['config']['spamProtectEmailAddresses_atSubst'] ?? '')) !== '') {
                $atLabel = $atLabelFromConfig;
            }
            $spamProtectedMailAddress = str_replace('@', $atLabel, htmlspecialchars($mailAddress));
            if ($tsfe->config['config']['spamProtectEmailAddresses_lastDotSubst'] ?? false) {
                $lastDotLabel = trim($tsfe->config['config']['spamProtectEmailAddresses_lastDotSubst']);
                $lastDotLabel = $lastDotLabel ?: '(dot)';
                $spamProtectedMailAddress = preg_replace('/\\.([^\\.]+)$/', $lastDotLabel . '$1', $spamProtectedMailAddress);
                if ($spamProtectedMailAddress === null) {
                    $this->logger->debug('Error replacing the last dot in email address "{email}"', ['email' => $spamProtectedMailAddress]);
                    $spamProtectedMailAddress = '';
                }
            }
            $linkText = str_ireplace($mailAddress, $spamProtectedMailAddress, $linkText);
        }

        return [$mailToUrl, $linkText, $attributes];
    }

    /**
     * Encryption of email addresses for <A>-tags See the spam protection setup in TS 'config.'
     *
     * @param string $string Input string to en/decode: "mailto:some@example.com
     * @param int $offset a number between -10 and 10, taken from config.spamProtectEmailAddresses
     * @return string encoded version of $string
     */
    protected function encryptEmail(string $string, int $offset): string
    {
        $out = '';
        // like str_rot13() but with a variable offset and a wider character range
        $len = strlen($string);
        for ($i = 0; $i < $len; $i++) {
            $charValue = ord($string[$i]);
            // 0-9 . , - + / :
            if ($charValue >= 43 && $charValue <= 58) {
                $out .= $this->encryptCharcode($charValue, 43, 58, $offset);
            } elseif ($charValue >= 64 && $charValue <= 90) {
                // A-Z @
                $out .= $this->encryptCharcode($charValue, 64, 90, $offset);
            } elseif ($charValue >= 97 && $charValue <= 122) {
                // a-z
                $out .= $this->encryptCharcode($charValue, 97, 122, $offset);
            } else {
                $out .= $string[$i];
            }
        }
        return $out;
    }

    /**
     * Encryption (or decryption) of a single character.
     * Within the given range the character is shifted with the supplied offset.
     *
     * @param int $n Ordinal of input character
     * @param int $start Start of range
     * @param int $end End of range
     * @param int $offset Offset
     * @return string encoded/decoded version of character
     */
    protected function encryptCharcode(int $n, int $start, int $end, int $offset): string
    {
        $n = $n + $offset;
        if ($offset > 0 && $n > $end) {
            $n = $start + ($n - $end - 1);
        } elseif ($offset < 0 && $n < $start) {
            $n = $end - ($start - $n - 1);
        }
        return chr($n);
    }
}
