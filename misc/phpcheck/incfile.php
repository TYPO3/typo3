<?php
SetCookie("test_script_cookie", "Cookie Value!", 0, "/");


class t3lib_div {
	function trimExplode($delim, $string, $onlyNonEmptyValues=0)	{
		// This explodes a comma-list into an array where the values are parsed through trim();
		$temp = explode($delim,$string);
		$newtemp=array();
		while(list($key,$val)=each($temp))	{
			if (!$onlyNonEmptyValues || strcmp("",trim($val)))	{
				$newtemp[]=trim($val);
			}
		}
		reset($newtemp);
		return $newtemp;
	}
	function dirname($path)	{
		$p=t3lib_div::revExplode("/",$path,2);
		return count($p)==2?$p[0]:"";
	}
	function revExplode($delim, $string, $count=0)	{
		$temp = explode($delim,strrev($string),$count);
		while(list($key,$val)=each($temp))	{
			$temp[$key]=strrev($val);
		}
		$temp=array_reverse($temp);
		reset($temp);
		return $temp;
	}

	/**
	 * Abstraction method which returns System Environment Variables regardless of server OS, CGI/MODULE version etc. Basically this is SERVER variables for most of them.
	 * This should be used instead of getEnv() and HTTP_SERVER_VARS/ENV_VARS to get reliable values for all situations.
	 *
	 * Usage: 226
	 *
	 * @param	string		Name of the "environment variable"/"server variable" you wish to use. Valid values are SCRIPT_NAME, SCRIPT_FILENAME, REQUEST_URI, PATH_INFO, REMOTE_ADDR, REMOTE_HOST, HTTP_REFERER, HTTP_HOST, HTTP_USER_AGENT, HTTP_ACCEPT_LANGUAGE, QUERY_STRING, TYPO3_DOCUMENT_ROOT, TYPO3_HOST_ONLY, TYPO3_HOST_ONLY, TYPO3_REQUEST_HOST, TYPO3_REQUEST_URL, TYPO3_REQUEST_SCRIPT, TYPO3_REQUEST_DIR, TYPO3_SITE_URL, _ARRAY
	 * @return	string		Value based on the input key, independent of server/os environment.
	 */
	function getIndpEnv($getEnvName)	{
		global $HTTP_SERVER_VARS;
		/*
			Conventions:
			output from parse_url():
			URL:	http://username:password@192.168.1.4:8080/typo3/32/temp/phpcheck/index.php/arg1/arg2/arg3/?arg1,arg2,arg3&p1=parameter1&p2[key]=value#link1
			    [scheme] => 'http'
			    [user] => 'username'
			    [pass] => 'password'
			    [host] => '192.168.1.4'
				[port] => '8080'
			    [path] => '/typo3/32/temp/phpcheck/index.php/arg1/arg2/arg3/'
			    [query] => 'arg1,arg2,arg3&p1=parameter1&p2[key]=value'
			    [fragment] => 'link1'

				Further definition: [path_script] = '/typo3/32/temp/phpcheck/index.php'
									[path_dir] = '/typo3/32/temp/phpcheck/'
									[path_info] = '/arg1/arg2/arg3/'
									[path] = [path_script/path_dir][path_info]


			Keys supported:

			URI______:
				REQUEST_URI		=	[path]?[query]		= /typo3/32/temp/phpcheck/index.php/arg1/arg2/arg3/?arg1,arg2,arg3&p1=parameter1&p2[key]=value
				HTTP_HOST		=	[host][:[port]]		= 192.168.1.4:8080
				SCRIPT_NAME		=	[path_script]++		= /typo3/32/temp/phpcheck/index.php		// NOTICE THAT SCRIPT_NAME will return the php-script name ALSO. [path_script] may not do that (eg. '/somedir/' may result in SCRIPT_NAME '/somedir/index.php')!
				PATH_INFO		=	[path_info]			= /arg1/arg2/arg3/
				QUERY_STRING	=	[query]				= arg1,arg2,arg3&p1=parameter1&p2[key]=value
				HTTP_REFERER	=	[scheme]://[host][:[port]][path]	= http://192.168.1.4:8080/typo3/32/temp/phpcheck/index.php/arg1/arg2/arg3/?arg1,arg2,arg3&p1=parameter1&p2[key]=value
										(Notice: NO username/password + NO fragment)

			CLIENT____:
				REMOTE_ADDR		=	(client IP)
				REMOTE_HOST		=	(client host)
				HTTP_USER_AGENT	=	(client user agent)
				HTTP_ACCEPT_LANGUAGE	= (client accept language)

			SERVER____:
				SCRIPT_FILENAME	=	Absolute filename of script		(Differs between windows/unix). On windows 'C:\\blabla\\blabl\\' will be converted to 'C:/blabla/blabl/'

			Special extras:
				TYPO3_HOST_ONLY	=		[host]			= 192.168.1.4
				TYPO3_PORT		=		[port]			= 8080 (blank if 80, taken from host value)
				TYPO3_REQUEST_HOST = 	[scheme]://[host][:[port]]
				TYPO3_REQUEST_URL =		[scheme]://[host][:[port]][path]?[query]	(sheme will by default be 'http' until we can detect if it's https -
				TYPO3_REQUEST_SCRIPT =  [scheme]://[host][:[port]][path_script]
				TYPO3_REQUEST_DIR =		[scheme]://[host][:[port]][path_dir]
				TYPO3_SITE_URL = 		[scheme]://[host][:[port]][path_dir] of the TYPO3 website
				TYPO3_DOCUMENT_ROOT	=	Absolute path of root of documents:	TYPO3_DOCUMENT_ROOT.SCRIPT_NAME = SCRIPT_FILENAME (typically)

			Notice: [fragment] is apparently NEVER available to the script!


			Testing suggestions:
			- Output all the values.
			- In the script, make a link to the script it self, maybe add some parameters and click the link a few times so HTTP_REFERER is seen
			- ALSO TRY the script from the ROOT of a site (like 'http://www.mytest.com/' and not 'http://www.mytest.com/test/' !!)

		*/

#		if ($getEnvName=='HTTP_REFERER')	return '';
		switch((string)$getEnvName)	{
			case 'SCRIPT_NAME':
				return php_sapi_name()=='cgi' ? $HTTP_SERVER_VARS['PATH_INFO'] : $HTTP_SERVER_VARS['SCRIPT_NAME'];
			break;
			case 'SCRIPT_FILENAME':
				return str_replace('//','/', str_replace('\\','/', php_sapi_name()=='cgi'||php_sapi_name()=='isapi' ? $HTTP_SERVER_VARS['PATH_TRANSLATED']:$HTTP_SERVER_VARS['SCRIPT_FILENAME']));
			break;
			case 'REQUEST_URI':
				// Typical application of REQUEST_URI is return urls, forms submitting to itselt etc. Eg:	returnUrl='.rawurlencode(t3lib_div::getIndpEnv('REQUEST_URI'))
				if (!$HTTP_SERVER_VARS['REQUEST_URI'])	{	// This is for ISS/CGI which does not have the REQUEST_URI available.
					return '/'.ereg_replace('^/','',t3lib_div::getIndpEnv('SCRIPT_NAME')).
						($HTTP_SERVER_VARS['QUERY_STRING']?'?'.$HTTP_SERVER_VARS['QUERY_STRING']:'');
				} else return $HTTP_SERVER_VARS['REQUEST_URI'];
			break;
			case 'PATH_INFO':
					// $HTTP_SERVER_VARS['PATH_INFO']!=$HTTP_SERVER_VARS['SCRIPT_NAME'] is necessary because some servers (Windows/CGI) are seen to set PATH_INFO equal to script_name
					// Further, there must be at least one '/' in the path - else the PATH_INFO value does not make sense.
					// IF 'PATH_INFO' never works for our purpose in TYPO3 with CGI-servers, then 'php_sapi_name()=='cgi'' might be a better check. Right now strcmp($HTTP_SERVER_VARS['PATH_INFO'],t3lib_div::getIndpEnv('SCRIPT_NAME')) will always return false for CGI-versions, but that is only as long as SCRIPT_NAME is set equal to PATH_INFO because of php_sapi_name()=='cgi' (see above)
//				if (strcmp($HTTP_SERVER_VARS['PATH_INFO'],t3lib_div::getIndpEnv('SCRIPT_NAME')) && count(explode('/',$HTTP_SERVER_VARS['PATH_INFO']))>1)	{
				if (php_sapi_name()!='cgi')	{
					return $HTTP_SERVER_VARS['PATH_INFO'];
				} else return '';
			break;
				// These are let through without modification
			case 'REMOTE_ADDR':
			case 'REMOTE_HOST':
			case 'HTTP_REFERER':
			case 'HTTP_HOST':
			case 'HTTP_USER_AGENT':
			case 'HTTP_ACCEPT_LANGUAGE':
			case 'QUERY_STRING':
				return $HTTP_SERVER_VARS[$getEnvName];
			break;
			case 'TYPO3_DOCUMENT_ROOT':
				// Some CGI-versions (LA13CGI) and mod-rewrite rules on MODULE versions will deliver a 'wrong' DOCUMENT_ROOT (according to our description). Further various aliases/mod_rewrite rules can disturb this as well.
				// Therefore the DOCUMENT_ROOT is now always calculated as the SCRIPT_FILENAME minus the end part shared with SCRIPT_NAME.
				$SFN = t3lib_div::getIndpEnv('SCRIPT_FILENAME');
				$SN_A = explode('/',strrev(t3lib_div::getIndpEnv('SCRIPT_NAME')));
				$SFN_A = explode('/',strrev($SFN));
				$acc=array();
				while(list($kk,$vv)=each($SN_A))	{
					if (!strcmp($SFN_A[$kk],$vv))	{
						$acc[]=$vv;
					} else break;
				}
				$commonEnd=strrev(implode('/',$acc));
				if (strcmp($commonEnd,''))		$DR = substr($SFN,0,-(strlen($commonEnd)+1));
				return $DR;
			break;
			case 'TYPO3_HOST_ONLY':
				$p=explode(':',$HTTP_SERVER_VARS['HTTP_HOST']);
				return $p[0];
			break;
			case 'TYPO3_PORT':
				$p=explode(':',$HTTP_SERVER_VARS['HTTP_HOST']);
				return $p[1];
			break;
			case 'TYPO3_REQUEST_HOST':
				return 'http'.($HTTP_SERVER_VARS['SSL_SESSION_ID']?'s':'').'://'.	// I hope this: ($HTTP_SERVER_VARS['SSL_SESSION_ID']?'s':'') - is sufficient to detect https...
					$HTTP_SERVER_VARS['HTTP_HOST'];
			break;
			case 'TYPO3_REQUEST_URL':
				return t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST').t3lib_div::getIndpEnv('REQUEST_URI');
			break;
			case 'TYPO3_REQUEST_SCRIPT':
				return t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST').t3lib_div::getIndpEnv('SCRIPT_NAME');
			break;
			case 'TYPO3_REQUEST_DIR':
				return t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST').t3lib_div::dirname(t3lib_div::getIndpEnv('SCRIPT_NAME')).'/';
			break;
			case 'TYPO3_SITE_URL':
				if (defined('PATH_thisScript') && defined('PATH_site'))	{
					$lPath = substr(dirname(PATH_thisScript),strlen(PATH_site)).'/';
					$url = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR');
					$siteUrl = substr($url,0,-strlen($lPath));
					if (substr($siteUrl,-1)!='/')	$siteUrl.='/';
					return $siteUrl;
				} else return '';

			break;
			case '_ARRAY':
				$out=array();
					// Here, list ALL possible keys to this function for debug display.
				$envTestVars = t3lib_div::trimExplode(',','
					HTTP_HOST,
					TYPO3_HOST_ONLY,
					TYPO3_PORT,
					PATH_INFO,
					QUERY_STRING,
					REQUEST_URI,
					HTTP_REFERER,
					TYPO3_REQUEST_HOST,
					TYPO3_REQUEST_URL,
					TYPO3_REQUEST_SCRIPT,
					TYPO3_REQUEST_DIR,
					TYPO3_SITE_URL,
					SCRIPT_NAME,
					TYPO3_DOCUMENT_ROOT,
					SCRIPT_FILENAME,
					REMOTE_ADDR,
					REMOTE_HOST,
					HTTP_USER_AGENT,
					HTTP_ACCEPT_LANGUAGE',1);
				reset($envTestVars);
				while(list(,$v)=each($envTestVars))	{
					$out[$v]=t3lib_div::getIndpEnv($v);
				}
				reset($out);
				return $out;
			break;
		}
	}

}

	function view_array($array_in)	{
		// Returns HTML-code, which is a visual representation of a multidimensional array
		// use t3lib_div::print_array() in order to print an array
		// Returns false if $array_in is not an array
		if (is_array($array_in))	{
			$result='<table border=1 cellpadding=1 cellspacing=0 bgcolor=white>';
			if (!count($array_in))	{$result.= '<tr><td><font face="Verdana,Arial" size="1"><b>'.HTMLSpecialChars("EMPTY!").'</b></font></td></tr>';}
			while (list($key,$val)=each($array_in))	{
				$result.= '<tr><td><font face="Verdana,Arial" size="1">'.HTMLSpecialChars($key).'</font></td><td>';
				if (is_array($array_in[$key]))	{
					$result.=t3lib_div::view_array($array_in[$key]);
				} else
					$result.= '<font face="Verdana,Arial" size="1" color=red>'.nl2br(HTMLSpecialChars($val)).'<BR></font>';
				$result.= '</td></tr>';
			}
			$result.= '</table>';
		} else	{
			$result  = false;
		}
		return $result;
	}
	function debug($array_in)	{
		// Prints an array
		echo view_array($array_in);
	}
















	error_reporting (E_ALL ^ E_NOTICE);

define("TYPO3_OS", stristr(PHP_OS,"win")&&!stristr(PHP_OS,"darwin")?"WIN":"");
/*
define("PATH_thisScript",
	TYPO3_OS=="WIN" ?
	str_replace('//','/',str_replace('\\','/', $HTTP_SERVER_VARS["PATH_TRANSLATED"]?$HTTP_SERVER_VARS["PATH_TRANSLATED"]:getenv("PATH_TRANSLATED"))) :
	(php_sapi_name()=="cgi"?(getenv("PATH_TRANSLATED")?getenv("PATH_TRANSLATED"):getenv("SCRIPT_FILENAME")):$HTTP_SERVER_VARS["PATH_TRANSLATED"])
);
	*/

define("PATH_thisScript",str_replace('//','/', str_replace('\\','/', php_sapi_name()=="cgi"||php_sapi_name()=="isapi" ? $HTTP_SERVER_VARS["PATH_TRANSLATED"]:$HTTP_SERVER_VARS["SCRIPT_FILENAME"])));
define('PATH_site', dirname(PATH_thisScript).'/');


if (count($_GET) || $_SERVER["HTTP_REFERER"])	{
	# KOMPENSATED:
	echo "<H3>t3lib_div::getIndpEnv()</H3><p>These are 'system variables' returned from t3lib_div::getIndpEnv() and should be universal for any server configuration:</p>";
	debug(t3lib_div::getIndpEnv("_ARRAY"));

	debug(array(
		"PHP_OS"=>PHP_OS,
		"TYPO3_OS"=>TYPO3_OS,
		"PATH_thisScript"=>PATH_thisScript,
		"php_sapi_name()" => php_sapi_name()
	));




	##debug(parse_url("http://admin:palindrom@192.168.1.4:8080/typo3/32/temp/phpcheck/index.php/arg1/arg2/arg3/index.php?arg1,arg2,arg3&p1=parameter1&p2[key]=value#link1"));


	echo "<H3>Raw values</H3><p>These are the raw 'system variables' returned from getenv(), HTTP_SERVER_VARS, HTTP_ENV_VARS etc. These are displayed here so we can find the right values via this testscript to map to with t3lib_div::getIndpEnv()</p>";
	$envTestVars = explode(",","REQUEST_URI,REMOTE_ADDR,REMOTE_HOST,PATH_INFO,SCRIPT_NAME,SCRIPT_FILENAME,HTTP_HOST,HTTP_USER_AGENT,HTTP_ACCEPT_ENCODING,HTTP_REFERER,QUERY_STRING");
	$lines=array();
		$lines[] = '<tr bgcolor="#eeeeee">
			<td>Key</td>
			<td nowrap>getenv()</td>
			<td nowrap>HTTP_SERVER_VARS</td>
			<td nowrap>_SERVER</td>
			<td nowrap>HTTP_ENV_VARS</td>
			<td nowrap>_ENV</td>
		</tr>';
	while(list(,$v)=each($envTestVars))	{
		$lines[] = '<tr>
			<td bgcolor="#eeeeee">'.htmlspecialchars($v).'</td>
			<td nowrap>'.htmlspecialchars(getenv($v)).'&nbsp;</td>
			<td nowrap>'.htmlspecialchars($GLOBALS["HTTP_SERVER_VARS"][$v]).'&nbsp;</td>
			<td nowrap>'.htmlspecialchars($GLOBALS["_SERVER"][$v]).'&nbsp;</td>
			<td nowrap>'.htmlspecialchars($GLOBALS["HTTP_ENV_VARS"][$v]).'&nbsp;</td>
			<td nowrap>'.htmlspecialchars($GLOBALS["_ENV"][$v]).'&nbsp;</td>
		</tr>';
	}
	echo '<table border=1 style="font-family:verdana; font-size:10px;">'.implode("",$lines).'</table>';

	echo '<table border=1 style="font-family:verdana; font-size:10px;">
	<tr><td>'.htmlspecialchars('$GLOBALS["HTTP_SERVER_VARS"]["DOCUMENT_ROOT"]').'</td><td>'.htmlspecialchars($GLOBALS["HTTP_SERVER_VARS"]["DOCUMENT_ROOT"]).'</td></tr>
	<tr><td>'.htmlspecialchars('$HTTP_SERVER_VARS["PATH_TRANSLATED"]').'</td><td>'.htmlspecialchars($HTTP_SERVER_VARS["PATH_TRANSLATED"]).'</td></tr>
	<tr><td>'.htmlspecialchars('$GLOBALS["HTTP_SERVER_VARS"]["REDIRECT_URL"]').'</td><td>'.htmlspecialchars($GLOBALS["HTTP_SERVER_VARS"]["REDIRECT_URL"]).'</td></tr>
	<tr><td>'.htmlspecialchars('$GLOBALS["HTTP_SERVER_VARS"]["REQUEST_URI"]').'</td><td>'.htmlspecialchars($GLOBALS["HTTP_SERVER_VARS"]["REQUEST_URI"]).'</td></tr>
	</table>';



	echo "Cookie 'test_script_cookie': '<strong>".$HTTP_COOKIE_VARS["test_script_cookie"]."</strong>'<BR>";


	echo '<HR><a name="link1"></a>';
	echo '<div style="border: 1px solid black; padding: 10px 10px 10px 10px;"><h3>What to do now?</h3>
		<p>1) Click this link above once more: <a href="index.php?arg1,arg2,arg3&p1=parameter1&p2[key]='.substr(md5(time()),0,4).'#link1">Go to this page again.</a><BR>
		2) Then save this HTML-page and send it to kasperYYYY@typo3.com with information about 1) which webserver (Apache/ISS), 2) Unix/Windows, 3) CGI or module (ISAPI)<br>
		2a) You might help us find any differences in your values to this <a href="reference.html" target="_blank">reference example</a> by comparing the values before you send the result (thanks).
		<br>
		3) If you are really advanced you try and click the link below here. With CGI-versions of servers it will most likely give an error page. If it does not, please send the output to me as well (save HTML-page and send to kasperYYYY@typo3.com). If you do this PATH_INFO test, please let me know.<br><br>

		4) For the really, really advanced folks, it might be interesting to see the output if you could place this link in the root of a domain. That means the index.php script will be executed from eg. "http://www.blablabla.com/" and not "http://www.blablabla.com/kaspers_test/" - it can make a difference.<br>
		<br>
		<br>
		I am operating with these categories of servers. <strong>Please identify your configuration and label your email with that "type":</strong><br><br>

		<table border=1>
<tr bgcolor="#eeeeee">
	<td><em>TYPE:</em></td>
	<td><em>Description:</em></td>
</tr>
<tr>
	<td>WA13CGI</td>
	<td>Windows / Apache 1.3.x / CGI</td>
</tr>
<tr>
	<td>WA2CGI</td>
	<td>Windows / Apache 2.x / CGI</td>
</tr>
<tr>
	<td>WA13ISAPI</td>
	<td>Windows / Apache 1.3.x / ISAPI-module</td>
</tr>
<tr>
	<td>WA2ISAPI</td>
	<td>Windows / Apache 2.x / ISAPI-module</td>
</tr>
<tr>
	<td>WISS_CGI</td>
	<td>Windows / ISS / CGI</td>
</tr>
<tr>
	<td>WISS_ISAPI</td>
	<td>Windows / ISS / ISAPI-module</td>
</tr>
<tr>
	<td>MA13MOD</td>
	<td>Mac (Darwin) / Apache 1.3.x / Module</td>
</tr>
<tr>
	<td>LA13CGI</td>
	<td>Linux / Apache 1.3.x / CGI</td>
</tr>
<tr>
	<td>LA2CGI</td>
	<td>Linux / Apache 2.x / CGI</td>
</tr>
<tr>
	<td>LA13MOD</td>
	<td>Linux / Apache 1.3.x / Module</td>
</tr>
<tr>
	<td>LA2MOD</td>
	<td>Linux / Apache 2.x / Module</td>
</tr>
</table>


		</p></div>';
	echo '<a href="index.php/arg1/arg2/arg3/#link2" name="link2">Go to this page again (PATH_INFO).</a><BR>';

	phpinfo();
} else {
	echo '<a href="index.php?arg1,arg2,arg3&p1=parameter1&p2[key]=value#link1" name="link1">Click this link to start the test.</a><BR>';
}
?>