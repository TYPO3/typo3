<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
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
 * Super Admin class has functions for the administration of multiple TYPO3 sites in folders
 *
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */

 
// *******************************
// Set error reporting 
// *******************************
error_reporting (E_ALL ^ E_NOTICE); 
define('TYPO3_mainDir', 'typo3/');		// This is the directory of the backend administration for the sites of this TYPO3 installation. 
 
 
// Dependency:
include_once("./typo3_src/t3lib/class.t3lib_div.php");

function debug($p1,$p2="")	{
	t3lib_div::debug($p1,$p2);
}

class t3lib_superadmin {
	var $parentDirs=array();
	var $globalSiteInfo=array();
	var $currentUrl="";
	var $targetWindow="superAdminWindow";
	var $targetWindowAdmin="superAdminWindowAdmin";
	var $targetWindowInstall="superAdminWindowInstall";
	var $mapDBtoKey=array();
	var $collectAdminPasswords=array();
	var $changeAdminPasswords=array();
	var $collectInstallPasswords=array();
	var $scriptName="superadmin.php";

		// Control:
	var $full=0;	// If set, the full information array per site is printed.

	var $noCVS=0;	// See tools/em/index.php....
	
	function init($parentDirs)	{
		$this->parentDirs = $parentDirs;
	}
	function initProcess()	{
		$content="";
		reset($this->parentDirs);
		while(list($k,$v)=each($this->parentDirs))	{
			$dir = ereg_replace("/$","",$v["dir"]);
			$baseUrl=ereg_replace("/$","",$v["url"]);
			$content.="<BR><BR><BR>";
			$content.=$this->headerParentDir($dir);
			if (@is_dir($dir))	{
				$in_dirs = t3lib_div::get_dirs($dir);
				asort($in_dirs);
				reset($in_dirs);
				$dirArr=array();
				while(list($k,$v)=each($in_dirs))	{
					if (substr($v,0,9)!="typo3_src")	{
						$this->currentUrl=$baseUrl."/".$v;
						$content.=$this->headerSiteDir($v);
						$content.=$this->processSiteDir($dir."/".$v,$dir);
					}
				}
			} else {
				$content.=$this->error("'".$dir."' was not a directory!");
			}
		}
//		debug($this->globalSiteInfo);
		return $content;
	}
	function make()	{
		reset($this->parentDirs);
		$content = $this->initProcess();
		
			// Output mode:
		$mode=t3lib_div::GPvar("show");
//debug($GLOBALS["HTTP_GET_VARS"]);
		switch($mode)	{
			case "menu":
				$lines=array();
				$lines[]=$this->setMenuItem("info","INFO");
				$lines[]=$this->setMenuItem("update","UPDATE");
				$lines[]='';
				$lines[]='<A HREF="'.$this->scriptName.'?type=page" target="TSApage">Default</A>';
				$lines[]='<a HREF="'.$this->scriptName.'?type=page&show=all" target="TSApage">All details</a>';
				$lines[]='<a HREF="'.$this->scriptName.'?type=page&show=admin" target="TSApage">Admin logins</a>';
				$lines[]='<a HREF="'.$this->scriptName.'?type=phpinfo" target="TSApage">phpinfo()</a>';
				$lines[]='<a HREF="'.$this->scriptName.'?type=localext&show=localext" target="TSApage">Local extensions</a>';
				$lines[]='';
				$content = '<font>'.implode("<BR>",$lines).'</font>';
				$content.= '<HR>';
				$content.=$this->menuContent(t3lib_div::GPvar("exp"));
				return '<h2><nobr><div align="center">TYPO3<BR>Super Admin</div></nobr></h2>'.$content;
			break;
			case "all":
				return '<h1>All details:</h1><h2>Overview:</h2>'.$this->makeTable()."<BR><HR><BR>".
				'<h1>Details per site:</h1>'.$content;
			break;
			case "admin":
				$content = $this->setNewPasswords();
				$this->makeTable();
				return $content.'<h1>Admin options:</h1><h2>Admin logins:</h2>'.$this->makeAdminLogin()."<BR><HR><BR>".
				'<h2>TBE Admin Passwords:</h2>'.t3lib_div::view_array($this->collectAdminPasswords)."<BR><HR><BR>".
				'<h2>Install Tool Passwords:</h2>'.t3lib_div::view_array($this->collectInstallPasswords)."<BR><HR><BR>".
				'<h2>Change TBE Admin Passwords:</h2>'.$this->changeAdminPasswordsForm()."<BR><HR><BR>";
			break;
			case "info":
				return '<h1>Single site details</h1>'.$this->singleSite(t3lib_div::GPvar("exp"))."<BR>";
			break;
			case "rmTempCached":
				return '<h1>Removing temp_CACHED_*.php files</h1>'.$this->rmCachedFiles(t3lib_div::GPvar("exp"))."<BR>";
			break;
			case "localext":	
				return '<h1>Local Extensions Found:</h1>'.$this->localExtensions()."<BR>";
			break;
			default:
				return '<h1>Default info:</h1>'.$content;
			break;
		}
	}
	function setMenuItem($code,$label)	{
		$out = '<a HREF="'.$this->scriptName.'?type=menu&show=menu&exp='.$code.'" target="TSAmenu">'.$label.'</a>';	
		if ($code==t3lib_div::GPvar("exp"))	{
			$out = '<font color=red>&gt;&gt;</font>'.$out;
		}
		return $out;
	}
	function error($str)	{
		$out = '<font color=red size=4>'.$str.'</font>';
		return $out;
	}
	function headerParentDir($str)	{
		$out = '<h2>'.$str.'</h2>';
		return $out;
	}
	function headerSiteDir($str)	{
		$out = '<h3>'.$str.'</h3>';
		return $out;
	}
	function processSiteDir($path,$dir)	{
		if (@is_dir($path))	{
			$localconf = $path."/typo3conf/localconf.php";
			if (@is_file($localconf))	{
				$key = md5($localconf);
				$this->includeLocalconf($localconf);

				$this->mapDBtoKey[$this->globalSiteInfo[$key]["siteInfo"]["TYPO3_db"]]=$key;
				$this->globalSiteInfo[$key]["siteInfo"]["MAIN_DIR"]=$dir;
				$this->globalSiteInfo[$key]["siteInfo"]["SA_PATH"]=$path;
				$this->globalSiteInfo[$key]["siteInfo"]["URL"]=$this->currentUrl."/";
				$this->globalSiteInfo[$key]["siteInfo"]["ADMIN_URL"]=$this->currentUrl."/".TYPO3_mainDir;
				$this->globalSiteInfo[$key]["siteInfo"]["INSTALL_URL"]=$this->currentUrl."/".TYPO3_mainDir."install/";

				$conMsg = $this->connectToDatabase($this->globalSiteInfo[$key]["siteInfo"]);
				if (!$conMsg)	{
					$this->getDBInfo($key);
					if ($this->full)	{
						$out.=t3lib_div::view_array($this->globalSiteInfo[$key]);
					} else {
						$out.=t3lib_div::view_array($this->globalSiteInfo[$key]["siteInfo"]);
					}
				} else {$out=$this->error($conMsg);}
			} else $out=$this->error($localconf." is not a file!");
		} else $out=$this->error($path." is not a directory!");
		return $out;
	}
	function includeLocalconf($localconf)	{
		include($localconf);

		$siteInfo=array();
		$siteInfo["sitename"] = $TYPO3_CONF_VARS["SYS"]["sitename"];
		$siteInfo["TYPO3_db"] = $typo_db;
		$siteInfo["TYPO3_db_username"] = $typo_db_username;
		$siteInfo["TYPO3_db_password"] = $typo_db_password;
		$siteInfo["TYPO3_db_host"] = $typo_db_host;
		$siteInfo["installToolPassword"] = $TYPO3_CONF_VARS["BE"]["installToolPassword"];
		$siteInfo["warningEmailAddress"] = $TYPO3_CONF_VARS["BE"]["warning_email_addr"];
		$siteInfo["warningMode"] = $TYPO3_CONF_VARS["BE"]["warning_mode"];
		
		$this->globalSiteInfo[md5($localconf)]=array("siteInfo"=>$siteInfo,"TYPO3_CONF_VARS"=>$TYPO3_CONF_VARS);
		return $siteInfo;
	}
	function connectToDatabase($siteInfo)	{
		if (@mysql_pconnect($siteInfo["TYPO3_db_host"], $siteInfo["TYPO3_db_username"], $siteInfo["TYPO3_db_password"]))	{
			if (!$siteInfo["TYPO3_db"])	{
				return $this->error("No database selected");
			} elseif (!mysql_select_db($siteInfo["TYPO3_db"]))	{
				return $this->error("Cannot connect to the current database, '".$siteInfo["TYPO3_db"]."'");
			}
		} else {
			return $this->error("The current username, password or host was not accepted when the connection to the database was attempted to be established!");
		}
	}
	function getDBInfo($key)	{
		$DB = $this->globalSiteInfo[$key]["siteInfo"]["TYPO3_db"];

			// Non-admin users
		$query="SELECT count(*) FROM be_users WHERE admin=0 AND NOT deleted";
		$res = mysql($DB,$query);
		$row = mysql_fetch_row($res);
		$this->globalSiteInfo[$key]["siteInfo"]["BE_USERS_NONADMIN"] = $row[0];
			// Admin users
		$query="SELECT count(*) FROM be_users WHERE admin!=0 AND NOT deleted";
		$res = mysql($DB,$query);
		$row = mysql_fetch_row($res);
		$this->globalSiteInfo[$key]["siteInfo"]["BE_USERS_ADMIN"] = $row[0];

			// Select Admin users
		$query="SELECT uid,username,password,email,realName FROM be_users WHERE admin!=0 AND NOT deleted";
		$res = mysql($DB,$query);
		while($row = mysql_fetch_assoc($res))	{
//			debug($row);
			$this->globalSiteInfo[$key]["siteInfo"]["ADMINS"][] = $row;
		}
	}
	function makeTable()	{
			// TITLE:
			$info=array();
			$info[]="Site:";
			$info[]="Database:";
			$info[]="Username";
			$info[]="Password";
			$info[]="Host";
			$info[]="Links (new win)";
			$info[]="#Users NA/A";
			$info[]="Admin be_users Info";
			$info[]="Install Tool Password";
			$info[]="Warning email address";
			$info[]="W.mode";
			$mainArrRows[]="<TR bgcolor=#eeeeee><TD nowrap valign=top>".implode("</TD><TD nowrap valign=top>",$info)."</TD></TR>";
	
		reset($this->globalSiteInfo);
		while(list($k,$all)=each($this->globalSiteInfo))	{
			$info=array();
			$info[]=$all["siteInfo"]["sitename"];
			$info[]=$all["siteInfo"]["TYPO3_db"];
			$info[]=$all["siteInfo"]["TYPO3_db_username"];
			$info[]=$all["siteInfo"]["TYPO3_db_password"];
			$info[]=$all["siteInfo"]["TYPO3_db_host"];
				// URL
			$info[]='<A HREF="'.$all["siteInfo"]["URL"].'" target="'.$this->targetWindow.'">Site</A> / <A HREF="'.$all["siteInfo"]["ADMIN_URL"].'" target="'.$this->targetWindowAdmin.'">Admin</A> / <A HREF="'.$all["siteInfo"]["INSTALL_URL"].'" target="'.$this->targetWindowInstall.'">Install</A>';
			$info[]=$all["siteInfo"]["BE_USERS_NONADMIN"]."/".$all["siteInfo"]["BE_USERS_ADMIN"];

				// Admin
			if (is_array($all["siteInfo"]["ADMINS"]))	{
				reset($all["siteInfo"]["ADMINS"]);
				$lines=array();
				while(list(,$vArr)=each($all["siteInfo"]["ADMINS"]))	{
					$lines[]=$vArr["password"]." - ".$vArr["username"]." (".$vArr["realName"].", ".$vArr["email"].")";
					$this->collectAdminPasswords[$vArr["password"]][] = $all["siteInfo"]["sitename"]." (".$all["siteInfo"]["TYPO3_db"]."), ".$vArr["username"]." (".$vArr["realName"].", ".$vArr["email"].")";
					$this->changeAdminPasswords[$vArr["password"]][]=$all["siteInfo"]["TYPO3_db"].":".$vArr["uid"].":".$vArr["username"];
				}
				$info[]=implode("<BR>",$lines);
			} else {
				$info[]='<font color="red">No DB connection!</font>';
			}
				// Install
			$info[]=$all["siteInfo"]["installToolPassword"];
			$this->collectInstallPasswords[$all["siteInfo"]["installToolPassword"]][] = $all["siteInfo"]["sitename"]." (".$all["siteInfo"]["TYPO3_db"].")";
			
			$info[]=$all["siteInfo"]["warningEmailAddress"];
			$info[]=$all["siteInfo"]["warningMode"];
//			debug($all["siteInfo"]);
			
			
				// compile
			$mainArrRows[]="<TR><TD nowrap valign=top>".implode("</TD><TD nowrap valign=top>",$info)."</TD></TR>";
		}
		$table = '<TABLE border=1 cellpadding=1 cellspacing=1>'.implode("",$mainArrRows).'</TABLE>';
		return $table;
	}

	/**
	 * Based on the globalSiteInfo array, this prints information about local extensions for each site.
	 * In particular version number and most recent mod-time is interesting!
	 */
	function localExtensions()	{
		$this->extensionInfoArray=array();

		reset($this->globalSiteInfo);
		while(list($k,$all)=each($this->globalSiteInfo))	{
			if ($all["siteInfo"]["SA_PATH"])	{
				$extDir = $all["siteInfo"]["SA_PATH"]."/typo3conf/ext/";
				if (@is_dir($extDir))	{
					$this->extensionInfoArray["site"][$k]=array();
					
#					debug($extDir,1);
					$extensions=t3lib_div::get_dirs($extDir);
					if (is_array($extensions))	{
	
	#					debug($extensions);
						reset($extensions);
						while(list(,$extKey)=each($extensions))	{
							$eInfo = $this->getExtensionInfo($extDir,$extKey,$k);
	
							$this->extensionInfoArray["site"][$k][$extKey]=$eInfo;
							$this->extensionInfoArray["ext"][$extKey][$k]=$eInfo;
						}
					}
				}
			}
		}	
		
			// Display results:
		$out="";
		
		
			// PER EXTENSION:
		if (is_array($this->extensionInfoArray["ext"]))	{
			$extensionKeysCollect=array();

			ksort($this->extensionInfoArray["ext"]);
			reset($this->extensionInfoArray["ext"]);
			$rows=array(
				"reg"=>array(),
				"user"=>array()
			);
			while(list($extKey,$instances)=each($this->extensionInfoArray["ext"]))	{
				$mtimes=array();

					// Find most recent mtime of the options:
				reset($instances);
				while(list($k,$eInfo)=each($instances))	{
					$mtimes[]=$eInfo["mtime"];
				}
					// Max mtime:
				$maxMtime=max($mtimes);
				$c=0;

					// So, traverse all sites with the extension present:
				reset($instances);
				while(list($k,$eInfo)=each($instances))	{
						// Set background color if mtime matches
					if ($maxMtime==$eInfo["mtime"])	{
						$this->extensionInfoArray["site"][$k][$extKey]["_highlight"]=1;
						$bgCol = ' bgcolor="#eeeeee""';
					} else {
						$bgCol = ' style="color: #999999; font-style: italic;"';
					}
	
						// Make row:
					$type = substr($extKey,0,5)!="user_"?"reg":"user";
					if ($type=="reg")	$extensionKeysCollect[]=$extKey;
					$rows[$type][]='
					<tr>
						'.(!$c?'<td rowspan="'.count($instances).'">'.$extKey.'</td>':'').'
						<td nowrap'.$bgCol.'>'.$this->globalSiteInfo[$k]["siteInfo"]["SA_PATH"].'</td>
						<td nowrap'.$bgCol.'>'.$eInfo["title"].'</td>
						<td nowrap'.$bgCol.'>'.$eInfo["version"].'</td>
						<td nowrap'.$bgCol.'>'.$eInfo["numberfiles"].'</td>
						<td nowrap'.$bgCol.'>'.($eInfo["manual"]?'M':'-').'</td>
						<td nowrap'.$bgCol.'>'.($eInfo["mtime"]?date("d-m-y H:i:s",$eInfo["mtime"]):'').'</td>
						<td nowrap'.$bgCol.'>'.$eInfo["mtime_hash"].'</td>
					</tr>
					';
					$c++;
				}
			}

			$out.='<h3>Registered extensions:</h3><table border=1>'.implode("",$rows["reg"]).'</table>';
			
			$extensionKeysCollect = array_unique($extensionKeysCollect);
			asort($extensionKeysCollect);
			$out.='<form><textarea cols="80" rows="10">'.implode(chr(10),$extensionKeysCollect).'</textarea></form>';

			$out.='<BR><h3>User extensions:</h3><table border=1>'.implode("",$rows["user"]).'</table>';
		}
		
			// PER SITE:
		if (is_array($this->extensionInfoArray["site"]))	{
			reset($this->extensionInfoArray["site"]);
			$rows=array();
			while(list($k,$extensions)=each($this->extensionInfoArray["site"]))	{
					// So, traverse all sites with the extension present:
				$c=0;
				reset($extensions);
				while(list($extKey,$eInfo)=each($extensions))	{
						// Set background color if mtime matches
					if ($eInfo["_highlight"])	{
						$bgCol = ' bgcolor="#eeeeee""';
					} else {
						$bgCol = ' style="color: #999999; font-style: italic;"';
					}
	
						// Make row:
					$rows[]='
					<tr>
						'.(!$c?'<td rowspan="'.count($extensions).'">'.$this->globalSiteInfo[$k]["siteInfo"]["SA_PATH"].'</td>':'').'
						<td nowrap'.$bgCol.'>'.$extKey.'</td>
						<td nowrap'.$bgCol.'>'.$eInfo["title"].'</td>
						<td nowrap'.$bgCol.'>'.$eInfo["version"].'</td>
						<td nowrap'.$bgCol.'>'.$eInfo["numberfiles"].'</td>
						<td nowrap'.$bgCol.'>'.($eInfo["mtime"]?date("d-m-y H:i:s",$eInfo["mtime"]):'').'</td>
						<td nowrap'.$bgCol.'>'.$eInfo["mtime_hash"].'</td>
					</tr>
					';
					$c++;
				}
			}
			$out.='<BR><h3>Sites:</h3><table border=1>'.implode("",$rows).'</table>';
		}
		return $out;


#		debug($this->extensionInfoArray);
#		debug($this->globalSiteInfo);
	}

	/**
	 * Gets information for an extension, eg. version and most-recently-edited-script
	 */
	function getExtensionInfo($path,$extKey,$k)	{
		$file = $path.$extKey."/ext_emconf.php";
		if (@is_file($file))	{
			$_EXTKEY = $extKey;
			include($file);
			
			$eInfo=array();
				// Info from emconf:
			$eInfo["title"] = $EM_CONF[$extKey]["title"];
			$eInfo["version"] = $EM_CONF[$extKey]["version"];
			$filesHash = unserialize($EM_CONF[$extKey]["_md5_values_when_last_written"]);

#			debug(count($filesHash),1);
			if (is_array($filesHash) && count($filesHash)<50)	{
					// Get all files list (may take LOONG time):
				$extPath=$path.$extKey."/";
				$fileArr = array();
				$fileArr = $this->removePrefixPathFromList($this->getAllFilesAndFoldersInPath($fileArr,$extPath),$extPath);
		
					// Number of files:
				$eInfo["numberfiles"]=count($fileArr);

					// Most recent modification:
				$eInfo["mtime_files"]=$this->findMostRecent($fileArr,$extPath);
				if (count($eInfo["mtime_files"]))	$eInfo["mtime"]=max($eInfo["mtime_files"]);
				$eInfo["mtime_hash"] = md5(implode(",",$eInfo["mtime_files"]));
			}
			
			$eInfo["manual"] = @is_file($path.$extKey."/doc/manual.sxw");

			return $eInfo;
#			debug(unserialize($EM_CONF[$extKey]["_md5_values_when_last_written"]));
#			debug($this->serverExtensionMD5Array($fileArr,$extPath));
#			debug($fileArr);
		} else return "ERROR: No emconf.php file: ".$file;
	}

	/**
	 * Recursively gather all files and folders of extension path.
	 */
	function getAllFilesAndFoldersInPath($fileArr,$extPath,$extList="",$regDirs=0)	{
		if ($regDirs)	$fileArr[]=$extPath;
		$fileArr=array_merge($fileArr,t3lib_div::getFilesInDir($extPath,$extList,1,1));		// Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
		
		$dirs = t3lib_div::get_dirs($extPath);
		if (is_array($dirs))	{
			reset($dirs);
			while(list(,$subdirs)=each($dirs))	{
				if ($subdirs && (strcmp($subdirs,"CVS") || !$this->noCVS))	{
					$fileArr = $this->getAllFilesAndFoldersInPath($fileArr,$extPath.$subdirs."/",$extList,$regDirs);
				}
			}
		}
		return $fileArr;
	}

	/**
	 * Creates a MD5-hash array over the current files in the extension
	 */
	function serverExtensionMD5Array($fileArr,$extPath)	{
		reset($fileArr);
		$md5Array=array();
		while(list(,$fN)=each($fileArr))	{
			if ($fN!="ext_emconf.php")	{
				$content_md5 = md5(t3lib_div::getUrl($extPath.$fN));
				$md5Array[$fN]=substr($content_md5,0,4);
			}
		}
		return $md5Array;
	}

	/**
	 * Creates a MD5-hash array over the current files in the extension
	 */
	function findMostRecent($fileArr,$extPath)	{
		reset($fileArr);
		$mtimeArray=array();
		while(list(,$fN)=each($fileArr))	{
			if ($fN!="ext_emconf.php")	{
				$mtime = filemtime($extPath.$fN);
				$mtimeArray[$fN]=$mtime;
			}
		}
		return $mtimeArray;
	}

	/**
	 * Removes the absolute part of all files/folders in fileArr
	 */
	function removePrefixPathFromList($fileArr,$extPath)	{
		reset($fileArr);
		while(list($k,$absFileRef)=each($fileArr))	{
			if(t3lib_div::isFirstPartOfStr($absFileRef,$extPath))	{
				$fileArr[$k]=substr($absFileRef,strlen($extPath));
			} else return "ERROR: One or more of the files was NOT prefixed with the prefix-path!";
		}
		return $fileArr;
	}

	
	
	function singleSite($exp)	{
		$all = $this->globalSiteInfo[$exp];
		$content = '<h2>'.$all["siteInfo"]["sitename"].' (DB: '.$all["siteInfo"]["TYPO3_db"].')</h2>';
		$content.= '<HR>';
		$content.= '<h3>Main details:</h3>';
		$content.= '<font>LINKS: <A HREF="'.$all["siteInfo"]["URL"].'" target="'.$this->targetWindow.'">Site</A> / <A HREF="'.$all["siteInfo"]["ADMIN_URL"].'" target="'.$this->targetWindowAdmin.'">Admin</A> / <A HREF="'.$all["siteInfo"]["INSTALL_URL"].'" target="'.$this->targetWindowInstall.'">Install</A></font><BR><BR>';
		$content.= t3lib_div::view_array($all);

		$content.= '<h3>Login-Log for last month:</h3>';
		$content.= $this->loginLog($all["siteInfo"]["TYPO3_db"]);

		return $content;
	}
	function rmCachedFiles($exp)	{
		$all = $this->globalSiteInfo[$exp];
		$content = '<h2>'.$all["siteInfo"]["sitename"].' (DB: '.$all["siteInfo"]["TYPO3_db"].')</h2>';
		$content.= '<HR>';
		$content.= '<h3>typo3conf/temp_CACHED_* files:</h3>';

		$path = $all["siteInfo"]["SA_PATH"]."/typo3conf/";
		if (@is_dir($path))	{
			$filesInDir=t3lib_div::getFilesInDir($path,"php",1);
			reset($filesInDir);
			while(list($kk,$vv)=each($filesInDir))	{
				if (t3lib_div::isFirstPartOfStr(basename($vv),"temp_CACHED_"))	{
					if (strstr(basename($vv),"ext_localconf.php") || strstr(basename($vv),"ext_tables.php"))	{
						$content.="REMOVED: ".$vv."<BR>";
						unlink($vv);
						if (file_exists($vv))	$content.="<strong><font color=red>ERROR: File still exists, so could not be removed anyways!</font></strong><BR>";
					}
				}
			}
		} else {
			$content.='<strong><font color=red>ERROR: '.$path.' was not a directory!</font></strong>';
		}
		
		return $content;
	}
	function menuContent($exp)	{
		if ($exp)	{
			reset($this->globalSiteInfo);
			$lines=array();
			$head="";
			while(list($k,$all)=each($this->globalSiteInfo))	{
					// Setting section header, if needed.
				if ($head!=$all["siteInfo"]["MAIN_DIR"])	{
					$lines[]='<h4><nobr>'.t3lib_div::fixed_lgd_pre($all["siteInfo"]["MAIN_DIR"],18).'</nobr></h4>';
					$head=$all["siteInfo"]["MAIN_DIR"];
				}
				
				switch($exp)	{
					case "update":
							// Label:
						$label = $all["siteInfo"]["sitename"] ? $all["siteInfo"]["sitename"] : "(DB: ".$all["siteInfo"]["TYPO3_db"].")";
						$lines[]='<HR><b>'.$label.'</b> ('.substr($all["siteInfo"]["SA_PATH"],strlen($all["siteInfo"]["MAIN_DIR"])+1).')<BR>';
						
							// Get SQL-files:
/*						$readPath = $all["siteInfo"]["SA_PATH"]."/typo3/t3lib/stddb/";
						$fileArr = t3lib_div::getFilesInDir($readPath,"sql",1,"mtime");
						$file=array();
						if (is_array($fileArr))	{
							reset($fileArr);
							while(list(,$fP)=each($fileArr))	{
	//							if (substr($fP,0,strlen($readPath."static_template"))==$readPath."static_template")	{
								if (substr($fP,0,strlen($readPath."static+"))==$readPath."static+")	{
									$file["static_template.sql"]=$fP;
								}
								if (substr($fP,0,strlen($readPath."sys_tabledescr_X"))==$readPath."sys_tabledescr_X")	{
									$file["sys_tabledescr_X.sql"]=$fP;
								}
								if (substr($fP,0,strlen($readPath."tables"))==$readPath."tables")	{
									$file["tables.sql"]=$fP;
								}
							}
						}
*/
						$tempVal='&_someUniqueValue='.time();

//							$url = $all["siteInfo"]["ADMIN_URL"]."mod/tools/em/index.php";
//							$lines[]='<nobr><a HREF="'.$url.'" target="TSApage">EM</a></nobr>';	

				

							$lines[]='<nobr><a HREF="'.$this->scriptName.'?type=page&show=rmTempCached&exp='.$k.$tempVal.'" target="TSApage">Remove temp_CACHED files</a></nobr>';	

							$url = $all["siteInfo"]["INSTALL_URL"]."index.php?TYPO3_INSTALL[type]=database&TYPO3_INSTALL[database_type]=cmpFile|CURRENT_TABLES".$tempVal."#bottom";
							$lines[]='<nobr><a HREF="'.$url.'" target="TSApage">CURRENT_TABLES</a></nobr>';	

							$url = $all["siteInfo"]["INSTALL_URL"]."index.php?TYPO3_INSTALL[type]=database&TYPO3_INSTALL[database_type]=import|CURRENT_STATIC"."&presetWholeTable=1".$tempVal."#bottom";
							$lines[]='<nobr><a HREF="'.$url.'" target="TSApage">CURRENT_STATIC</a></nobr>';	

/*
							// Link to tables:
						if ($file["tables.sql"])	{
							$url = $all["siteInfo"]["INSTALL_URL"]."index.php?TYPO3_INSTALL[type]=database&TYPO3_INSTALL[database_type]=cmpFile|".rawurlencode($file["tables.sql"]).$tempVal."#bottom";
							$lines[]='<nobr><a HREF="'.$url.'" target="TSApage">'.basename($file["tables.sql"]).'</a></nobr>';	
						}
							// Link to static_tempalte
						if ($file["static_template.sql"])	{
							$url = $all["siteInfo"]["INSTALL_URL"]."index.php?TYPO3_INSTALL[type]=database&TYPO3_INSTALL[database_type]=import|".rawurlencode($file["static_template.sql"])."&presetWholeTable=1".$tempVal."#bottom";
							$lines[]='<nobr><a HREF="'.$url.'" target="TSApage">'.basename($file["static_template.sql"]).'</a></nobr>';	
						}
							// Link to language file
						if ($file["sys_tabledescr_X.sql"])	{
							$url = $all["siteInfo"]["INSTALL_URL"]."index.php?TYPO3_INSTALL[type]=database&TYPO3_INSTALL[database_type]=import|".rawurlencode($file["sys_tabledescr_X.sql"])."&presetWholeTable=1".$tempVal."#bottom";
							$lines[]='<nobr><a HREF="'.$url.'" target="TSApage">'.basename($file["sys_tabledescr_X.sql"]).'</a></nobr>';	
						}
						*/
							// Cache
						$url = $all["siteInfo"]["INSTALL_URL"]."index.php?TYPO3_INSTALL[type]=database&TYPO3_INSTALL[database_type]=cache|".
										"&PRESET[database_clearcache][cache_pages]=1".
										"&PRESET[database_clearcache][cache_pagesection]=1".
										"&PRESET[database_clearcache][cache_hash]=1".
										$tempVal.
										"#bottom";
						$lines[]='<nobr><a HREF="'.$url.'" target="TSApage">Clear cache</a></nobr>';	
						

						$lines[]='<nobr><a HREF="'.$all["siteInfo"]["ADMIN_URL"].'index.php" target="'.$this->targetWindowAdmin.'">Admin -></a></nobr>';	
					break;
					case "info":
							// item
						$label = $all["siteInfo"]["sitename"] ? $all["siteInfo"]["sitename"] : "(DB: ".$all["siteInfo"]["TYPO3_db"].")";
						$lines[]='<nobr><a HREF="'.$this->scriptName.'?type=page&show=info&exp='.$k.'" target="TSApage">'.$label.'</a> ('.substr($all["siteInfo"]["SA_PATH"],strlen($all["siteInfo"]["MAIN_DIR"])+1).'/)</nobr>';	
					break;
				}
			}	
			return "<font>".implode("<BR>",$lines)."<BR></font>";	
		}
	}
	function makeAdminLogin()	{
		reset($this->globalSiteInfo);
		$lines=array();
		$head="";
		while(list($k,$all)=each($this->globalSiteInfo))	{
				// Setting section header, if needed.
			if ($head!=$all["siteInfo"]["MAIN_DIR"])	{
				$lines[]='<tr><td colspan=2><BR><h4>'.$all["siteInfo"]["MAIN_DIR"].'</h4></td></tr>';
				$head=$all["siteInfo"]["MAIN_DIR"];
			}



				// item
			$label = $all["siteInfo"]["sitename"] ? $all["siteInfo"]["sitename"] : "(DB: ".$all["siteInfo"]["TYPO3_db"].")";
			$unique=md5(microtime());
			
			$opts=array();
			
			$defUName="";
			if (is_array($all["siteInfo"]["ADMINS"]))	{
				reset($all["siteInfo"]["ADMINS"]);
				while(list(,$vArr)=each($all["siteInfo"]["ADMINS"]))	{
					$chalVal = md5($vArr["username"].":".$vArr["password"].":".$unique);
					$opts[]='<option value="'.$chalVal.'">'.$vArr["username"].'</option>';
					if (!$defUName) {$defUName=$vArr["username"];}
				}
			}
			if (count($opts)>1)	{
					$userident='
					<select name="userident" onChange="document[\''.$k.'\'].username.value=this.options[this.selectedIndex].text;">'.implode("",$opts).'</select>
				';
			} else {
				$userident='('.$defUName.')<BR><input type="Hidden" name="userident" value="'.$chalVal.'">';
			}
			
			$form='
			<form name="'.$k.'" action="'.$all["siteInfo"]["ADMIN_URL"].'index.php" target="EXTERnalWindow" method="post">
				<input type="submit" name="submit" value="Login">
				<input type="Hidden" name="username" value="'.$defUName.'">
				<input type="Hidden" name="challenge" value="'.$unique.'">
				<input type="Hidden" name="redirect_url" value="">
				<input type="Hidden" name="login_status" value="login">
				'.trim($userident).'
			</form>';
			
			$lines[]='<tr><td><strong>'.$label.'</strong></td><td nowrap>'.trim($form).'</td></tr>';
		}	
		return "<table border=1 cellpadding=5 cellspacing=1>".implode("",$lines)."</table>";	
	}
	function loginLog($DB)	{
			// Non-admin users
		$query="SELECT sys_log.*, be_users.username  AS username, be_users.admin AS admin FROM sys_log,be_users WHERE be_users.uid=sys_log.userid AND sys_log.type=255 AND sys_log.tstamp > ".(time()-(60*60*24*30))." ORDER BY sys_log.tstamp DESC";

		//1=login, 2=logout, 3=failed login (+ errorcode 3), 4=failure_warning_email sent
		$res = mysql($DB,$query);
		echo mysql_error();
		$dayRef="";
		$lines=array();
		while($row = mysql_fetch_assoc($res))	{
			$day = date("d-m-Y",$row["tstamp"]);
			if ($dayRef!=$day)	{
				$lines[]='
				<h4>'.$day.':</h4>';
				$dayRef=$day;
			}
			$theLine = date("H:i",$row["tstamp"]).":   ".str_pad(substr($row["username"],0,10),10)."    ".$this->log_getDetails($row["details"],unserialize($row["log_data"]));
			$lines[]= $row["admin"] ? '<span class=redclass>'.$theLine.'</span>' : $theLine;
			
			// debug($row);
		}
		return '<pre>'.implode(chr(10),$lines).'</pre>';
	}
	function log_getDetails($text,$data)	{
			// $code is used later on to substitute errormessages with language-corrected values...
		if (is_array($data))	{
			return sprintf($text, $data[0],$data[1],$data[2],$data[3],$data[4]);
		} else return $text;
	}
	function changeAdminPasswordsForm()	{
		reset($this->changeAdminPasswords);
		$content="";
		while(list($k,$p)=each($this->changeAdminPasswords))	{
			$content.='<h3>'.$k.'</h3>';
			reset($p);
			while(list($kk,$pp)=each($p))	{
				$content.='<nobr>';
				$content.='<input type="checkbox" name="SETFIELDS[]" value="'.$pp.'"> '.$pp.' - ';
				$content.=$this->collectAdminPasswords[$k][$kk];
				$content.='</nobr><BR>';
			}
		}
		
		$content.='New password: <input type="text" name="NEWPASS"><BR>';
		$content.='New password (md5): <input type="text" name="NEWPASS_md5"><BR>
			(This overrules any plain password above!)
		<br>';
		$content='
		<form action="'.$this->scriptName.'?type=page&show=admin" method="post">
		'.$content.'
		<input type="submit" name="Set">
		</form>
		';
		
		return $content;
	}
	function setNewPasswords()	{
		$whichFields = t3lib_div::GPvar("SETFIELDS");

		$pass = trim(t3lib_div::GPvar("NEWPASS"));
		$passMD5 = t3lib_div::GPvar("NEWPASS_md5");
		$updatedFlag=0;
		if ($pass || $passMD5)	{
			$pass = $passMD5 ? $passMD5 : md5($pass);
			
			reset($whichFields);
			while(list(,$values)=each($whichFields))	{
				$parts = explode(":",$values);
				if (count($parts)>2)	{
					$key = $this->mapDBtoKey[$parts[0]];
					if ($key && isset($this->globalSiteInfo[$key]["siteInfo"]))	{
						$error = $this->connectToDatabase($this->globalSiteInfo[$key]["siteInfo"]);
						if (!$error)	{
							$DB = $this->globalSiteInfo[$key]["siteInfo"]["TYPO3_db"];
							$content.='<h3>Updating '.$DB.':</h3>';
							$query = "UPDATE be_users SET password='".addslashes($pass)."' WHERE uid=".intval($parts[1])." AND username='".addslashes($parts[2])."' AND admin!=0";	// username/admin are added to security. But they are certainly redundant!!
							$content.='<i>'.htmlspecialchars($query).'</i><BR>';
							$res = mysql($DB,$query);
							echo mysql_error();
							$content.='Affected rows: '.mysql_affected_rows().'<BR><HR>';
							$updatedFlag="1";
						}
					}
				}
			}
		}
		$this->initProcess();
		return $content;
	}
	function defaultSet()	{
		$style = '
<style type="text/css">
.redclass {color: red;}
P {font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 11px}
FONT {font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10px}
H1 {font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 20px; color: #000066;}
H2 {font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 17px; color: #000066;}
H3 {font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 14px; color: #000066;}
H4 {font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 11px; color: maroon;}
TD {font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 10px}
</style>
';

		switch(t3lib_div::GPvar("type"))	{
			case "phpinfo":
				phpinfo();
			break;
			case "page":
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<?php echo $style;?>
<html>
<head>
	<title>TYPO3 Super Admin MAIN</title>
</head>
<body>
<br>
<?php 
	echo $this->make();
?>
</body>
</html>
<?php 
			break;
			case "menu":
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<?php echo $style;?>
<html>
<head>
	<title>TYPO3 Super Admin MENU</title>
</head>
<body>
<?php 
	echo $this->make();
?>
</body>
</html>
<?php 
			break;
			case "localext":
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<?php echo $style;?>
<html>
<head>
	<title>TYPO3 Super Admin</title>
</head>
<body>
<?php 
	echo $this->make();
?>
</body>
</html>
<?php 
			break;
			default:
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>TYPO3 Super Admin</title>
</head>
<frameset  cols="250,*">
    <frame name="TSAmenu" src="superadmin.php?type=menu&show=menu" marginwidth="10" marginheight="10" scrolling="auto" frameborder="0">
    <frame name="TSApage" src="superadmin.php?type=page" marginwidth="10" marginheight="10" scrolling="auto" frameborder="0">
</frameset>
</html>
<?php	
			break;
		}
	}
}


if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["t3lib/class.t3lib_superadmin.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["t3lib/class.t3lib_superadmin.php"]);
}

?>