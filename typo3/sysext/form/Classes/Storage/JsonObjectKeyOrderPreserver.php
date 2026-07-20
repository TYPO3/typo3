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

namespace TYPO3\CMS\Form\Storage;

/**
 * Workaround for form_definition.configuration being persisted through a
 * native SQL JSON column (TCA type=json).
 *
 * Multi-value form elements (SingleSelect, MultiSelect, RadioButton,
 * MultiCheckbox, ...) store their "options" as an associative map
 * (value => label) keyed by option value. json_encode() of such a map
 * produces a JSON *object*, and MySQL's native JSON column type does not
 * guarantee that a JSON object's member order survives a write/read round
 * trip. This is documented, spec-compliant behavior (RFC 8259: object
 * member order "has no significance"). JSON *array* element order, by
 * contrast, is reliably preserved by MySQL.
 *
 * MariaDB's JSON type is a plain LONGTEXT alias with a
 * CHECK(JSON_VALID(...)) constraint, so it happens to preserve the exact
 * text (and therefore object member order) byte-for-byte, which is why
 * this only reproduces on real MySQL.
 *
 * Practical effect without this workaround: reordering a select element's
 * options in the form editor visibly "works" right after saving, but
 * reverts to the previous order on the next reload, once MySQL has
 * renormalized the JSON object.
 *
 * protect() wraps every "options" map in an array-based structure before
 * persisting, so the intended order survives via a JSON array instead of
 * relying on object member order. restore() reverses this again after
 * json_decode() on read, using the explicit order list rather than the
 * member order MySQL happened to return the object in.
 *
 * Note: "options" is matched by key name rather than by resolving each
 * renderable's prototype configuration for declared multi-value
 * properties (as FormEditorController does for the editor UI). This is a
 * deliberate simplification. It also protects option order inside
 * variant overrides for free, without needing prototype/DI wiring in the
 * persistence layer and is safe even where it over-applies, since
 * protect()/restore() are lossless no-ops on any "options" map that
 * doesn't need reordering.
 *
 * @internal
 */
final readonly class JsonObjectKeyOrderPreserver
{
    private const MARKER = '__jsonKeyOrderProtected';

    public function protect(array $formDefinition): array
    {
        $output = $formDefinition;
        foreach ($formDefinition as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            if ($key === 'options' && !array_is_list($value)) {
                $output[$key] = [
                    self::MARKER => true,
                    'order' => array_keys($value),
                    'values' => $value,
                ];
                continue;
            }
            $output[$key] = $this->protect($value);
        }
        return $output;
    }

    public function restore(array $formDefinition): array
    {
        $output = $formDefinition;
        foreach ($formDefinition as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            if ($key === 'options' && ($value[self::MARKER] ?? false) === true) {
                $order = $value['order'] ?? [];
                $values = $value['values'] ?? [];
                $restored = [];
                foreach ($order as $optionKey) {
                    if (array_key_exists($optionKey, $values)) {
                        $restored[$optionKey] = $values[$optionKey];
                        unset($values[$optionKey]);
                    }
                }
                // Defensive fallback for entries the order list doesn't cover
                // (should not normally happen, keeps old/foreign data intact).
                $output[$key] = $restored + $values;
                continue;
            }
            $output[$key] = $this->restore($value);
        }
        return $output;
    }
}
