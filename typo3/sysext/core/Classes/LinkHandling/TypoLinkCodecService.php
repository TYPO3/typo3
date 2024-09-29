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

namespace TYPO3\CMS\Core\LinkHandling;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\LinkHandling\Event\AfterTypoLinkDecodedEvent;
use TYPO3\CMS\Core\LinkHandling\Event\BeforeTypoLinkEncodedEvent;

/**
 * This class provides basic functionality to encode and decode typolink strings
 */
#[Autoconfigure(public: true)]
final readonly class TypoLinkCodecService
{
    /**
     * Delimiter for TypoLink string parts
     */
    private const DELIMITER = ' ';

    /**
     * Symbol for TypoLink parts not specified
     */
    private const EMPTY_VALUE_SYMBOL = '-';

    public function __construct(private EventDispatcherInterface $eventDispatcher) {}

    /**
     * Encode TypoLink parts to a single string
     *
     * @param array{url?: string, target?: string, class?: string, title?: string, additionalParams?: string} $typoLinkParts
     * @return string A correctly encoded TypoLink string
     */
    public function encode(array $typoLinkParts): string
    {
        if (empty($typoLinkParts) || !isset($typoLinkParts['url'])) {
            return '';
        }

        // Get empty structure
        $reverseSortedParameters = array_reverse($this->decode(''), true);
        $aValueWasSet = false;
        foreach ($reverseSortedParameters as $key => &$value) {
            $value = $typoLinkParts[$key] ?? '';
            // escape special character \ and "
            $value = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
            // enclose with quotes if a string contains the delimiter
            if (str_contains($value, self::DELIMITER)) {
                $value = '"' . $value . '"';
            }
            // fill with - if another values has already been set
            if ($value === '' && $aValueWasSet) {
                $value = self::EMPTY_VALUE_SYMBOL;
            }
            if ($value !== '') {
                $aValueWasSet = true;
            }
        }

        $reverseSortedParameters = $this->eventDispatcher->dispatch(
            new BeforeTypoLinkEncodedEvent(
                parameters: $reverseSortedParameters,
                typoLinkParts: $typoLinkParts,
                delimiter: self::DELIMITER,
                emptyValueSymbol: self::EMPTY_VALUE_SYMBOL
            )
        )->getParameters();

        return trim(implode(self::DELIMITER, array_reverse($reverseSortedParameters, true)));
    }

    /**
     * Decodes a TypoLink string into its parts
     *
     * @param string $typoLink The properly encoded TypoLink string
     * @return array{url: string, target: string, class: string, title: string, additionalParams: string}
     */
    public function decode(string $typoLink): array
    {
        $typoLink = trim($typoLink);
        if ($typoLink !== '') {
            $parts = str_replace(['\\\\', '\\"'], ['\\', '"'], str_getcsv($typoLink, self::DELIMITER, '"', '\\'));
        } else {
            $parts = [];
        }

        // The order of the entries is crucial!!
        $typoLinkParts = [
            'url' => isset($parts[0]) ? trim($parts[0]) : '',
            'target' => isset($parts[1]) && $parts[1] !== self::EMPTY_VALUE_SYMBOL ? trim($parts[1]) : '',
            'class' => isset($parts[2]) && $parts[2] !== self::EMPTY_VALUE_SYMBOL ? trim($parts[2]) : '',
            'title' => isset($parts[3]) && $parts[3] !== self::EMPTY_VALUE_SYMBOL ? trim($parts[3]) : '',
            'additionalParams' => isset($parts[4]) && $parts[4] !== self::EMPTY_VALUE_SYMBOL ? trim($parts[4]) : '',
        ];

        return $this->eventDispatcher->dispatch(
            new AfterTypoLinkDecodedEvent(
                typoLinkParts: $typoLinkParts,
                typoLink: $typoLink,
                delimiter: self::DELIMITER,
                emptyValueSymbol: self::EMPTY_VALUE_SYMBOL
            )
        )->getTypoLinkParts();
    }
}
