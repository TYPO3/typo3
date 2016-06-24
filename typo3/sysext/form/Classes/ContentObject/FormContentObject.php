<?php
namespace TYPO3\CMS\Form\ContentObject;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Model\Configuration;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * FORM cObject, a wrapper to allow to use 10 = FORM in TypoScript
 * which actually executes the Extbase plugin (marked as non-cached)
 */
class FormContentObject extends AbstractContentObject
{
    /**
     * Renders the application defined cObject FORM
     *
     * The Extbase plugin "Form" is initialized. At this time, the
     * controller "Frontend" action "show" does the rest.
     *
     * @param array $conf TS configuration for this cObject
     * @return string HTML output
     * @throws \InvalidArgumentException
     */
    public function render($conf = array())
    {
        $mergedTypoScript = null;
        // If the FORM configuration is retrieved from the database
        // all TypoScript interpretation will be disabled for security.
        if ($this->cObj->data['CType'] === 'mailform') {
            // If the FORM configuration is retrieved from the database
            // and a predefined form is selected then the TypoScript
            // interpretation is allowed.
            $renderPredefinedForm = false;
            $predefinedFormIdentifier = null;
            if (!empty($this->cObj->data['tx_form_predefinedform'])) {
                $predefinedFormIdentifier = $this->cObj->data['tx_form_predefinedform'];
                if (isset($this->getTypoScriptFrontendController()->tmpl->setup['plugin.']['tx_form.']['predefinedForms.'][$predefinedFormIdentifier . '.'])) {
                    $renderPredefinedForm = true;
                } else {
                    throw new \InvalidArgumentException('No FORM configuration for identifier "' . $predefinedFormIdentifier . '" available.', 1466769483);
                }
            }

            if ($renderPredefinedForm && $predefinedFormIdentifier) {
                $mergedTypoScript = $this->getTypoScriptFrontendController()->tmpl->setup['plugin.']['tx_form.']['predefinedForms.'][$predefinedFormIdentifier . '.'];
                ArrayUtility::mergeRecursiveWithOverrule($mergedTypoScript, $conf);
            } else {
                $bodytext = $this->cObj->data['bodytext'];
                /** @var $typoScriptParser TypoScriptParser */
                $typoScriptParser = GeneralUtility::makeInstance(TypoScriptParser::class);
                $typoScriptParser->parse($bodytext);
                $mergedTypoScript = (array)$typoScriptParser->setup;
                ArrayUtility::mergeRecursiveWithOverrule($mergedTypoScript, $conf);
                // Disables TypoScript interpretation since TypoScript is handled that could contain insecure settings:
                $mergedTypoScript[Configuration::DISABLE_CONTENT_ELEMENT_RENDERING] = true;
            }
        }

        // make sure the extbase plugin is marked as Uncached
        $content = $this->prepareNonCacheableUserFunction(is_array($mergedTypoScript) ? $mergedTypoScript : $conf);

        // Only apply stdWrap to TypoScript that was NOT created by the wizard:
        if (isset($conf['stdWrap.'])) {
            $content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
        }
        return $content;
    }

    /**
     * Set up the extbase plugin to be a non-cacheable user function
     *
     * @param array $typoScript
     * @return string the content as placeholder for USER_INT code
     */
    protected function prepareNonCacheableUserFunction($typoScript)
    {
        $configuration = array (
            'userFunc' => 'TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run',
            'pluginName' => 'Form',
            'extensionName' => 'Form',
            'vendorName' => 'TYPO3\\CMS',
            'controller' => 'Frontend',
            'action' => 'show',
            'settings' => array('typoscript' => $typoScript),
            'persistence' => array(),
            'view' => array(),
        );

        $this->cObj->setUserObjectType(ContentObjectRenderer::OBJECTTYPE_USER_INT);
        $substKey = 'INT_SCRIPT.' . $this->getTypoScriptFrontendController()->uniqueHash();
        $content = '<!--' . $substKey . '-->';
        $this->getTypoScriptFrontendController()->config['INTincScript'][$substKey] = array(
            'conf' => $configuration,
            'cObj' => serialize($this->cObj),
            'type' => 'FUNC'
        );
        $this->cObj->setUserObjectType(false);
        return $content;
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
