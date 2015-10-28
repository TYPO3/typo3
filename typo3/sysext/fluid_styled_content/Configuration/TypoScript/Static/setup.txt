<INCLUDE_TYPOSCRIPT: source="FILE:./Setup/lib.parseFunc.ts">
<INCLUDE_TYPOSCRIPT: source="FILE:./Setup/lib.fluidContent.ts">
<INCLUDE_TYPOSCRIPT: source="FILE:./Setup/lib.stdheader.ts">
<INCLUDE_TYPOSCRIPT: source="FILE:./Setup/styles.content.get.ts">

tt_content = CASE
tt_content {
	key {
		field = CType
	}
	stdWrap {
		# Setup the edit panel for all content elements
		editPanel = 1
		editPanel {
			allow = move, new, edit, hide, delete
			label = %s
			onlyCurrentPid = 1
			previewBorder = 1
			edit.displayRecord = 1
		}
	}
	bullets =< lib.fluidContent
	bullets {
		templateName = Bullets
		dataProcessing {
			10 = TYPO3\CMS\Frontend\DataProcessing\SplitProcessor
			10 {
				if {
					value = 2
					isLessThan.field = bullets_type
				}
				fieldName = bodytext
				removeEmptyEntries = 1
				as = bullets
			}
			20 = TYPO3\CMS\Frontend\DataProcessing\CommaSeparatedValueProcessor
			20 {
				fieldName = bodytext
				if {
					value = 2
					equals.field = bullets_type
				}
				fieldDelimiter = |
				as = bullets
			}
		}
		stdWrap {
			# Setup the edit icon for content element "bullets"
			editIcons = tt_content: header [header_layout], bodytext [bullets_type]
			editIcons {
				beforeLastTag = 1
				iconTitle.data = LLL:EXT:fluid_styled_content/Resources/Private/Language/FrontendEditing.xlf:editIcon.bullets
			}
		}
	}
	div =< lib.fluidContent
	div {
		templateName = Div
	}
	header =< lib.fluidContent
	header {
		templateName = Header
		stdWrap {
			# Setup the edit icon for content element "header"
			editIcons = tt_content: header [header_layout|header_link], subheader, date
			editIcons {
				beforeLastTag = 1
				iconTitle.data = LLL:EXT:fluid_styled_content/Resources/Private/Language/FrontendEditing.xlf:editIcon.header
			}
		}
	}
	html =< lib.fluidContent
	html {
		templateName = Html
		stdWrap {
			# Setup the edit icon for content element "html"
			editIcons = tt_content: bodytext
			editIcons {
				beforeLastTag = 1
				iconTitle.data = LLL:EXT:fluid_styled_content/Resources/Private/Language/FrontendEditing.xlf:editIcon.html
			}
		}
	}
	list =< lib.fluidContent
	list {
		templateName = List
		stdWrap {
			# Setup the edit icon for content element "list"
			editIcons = tt_content: header [header_layout], list_type, layout, select_key, pages [recursive]
			editIcons {
				iconTitle.data = LLL:EXT:fluid_styled_content/Resources/Private/Language/FrontendEditing.xlf:editIcon.list
			}
		}
	}
	menu =< lib.fluidContent
	menu {
		templateName = Menu
		dataProcessing {
			10 = TYPO3\CMS\Frontend\DataProcessing\SplitProcessor
			10 {
				if.isTrue.field = pages
				fieldName = pages
				delimiter = ,
				removeEmptyEntries = 1
				filterIntegers = 1
				filterUnique = 1
				as = pageUids
			}
			20 < .10
			20 {
				if.isTrue.field = selected_categories
				fieldName = selected_categories
				as = categoryUids
			}
		}
		stdWrap {
			# Setup the edit icon for content element "menu"
			editIcons = tt_content: header [header_layout], menu_type, pages
			editIcons {
				iconTitle.data = LLL:EXT:fluid_styled_content/Resources/Private/Language/FrontendEditing.xlf:editIcon.menu
			}
		}
	}
	shortcut =< lib.fluidContent
	shortcut {
		templateName = Shortcut

		# Keep this, since the "conf" option can be used
		variables.shortcuts = RECORDS
		variables.shortcuts {
			source.field = records
			tables = {$styles.content.shortcut.tables}
		}

		stdWrap {
			# Setup the edit icon for content element "shortcut"
			editIcons = tt_content: header [header_layout], records
			editIcons {
				iconTitle.data = LLL:EXT:fluid_styled_content/Resources/Private/Language/FrontendEditing.xlf:editIcon.shortcut
			}
		}
	}
	table =< lib.fluidContent
	table {
		templateName = Table
		dataProcessing {
			10 = TYPO3\CMS\Frontend\DataProcessing\CommaSeparatedValueProcessor
			10 {
				fieldName = bodytext
				fieldDelimiter.char.cObject = TEXT
				fieldDelimiter.char.cObject {
					field = table_delimiter
				}
				fieldEnclosure.char.cObject = TEXT
				fieldEnclosure.char.cObject {
					field = table_enclosure
				}
				maximumColumns.field = cols
				as = table
			}
		}
		stdWrap {
			# Setup the edit icon for content element "table"
			editIcons = tt_content: header [header_layout], bodytext, [table_caption|cols|table_header_position|table_tfoot]
			editIcons {
				beforeLastTag = 1
				iconTitle.data = LLL:EXT:fluid_styled_content/Resources/Private/Language/FrontendEditing.xlf:editIcon.table
			}
		}
	}
	textmedia =< lib.fluidContent
	textmedia {
		templateName = Textmedia
		dataProcessing {
			10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
			10 {
				references.fieldName = assets
			}
			20 = TYPO3\CMS\Frontend\DataProcessing\GalleryProcessor
			20 {
				maxGalleryWidth = {$styles.content.textmedia.maxW}
				maxGalleryWidthInText = {$styles.content.textmedia.maxWInText}
				columnSpacing = {$styles.content.textmedia.columnSpacing}
				borderWidth = {$styles.content.textmedia.borderWidth}
				borderPadding = {$styles.content.textmedia.borderPadding}
			}
		}
		stdWrap {
			# Setup the edit icon for content element "textmedia"
			editIcons = tt_content: header [header_layout], bodytext, assets [imageorient|imagewidth|imageheight], [imagecols|imageborder], image_zoom
			editIcons {
				iconTitle.data = LLL:EXT:fluid_styled_content/Resources/Private/Language/FrontendEditing.xlf:editIcon.textmedia
			}
		}
	}
	uploads =< lib.fluidContent
	uploads {
		templateName = Uploads
		dataProcessing {
			10 = TYPO3\CMS\Frontend\DataProcessing\FilesProcessor
			10 {
				references.fieldName = media
				collections.field = file_collections
				sorting.field = filelink_sorting
			}
		}
		stdWrap {
			# Setup the edit icon for content element "uploads"
			editIcons = tt_content: header [header_layout], media, file_collections, filelink_sorting, [filelink_size|uploads_description|uploads_type]
			editIcons {
				iconTitle.data = LLL:EXT:fluid_styled_content/Resources/Private/Language/FrontendEditing.xlf:editIcon.uploads
			}
		}
	}

	# The "default" content element, which will be called when no rendering definition can be found
	default =< lib.fluidContent
}
