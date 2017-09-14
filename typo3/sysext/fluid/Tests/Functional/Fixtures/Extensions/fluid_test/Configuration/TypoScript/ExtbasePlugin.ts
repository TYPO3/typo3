page.10 = USER
page.10 {
    userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
    extensionName = FluidTest
    pluginName = Pi
    vendorName = TYPO3Fluid
}

[globalVar = GP:pluginConfig = extensionKey]
    plugin.tx_fluidtest.view < lib.viewConfig
[end]

[globalVar = GP:pluginConfig = pluginName]
    plugin.tx_fluidtest_pi.view < lib.viewConfig
[end]

[globalVar = GP:pluginConfig = incomplete]
    plugin.tx_fluidtest_pi.view < lib.viewConfig
    plugin.tx_fluidtest_pi.view.partialRootPaths >
    plugin.tx_fluidtest_pi.view.layoutRootPaths >
[end]
