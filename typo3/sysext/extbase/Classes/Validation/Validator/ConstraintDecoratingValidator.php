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

namespace TYPO3\CMS\Extbase\Validation\Validator;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Validator that decorates a Symfony Constraint.
 */
#[Exclude]
final readonly class ConstraintDecoratingValidator implements ValidatorInterface
{
    public function __construct(
        private Constraint $constraint,
    ) {}

    public function validate(mixed $value): Result
    {
        $validator = Validation::createValidatorBuilder()->disableTranslation()->getValidator();
        $constraintViolationList = $validator->validate($value, $this->constraint);
        $result = new Result();

        foreach ($constraintViolationList as $constraintViolation) {
            $arguments = \array_values($constraintViolation->getParameters());
            $code = $this->convertConstraintViolationCode($constraintViolation->getCode());
            $messageTemplate = $this->convertConstraintViolationMessageTemplate($constraintViolation);

            $result->addError(
                new Error(
                    $this->translateErrorMessage($messageTemplate, $arguments),
                    $code,
                    $arguments,
                ),
            );
        }

        return $result;
    }

    /**
     * Convert UUID-based violation code to an integer.
     */
    private function convertConstraintViolationCode(?string $code): int
    {
        if ($code !== null && $code !== '') {
            $hash = hash('sha256', $code);
            $code = hexdec(substr($hash, 0, 8));
        }

        return (int)$code;
    }

    /**
     * Convert named placeholders like {{ value }} to sprintf compatible placeholders like %1$s.
     *
     * Before: 'The value {{ value }} must follow the {{ format }} format.'
     * After: 'The value %1$s must follow the %2$s format.'
     */
    private function convertConstraintViolationMessageTemplate(ConstraintViolationInterface $constraintViolation): string
    {
        $placeholderMap = [];

        foreach (array_keys($constraintViolation->getParameters()) as $index => $placeholder) {
            $placeholderMap[$placeholder] = '%' . ($index + 1) . '$s';
        }

        return strtr($constraintViolation->getMessageTemplate(), $placeholderMap);
    }

    /**
     * @param list<scalar|\Stringable> $arguments
     */
    private function translateErrorMessage(string $translateKey, array $arguments = []): string
    {
        if (!str_starts_with($translateKey, 'LLL:')) {
            return $translateKey;
        }

        return LocalizationUtility::translate($translateKey, null, $arguments) ?? '';
    }

    public function setOptions(array $options): void
    {
        // Intentionally left blank.
    }

    public function getOptions(): array
    {
        return [];
    }

    public function setRequest(?ServerRequestInterface $request): void
    {
        // Intentionally left blank.
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return null;
    }
}
