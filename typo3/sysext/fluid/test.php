<?php
require_once(t3lib_extMgm::extPath('extbase').'class.tx_extbase_dispatcher.php');

class user_fluid_test {
	public function test() {
		new Tx_ExtBase_Dispatcher();

		$templateParser = Tx_Fluid_Compatibility_TemplateParserBuilder::build();
		$templateParser->parse('{namespace f3=Tx_Fluid_ViewHelpers}Hallo');
		return "HU";
	}
}
?>