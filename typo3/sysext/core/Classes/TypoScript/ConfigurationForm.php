<?php
namespace TYPO3\CMS\Core\TypoScript;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides a simplified layer for making Constant Editor style configuration forms
 */
class ConfigurationForm extends ExtendedTemplateService
{
    /**
     * @var array
     */
    public $categories = [];

    /**
     * @var bool
     */
    public $ext_dontCheckIssetValues = 1;

    /**
     * @var string
     */
    public $ext_CEformName = 'tsStyleConfigForm';

    /**
     * @var bool
     */
    public $ext_printAll = true;

    /**
     * @var array
     */
    public $ext_incomingValues = [];

    /**
     * @var array
     */
    protected $ext_realValues = [];

    /**
     * @param string $configTemplate
     * @return array
     */
    public function ext_initTSstyleConfig($configTemplate)
    {
        // Do not log time-performance information
        $this->tt_track = 0;
        $this->constants = [$configTemplate, ''];
        // The editable constants are returned in an array.
        $theConstants = $this->generateConfig_constants();
        return $theConstants;
    }

    /**
     * Ext set value array
     *
     * @param array $theConstants
     * @param array $valueArray
     * @return array
     */
    public function ext_setValueArray($theConstants, $valueArray)
    {
        $temp = $this->flatSetup;
        $this->flatSetup = [];
        $this->flattenSetup($valueArray, '');
        $this->objReg = $this->ext_realValues = $this->flatSetup;
        $this->flatSetup = $temp;
        foreach ($theConstants as $k => $p) {
            if (isset($this->objReg[$k])) {
                $theConstants[$k]['value'] = $this->ext_realValues[$k];
            }
        }
        // Reset the default pool of categories.
        $this->categories = [];
        // The returned constants are sorted in categories, that goes into the $this->categories array
        $this->ext_categorizeEditableConstants($theConstants);
        return $theConstants;
    }

    /**
     * @return array
     */
    public function ext_getCategoriesForModMenu()
    {
        return $this->ext_getCategoryLabelArray();
    }

    /**
     * @param string $cat
     */
    public function ext_makeHelpInformationForCategory($cat)
    {
        $this->ext_getTSCE_config($cat);
    }

    /**
     * Get the form for extension configuration
     *
     * @param string $cat
     * @param array $theConstants
     * @param string $script
     * @param string $addFields
     * @param string $extKey
     * @param bool $addFormTag Adds opening <form> tag to the output, if TRUE
     * @return string The form
     */
    public function ext_getForm($cat, $theConstants, $script = '', $addFields = '', $extKey = '', $addFormTag = true)
    {
        $this->ext_makeHelpInformationForCategory($cat);
        $printFields = trim($this->ext_printFields($theConstants, $cat));
        $content = '';
        $content .= GeneralUtility::wrapJS('
			function uFormUrl(aname) {
				document.' . $this->ext_CEformName . '.action = ' . GeneralUtility::quoteJSvalue(GeneralUtility::linkThisScript() . '#') . '+aname;
			}
		');
        if ($addFormTag) {
            $content .= '<form action="' . htmlspecialchars(($script ?: GeneralUtility::linkThisScript())) . '" name="' . $this->ext_CEformName . '" method="post" enctype="multipart/form-data">';
        }
        $content .= $addFields;
        $content .= $printFields;
        $content .= '<input class="btn btn-default" type="submit" name="submit" value="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_tsfe.xlf:update')) . '" id="configuration-submit-' . htmlspecialchars($extKey) . '" />';
        $example = $this->ext_displayExample();
        $content .= $example ? '<hr/>' . $example : '';
        return $content;
    }

    /**
     * Display example
     *
     * @return string
     */
    public function ext_displayExample()
    {
        $out = '';
        if ($this->helpConfig['description'] || $this->helpConfig['header']) {
            $out = ($this->helpConfig['description'] ? implode(explode('//', $this->helpConfig['description']), '<br />') . '<br />' : '')
                . ($this->helpConfig['bulletlist'] ? '<ul><li>' . implode(explode('//', $this->helpConfig['bulletlist']), '<li>') . '</ul>' : '<BR>');
        }
        return $out;
    }

    /**
     * Merge incoming with existing
     *
     * @param array $arr
     * @return array
     */
    public function ext_mergeIncomingWithExisting($arr)
    {
        $parseObj = GeneralUtility::makeInstance(Parser\TypoScriptParser::class);
        $parseObj->parse(implode(LF, $this->ext_incomingValues));
        $arr2 = $parseObj->setup;
        ArrayUtility::mergeRecursiveWithOverrule($arr, $arr2);
        return $arr;
    }

    /**
     * @param array $params
     * @return array
     */
    public function ext_fNandV($params)
    {
        $fN = 'data[' . $params['name'] . ']';
        $idName = str_replace('.', '-', $params['name']);
        $fV = ($params['value'] = isset($this->ext_realValues[$params['name']]) ? $this->ext_realValues[$params['name']] : $params['default_value']);
        $reg = [];
        // Values entered from the constantsedit cannot be constants!
        if (preg_match('/^\\{[\\$][a-zA-Z0-9\\.]*\\}$/', trim($fV), $reg)) {
            $fV = '';
        }
        $fV = htmlspecialchars($fV);
        return [$fN, $fV, $params, $idName];
    }

    /**
     * @param string $key
     * @param string $var
     */
    public function ext_putValueInConf($key, $var)
    {
        $this->ext_incomingValues[$key] = $key . '=' . $var;
    }

    /**
     * @param string $key
     */
    public function ext_removeValueInConf($key)
    {
    }
}
