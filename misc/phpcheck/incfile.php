<?php
	// This checks for my own IP at home. You can just remove the if-statement.
if (1==0 || ($_SERVER['REMOTE_ADDR']!='127.0.0.1'))	{
	die('In the source distribution of TYPO3, this script is disabled by a die() function call.<br/><b>Fix:</b> Open the file misc/phpcheck/incfile.php and remove/out-comment the line that outputs this message!');
}

include('../../t3lib/class.t3lib_div.php');

SetCookie('test_script_cookie', 'Cookie Value!', 0, t3lib_div::getIndpEnv('TYPO3_SITE_PATH'));

if (defined('E_DEPRECATED')) {
	error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED);
} else {
	error_reporting(E_ALL ^ E_NOTICE);
}

define("TYPO3_OS", stristr(PHP_OS,"win")&&!stristr(PHP_OS,"darwin")?"WIN":"");
/*
define("PATH_thisScript",
	TYPO3_OS=="WIN" ?
	str_replace('//','/',str_replace('\\','/', $HTTP_SERVER_VARS["PATH_TRANSLATED"]?$HTTP_SERVER_VARS["PATH_TRANSLATED"]:getenv("PATH_TRANSLATED"))) :
	(php_sapi_name()=="cgi"?(getenv("PATH_TRANSLATED")?getenv("PATH_TRANSLATED"):getenv("SCRIPT_FILENAME")):$HTTP_SERVER_VARS["PATH_TRANSLATED"])
);
	*/

define("PATH_thisScript", str_replace('//','/', str_replace('\\','/', php_sapi_name()=="cgi"||php_sapi_name()=="isapi" ? $HTTP_SERVER_VARS["PATH_TRANSLATED"]:$HTTP_SERVER_VARS["SCRIPT_FILENAME"])));
define('PATH_site', dirname(PATH_thisScript).'/');



if (count($_GET) || $_SERVER["HTTP_REFERER"])	{
	# KOMPENSATED:
	echo "<H3>t3lib_div::getIndpEnv()</H3><p>These are 'system variables' returned from t3lib_div::getIndpEnv() and should be universal for any server configuration:</p>";
	t3lib_div::debug(t3lib_div::getIndpEnv("_ARRAY"));

	t3lib_div::debug(array(
		"PHP_OS"=>PHP_OS,
		"TYPO3_OS"=>TYPO3_OS,
		"PATH_thisScript"=>PATH_thisScript,
		"php_sapi_name()" => php_sapi_name()
	));




	##t3lib_div::debug(parse_url("http://admin:palindrom@192.168.1.4:8080/typo3/32/temp/phpcheck/index.php/arg1/arg2/arg3/index.php?arg1,arg2,arg3&p1=parameter1&p2[key]=value#link1"));


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