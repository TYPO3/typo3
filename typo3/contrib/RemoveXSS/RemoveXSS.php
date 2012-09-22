<?php
/**
 * Usage: Run *every* variable passed in through it.
 * The goal of this function is to be a generic function that can be used to
 * parse almost any input and render it XSS safe. For more information on
 * actual XSS attacks, check out http://ha.ckers.org/xss.html. Another
 * excellent site is the XSS Database which details each attack and how it
 * works.
 *
 * Used with permission by the author.
 * URL: http://quickwired.com/smallprojects/php_xss_filter_function.php
 *
 * Check XSS attacks on http://ha.ckers.org/xss.html
 *
 * License:
 * This code is public domain, you are free to do whatever you want with it,
 * including adding it to your own project which can be under any license.
 *
 * @author	Travis Puderbaugh <kallahar@quickwired.com>
 * @author	Jigal van Hemert <jigal@xs4all.nl>
 * @package	RemoveXSS
 */
class RemoveXSS {

	/**
	 * Removes potential XSS code from an input string.
	 *
	 * Using an external class by Travis Puderbaugh <kallahar@quickwired.com>
	 *
	 * @param string $val Input string
	 * @param string $replaceString replaceString for inserting in keywords (which destroys the tags)
	 * @return string Input string with potential XSS code removed
	 */
	public static function process($val, $replaceString = '<x>') {
			// don't use empty $replaceString because then no XSS-remove will be done
		if ($replaceString == '') {
			$replaceString = '<x>';
		}
			// remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
			// this prevents some character re-spacing such as <java\0script>
			// note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
		$val = preg_replace('/([\x00-\x08]|[\x0b-\x0c]|[\x0e-\x19])/', '', $val);

			// straight replacements, the user should never need these since they're normal characters
			// this prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
		$searchHexEncodings = '/&#[xX]0{0,8}(21|22|23|24|25|26|27|28|29|2a|2b|2d|2f|30|31|32|33|34|35|36|37|38|39|3a|3b|3d|3f|40|41|42|43|44|45|46|47|48|49|4a|4b|4c|4d|4e|4f|50|51|52|53|54|55|56|57|58|59|5a|5b|5c|5d|5e|5f|60|61|62|63|64|65|66|67|68|69|6a|6b|6c|6d|6e|6f|70|71|72|73|74|75|76|77|78|79|7a|7b|7c|7d|7e);?/ie';
		$searchUnicodeEncodings = '/&#0{0,8}(33|34|35|36|37|38|39|40|41|42|43|45|47|48|49|50|51|52|53|54|55|56|57|58|59|61|63|64|65|66|67|68|69|70|71|72|73|74|75|76|77|78|79|80|81|82|83|84|85|86|87|88|89|90|91|92|93|94|95|96|97|98|99|100|101|102|103|104|105|106|107|108|109|110|111|112|113|114|115|116|117|118|119|120|121|122|123|124|125|126);?/ie';
		while (preg_match($searchHexEncodings, $val) || preg_match($searchUnicodeEncodings, $val)) {
			$val = preg_replace($searchHexEncodings, "chr(hexdec('\\1'))", $val);
			$val = preg_replace($searchUnicodeEncodings, "chr('\\1')", $val);
		}

			// now the only remaining whitespace attacks are \t, \n, and \r
		$ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed',
			'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base', 'video', 'audio', 'track',
			'canvas', 'onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut',
			'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate',
			'onblur', 'onbounce', 'oncanplay', 'oncanplaythrough', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu',
			'oncontrolselect', 'oncopy', 'oncuechange', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete',
			'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart',
			'ondrop', 'ondurationchange', 'onemptied', 'onended', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish',
			'onfocus', 'onfocusin', 'onfocusout', 'onhashchange', 'onhelp', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress',
			'onkeyup', 'onlayoutcomplete', 'onload', 'onloadeddata', 'onloadedmetadata', 'onloadstart', 'onlosecapture',
			'onmessage', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup',
			'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onoffline', 'ononline', 'onpagehide', 'onpageshow', 'onpaste',
			'onpause', 'onplay', 'onplaying', 'onpopstate', 'onprogress', 'onpropertychange', 'onratechange', 'onreadystatechange',
			'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted',
			'onscroll', 'onseeked', 'onseeking','onselect', 'onselectionchange', 'onselectstart', 'onshow', 'onstalled', 'onstart',
			'onstop', 'onstorage', 'onsubmit', 'onsuspend', 'ontimeupdate', 'onunload', 'onvolumechange', 'onwaiting');
		$ra_tag = array('applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame',
			'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base', 'video', 'audio', 'track', 'canvas');
		$ra_attribute = array('style', 'onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate',
			'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint',
			'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncanplay', 'oncanplaythrough', 'oncellchange', 'onchange',
			'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncuechange', 'oncut', 'ondataavailable', 'ondatasetchanged',
			'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover',
			'ondragstart', 'ondrop', 'ondurationchange', 'onemptied', 'onended', 'onerror', 'onerrorupdate', 'onfilterchange',
			'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhashchange', 'onhelp', 'oninput', 'oninvalid,', 'onkeydown',
			'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onloadeddata', 'onloadedmetadata', 'onloadstart',
			'onlosecapture', 'onmessage', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout',
			'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onoffline', 'ononline',
			'onpagehide', 'onpageshow', 'onpaste', 'onpause', 'onplay', 'onplaying', 'onpopstate', 'onprogress',
			'onpropertychange', 'onratechange', 'onreadystatechange', 'onredo', 'onreset', 'onresize', 'onresizeend',
			'onresizestart','onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onseeked', 'onseeking',
			'onselect', 'onselectionchange', 'onselectstart', 'onshow', 'onstalled', 'onstart', 'onstop', 'onstorage', 'onsubmit',
			'onsuspend', 'ontimeupdate', 'onundo', 'onunload', 'onvolumechange', 'onwaiting');
		$ra_protocol = array('javascript', 'vbscript', 'expression');

			//remove the potential &#xxx; stuff for testing
		$val2 = preg_replace('/(&#[xX]?0{0,8}(9|10|13|a|b);?)*\s*/i', '', $val);
		$ra = array();

		foreach ($ra1 as $ra1word) {
				// stripos is faster than the regular expressions used later and because the words we're looking for only have
				// chars < 0x80 we can use the non-multibyte safe version
			if (stripos($val2, $ra1word ) !== FALSE ) {
					//keep list of potential words that were found
				if (in_array($ra1word, $ra_protocol, TRUE)) {
					$ra[] = array($ra1word, 'ra_protocol');
				}
				if (in_array($ra1word, $ra_tag, TRUE)) {
					$ra[] = array($ra1word, 'ra_tag');
				}
				if (in_array($ra1word, $ra_attribute, TRUE)) {
					$ra[] = array($ra1word, 'ra_attribute');
				}
					//some keywords appear in more than one array
					//these get multiple entries in $ra, each with the appropriate type
			}
		}
			//only process potential words
		if (count($ra) > 0) {
				// keep replacing as long as the previous round replaced something
			$found = TRUE;
			while ($found == TRUE) {
				$val_before = $val;
				for ($i = 0; $i < sizeof($ra); $i++) {
					$pattern = '';
					for ($j = 0; $j < strlen($ra[$i][0]); $j++) {
						if ($j > 0) {
							$pattern .= '((&#[xX]0{0,8}([9ab]);?)|(&#0{0,8}(9|10|13);?)|\s)*';
						}
						$pattern .= $ra[$i][0][$j];
					}
						//handle each type a little different (extra conditions to prevent false positives a bit better)
					switch ($ra[$i][1]) {
						case 'ra_protocol':
								//these take the form of e.g. 'javascript:'
							$pattern .= '((&#[xX]0{0,8}([9ab]);?)|(&#0{0,8}(9|10|13);?)|\s)*(?=:)';
							break;
						case 'ra_tag':
								//these take the form of e.g. '<SCRIPT[^\da-z] ....';
							$pattern = '(?<=<)' . $pattern . '((&#[xX]0{0,8}([9ab]);?)|(&#0{0,8}(9|10|13);?)|\s)*(?=[^\da-z])';
							break;
						case 'ra_attribute':
								//these take the form of e.g. 'onload='  Beware that a lot of characters are allowed
								//between the attribute and the equal sign!
							$pattern .= '[\s\!\#\$\%\&\(\)\*\~\+\-\_\.\,\:\;\?\@\[\/\|\\\\\]\^\`]*(?==)';
							break;
					}
					$pattern = '/' . $pattern . '/i';
						// add in <x> to nerf the tag
					$replacement = substr_replace($ra[$i][0], $replaceString, 2, 0);
						// filter out the hex tags
					$val = preg_replace($pattern, $replacement, $val);
					if ($val_before == $val) {
							// no replacements were made, so exit the loop
						$found = FALSE;
					}
				}
			}
		}
		return $val;
	}
}

?>