<?php



abstract class Tx_Core_Tests_Unit_Core_Fixtures_LegacyClassFixture {
	public function foo(t3lib_div $foo) {
	}
	abstract public function bar(t3lib_div $bar);
	public function nothing() {
	}
	protected function stillNothing(Tx_Core_Tests_Unit_Core_Fixtures_LegacyClassFixture $nothing) {
	}
}