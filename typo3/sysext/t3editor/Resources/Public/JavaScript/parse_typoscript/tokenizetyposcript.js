/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/* Tokenizer for TypoScript code
 *
 * based on tokenizejavascript.js by Marijn Haverbeke
 */

// List of "reserved" word in typoscript and a css-class
var typoscriptWords = {
  '_CSS_DEFAULT_STYLE': 'keyword',
  '_DEFAULT_PI_VARS': 'keyword',
  '_GIFBUILDER': 'keyword',
  '_LOCAL_LANG': 'keyword',
  '_offset': 'reserved',
  'absRefPrefix': 'reserved',
  'accessibility': 'reserved',
  'accessKey': 'reserved',
  'ACT': 'keyword3',
  'ACTIFSUB': 'keyword3',
  'ACTIFSUBRO': 'keyword',
  'ACTRO': 'keyword3',
  'addAttributes': 'reserved',
  'addExtUrlsAndShortCuts': 'reserved',
  'addItems': 'reserved',
  'additionalHeaders': 'reserved',
  'additionalParams': 'reserved',
  'addParams': 'reserved',
  'addQueryString': 'reserved',
  'adjustItemsH': 'reserved',
  'adjustSubItemsH': 'reserved',
  'admPanel': 'keyword2',
  'after': 'reserved',
  'afterImg': 'reserved',
  'afterImgLink': 'reserved',
  'afterImgTagParams': 'reserved',
  'afterROImg': 'reserved',
  'afterWrap': 'reserved',
  'age': 'reserved',
  'alertPopups': 'reserved',
  'align': 'reserved',
  'all': 'keyword3',
  'allow': 'reserved',
  'allowCaching': 'reserved',
  'allowedAttribs': 'reserved',
  'allowedClasses': 'reserved',
  'allowedCols': 'reserved',
  'allowedNewTables': 'reserved',
  'allowTags': 'reserved',
  'allStdWrap': 'reserved',
  'allWrap': 'reserved',
  'alt_print': 'keyword2',
  'alternativeSortingField': 'reserved',
  'alternativeTempPath': 'reserved',
  'altIcons': 'reserved',
  'altImgResource': 'reserved',
  'altLabels': 'reserved',
  'altTarget': 'reserved',
  'altText': 'reserved',
  'altUrl': 'reserved',
  'altUrl_noDefaultParams': 'reserved',
  'altWrap': 'reserved',
  'always': 'reserved',
  'alwaysActivePIDlist': 'reserved',
  'alwaysLink': 'reserved',
  'andWhere': 'reserved',
  'angle': 'reserved',
  'antiAlias': 'reserved',
  'append': 'reserved',
  'applyTotalH': 'reserved',
  'applyTotalW': 'reserved',
  'archive': 'reserved',
  'ascii': 'keyword3',
  'ATagAfterWrap': 'reserved',
  'ATagBeforeWrap': 'reserved',
  'ATagParams': 'reserved',
  'ATagTitle': 'reserved',
  'atLeast': 'keyword3',
  'atMost': 'keyword3',
  'attribute': 'reserved',
  'auth': 'keyword2',
  'autoLevels': 'reserved',
  'autonumber': 'reserved',
  'backColor': 'reserved',
  'background': 'reserved',
  'baseURL': 'reserved',
  'BE': 'keyword3',
  'be_groups': 'keyword3',
  'be_users': 'keyword3',
  'before': 'reserved',
  'beforeImg': 'reserved',
  'beforeImgLink': 'reserved',
  'beforeImgTagParams': 'reserved',
  'beforeROImg': 'reserved',
  'beforeWrap': 'reserved',
  'begin': 'reserved',
  'beLoginLinkIPList': 'reserved',
  'beLoginLinkIPList_login': 'reserved',
  'beLoginLinkIPList_logout': 'reserved',
  'bgCol': 'reserved',
  'bgImg': 'reserved',
  'blur': 'reserved',
  'bm': 'reserved',
  'bodyTag': 'reserved',
  'bodyTagAdd': 'reserved',
  'bodyTagCObject': 'reserved',
  'bodyTagMargins': 'reserved',
  'bodytext': 'reserved',
  'border': 'reserved',
  'borderCol': 'reserved',
  'borderThick': 'reserved',
  'bottomBackColor': 'reserved',
  'bottomContent': 'reserved',
  'bottomHeight': 'reserved',
  'bottomImg': 'reserved',
  'bottomImg_mask': 'reserved',
  'BOX': 'keyword3',
  'br': 'reserved',
  'browse': 'keyword3',
  'browser': 'keyword2',
  'brTag': 'reserved',
  'bullet': 'reserved',
  'bulletlist': 'reserved',
  'bullets': 'keyword3',
  'bytes': 'reserved',
  'cache': 'keyword2',
  'cache_clearAtMidnight': 'reserved',
  'cache_period': 'reserved',
  'caption': 'reserved',
  'caption_stdWrap': 'reserved',
  'captionHeader': 'reserved',
  'captionSplit': 'reserved',
  'CARRAY': 'keyword',
  'CASE': 'keyword',
  'case': 'reserved',
  'casesensitiveComp': 'reserved',
  'cellpadding': 'reserved',
  'cellspacing': 'reserved',
  'char': 'reserved',
  'charcoal': 'reserved',
  'charMapConfig': 'reserved',
  'CHECK': 'keyword2',
  'check': 'reserved',
  'class': 'reserved',
  'classesAnchor': 'reserved',
  'classesCharacter': 'reserved',
  'classesImage': 'reserved',
  'classesParagraph': 'reserved',
  'clear': 'reserved',
  'clearCache': 'reserved',
  'clearCache_disable': 'reserved',
  'clearCache_pageGrandParent': 'reserved',
  'clearCache_pageSiblingChildren': 'reserved',
  'clearCacheCmd': 'reserved',
  'clearCacheLevels': 'reserved',
  'clearCacheOfPages': 'reserved',
  'clickTitleMode': 'reserved',
  'clipboardNumberPads': 'reserved',
  'cMargins': 'reserved',
  'COA': 'keyword',
  'COA_INT': 'keyword',
  'cObj': 'keyword2',
  'COBJ_ARRAY': 'keyword',
  'cObject': 'keyword2',
  'cObjNum': 'reserved',
  'collapse': 'reserved',
  'collections': 'reserved',
  'color': 'reserved',
  'color1': 'reserved',
  'color2': 'reserved',
  'color3': 'reserved',
  'color4': 'reserved',
  'colors': 'reserved',
  'colour': 'reserved',
  'colPos_list': 'reserved',
  'colRelations': 'reserved',
  'cols': 'reserved',
  'colSpace': 'reserved',
  'COMMENT': 'keyword2',
  'comment_auto': 'reserved',
  'commentWrap': 'reserved',
  'compensateFieldWidth': 'reserved',
  'compX': 'reserved',
  'compY': 'reserved',
  'conf': 'reserved',
  'CONFIG': 'keyword',
  'config': 'keyword2',
  'CONSTANTS': 'keyword',
  'constants': 'reserved',
  'CONTENT': 'keyword',
  'content': 'keyword2',
  'content_from_pid_allowOutsideDomain': 'reserved',
  'contextMenu': 'reserved',
  'copy': 'keyword2',
  'copyLevels': 'reserved',
  'count_HMENU_MENUOBJ': 'reserved',
  'count_menuItems': 'reserved',
  'count_MENUOBJ': 'reserved',
  'create': 'reserved',
  'createFoldersInEB': 'reserved',
  'crop': 'reserved',
  'csConv': 'reserved',
  'CSS_inlineStyle': 'keyword2',
  'CType': 'keyword',
  'CUR': 'keyword3',
  'CURIFSUB': 'keyword3',
  'CURIFSUBRO': 'keyword3',
  'current': 'reserved',
  'CURRO': 'keyword3',
  'curUid': 'reserved',
  'cut': 'keyword2',
  'cWidth': 'reserved',
  'data': 'reserved',
  'dataArray': 'keyword2',
  'dataWrap': 'reserved',
  'date': 'reserved',
  'date_stdWrap': 'reserved',
  'datePrefix': 'reserved',
  'dayofmonth': 'keyword2',
  'dayofweek': 'keyword2',
  'DB': 'keyword',
  'db_list': 'keyword2',
  'debug': 'reserved',
  'debugData': 'reserved',
  'debugFunc': 'reserved',
  'debugItemConf': 'reserved',
  'debugRenumberedObject': 'reserved',
  'default': 'keyword3',
  'defaultAlign': 'reserved',
  'defaultCmd': 'reserved',
  'defaultFileUploads': 'reserved',
  'defaultHeaderType': 'reserved',
  'defaultOutput': 'reserved',
  'defaults': 'reserved',
  'defaultType': 'reserved',
  'delete': 'reserved',
  'denyTags': 'reserved',
  'depth': 'reserved',
  'DESC': 'reserved',
  'description': 'keyword3',
  'dimensions': 'reserved',
  'direction': 'reserved',
  'directory': 'keyword3',
  'directReturn': 'keyword3',
  'disableAdvanced': 'reserved',
  'disableAllHeaderCode': 'reserved',
  'disableAltText': 'reserved',
  'disableBodyTag': 'reserved',
  'disableCacheSelector': 'reserved',
  'disableCharsetHeader': 'reserved',
  'disabled': 'reserved',
  'disableDelete': 'reserved',
  'disableDocSelector': 'reserved',
  'disableHideAtCopy': 'reserved',
  'disableIconLinkToContextmenu': 'reserved',
  'disableItems': 'reserved',
  'disableNewContentElementWizard': 'reserved',
  'disableNoMatchingValueElement': 'reserved',
  'disablePageExternalUrl': 'reserved',
  'disablePrefixComment': 'reserved',
  'disablePrependAtCopy': 'reserved',
  'disableSearchBox': 'reserved',
  'disableSingleTableView': 'reserved',
  'displayContent': 'reserved',
  'displayFieldIcons': 'reserved',
  'displayIcons': 'reserved',
  'displayMessages': 'reserved',
  'displayRecord': 'reserved',
  'displayTimes': 'reserved',
  'distributeX': 'reserved',
  'distributeY': 'reserved',
  'div': 'keyword3',
  'DIV': 'reserved',
  'doctype': 'reserved',
  'doctypeSwitch': 'reserved',
  'DOCUMENT_BODY': 'keyword',
  'doktype': 'reserved',
  'doNotLinkIt': 'reserved',
  'doNotShowLink': 'reserved',
  'doNotStripHTML': 'reserved',
  'dontCheckPid': 'reserved',
  'dontLinkIfSubmenu': 'reserved',
  'dontWrapInTable': 'reserved',
  'doubleBrTag': 'reserved',
  'dWorkArea': 'reserved',
  'dynCSS': 'keyword2',
  'edge': 'reserved',
  'edit': 'keyword2',
  'edit_access': 'keyword2',
  'edit_docModuleUpload': 'reserved',
  'edit_pageheader': 'keyword2',
  'edit_RTE': 'reserved',
  'editFieldsAtATime': 'reserved',
  'editFormsOnPage': 'reserved',
  'editIcons': 'reserved',
  'editNoPopup': 'reserved',
  'EDITPANEL': 'keyword',
  'editPanel': 'reserved',
  'EFFECT': 'keyword',
  'elements': 'reserved',
  'else': 'keyword3',
  'email': 'keyword3',
  'emailMeAtLogin': 'reserved',
  'emailMess': 'reserved',
  'emboss': 'reserved',
  'enable': 'reserved',
  'encapsLines': 'reserved',
  'encapsLinesStdWrap': 'reserved',
  'encapsTagList': 'reserved',
  'end': 'keyword3',
  'entryLevel': 'reserved',
  'equalH': 'reserved',
  'equals': 'keyword3',
  'everybody': 'reserved',
  'excludeDoktypes': 'reserved',
  'excludeUidList': 'reserved',
  'expAll': 'reserved',
  'expand': 'reserved',
  'explode': 'reserved',
  'ext': 'reserved',
  'external': 'keyword3',
  'externalBlocks': 'reserved',
  'extTarget': 'reserved',
  'face': 'reserved',
  'false': 'keyword3',
  'FE': 'keyword3',
  'fe_adminLib': 'reserved',
  'fe_groups': 'keyword3',
  'fe_users': 'keyword3',
  'feadmin': 'keyword3',
  'field': 'reserved',
  'fieldName': 'reserved',
  'fieldOrder': 'reserved',
  'fieldRequired': 'reserved',
  'fields': 'reserved',
  'fieldWrap': 'reserved',
  'FILE': 'keyword',
  'file': 'reserved',
  'file1': 'reserved',
  'file2': 'reserved',
  'file3': 'reserved',
  'file4': 'reserved',
  'file5': 'reserved',
  'filelink': 'reserved',
  'filelist': 'reserved',
  'FILES': 'keyword',
  'files': 'reserved',
  'firstLabel': 'reserved',
  'firstLabelGeneral': 'reserved',
  'fixAttrib': 'reserved',
  'flip': 'reserved',
  'flop': 'reserved',
  'FLUIDTEMPLATE': 'keyword',
  'folder': 'keyword2',
  'folders': 'reserved',
  'folderTree': 'keyword2',
  'foldoutMenu': 'keyword2',
  'fontColor': 'reserved',
  'fontFile': 'reserved',
  'fontOffset': 'reserved',
  'fontSize': 'reserved',
  'fontSizeMultiplicator': 'reserved',
  'fontTag': 'reserved',
  'forceDisplayFieldIcons': 'reserved',
  'forceDisplayIcons': 'reserved',
  'forceTemplateParsing': 'reserved',
  'forceTypeValue': 'reserved',
  'FORM': 'keyword',
  'format': 'reserved',
  'FRAME': 'keyword',
  'frame': 'reserved',
  'frameReloadIfNotInFrameset': 'reserved',
  'FRAMESET': 'keyword',
  'frameSet': 'reserved',
  'ftu': 'reserved',
  'function': 'reserved',
  'Functions': 'keyword2',
  'gamma': 'reserved',
  'gapBgCol': 'reserved',
  'gapLineCol': 'reserved',
  'gapLineThickness': 'reserved',
  'gapWidth': 'reserved',
  'get': 'reserved',
  'getBorder': 'reserved',
  'getLeft': 'reserved',
  'getRight': 'reserved',
  'GIFBUILDER': 'keyword',
  'global': 'keyword',
  'globalNesting': 'reserved',
  'globalString': 'keyword',
  'globalVar': 'keyword',
  'GMENU': 'keyword',
  'GP': 'keyword',
  'gray': 'reserved',
  'group': 'reserved',
  'groupBy': 'reserved',
  'groupid': 'reserved',
  'header': 'keyword3',
  'header_layout': 'reserved',
  'headerComment': 'reserved',
  'headerData': 'reserved',
  'headerSpace': 'reserved',
  'headTag': 'reserved',
  'height': 'reserved',
  'helpText': 'reserved',
  'hidden': 'reserved',
  'hiddenFields': 'reserved',
  'hide': 'reserved',
  'hideButCreateMap': 'reserved',
  'hidePStyleItems': 'reserved',
  'hideRecords': 'reserved',
  'highColor': 'reserved',
  'history': 'reserved',
  'HMENU': 'keyword',
  'hostname': 'keyword2',
  'hour': 'keyword2',
  'HTML': 'keyword',
  'html': 'keyword3',
  'HTMLparser': 'reserved',
  'HTMLparser_tags': 'reserved',
  'htmlSpecialChars': 'reserved',
  'htmlTag_dir': 'reserved',
  'htmlTag_langKey': 'reserved',
  'htmlTag_setParams': 'reserved',
  'http': 'reserved',
  'icon': 'reserved',
  'icon_image_ext_list': 'reserved',
  'icon_link': 'reserved',
  'iconCObject': 'reserved',
  'id': 'keyword3',
  'IENV': 'keyword',
  'if': 'keyword3',
  'ifEmpty': 'keyword3',
  'IFSUB': 'keyword3',
  'IFSUBRO': 'keyword3',
  'IMAGE': 'keyword',
  'image': 'keyword3',
  'image_frames': 'reserved',
  'imageLinkWrap': 'reserved',
  'imagePath': 'reserved',
  'images': 'reserved',
  'imageWrapIfAny': 'reserved',
  'IMG_RESOURCE': 'keyword',
  'imgList': 'keyword2',
  'imgMap': 'reserved',
  'imgMapExtras': 'reserved',
  'imgMax': 'reserved',
  'IMGMENU': 'keyword',
  'IMGMENUITEM': 'keyword',
  'imgNameNotRandom': 'reserved',
  'imgNamePrefix': 'reserved',
  'imgObjNum': 'reserved',
  'imgParams': 'reserved',
  'imgPath': 'reserved',
  'imgResource': 'keyword2',
  'imgStart': 'reserved',
  'IMGTEXT': 'keyword',
  'imgText': 'keyword2',
  'import': 'reserved',
  'inBranch': 'keyword3',
  'inc': 'reserved',
  'INCLUDE_TYPOSCRIPT': 'keyword',
  'includeCSS': 'reserved',
  'includeLibrary': 'reserved',
  'includeNotInMenu': 'reserved',
  'index': 'reserved',
  'index_descrLgd': 'reserved',
  'index_enable': 'reserved',
  'index_externals': 'reserved',
  'info': 'keyword2',
  'inlineStyle2TempFile': 'reserved',
  'innerStdWrap': 'reserved',
  'innerStdWrap_all': 'reserved',
  'innerWrap': 'reserved',
  'innerWrap2': 'reserved',
  'input': 'reserved',
  'inputLevels': 'reserved',
  'insertClassesFromRTE': 'reserved',
  'insertData': 'reserved',
  'intensity': 'reserved',
  'intTarget': 'reserved',
  'intval': 'reserved',
  'invert': 'reserved',
  'IP': 'keyword2',
  'IProcFunc': 'reserved',
  'isFalse': 'keyword3',
  'isGreaterThan': 'keyword3',
  'isInList': 'keyword3',
  'isLessThan': 'keyword3',
  'isPositive': 'keyword3',
  'isTrue': 'keyword3',
  'itemArrayProcFunc': 'reserved',
  'itemH': 'reserved',
  'items': 'reserved',
  'itemsProcFunc': 'reserved',
  'iterations': 'reserved',
  'join': 'reserved',
  'JSMENU': 'keyword',
  'jsmenu': 'keyword2',
  'JSMENUITEM': 'keyword',
  'JSwindow': 'keyword2',
  'JSWindow': 'reserved',
  'JSwindow_params': 'reserved',
  'keep': 'reserved',
  'keepEntries': 'reserved',
  'keepNonMatchedTags': 'reserved',
  'key': 'reserved',
  'keyword3': 'keyword3',
  'LABEL': 'keyword2',
  'label': 'reserved',
  'labelStdWrap': 'reserved',
  'labelWrap': 'reserved',
  'lang': 'reserved',
  'language': 'keyword3',
  'language_alt': 'reserved',
  'languageField': 'reserved',
  'layout': 'keyword2',
  'left': 'reserved',
  'leftjoin': 'reserved',
  'levels': 'reserved',
  'leveltitle': 'keyword3',
  'leveluid': 'reserved',
  'lib': 'keyword2',
  'limit': 'reserved',
  'line': 'reserved',
  'lineColor': 'reserved',
  'lineThickness': 'reserved',
  'linkPrefix': 'reserved',
  'linkTitleToSelf': 'reserved',
  'linkVars': 'reserved',
  'linkWrap': 'reserved',
  'list': 'keyword3',
  'listNum': 'reserved',
  'listOnlyInSingleTableView': 'reserved',
  'LIT': 'keyword',
  'lm': 'reserved',
  'LOAD_REGISTER': 'keyword',
  'locale_all': 'reserved',
  'localNesting': 'reserved',
  'locationData': 'reserved',
  'lockFilePath': 'reserved',
  'lockToIP': 'reserved',
  'login': 'keyword3',
  'loginUser': 'keyword2',
  'longdescURL': 'reserved',
  'lowColor': 'reserved',
  'lower': 'reserved',
  'LR': 'reserved',
  'mailform': 'keyword3',
  'mailto': 'reserved',
  'main': 'reserved',
  'mainScript': 'reserved',
  'makelinks': 'reserved',
  'markerWrap': 'reserved',
  'marks': 'keyword2',
  'mask': 'reserved',
  'max': 'reserved',
  'maxAge': 'reserved',
  'maxChars': 'reserved',
  'maxH': 'reserved',
  'maxHeight': 'reserved',
  'maxItems': 'reserved',
  'maxW': 'reserved',
  'maxWidth': 'reserved',
  'maxWInText': 'reserved',
  'media': 'keyword3',
  'menu': 'keyword3',
  'menu_type': 'reserved',
  'menuHeight': 'reserved',
  'menuName': 'reserved',
  'menuOffset': 'reserved',
  'menuWidth': 'reserved',
  'message_preview': 'reserved',
  'META': 'keyword',
  'meta': 'reserved',
  'metaCharset': 'reserved',
  'method': 'reserved',
  'min': 'reserved',
  'minH': 'reserved',
  'minItems': 'reserved',
  'minute': 'keyword2',
  'minW': 'reserved',
  'mod': 'keyword3',
  'mode': 'reserved',
  'module': 'keyword2',
  'month': 'keyword2',
  'move_wizard': 'keyword2',
  'MP_defaults': 'reserved',
  'MP_disableTypolinkClosestMPvalue': 'reserved',
  'MP_mapRootPoints': 'reserved',
  'MULTIMEDIA': 'keyword',
  'multimedia': 'keyword3',
  'name': 'reserved',
  'negate': 'keyword3',
  'nesting': 'reserved',
  'neverHideAtCopy': 'reserved',
  'new': 'keyword2',
  'NEW': 'keyword3',
  'new_wizard': 'keyword2',
  'newPageWiz': 'reserved',
  'newRecordFromTable': 'reserved',
  'newWindow': 'reserved',
  'newWizards': 'reserved',
  'next': 'reserved',
  'niceText': 'reserved',
  'nicetext': 'reserved',
  'NO': 'keyword3',
  'no_cache': 'reserved',
  'no_search': 'reserved',
  'noAttrib': 'reserved',
  'noCache': 'reserved',
  'noCreateRecordsLink': 'reserved',
  'noLink': 'reserved',
  'noMatchingValue_label': 'reserved',
  'nonCachedSubst': 'reserved',
  'none': 'keyword3',
  'nonTypoTagStdWrap': 'reserved',
  'nonTypoTagUserFunc': 'reserved',
  'nonWrappedTag': 'reserved',
  'noOrderBy': 'reserved',
  'noPageTitle': 'reserved',
  'noResultObj': 'keyword2',
  'noThumbsInEB': 'reserved',
  'noThumbsInRTEimageSelect': 'reserved',
  'noTrimWrap': 'reserved',
  'noValueInsert': 'reserved',
  'numRows': 'keyword2',
  'obj': 'reserved',
  'offset': 'reserved',
  'onlineWorkspaceInfo': 'reserved',
  'onlyCurrentPid': 'reserved',
  'opacity': 'reserved',
  'options': 'keyword2',
  'orderBy': 'reserved',
  'outerWrap': 'reserved',
  'outline': 'reserved',
  'outputLevels': 'reserved',
  'override': 'reserved',
  'overrideAttribs': 'reserved',
  'overrideId': 'reserved',
  'overridePageModule': 'reserved',
  'overrideWithExtension': 'reserved',
  'PAGE': 'keyword',
  'page': 'keyword2',
  'PAGE_TARGET': 'keyword',
  'PAGE_TSCONFIG_ID': 'keyword',
  'PAGE_TSCONFIG_IDLIST': 'keyword',
  'PAGE_TSCONFIG_STR': 'keyword',
  'pageFrameObj': 'reserved',
  'pageGenScript': 'reserved',
  'pages': 'keyword3',
  'pages_language_overlay': 'keyword3',
  'pageTitleFirst': 'reserved',
  'pageTree': 'keyword2',
  'parameter': 'reserved',
  'params': 'reserved',
  'parseFunc': 'reserved',
  'parseFunc_RTE': 'keyword3',
  'parser': 'reserved',
  'password': 'reserved',
  'paste': 'keyword2',
  'path': 'reserved',
  'permissions': 'reserved',
  'perms': 'keyword2',
  'pid': 'keyword3',
  'pid_list': 'reserved',
  'pidInList': 'reserved',
  'PIDinRootline': 'keyword2',
  'PIDupinRootline': 'keyword2',
  'pixelSpaceFontSizeRef': 'reserved',
  'plaintextLib': 'reserved',
  'plainTextStdWrap': 'reserved',
  'plugin': 'keyword2',
  'postCObject': 'reserved',
  'postLineBlanks': 'reserved',
  'postLineChar': 'reserved',
  'postLineLen': 'reserved',
  'postUserFunc': 'reserved',
  'postUserFuncInt': 'reserved',
  'preBlanks': 'reserved',
  'preCObject': 'reserved',
  'prefix': 'reserved',
  'prefixComment': 'reserved',
  'prefixLocalAnchors': 'reserved',
  'prefixRelPathWith': 'reserved',
  'preIfEmptyListNum': 'reserved',
  'preLineBlanks': 'reserved',
  'preLineChar': 'reserved',
  'preLineLen': 'reserved',
  'prepend': 'reserved',
  'preserveEntities': 'reserved',
  'preUserFunc': 'reserved',
  'prev': 'reserved',
  'preview': 'keyword2',
  'previewBorder': 'reserved',
  'prevnextToSection': 'reserved',
  'prioriCalc': 'reserved',
  'proc': 'reserved',
  'processor_allowUpscaling': 'reserved',
  'properties': 'reserved',
  'protect': 'reserved',
  'protectLvar': 'reserved',
  'publish': 'keyword2',
  'publish_levels': 'reserved',
  'QEisDefault': 'reserved',
  'quality': 'reserved',
  'RADIO': 'keyword2',
  'radio': 'reserved',
  'radioWrap': 'reserved',
  'range': 'reserved',
  'rawUrlEncode': 'reserved',
  'recipient': 'reserved',
  'RECORDS': 'keyword',
  'recursive': 'reserved',
  'recursiveDelete': 'reserved',
  'redirect': 'reserved',
  'redirectToURL': 'reserved',
  'reduceColors': 'reserved',
  'references': 'reserved',
  'register': 'reserved',
  'relPathPrefix': 'reserved',
  'remap': 'reserved',
  'remapTag': 'reserved',
  'REMOTE_ADDR': 'keyword',
  'removeBadHTML': 'reserved',
  'removeDefaultJS': 'reserved',
  'removeIfEquals': 'reserved',
  'removeIfFalse': 'reserved',
  'removeItems': 'reserved',
  'removeObjectsOfDummy': 'reserved',
  'removePrependedNumbers': 'reserved',
  'removeTags': 'reserved',
  'removeWrapping': 'reserved',
  'renderObj': 'keyword2',
  'renderWrap': 'reserved',
  'REQ': 'keyword2',
  'required': 'keyword3',
  'reset': 'reserved',
  'resources': 'reserved',
  'RESTORE_REGISTER': 'keyword',
  'resultObj': 'reserved',
  'returnLast': 'reserved',
  'returnUrl': 'reserved',
  'rightjoin': 'reserved',
  'rm': 'reserved',
  'rmTagIfNoAttrib': 'reserved',
  'RO': 'keyword3',
  'rootline': 'keyword3',
  'rotate': 'reserved',
  'rows': 'reserved',
  'rowSpace': 'reserved',
  'RTE': 'keyword2',
  'RTE_compliant': 'keyword2',
  'RTEfullScreenWidth': 'reserved',
  'rules': 'reserved',
  'sample': 'reserved',
  'saveClipboard': 'reserved',
  'saveDocNew': 'reserved',
  'script': 'keyword3',
  'search': 'keyword3',
  'SEARCHRESULT': 'keyword',
  'secondRow': 'reserved',
  'section': 'reserved',
  'sectionIndex': 'reserved',
  'select': 'keyword2',
  'selectFields': 'reserved',
  'separator': 'reserved',
  'set': 'reserved',
  'setContentToCurrent': 'reserved',
  'setCurrent': 'reserved',
  'setfixed': 'reserved',
  'setOnly': 'reserved',
  'setup': 'keyword2',
  'shadow': 'reserved',
  'SHARED': 'keyword',
  'sharpen': 'reserved',
  'shear': 'reserved',
  'short': 'reserved',
  'shortcut': 'keyword3',
  'shortcutFrame': 'reserved',
  'shortcutIcon': 'reserved',
  'show': 'reserved',
  'showAccessRestrictedPages': 'reserved',
  'showActive': 'reserved',
  'showClipControlPanelsDespiteOfCMlayers': 'reserved',
  'showFirst': 'reserved',
  'showHiddenPages': 'reserved',
  'showHiddenRecords': 'reserved',
  'showHistory': 'reserved',
  'showPageIdWithTitle': 'reserved',
  'showTagFreeClasses': 'reserved',
  'simulateDate': 'reserved',
  'simulateUserGroup': 'reserved',
  'singlePid': 'reserved',
  'site_author': 'reserved',
  'site_reserved': 'reserved',
  'sitemap': 'keyword3',
  'sitetitle': 'reserved',
  'siteUrl': 'reserved',
  'size': 'reserved',
  'solarize': 'reserved',
  'sorting': 'reserved',
  'source': 'reserved',
  'space': 'reserved',
  'spaceAfter': 'reserved',
  'spaceBefore': 'reserved',
  'spaceBelowAbove': 'reserved',
  'spaceLeft': 'reserved',
  'spaceRight': 'reserved',
  'spacing': 'reserved',
  'spamProtectEmailAddresses': 'reserved',
  'spamProtectEmailAddresses_atSubst': 'reserved',
  'spamProtectEmailAddresses_lastDotSubst': 'reserved',
  'SPC': 'keyword3',
  'special': 'reserved',
  'split': 'keyword2',
  'splitChar': 'reserved',
  'splitRendering': 'reserved',
  'src': 'reserved',
  'stdheader': 'reserved',
  'stdWrap': 'keyword2',
  'stdWrap2': 'reserved',
  'strftime': 'reserved',
  'stripHtml': 'reserved',
  'styles': 'reserved',
  'stylesheet': 'reserved',
  'submenuObjSuffixes': 'reserved',
  'subMenuOffset': 'reserved',
  'submit': 'reserved',
  'subparts': 'keyword2',
  'subst_elementUid': 'reserved',
  'substMarksSeparately': 'reserved',
  'substring': 'reserved',
  'swirl': 'reserved',
  'sword': 'reserved',
  'sword_noMixedCase': 'reserved',
  'SWORD_PARAMS': 'reserved',
  'sword_standAlone': 'reserved',
  'sys_dmail': 'keyword3',
  'sys_domain': 'keyword3',
  'sys_filemounts': 'keyword3',
  'sys_language_mode': 'reserved',
  'sys_language_overlay': 'reserved',
  'sys_language_uid': 'reserved',
  'sys_note': 'keyword3',
  'sys_template': 'keyword3',
  'system': 'keyword2',
  'table': 'keyword3',
  'tableCellColor': 'reserved',
  'tableParams': 'reserved',
  'tables': 'reserved',
  'tableStdWrap': 'reserved',
  'tableWidth': 'reserved',
  'tags': 'reserved',
  'target': 'reserved',
  'TCAdefaults': 'keyword',
  'TCEFORM': 'keyword',
  'TCEMAIN': 'keyword',
  'TDparams': 'reserved',
  'temp': 'keyword2',
  'TEMPLATE': 'keyword',
  'template': 'keyword2',
  'templateContent': 'reserved',
  'templateFile': 'reserved',
  'TEXT': 'keyword',
  'text': 'keyword3',
  'textarea': 'reserved',
  'textMargin': 'reserved',
  'textMargin_outOfText': 'reserved',
  'textMaxLength': 'reserved',
  'textObjNum': 'reserved',
  'textpic': 'keyword3',
  'textPos': 'reserved',
  'thickness': 'reserved',
  'this': 'keyword3',
  'thumbnailsByDefault': 'reserved',
  'tile': 'reserved',
  'time_stdWrap': 'reserved',
  'tipafriendLib': 'reserved',
  'title': 'reserved',
  'titleLen': 'reserved',
  'titleTagFunction': 'reserved',
  'titleText': 'reserved',
  'tm': 'reserved',
  'TMENU': 'keyword',
  'TMENUITEM': 'keyword',
  'token': 'reserved',
  'top': 'keyword3',
  'totalWidth': 'reserved',
  'transparentBackground': 'reserved',
  'transparentColor': 'reserved',
  'treeLevel': 'keyword2',
  'trim': 'reserved',
  'true': 'keyword3',
  'tsdebug': 'keyword2',
  'tsdebug_tree': 'reserved',
  'TSFE': 'keyword',
  'type': 'reserved',
  'typeNum': 'reserved',
  'types': 'reserved',
  'typolink': 'keyword2',
  'typolinkCheckRootline': 'reserved',
  'uid': 'keyword3',
  'uidInList': 'reserved',
  'uniqueGlobal': 'keyword3',
  'uniqueLocal': 'keyword3',
  'unset': 'reserved',
  'unsetEmpty': 'keyword3',
  'updated': 'keyword3',
  'uploads': 'keyword3',
  'upper': 'reserved',
  'url': 'keyword2',
  'us': 'keyword3',
  'useCacheHash': 'reserved',
  'useLargestItemX': 'reserved',
  'useLargestItemY': 'reserved',
  'USER': 'keyword',
  'user': 'reserved',
  'USER_INT': 'keyword',
  'user_task': 'keyword3',
  'useragent': 'keyword2',
  'USERDEF1': 'keyword3',
  'USERDEF1RO': 'keyword3',
  'USERDEF2': 'keyword3',
  'USERDEF2RO': 'keyword3',
  'userdefined': 'reserved',
  'userFunc': 'keyword2',
  'userfunction': 'reserved',
  'usergroup': 'keyword3',
  'userid': 'reserved',
  'USERNAME_substToken': 'reserved',
  'userProc': 'reserved',
  'USR': 'keyword3',
  'USRRO': 'keyword3',
  'value': 'reserved',
  'valueArray': 'reserved',
  'version': 'keyword2',
  'view': 'keyword2',
  'wave': 'reserved',
  'web_func': 'keyword3',
  'web_info': 'keyword3',
  'web_layout': 'keyword3',
  'web_list': 'keyword3',
  'web_ts': 'keyword',
  'where': 'reserved',
  'width': 'reserved',
  'wiz': 'reserved',
  'wordSpacing': 'reserved',
  'workArea': 'reserved',
  'workOnSubpart': 'keyword2',
  'wrap': 'reserved',
  'wrap1': 'reserved',
  'wrap2': 'reserved',
  'wrap3': 'reserved',
  'wrapAfterTags': 'reserved',
  'wrapAlign': 'reserved',
  'wrapFieldName': 'reserved',
  'wrapItemAndSub': 'reserved',
  'wrapNonWrappedLines': 'reserved',
  'wraps': 'reserved',
  'xhtml_cleaning': 'reserved',
  'xhtml_strict': 'keyword3',
  'xhtml_trans': 'keyword3',
  'xmlprologue': 'reserved',
  'XY': 'keyword3'
};

var tokenizeTypoScript = function() {

  // Some helper regexp matchers.
  var isOperatorChar = matcher(/[\+\-\*\&\%\/=<>!\?]/);
  var isDigit = matcher(/[0-9]/);
  var isHexDigit = matcher(/[0-9A-Fa-f]/);
  var isWordChar = matcher(/[\w\$_]/);

  function isWhiteSpace(ch) {
    // Unfortunately, IE's regexp matcher thinks non-breaking spaces
    // aren't whitespace. Also, in our scheme newlines are no
    // whitespace (they are another special case).
    return ch != "\n" && (ch == nbsp || /\s/.test(ch));
  }

  // This function produces a MochiKit-style iterator that tokenizes
  // the output of the given stringstream (see stringstream.js).
  // Tokens are objects with a type, style, and value property. The
  // value contains the textual content of the token. Because this may
  // include trailing whitespace (for efficiency reasons), some
  // tokens, such a variable names, also have a name property
  // containing their actual textual value.
  return function(source) {
    // Produce a value to return. Automatically skips and includes any
    // whitespace. The base argument is prepended to the value
    // property and assigned to the name property -- this is used when
    // the caller has already extracted the text from the stream
    // himself.
    function result(type, style, base) {
      // nextWhile(isWhiteSpace); - comment thats line because needed for autocomplete
      var value = {
        type: type,
        style: style,
        value: (base ? base + source.get() : source.get())
      };
      if (base) {
        value.name = base;
      }
      return value;
    }

    // Advance the text stream over characters for which test returns
    // true. (The characters that are 'consumed' like this can later
    // be retrieved by calling source.get()).
    function nextWhile(test) {
      var next;
      while ((next = source.peek()) && test(next)) {
        source.next();
      }
    }

    // Advance the stream until the given character (not preceded by a
    // backslash) is encountered (or a newline is found).
    function nextUntilUnescaped(end) {
      var escaped = false;
      var next;
      while ((next = source.peek()) && next != "\n") {
        source.next();
        if (next == end && !escaped) {
          break;
        }
        escaped = next == "\\";
      }
    }

    function readHexNumber() {
      source.next();
      // skip the 'x'
      nextWhile(isHexDigit);
      return result("number", "atom");
    }

    function readNumber() {
      nextWhile(isDigit);
      return result("number", "atom");
    }

    // Read a word, look it up in keywords. If not found, it is a
    // variable, otherwise it is a keyword of the type found.
    function readWord() {
      nextWhile(isWordChar);
      var word = source.get();
      var known = typoscriptWords.hasOwnProperty(word) && {
        type: 'keyword',
        style: typoscriptWords[word]
      };
      return known ?
        result(known.type, known.style, word) :
        result("variable", "other", word);
    }

    function readRegexp() {
      nextUntilUnescaped("/");
      nextWhile(matcher(/[gi]/));
      return result("regexp", "string");
    }

    // Mutli-line comments are tricky. We want to return the newlines
    // embedded in them as regular newline tokens, and then continue
    // returning a comment token for every line of the comment. So
    // some state has to be saved (inComment) to indicate whether we
    // are inside a /* */ sequence.
    function readMultilineComment(start) {
      this.inComment = true;
      var maybeEnd = (start == "*");
      while (true) {
        var next = source.peek();
        if (next == "\n") {
          break;
        }
        source.next();
        if (next == "/" && maybeEnd) {
          this.inComment = false;
          break;
        }
        maybeEnd = (next == "*");
      }

      return result("comment", "ts-comment");
    }

    // Fetch the next token. Dispatches on first character in the
    // stream, or first two characters when the first is a slash. The
    // || things are a silly trick to keep simple cases on a single
    // line.
    function next() {
      var token = null;
      var ch = source.next();
      if (ch == "\n") {
        token = {
          type: "newline",
          style: "whitespace",
          value: source.get()
        };
        this.inValue = false;

      } else if (!this.inValue && this.inComment) {
        token = readMultilineComment.call(this, ch);

        /*
        } else if (this.inValue) {
          token = nextUntilUnescaped(null) || {
            type: "value",
            style: "ts-value",
            value: source.get()
          };
          this.inValue = false;
        */

      } else if (isWhiteSpace(ch)) {
        token = nextWhile(isWhiteSpace) || result("whitespace", "whitespace");

      } else if (!this.inValue && (ch == "\"" || ch == "'")) {
        token = nextUntilUnescaped(ch) || result("string", "string");

      } else if (
        (ch == "<" ||
          ch == ">" ||
          ch == "." ||
          (ch == "=" && source.peek() != "<")
        )
        && source.peek() != "\n") { // there must be some value behind the operator!
        this.inValue = true;
        token = result(ch, "ts-operator");

      } else if (!this.inValue && ch == "[") {
        token = nextUntilUnescaped("]") || result("condition", "ts-condition");

        // with punctuation, the type of the token is the symbol itself
      } else if (!this.inValue && /[\[\]\(\),;\:\.\<\>\=]/.test(ch)) {
        token = result(ch, "ts-operator");

      } else if (!this.inValue && (ch == "{" || ch == "}")) {
        token = result(ch, "ts-operator curly-bracket");

      } else if (!this.inValue && ch == "0" && (source.peek() == "x" || source.peek() == "X")) {
        token = readHexNumber();

      } else if (!this.inValue && isDigit(ch)) {
        token = readNumber();

      } else if (!this.inValue && ch == "/") {
        next = source.peek();

        if (next == "*") {
          token = readMultilineComment.call(this, ch);

        } else if (next == "/") {
          token = nextUntilUnescaped(null) || result("comment", "ts-comment");

        } else if (this.regexp) {
          token = readRegexp();

        } else {
          token = nextWhile(isOperatorChar) || result("operator", "ts-operator");
        }

      } else if (!this.inValue && ch == "#") {
        token = nextUntilUnescaped(null) || result("comment", "ts-comment");

      } else if (!this.inValue && isOperatorChar(ch)) {
        token = nextWhile(isOperatorChar) || result("operator", "ts-operator");

      } else {
        token = readWord();
        if (this.inValue) {
          token.style += ' ts-value';
        }
      }

      // JavaScript's syntax rules for when a slash might be the start
      // of a regexp and when it is just a division operator are kind
      // of non-obvious. This decides, based on the current token,
      // whether the next token could be a regular expression.
      if (token.style != "whitespace" && token != "comment") {
        this.regexp = token.type == "operator" || token.type == "keyword c" || token.type.match(/[\[{}\(,;:]/);
      }
      return token;
    }

    // Wrap it in an iterator. The state (regexp and inComment) is
    // exposed because a parser will need to save it when making a
    // copy of its state.
    return {
      next: next,
      regexp: true,
      inComment: false,
      inValue: false
    };
  }
}();
