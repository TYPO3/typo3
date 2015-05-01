<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be;

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

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;


/**
 * View helper for rendering a styled content infobox markup.
 *
 * = States =
 *
 * The Infobox provides different context sensitive states that
 * can be used to provide an additional visual feedback to the
 * to the user to underline the meaning of the information.
 *
 * Possible values are in range from -2 to 2. Please choose a
 * meaningful value from the following list.
 *
 * -2: Notices (Default)
 * -1: Information
 * 0: Positive feedback
 * 1: Warnings
 * 2: Error
 *
 * = Examples =
 *
 * <code title="Simple">
 * <f:be.infobox title="Message title">your box content</f:be.infobox>
 * </code>
 *
 * <code title="All options">
 * <f:be.infobox title="Message title" message="your box content" state="-2" iconName="check" disableIcon="TRUE" />
 * </code>
 *
 * @api
 */
class InfoboxViewHelper extends AbstractViewHelper implements CompilableInterface {

	const STATE_NOTICE = -2;
	const STATE_INFO = -1;
	const STATE_OK = 0;
	const STATE_WARNING = 1;
	const STATE_ERROR = 2;

	/**
	 * @param string $title The title of the infobox
	 * @param string $message The message of the infobox, if NULL tag content is used
	 * @param int $state The state of the box, InfoboxViewHelper::STATE_*
	 * @param string $iconName The icon name from fontawsome, NULL sets default icon
	 * @param bool $disableIcon If set to TRUE, the icon is not rendered.
	 *
	 * @return string
	 */
	public function render($title = NULL, $message = NULL, $state = self::STATE_NOTICE, $iconName = NULL, $disableIcon = FALSE) {
		return self::renderStatic(
			array(
				'title' => $title,
				'message' => $message,
				'state' => $state,
				'iconName' => $iconName,
				'disableIcon' => $disableIcon
			),
			$this->buildRenderChildrenClosure(),
			$this->renderingContext
		);
	}

	/**
	 * @param array $arguments
	 * @param callable $renderChildrenClosure
	 * @param RenderingContextInterface $renderingContext
	 *
	 * @return string
	 * @throws Exception
	 */
	static public function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext) {
		$title = $arguments['title'];
		$message = $arguments['message'];
		$state = $arguments['state'];
		$iconName = $arguments['iconName'];
		$disableIcon = $arguments['disableIcon'];

		if ($message === NULL) {
			$message = $renderChildrenClosure();
		}
		switch ($state) {
			case self::STATE_NOTICE:
				$stateClass = 'notice';
				$icon = 'lightbulb-o';
				break;
			case self::STATE_INFO:
				$stateClass = 'info';
				$icon = 'info';
				break;
			case self::STATE_OK:
				$stateClass = 'success';
				$icon = 'check';
				break;
			case self::STATE_WARNING:
				$stateClass = 'warning';
				$icon = 'exclamation';
				break;
			case self::STATE_ERROR:
				$stateClass = 'danger';
				$icon = 'times';
				break;
			default:
				$stateClass = 'notice';
				$icon = 'lightbulb-o';
		}
		if ($iconName !== NULL) {
			$icon = htmlspecialchars($iconName);
		}
		$iconTemplate = '';
		if (!$disableIcon) {
			$iconTemplate = '' .
				'<div class="media-left">' .
					'<span class="fa-stack fa-lg callout-icon">' .
						'<i class="fa fa-circle fa-stack-2x"></i>' .
						'<i class="fa fa-' . $icon . ' fa-stack-1x"></i>' .
					'</span>' .
				'</div>';
		}
		$titleTemplate = '';
		if ($title !== NULL) {
			$titleTemplate = '<h4 class="callout-title">' . $title . '</h4>';
		}
		return '<div class="callout callout-' . $stateClass . '">' .
				'<div class="media">' .
					$iconTemplate .
					'<div class="media-body">' .
						$titleTemplate .
						'<div class="callout-body">' . $message . '</div>' .
					'</div>' .
				'</div>' .
			'</div>';
	}
}
