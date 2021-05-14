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
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;

/**
 * Render thumbnails of icons,
 * typically used with type=select.
 */
class SelectIcons extends AbstractNode
{
    /**
     * Render thumbnails of selected files
     *
     * @return array
     */
    public function render(): array
    {
        $selectIcons = [];
        $result = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $selectItems = $parameterArray['fieldConf']['config']['items'];

        $selectItemCounter = 0;
        foreach ($selectItems as $item) {
            if ($item[1] === '--div--') {
                continue;
            }
            $icon = !empty($item[2]) ? FormEngineUtility::getIconHtml($item[2], $item[0], $item[0]) : '';
            if ($icon) {
                $fieldValue = $this->data['databaseRow'][$this->data['fieldName']];
                $selectIcons[] = [
                        'title' => $item[0],
                        'active' => ($fieldValue[0] ?? false) === (string)($item[1] ?? ''),
                        'icon' => $icon,
                        'index' => $selectItemCounter,
                    ];
            }
            $selectItemCounter++;
        }

        $html = [];
        if (!empty($selectIcons)) {
            $html[] = '<div class="t3js-forms-select-single-icons icon-list">';
            $html[] =    '<div class="row">';
            foreach ($selectIcons as $i => $selectIcon) {
                $active = $selectIcon['active'] ?  ' active' : '';
                $html[] =   '<div class="col col-auto item' . $active . '">';
                if (is_array($selectIcon)) {
                    $html[] = '<a href="#" title="' . htmlspecialchars($selectIcon['title'], ENT_COMPAT, 'UTF-8', false) . '" data-select-index="' . htmlspecialchars((string)$selectIcon['index']) . '">';
                    $html[] =   $selectIcon['icon'];
                    $html[] = '</a>';
                }
                $html[] =   '</div>';
            }
            $html[] =    '</div>';
            $html[] = '</div>';
        }

        $result['html'] = implode(LF, $html);
        return $result;
    }
}
