# Default configuration for content elements which are using FLUIDTEMPLATE directly
lib.fluidContent >
lib.fluidContent = FLUIDTEMPLATE
lib.fluidContent {
	templateName = Default
	templateRootPaths {
		10 = {$styles.templates.templateRootPath}
	}
	partialRootPaths {
		10 = {$styles.templates.partialRootPath}
	}
	layoutRootPaths {
		10 = {$styles.templates.layoutRootPath}
	}
	settings {
		defaultHeaderType = {$styles.content.defaultHeaderType}

		media {
			gallery {
				columnSpacing = {$styles.content.textmedia.colSpace}
				maximumImageWidth = {$styles.content.textmedia.maxW}
				maximumImageWidthInText = {$styles.content.textmedia.maxWInText}
				borderWidth = {$styles.content.textmedia.borderThick}
				borderPadding = {$styles.content.textmedia.borderSpace}
			}
			popup {
				bodyTag = <body style="margin:0; background:#fff;">
				wrap = <a href="javascript:close();"> | </a>
				width = {$styles.content.textmedia.linkWrap.width}
				height = {$styles.content.textmedia.linkWrap.height}
				effects = {$styles.content.textmedia.linkWrap.effects}

				JSwindow = 1
				JSwindow {
					newWindow = {$styles.content.textmedia.linkWrap.newWindow}
					if.isFalse = {$styles.content.textmedia.linkWrap.lightboxEnabled}
				}

				directImageLink = {$styles.content.textmedia.linkWrap.lightboxEnabled}

				linkParams.ATagParams.dataWrap =  class="{$styles.content.textmedia.linkWrap.lightboxCssClass}" rel="{$styles.content.textmedia.linkWrap.lightboxRelAttribute}"
			}
		}
	}
}