/***************************************************************
*
*  JavaScript DHTML layer menu
*
* $Id: jsfunc.layermenu.js 5165 2009-03-09 18:28:59Z ohader $
*
*
*
*  Copyright notice
*
*  (c) 1998-2010 Kasper Skårhøj
*  All rights reserved
*
*  This script is part of the TYPO3 tslib/ library provided by
*  Kasper Skårhøj <kasper@typo3.com> together with TYPO3
*
*  Released under GNU/GPL (see license file in tslib/)
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
*  This copyright notice MUST APPEAR in all copies of this script
***************************************************************/

var GLV_x=0;
var GLV_y=0;
var GLV_curLayerWidth = new Array();
var GLV_curLayerHeight = new Array();
var GLV_curLayerX = new Array();
var GLV_curLayerY = new Array();
var GLV_menuOn = new Array();
var GLV_gap = new Array();
var GLV_currentLayer = new Array();
var GLV_currentROitem = new Array();
var GLV_hasBeenOver = new Array();
var GLV_doReset = new Array();
var GLV_lastKey = new Array();
var GLV_menuXY = new Array();
var GLV_date = new Array();
var GLV_timeout = new Array();
var GLV_timeoutRef = new Array();
var GLV_onlyOnLoad = new Array();
var GLV_dontHideOnMouseUp = new Array();
var GLV_date = new Date();
var GLV_restoreMenu = new Array();
var GLV_timeout_count=0;
var GLV_timeout_pointers = new Array();
var GLV_dontFollowMouse = new Array();

	//browsercheck...
function GL_checkBrowser(){
	this.dom= (document.getElementById);

		// detect version (even if Opera disguises as Mozilla or IE)
	if (op = (navigator.userAgent.indexOf("Opera")>-1))	{
		switch (parseInt(navigator.userAgent.substr(navigator.userAgent.indexOf("Opera")+6)))	{
			case 5:
			case 6:
				this.op5= true;
				break;
			case 7:
			case 8:
				this.op7= true;
				break;
			default:
				this.op4= true;
		}
	}
	this.konq=  (navigator.userAgent.indexOf("Konq")>-1);
	this.ie4= (document.all && !this.dom && !op && !this.konq);
	this.ie5= (document.all && this.dom && !op && !this.konq);
	this.ie6= (this.ie5);
	this.ns4= (document.layers && !this.dom && !this.konq);
	this.ns5= (!document.all && this.dom && !op && !this.konq);
	this.ns6= (this.ns5);
	this.bw=  (this.ie4 || this.ie5 || this.ns4 || this.ns6 || this.konq || op);

	return this;
}
bw= new GL_checkBrowser();

	//NS4 resize fix.
if(document.layers){
    scrX= innerWidth; scrY= innerHeight;
    onresize= function()	{if(scrX!= innerWidth || scrY!= innerHeight)	{history.go(0);}};
}
	//Returns css
function GL_getObjCss(obj){
	return bw.dom? document.getElementById(obj).style:bw.ie4?document.all[obj].style:bw.ns4?document.layers[obj]:0;
};
function GL_isObjCss(obj){
	flag = bw.dom? document.getElementById(obj):bw.ie4?document.all[obj]:bw.ns4?document.layers[obj]:0;
	if (flag)	return true;
};
function GL_getObj(obj){
	nest="";
	this.el= bw.ie4?document.all[obj]:bw.ns4?eval(nest+"document."+obj):document.getElementById(obj);
	this.css= bw.ns4?this.el:this.el.style;
	this.ref= bw.ns4?this.el.document:document;
	this.x= (bw.ns4||bw.op5)?this.css.left:this.el.offsetLeft;
	this.y= (bw.ns4||bw.op5)?this.css.top:this.el.offsetTop;
	this.height= (bw.ie4||bw.ie5||bw.ns6||bw.op7)?this.el.offsetHeight:bw.ns4?this.ref.height:bw.op4?this.css.pixelHeight:0;
	this.width= (bw.ie4||bw.ie5||bw.ns6||bw.op7)?this.el.offsetWidth:bw.ns4?this.ref.width:bw.op4?this.css.pixelWidth:0;

	return this;
}
function GL_initLayers() {
	if(bw.ns4) document.captureEvents(Event.MOUSEMOVE);
	GL_timeout_func();
}
function GL_updateTime(WMid)	{
	GLV_date = new Date();
	GLV_timeout[WMid] = GLV_date.getTime();
}
function GL_doResetAll(WMid) {
	var resetSubMenus="";
	eval('resetSubMenus = GL'+WMid+'_resetSubMenus();');
	if (GLV_doReset[WMid] && resetSubMenus) {
		GLV_doReset[WMid] = false;
		GL_hideAll(WMid);
		if (GLV_onlyOnLoad[WMid])	GL_restoreMenu(WMid);
	}
}
function GL_timeout_func()	{
	GLV_date = new Date();
	var stuff="";
	for (var a=0;a<GLV_timeout_count;a++)	{
		WMid = GLV_timeout_pointers[a];
		if (GLV_date.getTime()-GLV_timeout[WMid] > GLV_timeoutRef[WMid])	{
			GL_doResetAll(WMid);
		}
	}
//window.status = GLV_date.getTime()-GLV_timeout[WMid]-GLV_timeoutRef[WMid]
	window.setTimeout("GL_timeout_func();",200);
}
function GL_resetAll(WMid) {
	if (!GLV_doReset[WMid]) {
		GL_updateTime(WMid);
		GLV_doReset[WMid] = true;
	}
}
function GL_mouseUp(WMid,e) {
	GLV_doReset[WMid] = false;
	if (!GLV_dontHideOnMouseUp[WMid])	{
		GL_hideAll(WMid);
		if (GLV_onlyOnLoad[WMid])	GL_restoreMenu(WMid);
	}
}
function GL_stopMove(WMid) {
	GLV_menuOn[WMid] = null;
}
function GL_restoreMenu(WMid)	{
	eval('GL'+WMid+'_restoreMenu()');
}
function GL_doTop(WMid,id) {
	GL_hideAll(WMid);
	if (GL_isObjCss(id))	{
		GLV_menuOn[WMid] = GL_getObjCss(id);
		GLV_menuOn[WMid].visibility = "visible";

		eval('GL'+WMid+'_doTop(WMid,id)');

		var layerObj = GL_getObj(id);
		GLV_curLayerHeight[WMid] = layerObj.height;
		GLV_curLayerWidth[WMid] = layerObj.width;
		GLV_curLayerX[WMid] = layerObj.x;
		GLV_curLayerY[WMid]  = layerObj.y;
		GLV_currentLayer[WMid] = id;
		GLV_hasBeenOver[WMid]=0;
	}
}
	//Capturing mousemove
function GL_getMouse(e) {
	GLV_x= (bw.ns4||bw.ns5)?e.pageX:(bw.ie4||bw.op4)?event.x:(event.x-2)+document.body.scrollLeft;
	GLV_y= (bw.ns4||bw.ns5)?e.pageY:(bw.ie4||bw.op4)?event.y:(event.y-2)+document.body.scrollTop;
}
function GL_mouseMoveEvaluate(WMid)	{
	if (GLV_gap[WMid] && GLV_currentLayer[WMid]!=null)	{
		if (	GLV_x+GLV_gap[WMid]-GLV_curLayerX[WMid] <0 || GLV_y+GLV_gap[WMid]-GLV_curLayerY[WMid] <0 || GLV_curLayerX[WMid]+GLV_curLayerWidth[WMid]+GLV_gap[WMid]-GLV_x <0 || GLV_curLayerY[WMid]+GLV_curLayerHeight[WMid]+GLV_gap[WMid]-GLV_y <0)	{
			if (GLV_hasBeenOver[WMid])	{
				GLV_doReset[WMid]=true;
			}
		} else {
			GL_updateTime(WMid);
			GLV_hasBeenOver[WMid]=1;
			GLV_doReset[WMid]=false;	// Added 120902: When on the layer we do not want the layer to be reset...
		}
	}
}
function GL_hideAll(WMid)	{
	GLV_doReset[WMid] = false;
	GLV_currentLayer[WMid] = null;
	if (GL_isObjCss(GLV_lastKey[WMid]) && GL_getObjCss(GLV_lastKey[WMid]))	{	eval('GL'+WMid+'_hideCode()');	}
	GLV_hasBeenOver[WMid]=0;
}

function GL_iframer(WMid,id,state)	{
	if (bw.ie4||bw.ie5) {
		ifrmObj = bw.ie4?document.all["Iframe"+WMid]:document.getElementById("Iframe"+WMid);
		if (state) {
			parentObj = bw.ie4?document.all[id]:document.getElementById(id);
			ifrmObj.style.filter='Alpha(opacity=0)';
			ifrmObj.style.width = parentObj.offsetWidth + "px";
			ifrmObj.style.height = parentObj.offsetHeight + "px";
			ifrmObj.style.left = parentObj.offsetLeft + "px";
			ifrmObj.style.top = parentObj.offsetTop + "px";
			ifrmObj.style.zIndex = parentObj.style.zIndex-1;
			ifrmObj.style.display = "";
		}
		else ifrmObj.style.display = "none";
	}
}

