<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

require_once(PATH_t3lib."class.t3lib_tceforms.php");
require_once(PATH_t3lib."class.t3lib_tcemain.php");

require_once(PATH_t3lib."class.t3lib_fullsearch.php");
require_once(PATH_t3lib."class.t3lib_xml.php");
require_once(PATH_t3lib."class.t3lib_loaddbgroup.php");

class tx_sysaction extends mod_user_task {
	var $todoTypesCache = array();
	var $insCounter=0;
	var $xCol;
	var $t3lib_TCEforms;

	function overview_main(&$pObj)	{
		$mC = $this->renderActionList();
		if ($mC)	{
			$icon = '<img src="'.$this->backPath.t3lib_extMgm::extRelPath("sys_action").'ext_icon.gif" width=18 height=16 class="absmiddle">';
			$content.=$pObj->doc->section($icon."&nbsp;".$this->headLink("tx_sysaction",1),$mC,1,1,0,1);
		}
		return $content;
	}
	function main() {
		global $SOBE,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		return $this->renderActions();
	}
	function JScode()	{
		$this->t3lib_TCEforms = t3lib_div::makeInstance("t3lib_TCEforms");
		$this->t3lib_TCEforms->backPath = $GLOBALS["BACK_PATH"];
		return $this->t3lib_TCEforms->dbFileCon();
	}

	// ************************
	// ACTIONS
	// ***********************
	function renderActions()	{
		global $LANG;
		$uid = t3lib_div::intInRange(t3lib_div::_GP("sys_action_uid"),0);
		$out="";
		$header="";
		if ($uid)	{
			$res = $this->getActionResPointer($uid);
			if($actionRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{

					// Action header:
				$header = t3lib_iconworks::getIconImage("sys_action",$actionRow,$this->backPath,'hspace="2" class="absmiddle"').'<b>'.$actionRow["title"].'</b>';
				$out.='<table border=0 cellpadding=0 cellspacing=1 width=100%>
					<tr><td colspan=2 class="bgColor5">'.fw($header).'</td></tr>
					<tr>
						<td width=1% valign=top class="bgColor4">'.fw($LANG->sL(t3lib_BEfunc::getItemLabel("sys_action","type"))."&nbsp;").'</td>
						<td valign=top class="bgColor4">'.fw(htmlspecialchars(t3lib_BEfunc::getProcessedValue("sys_action","type",$actionRow["type"]))).'</td>
					</tr>
					<tr>
						<td width=1% valign=top class="bgColor4">'.fw($LANG->sL(t3lib_BEfunc::getItemLabel("sys_action","description"))."&nbsp;").'</td>
						<td valign=top class="bgColor4">'.fw(nl2br($actionRow["description"])).'</td>
					</tr>';
				$out.='</table>';
				$theCode = $this->pObj->doc->section("",$out,0,1);



					// Types of actions:
				switch($actionRow["type"])	{
					case 1:
						$actionContent="";
						$beRec = t3lib_BEfunc::getRecord("be_users",intval($actionRow["t1_copy_of_user"]));
						if (is_array($beRec))	{
							// Create or update:
							$inData = t3lib_div::_GP("data");
//	debug($inData);
							$userRecord="";
							$newFlag=0;
							if (is_array($inData["be_users"]))	{
								$nId = $this->action_t1_createUpdateBeUser($inData["be_users"],$actionRow);
								$userRecord=t3lib_BEfunc::getRecord("be_users",$nId);
							}
							if (t3lib_div::_GP("be_users_uid"))	{
								$userRecord=t3lib_BEfunc::getRecord("be_users",t3lib_div::_GP("be_users_uid"));
							}
							if (!is_array($userRecord))	{
								$userRecord=array();
								if (is_array($inData["be_users"]["NEW"]))	{	$userRecord=$inData["be_users"]["NEW"];		}
								$userRecord["uid"]="NEW";
								$newFlag=1;
							}



								// List of users...
							$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'be_users', 'pid=0 AND cruser_id='.intval($this->BE_USER->user['uid']).' AND createdByAction='.intval($actionRow['uid']).t3lib_BEfunc::deleteClause('be_users'), '', 'username');
							$lines = array();
							while($uRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
								$lines[] = "<nobr>".($uRow["uid"]==$userRecord["uid"]?"<b>":"").$this->action_linkUserName(t3lib_iconworks::getIconImage("be_users",$uRow,$this->backPath,'title="uid='.$uRow["uid"].'" hspace="2" align="top"').$uRow["username"]." (".$uRow["realName"].")".($uRow["uid"]==$userRecord["uid"]?"</b>":"")."</nobr>",$actionRow["uid"],$uRow["uid"])."<br>";
							}
							if (count($lines))	{
								$theCode.= $this->pObj->doc->section($LANG->getLL("action_t1_listOfUsers"),implode("",$lines),0,1);
							}

							$formA=Array();
							$opt=array();

							$grList = t3lib_div::trimExplode(",",$actionRow["t1_allowed_groups"],1);
							reset($grList);
							$opt[]='<option value=""></option>';
							while(list(,$gu)=each($grList))	{
								$checkGr = t3lib_BEfunc::getRecord("be_groups",$gu);
								if (is_array($checkGr))	$opt[]='<option value="'.$checkGr["uid"].'"'.(t3lib_div::inList($userRecord["usergroup"],$checkGr["uid"])?" selected":"").'>'.htmlspecialchars($checkGr["title"]).'</option>';
							}

							$formA[]=array($LANG->getLL("action_BEu_hidden").":&nbsp;", '<input type="checkbox" name="data[be_users]['.$userRecord["uid"].'][disable]" value=1'.($userRecord["disable"]?" checked":"").'>');
							$formA[]=array($LANG->getLL("action_BEu_username").":&nbsp;", '<input type="text" name="data[be_users]['.$userRecord["uid"].'][username]" max=15 value="'.htmlspecialchars($userRecord["username"]).'"'.$this->pObj->doc->formWidth(15).'>');
							$formA[]=array($LANG->getLL("action_BEu_password").":&nbsp;", '<input type="password" name="data[be_users]['.$userRecord["uid"].'][password]" max=40'.$this->pObj->doc->formWidth(15).'>');
							$formA[]=array($LANG->getLL("action_BEu_realName").":&nbsp;", '<input type="text" name="data[be_users]['.$userRecord["uid"].'][realName]" value="'.htmlspecialchars($userRecord["realName"]).'"'.$this->pObj->doc->formWidth(30).'>');
							$formA[]=array($LANG->getLL("action_BEu_email").":&nbsp;", '<input type="text" name="data[be_users]['.$userRecord["uid"].'][email]" value="'.htmlspecialchars($userRecord["email"]).'"'.$this->pObj->doc->formWidth(30).'>');
							if (count($grList)) {
								$formA[]=array($LANG->getLL("action_BEu_usergroups").":&nbsp;", '<select size='.t3lib_div::intInRange(count($opt),2).' multiple name="data[be_users]['.$userRecord["uid"].'][usergroups][]">'.implode("",$opt).'</select>');
							}
								// DB mounts:
							$loadDB = t3lib_div::makeInstance("t3lib_loadDBGroup");
							$loadDB->start($userRecord["db_mountpoints"],"pages");
							$params=array(
								"size" => 3
							);
							$formA[]=array($LANG->getLL("action_BEu_db_mount").":&nbsp;", $this->t3lib_TCEforms->dbFileIcons('data[be_users]['.$userRecord["uid"].'][db_mountpoints]','db','pages',$loadDB->itemArray,"",$params));

							$formA[]=array("&nbsp;","&nbsp;");
							$formA[]=array("&nbsp;", '<input type=hidden value="'.$uid.'" name="sys_action_uid"><input type=hidden value="'.t3lib_div::_GP("be_users_uid").'" name="be_users_uid"><input type=hidden value="'.$uid.'" name="sys_action_uid"><input type="submit" name="submit" value="'.$LANG->getLL($newFlag?"lCreate":"lUpdate").'">'.(!$newFlag?' <input type="submit" name="_delete_" value="'.$LANG->getLL("lDelete").'" onClick="return confirm('.$GLOBALS['LANG']->JScharCode($LANG->getLL("lDelete_warning")).');">':''));

							if (!$newFlag)	{
								$p = 'uid='.$userRecord["uid"].", ".$LANG->getLL("lHomedir").": ";
								$hPath=$this->action_getUserMainDir();
								if ($hPath && @is_dir($hPath.$userRecord["uid"]."/"))	{
									$p.= $hPath;
								} else {
									$p.= $LANG->getLL("lNone");
								}
								$actionContent.=t3lib_iconworks::getIconImage("be_users",$userRecord,$this->backPath,'title="'.htmlspecialchars($p).'" hspace=2 align=top').$userRecord["username"]." (".$userRecord["realName"].")";
							}
							$actionContent.=$this->pObj->doc->table($formA);
							$theCode.= $this->pObj->doc->section($LANG->getLL($newFlag?"action_Create":"action_Update"),$actionContent,0,1);
						} else {
							$theCode.= $this->pObj->doc->section($LANG->getLL("action_error"),'<span class="typo3-red">'.$LANG->getLL("action_notReady").'</span>',0,1);
						}
					break;
					case 2:
						if (t3lib_extMgm::isLoaded("lowlevel"))	{
							$sql_query = unserialize($actionRow["t2_data"]);
							if (is_array($sql_query) && strtoupper(substr(trim($sql_query["qSelect"]),0,6))=="SELECT")	{
								$fullsearch = t3lib_div::makeInstance("t3lib_fullsearch");
								$fullsearch->formW = 40;
								$fullsearch->noDownloadB=1;
								$actionContent="";
	//		debug($sql_query);
								$type = $sql_query["qC"]["search_query_makeQuery"];
								$res = $GLOBALS['TYPO3_DB']->sql(TYPO3_db,$sql_query["qSelect"]);
								if (!$GLOBALS['TYPO3_DB']->sql_error())	{
									$fullsearch->formW=48;
									$cP=$fullsearch->getQueryResultCode($type,$res,$sql_query["qC"]["queryTable"]);
									$actionContent=$cP["content"];
									if ($type=="csv" || $type=="xml")	{
										$actionContent.='<BR><BR><a href="'.t3lib_div::getIndpEnv("REQUEST_URI").'&download_file=1"><strong>'.$LANG->getLL("action_download_file").'</strong></a>';
									}
								} else {
									$actionContent.=$GLOBALS['TYPO3_DB']->sql_error();
								}
								if ($this->BE_USER->isAdmin())	{
									$actionContent.="<HR>".$fullsearch->tableWrap($sql_query["qSelect"]);
									$actionContent.='<BR><strong><a href="'.$this->backPath.t3lib_extMgm::extRelPath("lowlevel").'dbint/index.php?id='.
										'&SET[function]=search'.
										'&SET[search]=query'.
										'&storeControl[STORE]=-'.$actionRow["uid"].
										'&storeControl[LOAD]=1'.
										'">Edit Query</a></strong>';
								}
								$theCode.= $this->pObj->doc->section($LANG->getLL("action_t2_result"),$actionContent,0,1);
							} else {
								$theCode.= $this->pObj->doc->section($LANG->getLL("action_error"),'<span class="typo3-red">'.$LANG->getLL("action_notReady").'</span>',0,1);
							}
						} else {
							$theCode.= $this->pObj->doc->section($LANG->getLL("action_error"),'<span class="typo3-red">The extension "lowlevel" must be installed in order to create a quiry</span>',0,1);
						}
					break;
					case 3:
						Header("Location: ".t3lib_div::locationHeaderUrl($this->backPath."db_list.php?id=".intval($actionRow["t3_listPid"])."&table=".$actionRow["t3_tables"]));
						exit;
					break;
					case 4:
						$dbAnalysis = t3lib_div::makeInstance("t3lib_loadDBGroup");
						$dbAnalysis->fromTC=0;
						$dbAnalysis->start($actionRow["t4_recordsToEdit"],"*");
						$dbAnalysis->getFromDB();

						$lines=array();
						reset($dbAnalysis->itemArray);
						while(list(,$el)=each($dbAnalysis->itemArray))	{
							$path = t3lib_BEfunc::getRecordPath ($el["id"],$this->perms_clause,$this->BE_USER->uc["titleLen"]);
							$lines[]='<tr>
								<td nowrap class="bgColor4">'.
									'<a href="'.$this->backPath.'alt_doc.php?returnUrl='.rawurlencode(t3lib_div::getIndpEnv("REQUEST_URI")).'&edit['.$el["table"].']['.$el["id"].']=edit">'.
									t3lib_iconworks::getIconImage($el["table"],$dbAnalysis->results[$el["table"]][$el["id"]],$this->backPath,'hspace="2" align="top" title="'.htmlspecialchars($path).'"').
									t3lib_BEfunc::getRecordTitle($el["table"],$dbAnalysis->results[$el["table"]][$el["id"]],1).'</a></td>
								</tr>';
						}
						$actionContent = '<table border=0 cellpadding=0 cellspacing=2>'.implode("",$lines).'</table>';
						$theCode.= $this->pObj->doc->section($LANG->getLL("action_t4_edit"),$actionContent,0,1);
					break;
					case 5:
						Header('Location: '.t3lib_div::locationHeaderUrl($this->backPath.'alt_doc.php?returnUrl='.rawurlencode('db_list.php?id='.intval($actionRow['t3_listPid']).'&table='.$actionRow['t3_tables']).'&edit['.$actionRow['t3_tables'].']['.intval($actionRow['t3_listPid']).']=new'));
						exit;
					break;
					default:
						$theCode.= $this->pObj->doc->section($LANG->getLL("action_error"),'<span class="typo3-red">'.$LANG->getLL("action_noType").'</span>',0,1);
					break;
				}

			}
		}
		return $theCode;
	}
	function getActionResPointer($uid=0)	{
		if ($this->BE_USER->isAdmin())	{
			$wQ='';
			if (intval($uid)>0)	$wQ.=' AND sys_action.uid='.intval($uid);

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_action', 'sys_action.pid=0'.$wQ, '', 'sys_action.title');
		} else {
			$wQ = 'be_groups.uid IN ('.($this->BE_USER->groupList?$this->BE_USER->groupList:0).')';
			$hQ = 'AND sys_action.hidden=0 ';
			if (intval($uid)>0)	$wQ.=' AND sys_action.uid='.intval($uid);

			$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
						'sys_action.*',
						'sys_action',
						'sys_action_asgr_mm',
						'be_groups',
						' AND '.$wQ.' AND sys_action.pid=0 '.$hQ,
						'sys_action.uid',
						'sys_action.title'
					);
		}

		return $res;
	}
	function renderActionList()	{
		global $LANG;

		$res = $this->getActionResPointer();
		$lines=array();
		while($actionRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$lines[]='<nobr>'.t3lib_iconworks::getIconImage("sys_action",$actionRow,$this->backPath,'hspace="2" align="top"').$this->action_link($this->fixed_lgd($actionRow["title"]),$actionRow["uid"],$actionRow["description"]).'</nobr><BR>';
		}
		$out = implode("",$lines);
		return $out;
	}
	function action_link($str,$id,$title="")	{
		$str='<a href="index.php?SET[function]=tx_sysaction&sys_action_uid='.$id.'" target="list_frame" onClick="this.blur();" title="'.htmlspecialchars($title).'">'.$str.'</a>';
		return $str;
	}
	function action_linkUserName($str,$id,$bid)	{
		$str='<a href="index.php?sys_action_uid='.$id.'&be_users_uid='.$bid.'" onClick="this.blur();">'.$str.'</a>';
		return $str;
	}
	function action_t1_createUpdateBeUser($arr,$actionRow=array())	{
		reset($arr);
		$key = key($arr);
		$data="";
		$nId=0;
		$BEuid = $actionRow["t1_copy_of_user"];
		if ($key=="NEW")	{
			$beRec = t3lib_BEfunc::getRecord("be_users",intval($BEuid));
			if (is_array($beRec) && trim($arr[$key]["password"]) && $this->fixUsername($arr[$key]["username"],$actionRow["t1_userprefix"]))	{
//				debug($arr[$key]);
				$data=array();
				$data["be_users"][$key]=$beRec;
				$data["be_users"][$key]["username"]=$this->fixUsername($arr[$key]["username"],$actionRow["t1_userprefix"]);
				$data["be_users"][$key]["password"]=md5(trim($arr[$key]["password"]));
				$data["be_users"][$key]["realName"]=$arr[$key]["realName"];
				$data["be_users"][$key]["email"]=$arr[$key]["email"];
				$data["be_users"][$key]["disable"]=intval($arr[$key]["disable"]);
				$data["be_users"][$key]["admin"]=0;
				$data["be_users"][$key]["usergroup"] = $this->fixUserGroup($data["be_users"][$key]["usergroup"],$actionRow["t1_allowed_groups"],$arr[$key]["usergroups"]);
				$data["be_users"][$key]["db_mountpoints"]=$arr[$key]["db_mountpoints"];
				$data["be_users"][$key]["createdByAction"]=$actionRow["uid"];
			}
		} else {
			$beRec = t3lib_BEfunc::getRecord("be_users",intval($key));
			if (is_array($beRec) && $beRec["cruser_id"]==$this->BE_USER->user["uid"])	{
				if (t3lib_div::_GP("_delete_"))	{
						// delete... ?
					$cmd=array();
					$cmd["be_users"][$key]["delete"]=1;

					$tce = t3lib_div::makeInstance("t3lib_TCEmain");
					$tce->stripslashes_values=0;
					$tce->start(Array(),$cmd,$this->BE_USER);
					$tce->admin=1;
					$tce->process_cmdmap();
//					debug($cmd);
					$nId = 0;
				} elseif ($this->fixUsername($arr[$key]["username"],$actionRow["t1_userprefix"]))	{
					// check ownership...
					$data=array();
					$data["be_users"][$key]["username"]=$this->fixUsername($arr[$key]["username"],$actionRow["t1_userprefix"]);
					if (trim($arr[$key]["password"]))	{$data["be_users"][$key]["password"]=md5(trim($arr[$key]["password"]));}

					$data["be_users"][$key]["realName"]=$arr[$key]["realName"];
					$data["be_users"][$key]["email"]=$arr[$key]["email"];
					$data["be_users"][$key]["disable"]=intval($arr[$key]["disable"]);
					$data["be_users"][$key]["admin"]=0;
					$data["be_users"][$key]["usergroup"] = $this->fixUserGroup($beRec["usergroup"],$actionRow["t1_allowed_groups"],$arr[$key]["usergroups"]);
					$data["be_users"][$key]["db_mountpoints"]=$arr[$key]["db_mountpoints"];
					$nId=$key;
				}
			}
		}


		if (is_array($data))	{
			$tce = t3lib_div::makeInstance("t3lib_TCEmain");
			$tce->stripslashes_values=0;
			$tce->start($data,Array(),$this->BE_USER);
			$tce->admin=1;
			$tce->process_datamap();
			$nId = intval($tce->substNEWwithIDs["NEW"]);
			if ($nId)	{	// Create
				$this->action_createDir($nId);
			} else {	// update
				$nId=intval($key);
			}
			unset($tce);
		}
		return $nId;
	}
	function fixUsername($username,$prefix)	{
		$username=trim($username);
		$prefix=trim($prefix);
		$username=ereg_replace("^".quotemeta($prefix),"",$username);

		if ($username)	{
			return $prefix.$username;
		} else return false;
	}
	function fixUserGroup($curUserGroup,$allowedGroups,$inGroups)	{
			// User group:
			// All current groups:
		$cGroups = array_flip(t3lib_div::trimExplode(",",$curUserGroup,1));
		$grList = t3lib_div::intExplode(",",$allowedGroups);
		reset($grList);
		while(list(,$gu)=each($grList))	{
			unset($cGroups[$gu]);	// Remove the group if it's in the array for some reason...
		}
			// reverse array again and set incoming groups:
		$cGroups=array_keys($cGroups);
		if (is_array($inGroups))	{
			reset($inGroups);
			while(list(,$gu)=each($inGroups))	{
				$checkGr = t3lib_BEfunc::getRecord("be_groups",$gu);
				if (is_array($checkGr) && in_array($gu,$grList))	{
					$cGroups[]=$gu;
				}
			}
		}
		return implode(",",$cGroups);
	}
	function action_createDir($uid)	{
		$path = $this->action_getUserMainDir();
		if ($path)	{
			@mkdir($path.$uid,0755);
			@mkdir($path.$uid."/_temp_",0755);
//			debug($path);
		}
	}
	function action_getUserMainDir()	{
		$path = $GLOBALS["TYPO3_CONF_VARS"]["BE"]["userHomePath"];
		if ($path && @is_dir($path) &&
				$GLOBALS["TYPO3_CONF_VARS"]["BE"]["lockRootPath"] &&
				t3lib_div::isFirstPartOfStr($path,$GLOBALS["TYPO3_CONF_VARS"]["BE"]["lockRootPath"]) &&
				substr($path,-1)=="/"
			)	{
			return $path;
		}
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sys_action/class.tx_sysaction.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/sys_action/class.tx_sysaction.php"]);
}

?>