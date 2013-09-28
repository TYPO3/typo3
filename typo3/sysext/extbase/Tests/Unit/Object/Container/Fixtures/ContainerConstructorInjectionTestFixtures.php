<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures;


class SimpleTypeConstructorArgument {

	/**
	 * @var boolean
	 */
	public $foo;

	/**
	 * @param boolean $foo
	 */
	public function __construct($foo = FALSE) {
		$this->foo = $foo;
	}
}


class ArgumentTestClass {

}


class MandatoryConstructorArgument {

	/**
	 * @var ArgumentTestClass
	 */
	public $argumentTestClass;

	public $allArguments;

	/**
	 * @param ArgumentTestClass $argumentTestClass
	 */
	public function __construct(ArgumentTestClass $argumentTestClass) {
		$this->argumentTestClass = $argumentTestClass;
		$this->allArguments = func_get_args();
	}
}


class OptionalConstructorArgument {

	/**
	 * @var ArgumentTestClass
	 */
	public $argumentTestClass;

	/**
	 * @param ArgumentTestClass $argumentTestClass
	 */
	public function __construct(ArgumentTestClass $argumentTestClass = NULL) {
		$this->argumentTestClass = $argumentTestClass;
	}
}


class MandatoryConstructorArgumentTwo {

	/**
	 * @var ArgumentTestClass
	 */
	public $argumentTestClass;

	/**
	 * @var ArgumentTestClass
	 */
	public $argumentTestClassTwo;

	/**
	 * @param ArgumentTestClass $argumentTestClass
	 * @param ArgumentTestClass $argumentTestClassTwo
	 */
	public function __construct(ArgumentTestClass $argumentTestClass, ArgumentTestClass $argumentTestClassTwo) {
		$this->argumentTestClass = $argumentTestClass;
		$this->argumentTestClassTwo = $argumentTestClassTwo;
	}
}


class TwoConstructorArgumentsSecondOptional {

	/**
	 * @var ArgumentTestClass
	 */
	public $argumentTestClass;

	/**
	 * @var ArgumentTestClass
	 */
	public $argumentTestClassTwo;

	/**
	 * @param ArgumentTestClass $argumentTestClass
	 * @param ArgumentTestClass $argumentTestClassTwo
	 */
	public function __construct(ArgumentTestClass $argumentTestClass, ArgumentTestClass $argumentTestClassTwo = NULL) {
		$this->argumentTestClass = $argumentTestClass;
		$this->argumentTestClassTwo = $argumentTestClassTwo;
	}
}


class TwoConstructorArgumentsFirstOptional {

	/**
	 * @var ArgumentTestClass
	 */
	public $argumentTestClass;

	/**
	 * @var ArgumentTestClass
	 */
	public $argumentTestClassTwo;

	/**
	 * This can not be handled correctly at the moment since the underlying
	 * reflection API of PHP marks the first parameter as required!
	 *
	 * @param ArgumentTestClass $argumentTestClass
	 * @param ArgumentTestClass $argumentTestClassTwo
	 */
	public function __construct(ArgumentTestClass $argumentTestClass = NULL, ArgumentTestClass $argumentTestClassTwo) {
		$this->argumentTestClass = $argumentTestClass;
		$this->argumentTestClassTwo = $argumentTestClassTwo;
	}
}


class TwoConstructorArgumentsBothOptional {

	/**
	 * @var ArgumentTestClass
	 */
	public $argumentTestClass;

	/**
	 * @var ArgumentTestClass
	 */
	public $argumentTestClassTwo;

	/**
	 * @param ArgumentTestClass $argumentTestClass
	 * @param ArgumentTestClass $argumentTestClassTwo
	 */
	public function __construct(ArgumentTestClass $argumentTestClass = NULL, ArgumentTestClass $argumentTestClassTwo = NULL) {
		$this->argumentTestClass = $argumentTestClass;
		$this->argumentTestClassTwo = $argumentTestClassTwo;
	}
}
