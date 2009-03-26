<?php
require_once(t3lib_extMgm::extPath('extbase').'class.tx_extbase_dispatcher.php');

class user_fluid_test {
	public function test() {
		new Tx_ExtBase_Dispatcher();

		$objectFactory = new Tx_Fluid_Compatibility_ObjectFactory();

		$templateParser = Tx_Fluid_Compatibility_TemplateParserBuilder::build();
		$results = $templateParser->parse('{namespace f3=Tx_Fluid_ViewHelpers} Hallo {name}, <f3:for each="{posts}" as="post">{post}<br></f3:for>');
		$rootNode = $results->getRootNode();

		$variableContainer = $objectFactory->create('Tx_Fluid_Core_VariableContainer', array('name' => 'Bastian', 'posts' => array('My Posting 1', 'FGluid is cool', 'c')));

		return $rootNode->evaluate($variableContainer);
	}
}
?>