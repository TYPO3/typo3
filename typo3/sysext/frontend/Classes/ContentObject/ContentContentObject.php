<?php

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

namespace TYPO3\CMS\Frontend\ContentObject;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\Event\ModifyRecordsAfterFetchingContentEvent;

/**
 * Contains CONTENT class object.
 */
class ContentContentObject extends AbstractContentObject
{
    public function __construct(
        private readonly TimeTracker $timeTracker,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Rendering the cObject, CONTENT
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = [])
    {
        if (!empty($conf['if.']) && !$this->cObj->checkIf($conf['if.'])) {
            return '';
        }

        $theValue = '';
        $conf['table'] = trim((string)$this->cObj->stdWrapValue('table', $conf));
        $conf['select.'] = !empty($conf['select.']) ? $conf['select.'] : [];
        $renderObjName = ($conf['renderObj'] ?? false) ? $conf['renderObj'] : '<' . $conf['table'];
        $renderObjKey = ($conf['renderObj'] ?? false) ? 'renderObj' : '';
        $renderObjConf = $conf['renderObj.'] ?? [];
        $slide = (int)$this->cObj->stdWrapValue('slide', $conf);
        if (!$slide) {
            $slide = 0;
        }
        $slideCollect = (int)$this->cObj->stdWrapValue('collect', $conf['slide.'] ?? []);
        if (!$slideCollect) {
            $slideCollect = 0;
        }
        $slideCollectReverse = (bool)$this->cObj->stdWrapValue('collectReverse', $conf['slide.'] ?? []);
        $slideCollectFuzzy = (bool)$this->cObj->stdWrapValue('collectFuzzy', $conf['slide.'] ?? []);
        if (!$slideCollect) {
            $slideCollectFuzzy = true;
        }
        $again = false;
        $tmpValue = '';

        do {
            $cobjValue = '';
            $modifyRecordsEvent = $this->eventDispatcher->dispatch(
                new ModifyRecordsAfterFetchingContentEvent(
                    $this->cObj->getRecords($conf['table'], $conf['select.']),
                    $theValue,
                    $slide,
                    $slideCollect,
                    $slideCollectReverse,
                    $slideCollectFuzzy,
                    $conf
                )
            );

            $records = $modifyRecordsEvent->getRecords();
            $theValue = $modifyRecordsEvent->getFinalContent();
            $slide = $modifyRecordsEvent->getSlide();
            $slideCollect = $modifyRecordsEvent->getSlideCollect();
            $slideCollectReverse = $modifyRecordsEvent->getSlideCollectReverse();
            $slideCollectFuzzy = $modifyRecordsEvent->getSlideCollectFuzzy();
            $conf = $modifyRecordsEvent->getConfiguration();

            if ($records !== []) {
                $this->timeTracker->setTSlogMessage('NUMROWS: ' . count($records));

                $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $this->getTypoScriptFrontendController());
                $cObj->setParent($this->cObj->data, $this->cObj->currentRecord);
                $this->cObj->currentRecordNumber = 0;

                foreach ($records as $row) {
                    $registerField = $conf['table'] . ':' . ($row['uid'] ?? 0);
                    $this->cObj->currentRecordNumber++;
                    $cObj->parentRecordNumber = $this->cObj->currentRecordNumber;
                    $this->cObj->lastChanged($row['tstamp'] ?? 0);
                    $cObj->setRequest($this->request);
                    $cObj->start($row, $conf['table']);
                    $tmpValue = $cObj->cObjGetSingle($renderObjName, $renderObjConf, $renderObjKey);
                    $cobjValue .= $tmpValue;
                }
            }
            if ($slideCollectReverse) {
                $theValue = $cobjValue . $theValue;
            } else {
                $theValue .= $cobjValue;
            }
            if ($slideCollect > 0) {
                $slideCollect--;
            }
            if ($slide) {
                if ($slide > 0) {
                    $slide--;
                }
                $conf['select.']['pidInList'] = $this->cObj->getSlidePids(
                    $conf['select.']['pidInList'] ?? '',
                    $conf['select.']['pidInList.'] ?? [],
                );
                if (isset($conf['select.']['pidInList.'])) {
                    unset($conf['select.']['pidInList.']);
                }
                $again = (string)$conf['select.']['pidInList'] !== '';
            }
        } while ($again && $slide && ((string)$tmpValue === '' && $slideCollectFuzzy || $slideCollect));

        $wrap = $this->cObj->stdWrapValue('wrap', $conf);
        if ($wrap) {
            $theValue = $this->cObj->wrap($theValue, $wrap);
        }
        if (isset($conf['stdWrap.'])) {
            $theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
        }
        return $theValue;
    }
}
