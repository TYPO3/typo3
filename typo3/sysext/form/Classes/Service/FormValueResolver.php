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

namespace TYPO3\CMS\Form\Service;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\ProcessableValueFormElementInterface;
use TYPO3\CMS\Form\Domain\Model\FormElements\StringableFormElementInterface;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * Resolves form element values to their display representation (e.g. translated labels).
 *
 * @internal implementation detail of the form finishers and the form value ViewHelpers.
 */
final readonly class FormValueResolver
{
    public function __construct(
        private TranslationService $translationService,
    ) {}

    /**
     * Resolves the display value for a single form element.
     *
     * Priority:
     * 1. ProcessableValueFormElementInterface – element-provided representation
     *    (translated select options, country names, formatted dates, …)
     * 2. Translated "options" property – generic key-to-label mapping
     * 3. Object conversion, which prefers StringableFormElementInterface and
     *    falls back to DateTime, File/FileReference and __toString()
     * 4. Raw value passthrough for scalars
     */
    public function resolveDisplayValue(FormElementInterface $element, mixed $value, FormRuntime $formRuntime): mixed
    {
        if ($element instanceof ProcessableValueFormElementInterface) {
            return $element->processElementValue($value, $formRuntime);
        }
        $options = $element->getProperties()['options'] ?? null;
        if (is_array($options)) {
            $translatedOptions = $this->translationService->translateFormElementValue($element, ['options'], $formRuntime);
            if (is_array($translatedOptions)) {
                if (is_array($value)) {
                    return self::mapValuesToOptions($value, $translatedOptions);
                }
                return self::mapValueToOption($value, $translatedOptions);
            }
        }
        if ($value instanceof ObjectStorage) {
            $result = [];
            foreach ($value as $item) {
                $result[] = $this->processObject($element, $item);
            }
            return $result;
        }
        if (is_object($value)) {
            return $this->processObject($element, $value);
        }
        return $value;
    }

    private static function mapValuesToOptions(array $value, array $options): array
    {
        $result = [];
        foreach ($value as $key) {
            $result[] = self::mapValueToOption($key, $options);
        }
        return $result;
    }

    private static function mapValueToOption(mixed $value, array $options): mixed
    {
        return $options[$value] ?? $value;
    }

    private function processObject(FormElementInterface $element, object $object): string
    {
        if ($element instanceof StringableFormElementInterface) {
            return $element->valueToString($object);
        }
        if ($object instanceof \DateTime) {
            return $object->format(\DateTimeInterface::W3C);
        }
        if ($object instanceof File || $object instanceof FileReference) {
            if ($object instanceof FileReference) {
                $object = $object->getOriginalResource();
            }
            return $object->getName();
        }
        if (method_exists($object, '__toString')) {
            return (string)$object;
        }
        return 'Object [' . get_class($object) . ']';
    }
}
