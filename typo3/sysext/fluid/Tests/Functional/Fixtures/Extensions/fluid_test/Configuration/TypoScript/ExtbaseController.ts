page.10 = USER
page.10 {
    userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
    extensionName = FluidTest
    pluginName = Pi
    view < lib.viewConfig
}

[request.getQueryParams()['widgetConfig'] == 'new' || request.getParsedBody()['widgetConfig'] == 'new']
    page.10.view.widget.TYPO3\CMS\Fluid\ViewHelpers\Widget\PaginateViewHelper.templateRootPath >
[end]

[request.getQueryParams()['widgetConfig'] == 'old' || request.getParsedBody()['widgetConfig'] == 'old']
    page.10.view.widget.TYPO3\CMS\Fluid\ViewHelpers\Widget\PaginateViewHelper.templateRootPaths >
[end]
