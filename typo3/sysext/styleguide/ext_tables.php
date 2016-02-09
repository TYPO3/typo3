<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {

    // Register "Styleguide" backend module
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'TYPO3.CMS.styleguide',
        'help',
        'styleguide',
        '',
        [
            'Styleguide' => 'index, typography, trees, tables, buttons, infobox, avatar, flashMessages, tca, debug, helpers, icons, tab'
        ],
        [
            'access' => 'user,group',
            'icon'   => 'EXT:styleguide/Resources/Public/Icons/module.svg',
            'labels' => 'LLL:EXT:styleguide/Resources/Private/Language/locallang_styleguide.xlf',
        ]
    );

    // @todo: If one of those lines is missing, dataHandler will just not persist new rows, but gives no error message
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_elements_basic');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_elements_group');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_elements_rsainput');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_elements_rsainput_inline_1_child');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_elements_rsainput_flex_1_inline_1_child');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_elements_rte');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_elements_rte_inline_1_child');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_elements_rte_flex_1_inline_1_child');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_elements_select');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_elements_special');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_elements_t3editor');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_elements_t3editor_inline_1_child');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_elements_t3editor_flex_1_inline_1_child');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_inline_expand');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_inline_expand_inline_1_child');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_required');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_required_flex_2_inline_1_child');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_required_inline_1_child');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_required_inline_2_child');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_required_inline_3_child');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_required_rte_2_child');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_staticdata');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_values_default');




    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms_inline_2_child1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms_inline_2_child2');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms_inline_3_mm');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_forms_inline_3_child');


    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_mnsymmetric_hotel');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_styleguide_mnsymmetric_hotel_rel');
}
