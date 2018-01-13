<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Render list of allowed / disalowwed file types,
 * typically used with type=group and internal_type=file.
 */
class FileTypeList extends AbstractNode
{
    /**
     * Render list of allowed and disallowed file types
     *
     * @return array
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];

        if (!isset($config['allowed']) || !is_string($config['allowed']) || empty($config['allowed'])
            || !isset($config['internal_type']) || $config['internal_type'] !== 'file'
        ) {
            // No handling if the field has no, or funny "allowed" setting, and if internal_type is not "file"
            return $result;
        }

        $allowed = GeneralUtility::trimExplode(',', $config['allowed'], true);
        $disallowed = isset($config['disallowed']) ? GeneralUtility::trimExplode(',', $config['disallowed'], true) : [];

        $allowedHtml = [];
        foreach ($allowed as $item) {
            $allowedHtml[] = '<span class="label label-success">' . htmlspecialchars(strtoupper($item)) . '</span> ';
        }
        $disallowedHtml = [];
        foreach ($disallowed as $item) {
            $disallowedHtml[] = '<span class="label label-danger">' . htmlspecialchars(strtoupper($item)) . '</span> ';
        }
        $html = [];
        if (!empty($allowedHtml) || !empty($disallowedHtml)) {
            $html[] = '<div class="help-block">';
            $html[] =   implode(LF, $allowedHtml);
            $html[] =   implode(LF, $disallowedHtml);
            $html[] = '</div>';
        }

        $result['html'] = implode(LF, $html);
        return $result;
    }
}
