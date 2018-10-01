<?php
namespace TYPO3\CMS\Backend\Tree\View;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Local position map class when creating new Content Elements
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class ContentCreationPagePositionMap extends PagePositionMap
{
    /**
     * @var bool
     */
    public $dontPrintPageInsertIcons = 1;

    /**
     * Wrapping the title of the record - here we just return it.
     *
     * @param string $str The title value.
     * @param array $row The record row.
     * @return string Wrapped title string.
     */
    public function wrapRecordTitle($str, $row)
    {
        return $str;
    }

    /**
     * Create on-click event value.
     *
     * @param array $row The record.
     * @param string $vv Column position value.
     * @param int $moveUid Move uid
     * @param int $pid PID value.
     * @param int $sys_lang System language
     * @return string
     */
    public function onClickInsertRecord($row, $vv, $moveUid, $pid, $sys_lang = 0)
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $location = (string)$uriBuilder->buildUriFromRoute('record_edit', [
            'edit[tt_content][' . (is_array($row) ? -$row['uid'] : $pid) . ']' => 'new',
            'defVals[tt_content][colPos]' => $vv,
            'defVals[tt_content][sys_language_uid]' => $sys_lang,
            'returnUrl' => GeneralUtility::_GP('returnUrl')
        ]);
        return $this->clientContext . '.location.href=' . GeneralUtility::quoteJSvalue($location) . '+document.editForm.defValues.value; return false;';
    }
}
