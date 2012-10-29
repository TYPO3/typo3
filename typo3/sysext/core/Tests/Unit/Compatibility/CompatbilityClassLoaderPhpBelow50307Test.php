<?php
namespace TYPO3\CMS\Core\Tests\Unit\Compatibility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Helmut Hummel <helmut.hummel@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for TYPO3\CMS\Core\Compatibility\CompatbilityClassLoaderPhpBelow50307
 *
 */
class CompatbilityClassLoaderPhpBelow50307Test extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Class name of the fixture class
	 *
	 * @var string
	 */
	protected $testClassName = 'Tx_Core_Tests_Unit_Core_Fixtures_LegacyClassFixture';

	/**
	 * Fixture class template
	 *
	 * @var string
	 */
	protected $classTemplate = 'abstract class Tx_Core_Tests_Unit_Core_Fixtures_LegacyClassFixture {
		%s
	}';

	/**
	 * Fixture class code
	 *
	 * @var string
	 */
	public static $classCode = '';

	/**
	 * @var \TYPO3\CMS\Core\Compatibility\CompatbilityClassLoaderPhpBelow50307|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $fixture;

	/**
	 * @return string
	 */
	protected function getCompatibilityClassLoaderMockClass() {
		$className = uniqid('CompatbilityClassLoaderPhpBelow50307Mock');

		$mockClass = 'if (!class_exists(\'ClassPathHasBeenRequired\')) { class ClassPathHasBeenRequired extends Exception {} }' . LF .
			'class ' . $className . ' extends \\TYPO3\\CMS\\Core\\Compatibility\\CompatbilityClassLoaderPhpBelow50307 {' . LF .
				'protected static function requireClassFile($classPath) {' . LF .
					'throw new ClassPathHasBeenRequired(\'Class path \' . $classPath);' . LF .
				'}' . LF .
				'static public function getClassFileContent() {' . LF .
				'	return TYPO3\\CMS\\Core\\Tests\Unit\Compatibility\\CompatbilityClassLoaderPhpBelow50307Test::$classCode;' . LF .
				'}' . LF .
				'static public function rewriteMethodTypeHintsFromClassPath($classPath) {' . LF .
				'	return parent::rewriteMethodTypeHintsFromClassPath($classPath);' . LF .
				'}' . LF .
			'}';
		eval($mockClass);
		return $className;
	}

	/**
	 * @test
	 * @expectedException \ClassPathHasBeenRequired
	 */
	public function coreClassesAreRequiredImmediately() {
		$classPath = '/dummy/path';
		$className = 'TYPO3\\CMS\\Core\\Utility\\GeneralUtility';

		$mockClassLoaderClass = $this->getCompatibilityClassLoaderMockClass();
		$mockClassLoaderClass::requireClassFileOnce($classPath, $className);
	}

	/**
	 * @test
	 * @expectedException \ClassPathHasBeenRequired
	 */
	public function thirdPartyClassesAreRequiredImmediately() {
		$classPath = '/dummy/path';
		$className = 'SwiftMailer';

		$mockClassLoaderClass = $this->getCompatibilityClassLoaderMockClass();
		$mockClassLoaderClass::requireClassFileOnce($classPath, $className);
	}

	/**
	 * @test
	 * @expectedException \ClassPathHasBeenRequired
	 */
	public function nameSpacedExtensionClassesAreRequiredImmediately() {
		$classPath = '/dummy/path';
		$className = 'Vendor\\CoolExtension\\Service\\CoolService';

		$mockClassLoaderClass = $this->getCompatibilityClassLoaderMockClass();
		$mockClassLoaderClass::requireClassFileOnce($classPath, $className);
	}

	/**
	 * @test
	 */
	public function classCacheOnlyContainsRequireOfOriginalClassIfNothingHasBeenRewritten() {
		$classPath = '/dummy/path';
		self::$classCode = sprintf($this->classTemplate,
			'	/**
	 *
	 */
	public function nothing() {
	}
		');

		$mockClassLoaderClass = $this->getCompatibilityClassLoaderMockClass();
		$rewrittenContent = $mockClassLoaderClass::rewriteMethodTypeHintsFromClassPath($classPath);
		$this->assertSame('require_once \'' . $classPath . '\';', $rewrittenContent);
	}

	/**
	 * @test
	 */
	public function typeHintInOneLineAbstractFunctionIsCorrectlyRewritten() {
		$classPath = '/dummy/path';
		self::$classCode = sprintf($this->classTemplate,
			'		/**
	 * @abstract
	 * @param t3lib_div $bar
	 * @return mixed
	 */
	abstract public function bar(t3lib_div $bar);
');

		$mockClassLoaderClass = $this->getCompatibilityClassLoaderMockClass();
		$rewrittenContent = $mockClassLoaderClass::rewriteMethodTypeHintsFromClassPath($classPath);
		$this->assertContains('abstract public function bar(\TYPO3\CMS\Core\Utility\GeneralUtility $bar);', $rewrittenContent);
	}

	/**
	 * @test
	 */
	public function typeHintInOneLineFunctionWithOneParameterIsCorrectlyRewritten() {
		$classPath = '/dummy/path';
		self::$classCode = sprintf($this->classTemplate,
			'	/**
	 * @param t3lib_div $foo
	 */
	public function foo(t3lib_div $foo) {
		// this is only a dummy function
		if ($foo instanceof t3lib_div) {
			return FALSE;
		}
	}
');

		$mockClassLoaderClass = $this->getCompatibilityClassLoaderMockClass();
		$rewrittenContent = $mockClassLoaderClass::rewriteMethodTypeHintsFromClassPath($classPath);
		$this->assertContains('// this is only a dummy function' . LF . TAB . TAB . 'if', $rewrittenContent, 'Comment not touched, newline after function ignored');
		$this->assertContains('public function foo(\TYPO3\CMS\Core\Utility\GeneralUtility $foo) {', $rewrittenContent);
	}

	/**
	 * @test
	 */
	public function typeHintInTwoLineFunctionWithTwoParametersIsCorrectlyRewritten() {
		$classPath = '/dummy/path';
		self::$classCode = sprintf($this->classTemplate,
			'	/**
	 * @param t3lib_div $foo
	 * @param $baz
	 */
	public function	 baz(t3lib_div $foo,
						$baz) {
	}
');

		$mockClassLoaderClass = $this->getCompatibilityClassLoaderMockClass();
		$rewrittenContent = $mockClassLoaderClass::rewriteMethodTypeHintsFromClassPath($classPath);
		$this->assertContains('public function baz(\TYPO3\CMS\Core\Utility\GeneralUtility $foo, $baz) {', $rewrittenContent);
	}

	/**
	 * @test
	 */
	public function typeHintInTwoLineFunctionWithTwoParametersWhileOneHavingTypehintNotInAliasMapIsCorrectlyRewritten() {
		$classPath = '/dummy/path';
		self::$classCode = sprintf($this->classTemplate,
			'	/**
	 * @param Tx_Extbase_Persistence_QueryInterface $query
	 * @param Tx_News_Domain_Model_DemandInterface $demand
	 */
	abstract protected function createConstraintsFromDemand(Tx_Extbase_Persistence_QueryInterface $query,
												   Tx_News_Domain_Model_DemandInterface $demand);
');

		$mockClassLoaderClass = $this->getCompatibilityClassLoaderMockClass();
		$rewrittenContent = $mockClassLoaderClass::rewriteMethodTypeHintsFromClassPath($classPath);
		$this->assertContains('protected function createConstraintsFromDemand(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query, Tx_News_Domain_Model_DemandInterface $demand);', $rewrittenContent, 'Multi line abstract and second parameter with own typehint not in aliasmap');
	}

	/**
	 * @test
	 */
	public function functionsWithoutParametersOrTypeHintsNotInAliasMapRemainUntouchedEvenWhenOtherTypeHintsAreRewritten() {
		$classPath = '/dummy/path';
		self::$classCode = sprintf($this->classTemplate,
			'	/**
	 * @param t3lib_div $foo
	 */
	public function foo(t3lib_div $foo) {
	}

	/**
	 *
	 */
	public function nothing() {
	}

	/**
	 * @param Tx_Core_Tests_Unit_Core_Fixtures_LegacyClassFixture $nothing
	 */
	protected function stillNothing(Tx_Core_Tests_Unit_Core_Fixtures_LegacyClassFixture $nothing) {
	}
');

		$mockClassLoaderClass = $this->getCompatibilityClassLoaderMockClass();
		$rewrittenContent = $mockClassLoaderClass::rewriteMethodTypeHintsFromClassPath($classPath);
		$this->assertContains('public function foo(\TYPO3\CMS\Core\Utility\GeneralUtility $foo) {', $rewrittenContent);
		$this->assertContains('public function nothing() {', $rewrittenContent, 'One line one parameter');
		$this->assertContains('protected function stillNothing(Tx_Core_Tests_Unit_Core_Fixtures_LegacyClassFixture $nothing) {', $rewrittenContent, 'One line on parameter with typehint not in aliasmap');
	}
}

?>