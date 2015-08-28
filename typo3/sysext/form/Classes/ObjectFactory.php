<?php
namespace TYPO3\CMS\Form;

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

/**
 * Class ObjectFactory
 */
class ObjectFactory {

	/**
	 * @var array
	 */
	static protected $nameMapping = array(
		'typo3\\cms\\form\\domain\\model\\attribute\\acceptcharsetattribute' => \TYPO3\CMS\Form\Domain\Model\Attribute\AcceptCharsetAttribute::class,
		'typo3\\cms\\form\\domain\\model\\element\\checkboxgroupelement' => \TYPO3\CMS\Form\Domain\Model\Element\CheckboxGroupElement::class,
		'typo3\\cms\\form\\domain\\model\\element\\radiogroupelement' => \TYPO3\CMS\Form\Domain\Model\Element\RadioGroupElement::class,
		'typo3\\cms\\form\\domain\\model\\json\\checkboxgroupjsonelement' => \TYPO3\CMS\Form\Domain\Model\Json\CheckboxGroupJsonElement::class,
		'typo3\\cms\\form\\domain\\model\\json\\radiogroupjsonelement' => \TYPO3\CMS\Form\Domain\Model\Json\RadioGroupJsonElement::class,
		'typo3\\cms\\form\\filter\\regexpfilter' => \TYPO3\CMS\Form\Filter\RegExpFilter::class,
		'typo3\\cms\\form\\filter\\stripnewlinesfilter' => \TYPO3\CMS\Form\Filter\StripNewLinesFilter::class,
		'typo3\\cms\\form\\filter\\titlecasefilter' => \TYPO3\CMS\Form\Filter\TitleCaseFilter::class,
		'typo3\\cms\\form\\filter\\uppercasefilter' => \TYPO3\CMS\Form\Filter\UpperCaseFilter::class,
		'typo3\\cms\\form\\validation\\fileallowedtypesvalidator' => \TYPO3\CMS\Form\Validation\FileAllowedTypesValidator::class,
		'typo3\\cms\\form\\validation\\filemaximumsizevalidator' => \TYPO3\CMS\Form\Validation\FileMaximumSizeValidator::class,
		'typo3\\cms\\form\\validation\\fileminimumsizevalidator' => \TYPO3\CMS\Form\Validation\FileMinimumSizeValidator::class,
		'typo3\\cms\\form\\validation\\greaterthanvalidator' => \TYPO3\CMS\Form\Validation\GreaterThanValidator::class,
		'typo3\\cms\\form\\validation\\inarrayvalidator' => \TYPO3\CMS\Form\Validation\InArrayValidator::class,
		'typo3\\cms\\form\\validation\\regexpvalidator' => \TYPO3\CMS\Form\Validation\RegExpValidator::class,
		'typo3\\cms\\form\\view\\confirmation\\element\\checkboxgroupelementview' => \TYPO3\CMS\Form\View\Confirmation\Element\CheckboxGroupElementView::class,
		'typo3\\cms\\form\\view\\confirmation\\element\\radiogroupelementview' => \TYPO3\CMS\Form\View\Confirmation\Element\RadioGroupElementView::class,
		'typo3\\cms\\form\\view\\form\\element\\checkboxgroupelementview' => \TYPO3\CMS\Form\View\Form\Element\CheckboxGroupElementView::class,
		'typo3\\cms\\form\\view\\form\\element\\radiogroupelementview' => \TYPO3\CMS\Form\View\Form\Element\RadioGroupElementView::class,
		'typo3\\cms\\form\\view\\mail\\html\\element\\checkboxgroupelementview' => \TYPO3\CMS\Form\View\Mail\Html\Element\CheckboxGroupElementView::class,
		'typo3\\cms\\form\\view\\mail\\html\\element\\radiogroupelementview' => \TYPO3\CMS\Form\View\Mail\Html\Element\RadioGroupElementView::class,
		'typo3\\cms\\form\\view\\mail\\plain\\element\\checkboxgroupelementview' => \TYPO3\CMS\Form\View\Mail\Plain\Element\CheckboxGroupElementView::class,
		'typo3\\cms\\form\\view\\mail\\plain\\element\\radiogroupelementview' => \TYPO3\CMS\Form\View\Mail\Plain\Element\RadioGroupElementView::class,
		'typo3\\cms\\form\\filter\\removexssfilter' => \TYPO3\CMS\Form\Filter\RemoveXssFilter::class,
	);

	/**
	 * @param string $alias
	 * @param string $originalClassName
	 */
	static public function addToMap($alias, $originalClassName) {
		self::$nameMapping[$alias] = $originalClassName;
	}

	/**
	 * @param string $classNameOrAlias
	 * @return object
	 * @throws \RuntimeException
	 */
	static public function createFormObject($classNameOrAlias) {
		$lowerCasedClassNameOrAlias = strtolower($classNameOrAlias);
		if (!empty(self::$nameMapping[$lowerCasedClassNameOrAlias])) {
			$className = self::$nameMapping[$lowerCasedClassNameOrAlias];
		} else {
			$className = $classNameOrAlias;
		}

		if (!class_exists($className)) {
			throw new \RuntimeException('Class "' . $className . '" does not exist', 1440779351);
		}

		$arguments = func_get_args();
		$arguments[0] = $className;
		return call_user_func_array(array(GeneralUtility::class, 'makeInstance'), $arguments);
	}

}