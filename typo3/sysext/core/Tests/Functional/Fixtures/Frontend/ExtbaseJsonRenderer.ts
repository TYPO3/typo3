<INCLUDE_TYPOSCRIPT: source="FILE:EXT:irre_tutorial/Configuration/TypoScript/setup.txt">

page {
	20 = COA
	20 {
		10 = USER
		10 {
			userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
			extensionName = IrreTutorial
			pluginName = Irre
			vendorName = OliverHader
		}
		stdWrap.postUserFunc = TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\Renderer->parseValues
		stdWrap.postUserFunc.as = Extbase
	}
}