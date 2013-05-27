<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * This ViewHelper cycles through the specified values.
 * This can be often used to specify CSS classes for example.
 * **Note:** To achieve the "zebra class" effect in a loop you can also use the "iteration" argument of the **for** ViewHelper.
 *
 * = Examples =
 *
 * <code title="Simple">
 * <f:for each="{0:1, 1:2, 2:3, 3:4}" as="foo"><f:cycle values="{0: 'foo', 1: 'bar', 2: 'baz'}" as="cycle">{cycle}</f:cycle></f:for>
 * </code>
 * <output>
 * foobarbazfoo
 * </output>
 *
 * <code title="Alternating CSS class">
 * <ul>
 *   <f:for each="{0:1, 1:2, 2:3, 3:4}" as="foo">
 *     <f:cycle values="{0: 'odd', 1: 'even'}" as="zebraClass">
 *       <li class="{zebraClass}">{foo}</li>
 *     </f:cycle>
 *   </f:for>
 * </ul>
 * </code>
 * <output>
 * <ul>
 *   <li class="odd">1</li>
 *   <li class="even">2</li>
 *   <li class="odd">3</li>
 *   <li class="even">4</li>
 * </ul>
 * </output>
 *
 * @api
 */
class CycleViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * the values to be iterated through
	 *
	 * @var array|\TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	protected $values = NULL;

	/**
	 * current values index
	 *
	 * @var integer
	 */
	protected $currentCycleIndex = NULL;

	/**
	 * @param array $values The array or object implementing \ArrayAccess (for example \TYPO3\CMS\Extbase\Persistence\ObjectStorage) to iterated over
	 * @param string $as The name of the iteration variable
	 * @return string Rendered result
	 * @api
	 */
	public function render($values, $as) {
		if ($values === NULL) {
			return $this->renderChildren();
		}
		if ($this->values === NULL) {
			$this->initializeValues($values);
		}
		if ($this->currentCycleIndex === NULL || $this->currentCycleIndex >= count($this->values)) {
			$this->currentCycleIndex = 0;
		}

		$currentValue = isset($this->values[$this->currentCycleIndex]) ? $this->values[$this->currentCycleIndex] : NULL;
		$this->templateVariableContainer->add($as, $currentValue);
		$output = $this->renderChildren();
		$this->templateVariableContainer->remove($as);

		$this->currentCycleIndex ++;

		return $output;
	}

	/**
	 * Sets this->values to the current values argument and resets $this->currentCycleIndex.
	 *
	 * @param array|\Traversable $values The array or \TYPO3\CMS\Extbase\Persistence\ObjectStorage to be stored in $this->values
	 * @return void
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 */
	protected function initializeValues($values) {
		if (is_object($values)) {
			if (!$values instanceof \Traversable) {
				throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('CycleViewHelper only supports arrays and objects implementing \Traversable interface' , 1248728393);
			}
			$this->values = iterator_to_array($values, FALSE);
		} else {
			$this->values = array_values($values);
		}
		$this->currentCycleIndex = 0;
	}
}

?>