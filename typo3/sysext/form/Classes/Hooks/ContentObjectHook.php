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
use TYPO3\CMS\Extbase\Core\Bootstrap;
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
     * If FORM is dedected by the ContentObjectRenderer,
     * the Extbase plugin "Form" is initialized. At this time, the
     * controller "Frontend" action "execute" does the rest.
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
        // render the FORM CE from TYPO3 < 4.6
        if ($typoScriptObjectName === 'FORM'
            && !empty($typoScript['useDefaultContentObject'])
            && ExtensionManagementUtility::isLoaded('compatibility6')
        ) {
            $content = $contentObject->getContentObject($typoScriptObjectName)->render($typoScript);
        } elseif ($typoScriptObjectName === 'FORM') {
            $mergedTypoScript = null;
            // If the FORM configuration comes from the database
            // all TypoScript interpretation will be disabled for security.
            if ($contentObject->data['CType'] === 'mailform') {
                // If the FORM configuration comes from the database
                // and a predefined form is selected than the TypoScript
                // interpretation is allowed.
                $renderPredefinedForm = false;
                if (isset($contentObject->data['tx_form_predefinedform'])
                    && !empty($contentObject->data['tx_form_predefinedform'])
                ) {
                    $predefinedFormIdentifier = $contentObject->data['tx_form_predefinedform'];
                    if (isset($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_form.']['predefinedForms.'][$predefinedFormIdentifier . '.'])) {
                        $renderPredefinedForm = true;
                    } else {
                        throw new \InvalidArgumentException('No FORM configuration for identifier "' . $predefinedFormIdentifier . '" available.', 1457097250);
                    }
                }

                if ($renderPredefinedForm) {
                    $mergedTypoScript = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_form.']['predefinedForms.'][$predefinedFormIdentifier . '.'];
                    ArrayUtility::mergeRecursiveWithOverrule($mergedTypoScript, $typoScript);
                } else {
                    $bodytext = $contentObject->data['bodytext'];
                    /** @var $typoScriptParser TypoScriptParser */
                    $typoScriptParser = GeneralUtility::makeInstance(TypoScriptParser::class);
                    $typoScriptParser->parse($bodytext);
                    $mergedTypoScript = (array)$typoScriptParser->setup;
                    ArrayUtility::mergeRecursiveWithOverrule($mergedTypoScript, $typoScript);
                    // Disables TypoScript interpretation since TypoScript is handled that could contain insecure settings:
                    $mergedTypoScript[Configuration::DISABLE_CONTENT_ELEMENT_RENDERING] = true;
                }
            }
            $newTypoScript = (is_array($mergedTypoScript) ? $mergedTypoScript : $typoScript);

            $extbase = GeneralUtility::makeInstance(Bootstrap::class);
            $content = $extbase->run('', array(
                'pluginName' => 'Form',
                'extensionName' => 'Form',
                'vendorName' => 'TYPO3\\CMS',
                'controller' => 'Frontend',
                'action' => 'show',
                'settings' => array('typoscript' => $newTypoScript),
                'persistence' => array(),
                'view' => array(),
            ));

            // Only apply stdWrap to TypoScript that was NOT created by the wizard:
            if (isset($typoScript['stdWrap.'])) {
                $content = $contentObject->stdWrap($content, $typoScript['stdWrap.']);
            }
        }
        return $content;
    }
}
