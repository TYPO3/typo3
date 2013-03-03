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
 * ViewHelper that renders a section or a specified partial
 *
 * == Examples ==
 *
 * <code title="Rendering partials">
 * <f:render partial="SomePartial" arguments="{foo: someVariable}" />
 * </code>
 * <output>
 * the content of the partial "SomePartial". The content of the variable {someVariable} will be available in the partial as {foo}
 * </output>
 *
 * <code title="Rendering sections">
 * <f:section name="someSection">This is a section. {foo}</f:section>
 * <f:render section="someSection" arguments="{foo: someVariable}" />
 * </code>
 * <output>
 * the content of the section "someSection". The content of the variable {someVariable} will be available in the partial as {foo}
 * </output>
 *
 * <code title="Rendering recursive sections">
 * <f:section name="mySection">
 *  <ul>
 *    <f:for each="{myMenu}" as="menuItem">
 *      <li>
 *        {menuItem.text}
 *        <f:if condition="{menuItem.subItems}">
 *          <f:render section="mySection" arguments="{myMenu: menuItem.subItems}" />
 *        </f:if>
 *      </li>
 *    </f:for>
 *  </ul>
 * </f:section>
 * <f:render section="mySection" arguments="{myMenu: menu}" />
 * </code>
 * <output>
 * <ul>
 *   <li>menu1
 *     <ul>
 *       <li>menu1a</li>
 *       <li>menu1b</li>
 *     </ul>
 *   </li>
 * [...]
 * (depending on the value of {menu})
 * </output>
 *
 *
 * <code title="Passing all variables to a partial">
 * <f:render partial="somePartial" arguments="{_all}" />
 * </code>
 * <output>
 * the content of the partial "somePartial".
 * Using the reserved keyword "_all", all available variables will be passed along to the partial
 * </output>
 *
 * @api
 */
class RenderViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Renders the content.
	 *
	 * @param string $section Name of section to render. If used in a layout, renders a section of the main content file. If used inside a standard template, renders a section of the same file.
	 * @param string $partial Reference to a partial.
	 * @param array $arguments Arguments to pass to the partial.
	 * @param boolean $optional Set to TRUE, to ignore unknown sections, so the definition of a section inside a template can be optional for a layout
	 * @return string
	 * @api
	 */
	public function render($section = NULL, $partial = NULL, $arguments = array(), $optional = FALSE) {
		$arguments = $this->loadSettingsIntoArguments($arguments);

		if ($partial !== NULL) {
			return $this->viewHelperVariableContainer->getView()->renderPartial($partial, $section, $arguments);
		} elseif ($section !== NULL) {
			return $this->viewHelperVariableContainer->getView()->renderSection($section, $arguments, $optional);
		}
		return '';
	}

	/**
	 * If $arguments['settings'] is not set, it is loaded from the TemplateVariableContainer (if it is available there).
	 *
	 * @param array $arguments
	 * @return array
	 */
	protected function loadSettingsIntoArguments($arguments) {
		if (!isset($arguments['settings']) && $this->templateVariableContainer->exists('settings')) {
			$arguments['settings'] = $this->templateVariableContainer->get('settings');
		}
		return $arguments;
	}
}

?>