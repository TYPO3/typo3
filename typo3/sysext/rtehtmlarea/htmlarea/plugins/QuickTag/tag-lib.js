/*
  TAG Library for QuickTag Plugin  
  -------------------------------
  
  allTags = All tags that appears in the first dropdown ('TAGS') {'caption': 'value'}  
  tagLib = The tags with options (just to check if current TAG have options) {'[TAG]': true}
  subTagLib = Complements for some tags that needs it (TABLE) 
    {'[TAG]': {'op': 'after tag open', 'cl': 'before tag close'}}
  opTag_all = Common attributes to all TAGS {'caption': 'value'}  
  opAtt_all = Options for the common attributes {'attribute': {'caption': 'value'}}
  opTag_[TAG] = Attributes for [TAG] {'caption': 'value'}
  opAtt_[TAG] = Options for the [TAG] attributes {'attribute': {'caption': 'value'}}
  
*/

var allTags = {
'a': 'a',
'abbr': 'abbr',
'acronym': 'acronym',
'address': 'address',
'b': 'b',
'big': 'big',
'blockquote': 'blockquote',
'cite': 'cite',
'code': 'code',
'div': 'div',
'em': 'em',
'fieldset': 'fieldset',
'font': 'font',
'h1': 'h1',
'h2': 'h2',
'h3': 'h3',
'h4': 'h4',
'h5': 'h5',
'h6': 'h6',
'i': 'i',
'legend': 'legend',
'li': 'li',
'ol': 'ol',
'ul': 'ul',
'p': 'p',
'pre': 'pre',
'q': 'q',
'small': 'small',
'span': 'span',
'strong': 'strong',
'sub': 'sub',
'sup': 'sup',
'table': 'table',
'tt': 'tt'
};

// tags with options
var tagLib =  {
	'a': true,
	'div': true,
	'font': true,
	'h1': true,
	'h2': true,
	'h3': true,
	'h4': true,
	'h5': true,
	'h6': true,
	'p': true,
	'table': true
};
// tags that needs some complement
var subTagLib = {'table': {'op': '<tbody><tr><td>',
                           'cl': '</td></tr></tbody>'}
};

var opTag_a = {
'href': 'href="',
'name': 'name="',
'target': 'target="'
};
var opAtt_a = {
'href': {'http://': 'http://',
         'https://': 'https://',
         'ftp://': 'ftp://',
         'mailto:': 'mailto:',
         '#': '#"'},
'target': {'_top': '_top"',
           '_self': '_self"',
           '_parent': '_parent"',
           '_blank': '_blank"'}
};

var opTag_font = {
'face': 'face="',
'size': 'size="',
'color': 'color="'
};
var opAtt_font = {
'face': {'Verdana': 'Verdana"',
         'Arial': 'Arial"',
         'Tahoma': 'Tahoma"',
         'Courier New': 'Courier New"',
         'Times New Roman': 'Times New Roman"'},
'size': {'1': '1"','2': '2"','3': '3"','4': '4"','5': '5"','6': '6"',
         '+1': '+1"','+2': '+2"','+3': '+3"','+4': '+4"','+5': '+5"','+6': '+6"',
         '-1': '-1"','-2': '-2"','-3': '-3"','-4': '-4"','-5': '-5"','-6': '-6"'}
};

var opTag_div = {
'align': 'align="'
};
var opAtt_div = {
'align': {'center': 'center"',
          'left': 'left"',
          'right': 'right"',
          'justify': 'justify"'}
};

var opTag_h = {
'align': 'align="'
};
var opAtt_h = {
'align': {'center': 'center"',
          'left': 'left"',
          'right': 'right"',
          'justify': 'justify"'}
};

var opTag_p = {
'align': 'align="'
};
var opAtt_p = {
'align': {'center': 'center"',
          'left': 'left"',
          'right': 'right"',
          'justify': 'justify"'}
};

var opTag_table = {
'align': 'align="',
'width': 'width="',
'height': 'height="',
'cellpadding': 'cellpadding="',
'cellspacing': 'cellspacing="',
'background': 'background="',
'bgcolor': 'bgcolor="',
'border': 'border="',
'bordercolor': 'bordercolor="',
'bordercolorlight': 'bordercolorlight="',
'bordercolordark': 'bordercolordark="'
};
var opAtt_table = {
'align': {'center': 'center"',
          'left': 'left"',
          'right': 'right"'}
};

// for all tags 
var opTag_all = {
	'class': 'class="',
	'dir': 'dir="',
	'id': 'id="',
	'lang': 'lang="',
	'onFocus': 'onFocus="',
	'onBlur': 'onBlur="',
	'onClick': 'onClick="',
	'onDblClick': 'onDblClick="',
	'onMouseDown': 'onMouseDown="',
	'onMouseUp': 'onMouseUp="',
	'onMouseOver': 'onMouseOver="',
	'onMouseMove': 'onMouseMove="',
	'onMouseOut': 'onMouseOut="',
	'onKeyPress': 'onKeyPress="',
	'onKeyDown': 'onKeyDown="',
	'onKeyUp': 'onKeyUp="',
	'style': 'style="',
	'title': 'title="',
	'xml:lang' : 'xml:lang="'
};
var opAtt_all = {
'class': {},
'dir': {'rtl': 'rtl"','ltr': 'ltr"'},
'lang': {'Afrikaans ': 'af"',
         'Albanian ': 'sq"',
         'Arabic ': 'ar"',
         'Basque ': 'eu"',
         'Breton ': 'br"',
         'Bulgarian ': 'bg"',
         'Belarusian ': 'be"',
         'Catalan ': 'ca"',
         'Chinese ': 'zh"',
         'Croatian ': 'hr"',
         'Czech ': 'cs"',
         'Danish ': 'da"',
         'Dutch ': 'nl"',
         'English ': 'en"',
         'Estonian ': 'et"',
         'Faeroese ': 'fo"',
         'Farsi ': 'fa"',
         'Finnish ': 'fi"',
         'French ': 'fr"',
         'Gaelic ': 'gd"',
         'German ': 'de"',
         'Greek ': 'el"',
         'Hebrew ': 'he"',
         'Hindi ': 'hi"',
         'Hungarian ': 'hu"',
         'Icelandic ': 'is"',
         'Indonesian ': 'id"',
         'Italian ': 'it"',
         'Japanese ': 'ja"',
         'Korean ': 'ko"',
         'Latvian ': 'lv"',
         'Lithuanian ': 'lt"',
         'Macedonian ': 'mk"',
         'Malaysian ': 'ms"',
         'Maltese ': 'mt"',
         'Norwegian ': 'no"',
         'Polish ': 'pl"',
         'Portuguese ': 'pt"',
         'Rhaeto-Romanic ': 'rm"',
         'Romanian ': 'ro"',
         'Russian ': 'ru"',
         'Sami ': 'sz"',
         'Serbian ': 'sr"',
         'Setswana ': 'tn"',
         'Slovak ': 'sk"',
         'Slovenian ': 'sl"',
         'Spanish ': 'es"',
         'Sutu ': 'sx"',
         'Swedish ': 'sv"',
         'Thai ': 'th"',
         'Tsonga ': 'ts"',
         'Turkish ': 'tr"',
         'Ukrainian ': 'uk"',
         'Urdu ': 'ur"',
         'Vietnamese ': 'vi"',
         'Xhosa ': 'xh"',
         'Yiddish ': 'yi"',
         'Zulu': 'zu"'},
'style': {'azimuth': 'azimuth: ',
          'background': 'background: ',
          'background-attachment': 'background-attachment: ',
          'background-color': 'background-color: ',
          'background-image': 'background-image: ',
          'background-position': 'background-position: ',
          'background-repeat': 'background-repeat: ',
          'border': 'border: ',
          'border-bottom': 'border-bottom: ',
          'border-left': 'border-left: ',
          'border-right': 'border-right: ',
          'border-top': 'border-top: ',
          'border-bottom-color': 'border-bottom-color: ',
          'border-left-color': 'border-left-color: ',
          'border-right-color': 'border-right-color: ',
          'border-top-color': 'border-top-color: ',
          'border-bottom-style': 'border-bottom-style: ',
          'border-left-style': 'border-left-style: ',
          'border-right-style': 'border-right-style: ',
          'border-top-style': 'border-top-style: ',
          'border-bottom-width': 'border-bottom-width: ',
          'border-left-width': 'border-left-width: ',
          'border-right-width': 'border-right-width: ',
          'border-top-width': 'border-top-width: ',
          'border-collapse': 'border-collapse: ',
          'border-color': 'border-color: ',
          'border-style': 'border-style: ',
          'border-width': 'border-width: ',
          'bottom': 'bottom: ',
          'caption-side': 'caption-side: ',
          'cell-spacing': 'cell-spacing: ',
          'clear': 'clear: ',
          'clip': 'clip: ',
          'color': 'color: ',
          'column-span': 'column-span: ',
          'content': 'content: ',
          'cue': 'cue: ',
          'cue-after': 'cue-after: ',
          'cue-before': 'cue-before: ',
          'cursor': 'cursor: ',
          'direction': 'direction: ',
          'display': 'display: ',
          'elevation': 'elevation: ',
          'filter': 'filter: ',
          'float': 'float: ',
          'font-family': 'font-family: ',
          'font-size': 'font-size: ',
          'font-size-adjust': 'font-size-adjust: ',
          'font-style': 'font-style: ',
          'font-variant': 'font-variant: ',
          'font-weight': 'font-weight: ',
          'height': 'height: ',
          '!important': '!important: ',
          'left': 'left: ',
          'letter-spacing': 'letter-spacing: ',
          'line-height': 'line-height: ',
          'list-style': 'list-style: ',
          'list-style-image': 'list-style-image: ',
          'list-style-position': 'list-style-position: ',
          'list-style-type': 'list-style-type: ',
          'margin': 'margin: ',
          'margin-bottom': 'margin-bottom: ',
          'margin-left': 'margin-left: ',
          'margin-right': 'margin-right: ',
          'margin-top': 'margin-top: ',
          'marks': 'marks: ',
          'max-height': 'max-height: ',
          'min-height': 'min-height: ',
          'max-width': 'max-width: ',
          'min-width': 'min-width: ',
          'orphans': 'orphans: ',
          'overflow': 'overflow: ',
          'padding': 'padding: ',
          'padding-bottom': 'padding-bottom: ',
          'padding-left': 'padding-left: ',
          'padding-right': 'padding-right: ',
          'padding-top': 'padding-top: ',
          'page-break-after': 'page-break-after: ',
          'page-break-before': 'page-break-before: ',
          'pause': 'pause: ',
          'pause-after': 'pause-after: ',
          'pause-before': 'pause-before: ',
          'pitch': 'pitch: ',
          'pitch-range': 'pitch-range: ',
          'play-during': 'play-during: ',
          'position': 'position: ',
          'richness': 'richness: ',
          'right': 'right: ',
          'row-span': 'row-span: ',
          'size': 'size: ',
          'speak': 'speak: ',
          'speak-date': 'speak-date: ',
          'speak-header': 'speak-header: ',
          'speak-numeral': 'speak-numeral: ',
          'speak-punctuation': 'speak-punctuation: ',
          'speak-time': 'speak-time: ',
          'speech-rate': 'speech-rate: ',
          'stress': 'stress: ',
          'table-layout': 'table-layout: ',
          'text-align': 'text-align: ',
          'text-decoration': 'text-decoration: ',
          'text-indent': 'text-indent: ',
          'text-shadow': 'text-shadow: ',
          'text-transform': 'text-transform: ',
          'top': 'top: ',
          'vertical-align': 'vertical-align: ',
          'visibility': 'visibility: ',
          'voice-family': 'voice-family: ',
          'volume': 'volume: ',
          'white-space': 'white-space: ',
          'widows': 'widows: ',
          'width': 'width: ',
          'word-spacing': 'word-spacing: ',
          'z-index': 'z-index: ' }
};
opAtt_all["xml:lang"] = opAtt_all["lang"];

// add the common items to all objects
for(var i in tagLib) {
i = i.replace(/^h[1-6]$/,"h"); // h1 .. h6
  for(var j in opTag_all)
    eval('opTag_'+i+'["'+j+'"] = opTag_all["'+j+'"];');
  for(var j in opAtt_all)
    eval('opAtt_'+i+'["'+j+'"] = opAtt_all["'+j+'"];');
}