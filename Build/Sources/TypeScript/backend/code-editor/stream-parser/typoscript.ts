import type { StringStream, StreamParser, IndentContext } from '@codemirror/language';

type Type = string;
type Value = string;
type Keyword = {
  type: string,
  style: 'keyword'
};

type CombinatorReturnType = boolean | undefined;
type CombinatorFunction = (type?: Type, value?: unknown) => CombinatorReturnType;
type CombinatorLexFunction = ((type?: Type, value?: unknown) => CombinatorReturnType) & { lex: true };
type CC = CombinatorFunction | CombinatorLexFunction;

type CX = {
  state: State,
  stream?: StringStream,
  column: number,
  marked: string,
  style?: string,
  cc: CC[]
};

type LocalVars = {
  name: string,
  next: LocalVars
};

type Context = {
  vars: LocalVars,
  prev?: Context | null
};

type State = {
  tokenize: (stream: StringStream, state: State) => string | undefined;
  lastType: Type,
  cc: CC[],
  lexical: TSLexical,
  localVars: LocalVars,
  context: Context,
  indented: number,
};

class TSLexical {
  public indented: number;
  public column: number;
  public type: Type;
  public prev: TSLexical;
  public info: number | string;
  public align: boolean;
  public pos: number | undefined;

  constructor(indented: number, column: number, type: Type, align: boolean, prev?: TSLexical, info?: number | string) {
    this.indented = indented;
    this.column = column;
    this.type = type;
    this.prev = prev;
    this.info = info;
    if (align != null) {
      this.align = align;
    }
  }
}

function expressionAllowed(stream: StringStream, state: State): boolean {
  return (
    // eslint-disable-next-line no-useless-escape
    /^(?:operator|sof|keyword c|case|new|export|default|[\[{}\(,;:]|=>)$/.test(state.lastType)
  );
}

export class TypoScriptStreamParserFactory {
  public create(): StreamParser<State> {
    const wordRE = /[\w$\xa1-\uffff]/;
    //const statementIndent = undefined;
    const statementIndent = false;
    const parserConfig: { name: string, localVars: LocalVars, doubleIndentSwitch: boolean } = {
      name: 'typoscript',
      localVars: undefined,
      doubleIndentSwitch: false,
    };

    // Tokenizer

    const keywords = function(): Record<string, Keyword> {
      function kw(type: string): Keyword {
        return { type: type, style: 'keyword' };
      }

      const A = kw('keyword a'), B = kw('keyword b');

      return {
        '_CSS_DEFAULT_STYLE': kw('_CSS_DEFAULT_STYLE'),
        '_LOCAL_LANG': kw('_LOCAL_LANG'),
        '_offset': kw('_offset'),
        'absRefPrefix': kw('absRefPrefix'),
        'accessibility': kw('accessibility'),
        'ACT': B,
        'ACTIFSUB': B,
        'ACTIFSUBRO': kw('ACTIFSUBRO'),
        'ACTRO': B,
        'addAttributes': kw('addAttributes'),
        'addExtUrlsAndShortCuts': kw('addExtUrlsAndShortCuts'),
        'addItems': kw('addItems'),
        'additionalHeaders': kw('additionalHeaders'),
        'additionalParams': kw('additionalParams'),
        'addQueryString': kw('addQueryString'),
        'adjustItemsH': kw('adjustItemsH'),
        'adjustSubItemsH': kw('adjustSubItemsH'),
        'admPanel': A,
        'after': kw('after'),
        'afterImg': kw('afterImg'),
        'afterImgLink': kw('afterImgLink'),
        'afterImgTagParams': kw('afterImgTagParams'),
        'afterROImg': kw('afterROImg'),
        'afterWrap': kw('afterWrap'),
        'age': kw('age'),
        'alertPopups': kw('alertPopups'),
        'align': kw('align'),
        'all': B,
        'allow': kw('allow'),
        'allowCaching': kw('allowCaching'),
        'allowedAttribs': kw('allowedAttribs'),
        'allowedClasses': kw('allowedClasses'),
        'allowedCols': kw('allowedCols'),
        'allowedNewTables': kw('allowedNewTables'),
        'allowTags': kw('allowTags'),
        'allStdWrap': kw('allStdWrap'),
        'allWrap': kw('allWrap'),
        'alt_print': A,
        'alternativeSortingField': kw('alternativeSortingField'),
        'altIcons': kw('altIcons'),
        'altImgResource': kw('altImgResource'),
        'altLabels': kw('altLabels'),
        'altTarget': kw('altTarget'),
        'altText': kw('altText'),
        'altUrl': kw('altUrl'),
        'altUrl_noDefaultParams': kw('altUrl_noDefaultParams'),
        'altWrap': kw('altWrap'),
        'always': kw('always'),
        'alwaysActivePIDlist': kw('alwaysActivePIDlist'),
        'alwaysLink': kw('alwaysLink'),
        'andWhere': kw('andWhere'),
        'angle': kw('angle'),
        'antiAlias': kw('antiAlias'),
        'append': kw('append'),
        'applyTotalH': kw('applyTotalH'),
        'applyTotalW': kw('applyTotalW'),
        'archive': kw('archive'),
        'ascii': B,
        'ATagAfterWrap': kw('ATagAfterWrap'),
        'ATagBeforeWrap': kw('ATagBeforeWrap'),
        'ATagParams': kw('ATagParams'),
        'ATagTitle': kw('ATagTitle'),
        'atLeast': B,
        'atMost': B,
        'attribute': kw('attribute'),
        'auth': A,
        'autoLevels': kw('autoLevels'),
        'autonumber': kw('autonumber'),
        'backColor': kw('backColor'),
        'background': kw('background'),
        'baseURL': kw('baseURL'),
        'BE': B,
        'be_groups': B,
        'be_users': B,
        'before': kw('before'),
        'beforeImg': kw('beforeImg'),
        'beforeImgLink': kw('beforeImgLink'),
        'beforeImgTagParams': kw('beforeImgTagParams'),
        'beforeROImg': kw('beforeROImg'),
        'beforeWrap': kw('beforeWrap'),
        'begin': kw('begin'),
        'bgCol': kw('bgCol'),
        'bgImg': kw('bgImg'),
        'blur': kw('blur'),
        'bm': kw('bm'),
        'bodyTag': kw('bodyTag'),
        'bodyTagAdd': kw('bodyTagAdd'),
        'bodyTagCObject': kw('bodyTagCObject'),
        'bodytext': kw('bodytext'),
        'borderCol': kw('borderCol'),
        'borderThick': kw('borderThick'),
        'bottomBackColor': kw('bottomBackColor'),
        'bottomContent': kw('bottomContent'),
        'bottomHeight': kw('bottomHeight'),
        'bottomImg': kw('bottomImg'),
        'bottomImg_mask': kw('bottomImg_mask'),
        'BOX': B,
        'br': kw('br'),
        'browse': B,
        'browser': A,
        'brTag': kw('brTag'),
        'bullet': kw('bullet'),
        'bulletlist': kw('bulletlist'),
        'bullets': B,
        'bytes': kw('bytes'),
        'cache': A,
        'cache_clearAtMidnight': kw('cache_clearAtMidnight'),
        'cache_period': kw('cache_period'),
        'caption': kw('caption'),
        'caption_stdWrap': kw('caption_stdWrap'),
        'captionHeader': kw('captionHeader'),
        'captionSplit': kw('captionSplit'),
        'CARRAY': kw('CARRAY'),
        'CASE': kw('CASE'),
        'case': kw('case'),
        'casesensitiveComp': kw('casesensitiveComp'),
        'cellpadding': kw('cellpadding'),
        'cellspacing': kw('cellspacing'),
        'char': kw('char'),
        'charcoal': kw('charcoal'),
        'charMapConfig': kw('charMapConfig'),
        'CHECK': A,
        'check': kw('check'),
        'class': kw('class'),
        'classesAnchor': kw('classesAnchor'),
        'classesCharacter': kw('classesCharacter'),
        'classesImage': kw('classesImage'),
        'classesParagraph': kw('classesParagraph'),
        'clear': kw('clear'),
        'clearCache': kw('clearCache'),
        'clearCache_disable': kw('clearCache_disable'),
        'clearCache_pageGrandParent': kw('clearCache_pageGrandParent'),
        'clearCache_pageSiblingChildren': kw('clearCache_pageSiblingChildren'),
        'clearCacheCmd': kw('clearCacheCmd'),
        'clearCacheLevels': kw('clearCacheLevels'),
        'clearCacheOfPages': kw('clearCacheOfPages'),
        'clickTitleMode': kw('clickTitleMode'),
        'clipboardNumberPads': kw('clipboardNumberPads'),
        'cMargins': kw('cMargins'),
        'COA': kw('COA'),
        'COA_INT': kw('COA_INT'),
        'cObj': A,
        'COBJ_ARRAY': kw('COBJ_ARRAY'),
        'cObject': A,
        'cObjNum': kw('cObjNum'),
        'collapse': kw('collapse'),
        'collections': kw('collections'),
        'color': kw('color'),
        'color1': kw('color1'),
        'color2': kw('color2'),
        'color3': kw('color3'),
        'color4': kw('color4'),
        'colors': kw('colors'),
        'colour': kw('colour'),
        'colPos_list': kw('colPos_list'),
        'colRelations': kw('colRelations'),
        'cols': kw('cols'),
        'colSpace': kw('colSpace'),
        'COMMENT': A,
        'comment_auto': kw('comment_auto'),
        'commentWrap': kw('commentWrap'),
        'compX': kw('compX'),
        'compY': kw('compY'),
        'conf': kw('conf'),
        'CONFIG': kw('CONFIG'),
        'config': A,
        'CONSTANTS': kw('CONSTANTS'),
        'constants': kw('constants'),
        'CONTENT': kw('CONTENT'),
        'content': A,
        'contextMenu': kw('contextMenu'),
        'copy': A,
        'copyLevels': kw('copyLevels'),
        'count_HMENU_MENUOBJ': kw('count_HMENU_MENUOBJ'),
        'count_menuItems': kw('count_menuItems'),
        'count_MENUOBJ': kw('count_MENUOBJ'),
        'create': kw('create'),
        'crop': kw('crop'),
        'csConv': kw('csConv'),
        'CType': kw('CType'),
        'CUR': B,
        'CURIFSUB': B,
        'CURIFSUBRO': B,
        'current': kw('current'),
        'CURRO': B,
        'curUid': kw('curUid'),
        'cut': A,
        'cWidth': kw('cWidth'),
        'data': kw('data'),
        'dataArray': A,
        'dataWrap': kw('dataWrap'),
        'date': kw('date'),
        'date_stdWrap': kw('date_stdWrap'),
        'datePrefix': kw('datePrefix'),
        'dayofmonth': A,
        'dayofweek': A,
        'DB': kw('DB'),
        'db_list': A,
        'debug': kw('debug'),
        'debugData': kw('debugData'),
        'debugFunc': kw('debugFunc'),
        'debugItemConf': kw('debugItemConf'),
        'default': B,
        'defaultAlign': kw('defaultAlign'),
        'defaultCmd': kw('defaultCmd'),
        'defaultHeaderType': kw('defaultHeaderType'),
        'defaultOutput': kw('defaultOutput'),
        'defaults': kw('defaults'),
        'defaultType': kw('defaultType'),
        'delete': kw('delete'),
        'denyTags': kw('denyTags'),
        'depth': kw('depth'),
        'DESC': kw('DESC'),
        'description': B,
        'dimensions': kw('dimensions'),
        'direction': kw('direction'),
        'directory': B,
        'directReturn': B,
        'disableAllHeaderCode': kw('disableAllHeaderCode'),
        'disableAltText': kw('disableAltText'),
        'disableBodyTag': kw('disableBodyTag'),
        'disabled': kw('disabled'),
        'disableDelete': kw('disableDelete'),
        'disableHideAtCopy': kw('disableHideAtCopy'),
        'disableItems': kw('disableItems'),
        'disableNoMatchingValueElement': kw('disableNoMatchingValueElement'),
        'disablePrefixComment': kw('disablePrefixComment'),
        'disablePrependAtCopy': kw('disablePrependAtCopy'),
        'disableSearchBox': kw('disableSearchBox'),
        'disableSingleTableView': kw('disableSingleTableView'),
        'displayContent': kw('displayContent'),
        'displayFieldIcons': kw('displayFieldIcons'),
        'displayIcons': kw('displayIcons'),
        'displayMessages': kw('displayMessages'),
        'displayRecord': kw('displayRecord'),
        'displayTimes': kw('displayTimes'),
        'distributeX': kw('distributeX'),
        'distributeY': kw('distributeY'),
        'div': B,
        'DIV': kw('DIV'),
        'doctype': kw('doctype'),
        'DOCUMENT_BODY': kw('DOCUMENT_BODY'),
        'doktype': kw('doktype'),
        'doNotLinkIt': kw('doNotLinkIt'),
        'doNotShowLink': kw('doNotShowLink'),
        'doNotStripHTML': kw('doNotStripHTML'),
        'dontCheckPid': kw('dontCheckPid'),
        'dontLinkIfSubmenu': kw('dontLinkIfSubmenu'),
        'dontWrapInTable': kw('dontWrapInTable'),
        'doubleBrTag': kw('doubleBrTag'),
        'dynCSS': A,
        'edge': kw('edge'),
        'edit': A,
        'edit_access': A,
        'edit_docModuleUpload': kw('edit_docModuleUpload'),
        'EFFECT': kw('EFFECT'),
        'elements': kw('elements'),
        'else': B,
        'email': B,
        'emailMeAtLogin': kw('emailMeAtLogin'),
        'emailMess': kw('emailMess'),
        'emboss': kw('emboss'),
        'enable': kw('enable'),
        'encapsLines': kw('encapsLines'),
        'encapsLinesStdWrap': kw('encapsLinesStdWrap'),
        'encapsTagList': kw('encapsTagList'),
        'end': B,
        'entryLevel': kw('entryLevel'),
        'equalH': kw('equalH'),
        'equals': B,
        'everybody': kw('everybody'),
        'excludeDoktypes': kw('excludeDoktypes'),
        'excludeUidList': kw('excludeUidList'),
        'expAll': kw('expAll'),
        'expand': kw('expand'),
        'explode': kw('explode'),
        'ext': kw('ext'),
        'external': B,
        'externalBlocks': kw('externalBlocks'),
        'extTarget': kw('extTarget'),
        'face': kw('face'),
        'false': B,
        'FE': B,
        'fe_adminLib': kw('fe_adminLib'),
        'fe_groups': B,
        'fe_users': B,
        'feadmin': B,
        'field': kw('field'),
        'fieldName': kw('fieldName'),
        'fieldOrder': kw('fieldOrder'),
        'fieldRequired': kw('fieldRequired'),
        'fields': kw('fields'),
        'fieldWrap': kw('fieldWrap'),
        'file': kw('file'),
        'file1': kw('file1'),
        'file2': kw('file2'),
        'file3': kw('file3'),
        'file4': kw('file4'),
        'file5': kw('file5'),
        'FILES': kw('FILES'),
        'files': kw('files'),
        'firstLabel': kw('firstLabel'),
        'firstLabelGeneral': kw('firstLabelGeneral'),
        'fixAttrib': kw('fixAttrib'),
        'flip': kw('flip'),
        'flop': kw('flop'),
        'FLUIDTEMPLATE': kw('FLUIDTEMPLATE'),
        'folder': A,
        'folders': kw('folders'),
        'folderTree': A,
        'foldoutMenu': A,
        'fontColor': kw('fontColor'),
        'fontFile': kw('fontFile'),
        'fontOffset': kw('fontOffset'),
        'fontSize': kw('fontSize'),
        'fontSizeMultiplicator': kw('fontSizeMultiplicator'),
        'forceDisplayFieldIcons': kw('forceDisplayFieldIcons'),
        'forceDisplayIcons': kw('forceDisplayIcons'),
        'forceTemplateParsing': kw('forceTemplateParsing'),
        'forceTypeValue': kw('forceTypeValue'),
        'FORM': kw('FORM'),
        'format': kw('format'),
        'function': kw('function'),
        'Functions': A,
        'gamma': kw('gamma'),
        'gapBgCol': kw('gapBgCol'),
        'gapLineCol': kw('gapLineCol'),
        'gapLineThickness': kw('gapLineThickness'),
        'gapWidth': kw('gapWidth'),
        'get': kw('get'),
        'getBorder': kw('getBorder'),
        'getLeft': kw('getLeft'),
        'getRight': kw('getRight'),
        'GIFBUILDER': kw('GIFBUILDER'),
        'global': kw('global'),
        'globalNesting': kw('globalNesting'),
        'globalString': kw('globalString'),
        'globalVar': kw('globalVar'),
        'GP': kw('GP'),
        'gray': kw('gray'),
        'group': kw('group'),
        'groupBy': kw('groupBy'),
        'groupid': kw('groupid'),
        'header': B,
        'header_layout': kw('header_layout'),
        'headerComment': kw('headerComment'),
        'headerData': kw('headerData'),
        'headerSpace': kw('headerSpace'),
        'headTag': kw('headTag'),
        'height': kw('height'),
        'helpText': kw('helpText'),
        'hidden': kw('hidden'),
        'hiddenFields': kw('hiddenFields'),
        'hide': kw('hide'),
        'hidePStyleItems': kw('hidePStyleItems'),
        'hideRecords': kw('hideRecords'),
        'highColor': kw('highColor'),
        'history': kw('history'),
        'HMENU': kw('HMENU'),
        'hostname': A,
        'hour': A,
        'HTML': kw('HTML'),
        'html': B,
        'HTMLparser': kw('HTMLparser'),
        'HTMLparser_tags': kw('HTMLparser_tags'),
        'htmlSpecialChars': kw('htmlSpecialChars'),
        'htmlTag_setParams': kw('htmlTag_setParams'),
        'http': kw('http'),
        'icon': kw('icon'),
        'icon_image_ext_list': kw('icon_image_ext_list'),
        'icon_link': kw('icon_link'),
        'iconCObject': kw('iconCObject'),
        'id': B,
        'IENV': kw('IENV'),
        'if': B,
        'ifEmpty': B,
        'IFSUB': B,
        'IFSUBRO': B,
        'IMAGE': kw('IMAGE'),
        'image': B,
        'image_frames': kw('image_frames'),
        'imageLinkWrap': kw('imageLinkWrap'),
        'imagePath': kw('imagePath'),
        'images': kw('images'),
        'imageWrapIfAny': kw('imageWrapIfAny'),
        'IMG_RESOURCE': kw('IMG_RESOURCE'),
        'imgList': A,
        'imgMax': kw('imgMax'),
        'imgNameNotRandom': kw('imgNameNotRandom'),
        'imgNamePrefix': kw('imgNamePrefix'),
        'imgObjNum': kw('imgObjNum'),
        'imgParams': kw('imgParams'),
        'imgPath': kw('imgPath'),
        'imgResource': A,
        'imgStart': kw('imgStart'),
        'IMGTEXT': kw('IMGTEXT'),
        'imgText': A,
        'import': kw('import'),
        'inBranch': B,
        'inc': kw('inc'),
        'INCLUDE_TYPOSCRIPT': kw('INCLUDE_TYPOSCRIPT'),
        'includeCSS': kw('includeCSS'),
        'includeLibrary': kw('includeLibrary'),
        'includeNotInMenu': kw('includeNotInMenu'),
        'index': kw('index'),
        'index_descrLgd': kw('index_descrLgd'),
        'index_enable': kw('index_enable'),
        'index_externals': kw('index_externals'),
        'info': A,
        'inlineStyle2TempFile': kw('inlineStyle2TempFile'),
        'innerStdWrap': kw('innerStdWrap'),
        'innerStdWrap_all': kw('innerStdWrap_all'),
        'innerWrap': kw('innerWrap'),
        'innerWrap2': kw('innerWrap2'),
        'input': kw('input'),
        'inputLevels': kw('inputLevels'),
        'insertData': kw('insertData'),
        'intensity': kw('intensity'),
        'intTarget': kw('intTarget'),
        'intval': kw('intval'),
        'invert': kw('invert'),
        'IP': A,
        'IProcFunc': kw('IProcFunc'),
        'isFalse': B,
        'isGreaterThan': B,
        'isInList': B,
        'isLessThan': B,
        'isPositive': B,
        'isTrue': B,
        'itemArrayProcFunc': kw('itemArrayProcFunc'),
        'itemH': kw('itemH'),
        'items': kw('items'),
        'itemsProcFunc': kw('itemsProcFunc'),
        'iterations': kw('iterations'),
        'join': kw('join'),
        'JSwindow': A,
        'JSWindow': kw('JSWindow'),
        'JSwindow_params': kw('JSwindow_params'),
        'keep': kw('keep'),
        'keepEntries': kw('keepEntries'),
        'keepNonMatchedTags': kw('keepNonMatchedTags'),
        'key': kw('key'),
        'keyword3': B,
        'LABEL': A,
        'label': kw('label'),
        'labelStdWrap': kw('labelStdWrap'),
        'labelWrap': kw('labelWrap'),
        'lang': kw('lang'),
        'languageField': kw('languageField'),
        'layout': A,
        'left': kw('left'),
        'leftjoin': kw('leftjoin'),
        'levels': kw('levels'),
        'leveltitle': B,
        'leveluid': kw('leveluid'),
        'lib': A,
        'limit': kw('limit'),
        'line': kw('line'),
        'lineColor': kw('lineColor'),
        'lineThickness': kw('lineThickness'),
        'linkPrefix': kw('linkPrefix'),
        'linkTitleToSelf': kw('linkTitleToSelf'),
        'linkVars': kw('linkVars'),
        'linkWrap': kw('linkWrap'),
        'list': B,
        'listNum': kw('listNum'),
        'listOnlyInSingleTableView': kw('listOnlyInSingleTableView'),
        'LIT': kw('LIT'),
        'lm': kw('lm'),
        'LOAD_REGISTER': kw('LOAD_REGISTER'),
        'locale_all': kw('locale_all'),
        'localNesting': kw('localNesting'),
        'locationData': kw('locationData'),
        'login': B,
        'loginUser': A,
        'lowColor': kw('lowColor'),
        'lower': kw('lower'),
        'LR': kw('LR'),
        'mailform': B,
        'mailto': kw('mailto'),
        'main': kw('main'),
        'makelinks': kw('makelinks'),
        'markerWrap': kw('markerWrap'),
        'marks': A,
        'mask': kw('mask'),
        'max': kw('max'),
        'maxAge': kw('maxAge'),
        'maxChars': kw('maxChars'),
        'maxH': kw('maxH'),
        'maxHeight': kw('maxHeight'),
        'maxItems': kw('maxItems'),
        'maxW': kw('maxW'),
        'maxWidth': kw('maxWidth'),
        'maxWInText': kw('maxWInText'),
        'media': B,
        'menu': B,
        'menuHeight': kw('menuHeight'),
        'menuName': kw('menuName'),
        'menuOffset': kw('menuOffset'),
        'menuWidth': kw('menuWidth'),
        'message_preview': kw('message_preview'),
        'META': kw('META'),
        'meta': kw('meta'),
        'method': kw('method'),
        'min': kw('min'),
        'minH': kw('minH'),
        'minItems': kw('minItems'),
        'minute': A,
        'minW': kw('minW'),
        'mod': B,
        'mode': kw('mode'),
        'module': A,
        'month': A,
        'move_wizard': A,
        'MP_defaults': kw('MP_defaults'),
        'MP_disableTypolinkClosestMPvalue': kw('MP_disableTypolinkClosestMPvalue'),
        'MP_mapRootPoints': kw('MP_mapRootPoints'),
        'MULTIMEDIA': kw('MULTIMEDIA'),
        'multimedia': B,
        'name': kw('name'),
        'negate': B,
        'nesting': kw('nesting'),
        'neverHideAtCopy': kw('neverHideAtCopy'),
        'new': A,
        'NEW': B,
        'new_wizard': A,
        'newPageWiz': kw('newPageWiz'),
        'newRecordFromTable': kw('newRecordFromTable'),
        'newWindow': kw('newWindow'),
        'newWizards': kw('newWizards'),
        'next': kw('next'),
        'niceText': kw('niceText'),
        'nicetext': kw('nicetext'),
        'NO': B,
        'no_cache': kw('no_cache'),
        'no_search': kw('no_search'),
        'noAttrib': kw('noAttrib'),
        'noCache': kw('noCache'),
        'noCreateRecordsLink': kw('noCreateRecordsLink'),
        'noLink': kw('noLink'),
        'noMatchingValue_label': kw('noMatchingValue_label'),
        'nonCachedSubst': kw('nonCachedSubst'),
        'none': B,
        'nonTypoTagStdWrap': kw('nonTypoTagStdWrap'),
        'nonTypoTagUserFunc': kw('nonTypoTagUserFunc'),
        'nonWrappedTag': kw('nonWrappedTag'),
        'noOrderBy': kw('noOrderBy'),
        'noPageTitle': kw('noPageTitle'),
        'noResultObj': A,
        'noThumbsInEB': kw('noThumbsInEB'),
        'noTrimWrap': kw('noTrimWrap'),
        'noValueInsert': kw('noValueInsert'),
        'numRows': A,
        'obj': kw('obj'),
        'offset': kw('offset'),
        'onlineWorkspaceInfo': kw('onlineWorkspaceInfo'),
        'onlyCurrentPid': kw('onlyCurrentPid'),
        'opacity': kw('opacity'),
        'options': A,
        'orderBy': kw('orderBy'),
        'outerWrap': kw('outerWrap'),
        'outline': kw('outline'),
        'outputLevels': kw('outputLevels'),
        'override': kw('override'),
        'overrideAttribs': kw('overrideAttribs'),
        'overrideId': kw('overrideId'),
        'PAGE': kw('PAGE'),
        'page': A,
        'PAGE_TARGET': kw('PAGE_TARGET'),
        'PAGE_TSCONFIG_ID': kw('PAGE_TSCONFIG_ID'),
        'PAGE_TSCONFIG_IDLIST': kw('PAGE_TSCONFIG_IDLIST'),
        'PAGE_TSCONFIG_STR': kw('PAGE_TSCONFIG_STR'),
        'pageFrameObj': kw('pageFrameObj'),
        'pages': B,
        'pageTitleFirst': kw('pageTitleFirst'),
        'pageTree': A,
        'parameter': kw('parameter'),
        'params': kw('params'),
        'parseFunc': kw('parseFunc'),
        'parseFunc_RTE': B,
        'parser': kw('parser'),
        'password': kw('password'),
        'paste': A,
        'path': kw('path'),
        'permissions': kw('permissions'),
        'perms': A,
        'pid': B,
        'pid_list': kw('pid_list'),
        'pidInList': kw('pidInList'),
        'PIDinRootline': A,
        'PIDupinRootline': A,
        'pixelSpaceFontSizeRef': kw('pixelSpaceFontSizeRef'),
        'plaintextLib': kw('plaintextLib'),
        'plainTextStdWrap': kw('plainTextStdWrap'),
        'plugin': A,
        'postCObject': kw('postCObject'),
        'postLineBlanks': kw('postLineBlanks'),
        'postLineChar': kw('postLineChar'),
        'postLineLen': kw('postLineLen'),
        'postUserFunc': kw('postUserFunc'),
        'postUserFuncInt': kw('postUserFuncInt'),
        'preBlanks': kw('preBlanks'),
        'preCObject': kw('preCObject'),
        'prefix': kw('prefix'),
        'prefixComment': kw('prefixComment'),
        'prefixRelPathWith': kw('prefixRelPathWith'),
        'preIfEmptyListNum': kw('preIfEmptyListNum'),
        'preLineBlanks': kw('preLineBlanks'),
        'preLineChar': kw('preLineChar'),
        'preLineLen': kw('preLineLen'),
        'prepend': kw('prepend'),
        'preserveEntities': kw('preserveEntities'),
        'preUserFunc': kw('preUserFunc'),
        'prev': kw('prev'),
        'preview': A,
        'previewBorder': kw('previewBorder'),
        'prevnextToSection': kw('prevnextToSection'),
        'prioriCalc': kw('prioriCalc'),
        'proc': kw('proc'),
        'processor_allowUpscaling': kw('processor_allowUpscaling'),
        'properties': kw('properties'),
        'protect': kw('protect'),
        'protectLvar': kw('protectLvar'),
        'publish': A,
        'publish_levels': kw('publish_levels'),
        'quality': kw('quality'),
        'RADIO': A,
        'radio': kw('radio'),
        'radioWrap': kw('radioWrap'),
        'range': kw('range'),
        'rawUrlEncode': kw('rawUrlEncode'),
        'recipient': kw('recipient'),
        'RECORDS': kw('RECORDS'),
        'recursive': kw('recursive'),
        'redirect': kw('redirect'),
        'redirectToURL': kw('redirectToURL'),
        'references': kw('references'),
        'register': kw('register'),
        'relPathPrefix': kw('relPathPrefix'),
        'remap': kw('remap'),
        'remapTag': kw('remapTag'),
        'REMOTE_ADDR': kw('REMOTE_ADDR'),
        'removeDefaultJS': kw('removeDefaultJS'),
        'removeIfEquals': kw('removeIfEquals'),
        'removeIfFalse': kw('removeIfFalse'),
        'removeItems': kw('removeItems'),
        'removeObjectsOfDummy': kw('removeObjectsOfDummy'),
        'removePrependedNumbers': kw('removePrependedNumbers'),
        'removeTags': kw('removeTags'),
        'removeWrapping': kw('removeWrapping'),
        'renderObj': A,
        'renderWrap': kw('renderWrap'),
        'REQ': A,
        'required': B,
        'reset': kw('reset'),
        'resources': kw('resources'),
        'RESTORE_REGISTER': kw('RESTORE_REGISTER'),
        'resultObj': kw('resultObj'),
        'returnLast': kw('returnLast'),
        'returnUrl': kw('returnUrl'),
        'rightjoin': kw('rightjoin'),
        'rm': kw('rm'),
        'rmTagIfNoAttrib': kw('rmTagIfNoAttrib'),
        'RO': B,
        'rootline': B,
        'rotate': kw('rotate'),
        'rows': kw('rows'),
        'rowSpace': kw('rowSpace'),
        'RTE': A,
        'RTE_compliant': A,
        'rules': kw('rules'),
        'sample': kw('sample'),
        'saveClipboard': kw('saveClipboard'),
        'saveDocNew': kw('saveDocNew'),
        'script': B,
        'search': B,
        'SEARCHRESULT': kw('SEARCHRESULT'),
        'secondRow': kw('secondRow'),
        'section': kw('section'),
        'sectionIndex': kw('sectionIndex'),
        'select': A,
        'selectFields': kw('selectFields'),
        'separator': kw('separator'),
        'set': kw('set'),
        'setContentToCurrent': kw('setContentToCurrent'),
        'setCurrent': kw('setCurrent'),
        'setfixed': kw('setfixed'),
        'setOnly': kw('setOnly'),
        'setup': A,
        'shadow': kw('shadow'),
        'SHARED': kw('SHARED'),
        'sharpen': kw('sharpen'),
        'shear': kw('shear'),
        'short': kw('short'),
        'shortcut': B,
        'shortcutFrame': kw('shortcutFrame'),
        'shortcutIcon': kw('shortcutIcon'),
        'show': kw('show'),
        'showAccessRestrictedPages': kw('showAccessRestrictedPages'),
        'showActive': kw('showActive'),
        'showFirst': kw('showFirst'),
        'showHiddenPages': kw('showHiddenPages'),
        'showHiddenRecords': kw('showHiddenRecords'),
        'showHistory': kw('showHistory'),
        'showPageIdWithTitle': kw('showPageIdWithTitle'),
        'showTagFreeClasses': kw('showTagFreeClasses'),
        'showWebsiteTitle': kw('showWebsiteTitle'),
        'simulateDate': kw('simulateDate'),
        'simulateUserGroup': kw('simulateUserGroup'),
        'singlePid': kw('singlePid'),
        'site_author': kw('site_author'),
        'site_reserved': kw('site_reserved'),
        'sitemap': B,
        'siteUrl': kw('siteUrl'),
        'size': kw('size'),
        'solarize': kw('solarize'),
        'sorting': kw('sorting'),
        'source': kw('source'),
        'space': kw('space'),
        'spaceBelowAbove': kw('spaceBelowAbove'),
        'spaceLeft': kw('spaceLeft'),
        'spaceRight': kw('spaceRight'),
        'spacing': kw('spacing'),
        'spamProtectEmailAddresses': kw('spamProtectEmailAddresses'),
        'spamProtectEmailAddresses_atSubst': kw('spamProtectEmailAddresses_atSubst'),
        'spamProtectEmailAddresses_lastDotSubst': kw('spamProtectEmailAddresses_lastDotSubst'),
        'SPC': B,
        'special': kw('special'),
        'split': A,
        'splitChar': kw('splitChar'),
        'splitRendering': kw('splitRendering'),
        'src': kw('src'),
        'stdWrap': A,
        'stdWrap2': kw('stdWrap2'),
        'strftime': kw('strftime'),
        'stripHtml': kw('stripHtml'),
        'styles': kw('styles'),
        'submenuObjSuffixes': kw('submenuObjSuffixes'),
        'subMenuOffset': kw('subMenuOffset'),
        'submit': kw('submit'),
        'subparts': A,
        'subst_elementUid': kw('subst_elementUid'),
        'substMarksSeparately': kw('substMarksSeparately'),
        'substring': kw('substring'),
        'swirl': kw('swirl'),
        'sys_dmail': B,
        'sys_filemounts': B,
        'sys_note': B,
        'sys_template': B,
        'system': A,
        'table': B,
        'tableCellColor': kw('tableCellColor'),
        'tableParams': kw('tableParams'),
        'tables': kw('tables'),
        'tableStdWrap': kw('tableStdWrap'),
        'tableWidth': kw('tableWidth'),
        'tags': kw('tags'),
        'target': kw('target'),
        'TCAdefaults': kw('TCAdefaults'),
        'TCEFORM': kw('TCEFORM'),
        'TCEMAIN': kw('TCEMAIN'),
        'TDparams': kw('TDparams'),
        'temp': A,
        'template': A,
        'templateContent': kw('templateContent'),
        'templateFile': kw('templateFile'),
        'TEXT': kw('TEXT'),
        'text': B,
        'textarea': kw('textarea'),
        'textMargin': kw('textMargin'),
        'textMargin_outOfText': kw('textMargin_outOfText'),
        'textMaxLength': kw('textMaxLength'),
        'textObjNum': kw('textObjNum'),
        'textpic': B,
        'textPos': kw('textPos'),
        'thickness': kw('thickness'),
        'this': B,
        'tile': kw('tile'),
        'time_stdWrap': kw('time_stdWrap'),
        'tipafriendLib': kw('tipafriendLib'),
        'title': kw('title'),
        'titleLen': kw('titleLen'),
        'titleText': kw('titleText'),
        'tm': kw('tm'),
        'TMENU': kw('TMENU'),
        'TMENUITEM': kw('TMENUITEM'),
        'token': kw('token'),
        'top': B,
        'totalWidth': kw('totalWidth'),
        'transparentBackground': kw('transparentBackground'),
        'transparentColor': kw('transparentColor'),
        'treeLevel': A,
        'trim': kw('trim'),
        'true': B,
        'tsdebug': A,
        'tsdebug_tree': kw('tsdebug_tree'),
        'TSFE': kw('TSFE'),
        'type': kw('type'),
        'typeNum': kw('typeNum'),
        'types': kw('types'),
        'typolink': A,
        'uid': B,
        'uidInList': kw('uidInList'),
        'uniqueGlobal': B,
        'uniqueLocal': B,
        'unset': kw('unset'),
        'unsetEmpty': B,
        'updated': B,
        'uploads': B,
        'upper': kw('upper'),
        'url': A,
        'us': B,
        'useLargestItemX': kw('useLargestItemX'),
        'useLargestItemY': kw('useLargestItemY'),
        'USER': kw('USER'),
        'user': kw('user'),
        'USER_INT': kw('USER_INT'),
        'user_task': B,
        'useragent': A,
        'USERDEF1': B,
        'USERDEF1RO': B,
        'USERDEF2': B,
        'USERDEF2RO': B,
        'userdefined': kw('userdefined'),
        'userFunc': A,
        'userfunction': kw('userfunction'),
        'usergroup': B,
        'userid': kw('userid'),
        'userProc': kw('userProc'),
        'USR': B,
        'USRRO': B,
        'value': kw('value'),
        'valueArray': kw('valueArray'),
        'version': A,
        'view': A,
        'wave': kw('wave'),
        'web_func': B,
        'web_info': B,
        'web_layout': B,
        'web_list': B,
        'web_ts': kw('web_ts'),
        'where': kw('where'),
        'width': kw('width'),
        'wiz': kw('wiz'),
        'wordSpacing': kw('wordSpacing'),
        'workArea': kw('workArea'),
        'workOnSubpart': A,
        'wrap': kw('wrap'),
        'wrap1': kw('wrap1'),
        'wrap2': kw('wrap2'),
        'wrap3': kw('wrap3'),
        'wrapAfterTags': kw('wrapAfterTags'),
        'wrapAlign': kw('wrapAlign'),
        'wrapFieldName': kw('wrapFieldName'),
        'wrapItemAndSub': kw('wrapItemAndSub'),
        'wrapNonWrappedLines': kw('wrapNonWrappedLines'),
        'wraps': kw('wraps'),
        'xhtml_strict': B,
        'xhtml_trans': B,
        'xmlprologue': kw('xmlprologue'),
        'XY': B
      };
    }();

    // eslint-disable-next-line no-useless-escape
    const isOperatorChar = /[\+\-\*\&\%\/=<>!\?]/;
    let inValue = false;

    function readRegexp(stream: StringStream) {
      let escaped = false, next, inSet = false;
      while ((next = stream.next()) !== undefined) {
        if (!escaped) {
          if (next === '/' && !inSet) {return;}
          if (next === '[') {inSet = true;}
          else if (inSet && next === ']') {inSet = false;}
        }
        escaped = !escaped && next === '\\';
      }
    }

    // Used as scratch variables to communicate multiple values without
    // consing up tons of objects.
    let type: string;
    let content: string;

    function ret(tp: string, style?: string, cont?: string): string | undefined {
      type = tp;
      content = cont;
      return style;
    }

    function tokenBase(stream: StringStream, state: State): string | undefined {
      const ch = stream.next();
      if (typeof ch !== 'string') {
        return undefined;
      }

      if (ch === '\n') {
        inValue = false;
      }

      // eslint-disable-next-line no-useless-escape
      if (ch === '.' && stream.match(/^\d+(?:[eE][+\-]?\d+)?/)) {
        return ret('number', 'number');
      }
      if (ch === '.' && stream.match('..')) {
        return ret('spread', 'meta');
      }
      // eslint-disable-next-line no-useless-escape
      if (ch && /[\[\]{}\(\),;\:\.]/.test(ch)) {
        return ret(ch);
      }
      if ((ch === '<' || ch === '>' || ch === '.' || (ch === '=' && stream.peek() !== '<'))) {
        inValue = true;
        return ret(ch, 'operator');
      }
      // eslint-disable-next-line no-useless-escape
      if (!inValue && ch && /[\[\]\(\),;\:\.\<\>\=]/.test(ch)) {
        return ret(ch, 'operator');
      }
      if (ch === '0' && stream.eat(/x/i)) {
        stream.eatWhile(/[\da-f]/i);
        return ret('number', 'number');
      }
      if (ch === '0' && stream.eat(/o/i)) {
        stream.eatWhile(/[0-7]/i);
        return ret('number', 'number');
      }
      if (ch === '0' && stream.eat(/b/i)) {
        stream.eatWhile(/[01]/i);
        return ret('number', 'number');
      }
      if (ch && /\d/.test(ch)) {
        // eslint-disable-next-line no-useless-escape
        stream.match(/^\d*(?:\.\d+)?(?:[eE][+\-]?\d+)?/);
        return ret('number', 'number');
      }
      if (ch === '/') {
        if (stream.eat('*')) {
          state.tokenize = tokenComment;
          return tokenComment(stream, state);
        }
        if (stream.eat('/')) {
          stream.skipToEnd();
          return ret('comment', 'comment');
        }
        if (expressionAllowed(stream, state)) {
          readRegexp(stream);
          stream.match(/^\b(([gimyu])(?![gimyu]*\2))+\b/);
          return ret('regexp', 'string-2');
        }

        stream.eatWhile(isOperatorChar);
        return ret('operator', 'operator', stream.current());
      }
      if (ch === '#') {
        stream.skipToEnd();
        return ret('comment', 'comment');
      }
      if (isOperatorChar.test(ch)) {
        if (ch !== '>' || !state.lexical || state.lexical.type !== '>') {
          stream.eatWhile(isOperatorChar);
        }
        return ret('operator', 'operator', stream.current());
      }
      if (wordRE.test(ch)) {
        stream.eatWhile(wordRE);
        const word = stream.current();
        // eslint-disable-next-line no-prototype-builtins
        if (keywords.propertyIsEnumerable(word)) {
          const kw = keywords[word];
          return ret(kw.type, kw.style, word);
        }
        if (inValue) {
          return ret('string', 'string', word);
        }
        return ret('variable', undefined, word);
      }

      return undefined;
    }

    function tokenComment(stream: StringStream, state: State) {
      let maybeEnd = false, ch;
      while ((ch = stream.next()) !== undefined) {
        if (ch === '/' && maybeEnd) {
          state.tokenize = tokenBase;
          break;
        }
        maybeEnd = (ch === '*');
      }
      return ret('comment', 'comment');
    }

    // Parser

    const atomicTypes = {
      'atom': true,
      'number': true,
      'variable': true,
      'string': true,
      'regexp': true
    };

    function inScope(state: State, varname: string): CombinatorReturnType {
      for (let v = state.localVars; v; v = v.next) {
        if (v.name == varname) {
          return true;
        }
      }
      for (let cx = state.context; cx; cx = cx.prev) {
        for (let v = cx.vars; v; v = v.next) {
          if (v.name == varname) {
            return true;
          }
        }
      }

      return undefined;
    }

    const cx: CX = {
      state: null,
      column: null,
      marked: null,
      cc: null
    };

    function parseTS(state: State, style: string, type: Type, content: string, stream: StringStream): string {
      const cc = state.cc;
      // Communicate our context to the combinators.
      // (Less wasteful than consing up a hundred closures on every call.)
      cx.state = state;
      cx.stream = stream;
      cx.marked = null;
      cx.cc = cc;
      cx.style = style;

      // eslint-disable-next-line no-prototype-builtins
      if (!state.lexical.hasOwnProperty('align')) {
        state.lexical.align = true;
      }

      while (true) {
        const combinator = cc.length ? cc.pop() : statement;
        if (typeof combinator === 'function' && combinator(type, content)) {
          while (cc.length && cc[cc.length - 1] && (cc[cc.length - 1] as CombinatorLexFunction).lex) {
            cc.pop()();
          }
          if (cx.marked) {
            return cx.marked;
          }
          if (type === 'variable' && inScope(state, content)) {
            return 'variable-2';
          }
          return style;
        }
      }
    }

    // Combinator utils

    function pass(...args: CC[]): undefined {
      for (let i = args.length - 1; i >= 0; i--) {
        cx.cc.push(args[i]);
      }
      return undefined;
    }

    function cont(...args: CC[]): CombinatorReturnType {
      pass(...args);
      return true;
    }

    function register(varname: string): void {
      function inList(list: LocalVars): boolean {
        for (let v = list; v; v = v.next) {
          if (v.name == varname) {
            return true;
          }
        }
        return false;
      }

      const state = cx.state;
      cx.marked = 'def';
      if (state.context) {
        if (inList(state.localVars)) {
          return;
        }
        state.localVars = { name: varname, next: state.localVars };
      }
    }

    // Combinators

    function pushlex(type: Type, info?: number | string): CombinatorLexFunction {
      const result: CombinatorLexFunction = function(): CombinatorReturnType {
        const state = cx.state;
        let indent = state.indented;
        if (state.lexical.type === 'stat') {
          indent = state.lexical.indented;
        } else {
          for (let outer = state.lexical; outer && outer.type === ')' && outer.align; outer = outer.prev) {
            indent = outer.indented;
          }
        }
        state.lexical = new TSLexical(indent, cx.stream.column(), type, null, state.lexical, info);
        return undefined;
      };
      result.lex = true;
      return result;
    }

    function poplex(): CombinatorReturnType {
      const state = cx.state;
      if (state.lexical.prev) {
        if (state.lexical.type === ')') {
          state.indented = state.lexical.indented;
        }
        state.lexical = state.lexical.prev;
      }
      return undefined;
    }
    poplex.lex = true;

    function expect(wanted: Type): CombinatorFunction {
      function exp(type: Type): CombinatorReturnType {
        if (type == wanted) {
          return cont();
        } else if (wanted === ';') {
          return pass();
        } else {
          return cont(exp);
        }
      }
      return exp;
    }

    function statement(type: Type, value: Value): CombinatorReturnType {
      if (type === 'keyword a') {
        return cont(pushlex('form'), parenExpr, statement, poplex);
      }
      if (type === 'keyword b') {
        return cont(pushlex('form'), statement, poplex);
      }
      if (type === '{') {
        return cont(pushlex('}'), block, poplex);
      }
      if (type === ';') {
        return cont();
      }
      if (type === 'variable') {
        return cont(pushlex('stat'), maybelabel);
      }
      if (type === 'import') {
        return cont(pushlex('stat'), afterImport, poplex);
      }
      if (value === '@') {
        return cont(expression, statement);
      }
      return pass(pushlex('stat'), expression, expect(';'), poplex);
    }

    function expression(type: Type): CombinatorReturnType {
      return expressionInner(type, false);
    }

    function expressionNoComma(type: Type): CombinatorReturnType {
      return expressionInner(type, true);
    }

    function parenExpr(type: Type): CombinatorReturnType {
      if (type !== '(') {
        return pass();
      }
      return cont(pushlex(')'), expression, expect(')'), poplex);
    }

    function expressionInner(type: Type, noComma: boolean) {
      const maybeop = noComma ? maybeoperatorNoComma : maybeoperatorComma;
      // eslint-disable-next-line no-prototype-builtins
      if (atomicTypes.hasOwnProperty(type)) {
        return cont(maybeop);
      }
      if (type === 'keyword c') {
        return cont(noComma ? maybeexpressionNoComma : maybeexpression);
      }
      if (type === '(') {
        return cont(pushlex(')'), maybeexpression, expect(')'), poplex, maybeop);
      }
      if (type === 'operator') {
        return cont(noComma ? expressionNoComma : expression);
      }
      if (type === '{') {
        return contCommasep(objprop, '}', null, maybeop);
      }
      return cont();
    }

    function maybeexpression(type: Type): CombinatorReturnType {
      // eslint-disable-next-line no-useless-escape
      if (type.match(/[;\}\)\],]/)) {
        return pass();
      }
      return pass(expression);
    }

    function maybeexpressionNoComma(type: Type): CombinatorReturnType {
      // eslint-disable-next-line no-useless-escape
      if (type.match(/[;\}\)\],]/)) {return pass();}
      return pass(expressionNoComma);
    }

    function maybeoperatorComma(type: Type, value: Value): CombinatorReturnType {
      if (type === ',') {return cont(expression);}
      return maybeoperatorNoComma(type, value, false);
    }

    function maybeoperatorNoComma(type: Type, value: Value, noComma?: boolean): CombinatorReturnType {
      const me = noComma == false ? maybeoperatorComma : maybeoperatorNoComma;
      const expr = noComma == false ? expression : expressionNoComma;
      if (type === 'operator') {
        if (/\+\+|--/.test(value)) {return cont(me);}
        if (value === '?') {return cont(expression, expect(':'), expr);}
        return cont(expr);
      }
      if (type === ';') {
        return undefined;
      }
      if (type === '(') {
        return contCommasep(expressionNoComma, ')', 'call', me);
      }
      if (type === '.') {
        return cont(property, me);
      }
      if (type === '[') {
        return cont(pushlex(']'), maybeexpression, expect(']'), poplex, me);
      }
      return undefined;
    }

    function maybelabel(type: Type): CombinatorReturnType {
      if (type === ':') {
        return cont(poplex, statement);
      }
      return pass(maybeoperatorComma, expect(';'), poplex);
    }

    function property(type: Type): CombinatorReturnType {
      if (type === 'variable') {
        cx.marked = 'property';
        return cont();
      }
      return undefined;
    }

    function objprop(type: Type): CombinatorReturnType {
      if (type === 'async') {
        cx.marked = 'property';
        return cont(objprop);
      } else if (type === 'variable' || cx.style === 'keyword') {
        cx.marked = 'property';
        return cont(afterprop);
      } else if (type === 'number' || type === 'string') {
        cx.marked = cx.style + ' property';
        return cont(afterprop);
      } else if (type === 'modifier') {
        return cont(objprop);
      } else if (type === ':') {
        return pass(afterprop);
      }
      return undefined;
    }

    function afterprop(type: Type): CombinatorReturnType {
      if (type === ':') {
        return cont(expressionNoComma);
      }
      return undefined;
    }

    function commasep(what: CC, end: Type, sep?: string): CombinatorFunction {
      function proceed(type: Type, value: Value): CombinatorReturnType {
        if (sep ? sep.indexOf(type) > -1 : type === ',') {
          const lex = cx.state.lexical;
          if (lex.info === 'call') {
            lex.pos = (lex.pos || 0) + 1;
          }
          return cont(
            function(type: Type, value: Value): CombinatorReturnType {
              if (type == end || value == end) {
                return pass();
              }
              return pass(what);
            },
            proceed
          );
        }
        if (type == end || value == end) {
          return cont();
        }
        return cont(expect(end));
      }

      return function(type, value): CombinatorReturnType {
        if (type == end || value == end) {
          return cont();
        }
        return pass(what, proceed);
      };
    }

    function contCommasep(what: CC, end: Type, info?: string, ...rest: CC[]): CombinatorReturnType {
      for (const combinator of rest) {
        cx.cc.push(combinator);
      }
      return cont(pushlex(end, info), commasep(what, end), poplex);
    }

    function block(type: Type): CombinatorReturnType {
      if (type === '}') {
        return cont();
      }
      return pass(statement, block);
    }

    function pattern(type: Type, value: Value): CombinatorReturnType {
      if (type === 'modifier') {
        return cont(pattern);
      }
      if (type === 'variable') {
        register(value);
        return cont();
      }
      if (type === '{') {
        return contCommasep(proppattern, '}');
      }
      return undefined;
    }

    function proppattern(type: Type, value: Value): CombinatorReturnType {
      if (type === 'variable' && !cx.stream.match(/^\s*:/, false)) {
        register(value);
        return cont(maybeAssign);
      }
      if (type === 'variable') {
        cx.marked = 'property';
      }
      if (type === '}') {
        return pass();
      }
      return cont(expect(':'), pattern, maybeAssign);
    }

    function maybeAssign(_type: Type, value: Value): CombinatorReturnType {
      if (value === '=') {
        return cont(expressionNoComma);
      }
      return undefined;
    }


    function afterImport(type: Type): CombinatorReturnType {
      if (type === 'string') {
        return cont();
      }
      return pass(importSpec, maybeMoreImports, maybeFrom);
    }

    function importSpec(type: Type, value: Value): CombinatorReturnType {
      if (type === '{') {
        return contCommasep(importSpec, '}');
      }
      if (type === 'variable') {
        register(value);
      }
      if (value === '*') {
        cx.marked = 'keyword';
      }
      return cont(maybeAs);
    }

    function maybeMoreImports(type: Type): CombinatorReturnType {
      if (type === ',') {
        return cont(importSpec, maybeMoreImports);
      }
      return undefined;
    }

    function maybeAs(_type: Value, value: Value): CombinatorReturnType {
      if (value === 'as') {
        cx.marked = 'keyword';
        return cont(importSpec);
      }
      return undefined;
    }

    function maybeFrom(_type: Type, value: Value): CombinatorReturnType {
      if (value === 'from') {
        cx.marked = 'keyword';
        return cont(expression);
      }
      return undefined;
    }

    function isContinuedStatement(state: State, textAfter: string): CombinatorReturnType {
      return state.lastType === 'operator' || state.lastType === ',' ||
        isOperatorChar.test(textAfter.charAt(0)) ||
        /[,.]/.test(textAfter.charAt(0));
    }

    // Interface

    class TypoScriptStreamParser implements StreamParser<State> {
      public readonly electricInput: RegExp = /^\s*(?:case .*?:|default:|\{|\})$/;
      public readonly blockCommentStart: string = '/*';
      public readonly blockCommentEnd: string = '*/';
      public readonly lineComment: string = '#';
      public readonly fold: string = 'brace';
      public readonly closeBrackets: string = '(){}\'\'""`';
      public readonly helperType: string = 'typoscript';

      public readonly name: string = 'TypoScript';

      public startState(indentUnit: number): State {
        return {
          tokenize: tokenBase,
          lastType: 'sof',
          cc: [],
          lexical: new TSLexical(-indentUnit, 0, 'block', false),
          localVars: parserConfig.localVars,
          context: parserConfig.localVars && { vars: parserConfig.localVars },
          indented: 0
        };
      }

      public token(stream: StringStream, state: State): string | null {
        if (stream.sol()) {
          // eslint-disable-next-line no-prototype-builtins
          if (!state.lexical.hasOwnProperty('align')) {
            state.lexical.align = false;
          }
          state.indented = stream.indentation();
        }
        if (state.tokenize != tokenComment && stream.eatSpace()) {
          return null;
        }
        const style = state.tokenize(stream, state);
        if (type === 'comment') {
          return style;
        }
        state.lastType = type === 'operator' && (content === '++' || content === '--') ? 'incdec' : type;
        return parseTS(state, style, type, content, stream);
      }

      public indent(state: State, textAfter: string, cx: IndentContext): number | null {
        if (state.tokenize == tokenComment) {
          // pass
          return null;
        }
        if (state.tokenize != tokenBase) {
          return 0;
        }
        const firstChar = textAfter && textAfter.charAt(0);
        let lexical = state.lexical;
        let top;
        while (
          (lexical.type === 'stat' || lexical.type === 'form') && (
            firstChar === '}' || (
              (top = state.cc[state.cc.length - 1]) &&
              (top == maybeoperatorComma || top == maybeoperatorNoComma) &&
              // eslint-disable-next-line no-useless-escape
              !/^[,\.=+\-*:?[\(]/.test(textAfter)
            )
          )
        ) {
          lexical = lexical.prev;
        }
        if (statementIndent && lexical.type === ')' && lexical.prev.type === 'stat') {
          lexical = lexical.prev;
        }
        const type = lexical.type;
        const closing = firstChar == type;

        if (type === 'form' && firstChar === '{') {
          return lexical.indented;
        } else if (type === 'form') {
          return lexical.indented + cx.unit;
        } else if (type === 'stat') {
          return lexical.indented + (isContinuedStatement(state, textAfter) ? statementIndent || cx.unit : 0);
        } else if (lexical.info === 'switch' && !closing && parserConfig.doubleIndentSwitch != false) {
          return lexical.indented + (/^(?:case|default)\b/.test(textAfter) ? cx.unit : 2 * cx.unit);
        } else if (lexical.align) {
          return lexical.column + (closing ? 0 : 1);
        } else {
          return lexical.indented + (closing ? 0 : cx.unit);
        }
      }

      public expressionAllowed(stream: StringStream, state: State): boolean {
        return expressionAllowed(stream, state);
      }
    }

    return new TypoScriptStreamParser();
  }
}
