<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2003-2004 Kasper Skaarhoj (kasper@typo3.com)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Class for conversion between charsets.
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  119: class t3lib_cs
 *  261:     function parse_charset($charset)
 *  278:     function conv($str,$fromCS,$toCS,$useEntityForNoChar=0)
 *  312:     function utf8_encode($str,$charset)
 *  359:     function utf8_decode($str,$charset,$useEntityForNoChar=0)
 *  407:     function utf8_to_entities($str)
 *  440:     function entities_to_utf8($str,$alsoStdHtmlEnt=0)
 *  474:     function utf8_to_numberarray($str,$convEntities=0,$retChar=0)
 *  515:     function initCharset($charset)
 *  586:     function UnumberToChar($cbyte)
 *  630:     function utf8CharToUnumber($str,$hex=0)
 *
 *              SECTION: String operation functions
 *  682:     function strtrunc($charset,$string,$len)
 *  716:     function substr($charset,$str,$start,$len=null)
 *  755:     function strlen($charset,$string)
 *
 *              SECTION: UTF-8 String operation functions
 *  803:     function utf8_strtrunc($str,$len)
 *  831:     function utf8_substr($str,$start,$len=null)
 *  857:     function utf8_strlen($str)
 *  879:     function utf8_strpos($haystack,$needle,$offset=0)
 *  902:     function utf8_strrpos($haystack,$needle)
 *  921:     function utf8_char2byte_pos($str,$pos)
 *  946:     function utf8_byte2char_pos($str,$pos)
 *
 *              SECTION: EUC String operation functions
 *  994:     function euc_strtrunc($str,$len,$charset)
 * 1028:     function euc_substr($str,$start,$charset,$len=null)
 * 1055:     function euc_strlen($str,$charset)
 * 1082:     function euc_char2byte_pos($str,$pos,$charset)
 *
 * TOTAL FUNCTIONS: 24
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */








/**
 * Notes on UTF-8
 *
 * Functions working on UTF-8 strings:
 *
 * - strchr/strstr
 * - strrchr
 * - substr_count
 * - implode/explode/join
 *
 * Functions nearly working on UTF-8 strings:
 *
 * - strlen: returns the length in BYTES, if you need the length in CHARACTERS use utf_strlen
 * - trim/ltrim/rtrim: the second parameter 'charlist' won't work for characters not contained 7-bit ASCII
 * - strpos/strrpos: they return the BYTE position, if you need the CHARACTER position use utf8_strpos/utf8_strrpos
 * - htmlentities: charset support for UTF-8 only since PHP 4.3.0
 *
 * Functions NOT working on UTF-8 strings:
 *
 * - str*cmp
 * - stristr
 * - stripos
 * - substr
 * - strrev
 * - ereg/eregi
 * - split/spliti
 * - preg_*
 * - ...
 *
 */
/**
 * Class for conversion between charsets.
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_cs {
	var $noCharByteVal=127;		// ASCII Value for chars with no equalent.

		// This is the array where parsed conversion tables are stored (cached)
	var $parsedCharsets=array();

		// An array where case folding data will be stored (cached)
	var $caseFolding=array();

		// This tells the converter which charsets has two bytes per char:
	var $twoByteSets=array(
		'ucs-2'=>1,	// 2-byte Unicode
	);

		// This tells the converter which charsets has four bytes per char:
	var $fourByteSets=array(
		'ucs-4'=>1,	// 4-byte Unicode
		'utf-32'=>1,	// 4-byte Unicode (limited to the 21-bits of UTF-16)
	);

		// This tells the converter which charsets use a scheme like the Extended Unix Code:
	var $eucBasedSets=array(
		'gb2312'=>1,	// Chinese, simplified.
		'big5'=>1,	// Chinese, traditional.
		'shift_jis'=>1,	// Japanes - WARNING: Shift-JIS includes half-width katakana single-bytes characters above 0x80!
	);

		// see	http://developer.apple.com/documentation/macos8/TextIntlSvcs/TextEncodingConversionManager/TEC1.5/TEC.b0.html
		// http://czyborra.com/charsets/iso8859.html
	var $synonyms=array(
		'us' => 'ascii',
		'us-ascii'=> 'ascii',
		'cp819' => 'iso-8859-1',
		'ibm819' => 'iso-8859-1',
		'iso-ir-100' => 'iso-8859-1',
		'iso-ir-109' => 'iso-8859-2',
		'iso-ir-148' => 'iso-8859-9',
		'iso-ir-199' => 'iso-8859-14',
		'iso-ir-203' => 'iso-8859-15',
		'csisolatin1' => 'iso-8859-1',
		'csisolatin2' => 'iso-8859-2',
		'csisolatin3' => 'iso-8859-3',
		'csisolatin5' => 'iso-8859-9',
		'csisolatin8' => 'iso-8859-14',
		'csisolatin9' => 'iso-8859-15',
		'csisolatingreek' => 'iso-8859-7',
		'iso-celtic' => 'iso-8859-14',
		'latin1' => 'iso-8859-1',
		'latin2' => 'iso-8859-2',
		'latin3' => 'iso-8859-3',
		'latin5' => 'iso-8859-9',
		'latin6' => 'iso-8859-10',
		'latin8' => 'iso-8859-14',
		'latin9' => 'iso-8859-15',
		'l1' => 'iso-8859-1',
		'l2' => 'iso-8859-2',
		'l3' => 'iso-8859-3',
		'l5' => 'iso-8859-9',
		'l6' => 'iso-8859-10',
		'l8' => 'iso-8859-14',
		'l9' => 'iso-8859-15',
		'cyrillic' => 'iso-8859-5',
		'arabic' => 'iso-8859-6',
		'win874' => 'windows-874',
		'win1250' => 'windows-1250',
		'win1251' => 'windows-1251',
		'win1252' => 'windows-1252',
		'win1253' => 'windows-1253',
		'win1254' => 'windows-1254',
		'win1255' => 'windows-1255',
		'win1256' => 'windows-1256',
		'win1257' => 'windows-1257',
		'win1258' => 'windows-1258',
		'cp1250' => 'windows-1250',
		'cp1252' => 'windows-1252',
		'ms-ee' => 'windows-1250',
		'ms-ansi' => 'windows-1252',
		'ms-greek' => 'windows-1253',
		'ms-turk' => 'windows-1254',
		'winbaltrim' => 'windows-1257',
		'koi-8ru' => 'koi-8r',
		'koi8r' => 'koi-8r',
		'cp878' => 'koi-8r',
		'mac' => 'macRoman',
		'macintosh' => 'macRoman',
		'euc-cn' => 'gb2312',
		'x-euc-cn' => 'gb2312',
		'cp936' => 'gb2312',
		'big-5' => 'big5',
		'cp950' => 'big5',
		'sjis' => 'shift_jis',
		'shift-jis' => 'shift_jis',
		'cp932' => 'shift_jis',
		'utf7' => 'utf-7',
		'utf8' => 'utf-8',
		'utf16' => 'utf-16',
		'utf32' => 'utf-32',
		'utf8' => 'utf-8',
		'ucs2' => 'ucs-2',
		'ucs4' => 'ucs-4',
	);

		// TYPO3 specific: Array with the system charsets used for each system language in TYPO3:
		// Empty values means "iso-8859-1"
	var $charSetArray = array(
		'dk' => '',
		'de' => '',
		'no' => '',
		'it' => '',
		'fr' => '',
		'es' => '',
		'nl' => '',
		'cz' => 'windows-1250',
		'pl' => 'iso-8859-2',
		'si' => 'windows-1250',
		'fi' => '',
		'tr' => 'iso-8859-9',
		'se' => '',
		'pt' => '',
		'ru' => 'windows-1251',
		'ro' => 'iso-8859-2',
		'ch' => 'gb2312',
		'sk' => 'windows-1250',
		'lt' => 'windows-1257',
		'is' => 'utf-8',
		'hr' => 'windows-1250',
		'hu' => 'iso-8859-2',
		'gl' => '',
		'th' => 'iso-8859-11',
		'gr' => 'iso-8859-7',
		'hk' => 'big5',
		'eu' => '',
		'bg' => 'windows-1251',
		'br' => '',
		'et' => 'iso-8859-4',
		'ar' => 'iso-8859-6',
		'he' => 'utf-8',
		'ua' => 'windows-1251',
		'lv' => 'utf-8',
		'jp' => 'shift-jis',
		'vn' => 'utf-8',
	);

	/**
	 * Normalize - changes input character set to lowercase letters.
	 *
	 * @param	string		Input charset
	 * @return	string		Normalized charset
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function parse_charset($charset)	{
		$charset = strtolower($charset);
		if (isset($this->synonyms[$charset]))	$charset = $this->synonyms[$charset];

		return $charset;
	}


	/**
	 * Convert from one charset to another charset.
	 *
	 * @param	string		Input string
	 * @param	string		From charset (the current charset of the string)
	 * @param	string		To charset (the output charset wanted)
	 * @param	boolean		If set, then characters that are not available in the destination character set will be encoded as numeric entities
	 * @return	string		Converted string
	 */
	function conv($str,$fromCS,$toCS,$useEntityForNoChar=0)	{
		global $TYPO3_CONF_VARS;

		if ($fromCS==$toCS)	return $str;

		if (!$useEntityForNoChar)	{ // iconv and recode don't support fallback to SGML entities
			if ($TYPO3_CONF_VARS['SYS']['t3lib_cs_convMethod'] == 'iconv')	{
				$conv_str = iconv($str,$fromCS,$toCS.'//TRANSLIT');
				if (false !== $conv_str)	return $conv_str;
			}
			elseif ($TYPO3_CONF_VARS['SYS']['t3lib_cs_convMethod'] == 'recode')	{
				$conv_str = recode_string($toCS.'..'.$fromCS,$str);
				if (false !== $conv_str)	return $conv_str;
			}
			elseif ($TYPO3_CONF_VARS['SYS']['t3lib_cs_convMethod'] == 'mbstring')	{
				$conv_str = mb_convert_encoding($str,$toCS,$fromCS);
				if (false !== $conv_str)	return $conv_str; // returns false for unsupported charsets
			}
			// fallback to TYPO3 conversion
		}

		if ($fromCS!='utf-8')	$str=$this->utf8_encode($str,$fromCS);
		if ($toCS!='utf-8')		$str=$this->utf8_decode($str,$toCS,$useEntityForNoChar);
		return $str;
	}


	/**
	 * Converts $str from $charset to UTF-8
	 *
	 * @param	string		String in local charset to convert to UTF-8
	 * @param	string		Charset, lowercase. Must be found in csconvtbl/ folder.
	 * @return	string		Output string, converted to UTF-8
	 */
	function utf8_encode($str,$charset)	{

			// Charset is case-insensitive.
		if ($this->initCharset($charset))	{	// Parse conv. table if not already...
			$strLen = strlen($str);
			$outStr='';

			for ($a=0;$a<$strLen;$a++)	{	// Traverse each char in string.
				$chr=substr($str,$a,1);
				$ord=ord($chr);
				if ($this->twoByteSets[$charset])	{	// If the charset has two bytes per char
					$ord2 = ord($str{$a+1});
					$ord = $ord<<8 & $ord2; // assume big endian

					if (isset($this->parsedCharsets[$charset]['local'][$ord]))	{	// If the local char-number was found in parsed conv. table then we use that, otherwise 127 (no char?)
						$outStr.=$this->parsedCharsets[$charset]['local'][$ord];
					} else $outStr.=chr($this->noCharByteVal);	// No char exists
					$a++;
				} elseif ($ord>127)	{	// If char has value over 127 it's a multibyte char in UTF-8
					if ($this->eucBasedSets[$charset])	{	// EUC uses two-bytes above 127; we get both and advance pointer and make $ord a 16bit int.
						$a++;
						$ord2=ord(substr($str,$a,1));
						$ord = $ord*256+$ord2;
					}
					elseif ($charset == 'shift_jis' && ($ord <160 || $ord>223))	{	// Shift-JIS is like EUC, but chars between 160 and 223 are single byte
						$a++;
						$ord2=ord(substr($str,$a,1));
						$ord = $ord*256+$ord2;
					}

					if (isset($this->parsedCharsets[$charset]['local'][$ord]))	{	// If the local char-number was found in parsed conv. table then we use that, otherwise 127 (no char?)
						$outStr.=$this->parsedCharsets[$charset]['local'][$ord];
					} else $outStr.=chr($this->noCharByteVal);	// No char exists
				} else $outStr.=$chr;	// ... otherwise it's just ASCII 0-127 and one byte. Transparent
			}
			return $outStr;
		}
	}

	/**
	 * Converts $str from UTF-8 to $charset
	 *
	 * @param	string		String in UTF-8 to convert to local charset
	 * @param	string		Charset, lowercase. Must be found in csconvtbl/ folder.
	 * @param	boolean		If set, then characters that are not available in the destination character set will be encoded as numeric entities
	 * @return	string		Output string, converted to local charset
	 */
	function utf8_decode($str,$charset,$useEntityForNoChar=0)	{

			// Charset is case-insensitive.
		if ($this->initCharset($charset))	{	// Parse conv. table if not already...
			$strLen = strlen($str);
			$outStr='';
			$buf='';
			for ($a=0,$i=0;$a<$strLen;$a++,$i++)	{	// Traverse each char in UTF-8 string.
				$chr=substr($str,$a,1);
				$ord=ord($chr);
				if ($ord>127)	{	// This means multibyte! (first byte!)
					if ($ord & 64)	{	// Since the first byte must have the 7th bit set we check that. Otherwise we might be in the middle of a byte sequence.

						$buf=$chr;	// Add first byte
						for ($b=0;$b<8;$b++)	{	// for each byte in multibyte string...
							$ord = $ord << 1;	// Shift it left and ...
							if ($ord & 128)	{	// ... and with 8th bit - if that is set, then there are still bytes in sequence.
								$a++;	// Increase pointer...
								$buf.=substr($str,$a,1);	// ... and add the next char.
							} else break;
						}

# Martin Kutschker...! this does not work! With russian UTF-8 converted back to windows-1251 it failed... So the old code is re-inserted.
#						for ($bc=0; $ord & 0x80; $ord = $ord << 1) { $bc++; }	// calculate number of bytes
#						$buf.=substr($str,$i,$bc);
#						$i+=$bc-1;

						if (isset($this->parsedCharsets[$charset]['utf8'][$buf]))	{	// If the UTF-8 char-sequence is found then...
							$mByte = $this->parsedCharsets[$charset]['utf8'][$buf];	// The local number
							if ($mByte>255)	{	// If the local number is greater than 255 we will need to split the byte (16bit word assumed) in two chars.
								$outStr.= chr(($mByte >> 8) & 255).chr($mByte & 255);
							} else $outStr.= chr($mByte);
						} elseif ($useEntityForNoChar) {	// Create num entity:
							$outStr.='&#'.$this->utf8CharToUnumber($buf,1).';';
						} else $outStr.=chr($this->noCharByteVal);	// No char exists
					} else $outStr.=chr($this->noCharByteVal);	// No char exists (MIDDLE of MB sequence!)
				} else $outStr.=$chr;	// ... otherwise it's just ASCII 0-127 and one byte. Transparent
			}
			return $outStr;
		}
	}

	/**
	 * Converts all chars > 127 to numeric entities.
	 *
	 * @param	string		Input string
	 * @return	string		Output string
	 */
	function utf8_to_entities($str)	{
		$strLen = strlen($str);
		$outStr='';
		$buf='';
		for ($a=0;$a<$strLen;$a++)	{	// Traverse each char in UTF-8 string.
			$chr=substr($str,$a,1);
			$ord=ord($chr);
			if ($ord>127)	{	// This means multibyte! (first byte!)
				if ($ord & 64)	{	// Since the first byte must have the 7th bit set we check that. Otherwise we might be in the middle of a byte sequence.
					$buf=$chr;	// Add first byte
					for ($b=0;$b<8;$b++)	{	// for each byte in multibyte string...
						$ord = $ord << 1;	// Shift it left and ...
						if ($ord & 128)	{	// ... and with 8th bit - if that is set, then there are still bytes in sequence.
							$a++;	// Increase pointer...
							$buf.=substr($str,$a,1);	// ... and add the next char.
						} else break;
					}

					$outStr.='&#'.$this->utf8CharToUnumber($buf,1).';';
				} else $outStr.=chr($this->noCharByteVal);	// No char exists (MIDDLE of MB sequence!)
			} else $outStr.=$chr;	// ... otherwise it's just ASCII 0-127 and one byte. Transparent
		}

		return $outStr;
	}

	/**
	 * Converts numeric entities (UNICODE, eg. decimal (&#1234;) or hexadecimal (&#x1b;)) to UTF-8 multibyte chars
	 *
	 * @param	string		Input string, UTF-8
	 * @param	boolean		If set, then all string-HTML entities (like &amp; or &pound; will be converted as well)
	 * @return	string		Output string
	 */
	function entities_to_utf8($str,$alsoStdHtmlEnt=0)	{
		if ($alsoStdHtmlEnt)	{
			$trans_tbl = array_flip(get_html_translation_table(HTML_ENTITIES));
		}

		$token = md5(microtime());
		$parts = explode($token,ereg_replace('(&([#[:alnum:]]*);)',$token.'\2'.$token,$str));
		foreach($parts as $k => $v)	{
			if ($k%2)	{
				if (substr($v,0,1)=='#')	{	// Dec or hex entities:
					if (substr($v,1,1)=='x')	{
						$parts[$k] = $this->UnumberToChar(hexdec(substr($v,2)));
					} else {
						$parts[$k] = $this->UnumberToChar(substr($v,1));
					}
				} elseif ($alsoStdHtmlEnt && $trans_tbl['&'.$v.';']) {	// Other entities:
					$parts[$k] = $this->utf8_encode($trans_tbl['&'.$v.';'],'iso-8859-1');
				} else {	// No conversion:
					$parts[$k] ='&'.$v.';';
				}
			}
		}

		return implode('',$parts);
	}

	/**
	 * Converts all chars in the input UTF-8 string into integer numbers returned in an array
	 *
	 * @param	string		Input string, UTF-8
	 * @param	boolean		If set, then all HTML entities (like &amp; or &pound; or &#123; or &#x3f5d;) will be detected as characters.
	 * @param	boolean		If set, then instead of integer numbers the real UTF-8 char is returned.
	 * @return	array		Output array with the char numbers
	 */
	function utf8_to_numberarray($str,$convEntities=0,$retChar=0)	{
			// If entities must be registered as well...:
		if ($convEntities)	{
			$str = $this->entities_to_utf8($str,1);
		}
			// Do conversion:
		$strLen = strlen($str);
		$outArr=array();
		$buf='';
		for ($a=0;$a<$strLen;$a++)	{	// Traverse each char in UTF-8 string.
			$chr=substr($str,$a,1);
			$ord=ord($chr);
			if ($ord>127)	{	// This means multibyte! (first byte!)
				if ($ord & 64)	{	// Since the first byte must have the 7th bit set we check that. Otherwise we might be in the middle of a byte sequence.
					$buf=$chr;	// Add first byte
					for ($b=0;$b<8;$b++)	{	// for each byte in multibyte string...
						$ord = $ord << 1;	// Shift it left and ...
						if ($ord & 128)	{	// ... and with 8th bit - if that is set, then there are still bytes in sequence.
							$a++;	// Increase pointer...
							$buf.=substr($str,$a,1);	// ... and add the next char.
						} else break;
					}

					$outArr[]=$retChar?$buf:$this->utf8CharToUnumber($buf);
				} else $outArr[]=$retChar?chr($this->noCharByteVal):$this->noCharByteVal;	// No char exists (MIDDLE of MB sequence!)
			} else $outArr[]=$retChar?chr($ord):$ord;	// ... otherwise it's just ASCII 0-127 and one byte. Transparent
		}

		return $outArr;
	}

	/**
	 * This will initialize a charset for use if it's defined in the PATH_t3lib.'csconvtbl/' folder
	 * This function is automatically called by the conversion functions
	 *
	 * PLEASE SEE: http://www.unicode.org/Public/MAPPINGS/
	 *
	 * @param	string		The charset to be initialized. Use lowercase charset always (the charset must match exactly with a filename in csconvtbl/ folder ([charset].tbl)
	 * @return	integer		Returns '1' if already loaded. Returns FALSE if charset conversion table was not found. Returns '2' if the charset conversion table was found and parsed.
	 * @access private
	 */
	function initCharset($charset)	{
			// Only process if the charset is not yet loaded:
		if (!is_array($this->parsedCharsets[$charset]))	{

				// Conversion table filename:
			$charsetConvTableFile = PATH_t3lib.'csconvtbl/'.$charset.'.tbl';

				// If the conversion table is found:
			if ($charset && t3lib_div::validPathStr($charsetConvTableFile) && @is_file($charsetConvTableFile))	{
					// Cache file for charsets:
					// Caching brought parsing time for gb2312 down from 2400 ms to 150 ms. For other charsets we are talking 11 ms down to zero.
				$cacheFile = t3lib_div::getFileAbsFileName('typo3temp/charset_'.$charset.'.tbl');
				if ($cacheFile && @is_file($cacheFile))	{
					$this->parsedCharsets[$charset]=unserialize(t3lib_div::getUrl($cacheFile));
				} else {
						// Parse conversion table into lines:
					$lines=t3lib_div::trimExplode(chr(10),t3lib_div::getUrl($charsetConvTableFile),1);
						// Initialize the internal variable holding the conv. table:
					$this->parsedCharsets[$charset]=array('local'=>array(),'utf8'=>array());
						// traverse the lines:
					$detectedType='';
					foreach($lines as $value)	{
						if (trim($value) && substr($value,0,1)!='#')	{	// Comment line or blanks are ignored.

								// Detect type if not done yet: (Done on first real line)
								// The "whitespaced" type is on the syntax 	"0x0A	0x000A	#LINE FEED" 	while 	"ms-token" is like 		"B9 = U+00B9 : SUPERSCRIPT ONE"
							if (!$detectedType)		$detectedType = ereg('[[:space:]]*0x([[:alnum:]]*)[[:space:]]+0x([[:alnum:]]*)[[:space:]]+',$value) ? 'whitespaced' : 'ms-token';

							if ($detectedType=='ms-token')	{
								list($hexbyte,$utf8) = split('=|:',$value,3);
							} elseif ($detectedType=='whitespaced')	{
								$regA=array();
								ereg('[[:space:]]*0x([[:alnum:]]*)[[:space:]]+0x([[:alnum:]]*)[[:space:]]+',$value,$regA);
								$hexbyte = $regA[1];
								$utf8 = 'U+'.$regA[2];
							}
							$decval = hexdec(trim($hexbyte));
							if ($decval>127)	{
								$utf8decval = hexdec(substr(trim($utf8),2));
								$this->parsedCharsets[$charset]['local'][$decval]=$this->UnumberToChar($utf8decval);
								$this->parsedCharsets[$charset]['utf8'][$this->parsedCharsets[$charset]['local'][$decval]]=$decval;
							}
						}
					}
					if ($cacheFile)	{
						t3lib_div::writeFile($cacheFile,serialize($this->parsedCharsets[$charset]));
					}
				}
				return 2;
			} else return false;
		} else return 1;
	}

	/**
	 * Converts a UNICODE number to a UTF-8 multibyte character
	 * Algorithm based on script found at From: http://czyborra.com/utf/
	 *
	 * The binary representation of the character's integer value is thus simply spread across the bytes and the number of high bits set in the lead byte announces the number of bytes in the multibyte sequence:
	 *
	 *  bytes | bits | representation
	 *      1 |    7 | 0vvvvvvv
	 *      2 |   11 | 110vvvvv 10vvvvvv
	 *      3 |   16 | 1110vvvv 10vvvvvv 10vvvvvv
	 *      4 |   21 | 11110vvv 10vvvvvv 10vvvvvv 10vvvvvv
	 *      5 |   26 | 111110vv 10vvvvvv 10vvvvvv 10vvvvvv 10vvvvvv
	 *      6 |   31 | 1111110v 10vvvvvv 10vvvvvv 10vvvvvv 10vvvvvv 10vvvvvv
	 *
	 * @param	integer		UNICODE integer
	 * @return	string		UTF-8 multibyte character string
	 * @see utf8CharToUnumber()
	 */
	function UnumberToChar($cbyte)	{
		$str='';

		if ($cbyte < 0x80) {
			$str.=chr($cbyte);
		} else if ($cbyte < 0x800) {
			$str.=chr(0xC0 | ($cbyte >> 6));
			$str.=chr(0x80 | ($cbyte & 0x3F));
		} else if ($cbyte < 0x10000) {
			$str.=chr(0xE0 | ($cbyte >> 12));
			$str.=chr(0x80 | (($cbyte >> 6) & 0x3F));
			$str.=chr(0x80 | ($cbyte & 0x3F));
		} else if ($cbyte < 0x200000) {
			$str.=chr(0xF0 | ($cbyte >> 18));
			$str.=chr(0x80 | (($cbyte >> 12) & 0x3F));
			$str.=chr(0x80 | (($cbyte >> 6) & 0x3F));
			$str.=chr(0x80 | ($cbyte & 0x3F));
		} else if ($cbyte < 0x4000000) {
			$str.=chr(0xF8 | ($cbyte >> 24));
			$str.=chr(0x80 | (($cbyte >> 18) & 0x3F));
			$str.=chr(0x80 | (($cbyte >> 12) & 0x3F));
			$str.=chr(0x80 | (($cbyte >> 6) & 0x3F));
			$str.=chr(0x80 | ($cbyte & 0x3F));
		} else if ($cbyte < 0x80000000) {
			$str.=chr(0xFC | ($cbyte >> 30));
			$str.=chr(0x80 | (($cbyte >> 24) & 0x3F));
			$str.=chr(0x80 | (($cbyte >> 18) & 0x3F));
			$str.=chr(0x80 | (($cbyte >> 12) & 0x3F));
			$str.=chr(0x80 | (($cbyte >> 6) & 0x3F));
			$str.=chr(0x80 | ($cbyte & 0x3F));
		} else { // Cannot express a 32-bit character in UTF-8
			$str .= chr($this->noCharByteVal);
		}
		return $str;
	}

	/**
	 * Converts a UTF-8 Multibyte character to a UNICODE number
	 *
	 * @param	string		UTF-8 multibyte character string
	 * @param	boolean		If set, then a hex. number is returned.
	 * @return	integer		UNICODE integer
	 * @see UnumberToChar()
	 */
	function utf8CharToUnumber($str,$hex=0)	{
		$ord=ord(substr($str,0,1));	// First char

		if (($ord & 192) == 192)	{	// This verifyes that it IS a multi byte string
			$binBuf='';
			for ($b=0;$b<8;$b++)	{	// for each byte in multibyte string...
				$ord = $ord << 1;	// Shift it left and ...
				if ($ord & 128)	{	// ... and with 8th bit - if that is set, then there are still bytes in sequence.
					$binBuf.=substr('00000000'.decbin(ord(substr($str,$b+1,1))),-6);
				} else break;
			}
			$binBuf=substr('00000000'.decbin(ord(substr($str,0,1))),-(6-$b)).$binBuf;

			$int = bindec($binBuf);
		} else $int = $ord;

		return $hex ? 'x'.dechex($int) : $int;
	}

	/**
	 * This function initializes the UTF-8 case folding table.
	 *
	 * PLEASE SEE: http://www.unicode.org/Public/UNIDATA/
	 *
	 * @return	integer		Returns FALSE on error, a TRUE value on success: 1 table already loaded, 2, cached version, 3 table parsed (and cached).
	 * @access private
	 */
	function initCaseFoldingUTF8()	{
			// Only process if the case table is not yet loaded:
		if (is_array($this->caseFolding['utf-8']))	return 1;

			// Use cached version if possible
		$cacheFile = t3lib_div::getFileAbsFileName('typo3temp/cscase_utf-8.tbl');
		if ($cacheFile && @is_file($cacheFile))	{
			$this->caseFolding['utf-8'] = unserialize(t3lib_div::getUrl($cacheFile));
			return 2;
		}

			// process main Unicode data file
		$unicodeDataFile = PATH_t3lib.'unidata/UnicodeData.txt';
		if (!(t3lib_div::validPathStr($unicodeDataFile) && @is_file($unicodeDataFile)))	return false;

		$fh = fopen($unicodeDataFile,'r');
		if (!$fh)	return false;

			// key = utf8 char (single codepoint), value = utf8 string (codepoint sequence)
			// note: we use the UTF-8 characters here and not the Unicode numbers to avoid conversion roundtrip in utf8_strtolower/-upper)
		$this->caseFolding['utf-8'] = array();
		$utf8CaseFolding =& $this->caseFolding['utf-8']; // a shorthand
		$utf8CaseFolding['toUpper'] = array();
		$utf8CaseFolding['toLower'] = array();
		$utf8CaseFolding['toTitle'] = array();

		while (!feof($fh))	{
			$line = fgets($fh);
				// has also other info like character class (digit, white space, etc.) and more
			list($char,,,,,,,,,,,,$upper,$lower,$title,) = split(';', rtrim($line));
			$char = $this->UnumberToChar(hexdec($char));
			if ($upper)	$utf8CaseFolding['toUpper'][$char] = $this->UnumberToChar(hexdec($upper));
			if ($lower)	$utf8CaseFolding['toLower'][$char] = $this->UnumberToChar(hexdec($lower));
				// store "title" only when different from "upper" (only a few)
			if ($title && $title != $upper)	$utf8CaseFolding['toTitle'][$char] = $this->UnumberToChar(hexdec($title));
		}
		fclose($fh);

			// process additional Unicode data for casing (allow folded characters to expand into a sequence)
		$specialCasingFile = PATH_t3lib.'unidata/SpecialCasing.txt';
		if (t3lib_div::validPathStr($specialCasingFile) && @is_file($specialCasingFile))	{

			$fh = fopen($specialCasingFile,'r');
			if ($fh)	{
				while (!feof($fh))	{
					$line = fgets($fh);
					if ($line{0} != '#' && trim($line) != '')	{

						list($char,$lower,$title,$upper,$cond) = t3lib_div::trimExplode(';', $line);
						if ($cond == '' || $cond{0} == '#')	{
							$utf8_char = $this->UnumberToChar(hexdec($char));
							if ($char != $lower)	{
								$arr = split(' ',$lower);
								for ($i=0; isset($arr[$i]); $i++)	$arr[$i] = $this->UnumberToChar(hexdec($arr[$i]));
								$utf8CaseFolding['toLower'][$utf8_char] = implode($arr);
							}
							if ($char != $title && $title != $upper)	{
								$arr = split(' ',$title);
								for ($i=0; isset($arr[$i]); $i++)	$arr[$i] = $this->UnumberToChar(hexdec($arr[$i]));
								$utf8CaseFolding['toTitle'][$utf8_char] = implode($arr);
							}
							if ($char != $upper)	{
									$arr = split(' ',$upper);
								for ($i=0; isset($arr[$i]); $i++)	$arr[$i] = $this->UnumberToChar(hexdec($arr[$i]));
								$utf8CaseFolding['toUpper'][$utf8_char] = implode($arr);
							}
						}
					}
				}
				fclose($fh);
			}
		}

		if ($cacheFile)	{
				t3lib_div::writeFile($cacheFile,serialize($utf8CaseFolding));
		}

		return 3;
	}

	/**
	 * This function initializes the folding table for a charset other than UTF-8.
	 * This function is automatically called by the case folding functions.
	 *
	 * @return	integer		Returns FALSE on error, a TRUE value on success: 1 table already loaded, 2, cached version, 3 table parsed (and cached).
	 * @access private
	 */
	function initCaseFolding($charset)	{
			// Only process if the case table is not yet loaded:
		if (is_array($this->caseFolding[$charset]))	return 1;

			// Use cached version if possible
		$cacheFile = t3lib_div::getFileAbsFileName('typo3temp/cscase_'.$charset.'.tbl');
		if ($cacheFile && @is_file($cacheFile))	{
			$this->caseFolding[$charset] = unserialize(t3lib_div::getUrl($cacheFile));
			return 2;
		}

			// init UTF-8 conversion for this charset
		if (!$this->initCharset($charset))	{
			return false;
		}

			// UTF-8 case folding is used as the base conversion table
		if (!$this->initCaseFoldingUTF8())	{
			return false;
		}

		$nochar = chr($this->noCharByteVal);
		foreach ($this->parsedCharsets[$charset]['local'] as $ci => $utf8)	{
				// reconvert to charset (don't use chr() of numeric value, might be muli-byte)
			$c = $this->conv($utf8, 'utf-8', $charset);

			$cc = $this->conv($this->caseFolding['utf-8']['toUpper'][$utf8], 'utf-8', $charset);
			if ($cc && $cc != $nochar)	$this->caseFolding[$charset]['toUpper'][$c] = $cc;

			$cc = $this->conv($this->caseFolding['utf-8']['toLower'][$utf8], 'utf-8', $charset);
			if ($cc && $cc != $nochar)	$this->caseFolding[$charset]['toLower'][$c] = $cc;

			$cc = $this->conv($this->caseFolding['utf-8']['toTitle'][$utf8], 'utf-8', $charset);
			if ($cc && $cc != $nochar)	$this->caseFolding[$charset]['toTitle'][$c] = $cc;
		}

			// add the ASCII case table
		for ($i=ord('a'); $i<=ord('z'); $i++)	{
			$this->caseFolding[$charset]['toUpper'][chr($i)] = chr($i-32);
		}
		for ($i=ord('A'); $i<=ord('Z'); $i++)	{
			$this->caseFolding[$charset]['toLower'][chr($i)] = chr($i+32);
		}

		if ($cacheFile)	{
				t3lib_div::writeFile($cacheFile,serialize($this->caseFolding[$charset]));
		}

		return 3;
	}

















	/********************************************
	 *
	 * String operation functions
	 *
	 ********************************************/

	/**
	 * Cuts a string short at a given byte length.
	 *
	 * @param	string		the character set
	 * @param	string		character string
	 * @param	integer		the byte length
	 * @return	string		the shortened string
	 * @see mb_strcut()
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function strtrunc($charset,$string,$len)	{
		if ($len <= 0)	return '';

		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['t3lib_cs_utils'] == 'mbstring')	{
			return mb_strcut($string,0,$len,$charset);
		} elseif ($charset == 'utf-8')	{
			return $this->utf8_strtrunc($string);
		} elseif ($this->eucBasedSets[$charset])	{
			return $this->euc_strtrunc($string,$charset);
		} elseif ($this->twoByteSets[$charset])	{
			if ($len % 2)	$len--;		// don't cut at odd positions
		} elseif ($this->fourByteSets[$charset])	{
			$x = $len % 4;
			$len -= $x;	// realign to position dividable by four
		}
		// treat everything else as single-byte encoding
		return substr($string,0,$len);
	}

	/**
	 * Returns a part of a string.
	 *
	 * @param	string		the character set
	 * @param	string		character string
	 * @param	int		start position (character position)
	 * @param	int		length (in characters)
	 * @return	string		the substring
	 * @see substr(), mb_substr()
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function substr($charset,$string,$start,$len=null)	{
		if ($len===0)	return '';

		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['t3lib_cs_utils'] == 'mbstring')	{
				// cannot omit $len, when specifying charset
			if ($len==null)	{
				$enc = mb_internal_encoding();	// save internal encoding
				mb_internal_encoding('utf-8');
				$str = mb_substr($string,$start);
				mb_internal_encoding($enc);	// restore internal encoding

				return $str;
			}
			else	return mb_substr($string,$start,$len,'utf-8');
		} elseif ($charset == 'utf-8')	{
			return $this->utf8_substr($string,$start,$len);
		} elseif ($this->eucBasedSets[$charset])	{
			return $this->euc_substr($string,$start,$charset,$len);
		} elseif ($this->twoByteSets[$charset])	{
			return substr($string,$start*2,$len*2);
		} elseif ($this->fourByteSets[$charset])	{
			return substr($string,$start*4,$len*4);
		}

		// treat everything else as single-byte encoding
		return substr($string,$start,$len);
	}

	/**
	 * Truncates a string and pre-/appends a string.
	 *
	 * @param	string		the character set
	 * @param	string		character string
	 * @param	int		length (in characters)
	 * @param	string		crop signifier
	 * @return	string		the shortened string
	 * @see substr(), mb_strimwidth()
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function crop($charset,$string,$len,$crop='')	{
		if ($len == 0)	return $crop;

		if ($charset == 'utf-8')	{
			$i = $this->utf8_char2byte_pos($string,$len);
		} elseif ($this->eucBasedSets[$charset])	{
			$i = $this->euc_char2byte_pos($string,$len,$charset);
		} else {
			if ($len > 0)	{
				$i = $len;
			} else {
				$i = strlen($string)+$len;
				if ($i<=0)	$i = false;
			}
		}

		if ($i === false)	{	// $len outside actual string length
			return $string;
		} else	{
			if ($len > 0)	{
				if ($string{$i+1})	{
					return substr($string,0,$i).$crop;
				}
			} else {
				if ($string{$i-1})	{
					return $crop.substr($string,$i);
				}
			}
		}

		return $string;
	}

	/**
	 * Counts the number of characters.
	 *
	 * @param	string		the character set
	 * @param	string		character string
	 * @return	integer		the number of characters
	 * @see strlen()
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function strlen($charset,$string)	{
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['t3lib_cs_utils'] == 'mbstring')	{
			return mb_strlen($string,$charset);
		} elseif ($charset == 'utf-8')	{
			return $this->utf8_strlen($string);
		} elseif ($this->eucBasedSets[$charset])	{
			return $this->euc_strlen($string,$charset);
		} elseif ($this->twoByteSets[$charset])	{
			return strlen($string)/2;
		} elseif ($this->fourByteSets[$charset])	{
			return strlen($string)/4;
		}
		// treat everything else as single-byte encoding
		return strlen($string);
	}

	/**
	 * Translates all characters of a string into their respective case values.
	 * Unlike strtolower() and strtoupper() this method is locale independent.
	 *
	 * Real case folding is language dependent, this method ignores this fact.
	 *
	 * @param	string		string
	 * @return	string		the converted string
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 * @see strtolower(), strtoupper()
	 */
	function conv_case($charset,$string,$case)	{
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['t3lib_cs_utils'] == 'mbstring' &&
			float(phpversion()) >= 4.3)	{
			if ($case == 'toLower')	{
				return mb_strtolower($str,'utf-8');
			} else {
				return mb_strtoupper($str,'utf-8');
			}
		} elseif ($charset == 'utf-8')	{
			return $this->utf8_conv_case($string,$case);
		} elseif ($this->eucBasedSets[$charset])	{
			return $this->euc_conv_case($string,$case,$charset);
		}

		// treat everything else as single-byte encoding
		if (!$this->initCaseFolding($charset))	return $string;	// do nothing

		$out = '';
		$caseConv =& $this->caseFolding[$charset][$case];
		for($i=0; $c=$string{$i}; $i++)	{
			$cc = $caseConv[$c];
			if ($cc)	{
				$out .= $cc;
			} else {
				$out .= $c;
			}
		}

		// is a simple strtr() faster or slower than the code above?
		// perhaps faster for small single-byte tables but slower for large multi-byte tables?
		//
		// return strtr($string,$this->caseFolding[$charset][$case]);

		return $out;
	}














	/********************************************
	 *
	 * Internal UTF-8 string operation functions
	 *
	 ********************************************/

	/**
	 * Truncates a string in UTF-8 short at a given byte length.
	 *
	 * @param	string		UTF-8 multibyte character string
	 * @param	integer		the byte length
	 * @return	string		the shortened string
	 * @see mb_strcut()
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function utf8_strtrunc($str,$len)	{
		$i = $len-1;
		if (ord($str{$i}) & 0x80) { // part of a multibyte sequence
			for (; $i>0 && !(ord($str{$i}) & 0x40); $i--)	;	// find the first byte
			if ($i <= 0)	return ''; // sanity check
			for ($bc=0, $mbs=ord($str{$i}); $mbs & 0x80; $mbs = $mbs << 1)	$bc++;	// calculate number of bytes
			if ($bc+$i > $len)	return substr($str,0,$i);
                        // fallthru: multibyte char fits into length
		}
		return substr($str,$len);
	}

	/**
	 * Returns a part of a UTF-8 string.
	 *
	 * @param	string		$str	UTF-8 string
	 * @param	int		$start	start position (character position)
	 * @param	int		$len	length (in characters)
	 * @return	string		the substring
	 * @see substr()
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function utf8_substr($str,$start,$len=null)	{
		$byte_start = $this->utf8_char2byte_pos($str,$start);
		if ($byte_start === false)	return false;	// $start outside string length

		$str = substr($str,$byte_start);

		if ($len!=null)	{
			$byte_end = $this->utf8_char2byte_pos($str,$len);
			if ($byte_end === false)	// $len outside actual string length
				return $str;
			else
				return substr($str,0,$byte_end);
		}
		else	return $str;
	}

	/**
	 * Counts the number of characters of a string in UTF-8.
	 *
	 * @param	string		UTF-8 multibyte character string
	 * @return	int		the number of characters
	 * @see strlen()
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function utf8_strlen($str)	{
		$n=0;
		for($i=0; $str{$i}; $i++)	{
			$c = ord($str{$i});
			if (!($c & 0x80))	// single-byte (0xxxxxx)
				$n++;
			elseif (($c & 0xC0) == 0xC0)	// multi-byte starting byte (11xxxxxx)
				$n++;
		}
		return $n;
	}

	/**
	 * Find position of first occurrence of a string, both arguments are in UTF-8.
	 *
	 * @param	string		UTF-8 string to search in
	 * @param	string		UTF-8 string to search for
	 * @param	int		positition to start the search
	 * @return	int		the character position
	 * @see strpos()
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function utf8_strpos($haystack,$needle,$offset=0)	{
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['t3lib_cs_utils'] == 'mbstring')	{
			return mb_strpos($haystack,$needle,'utf-8');
		}

		$byte_offset = $this->utf8_char2byte_pos($haystack,$offset);
		if ($byte_offset === false)	return false; // offset beyond string length

		$byte_pos = strpos($haystack,$needle,$byte_offset);
		if ($byte_pos === false)	return false; // needle not found

		return $this->utf8_byte2char_pos($haystack,$byte_pos);
	}

	/**
	 * Find position of last occurrence of a char in a string, both arguments are in UTF-8.
	 *
	 * @param	string		UTF-8 string to search in
	 * @param	char		UTF-8 character to search for
	 * @return	int		the character position
	 * @see strrpos()
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function utf8_strrpos($haystack,$needle)	{
		if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['t3lib_cs_utils'] == 'mbstring')	{
			return mb_strrpos($haystack,$needle,'utf-8');
		}

		$byte_pos = strrpos($haystack,$needle);
		if ($byte_pos === false)	return false; // needle not found

		return $this->utf8_byte2char_pos($haystack,$byte_pos);
	}

	/**
	 * Translates a character position into an 'absolute' byte position.
	 *
	 * @param	string		UTF-8 string
	 * @param	int		character position (negative values start from the end)
	 * @return	int		byte position
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function utf8_char2byte_pos($str,$pos)	{
		$n = 0;		// number of characters found
		$p = abs($pos);	// number of characters wanted

		if ($pos >= 0)	{
			$i = 0;
			$d = 1;
		} else {
			$i = strlen($str)-1;
			$d = -1;
		}

		for( ; $str{$i} && $n<$p; $i+=d)	{
			$c = (int)ord($str{$i});
			if (!($c & 0x80))	// single-byte (0xxxxxx)
				$n++;
			elseif (($c & 0xC0) == 0xC0)	// multi-byte starting byte (11xxxxxx)
				$n++;
		}
		if (!$str{$i})	return false; // offset beyond string length

		if ($pos >= 0)	{
				// skip trailing multi-byte data bytes
			while ((ord($str{$i}) & 0x80) && !(ord($str{$i}) & 0x40)) { $i++; }
		} else {
				// correct offset
			$i++;
		}

		return $i;
	}

	/**
	 * Translates an 'absolute' byte position into a character position.
	 *
	 * @param	string		UTF-8 string
	 * @param	int		byte position
	 * @return	int		character position
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function utf8_byte2char_pos($str,$pos)	{
		$n = 0;	// number of characters
		for($i=$pos; $i>0; $i--)	{
			$c = (int)ord($str{$i});
			if (!($c & 0x80))	// single-byte (0xxxxxx)
				$n++;
			elseif (($c & 0xC0) == 0xC0)	// multi-byte starting byte (11xxxxxx)
				$n++;
		}
		if (!$str{$i})	return false; // offset beyond string length

		return $n;
	}

	/**
	 * Translates all characters of an UTF-8 string into their respective case values.
	 *
	 * @param	string		UTF-8 string
	 * @param	string		conversion: 'toLower' or 'toUpper'
	 * @return	string		the converted string
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 * @see strtolower(), strtoupper(), mb_convert_case()
	 */
	function utf8_conv_case($str,$case)	{
		if (!$this->initCaseFoldingUTF8())	return $str;	// do nothing

		$out = '';
		$caseConv =& $this->caseFolding['utf-8'][$case];
		for($i=0; $str{$i}; $i++)	{
			$c = ord($str{$i});
			if (!($c & 0x80))	// single-byte (0xxxxxx)
				$mbc = $str{$i};
			elseif (($c & 0xC0) == 0xC0)	{	// multi-byte starting byte (11xxxxxx)
				for ($bc=0; $c & 0x80; $c = $c << 1) { $bc++; }	// calculate number of bytes
				$mbc = substr($str,$i,$bc);
				$i += $bc-1;
			}

			$cc = $caseConv[$mbc];
			if ($cc)	{
				$out .= $cc;
			} else {
				$out .= $mbc;
			}
		}

		return $out;
	}


















	/********************************************
	 *
	 * Internal EUC string operation functions
	 *
	 * Extended Unix Code:
	 *  ASCII compatible 7bit single bytes chars
	 *  8bit two byte chars
	 *
	 * Shift-JIS is treated as a special case.
	 *
	 ********************************************/

	/**
	 * Cuts a string in the EUC charset family short at a given byte length.
	 *
	 * @param	string		EUC multibyte character string
	 * @param	integer		the byte length
	 * @param	string		the charset
	 * @return	string		the shortened string
	 * @see mb_strcut()
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function euc_strtrunc($str,$len,$charset)	 {
		$sjis = ($charset == 'shift_jis');
		for ($i=0; $str{$i} && $i<$len; $i++) {
			$c = ord($str{$i});
			if ($sjis)	{
				if (($c >= 0x80 && $c < 0xA0) || ($c >= 0xE0))	$i++;	// advance a double-byte char
			}
			else	{
				if ($c >= 0x80)	$i++;	// advance a double-byte char
			}
		}
		if (!$str{$i})	return $str;	// string shorter than supplied length

		if ($i>$len)
			return substr($str,0,$len-1);	// we ended on a first byte
		else
			return substr($str,0,$len);
        }

	/**
	 * Returns a part of a string in the EUC charset family.
	 *
	 * @param	string		EUC multibyte character string
	 * @param	int		start position (character position)
	 * @param	string		the charset
	 * @param	int		length (in characters)
	 * @return	string		the substring
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function euc_substr($str,$start,$charset,$len=null)	{
		$byte_start = $this->euc_char2byte_pos($str,$start,$charset);
		if ($byte_start === false)	return false;	// $start outside string length

		$str = substr($str,$byte_start);

		if ($len!=null)	{
			$byte_end = $this->euc_char2byte_pos($str,$len,$charset);
			if ($byte_end === false)	// $len outside actual string length
				return $str;
			else
				return substr($str,0,$byte_end);
		}
		else	return $str;
	}

	/**
	 * Counts the number of characters of a string in the EUC charset family.
	 *
	 * @param	string		EUC multibyte character string
	 * @param	string		the charset
	 * @return	int		the number of characters
	 * @see strlen()
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function euc_strlen($str,$charset)	 {
		$sjis = ($charset == 'shift_jis');
		$n=0;
		for ($i=0; $str{$i}; $i++) {
			$c = ord($str{$i});
			if ($sjis)	{
				if (($c >= 0x80 && $c < 0xA0) || ($c >= 0xE0))	$i++;	// advance a double-byte char
			}
			else	{
				if ($c >= 0x80)	$i++;	// advance a double-byte char
			}

			$n++;
		}

		return $n;
        }

	/**
	 * Translates a character position into an 'absolute' byte position.
	 *
	 * @param	string		EUC multibyte character string
	 * @param	int		character position (negative values start from the end)
	 * @param	string		the charset
	 * @return	int		byte position
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 */
	function euc_char2byte_pos($str,$pos,$charset)	{
		$sjis = ($charset == 'shift_jis');
		$n = 0; // number of characters seen
		$p = abs($pos);	// number of characters wanted

		if ($pos >= 0)	{
			$i = 0;
			$d = 1;
		} else {
			$i = strlen($str)-1;
			$d = -1;
		}

		for ( ; $str{$i} && $n<$p; $i+=$d) {
			$c = ord($str{$i});
			if ($sjis)	{
				if (($c >= 0x80 && $c < 0xA0) || ($c >= 0xE0))	$i+=$d;	// advance a double-byte char
			}
			else	{
				if ($c >= 0x80)	$i+=$d;	// advance a double-byte char
			}

			$n++;
		}
		if (!$str{$i})	return false; // offset beyond string length

		if ($pos < 0)	$i++;	// correct offset

		return $i;
	}

	/**
	 * Translates all characters of a string in the EUC charset family into their respective case values.
	 *
	 * @param	string		EUC multibyte character string
	 * @param	string		conversion: 'toLower' or 'toUpper'
	 * @param	string		the charset
	 * @return	string		the converted string
	 * @author	Martin Kutschker <martin.t.kutschker@blackbox.net>
	 * @see strtolower(), strtoupper(), mb_convert_case()
	 */
	function euc_conv_case($str,$case,$charset)	{
		if (!$this->initCaseFolding($charset))	return $str;	// do nothing

		$sjis = ($charset == 'shift_jis');
		$out = '';
		$caseConv =& $this->caseFolding[$charset][$case];
		for($i=0; $mbc=$str{$i}; $i++)	{
			$c = ord($str{$i});

			if ($sjis)	{
				if (($c >= 0x80 && $c < 0xA0) || ($c >= 0xE0))	{	// a double-byte char
					$mbc = substr($str,$i,2);
					$i++;
				}
			}
			else	{
				if ($c >= 0x80)	{	// a double-byte char
					$mbc = substr($str,$i,2);
					$i++;
				}
			}

			$cc = $caseConv[$mbc];
			if ($cc)	{
				$out .= $cc;
			} else {
				$out .= $mbc;
			}
		}

		return $out;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_cs.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_cs.php']);
}
?>
