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

namespace TYPO3\CMS\Core\Validation;

use TYPO3\CMS\Core\Localization\LanguageService;

trait ResultRenderingTrait
{
    public function renderResultException(ResultException $exception, ?LanguageService $languageService = null): string
    {
        return sprintf(
            '%s: %s',
            $exception->getMessage(),
            implode(
                ' | ',
                $this->compileResultMessages($exception->messages, $languageService)
            )
        );
    }

    /**
     * @param list<ResultMessage> $messages
     * @return list<string>
     */
    public function compileResultMessages(array $messages, ?LanguageService $languageService = null): array
    {
        return array_map(
            static fn(ResultMessage $message): string => $message->labelBag !== null && $languageService !== null
                ? $message->labelBag->compile($languageService)
                : $message->message,
            $messages
        );
    }
}
