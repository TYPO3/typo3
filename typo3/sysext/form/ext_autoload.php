<?php
$extPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('form');
/*
 * These are classes which may be looked up with different casing than the original class
 * which is no issue with PHP as class names are case insensitive, but is an issue with our class loader
 * as it makes assumptions on the file location from the looked up class name, which fails on case sensitive file systems.
 * This file must stay as long as our class loader has to look up class files during runtime.
 */
return array(
	'typo3\\cms\\form\\domain\\model\\attribute\\acceptcharsetattribute' => $extPath . 'Classes/Domain/Model/Attribute/AcceptCharsetAttribute.php',
	'typo3\\cms\\form\\domain\\model\\element\\checkboxgroupelement' => $extPath . 'Classes/Domain/Model/Element/CheckboxGroupElement.php',
	'typo3\\cms\\form\\domain\\model\\element\\radiogroupelement' => $extPath . 'Classes/Domain/Model/Element/RadioGroupElement.php',
	'typo3\\cms\\form\\domain\\model\\json\\checkboxgroupjsonelement' => $extPath . 'Classes/Domain/Model/Json/CheckboxGroupJsonElement.php',
	'typo3\\cms\\form\\domain\\model\\json\\radiogroupjsonelement' => $extPath . 'Classes/Domain/Model/Json/RadioGroupJsonElement.php',
	'typo3\\cms\\form\\filter\\regexpfilter' => $extPath . 'Classes/Filter/RegExpFilter.php',
	'typo3\\cms\\form\\filter\\stripnewlinesfilter' => $extPath . 'Classes/Filter/StripNewLinesFilter.php',
	'typo3\\cms\\form\\filter\\titlecasefilter' => $extPath . 'Classes/Filter/TitleCaseFilter.php',
	'typo3\\cms\\form\\filter\\uppercasefilter' => $extPath . 'Classes/Filter/UpperCaseFilter.php',
	'typo3\\cms\\form\\filter\\removexssfilter' => $extPath . 'Classes/Filter/RemoveXssFilter.php',
	'typo3\\cms\\form\\validation\\fileallowedtypesvalidator' => $extPath . 'Classes/Validation/FileAllowedTypesValidator.php',
	'typo3\\cms\\form\\validation\\filemaximumsizevalidator' => $extPath . 'Classes/Validation/FileMaximumSizeValidator.php',
	'typo3\\cms\\form\\validation\\fileminimumsizevalidator' => $extPath . 'Classes/Validation/FileMinimumSizeValidator.php',
	'typo3\\cms\\form\\validation\\greaterthanvalidator' => $extPath . 'Classes/Validation/GreaterThanValidator.php',
	'typo3\\cms\\form\\validation\\inarrayvalidator' => $extPath . 'Classes/Validation/InArrayValidator.php',
	'typo3\\cms\\form\\validation\\regexpvalidator' => $extPath . 'Classes/Validation/RegExpValidator.php',
	'typo3\\cms\\form\\view\\confirmation\\element\\checkboxgroupelementview' => $extPath . 'Classes/View/Confirmation/Element/CheckboxGroupElementView.php',
	'typo3\\cms\\form\\view\\confirmation\\element\\radiogroupelementview' => $extPath . 'Classes/View/Confirmation/Element/RadioGroupElementView.php',
	'typo3\\cms\\form\\view\\form\\element\\checkboxgroupelementview' => $extPath . 'Classes/View/Form/Element/CheckboxGroupElementView.php',
	'typo3\\cms\\form\\view\\form\\element\\radiogroupelementview' => $extPath . 'Classes/View/Form/Element/RadioGroupElementView.php',
	'typo3\\cms\\form\\view\\mail\\html\\element\\checkboxgroupelementview' => $extPath . 'Classes/View/Mail/Html/Element/CheckboxGroupElementView.php',
	'typo3\\cms\\form\\view\\mail\\html\\element\\radiogroupelementview' => $extPath . 'Classes/View/Mail/Html/Element/RadioGroupElementView.php',
	'typo3\\cms\\form\\view\\mail\\plain\\element\\checkboxgroupelementview' => $extPath . 'Classes/View/Mail/Plain/Element/CheckboxGroupElementView.php',
	'typo3\\cms\\form\\view\\mail\\plain\\element\\radiogroupelementview' => $extPath . 'Classes/View/Mail/Plain/Element/RadioGroupElementView.php',
);
