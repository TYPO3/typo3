config {
	no_cache = 1
	debug = 0
	xhtml_cleaning = 0
	admPanel = 0
	disableAllHeaderCode = 1
	sendCacheHeaders = 0
	sys_language_uid = 0
	additionalHeaders.10.header = Content-Type: application/json; charset=utf-8
	additionalHeaders.10.replace = 1
}

plugin.tx_blogexample {
	persistence {
		storagePid = 89
	}
}

page = PAGE
page {
	10 = COA
	10 {
		10 = USER
		10 {
			userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
			extensionName = BlogExample
			pluginName = Content
			vendorName = ExtbaseTeam
		}
		stdWrap.postUserFunc = TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Renderer->parseValues
		stdWrap.postUserFunc.as = Extbase
	}

	stdWrap.postUserFunc = TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Renderer->renderSections
}

[globalVar = GP:L = 1]
	config.sys_language_uid = 1
[end]
[globalVar = GP:L = 2]
	config.sys_language_uid = 2
[end]
[globalVar = GP:L = 3]
	config.sys_language_uid = 3
[end]
