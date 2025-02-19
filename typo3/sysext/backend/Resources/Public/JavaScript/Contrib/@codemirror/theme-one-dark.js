import{EditorView as C}from"@codemirror/view";import{HighlightStyle as y,syntaxHighlighting as B}from"@codemirror/language";import{tags as o}from"@lezer/highlight";const d="#e5c07b",c="#e06c75",m="#56b6c2",g="#ffffff",r="#abb2bf",e="#7d8799",s="#61afef",b="#98c379",a="#d19a66",p="#c678dd",f="#21252b",l="#2c313a",n="#282c34",t="#353a42",u="#3E4451",i="#528bff",v={chalky:d,coral:c,cyan:m,invalid:g,ivory:r,stone:e,malibu:s,sage:b,whiskey:a,violet:p,darkBackground:f,highlightBackground:l,background:n,tooltipBackground:t,selection:u,cursor:i},k=C.theme({"&":{color:r,backgroundColor:n},".cm-content":{caretColor:i},".cm-cursor, .cm-dropCursor":{borderLeftColor:i},"&.cm-focused > .cm-scroller > .cm-selectionLayer .cm-selectionBackground, .cm-selectionBackground, .cm-content ::selection":{backgroundColor:u},".cm-panels":{backgroundColor:f,color:r},".cm-panels.cm-panels-top":{borderBottom:"2px solid black"},".cm-panels.cm-panels-bottom":{borderTop:"2px solid black"},".cm-searchMatch":{backgroundColor:"#72a1ff59",outline:"1px solid #457dff"},".cm-searchMatch.cm-searchMatch-selected":{backgroundColor:"#6199ff2f"},".cm-activeLine":{backgroundColor:"#6699ff0b"},".cm-selectionMatch":{backgroundColor:"#aafe661a"},"&.cm-focused .cm-matchingBracket, &.cm-focused .cm-nonmatchingBracket":{backgroundColor:"#bad0f847"},".cm-gutters":{backgroundColor:n,color:e,border:"none"},".cm-activeLineGutter":{backgroundColor:l},".cm-foldPlaceholder":{backgroundColor:"transparent",border:"none",color:"#ddd"},".cm-tooltip":{border:"none",backgroundColor:t},".cm-tooltip .cm-tooltip-arrow:before":{borderTopColor:"transparent",borderBottomColor:"transparent"},".cm-tooltip .cm-tooltip-arrow:after":{borderTopColor:t,borderBottomColor:t},".cm-tooltip-autocomplete":{"& > ul > li[aria-selected]":{backgroundColor:l,color:r}}},{dark:!0}),h=y.define([{tag:o.keyword,color:p},{tag:[o.name,o.deleted,o.character,o.propertyName,o.macroName],color:c},{tag:[o.function(o.variableName),o.labelName],color:s},{tag:[o.color,o.constant(o.name),o.standard(o.name)],color:a},{tag:[o.definition(o.name),o.separator],color:r},{tag:[o.typeName,o.className,o.number,o.changed,o.annotation,o.modifier,o.self,o.namespace],color:d},{tag:[o.operator,o.operatorKeyword,o.url,o.escape,o.regexp,o.link,o.special(o.string)],color:m},{tag:[o.meta,o.comment],color:e},{tag:o.strong,fontWeight:"bold"},{tag:o.emphasis,fontStyle:"italic"},{tag:o.strikethrough,textDecoration:"line-through"},{tag:o.link,color:e,textDecoration:"underline"},{tag:o.heading,fontWeight:"bold",color:c},{tag:[o.atom,o.bool,o.special(o.variableName)],color:a},{tag:[o.processingInstruction,o.string,o.inserted],color:b},{tag:o.invalid,color:g}]),x=[k,B(h)];export{v as color,x as oneDark,h as oneDarkHighlightStyle,k as oneDarkTheme};
