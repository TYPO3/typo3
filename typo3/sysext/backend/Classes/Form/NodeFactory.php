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

namespace TYPO3\CMS\Backend\Form;

use TYPO3\CMS\Backend\Form\Container\FilesControlContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Create an element object depending on renderType.
 *
 * This is the main factory to instantiate any node within the render
 * chain of FormEngine. All nodes must implement NodeInterface.
 *
 * Nodes are "container" classes of the render chain, "element" classes that
 * render single elements, as well as "fieldWizard", "fieldInformation" and
 * "fieldControl" classes which are called by single elements to enrich them.
 *
 * This factory gets a string "renderType" and then looks up in a list which
 * specific class should handle this renderType. This list can be extended with
 * own renderTypes by extensions, existing renderTypes can be overridden, and
 * - for complex cases - it is possible to register own resolver classes for single
 * renderTypes that can return a node class name to override the default lookup list.
 *
 * @todo: Declare final in v13.
 */
class NodeFactory
{
    /**
     * Node resolver classes
     * Nested array with nodeName as key, (sorted) priority as sub key and class as value
     */
    protected array $nodeResolver = [];

    /**
     * Default registry of node name to handling class
     */
    protected array $nodeTypes = [
        // Default container classes
        'flex' => Container\FlexFormEntryContainer::class,
        'flexFormContainerContainer' => Container\FlexFormContainerContainer::class,
        'flexFormElementContainer' => Container\FlexFormElementContainer::class,
        'flexFormNoTabsContainer' => Container\FlexFormNoTabsContainer::class,
        'flexFormSectionContainer' => Container\FlexFormSectionContainer::class,
        'flexFormTabsContainer' => Container\FlexFormTabsContainer::class,
        'fullRecordContainer' => Container\FullRecordContainer::class,
        'inline' => Container\InlineControlContainer::class,
        'inlineRecordContainer' => Container\InlineRecordContainer::class,
        FilesControlContainer::NODE_TYPE_IDENTIFIER => Container\FilesControlContainer::class,
        Container\FileReferenceContainer::NODE_TYPE_IDENTIFIER => Container\FileReferenceContainer::class,
        'siteLanguage' => Container\SiteLanguageContainer::class,
        'listOfFieldsContainer' => Container\ListOfFieldsContainer::class,
        'noTabsContainer' => Container\NoTabsContainer::class,
        'outerWrapContainer' => Container\OuterWrapContainer::class,
        'paletteAndSingleContainer' => Container\PaletteAndSingleContainer::class,
        'singleFieldContainer' => Container\SingleFieldContainer::class,
        'tabsContainer' => Container\TabsContainer::class,

        // Default single element classes
        'check' => Element\CheckboxElement::class,
        'checkboxToggle' => Element\CheckboxToggleElement::class,
        'checkboxLabeledToggle' => Element\CheckboxLabeledToggleElement::class,
        'email' => Element\EmailElement::class,
        'group' => Element\GroupElement::class,
        'folder' => Element\FolderElement::class,
        'input' => Element\InputTextElement::class,
        'number' => Element\NumberElement::class,
        'datetime' => Element\DatetimeElement::class,
        'link' => Element\LinkElement::class,
        'password' => Element\PasswordElement::class,
        'hidden' => Element\InputHiddenElement::class,
        'imageManipulation' => Element\ImageManipulationElement::class,
        'none' => Element\NoneElement::class,
        'radio' => Element\RadioElement::class,
        'selectCheckBox' => Element\SelectCheckBoxElement::class,
        'selectMultipleSideBySide' => Element\SelectMultipleSideBySideElement::class,
        'selectTree' => Element\SelectTreeElement::class,
        'selectSingle' => Element\SelectSingleElement::class,
        'selectSingleBox' => Element\SelectSingleBoxElement::class,
        'color' => Element\ColorElement::class,
        // t3editor is defined with a fallback so extensions can use it even if ext:t3editor is not loaded
        't3editor' => Element\TextElement::class,
        'text' => Element\TextElement::class,
        'textTable' => Element\TextTableElement::class,
        'unknown' => Element\UnknownElement::class,
        'user' => Element\UserElement::class,
        // special renderType for type="user" on sys_file_storage is_public column
        'userSysFileStorageIsPublic' => Element\UserSysFileStorageIsPublicElement::class,
        'fileInfo' => Element\FileInfoElement::class,
        'mfaInfo' => Element\MfaInfoElement::class,
        'slug' => Element\InputSlugElement::class,
        'language' => Element\SelectSingleElement::class,
        'category' => Element\CategoryElement::class,
        'passthrough' => Element\PassThroughElement::class,
        'belayoutwizard' => Element\BackendLayoutWizardElement::class,
        'json' => Element\JsonElement::class,
        'uuid' => Element\UuidElement::class,

        // Default classes to enrich single elements
        'fieldControl' => NodeExpansion\FieldControl::class,
        'fieldInformation' => NodeExpansion\FieldInformation::class,
        'fieldWizard' => NodeExpansion\FieldWizard::class,

        // Element information
        'tcaDescription' => FieldInformation\TcaDescription::class,
        'adminIsSystemMaintainer' => FieldInformation\AdminIsSystemMaintainer::class,
        'backendLayoutFromParentPage' => FieldInformation\BackendLayoutFromParentPage::class,

        // Element wizards
        'defaultLanguageDifferences' => FieldWizard\DefaultLanguageDifferences::class,
        'localizationStateSelector' => FieldWizard\LocalizationStateSelector::class,
        'otherLanguageContent' => FieldWizard\OtherLanguageContent::class,
        'otherLanguageThumbnails' => FieldWizard\OtherLanguageThumbnails::class,
        'recordsOverview' => FieldWizard\RecordsOverview::class,
        'selectIcons' => FieldWizard\SelectIcons::class,
        'tableList' => FieldWizard\TableList::class,

        // Element controls
        'addRecord' => FieldControl\AddRecord::class,
        'editPopup' => FieldControl\EditPopup::class,
        'elementBrowser' => FieldControl\ElementBrowser::class,
        'insertClipboard' => FieldControl\InsertClipboard::class,
        'linkPopup' => FieldControl\LinkPopup::class,
        'listModule' => FieldControl\ListModule::class,
        'resetSelection' => FieldControl\ResetSelection::class,
        'passwordGenerator' => FieldControl\PasswordGenerator::class,
    ];

    /**
     * Set up factory. Initialize additionally registered nodes.
     */
    public function __construct()
    {
        $this->registerAdditionalNodeTypesFromConfiguration();
        $this->registerNodeResolvers();
    }

    /**
     * Create a node depending on type
     *
     * @param array $data All information to decide which class should be instantiated and given down to sub nodes
     * @throws Exception
     */
    public function create(array $data): NodeInterface
    {
        if (empty($data['renderType'])) {
            throw new Exception(
                'Missing "renderType" in TCA of field "[' . ($data['tableName'] ?? 'unknown') . '][' . ($data['fieldName'] ?? 'unknown') . ']".',
                1431452406
            );
        }
        $type = $data['renderType'];

        $className = $this->nodeTypes[$type] ?? $this->nodeTypes['unknown'];

        if (!empty($this->nodeResolver[$type])) {
            // Resolver with the highest priority is called first. If it returns with a new class name,
            // it will be taken and loop is aborted, otherwise resolver with next lower priority is called.
            foreach ($this->nodeResolver[$type] as $priority => $resolverClassName) {
                $resolver = $this->initializeNodeResolverClass($resolverClassName, $data);
                // Resolver classes do NOT receive the name of the already resolved class. Single
                // resolvers should not have dependencies to each other or the default implementation,
                // so they also shouldn't know the output of a different resolving class.
                // Additionally, the globalOptions array is NOT given by reference here, changing config is a
                // task of container classes alone and must not be abused here.
                $newClassName = $resolver->resolve();
                if ($newClassName !== null) {
                    $className = $newClassName;
                    break;
                }
            }
        }

        return $this->initializeNodeClass($className, $data);
    }

    /**
     * Add node types from nodeRegistry to $this->nodeTypes.
     * This can be used to add new render types or to overwrite existing node types. The registered class must
     * implement the NodeInterface and will be called if a node with this renderType is rendered.
     *
     * @throws Exception if configuration is incomplete or two nodes with identical priorities are registered
     */
    protected function registerAdditionalNodeTypesFromConfiguration(): void
    {
        // List of additional or override nodes
        $registeredTypeOverrides = $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'];
        // Sanitize input array
        $registeredPrioritiesForNodeNames = [];
        foreach ($registeredTypeOverrides as $override) {
            if (!isset($override['nodeName']) || !isset($override['class']) || !isset($override['priority'])) {
                throw new Exception(
                    'Key class, nodeName or priority missing for an entry in $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'formEngine\'][\'nodeRegistry\']',
                    1432207533
                );
            }
            if ($override['priority'] < 0 || $override['priority'] > 100) {
                throw new Exception(
                    'Priority of element ' . $override['nodeName'] . ' with class ' . $override['class'] . ' is ' . $override['priority'] . ', but must between 0 and 100',
                    1432223531
                );
            }
            if (isset($registeredPrioritiesForNodeNames[$override['nodeName']][$override['priority']])) {
                throw new Exception(
                    'Element ' . $override['nodeName'] . ' already has an override registered with priority ' . $override['priority'],
                    1432223893
                );
            }
            $registeredPrioritiesForNodeNames[$override['nodeName']][$override['priority']] = '';
        }
        // Add element with the highest priority to registry
        $highestPriority = [];
        foreach ($registeredTypeOverrides as $override) {
            if (!isset($highestPriority[$override['nodeName']]) || $override['priority'] > $highestPriority[$override['nodeName']]) {
                $highestPriority[$override['nodeName']] = $override['priority'];
                $this->nodeTypes[$override['nodeName']] = $override['class'];
            }
        }
    }

    /**
     * Add resolver and add them sorted to a local property.
     * This can be used to manipulate the nodeName to class resolution with own code.
     *
     * @throws Exception if configuration is incomplete or two resolver with identical priorities are registered
     */
    protected function registerNodeResolvers(): void
    {
        // List of node resolver
        $registeredNodeResolvers = $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeResolver'];
        $resolversByType = [];
        foreach ($registeredNodeResolvers as $nodeResolver) {
            if (!isset($nodeResolver['nodeName']) || !isset($nodeResolver['class']) || !isset($nodeResolver['priority'])) {
                throw new Exception(
                    'Key class, nodeName or priority missing for an entry in $GLOBALS[\'TYPO3_CONF_VARS\'][\'SYS\'][\'formEngine\'][\'nodeResolver\']',
                    1433155522
                );
            }
            if ($nodeResolver['priority'] < 0 || $nodeResolver['priority'] > 100) {
                throw new Exception(
                    'Priority of element ' . $nodeResolver['nodeName'] . ' with class ' . $nodeResolver['class'] . ' is ' . $nodeResolver['priority'] . ', but must between 0 and 100',
                    1433155563
                );
            }
            if (isset($resolversByType[$nodeResolver['nodeName']][$nodeResolver['priority']])) {
                throw new Exception(
                    'Element ' . $nodeResolver['nodeName'] . ' already has a resolver registered with priority ' . $nodeResolver['priority'],
                    1433155705
                );
            }
            $resolversByType[$nodeResolver['nodeName']][$nodeResolver['priority']] = $nodeResolver['class'];
        }
        $sortedResolversByType = [];
        foreach ($resolversByType as $nodeName => $prioritiesAndClasses) {
            krsort($prioritiesAndClasses);
            $sortedResolversByType[$nodeName] = $prioritiesAndClasses;
        }
        $this->nodeResolver = $sortedResolversByType;
    }

    /**
     * Instantiate a NodeInterface class and set data.
     *
     * @param array $data Main data array
     */
    protected function initializeNodeClass(string $className, array $data): NodeInterface
    {
        if (method_exists($className, 'setData')) {
            $node = GeneralUtility::makeInstance($className);
            $node->setData($data);
            return $node;
        }
        return GeneralUtility::makeInstance($className, $this, $data);
    }

    /**
     * Instantiate a NodeResolverInterface class and set data.
     *
     * @param array $data Main data array
     */
    protected function initializeNodeResolverClass(string $className, array $data): NodeResolverInterface
    {
        if (method_exists($className, 'setData')) {
            $node = GeneralUtility::makeInstance($className);
            $node->setData($data);
            return $node;
        }
        return GeneralUtility::makeInstance($className, $this, $data);
    }
}
