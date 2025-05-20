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

namespace TYPO3\CMS\Core\Localization;

/**
 * @internal
 */
final class LabelBag
{
    /**
     * @var list<string>
     */
    public readonly array $arguments;

    /**
     * @param string $key e.g. `LLL:EXT:core/Resources/Private/Language/Labels.xlf:HelloWorld`
     * @param string ...$arguments optional label arguments to be substituted
     */
    public function __construct(
        public readonly string $key,
        string ...$arguments
    ) {
        $this->arguments = $arguments;
    }

    /**
     * Compiles the given label key and substituted label arguments if given.
     */
    public function compile(LanguageService $languageService): string
    {
        $label = $languageService->sL($this->key);
        return sprintf(
            $label,
            ...$this->arguments
        ) ?: sprintf(
            'Error: could not translate key "%s" with value "%s" and %d argument(s)!',
            $this->key,
            $label,
            count($this->arguments)
        );
    }
}
