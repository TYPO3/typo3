<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * Class, doing the sending of Direct-mails, eg. through a cron-job
 * Belongs to/See "direct_mail" extension.
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   86: class t3lib_dmailer extends t3lib_htmlmail 
 *   97:     function dmailer_prepare($row)	
 *  145:     function dmailer_sendAdvanced($recipRow,$tableNameChar)	
 *  217:     function dmailer_sendSimple($addressList)	
 *  239:     function dmailer_getBoundaryParts($cArray,$userCategories)	
 *  261:     function dmailer_masssend($query_info,$table,$mid)	
 *  298:     function dmailer_masssend_list($query_info,$mid)	
 *  360:     function shipOfMail($mid,$recipRow,$tKey)	
 *  378:     function convertFields($recipRow)	
 *  393:     function dmailer_setBeginEnd($mid,$key)	
 *  418:     function dmailer_howManySendMails($mid,$rtbl='')	
 *  434:     function dmailer_isSend($mid,$rid,$rtbl)	
 *  447:     function dmailer_getSentMails($mid,$rtbl)	
 *  467:     function dmailer_addToMailLog($mid,$rid,$size,$parsetime,$html)	
 *  479:     function runcron()	
 *
 * TOTAL FUNCTIONS: 14
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



/**
 *
 * SETTING UP a cron job on a UNIX box for distribution:
 * 
 * Write at the shell:
 * 
 * crontab -e
 * 
 * 
 * Then enter this line follow by a line-break:
 * 
 * * * * * /www/[path-to-your-typo3-site]/typo3/mod/web/dmail/dmailerd.phpcron
 * 
 * Every minute the cronjob checks if there are mails in the queue. 
 * If there are mails, 100 is sent at a time per job.
 */
/**
 * Class, doing the sending of Direct-mails, eg. through a cron-job
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_dmailer extends t3lib_htmlmail {
	var $sendPerCycle =50;
	var $logArray =array();
	var $massend_id_lists = array();
	var $flag_html = 0;
	var $flag_plain = 0;

	/**
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function dmailer_prepare($row)	{
		$sys_dmail_uid = $row['uid'];
		$this->useBase64();
		$this->theParts = unserialize($row['mailContent']);
		$this->messageid = $this->theParts['messageid'];
		$this->subject = $row['subject'];
		$this->from_email = $row['from_email'];
		$this->from_name = ($row['from_name']) ? $row['from_name'] : '';
		$this->replyto_email = ($row['replyto_email']) ? $row['replyto_email'] : '';
		$this->replyto_name = ($row['replyto_name']) ? $row['replyto_name'] : '';
		$this->organisation = ($row['organisation']) ? $row['organisation'] : '';
		$this->priority = t3lib_div::intInRange($row['priority'],1,5);
		$this->mailer = 'TYPO3 Direct Mail module';

		$this->dmailer['sectionBoundary'] = '<!--DMAILER_SECTION_BOUNDARY';
		$this->dmailer['html_content'] = base64_decode($this->theParts['html']['content']);
		$this->dmailer['plain_content'] = base64_decode($this->theParts['plain']['content']);
		$this->dmailer['messageID'] = $this->messageid;
		$this->dmailer['sys_dmail_uid'] = $sys_dmail_uid;
		$this->dmailer['sys_dmail_rec'] = $row;
	
		$this->dmailer['boundaryParts_html'] = explode($this->dmailer['sectionBoundary'], '_END-->'.$this->dmailer['html_content']);
		while(list($bKey,$bContent)=each($this->dmailer['boundaryParts_html']))	{
			$this->dmailer['boundaryParts_html'][$bKey] = explode('-->',$bContent,2);
				// Now, analyzing which media files are used in this part of the mail:
			$mediaParts = explode('cid:part',$this->dmailer['boundaryParts_html'][$bKey][1]);
			reset($mediaParts);
			next($mediaParts);
			while(list(,$part)=each($mediaParts))	{
				$this->dmailer['boundaryParts_html'][$bKey]['mediaList'].=','.strtok($part,'.');
			}
		}
		$this->dmailer['boundaryParts_plain'] = explode($this->dmailer['sectionBoundary'], '_END-->'.$this->dmailer['plain_content']);
		while(list($bKey,$bContent)=each($this->dmailer['boundaryParts_plain']))	{
			$this->dmailer['boundaryParts_plain'][$bKey] = explode('-->',$bContent,2);
		}
		
		$this->flag_html = $this->theParts['html']['content'] ? 1 : 0;
		$this->flag_plain = $this->theParts['plain']['content'] ? 1 : 0;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$recipRow: ...
	 * @param	[type]		$tableNameChar: ...
	 * @return	[type]		...
	 */
	function dmailer_sendAdvanced($recipRow,$tableNameChar)	{
		$returnCode=0;
		if ($recipRow['email'])	{
			$midRidId = 'MID'.$this->dmailer['sys_dmail_uid'].'_'.$tableNameChar.$recipRow['uid'];
			$uniqMsgId = md5(microtime()).'_'.$midRidId;
			$rowFieldsArray = explode(',', 'uid,name,title,email,phone,www,address,company,city,zip,country,fax,firstname');
			$uppercaseFieldsArray = explode(',', 'name,firstname');
			$authCode = t3lib_div::stdAuthCode($recipRow['uid']);
			$this->mediaList='';
			if ($this->flag_html && $recipRow['module_sys_dmail_html'])		{
				$tempContent_HTML = $this->dmailer_getBoundaryParts($this->dmailer['boundaryParts_html'],$recipRow['module_sys_dmail_category']);
				reset($rowFieldsArray);
				while(list(,$substField)=each($rowFieldsArray))	{
					$tempContent_HTML = str_replace('###USER_'.$substField.'###', $recipRow[$substField], $tempContent_HTML);
				}
				reset($uppercaseFieldsArray);
				while(list(,$substField)=each($uppercaseFieldsArray))	{
					$tempContent_HTML = str_replace('###USER_'.strtoupper($substField).'###', strtoupper($recipRow[$substField]), $tempContent_HTML);
				}
				$tempContent_HTML = str_replace('###SYS_TABLE_NAME###', $tableNameChar, $tempContent_HTML);	// Put in the tablename of the userinformation
				$tempContent_HTML = str_replace('###SYS_MAIL_ID###', $this->dmailer['sys_dmail_uid'], $tempContent_HTML);	// Put in the uid of the mail-record
				$tempContent_HTML = str_replace('###SYS_AUTHCODE###', $authCode, $tempContent_HTML);
				$tempContent_HTML = str_replace($this->dmailer['messageID'], $uniqMsgId, $tempContent_HTML);	// Put in the unique message id in HTML-code
				$this->theParts['html']['content'] = $this->encodeMsg($tempContent_HTML);
				$returnCode|=1;
			} else $this->theParts['html']['content'] = '';
	
				// Plain
			if ($this->flag_plain)		{
				$tempContent_Plain = $this->dmailer_getBoundaryParts($this->dmailer['boundaryParts_plain'],$recipRow['module_sys_dmail_category']);
				reset($rowFieldsArray);
				while(list(,$substField)=each($rowFieldsArray))	{
					$tempContent_Plain = str_replace('###USER_'.$substField.'###', $recipRow[$substField], $tempContent_Plain);
				}
				reset($uppercaseFieldsArray);
				while(list(,$substField)=each($uppercaseFieldsArray))	{
					$tempContent_Plain = str_replace('###USER_'.strtoupper($substField).'###', strtoupper($recipRow[$substField]), $tempContent_Plain);
				}
				$tempContent_Plain = str_replace('###SYS_TABLE_NAME###', $tableNameChar, $tempContent_Plain);	// Put in the tablename of the userinformation
				$tempContent_Plain = str_replace('###SYS_MAIL_ID###', $this->dmailer['sys_dmail_uid'], $tempContent_Plain);	// Put in the uid of the mail-record
				$tempContent_Plain = str_replace('###SYS_AUTHCODE###', $authCode, $tempContent_Plain);
				
				if (trim($this->dmailer['sys_dmail_rec']['long_link_rdct_url']))	{
					$tempContent_Plain = t3lib_div::substUrlsInPlainText($tempContent_Plain,$this->dmailer['sys_dmail_rec']['long_link_mode']?'all':'76',trim($this->dmailer['sys_dmail_rec']['long_link_rdct_url']));
				}

				$this->theParts['plain']['content'] = $this->encodeMsg($tempContent_Plain);
				$returnCode|=2;
			} else $this->theParts['plain']['content'] = '';

				// Set content
			$this->messageid = $uniqMsgId;
			$this->Xid = $midRidId.'-'.md5($midRidId);
			$this->returnPath = str_replace('###XID###',$midRidId,$this->dmailer['sys_dmail_rec']['return_path']);
			
			$this->part=0;
			$this->setHeaders();
			$this->setContent();
			$this->setRecipient($recipRow['email']);
			
			$this->message = str_replace($this->dmailer['messageID'], $uniqMsgId, $this->message);	// Put in the unique message id in whole message body
			$this->sendtheMail();
		}
		return $returnCode;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$addressList: ...
	 * @return	[type]		...
	 */
	function dmailer_sendSimple($addressList)	{
		if ($this->theParts['html']['content'])		{
			$this->theParts['html']['content'] = $this->encodeMsg($this->dmailer_getBoundaryParts($this->dmailer['boundaryParts_html'],-1));
		} else $this->theParts['html']['content'] = '';
		if ($this->theParts['plain']['content'])		{
			$this->theParts['plain']['content'] = $this->encodeMsg($this->dmailer_getBoundaryParts($this->dmailer['boundaryParts_plain'],-1));
		} else $this->theParts['plain']['content'] = '';
		
		$this->setHeaders();
		$this->setContent();
		$this->setRecipient($addressList);
		$this->sendtheMail();
		return true;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$cArray: ...
	 * @param	[type]		$userCategories: ...
	 * @return	[type]		...
	 */
	function dmailer_getBoundaryParts($cArray,$userCategories)	{
		$userCategories = intval($userCategories);
		reset($cArray);
		$returnVal='';
		while(list(,$cP)=each($cArray))	{
			$key=substr($cP[0],1);
			if ($key=='END' || !$key || $userCategories<0 || (intval($key) & $userCategories)>0)	{
				$returnVal.=$cP[1];
				$this->mediaList.=$cP['mediaList'];
			}
		}
		return $returnVal;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$query_info: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$mid: ...
	 * @return	[type]		...
	 */
	function dmailer_masssend($query_info,$table,$mid)	{
		$enableFields['tt_address']='NOT tt_address.deleted AND NOT tt_address.hidden';
		$enableFields['fe_users']='NOT fe_users.deleted AND NOT fe_users.disable';
		$tKey = substr($table,0,1);
		$begin=intval($this->dmailer_howManySendMails($mid,$tKey));
		if ($query_info[$table])	{
			$query='SELECT '.$table.'.* FROM $table WHERE '.$enableFields[$table].' AND ('.$query_info[$table].') ORDER BY tstamp DESC LIMIT '.intval($begin).','.$this->sendPerCycle; // This way, we select newest edited records first. So if any record is added or changed in between, it'll end on top and do no harm
			$res=mysql(TYPO3_db,$query);
			if (mysql_error())	{
				die (mysql_error());
			}
			$numRows=mysql_num_rows($res);
			$cc=0;
			while($recipRow=mysql_fetch_assoc($res))	{
				if (!$this->dmailer_isSend($mid,$recipRow['uid'],$tKey))	{
					$pt = t3lib_div::milliseconds();
					if ($recipRow['telephone'])	$recipRow['phone'] = $recipRow['telephone'];	// Compensation for the fact that fe_users has the field, 'telephone' instead of 'phone'
					$recipRow['firstname']=strtok(trim($recipRow['name']),' ');

					$rC = $this->dmailer_sendAdvanced($recipRow,$tKey);
					$this->dmailer_addToMailLog($mid,$tKey.'_'.$recipRow['uid'],strlen($this->message),t3lib_div::milliseconds()-$pt,$rC);
				}
				$cc++;
			}
			$this->logArray[]='Sending '.$cc.' mails to table '.$table;
			if ($numRows < $this->sendPerCycle)	return true;
		}
		return false;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$query_info: ...
	 * @param	[type]		$mid: ...
	 * @return	[type]		...
	 */
	function dmailer_masssend_list($query_info,$mid)	{
		$enableFields['tt_address']='NOT tt_address.deleted AND NOT tt_address.hidden';
		$enableFields['fe_users']='NOT fe_users.deleted AND NOT fe_users.disable';

		$c=0;
		$returnVal=true;
		if (is_array($query_info['id_lists']))	{
			reset($query_info['id_lists']);
			while(list($table,$listArr)=each($query_info['id_lists']))	{
				if (is_array($listArr))	{
					$ct=0;
						// FInd tKey
					if ($table=='tt_address' || $table=='fe_users')	{
						$tKey = substr($table,0,1);
					} elseif ($table=='PLAINLIST')	{
						$tKey='P';
					} else {$tKey='u';}

						// Send mails
					$sendIds=$this->dmailer_getSentMails($mid,$tKey);
					if ($table=='PLAINLIST')	{
						$sendIdsArr=explode(',',$sendIds);
						reset($listArr);
						while(list($kval,$recipRow)=each($listArr))	{
							$kval++;
							if (!in_array($kval,$sendIdsArr))	{
								if ($c>=$this->sendPerCycle)	{$returnVal = false; break;}		// We are NOT finished!
								$recipRow['uid']=$kval;
								$this->shipOfMail($mid,$recipRow,$tKey);
								$ct++;
								$c++;
							}
						}
					} else {
						$idList = implode(',',$listArr);
						if ($idList)	{
							$query='SELECT '.$table.'.* FROM $table WHERE uid IN ('.$idList.') AND uid NOT IN ('.($sendIds?$sendIds:0).') AND '.($enableFields[$table]?$enableFields[$table]:'1=1').' LIMIT '.($this->sendPerCycle+1);
							$res=mysql(TYPO3_db,$query);
							if (mysql_error())	{die (mysql_error());}
							while($recipRow=mysql_fetch_assoc($res))	{
								if ($c>=$this->sendPerCycle)	{$returnVal = false; break;}		// We are NOT finished!
								$this->shipOfMail($mid,$recipRow,$tKey);
								$ct++;
								$c++;
							}
						}
					}
					$this->logArray[]='Sending '.$ct.' mails to table '.$table;
				}
			}
		}
		return $returnVal;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$mid: ...
	 * @param	[type]		$recipRow: ...
	 * @param	[type]		$tKey: ...
	 * @return	[type]		...
	 */
	function shipOfMail($mid,$recipRow,$tKey)	{
		if (!$this->dmailer_isSend($mid,$recipRow['uid'],$tKey))	{
			$pt = t3lib_div::milliseconds();
			$recipRow=$this->convertFields($recipRow);
			
//			debug('->'.$recipRow['uid'],1);
//			$recipRow['email']='kasper@typo3.com';
			$rC=$this->dmailer_sendAdvanced($recipRow,$tKey);
			$this->dmailer_addToMailLog($mid,$tKey.'_'.$recipRow['uid'],strlen($this->message),t3lib_div::milliseconds()-$pt,$rC);
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$recipRow: ...
	 * @return	[type]		...
	 */
	function convertFields($recipRow)	{
		if ($recipRow['telephone'])	$recipRow['phone'] = $recipRow['telephone'];	// Compensation for the fact that fe_users has the field, 'telephone' instead of 'phone'
		$recipRow['firstname']=trim(strtok(trim($recipRow['name']),' '));
		if (strlen($recipRow['firstname'])<2 || ereg('[^[:alnum:]]$',$recipRow['firstname']))		$recipRow['firstname']=$recipRow['name'];		// Firstname must be more that 1 character
		if (!trim($recipRow['firstname']))	$recipRow['firstname']=$recipRow['email'];
		return 	$recipRow;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$mid: ...
	 * @param	[type]		$key: ...
	 * @return	[type]		...
	 */
	function dmailer_setBeginEnd($mid,$key)	{
		$query='UPDATE sys_dmail SET scheduled_'.$key.'='.time().' WHERE uid='.intval($mid);
		$res=mysql(TYPO3_db,$query);
		echo mysql_error();
		switch($key)	{
			case 'begin':
				$subject='DMAILER mid:'.$mid.' JOB BEGIN';
				$message=': '.date('d-m-y h:i:s');
			break;
			case 'end':
				$subject='DMAILER mid:'.$mid.' JOB END';
				$message=': '.date('d-m-y h:i:s');
			break;
		}
		$this->logArray[]=$subject.': '.$message;
		mail($this->from_email, $subject, $message);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$mid: ...
	 * @param	[type]		$rtbl: ...
	 * @return	[type]		...
	 */
	function dmailer_howManySendMails($mid,$rtbl='')	{
		$tblClause = $rtbl ? ' AND rtbl="'.$rtbl.'"' : '';
		$query='SELECT count(*) FROM sys_dmail_maillog WHERE mid='.$mid.' AND response_type=0'.$tblClause;
		$res=mysql(TYPO3_db,$query);
		$row= mysql_fetch_row($res);
		return $row[0];
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$mid: ...
	 * @param	[type]		$rid: ...
	 * @param	[type]		$rtbl: ...
	 * @return	[type]		...
	 */
	function dmailer_isSend($mid,$rid,$rtbl)	{
		$query='SELECT uid FROM sys_dmail_maillog WHERE rid='.intval($rid).' AND rtbl="'.$rtbl.'" AND mid='.intval($mid).' AND response_type=0';
		$res=mysql(TYPO3_db,$query);
		return mysql_num_rows($res);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$mid: ...
	 * @param	[type]		$rtbl: ...
	 * @return	[type]		...
	 */
	function dmailer_getSentMails($mid,$rtbl)	{
		$query='SELECT rid FROM sys_dmail_maillog WHERE mid='.$mid.' AND rtbl="'.$rtbl.'" AND response_type=0';
		$res=mysql(TYPO3_db,$query);
		$list=array();
		while($row=mysql_fetch_assoc($res))	{
			$list[]=$row['rid'];
		}
		return implode(',',$list);
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$mid: ...
	 * @param	[type]		$rid: ...
	 * @param	[type]		$size: ...
	 * @param	[type]		$parsetime: ...
	 * @param	[type]		$html: ...
	 * @return	[type]		...
	 */
	function dmailer_addToMailLog($mid,$rid,$size,$parsetime,$html)	{
		$temp_recip=explode('_',$rid);
		$temp_query="INSERT INTO sys_dmail_maillog (mid,rtbl,rid,tstamp,url,size,parsetime,html_sent) VALUES ('".intval($mid)."','".addslashes($temp_recip[0])."','".intval($temp_recip[1])."','".time()."','','".$size."','".$parsetime."',".intval($html).')';
		$temp_res = mysql(TYPO3_db,$temp_query);
		echo mysql_error();
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function runcron()	{
		$pt = t3lib_div::milliseconds();

		$query='SELECT * FROM sys_dmail WHERE scheduled!=0 AND scheduled<'.time().' AND scheduled_end=0 ORDER BY scheduled';
		$res=mysql(TYPO3_db,$query);
		if (mysql_error())	{
			die (mysql_error());
		}
		$this->logArray[]='Invoked at '.date('h:i:s d-m-Y');
		
		if ($row=mysql_fetch_assoc($res))	{
			$this->logArray[]='sys_dmail record '.$row['uid'].", '".$row['subject']."' processed...";
			$this->dmailer_prepare($row);
			$query_info=unserialize($row['query_info']);
			if (!$row['scheduled_begin'])	{$this->dmailer_setBeginEnd($row['uid'],'begin');}
/*
			$finished = $this->dmailer_masssend($query_info,'tt_address',$row['uid']);
			if ($finished)	{
				$finished = $this->dmailer_masssend($query_info,'fe_users',$row['uid']);
			}*/
			$finished = $this->dmailer_masssend_list($query_info,$row['uid']);
			
			if ($finished)	{$this->dmailer_setBeginEnd($row['uid'],'end');}
		} else {
			$this->logArray[]='Nothing to do.';
		}

		$parsetime=t3lib_div::milliseconds()-$pt;
		$this->logArray[]='Ending, parsetime: '.$parsetime.' ms';;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_dmailer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_dmailer.php']);
}
?>