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

namespace TYPO3\CMS\Backend\Form\Container;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract container has various methods used by the container classes
 */
abstract class AbstractContainer extends AbstractNode
{
    protected NodeFactory $nodeFactory;
    protected BackendViewFactory $backendViewFactory;

    /**
     * Injection of NodeFactory, which is used in this abstract already.
     * Using inject* method to not pollute __construct() for inheriting classes.
     */
    public function injectNodeFactory(NodeFactory $nodeFactory): void
    {
        $this->nodeFactory = $nodeFactory;
    }

    public function injectBackendViewFactory(BackendViewFactory $backendViewFactory)
    {
        $this->backendViewFactory = $backendViewFactory;
    }

    /**
     * Merge field information configuration with default and render them.
     *
     * @return array Result array
     */
    protected function renderFieldInformation(): array
    {
        $options = $this->data;
        $fieldInformation = $this->defaultFieldInformation;
        $currentRenderType = $this->data['renderType'];
        $fieldInformationFromTca = $options['processedTca']['ctrl']['container'][$currentRenderType]['fieldInformation'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($fieldInformation, $fieldInformationFromTca);
        $options['renderType'] = 'fieldInformation';
        $options['renderData']['fieldInformation'] = $fieldInformation;
        return $this->nodeFactory->create($options)->render();
    }

    /**
     * Merge field control configuration with default controls and render them.
     *
     * @return array Result array
     */
    protected function renderFieldControl(): array
    {
        $options = $this->data;
        $fieldControl = $this->defaultFieldControl;
        $currentRenderType = $this->data['renderType'];
        $fieldControlFromTca = $options['processedTca']['ctrl']['container'][$currentRenderType]['fieldControl'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($fieldControl, $fieldControlFromTca);
        $options['renderType'] = 'fieldControl';
        $options['renderData']['fieldControl'] = $fieldControl;
        return $this->nodeFactory->create($options)->render();
    }

    /**
     * Merge field wizard configuration with default wizards and render them.
     *
     * @return array Result array
     */
    protected function renderFieldWizard(): array
    {
        $options = $this->data;
        $fieldWizard = $this->defaultFieldWizard;
        $currentRenderType = $this->data['renderType'];
        $fieldWizardFromTca = $options['processedTca']['ctrl']['container'][$currentRenderType]['fieldWizard'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($fieldWizard, $fieldWizardFromTca);
        $options['renderType'] = 'fieldWizard';
        $options['renderData']['fieldWizard'] = $fieldWizard;
        return $this->nodeFactory->create($options)->render();
    }

    /**
     * A single field of TCA 'types' 'showitem' can have three semicolon separated configuration options:
     *   fieldName: Name of the field to be found in TCA 'columns' section
     *   fieldLabel: An alternative field label
     *   paletteName: Name of a palette to be found in TCA 'palettes' section that is rendered after this field
     *
     * @param string $field Semicolon separated field configuration
     * @throws \RuntimeException
     */
    protected function explodeSingleFieldShowItemConfiguration(string $field): array
    {
        $fieldArray = GeneralUtility::trimExplode(';', $field);
        if (empty($fieldArray[0])) {
            throw new \RuntimeException('Field must not be empty', 1426448465);
        }
        return [
            'fieldName' => $fieldArray[0],
            'fieldLabel' => !empty($fieldArray[1]) ? $fieldArray[1] : null,
            'paletteName' => !empty($fieldArray[2]) ? $fieldArray[2] : null,
        ];
    }

    /**
     * Render tabs with label and content. Used by TabsContainer and FlexFormTabsContainer.
     * Re-uses the template Tabs.html which is also used by ModuleTemplate.php.
     *
     * @param array $menuItems Tab elements, each element is an array with "label" and "content"
     * @param string $domId DOM id attribute, will be appended with an iteration number per tab.
     */
    protected function renderTabMenu(array $menuItems, string $domId): string
    {
        $view = $this->backendViewFactory->create($this->data['request']);
        $view->assignMultiple([
            'id' => $domId,
            'items' => $menuItems,
            'defaultTabIndex' => 1,
            'wrapContent' => false,
            'storeLastActiveTab' => true,
        ]);
        return $view->render('Form/Tabs');
    }

    protected function wrapWithFieldsetAndLegend(string $fieldContent): string
    {
        $legend = htmlspecialchars($this->data['parameterArray']['fieldConf']['label']);
        if ($this->getBackendUserAuthentication()->shallDisplayDebugInformation()) {
            $fieldName = $this->data['flexFormContainerFieldName'] ?? $this->data['flexFormFieldName'] ?? $this->data['fieldName'];
            $legend .= ' <code>[' . htmlspecialchars($fieldName) . ']</code>';
        }
        return '<fieldset><legend class="form-label t3js-formengine-label">' . $legend . '</legend>' . $fieldContent . '</fieldset>';
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
