config {
    no_cache = 1
    debug = 0
    admPanel = 0
    disableAllHeaderCode = 1
    sendCacheHeaders = 0
    absRefPrefix = /
    contentObjectExceptionHandler = 0
    intTarget = _blank
}

page = PAGE
page.config.no_cache = 0
page.config.contentObjectExceptionHandler = 0

lib.viewConfig {
    templateRootPaths {
        1 = EXT:fluid_test/Resources/Private/Base/Templates/
    }

    partialRootPaths {
        1 = EXT:fluid_test/Resources/Private/Base/Partials/
    }

    layoutRootPaths {
        1 = EXT:fluid_test/Resources/Private/Base/Layouts/
    }

    widget.TYPO3\CMS\Fluid\ViewHelpers\Widget\PaginateViewHelper {
        templateRootPath = EXT:fluid_test/Resources/Private/Base/Templates/
        templateRootPaths {
            1 = EXT:fluid_test/Resources/Private/Base/Templates/
        }
    }
}

[request.getQueryParams()['override'] == 'overrideAll' || request.getParsedBody()['override'] == 'overrideAll']
    lib.viewConfig {
        templateRootPaths {
            10 = EXT:fluid_test/Resources/Private/Override/Templates/
        }

        partialRootPaths {
            10 = EXT:fluid_test/Resources/Private/Override/Partials/
        }

        layoutRootPaths {
            10 = EXT:fluid_test/Resources/Private/Override/Layouts/
        }
    }
[end]

[request.getQueryParams()['override'] == 'templateOverride' || request.getParsedBody()['override'] == 'templateOverride']
    lib.viewConfig {
        templateRootPaths {
            15 = EXT:fluid_test/Resources/Private/TemplateOverride/Templates/
            10 = EXT:fluid_test/Resources/Private/Override/Templates/
        }
    }
[end]

[request.getQueryParams()['override'] == 'templateOverrideManual' || request.getParsedBody()['override'] == 'templateOverrideManual']
    lib.viewConfig {
        templateRootPaths {
            10 = EXT:fluid_test/Resources/Private/Override/Templates/
            bla = EXT:fluid_test/Resources/Private/TemplateOverride/Templates/
        }
    }
[end]

[request.getQueryParams()['override'] == 'partialOverride' || request.getParsedBody()['override'] == 'partialOverride']
    lib.viewConfig {
        partialRootPaths {
            15 = EXT:fluid_test/Resources/Private/PartialOverride/Partials/
            10 = EXT:fluid_test/Resources/Private/Override/Partials/
        }
    }
[end]

[request.getQueryParams()['override'] == 'partialOverrideManual' || request.getParsedBody()['override'] == 'partialOverrideManual']
    lib.viewConfig {
        partialRootPaths {
            10 = EXT:fluid_test/Resources/Private/Override/Partials/
            bla = EXT:fluid_test/Resources/Private/PartialOverride/Partials/
        }
    }
[end]

[request.getQueryParams()['override'] == 'layoutOverride' || request.getParsedBody()['override'] == 'layoutOverride']
    lib.viewConfig {
        layoutRootPaths {
            15 = EXT:fluid_test/Resources/Private/LayoutOverride/Layouts/
            10 = EXT:fluid_test/Resources/Private/Override/Layouts/
        }
    }
[end]

[request.getQueryParams()['override'] == 'layoutOverrideManual' || request.getParsedBody()['override'] == 'layoutOverrideManual']
    lib.viewConfig {
        layoutRootPaths {
            10 = EXT:fluid_test/Resources/Private/Override/Layouts/
            bla = EXT:fluid_test/Resources/Private/LayoutOverride/Layouts/
        }
    }
[end]

<INCLUDE_TYPOSCRIPT: source="FILE: ./FluidTemplateContentObject.ts" condition="[request.getQueryParams()['mode'] == 'fluidTemplate' || request.getParsedBody()['mode'] == 'fluidTemplate']">
<INCLUDE_TYPOSCRIPT: source="FILE: ./ExtbasePlugin.ts" condition="[request.getQueryParams()['mode'] == 'plugin' || request.getParsedBody()['mode'] == 'plugin']">
<INCLUDE_TYPOSCRIPT: source="FILE: ./ExtbaseController.ts" condition="[request.getQueryParams()['mode'] == 'controller' || request.getParsedBody()['mode'] == 'controller']">
<INCLUDE_TYPOSCRIPT: source="FILE: ./ExtbaseTwoPlugins.ts" condition="[request.getQueryParams()['mode'] == '2plugins' || request.getParsedBody()['mode'] == '2plugins']">
