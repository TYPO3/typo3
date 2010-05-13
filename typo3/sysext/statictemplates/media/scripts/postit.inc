<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * postit.inc
 *
 * Creates graphical postit notes with text on them.
 *
 * $Id: postit.inc 5165 2009-03-09 18:28:59Z ohader $
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */



if (!is_object($this)) die ('Error: No parent object present.');






/***************************************************************
TypoScript config:



.data		[string / stdWrap]		The data for the notes. Every line is a new note. Each line is divided by "|" where the first part is the test, the second part is the type (1-) and the third part is the optional link (typolink-format)
.charsPerLine	[string]			The max number of chars per line of text on the note.
.images.[x]		[image-contentObjects]   [x] is the type-number defined by the second parameter in each line of data.
.textBox {
    chars						integer, the number of chars on each line
    lineDist					integer, the number of pixels between each line
    tmplObjNumber 				integer, pointer to the GIFBUILDER-OBJECT (of type TEXT!!) which serves as a TEMPLATE for the objects used to create the textlines
    Valign 						string. If set to "center", the tmplObjNumber-TEXT-object is expected to be centeret in the image and calculations will be done to spred the lines above and below in case of multiple lines. (based on .angle of the TEXT object also.)
	maxLines
  }


Example:


// Postit:
tt_content.splash.20 = PHP_SCRIPT
tt_content.splash.20 {
  file = media/scripts/postit.inc
  data.field = bodytext
  cols = 3
  textBox {
    chars = 16
    lineDist = 18
    tmplObjNumber = 100
    Valign = center
	maxLines = 5
  }
  typolink {
    parameter.current = 1
    extTarget = {$styles.content.links.extTarget}
    target = {$styles.content.links.target}
  }
  images.1 = IMAGE
  images.1.file = GIFBUILDER
  images.1.file {
    XY = [5.w],[5.h]
    5 = IMAGE
    5.file = media/uploads/postit_1.gif
    100 = TEXT
    100.text = Testing
    100.offset = -5,60
    100.fontFile = fileadmin/fonts/arial_bold.ttf
    100.fontSize = 15
    100.align=center
  }
  images.2 < .images.1
  images.2.file.5.file = media/uploads/postit_2.gif
  images.2.file.100.angle = 11
  images.2.file.100.offset = -2,79
  images.3 < .images.1
  images.3.file.5.file = media/uploads/postit_3.gif
  images.3.file.100.angle = -13
  images.3.file.100.offset = -7,81
}

****************************************************************/





$data = $this->stdWrap($conf['data'],$conf['data.']);
$cols = intval($conf['cols']) ? intval($conf['cols']) : 3;


$lines = explode(chr(10),$data);
$imageArr = array();
foreach ($lines as $key => $content) {
	$content = trim($content);
	if ($content)	{
		$parts = explode('|',$content);
		$text = trim($parts[0]);
		$type = t3lib_div::intInRange($parts[1],1,3);
		$link = trim($parts[2]);
		if ($text)	{
			$imgConf = $conf['images.'][$type.'.'];
			$imgConf['file.'] = $this->gifBuilderTextBox ($imgConf['file.'], $conf['textBox.'], $text);

			$image = $this->IMAGE($imgConf);
			if ($image)	{
				$this->setCurrentVal($link);
				$imageArr[] = $this->typolink($image,$conf['typolink.']);
			}
		}
	}
}


if (is_array($imageArr))	{
	reset($imageArr);
	if ($cols)	{
		$res = '';
		$rows = ceil(count($imageArr)/$cols);

		for ($a=0;$a<$rows;$a++)	{
			$res.='<tr>';
			for ($b=0;$b<$cols;$b++)	{
				$res.='<td>'.$imageArr[(($a*$cols)+$b)].'</td>';
			}
			$res.='</tr>';
		}

		$content='<table border="0" cellspacing="0" cellpadding="0">'.$res.'</table>';
	} else {
		$content.=implode($imageArr,'');
	}
}

?>