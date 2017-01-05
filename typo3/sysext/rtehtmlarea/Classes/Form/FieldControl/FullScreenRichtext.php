<?php
declare(strict_types=1);
namespace TYPO3\CMS\Rtehtmlarea\Form\FieldControl;

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

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Renders the icon with link parameters to the "full screen" table wizard.
 */
class FullScreenRichtext extends AbstractNode
{
    /**
     * Add button control
     *
     * @return array As defined by FieldControl class
     */
    public function render()
    {
        if (!MathUtility::canBeInterpretedAsInteger($this->data['databaseRow']['uid'])) {
            return [];
        }

        $options = $this->data['renderData']['fieldControlOptions'];

        $urlParameters  = [
            'P' => [
                'table' => $this->data['tableName'],
                'field' => $this->data['fieldName'],
                'uid' => $this->data['databaseRow']['uid'],
                'returnUrl' => $this->data['returnUrl'],
            ],
        ];

        $onClick = [];
        $onClick[] = 'this.blur();';
        $onClick[] = 'return !TBE_EDITOR.isFormChanged();';

        return [
            'iconIdentifier' => 'actions-wizard-rte',
            'title' => $options['title'] ?? 'LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang.xlf:wizard.fullScreen',
            'linkAttributes' => [
                'onClick' => implode('', $onClick),
                'href' => BackendUtility::getModuleUrl('wizard_rte', $urlParameters),
            ],
        ];
    }
}
