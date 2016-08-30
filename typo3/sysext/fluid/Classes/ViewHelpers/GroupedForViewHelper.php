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
 * Grouped loop view helper.
 * Loops through the specified values.
 *
 * The groupBy argument also supports property paths.
 *
 * = Examples =
 *
 * <code title="Simple">
 * <f:groupedFor each="{0: {name: 'apple', color: 'green'}, 1: {name: 'cherry', color: 'red'}, 2: {name: 'banana', color: 'yellow'}, 3: {name: 'strawberry', color: 'red'}}" as="fruitsOfThisColor" groupBy="color">
 *   <f:for each="{fruitsOfThisColor}" as="fruit">
 *     {fruit.name}
 *   </f:for>
 * </f:groupedFor>
 * </code>
 * <output>
 * apple cherry strawberry banana
 * </output>
 *
 * <code title="Two dimensional list">
 * <ul>
 *   <f:groupedFor each="{0: {name: 'apple', color: 'green'}, 1: {name: 'cherry', color: 'red'}, 2: {name: 'banana', color: 'yellow'}, 3: {name: 'strawberry', color: 'red'}}" as="fruitsOfThisColor" groupBy="color" groupKey="color">
 *     <li>
 *       {color} fruits:
 *       <ul>
 *         <f:for each="{fruitsOfThisColor}" as="fruit" key="label">
 *           <li>{label}: {fruit.name}</li>
 *         </f:for>
 *       </ul>
 *     </li>
 *   </f:groupedFor>
 * </ul>
 * </code>
 * <output>
 * <ul>
 *   <li>green fruits
 *     <ul>
 *       <li>0: apple</li>
 *     </ul>
 *   </li>
 *   <li>red fruits
 *     <ul>
 *       <li>1: cherry</li>
 *     </ul>
 *     <ul>
 *       <li>3: strawberry</li>
 *     </ul>
 *   </li>
 *   <li>yellow fruits
 *     <ul>
 *       <li>2: banana</li>
 *     </ul>
 *   </li>
 * </ul>
 * </output>
 *
 * @api
 */
class GroupedForViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * Iterates through elements of $each and renders child nodes
     *
     * @param array $each The array or \TYPO3\CMS\Extbase\Persistence\ObjectStorage to iterated over
     * @param string $as The name of the iteration variable
     * @param string $groupBy Group by this property
     * @param string $groupKey The name of the variable to store the current group
     * @return string Rendered string
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     * @api
     */
    public function render($each, $as, $groupBy, $groupKey = 'groupKey')
    {
        $output = '';
        if ($each === null) {
            return '';
        }
        if (is_object($each)) {
            if (!$each instanceof \Traversable) {
                throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('GroupedForViewHelper only supports arrays and objects implementing \Traversable interface', 1253108907);
            }
            $each = iterator_to_array($each);
        }

        $groups = $this->groupElements($each, $groupBy);

        foreach ($groups['values'] as $currentGroupIndex => $group) {
            $this->templateVariableContainer->add($groupKey, $groups['keys'][$currentGroupIndex]);
            $this->templateVariableContainer->add($as, $group);
            $output .= $this->renderChildren();
            $this->templateVariableContainer->remove($groupKey);
            $this->templateVariableContainer->remove($as);
        }
        return $output;
    }

    /**
     * Groups the given array by the specified groupBy property.
     *
     * @param array $elements The array / traversable object to be grouped
     * @param string $groupBy Group by this property
     * @return array The grouped array in the form array('keys' => array('key1' => [key1value], 'key2' => [key2value], ...), 'values' => array('key1' => array([key1value] => [element1]), ...), ...)
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    protected function groupElements(array $elements, $groupBy)
    {
        $groups = ['keys' => [], 'values' => []];
        foreach ($elements as $key => $value) {
            if (is_array($value)) {
                $currentGroupIndex = isset($value[$groupBy]) ? $value[$groupBy] : null;
            } elseif (is_object($value)) {
                $currentGroupIndex = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($value, $groupBy);
            } else {
                throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('GroupedForViewHelper only supports multi-dimensional arrays and objects', 1253120365);
            }
            $currentGroupKeyValue = $currentGroupIndex;
            if (is_object($currentGroupIndex)) {
                if ($currentGroupIndex instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
                    $currentGroupIndex = $currentGroupIndex->_loadRealInstance();
                }
                $currentGroupIndex = spl_object_hash($currentGroupIndex);
            }
            $groups['keys'][$currentGroupIndex] = $currentGroupKeyValue;
            $groups['values'][$currentGroupIndex][$key] = $value;
        }
        return $groups;
    }
}
