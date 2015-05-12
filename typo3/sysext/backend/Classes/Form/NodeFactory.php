<?php
namespace TYPO3\CMS\Backend\Form;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Form\Container;
use TYPO3\CMS\Backend\Form\Element;

/**
 * Create an element object depending on type.
 *
 * @todo: This is currently just a straight ahead approach. A registry should be added allowing
 * @todo: extensions to overwrite existing implementations and all Element and Container classes
 * @todo: should be created through this factory. The factory itself could be added in the constructor
 * @todo: of AbstractNode to have it always available.
 */
class NodeFactory {

	/**
	 * Default registry of node-type to handling class
	 *
	 * @var array
	 */
	protected $nodeTypes = array(
		'flex' => Container\FlexFormContainer::class,
		'flexFormContainerContainer' => Container\FlexFormContainerContainer::class,
		'flexFormElementContainer' => Container\FlexFormElementContainer::class,
		'flexFormLanguageContainer' => Container\FlexFormLanguageContainer::class,
		'flexFormNoTabsContainer' => Container\FlexFormNoTabsContainer::class,
		'flexFormSectionContainer' => Container\FlexFormSectionContainer::class,
		'flexFormTabsContainer' => Container\FlexFormTabsContainer::class,
		'fullRecordContainer' => Container\FullRecordContainer::class,
		'inline' => Container\InlineControlContainer::class,
		'inlineRecordContainer' => Container\InlineRecordContainer::class,
		'listOfFieldsContainer' => Container\ListOfFieldsContainer::class,
		'noTabsContainer' => Container\NoTabsContainer::class,
		'paletteAndSingleContainer' => Container\PaletteAndSingleContainer::class,
		'singleFieldContainer' => Container\SingleFieldContainer::class,
		'soloFieldContainer' => Container\SoloFieldContainer::class,
		'tabsContainer' => Container\TabsContainer::class,

		'check' => Element\CheckboxElement::class,
		'group' => Element\GroupElement::class,
		'input' => Element\InputElement::class,
		'imageManipulation' => Element\ImageManipulationElement::class,
		'none' => Element\NoneElement::class,
		'radio' => Element\RadioElement::class,
		'selectCheckBox' => Element\SelectCheckBoxElement::class,
		'selectMultipleSideBySide' => Element\SelectMultipleSideBySideElement::class,
		'selectTree' => Element\SelectTreeElement::class,
		'selectSingle' => Element\SelectSingleElement::class,
		'selectSingleBox' => Element\SelectSingleBoxElement::class,
		'text' => Element\TextElement::class,
		'unknown' => Element\UnknownElement::class,
		'user' => Element\UserElement::class,
	);

	/**
	 * Set up factory
	 */
	public function __construct() {
		// @todo: Add additional base types and override existing types
	}

	/**
	 * Create an element depending on type
	 *
	 * @param array $globalOptions All information to decide which class should be instantiated and given down to sub nodes
	 * @return AbstractNode
	 * @throws Exception
	 */
	public function create(array $globalOptions) {
		if (!is_string($globalOptions['type'])) {
			throw new Exception('No type definition found', 1431452406);
		}
		$type = $globalOptions['type'];

		if ($type === 'select') {
			$config = $globalOptions['parameterArray']['fieldConf']['config'];
			$maxitems = (int)$config['maxitems'];
			if (isset($config['renderMode']) && $config['renderMode'] === 'tree') {
				$type = 'selectTree';
			} elseif ($maxitems <= 1) {
				$type = 'selectSingle';
			} elseif (isset($config['renderMode']) && $config['renderMode'] === 'singlebox') {
				$type = 'selectSingleBox';
			} elseif (isset($config['renderMode']) && $config['renderMode'] === 'checkbox') {
				$type = 'selectCheckBox';
			} else {
				$type = 'selectMultipleSideBySide';
			}
		}

		$className = isset($this->nodeTypes[$type]) ? $this->nodeTypes[$type] : $this->nodeTypes['unknown'];
		/** @var AbstractNode $nodeInstance */
		$nodeInstance = $this->instantiate($className);
		if (!$nodeInstance instanceof NodeInterface) {
			throw new Exception('Node of type ' . get_class($nodeInstance) . ' must implement NodeInterface', 1431872546);
		}
		return $nodeInstance->setGlobalOptions($globalOptions);
	}

	/**
	 * Instantiate given class name
	 *
	 * @param string $className Given class name
	 * @return object
	 */
	protected function instantiate($className) {
		return GeneralUtility::makeInstance($className);
	}

}
