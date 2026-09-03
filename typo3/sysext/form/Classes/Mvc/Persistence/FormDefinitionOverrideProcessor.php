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

namespace TYPO3\CMS\Form\Mvc\Persistence;

use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Applies the structural rules of TypoScript form definition overrides.
 *
 * @internal
 */
final class FormDefinitionOverrideProcessor
{
    private const RECIPIENT_OPTIONS = [
        'recipients',
        'carbonCopyRecipients',
        'blindCarbonCopyRecipients',
        'replyToRecipients',
    ];

    public function process(array $formDefinition, array $overrides): array
    {
        foreach ($overrides['finishers'] ?? [] as $index => $finisherOverride) {
            foreach (self::RECIPIENT_OPTIONS as $optionName) {
                if (!array_key_exists($optionName, $finisherOverride['options'] ?? [])) {
                    continue;
                }
                $recipients = $finisherOverride['options'][$optionName] ?? [];
                $finisherOverride['options'][$optionName] = $this->normalizeRecipients($recipients);
                $formDefinition['finishers'][$index]['options'][$optionName] = [];
            }
            $overrides['finishers'][$index] = $finisherOverride;
        }

        ArrayUtility::mergeRecursiveWithOverrule($formDefinition, $overrides);
        return $formDefinition;
    }

    private function normalizeRecipients(mixed $recipients): array
    {
        $normalizedRecipients = [];
        if (!is_array($recipients)) {
            return $normalizedRecipients;
        }

        foreach ($recipients as $recipient) {
            if (!is_array($recipient) || !isset($recipient['email'])) {
                throw new \InvalidArgumentException(
                    'Form TypoScript recipient overrides must use structured entries with an email value.',
                    1788434592
                );
            }
            $normalizedRecipients[(string)$recipient['email']] = (string)($recipient['name'] ?? '');
        }
        return $normalizedRecipients;
    }
}
