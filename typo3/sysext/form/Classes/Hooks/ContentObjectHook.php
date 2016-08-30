<?php
namespace TYPO3\CMS\Form\Hooks;

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

use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Model\Configuration;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Hook cObjGetSingleExt
 */
class ContentObjectHook
{
    /**
     * Renders the application defined cObject FORM
     * which overrides the TYPO3 default cObject FORM
     *
     * Convert FORM to COA_INT - COA_INT.10 = FORM_INT
     * If FORM_INT is also dedected by the ContentObjectRenderer, and now
     * the Extbaseplugin "Form" is initalized. At this time the
     * controller "Frontend" action "execute" do the rest.
     *
     * @param string $typoScriptObjectName Name of the object
     * @param array $typoScript TS configuration for this cObject
     * @param string $typoScriptKey A string label used for the internal debugging tracking.
     * @param ContentObjectRenderer $contentObject reference
     * @return string HTML output
     */
    public function cObjGetSingleExt($typoScriptObjectName, array $typoScript, $typoScriptKey, ContentObjectRenderer $contentObject)
    {
        $content = '';
        if (
            $typoScriptObjectName === 'FORM'
            && !empty($typoScript['useDefaultContentObject'])
            && ExtensionManagementUtility::isLoaded('compatibility6')
        ) {
            $content = $contentObject->getContentObject($typoScriptObjectName)->render($typoScript);
        } elseif ($typoScriptObjectName === 'FORM') {
            $mergedTypoScript = null;
            if ($contentObject->data['CType'] === 'mailform') {
                $bodytext = $contentObject->data['bodytext'];
                /** @var $typoScriptParser TypoScriptParser */
                $typoScriptParser = GeneralUtility::makeInstance(TypoScriptParser::class);
                $typoScriptParser->parse($bodytext);
                $mergedTypoScript = (array)$typoScriptParser->setup;
                ArrayUtility::mergeRecursiveWithOverrule($mergedTypoScript, $typoScript);
                // Disables content elements since TypoScript is handled that could contain insecure settings:
                $mergedTypoScript[Configuration::DISABLE_CONTENT_ELEMENT_RENDERING] = true;
            }
            $newTypoScript = [
                '10' => 'FORM_INT',
                '10.' => (is_array($mergedTypoScript) ? $mergedTypoScript : $typoScript),
            ];
            $content = $contentObject->cObjGetSingle('COA_INT', $newTypoScript);
            // Only apply stdWrap to TypoScript that was NOT created by the wizard:
            if (isset($typoScript['stdWrap.'])) {
                $content = $contentObject->stdWrap($content, $typoScript['stdWrap.']);
            }
        } elseif ($typoScriptObjectName === 'FORM_INT') {
            $extbase = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Core\Bootstrap::class);
            $content = $extbase->run('', [
                'pluginName' => 'Form',
                'extensionName' => 'Form',
                'vendorName' => 'TYPO3\\CMS',
                'controller' => 'Frontend',
                'action' => 'show',
                'settings' => ['typoscript' => $typoScript],
                'persistence' => [],
                'view' => [],
            ]);
        }
        return $content;
    }
}
