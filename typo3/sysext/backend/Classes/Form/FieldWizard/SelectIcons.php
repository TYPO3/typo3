<?php
declare(strict_types=1);
namespace TYPO3\CMS\Backend\Form\FieldWizard;

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
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;

/**
 * Render thumbnails of icons,
 * typically used with type=group and internal_type=file and file_reference.
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
        $result = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $selectItems = $parameterArray['fieldConf']['config']['items'];

        $selectItemCounter = 0;
        foreach ($selectItems as $item) {
            if ($item[1] === '--div--') {
                continue;
            } else {
                $icon = !empty($item[2]) ? FormEngineUtility::getIconHtml($item[2], $item[0], $item[0]) : '';
                if ($icon) {
                    $selectIcons[] = [
                        'title' => $item[0],
                        'icon' => $icon,
                        'index' => $selectItemCounter,
                    ];
                }
                $selectItemCounter++;
            }
        }

        $html = [];
        if (!empty($selectIcons)) {
            $html[] = '<div class="t3js-forms-select-single-icons table-icons table-fit table-fit-inline-block">';
            $html[] =    '<table class="table table-condensed table-white table-center">';
            $html[] =        '<tbody>';
            $html[] =            '<tr>';
            foreach ($selectIcons as $i => $selectIcon) {
                if ($i % 12 === 0 && $i !== 0) {
                    $html[] =    '</tr>';
                    $html[] =    '<tr>';
                }
                $html[] =            '<td>';
                if (is_array($selectIcon)) {
                    $html[] = '<a href="#" title="' . htmlspecialchars($selectIcon['title'], ENT_COMPAT, 'UTF-8', false) . '" data-select-index="' . htmlspecialchars((string)$selectIcon['index']) . '">';
                    $html[] =   $selectIcon['icon'];
                    $html[] = '</a>';
                }
                $html[] =            '</td>';
            }
            $html[] =            '</tr>';
            $html[] =        '</tbody>';
            $html[] =    '</table>';
            $html[] = '</div>';
        }

        $result['html'] = implode(LF, $html);
        return $result;
    }
}
