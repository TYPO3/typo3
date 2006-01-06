
var HTMLAreaeditor;
InsertSmiley=function(editor){
this.editor=editor;
var cfg=editor.config;
var actionHandlerFunctRef=InsertSmiley.actionHandler(this);
cfg.registerButton("InsertSmiley",InsertSmiley_langArray["Insert Smiley"],editor.imgURL("ed_smiley.gif","InsertSmiley"),false,actionHandlerFunctRef);
};
InsertSmiley.I18N=InsertSmiley_langArray;
InsertSmiley.actionHandler=function(instance){
return(function(editor){
instance.buttonPress(editor);
});
};
InsertSmiley.prototype.buttonPress=function(editor){
var sel=editor.getSelectedHTML().replace(/(<[^>]*>|&nbsp;|\n|\r)/g,"");
var param=new Object();
param.editor=editor;
param.editor_url=_typo3_host_url+_editor_url;
if(param.editor_url=="../"){
param.editor_url=document.URL;
param.editor_url=param.editor_url.replace(/^(.*\/).*\/.*$/g,"$1");
}
var setTagHandlerFunctRef=InsertSmiley.setTagHandler(this);
editor._popupDialog("plugin://InsertSmiley/insertsmiley",setTagHandlerFunctRef,param,250,220);
};
InsertSmiley.setTagHandler=function(instance){
return(function(param){
if(param&&typeof(param.imgURL)!="undefined"){
instance.editor.focusEditor();
instance.editor.insertHTML("<img src=\"" + param.imgURL + "\" alt=\"Smiley\" />");
}
});
};
InsertSmiley._pluginInfo={
name:"InsertSmiley",
version:"1.1",
developer:"Ki Master George & Stanislas Rolland",
developer_url:"http://www.fructifor.ca/",
c_owner:"Ki Master George & Stanislas Rolland",
sponsor:"Ki Master George & Fructifor Inc.",
sponsor_url:"http://www.fructifor.ca/",
license:"GPL"
};

