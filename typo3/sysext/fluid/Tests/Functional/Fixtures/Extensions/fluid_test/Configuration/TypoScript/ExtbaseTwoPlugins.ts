page.10 = USER
page.10 {
    userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
    extensionName = FluidTest
    pluginName = Pi
    view < lib.viewConfig
}

page.20 < page.10
page.20.view.templateRootPaths >
page.20.view.templateRootPaths.1 = EXT:fluid_test/Resources/Private/Override/Templates/


