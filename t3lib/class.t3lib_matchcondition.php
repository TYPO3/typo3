<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Contains class for Matching TypoScript conditions
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   77: class t3lib_matchCondition
 *   91:     function match($string)
 *  311:     function testNumber($test,$value)
 *  333:     function matchWild($haystack,$needle)
 *  363:     function whichDevice($useragent)
 *  413:     function browserInfo($useragent)
 *  517:     function browserInfo_version($tmp)
 *  529:     function getGlobal($var,$inArr='')
 *  554:     function getGP_ENV_TSFE($var)
 *
 * TOTAL FUNCTIONS: 8
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */












/**
 * Matching TypoScript conditions
 *
 * Used with the TypoScript parser.
 * Matches browserinfo, IPnumbers for use with templates
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 * @see t3lib_TStemplate::matching(), t3lib_TStemplate::generateConfig()
 */
class t3lib_matchCondition {
	var $matchAlternative=array();		// If this array has elements, the matching returns true if a whole "matchline" is found in the array!
	var $matchAll=0;					// If set all is matched!

	var $altRootLine=array();

	/**
	 * Evaluates a TypoScript condition given as input, eg. "[browser=net][...(other conditions)...]"
	 *
	 * @param	string		The condition to match against its criterias.
	 * @return	boolean		Returns true or false based on the evaluation.
	 * @see t3lib_tsparser::parse()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=292&cHash=c6c7d43d2f
	 */
	function match($string)	{
		if ( !is_array( $this->altRootLine ) ) {
			$this->altRootLine = array();
		}

		if ($this->matchAll)	return true;
		if (count($this->matchAlternative))	{
			return in_array($string,$this->matchAlternative);
		}

		if (!$this->browserInfoArray)	{
			$this->browserInfoArray = $this->browserInfo(t3lib_div::getIndpEnv('HTTP_USER_AGENT'));
		}
		$browserInfo = $this->browserInfoArray;
		$string = trim($string);
		$string = substr($string,1,strlen($string)-2);
		$parts = explode('][',$string);
		foreach ($parts as $val)	{
			$pcs = explode('=',$val,2);
			$switchKey = trim($pcs[0]);
			switch($switchKey)	{
				case 'browser':
					$values = explode(',',$pcs[1]);
					while(list(,$test)=each($values))	{
						if (strstr($browserInfo['browser'].$browserInfo['version'],trim($test)))	{
							return true;
						}
					}
				break;
				case 'version':
					$values = explode(',',$pcs[1]);
					while(list(,$test)=each($values))	{
						$test = trim($test);
						if ($test)	{
							if (strcspn($test,'=<>')==0)	{
								switch(substr($test,0,1))	{
									case '=':
										if (doubleval(substr($test,1))==$browserInfo['version'])	return true;
									break;
									case '<':
										if (doubleval(substr($test,1))>$browserInfo['version'])	return true;
									break;
									case '>':
										if (doubleval(substr($test,1))<$browserInfo['version'])	return true;
									break;
								}
							} else {
								if (strpos(' '.$browserInfo['version'],$test)==1)	{return true;}
							}
						}
					}
				break;
				case 'system':
					$values = explode(',',$pcs[1]);
					while(list(,$test)=each($values))	{
						$test = trim($test);
						if ($test)	{
							if (strpos(' '.$browserInfo['system'],$test)==1)	{return true;}
						}
					}
				break;
				case 'device':
					$values = explode(',',$pcs[1]);
					if (!isset($this->deviceInfo))	{
						$this->deviceInfo = $this->whichDevice(t3lib_div::getIndpEnv('HTTP_USER_AGENT'));
					}
					while(list(,$test)=each($values))	{
						$test = trim($test);
						if ($test)	{
							if ($this->deviceInfo==$test)	{return true;}
						}
					}
				break;
				case 'useragent':
					$test = trim($pcs[1]);
					if ($test)	{
						return $this->matchWild($browserInfo['useragent'],$test);
					}
				break;
				case 'language':
					$values = explode(',',$pcs[1]);
					while(list(,$test)=each($values))	{
						$test = trim($test);
						if ($test)	{
							if (ereg('^\*.+\*$',$test))	{
								$allLanguages = split('[,;]',t3lib_div::getIndpEnv('HTTP_ACCEPT_LANGUAGE'));
								if (in_array(substr($test,1,-1), $allLanguages))	{return true;}
							} else {
								if (t3lib_div::getIndpEnv('HTTP_ACCEPT_LANGUAGE') == $test)	{return true;}
							}
						}
					}
				break;
				case 'IP':
					if (t3lib_div::cmpIP(t3lib_div::getIndpEnv('REMOTE_ADDR'), $pcs[1]))	{return true;}
				break;
				case 'hostname':
					if (t3lib_div::cmpFQDN(t3lib_div::getIndpEnv('REMOTE_ADDR'), $pcs[1]))  {return true;}
				break;
					// hour, minute, dayofweek, dayofmonth, month
				case 'hour':
				case 'minute':
				case 'dayofweek':
				case 'dayofmonth':
				case 'month':
					$theEvalTime = $GLOBALS['SIM_EXEC_TIME'];	// In order to simulate time properly in templates.
					switch($switchKey)	{
						case 'hour':		$theTestValue = date('H',$theEvalTime);	break;
						case 'minute':		$theTestValue = date('i',$theEvalTime);	break;
						case 'dayofweek':	$theTestValue = date('w',$theEvalTime);	break;
						case 'dayofmonth':	$theTestValue = date('d',$theEvalTime);	break;
						case 'month':		$theTestValue = date('m',$theEvalTime);	break;
					}
					$theTestValue = intval($theTestValue);
						// comp
					$values = explode(',',$pcs[1]);
					reset($values);
					while(list(,$test)=each($values))	{
						$test = trim($test);
						if (t3lib_div::testInt($test))	{$test='='.$test;}
						if ($test)	{
							if ($this->testNumber($test,$theTestValue)) {return true;}
						}
					}
				break;
				case 'usergroup':
					if ($GLOBALS['TSFE']->gr_list!='0,-1')	{		// '0,-1' is the default usergroups when not logged in!
						$values = explode(',',$pcs[1]);
						while(list(,$test)=each($values))	{
							$test = trim($test);
							if ($test)	{
								if ($test=='*' || t3lib_div::inList($GLOBALS['TSFE']->gr_list,$test))	{return true;}
							}
						}
					}
				break;
				case 'loginUser':
					if ($GLOBALS['TSFE']->loginUser)	{
						$values = explode(',',$pcs[1]);
						while(list(,$test)=each($values))	{
							$test = trim($test);
							if ($test)	{
								if ($test=='*' || !strcmp($GLOBALS['TSFE']->fe_user->user['uid'],$test))	{return true;}
							}
						}
					}
				break;
				case 'globalVar':
					$values = explode(',',$pcs[1]);
					while(list(,$test)=each($values))	{
						$test = trim($test);
						if ($test)	{
							$point = strcspn($test,'=<>');
							$theVarName = substr($test,0,$point);
							$nv = $this->getGP_ENV_TSFE(trim($theVarName));
							$testValue = substr($test,$point);

							if ($this->testNumber($testValue,$nv)) {return true;}
						}
					}
				break;
				case 'globalString':
					$values = explode(',',$pcs[1]);
					while(list(,$test)=each($values))	{
						$test = trim($test);
						if ($test)	{
							$point = strcspn($test,'=');
							$theVarName = substr($test,0,$point);
							$nv = $this->getGP_ENV_TSFE(trim($theVarName));
							$testValue = substr($test,$point+1);

							if ($this->matchWild($nv,trim($testValue))) {return true;}
						}
					}
				break;
				case 'treeLevel':
					$values = explode(',',$pcs[1]);
					$theRootLine = is_array($GLOBALS['TSFE']->tmpl->rootLine) ? $GLOBALS['TSFE']->tmpl->rootLine : $this->altRootLine;
					$theRLC = count($theRootLine)-1;
					while(list(,$test)=each($values))	{
						$test = trim($test);
						if ($test==$theRLC)	{	return true;	}
					}
				break;
				case 'PIDupinRootline':
				case 'PIDinRootline':
					$values = explode(',',$pcs[1]);
					if (($switchKey=='PIDinRootline') || (!in_array($GLOBALS['TSFE']->id,$values))) {
						$theRootLine = is_array($GLOBALS['TSFE']->tmpl->rootLine) ? $GLOBALS['TSFE']->tmpl->rootLine : $this->altRootLine;
						reset($values);
						while(list(,$test)=each($values))	{
							$test = trim($test);
							reset($theRootLine);
							while(list($rl_key,$rl_dat)=each($theRootLine))	{
								if ($rl_dat['uid']==$test)	{	return true;	}
							}
						}
					}
				break;
				case 'compatVersion':
					return t3lib_div::compat_version($pcs[1]);
				break;
				case 'userFunc':
					$values = split('\(|\)',$pcs[1]);
					$funcName=trim($values[0]);
					$funcValue = t3lib_div::trimExplode(',',$values[1]);
					$pre = $GLOBALS['TSFE']->TYPO3_CONF_VARS['FE']['userFuncClassPrefix'];
					if ($pre &&
						!t3lib_div::isFirstPartOfStr(trim($funcName),$pre) &&
						!t3lib_div::isFirstPartOfStr(trim($funcName),'tx_')
					)	{
						if (is_object($GLOBALS['TT']))	$GLOBALS['TT']->setTSlogMessage('Match condition: Function "'.$funcName.'" was not prepended with "'.$pre.'"',3);
						return false;
					}
					if (function_exists($funcName) && call_user_func($funcName, $funcValue[0]))	{
						return true;
					}
				break;
			}
		}
	}

	/**
	 * Will evaluate a $value based on an operator: "<", ">" or "=" (default)
	 *
	 * @param	string		The value to compare with on the form [operator][number]. Eg. "< 123"
	 * @param	integer		The number
	 * @return	boolean		If $value is "50" and $test is "< 123" then it will return true.
	 */
	function testNumber($test,$value) {
		$test = trim($test);
		switch(substr($test,0,1))	{
			case '<':
				if (doubleval(substr($test,1))>$value)	return true;
			break;
			case '>':
				if (doubleval(substr($test,1))<$value)	return true;
			break;
			default:
				if (trim(substr($test,1))==$value)	return true;
			break;
		}
	}

	/**
	 * Matching two strings against each other, supporting a "*" wildcard
	 *
	 * @param	string		The string in which to find $needle.
	 * @param	string		The string to find in $haystack
	 * @return	boolean		Returns true if $needle matches or is found in (according to wildcards) in $haystack. Eg. if $haystack is "Netscape 6.5" and $needle is "Net*" or "Net*ape" then it returns true.
	 */
	function matchWild($haystack,$needle)	{
		if ($needle && $haystack)	{
			$regex = '/^'.preg_quote($needle,'/').'$/';
			$regex = str_replace('\\*', '.*', $regex);	// 1st fix: Replace \* with .* because * is our wildcard
			$regex = str_replace('\\\\.*', '\\*', $regex);	// 2nd fix: Match needle if the * was escaped

			if (preg_match($regex, $haystack, $res)) return true;
		}
	}

	/**
	 * Returns a code for a browsing device based on the input useragent string
	 *
	 * @param	string		User agent string from browser, t3lib_div::getIndpEnv('HTTP_USER_AGENT')
	 * @return	string		A code. See link.
	 * @access private
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=296&cHash=a8ae66c7d6
	 */
	function whichDevice($useragent)	{
		$agent=strtolower(trim($useragent));
			// pda
		if(	strstr($agent, 'avantgo'))	{
			return 'pda';
		}

			// wap
		$browser=substr($agent,0,4);
		$wapviwer=substr(stristr($agent,'wap'),0,3);
		if(	$wapviwer=='wap' ||
			$browser=='noki' ||
			$browser== 'eric' ||
			$browser== 'r380' ||
			$browser== 'up.b' ||
			$browser== 'winw' ||
			$browser== 'wapa')	{
				return 'wap';
		}

			// grabber
		if(	strstr($agent, 'g.r.a.b.') ||
			strstr($agent, 'utilmind httpget') ||
			strstr($agent, 'webcapture') ||
			strstr($agent, 'teleport') ||
			strstr($agent, 'webcopier'))	{
			return 'grabber';
		}

			// robots
		if(	strstr($agent, 'crawler') ||
			strstr($agent, 'spider') ||
			strstr($agent, 'googlebot') ||
			strstr($agent, 'searchbot') ||
			strstr($agent, 'infoseek') ||
			strstr($agent, 'altavista') ||
			strstr($agent, 'diibot'))	{
			return 'robot';
		}

			// Hook for extending device recognition capabilities:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_matchcondition.php']['devices_class']))	{
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_matchcondition.php']['devices_class'] as $_classRef)	{
				$_procObj = &t3lib_div::getUserObj($_classRef);
				return $_procObj->whichDevice_ext($useragent);
			}
		}

	}

	/**
	 * Generates an array with abstracted browser information
	 * In the function match() this method is called and the result stored in $this->browserInfoArray
	 *
	 * @param	string		The useragent string, t3lib_div::getIndpEnv('HTTP_USER_AGENT')
	 * @return	array		Contains keys "browser", "version", "system"
	 * @access private
	 * @see match()
	 */
	function browserInfo($useragent)	{
		$useragent = trim($useragent);
		$browserInfo=Array();
		$browserInfo['useragent']=$useragent;
		if ($useragent)	{
			// browser
			if (strstr($useragent,'MSIE'))	{
				$browserInfo['browser']='msie';
			} elseif(strstr($useragent,'Konqueror'))	{
				$browserInfo['browser']='konqueror';
			} elseif(strstr($useragent,'Opera'))	{
				$browserInfo['browser']='opera';
			} elseif(strstr($useragent,'Lynx'))	{
				$browserInfo['browser']='lynx';
			} elseif(strstr($useragent,'PHP'))	{
				$browserInfo['browser']='php';
			} elseif(strstr($useragent,'AvantGo'))	{
				$browserInfo['browser']='avantgo';
			} elseif(strstr($useragent,'WebCapture'))	{
				$browserInfo['browser']='acrobat';
			} elseif(strstr($useragent,'IBrowse'))	{
				$browserInfo['browser']='ibrowse';
			} elseif(strstr($useragent,'Teleport'))	{
				$browserInfo['browser']='teleport';
			} elseif(strstr($useragent,'Mozilla'))	{
				$browserInfo['browser']='netscape';
			} else {
				$browserInfo['browser']='unknown';
			}
			// version
			switch($browserInfo['browser'])	{
				case 'netscape':
					$browserInfo['version'] = $this->browserInfo_version(substr($useragent,8));
					if (strstr($useragent,'Netscape6')) {$browserInfo['version']=6;}
				break;
				case 'msie':
					$tmp = strstr($useragent,'MSIE');
					$browserInfo['version'] = $this->browserInfo_version(substr($tmp,4));
				break;
				case 'opera':
					$tmp = strstr($useragent,'Opera');
					$browserInfo['version'] = $this->browserInfo_version(substr($tmp,5));
				break;
				case 'lynx':
					$tmp = strstr($useragent,'Lynx/');
					$browserInfo['version'] = $this->browserInfo_version(substr($tmp,5));
				break;
				case 'php':
					$tmp = strstr($useragent,'PHP/');
					$browserInfo['version'] = $this->browserInfo_version(substr($tmp,4));
				break;
				case 'avantgo':
					$tmp = strstr($useragent,'AvantGo');
					$browserInfo['version'] = $this->browserInfo_version(substr($tmp,7));
				break;
				case 'acrobat':
					$tmp = strstr($useragent,'WebCapture');
					$browserInfo['version'] = $this->browserInfo_version(substr($tmp,10));
				break;
				case 'ibrowse':
					$tmp = strstr($useragent,'IBrowse/');
					$browserInfo['version'] = $this->browserInfo_version(substr($tmp,8));
				break;
				case 'konqueror':
					$tmp = strstr($useragent,'Konqueror/');
					$browserInfo['version'] = $this->browserInfo_version(substr($tmp,10));
				break;
			}
			// system
			$browserInfo['system']='';
			if (strstr($useragent,'Win'))	{
				// windows
				if (strstr($useragent,'Win98') || strstr($useragent,'Windows 98'))	{
					$browserInfo['system']='win98';
				} elseif (strstr($useragent,'Win95') || strstr($useragent,'Windows 95'))	{
					$browserInfo['system']='win95';
				} elseif (strstr($useragent,'WinNT') || strstr($useragent,'Windows NT'))	{
					$browserInfo['system']='winNT';
				} elseif (strstr($useragent,'Win16') || strstr($useragent,'Windows 311'))	{
					$browserInfo['system']='win311';
				}
			} elseif (strstr($useragent,'Mac'))	{
				$browserInfo['system']='mac';
				// unixes
			} elseif (strstr($useragent,'Linux'))	{
				$browserInfo['system']='linux';
			} elseif (strstr($useragent,'SGI') && strstr($useragent,' IRIX '))	{
				$browserInfo['system']='unix_sgi';
			} elseif (strstr($useragent,' SunOS '))	{
				$browserInfo['system']='unix_sun';
			} elseif (strstr($useragent,' HP-UX '))	{
				$browserInfo['system']='unix_hp';
			}
		}

		return $browserInfo;
	}

	/**
	 * Returns the version of a browser; Basically getting doubleval() of the input string, stripping of any non-numeric values in the beginning of the string first.
	 *
	 * @param	string		A string with version number, eg. "/7.32 blablabla"
	 * @return	double		Returns double value, eg. "7.32"
	 */
	function browserInfo_version($tmp)	{
		return doubleval(ereg_replace('^[^0-9]*','',$tmp));
	}

	/**
	 * Return global variable where the input string $var defines array keys separated by "|"
	 *
	 * @param	string		Global var key, eg. "HTTP_GET_VAR" or "HTTP_GET_VARS|id" to get the id GET parameter back.
	 * @param	array		Alternative array than $GLOBAL to get variables from.
	 * @return	mixed		Whatever value. If none, then blank string.
	 * @access private
	 */
	function getGlobal($var,$inArr='') {
		$vars = explode('|',$var);
		$c = count($vars);
		$k = trim($vars[0]);
		$theVar = is_array($inArr) ? $inArr[$k] : $GLOBALS[$k];

		for ($a=1;$a<$c;$a++) {
			if (!isset($theVar))	{break;}

			$key = trim($vars[$a]);
			if (is_object($theVar))	{
				$theVar = $theVar->$key;
			} elseif (is_array($theVar))	{
				$theVar = $theVar[$key];
			} else {
				return '';
			}
		}

		if (!is_array($theVar) && !is_object($theVar))	{
			return $theVar;
		} else {
			return '';
		}
	}

	/**
	 * Returns GP / ENV / TSFE vars
	 *
	 * @param	string		Identifier
	 * @return	mixed		The value of the variable pointed to.
	 * @access private
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=311&cHash=487cbd5cdf
	 */
	function getGP_ENV_TSFE($var) {
		$vars = explode(':',$var,2);
		if (count($vars)==1)	{
			$val = $this->getGlobal($var);
		} else {
			$splitAgain=explode('|',$vars[1],2);
			$k=trim($splitAgain[0]);
			if ($k)	{
				switch((string)trim($vars[0]))	{
					case 'GP':
						$val = t3lib_div::_GP($k);
					break;
					case 'TSFE':
						$val = $this->getGlobal('TSFE|'.$vars[1]);
						$splitAgain=0;	// getGlobal resolves all parts of the key, so no further splitting is needed
					break;
					case 'ENV':
						$val = getenv($k);
					break;
					case 'IENV':
						$val = t3lib_div::getIndpEnv($k);
					break;
					case 'LIT':
						return trim($vars[1]);	// return litteral value...
					break;
				}
					// If array:
				if (count($splitAgain)>1)	{
					if (is_array($val) && trim($splitAgain[1]))	{
						$val=$this->getGlobal($splitAgain[1],$val);
					} else {
						$val='';
					}
				}
			}
		}
		return $val;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_matchcondition.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_matchcondition.php']);
}
?>
