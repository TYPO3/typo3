/***************************************************************
*
*  Example script for keeping rollover effect when a menu item is clicked.
*
*
*  Copyright notice
* 
*  (c) 1998-2000 Kasper Skårhøj
*  All rights reserved
*
*  This script is part of the standard PHP-code library provided by
*  Kasper Skårhøj, kasper@typo3.com
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
* 
*  This copyright notice MUST APPEAR in all copies of this script
***************************************************************/

var ARO_Image=null;
function ARO_setLocationTop(uid,image) {
	ARO_setActiveImg(image);
	ARO_setFrame('left','index.php?id='+uid+'&type=2');
	ARO_setFrame('page','index.php?id='+uid+'&type=1');
}
function ARO_setLocationLeft(uid,image) {
	ARO_setActiveImg(image);
	ARO_setFrame('page','index.php?id='+uid+'&type=1');
}
function ARO_setFrame(key,url)	{
	if (top.frameset2 && top.frameset2[key]) {
		top.frameset2[key].location = url;
	}
}
function ARO_setActiveImg(image)	{
	ARO_out(ARO_Image,'',1);
	ARO_Image = image;
}
function ARO_over(name,imgObj,noOutAction)	{
	if (version == 'n3' && document[name]) {document[name].src = eval(name+'_h.src');}
		else if (imgObj)	{imgObj.src = eval(name+'_h.src');}
	if (ARO_Image!=name)  ARO_out(ARO_Image,'',1);
}
function ARO_out(name,imgObj,noOverAction)	{
	if (version == 'n3' && document[name]) {document[name].src = eval(name+'_n.src');}
		else if (imgObj)	{imgObj.src = eval(name+'_n.src');}
	if (!noOverAction)	ARO_over(ARO_Image,'',1);
}


