<?php
namespace TYPO3\CMS\Core\Crypto\PasswordHashing;

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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * class providing configuration checks for saltedpasswords.
 *
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
 */
class ExtensionManagerConfigurationUtility
{
    /**
     * @var array
     */
    protected $extConf = [];

    /**
     * Deprecate this class
     */
    public function __construct()
    {
        trigger_error(self::class . ' is obsolete and will be removed in TYPO3 v10.0.', E_USER_DEPRECATED);
    }

    /**
     * Initializes this object.
     */
    private function init()
    {
        $requestSetup = $this->processPostData((array)$_REQUEST['data']);
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('saltedpasswords');
        $this->extConf['BE'] = array_merge((array)$extConf['BE'], (array)$requestSetup['BE']);
        $this->extConf['FE'] = array_merge((array)$extConf['FE'], (array)$requestSetup['FE']);
        $this->getLanguageService()->includeLLFile('EXT:saltedpasswords/Resources/Private/Language/locallang.xlf');
    }

    /**
     * Renders a selector element that allows to select the hash method to be used.
     *
     * @param array $params Field information to be rendered
     * @param string $disposal The configuration disposal ('FE' or 'BE')
     * @return string The HTML selector
     */
    protected function buildHashMethodSelector(array $params, $disposal)
    {
        $this->init();
        $propertyName = $params['propertyName'];
        $unknownVariablePleaseRenameMe = '\'' . substr(md5($propertyName), 0, 10) . '\'';
        $pField = '';
        $registeredMethods = \TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory::getRegisteredSaltedHashingMethods();
        foreach ($registeredMethods as $class => $reference) {
            $classInstance = GeneralUtility::makeInstance($reference);
            if ($classInstance instanceof \TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashInterface && $classInstance->isAvailable()) {
                $sel = $this->extConf[$disposal]['saltedPWHashingMethod'] == $class ? ' selected="selected" ' : '';
                $label = 'ext.saltedpasswords.title.' . strtolower(end(explode('\\', $class)));
                $pField .= '<option value="' . htmlspecialchars($class) . '"' . $sel . '>' . $GLOBALS['LANG']->getLL($label) . '</option>';
            }
        }
        $pField = '<select class="form-control" id="' . $propertyName . '" name="' . $params['fieldName'] .
            '" onChange="uFormUrl(' . $unknownVariablePleaseRenameMe . ')">' . $pField . '</select>';
        return $pField;
    }

    /**
     * Renders a selector element that allows to select the hash method to be
     * used (frontend disposal).
     *
     * @param array $params Field information to be rendered
     * @return string The HTML selector
     */
    public function buildHashMethodSelectorFE(array $params)
    {
        return $this->buildHashMethodSelector($params, 'FE');
    }

    /**
     * Renders a selector element that allows to select the hash method to
     * be used (backend disposal)
     *
     * @param array $params Field information to be rendered
     * @return string The HTML selector
     */
    public function buildHashMethodSelectorBE(array $params)
    {
        return $this->buildHashMethodSelector($params, 'BE');
    }

    /**
     * Processes the information submitted by the user using a POST request and
     * transforms it to a TypoScript node notation.
     *
     * @param array $postArray Incoming POST information
     * @return array Processed and transformed POST information
     */
    protected function processPostData(array $postArray = [])
    {
        foreach ($postArray as $key => $value) {
            // @todo Explain
            $parts = explode('.', $key, 2);
            if (count($parts) == 2) {
                // @todo Explain
                $value = $this->processPostData([$parts[1] => $value]);
                $postArray[$parts[0] . '.'] = array_merge((array)$postArray[$parts[0] . '.'], $value);
            } else {
                // @todo Explain
                $postArray[$parts[0]] = $value;
            }
        }
        return $postArray;
    }

    /**
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
