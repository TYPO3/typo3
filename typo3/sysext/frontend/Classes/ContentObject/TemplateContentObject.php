<?php
namespace TYPO3\CMS\Frontend\ContentObject;

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

use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains TEMPLATE class object.
 */
class TemplateContentObject extends AbstractContentObject
{
    /**
     * @var MarkerBasedTemplateService
     */
    protected $templateService;

    /**
     * Default constructor, which also instantiates the MarkerBasedTemplateService.
     *
     * @param ContentObjectRenderer $cObj
     */
    public function __construct(ContentObjectRenderer $cObj)
    {
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        parent::__construct($cObj);
    }

    /**
     * Rendering the cObject, TEMPLATE
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     * @see substituteMarkerArrayCached()
     */
    public function render($conf = [])
    {
        $subparts = [];
        $marks = [];
        $wraps = [];
        $markerWrap = isset($conf['markerWrap.']) ? $this->cObj->stdWrap($conf['markerWrap'], $conf['markerWrap.']) : $conf['markerWrap'];
        if (!$markerWrap) {
            $markerWrap = '### | ###';
        }
        list($PRE, $POST) = explode('|', $markerWrap);
        $POST = trim($POST);
        $PRE = trim($PRE);
        // Getting the content
        $content = $this->cObj->cObjGetSingle($conf['template'], $conf['template.'], 'template');
        $workOnSubpart = isset($conf['workOnSubpart.']) ? $this->cObj->stdWrap($conf['workOnSubpart'], $conf['workOnSubpart.']) : $conf['workOnSubpart'];
        if ($workOnSubpart) {
            $content = $this->templateService->getSubpart($content, $PRE . $workOnSubpart . $POST);
        }
        // Fixing all relative paths found:
        if ($conf['relPathPrefix']) {
            $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);
            $content = $htmlParser->prefixResourcePath($conf['relPathPrefix'], $content, $conf['relPathPrefix.']);
        }
        if ($content) {
            $nonCachedSubst = isset($conf['nonCachedSubst.']) ? $this->cObj->stdWrap($conf['nonCachedSubst'], $conf['nonCachedSubst.']) : $conf['nonCachedSubst'];
            // NON-CACHED:
            if ($nonCachedSubst) {
                // Getting marks
                if (is_array($conf['marks.'])) {
                    foreach ($conf['marks.'] as $theKey => $theValue) {
                        if (!strstr($theKey, '.')) {
                            $content = str_replace($PRE . $theKey . $POST, $this->cObj->cObjGetSingle($theValue, $conf['marks.'][$theKey . '.'], 'marks.' . $theKey), $content);
                        }
                    }
                }
                // Getting subparts.
                if (is_array($conf['subparts.'])) {
                    foreach ($conf['subparts.'] as $theKey => $theValue) {
                        if (!strstr($theKey, '.')) {
                            $subpart = $this->templateService->getSubpart($content, $PRE . $theKey . $POST);
                            if ($subpart) {
                                $this->cObj->setCurrentVal($subpart);
                                $content = $this->templateService->substituteSubpart($content, $PRE . $theKey . $POST, $this->cObj->cObjGetSingle($theValue, $conf['subparts.'][$theKey . '.'], 'subparts.' . $theKey), true);
                            }
                        }
                    }
                }
                // Getting subpart wraps
                if (is_array($conf['wraps.'])) {
                    foreach ($conf['wraps.'] as $theKey => $theValue) {
                        if (!strstr($theKey, '.')) {
                            $subpart = $this->templateService->getSubpart($content, $PRE . $theKey . $POST);
                            if ($subpart) {
                                $this->cObj->setCurrentVal($subpart);
                                $content = $this->templateService->substituteSubpart($content, $PRE . $theKey . $POST, explode('|', $this->cObj->cObjGetSingle($theValue, $conf['wraps.'][$theKey . '.'], 'wraps.' . $theKey)), true);
                            }
                        }
                    }
                }
            } else {
                // CACHED
                // Getting subparts.
                if (is_array($conf['subparts.'])) {
                    foreach ($conf['subparts.'] as $theKey => $theValue) {
                        if (!strstr($theKey, '.')) {
                            $subpart = $this->templateService->getSubpart($content, $PRE . $theKey . $POST);
                            if ($subpart) {
                                $GLOBALS['TSFE']->register['SUBPART_' . $theKey] = $subpart;
                                $subparts[$theKey]['name'] = $theValue;
                                $subparts[$theKey]['conf'] = $conf['subparts.'][$theKey . '.'];
                            }
                        }
                    }
                }
                // Getting marks
                if (is_array($conf['marks.'])) {
                    foreach ($conf['marks.'] as $theKey => $theValue) {
                        if (!strstr($theKey, '.')) {
                            $marks[$theKey]['name'] = $theValue;
                            $marks[$theKey]['conf'] = $conf['marks.'][$theKey . '.'];
                        }
                    }
                }
                // Getting subpart wraps
                if (is_array($conf['wraps.'])) {
                    foreach ($conf['wraps.'] as $theKey => $theValue) {
                        if (!strstr($theKey, '.')) {
                            $wraps[$theKey]['name'] = $theValue;
                            $wraps[$theKey]['conf'] = $conf['wraps.'][$theKey . '.'];
                        }
                    }
                }
                // Getting subparts
                $subpartArray = [];
                foreach ($subparts as $theKey => $theValue) {
                    // Set current with the content of the subpart...
                    $this->cObj->data[$this->cObj->currentValKey] = $GLOBALS['TSFE']->register['SUBPART_' . $theKey];
                    // Get subpart cObject and substitute it!
                    $subpartArray[$PRE . $theKey . $POST] = $this->cObj->cObjGetSingle($theValue['name'], $theValue['conf'], 'subparts.' . $theKey);
                }
                // Reset current to empty
                $this->cObj->data[$this->cObj->currentValKey] = '';
                // Getting marks
                $markerArray = [];
                foreach ($marks as $theKey => $theValue) {
                    $markerArray[$PRE . $theKey . $POST] = $this->cObj->cObjGetSingle($theValue['name'], $theValue['conf'], 'marks.' . $theKey);
                }
                // Getting wraps
                $subpartWraps = [];
                foreach ($wraps as $theKey => $theValue) {
                    $subpartWraps[$PRE . $theKey . $POST] = explode('|', $this->cObj->cObjGetSingle($theValue['name'], $theValue['conf'], 'wraps.' . $theKey));
                }
                // Substitution
                $substMarksSeparately = isset($conf['substMarksSeparately.']) ? $this->cObj->stdWrap($conf['substMarksSeparately'], $conf['substMarksSeparately.']) : $conf['substMarksSeparately'];
                if ($substMarksSeparately) {
                    $content = $this->templateService->substituteMarkerArrayCached($content, [], $subpartArray, $subpartWraps);
                    $content = $this->templateService->substituteMarkerArray($content, $markerArray);
                } else {
                    $content = $this->templateService->substituteMarkerArrayCached($content, $markerArray, $subpartArray, $subpartWraps);
                }
            }
        }
        if (isset($conf['stdWrap.'])) {
            $content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
        }
        return $content;
    }
}
