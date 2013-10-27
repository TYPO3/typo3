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
 * Switch view helper which can be used to render content depending on a value or expression.
 * Implements what a basic switch()-PHP-method does.
 *
 * = Examples =
 *
 * <code title="Simple Switch statement">
 * <f:switch expression="{person.gender}">
 *   <f:case value="male">Mr.</f:case>
 *   <f:case value="female">Mrs.</f:case>
 *   <f:case default="TRUE">Mrs. or Mr.</f:case>
 * </f:switch>
 * </code>
 * <output>
 * Mr. / Mrs. (depending on the value of {person.gender}) or if no value evaluates to TRUE, default case
 * </output>
 *
 * Note: Using this view helper can be a sign of weak architecture. If you end up using it extensively
 * you might want to consider restructuring your controllers/actions and/or use partials and sections.
 * E.g. the above example could be achieved with <f:render partial="title.{person.gender}" /> and the partials
 * "title.male.html", "title.female.html", ...
 * Depending on the scenario this can be easier to extend and possibly contains less duplication.
 *
 * @api
 */
class SwitchViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper implements \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\ChildNodeAccessInterface {

	/**
	 * An array of \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode
	 * @var array
	 */
	private $childNodes = array();

	/**
	 * @var mixed
	 */
	protected $backupSwitchExpression = NULL;

	/**
	 * @var boolean
	 */
	protected $backupBreakState = FALSE;

	/**
	 * Setter for ChildNodes - as defined in ChildNodeAccessInterface
	 *
	 * @param array $childNodes Child nodes of this syntax tree node
	 * @return void
	 */
	public function setChildNodes(array $childNodes) {
		$this->childNodes = $childNodes;
	}

	/**
	 * @param mixed $expression
	 * @return string the rendered string
	 * @api
	 */
	public function render($expression) {
		$content = '';
		$this->backupSwitchState();
		$templateVariableContainer = $this->renderingContext->getViewHelperVariableContainer();

		$templateVariableContainer->addOrUpdate('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression', $expression);
		$templateVariableContainer->addOrUpdate('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'break', FALSE);

		foreach ($this->childNodes as $childNode) {
			if (
				!$childNode instanceof \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
				|| $childNode->getViewHelperClassName() !== 'TYPO3\CMS\Fluid\ViewHelpers\CaseViewHelper'
			) {
				continue;
			}
			$content = $childNode->evaluate($this->renderingContext);
			if ($templateVariableContainer->get('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'break') === TRUE) {
				break;
			}
		}

		$templateVariableContainer->remove('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression');
		$templateVariableContainer->remove('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'break');

		$this->restoreSwitchState();
		return $content;
	}

	/**
	 * Backups "switch expression" and "break" state of a possible parent switch ViewHelper to support nesting
	 *
	 * @return void
	 */
	protected function backupSwitchState() {
		if ($this->renderingContext->getViewHelperVariableContainer()->exists('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression')) {
			$this->backupSwitchExpression = $this->renderingContext->getViewHelperVariableContainer()->get('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'switchExpression');
		}
		if ($this->renderingContext->getViewHelperVariableContainer()->exists('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'break')) {
			$this->backupBreakState = $this->renderingContext->getViewHelperVariableContainer()->get('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'break');
		}
	}

	/**
	 * Restores "switch expression" and "break" states that might have been backed up in backupSwitchState() before
	 *
	 * @return void
	 */
	protected function restoreSwitchState() {
		if ($this->backupSwitchExpression !== NULL) {
			$this->renderingContext->getViewHelperVariableContainer()->addOrUpdate(
				'TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper',
				'switchExpression',
				$this->backupSwitchExpression
			);
		}
		if ($this->backupBreakState !== FALSE) {
			$this->renderingContext->getViewHelperVariableContainer()->addOrUpdate('TYPO3\CMS\Fluid\ViewHelpers\SwitchViewHelper', 'break', TRUE);
		}
	}
}
