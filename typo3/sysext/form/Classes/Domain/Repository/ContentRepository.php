<?php
namespace TYPO3\CMS\Form\Domain\Repository;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Factory\JsonToTypoScript;
use TYPO3\CMS\Form\Domain\Model\Content;
use TYPO3\CMS\Form\Utility\TypoScriptToJsonConverter;

/**
 * Repository for \TYPO3\CMS\Form\Domain\Model\Content
 */
class ContentRepository
{
    /**
     * Get the referenced record from the database
     *
     * Using the GET or POST variable 'P'
     *
     * @param null|int $recordId
     * @param null|string $table
     * @return bool|Content if found, FALSE if not
     */
    public function getRecord($recordId = null, $table = null)
    {
        $record = false;
        $getPostVariables = GeneralUtility::_GP('P');
        if (!$table) {
            $table = 'tt_content';
        }

        if (!$recordId) {
            $recordId = (int)$getPostVariables['uid'];
        }

        if ((int)$recordId === 0) {
            /** @var $typoScriptParser TypoScriptParser */
            $typoScriptParser = GeneralUtility::makeInstance(TypoScriptParser::class);
            $typoScriptParser->parse('');
            /** @var $record Content */
            $record = GeneralUtility::makeInstance(Content::class);
            $record->setUid(0);
            $record->setPageId(0);
            $record->setTyposcript($typoScriptParser->setup);
            $record->setBodytext('');

            return $record;
        }

        $row = BackendUtility::getRecord($table, (int)$recordId);
        if (is_array($row)) {
            // strip off the leading "[Translate to XY]" text after localizing the original record
            $languageField = $GLOBALS['TCA']['tt_content']['ctrl']['languageField'];
            $transOrigPointerField = $GLOBALS['TCA']['tt_content']['ctrl']['transOrigPointerField'];
            if ($row[$languageField] > 0 && $row[$transOrigPointerField] > 0) {
                $bodytext = preg_replace('/^\[.*?\] /', '', $row['bodytext'], 1);
            } else {
                $bodytext = $row['bodytext'];
            }

            /** @var $typoScriptParser TypoScriptParser */
            $typoScriptParser = GeneralUtility::makeInstance(TypoScriptParser::class);
            $typoScriptParser->parse($bodytext);
            /** @var $record Content */
            $record = GeneralUtility::makeInstance(Content::class);
            $record->setUid($row['uid']);
            $record->setPageId($row['pid']);
            $record->setTyposcript($typoScriptParser->setup);
            $record->setBodytext($bodytext);
        }
        return $record;
    }

    /**
     * Check if the referenced record exists
     *
     * @return bool TRUE if record exists, FALSE if not
     */
    public function hasRecord()
    {
        return $this->getRecord() !== false;
    }

    /**
     * Convert the incoming data of the FORM wizard
     *
     * @return string $typoscript after conversion
     */
    public function save()
    {
        $json = GeneralUtility::_GP('configuration');
        /** @var $converter JsonToTypoScript */
        $converter = GeneralUtility::makeInstance(JsonToTypoScript::class);
        $typoscript = $converter->convert($json);
        return $typoscript;
    }

    /**
     * Read and convert the content record to JSON
     *
     * @return string The JSON object if record exists, FALSE if not
     */
    public function getRecordAsJson()
    {
        $json = false;
        $record = $this->getRecord();
        if ($record) {
            $typoscript = $record->getTyposcript();
            /** @var $converter TypoScriptToJsonConverter */
            $converter = GeneralUtility::makeInstance(TypoScriptToJsonConverter::class);
            $json = $converter->convert($typoscript);
        }
        return $json;
    }
}
