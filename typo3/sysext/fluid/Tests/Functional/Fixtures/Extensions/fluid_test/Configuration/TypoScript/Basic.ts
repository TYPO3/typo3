config {
    no_cache = 1
    debug = 0
    xhtml_cleaning = 0
    admPanel = 0
    disableAllHeaderCode = 1
    sendCacheHeaders = 0
    sys_language_uid = 0
    sys_language_mode = ignore
    sys_language_overlay = 1
    absRefPrefix = /
    linkVars = L
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

[globalVar = GP:override = overrideAll]
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

[globalVar = GP:override = templateOverride]
    lib.viewConfig {
        templateRootPaths {
            15 = EXT:fluid_test/Resources/Private/TemplateOverride/Templates/
            10 = EXT:fluid_test/Resources/Private/Override/Templates/
        }
    }
[end]

[globalVar = GP:override = templateOverrideManual]
    lib.viewConfig {
        templateRootPaths {
            10 = EXT:fluid_test/Resources/Private/Override/Templates/
            bla = EXT:fluid_test/Resources/Private/TemplateOverride/Templates/
        }
    }
[end]

[globalVar = GP:override = partialOverride]
    lib.viewConfig {
        partialRootPaths {
            15 = EXT:fluid_test/Resources/Private/PartialOverride/Partials/
            10 = EXT:fluid_test/Resources/Private/Override/Partials/
        }
    }
[end]

[globalVar = GP:override = partialOverrideManual]
    lib.viewConfig {
        partialRootPaths {
            10 = EXT:fluid_test/Resources/Private/Override/Partials/
            bla = EXT:fluid_test/Resources/Private/PartialOverride/Partials/
        }
    }
[end]

[globalVar = GP:override = layoutOverride]
    lib.viewConfig {
        layoutRootPaths {
            15 = EXT:fluid_test/Resources/Private/LayoutOverride/Layouts/
            10 = EXT:fluid_test/Resources/Private/Override/Layouts/
        }
    }
[end]

[globalVar = GP:override = layoutOverrideManual]
    lib.viewConfig {
        layoutRootPaths {
            10 = EXT:fluid_test/Resources/Private/Override/Layouts/
            bla = EXT:fluid_test/Resources/Private/LayoutOverride/Layouts/
        }
    }
[end]

<INCLUDE_TYPOSCRIPT: source="FILE: ./FluidTemplateContentObject.ts" condition="[globalVar = GP:mode = fluidTemplate]">
<INCLUDE_TYPOSCRIPT: source="FILE: ./ExtbasePlugin.ts" condition="[globalVar = GP:mode = plugin]">
<INCLUDE_TYPOSCRIPT: source="FILE: ./ExtbaseController.ts" condition="[globalVar = GP:mode = controller]">
<INCLUDE_TYPOSCRIPT: source="FILE: ./ExtbaseTwoPlugins.ts" condition="[globalVar = GP:mode = 2plugins]">
