<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Redirects\Evaluation;

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

/**
 * Class SourceHost
 * Triggered from DataHandler as TCA formevals hook for validation / sanitation of domain values.
 * @internal
 */
class SourceHost
{
    /**
     * JavaScript code for client side validation/evaluation
     *
     * @return string JavaScript code for client side validation/evaluation
     */
    public function returnFieldJS(): string
    {
        $jsCode = [];
        $jsCode[] = 'if (value === \'*\') {return value;}';
        $jsCode[] = 'var parser = document.createElement(\'a\');';
        $jsCode[] = 'parser.href = value.indexOf(\'://\') != -1 ? value : \'http://\' + value;';
        $jsCode[] = 'return parser.host;';
        return implode(' ', $jsCode);
    }

    /**
     * Server-side removing of protocol on save
     *
     * @param string $value The field value to be evaluated
     * @return string Evaluated field value
     */
    public function evaluateFieldValue(string $value): string
    {
        // 1) Special case: * means any domain
        if ($value === '*') {
            return $value;
        }

        // 2) Check if value contains a protocol like http:// https:// etc...
        if (strpos($value, '://') !== false) {
            $tmp = $this->parseUrl($value);
            if (!empty($tmp)) {
                return $tmp;
            }
        }

        // 3) Check domain name
        // remove anything after the first "/"
        $checkValue = $value;
        if (strpos($value, '/') !== false) {
            $checkValue = substr($value, 0, strpos($value, '/'));
        }
        $validHostnameRegex = '/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z0-9]|[A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9])$/';
        if (preg_match_all($validHostnameRegex, $checkValue, $matches, PREG_SET_ORDER) !== false) {
            if (!empty($matches)) {
                return $checkValue;
            }
        }

        // 4) IPv4 or IPv6
        $isIP = filter_var($value, FILTER_VALIDATE_IP) === $value;
        if ($isIP) {
            return $value;
        }

        return '';
    }

    /**
     * @param string $value
     * @return string
     */
    protected function parseUrl(string $value): string
    {
        $urlParts = parse_url($value);
        if (!empty($urlParts['host'])) {
            $value = $urlParts['host'];

            // Special case IPv6 with protocol: http://[2001:0db8:85a3:08d3::0370:7344]/
            // $urlParts['host'] will be [2001:0db8:85a3:08d3::0370:7344]
            $ipv6Pattern = '/\[([a-zA-Z0-9:]*)\]/';
            preg_match_all($ipv6Pattern, $urlParts['host'], $ipv6Matches, PREG_SET_ORDER);
            if (!empty($ipv6Matches[0][1])) {
                $value = $ipv6Matches[0][1];
            }
        }
        return $value;
    }
}
