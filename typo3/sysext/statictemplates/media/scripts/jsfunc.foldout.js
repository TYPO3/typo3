/***************************************************************
*
*  JavaScript DHTML foldout menu
*
* $Id: jsfunc.foldout.js 5165 2009-03-09 18:28:59Z ohader $
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

//object constructor...
function GF_makeMenu(obj,nest,adjustH){
	nest= (!nest)?'':'document.'+nest+'.';
	this.el= bw.ie4?document.all[obj]:bw.ns4?eval(nest+'document.'+obj):document.getElementById(obj);
   	this.css= bw.ns4?this.el:this.el.style;
	this.ref= bw.ns4?this.el.document:document;
	this.x= (bw.ns4||bw.opera)?this.css.left:this.el.offsetLeft;
	this.y= (bw.ns4||bw.opera)?this.css.top:this.el.offsetTop;
	this.height= (bw.ie4||bw.ie5||bw.ns6)?this.el.offsetHeight:bw.ns4?this.ref.height:bw.opera?this.css.pixelHeight:0;
    this.vis= GF_visible;
	this.hideIt= GF_hideIt;
    this.showIt= GF_showIt;
    this.moveIt= GF_moveIt;
    this.moveBy= GF_moveBy;
		// Added by Kasper Skårhøj:
	if (adjustH)	this.height = this.height+adjustH;
	return this
}
//object methods...
function GF_showIt(){this.css.visibility='visible'}
function GF_hideIt(){this.css.visibility='hidden'}
function GF_visible(){if(this.css.visibility=='visible' || this.css.visibility=='VISIBLE' || this.css.visibility=='show') return true;}
function GF_moveIt(x,y){this.x=x; this.y=y; this.css.left=this.x; this.css.top=this.y}
function GF_moveBy(x,y){this.moveIt(this.x+x,this.y+y)}

/************************************************************************************
These functions GF_menu, GF_doMove, and GF_placeAll open and close the menu.
************************************************************************************/
function GF_menu(num){

    //If GFV_stayFolded is false, one fold is open, and it's not the one you clicked, we enter this if structure.
	if(bw.bw && !GFV_active && !GFV_stayFolded && GFV_currentFold!=null && GFV_currentFold!=num){
        GFV_active= true

        //Adjusting the speed of the animation.
        GFV_foldStep1= oSub[num].height/GFV_foldSpeed
        GFV_foldStep2= oSub[GFV_currentFold].height/-GFV_foldSpeed

        //Setting the images correctly, showing the new sub.
        if(GFV_foldImg) oTop[num].ref['imgA'+num].src=GFV_exImg.src
        if(GFV_foldImg) oTop[GFV_currentFold].ref['imgA'+GFV_currentFold].src=GFV_unImg.src
        oSub[num].showIt()

        //Keeping track of what fold is opened, then do the move.
        var temp= GFV_currentFold
        GFV_currentFold= num
        GF_doMove(num+1,temp+1)
    }

    //In the simple cases, we enter this else if structure.
    else if(bw.bw && !GFV_active){
        GFV_active= true

        //If the sub is not visible, show it, change it's image, and expand it.
		if(!oSub[num].vis()){
			oSub[num].showIt()
			if(GFV_foldImg)oTop[num].ref['imgA'+num].src=GFV_exImg.src
            GFV_foldStep1=oSub[num].height/GFV_foldSpeed
            GFV_currentFold=num
            GF_doMove(num+1,null)
		}

        //If the sub is visible, change it's image and collapse it.
        else{
			if(GFV_foldImg)oTop[num].ref['imgA'+num].src=GFV_unImg.src
            GFV_foldStep2=oSub[num].height/-GFV_foldSpeed
            GFV_currentFold=null
            GF_doMove(null,num+1)
		}
	}
}
function GF_doMove(expand,collapse){
    GFV_step++
    if(expand!=null) for(var i=expand;i<oTop.length;i++){ oTop[i].moveBy(0,GFV_foldStep1) }
    if(collapse!=null) for(var i=collapse;i<oTop.length;i++){ oTop[i].moveBy(0,GFV_foldStep2) }

    //If the animation is not done yet, activate GF_doMove again.
    if(GFV_step<GFV_foldSpeed) setTimeout('GF_doMove('+expand+','+collapse+')',GFV_foldTimer)

    //Else clean up and bail out.
    else{
        GFV_step=0
        if(collapse!=null) oSub[collapse-1].hideIt()
        GF_placeAll()
    }
}

//This function places everything at the exact right place and signals that the animation is done.
function GF_placeAll(){
    for(var i=1;i<oTop.length;i++){
        if(oSub[i-1].vis()) oTop[i].moveIt(0,oTop[i-1].y+oTop[i-1].height+oSub[i-1].height)
        else oTop[i].moveIt(0,oTop[i-1].y+oTop[i-1].height)
    }
    GFV_active= false
}
/*********************************************************************
The init function... there should be no need to change anything here.
*********************************************************************/
function GF_initFoldout(){

    //Making the object arrays...
	oTop= new Array()
	oSub= new Array()

    //Making the objects and hiding the subs...
    for(var i=1;i<=GFV_foldNumber+1;i++){
        oTop[i-1]= new GF_makeMenu('divTop'+i,'divCont',GFV_adjustTopHeights);
        oSub[i-1]= new GF_makeMenu('divSub'+i,'divCont.document.divTop'+i, GFV_adjustSubHeights);
        oSub[i-1].hideIt();
    }

    //Positioning the top objects...
    oTop[0].moveIt(0,0)
	for(var i=1;i<oTop.length;i++){ oTop[i].moveIt(0,oTop[i-1].y+oTop[i-1].height) }

    //Making the containing menu object and showing it...
  	oCont= new GF_makeMenu('divCont')
  	oCont.showIt()
}
function GF_resizeForOpera()	{
	if(bw.opera){ //Opera 5 resize fix.
		if(GFV_scrX<innerWidth-10 || GFV_scrY<innerHeight-10 || GFV_scrX>innerWidth+10 || GFV_scrY>innerHeight+10){
			GFV_scrX= innerWidth;
			GFV_scrY= innerHeight;
			GF_initFoldout();
		}
	}
}





