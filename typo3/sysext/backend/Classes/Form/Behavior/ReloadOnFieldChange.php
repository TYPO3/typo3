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

namespace TYPO3\CMS\Backend\Form\Behavior;

/**
 * Provides reload behavior in form view,
 * in case a particular field has been changed.
 */
class ReloadOnFieldChange implements OnFieldChangeInterface
{
    protected bool $confirmation;

    public function __construct(bool $confirmation)
    {
        $this->confirmation = $confirmation;
    }

    public function __toString(): string
    {
        return $this->generateInlineJavaScript();
    }

    public function toArray(): array
    {
        return [
            'name' => 'typo3-backend-form-reload',
            'data' => [
                'confirmation' => $this->confirmation,
            ],
        ];
    }

    protected function generateInlineJavaScript(): string
    {
        if ($this->confirmation) {
            $alertMsgOnChange = 'Modal.confirm('
                . 'TYPO3.lang["FormEngine.refreshRequiredTitle"],'
                . ' TYPO3.lang["FormEngine.refreshRequiredContent"]'
                . ')'
                . '.on('
                . '"button.clicked",'
                . ' function(e) { if (e.target.name == "ok") { FormEngine.saveDocument(); } Modal.dismiss(); }'
                . ');';
        } else {
            $alertMsgOnChange = 'FormEngine.saveDocument();';
        }
        return sprintf(
            "require(['TYPO3/CMS/Backend/FormEngine', 'TYPO3/CMS/Backend/Modal'], function (FormEngine, Modal) { %s });",
            $alertMsgOnChange
        );
    }
}
