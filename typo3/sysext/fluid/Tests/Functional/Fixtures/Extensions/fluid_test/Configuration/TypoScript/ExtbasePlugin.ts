page.10 = USER
page.10 {
    userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
    extensionName = FluidTest
    pluginName = Pi
}

[request.getQueryParams()['pluginConfig'] == 'extensionKey' || request.getParsedBody()['pluginConfig'] == 'extensionKey']
    plugin.tx_fluidtest.view < lib.viewConfig
[end]

[request.getQueryParams()['pluginConfig'] == 'pluginName' || request.getParsedBody()['pluginConfig'] == 'pluginName']
    plugin.tx_fluidtest_pi.view < lib.viewConfig
[end]

[request.getQueryParams()['pluginConfig'] == 'incomplete' || request.getParsedBody()['pluginConfig'] == 'incomplete']
    plugin.tx_fluidtest_pi.view < lib.viewConfig
    plugin.tx_fluidtest_pi.view.partialRootPaths >
    plugin.tx_fluidtest_pi.view.layoutRootPaths >
[end]
