<?php
namespace TYPO3\CMS\Beuser\ViewHelpers;

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
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Extbase\Domain\Model\BackendUser;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;

/**
 * Views sprite icon for a record (object)
 *
 * @author Felix Kopp <felix-source@phorax.com>
 * @internal
 */
class SpriteIconForRecordViewHelper extends AbstractBackendViewHelper implements CompilableInterface {

	/**
	 * Displays spriteIcon for database table and object
	 *
	 * @param string $table
	 * @param object $object
	 * @return string
	 * @see \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($table, $row)
	 */
	public function render($table, $object) {
		return self::renderStatic(
			array(
				'table' => $table,
				'object' => $object
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
		$object = $arguments['object'];
		$table = $arguments['table'];

		if (!is_object($object) || !method_exists($object, 'getUid')) {
			return '';
		}
		$row = array(
			'uid' => $object->getUid(),
			'startTime' => FALSE,
			'endTime' => FALSE
		);
		if (method_exists($object, 'getIsDisabled')) {
			$row['disable'] = $object->getIsDisabled();
		}
		if (method_exists($object, 'getHidden')) {
			$row['hidden'] = $object->getHidden();
		}
		if ($table === 'be_users' && $object instanceof BackendUser) {
			$row['admin'] = $object->getIsAdministrator();
		}
		if (method_exists($object, 'getStartDateAndTime')) {
			$row['startTime'] = $object->getStartDateAndTime();
		}
		if (method_exists($object, 'getEndDateAndTime')) {
			$row['endTime'] = $object->getEndDateAndTime();
		}
		return IconUtility::getSpriteIconForRecord($table, $row);
	}

}
