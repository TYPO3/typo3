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

namespace TYPO3\CMS\Backend\Form\FieldWizard;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

/**
 * Field wizard to toggle field required state for the "shortcut" field.
 *
 * This element injects a JS module so the client can re-evaluate
 * the required state when "shortcut_mode" changes.
 */
class ShortcutValidation extends AbstractNode
{
    public function render(): array
    {
        $result['javaScriptModules']['requiredByCondition'] = JavaScriptModuleInstruction::create(
            '@typo3/backend/form-engine/field-wizard/shortcut-validation.js'
        );
        return $result;
    }
}
