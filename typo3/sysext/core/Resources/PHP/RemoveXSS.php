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
 */
class RemoveXSS
{
    /**
     * Removes potential XSS code from an input string.
     *
     * Using an external class by Travis Puderbaugh <kallahar@quickwired.com>
     *
     * @param string $value Input string
     * @param string $replaceString replaceString for inserting in keywords (which destroys the tags)
     * @return string Input string with potential XSS code removed
     */
    public static function process($value, $replaceString = '<x>')
    {
        // Don't use empty $replaceString because then no XSS-remove will be done
        if ($replaceString == '') {
            $replaceString = '<x>';
        }
        // Remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed.
        // This prevents some character re-spacing such as <java\0script>
        // Note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
        $value = preg_replace('/([\x00-\x08]|[\x0b-\x0c]|[\x0e-\x19])/', '', $value);

        // Straight replacements, the user should never need these since they're normal characters.
        // This prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
        $searchHexEncodings = '/&#[xX]0{0,8}(21|22|23|24|25|26|27|28|29|2a|2b|2d|2f|30|31|32|33|34|35|36|37|38|39|3a|3b|3d|3f|40|41|42|43|44|45|46|47|48|49|4a|4b|4c|4d|4e|4f|50|51|52|53|54|55|56|57|58|59|5a|5b|5c|5d|5e|5f|60|61|62|63|64|65|66|67|68|69|6a|6b|6c|6d|6e|6f|70|71|72|73|74|75|76|77|78|79|7a|7b|7c|7d|7e);?/i';
        $searchUnicodeEncodings = '/&#0{0,8}(33|34|35|36|37|38|39|40|41|42|43|45|47|48|49|50|51|52|53|54|55|56|57|58|59|61|63|64|65|66|67|68|69|70|71|72|73|74|75|76|77|78|79|80|81|82|83|84|85|86|87|88|89|90|91|92|93|94|95|96|97|98|99|100|101|102|103|104|105|106|107|108|109|110|111|112|113|114|115|116|117|118|119|120|121|122|123|124|125|126);?/i';
        while (preg_match($searchHexEncodings, $value) || preg_match($searchUnicodeEncodings, $value)) {
            $value = preg_replace_callback(
                $searchHexEncodings,
                function ($matches) {
                    return chr(hexdec($matches[1]));
                },
                $value
            );
            $value = preg_replace_callback(
                $searchUnicodeEncodings,
                function ($matches) {
                    return chr($matches[1]);
                },
                $value
            );
        }

        // Now the only remaining whitespace attacks are \t, \n, and \r
        $allKeywords = ['javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed',
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
            'onscroll', 'onseeked', 'onseeking', 'onselect', 'onselectionchange', 'onselectstart', 'onshow', 'onstalled', 'onstart',
            'onstop', 'onstorage', 'onsubmit', 'onsuspend', 'ontimeupdate', 'onunload', 'onvolumechange', 'onwaiting'];
        $tagKeywords = ['applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame',
            'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base', 'video', 'audio', 'track', 'canvas'];
        $attributeKeywords = ['style', 'onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate',
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
            'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onseeked', 'onseeking',
            'onselect', 'onselectionchange', 'onselectstart', 'onshow', 'onstalled', 'onstart', 'onstop', 'onstorage', 'onsubmit',
            'onsuspend', 'ontimeupdate', 'onundo', 'onunload', 'onvolumechange', 'onwaiting'];
        $protocolKeywords = ['javascript', 'vbscript', 'expression'];

        // Remove the potential &#xxx; stuff for testing
        $valueForQuickCheck = preg_replace('/(&#[xX]?0{0,8}(9|10|13|a|b);?)*\s*/i', '', $value);
        $potentialKeywords = [];

        foreach ($allKeywords as $keyword) {
            // Stripos is faster than the regular expressions used later and because the words we're looking for only have
            // chars < 0x80 we can use the non-multibyte safe version.
            if (stripos($valueForQuickCheck, $keyword) !== false) {
                //keep list of potential words that were found
                if (in_array($keyword, $protocolKeywords, true)) {
                    $potentialKeywords[] = [$keyword, 'protocol'];
                }
                if (in_array($keyword, $tagKeywords, true)) {
                    $potentialKeywords[] = [$keyword, 'tag'];
                }
                if (in_array($keyword, $attributeKeywords, true)) {
                    $potentialKeywords[] = [$keyword, 'attribute'];
                }
                // Some keywords appear in more than one array.
                // These get multiple entries in $potentialKeywords, each with the appropriate type
            }
        }
        // Only process potential words
        if (!empty($potentialKeywords)) {
            // Keep replacing as long as the previous round replaced something
            $found = true;
            while ($found) {
                $valueBeforeReplacement = $value;
                foreach ($potentialKeywords as $potentialKeywordItem) {
                    list($keyword, $type) = $potentialKeywordItem;
                    $keywordLength = strlen($keyword);
                    // Build pattern with each letter of the keyword and potential (encoded) whitespace in between
                    $pattern = $keyword[0];
                    if ($keywordLength > 1) {
                        for ($j = 1; $j < $keywordLength; $j++) {
                            $pattern .= '((&#[xX]0{0,8}([9ab]);?)|(&#0{0,8}(9|10|13);?)|\s)*' . $keyword[$j];
                        }
                    }
                    // Handle each type a little different (extra conditions to prevent false positives a bit better)
                    switch ($type) {
                        case 'protocol':
                            // These take the form of e.g. 'javascript:'
                            $pattern .= '((&#[xX]0{0,8}([9ab]);?)|(&#0{0,8}(9|10|13);?)|\s)*(?=:)';
                            break;
                        case 'tag':
                            // These take the form of e.g. '<SCRIPT[^\da-z] ....';
                            $pattern = '(?<=<)' . $pattern . '((&#[xX]0{0,8}([9ab]);?)|(&#0{0,8}(9|10|13);?)|\s)*(?=[^\da-z])';
                            break;
                        case 'attribute':
                            // These take the form of e.g. 'onload='  Beware that a lot of characters are allowed
                            // between the attribute and the equal sign!
                            $pattern .= '[\s\!\#\$\%\&\(\)\*\~\+\-\_\.\,\:\;\?\@\[\/\|\\\\\]\^\`]*(?==)';
                            break;
                    }
                    $pattern = '/' . $pattern . '/i';
                    // Inject the replacement to render the potential problem harmless
                    $replacement = substr_replace($keyword, $replaceString, 2, 0);
                    // Perform the actual replacement
                    $value = preg_replace($pattern, $replacement, $value);
                    // If no replacements were made exit the loop
                    $found = ($valueBeforeReplacement !== $value);
                }
            }
        }
        return $value;
    }
}
