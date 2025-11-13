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

namespace TYPO3\CMS\Redirects\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
class QrCodeElement extends AbstractFormElement
{
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        $databaseRow = $this->data['databaseRow'] ?? [];
        if ($this->data['command'] !== 'edit') {
            // QR code can only be displayed on edit
            return [];
        }

        $languageService = $this->getLanguageService();
        $sourceHost = $databaseRow['source_host'] ?? '';
        $sourcePath = $databaseRow['source_path'] ?? '';
        if ($sourceHost && $sourcePath) {
            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/backend/element/qrcode-element.js');
            $resultArray['html'] = '
                <div class="form-control-wrap" style="max-width: ' . $this->formMaxWidth(MathUtility::forceIntegerInRange($this->data['parameterArray']['fieldConf']['config']['size'] ?? $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth)) . 'px">
                    <span class="form-label">
                        ' . htmlspecialchars($languageService->translate('sys_redirect.redirect_type.qr_code', 'redirects.db')) . '
                    </span>
                    <div class="card mb-0">
                        <div class="card-body">
                            <div class="text-center">
                                <typo3-qrcode class="text-start" content="https://' . $sourceHost . $sourcePath . '" size="large" show-download=""></typo3-qrcode>
                            </div>
                        </div>
                    </div>
                </div>
            ';
        } else {
            $resultArray['html'] = '<div class="alert alert-warning">' . htmlspecialchars($languageService->translate('no_qrcode', 'redirects.messages')) . '</div>';
        }

        return $resultArray;
    }
}
