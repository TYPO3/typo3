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
 * Import / Export module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   87: class localPageTree extends t3lib_browseTree
 *   92:     function localPageTree()
 *  103:     function wrapTitle($title,$v)
 *  116:     function PM_ATagWrap($icon,$cmd,$bMark='')
 *  127:     function wrapIcon($icon,$row)
 *  136:     function permsC()
 *  146:     function ext_tree($pid)
 *
 *
 *  227: class SC_mod_tools_log_index extends t3lib_SCbase
 *  233:     function main()
 *  366:     function printContent()
 *
 *              SECTION: EXPORT FUNCTIONS
 *  388:     function exportData($inData)
 *  545:     function listQueryPid($table,$pid)
 *  558:     function makeConfigurationForm($inData)
 *
 *              SECTION: IMPORT FUNCTIONS
 *  651:     function importData($inData)
 *  748:     function tableSelector($prefix,$value,$excludeList="")
 *  781:     function renderSelectBox($prefix,$value,$optValues)
 *
 * TOTAL FUNCTIONS: 14
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:impexp/app/locallang.php');
require_once (PATH_t3lib.'class.t3lib_scbase.php');
require_once (t3lib_extMgm::extPath('impexp').'class.tx_impexp.php');
require_once (PATH_t3lib.'class.t3lib_browsetree.php');
require_once (PATH_t3lib.'class.t3lib_pagetree.php');

t3lib_extMgm::isLoaded('impexp',1);




/**
 * Main script class
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_impexp
 */
class localPageTree extends t3lib_browseTree {

	/**
	 * @return	[type]		...
	 */
	function localPageTree() {
		$this->init();
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$title: ...
	 * @param	[type]		$v: ...
	 * @return	[type]		...
	 */
	function wrapTitle($title,$v)	{
		$title= (!strcmp(trim($title),'')) ? '<em>['.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.no_title',1).']</em>' : htmlspecialchars($title);
		return $title;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$icon: ...
	 * @param	[type]		$cmd: ...
	 * @param	[type]		$bMark: ...
	 * @return	[type]		...
	 */
	function PM_ATagWrap($icon,$cmd,$bMark='')	{
		return $icon;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$icon: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function wrapIcon($icon,$row)	{
		return $icon;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function permsC()	{
		return $this->BE_USER->getPagePermsClause(1);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$pid: ...
	 * @return	[type]		...
	 */
	function ext_tree($pid)	{
		$this->init(' AND '.$this->permsC());

			// Get stored tree structure:
		$this->stored = unserialize($this->BE_USER->uc['browseTrees']['browsePages']);

			// PM action:
		$PM = t3lib_div::intExplode('_',t3lib_div::_GP('PM'));

			// traverse mounts:
		$titleLen=intval($this->BE_USER->uc['titleLen']);
		$treeArr=array();


		$idx=0;
#		$pid=1;

			// Set first:
		$this->bank=$idx;
		$isOpen = $this->stored[$idx][$pid] || $this->expandFirst;

		$curIds = $this->ids;	// save ids
		$this->reset();
		$this->ids = $curIds;

			// Set PM icon:
		$cmd=$this->bank.'_'.($isOpen?'0_':'1_').$pid;
		$icon='<img src="'.$this->backPath.'t3lib/gfx/ol/'.($isOpen?'minus':'plus').'only.gif" width="18" height="16" align="top" border="0" alt="" /></a>';
		$firstHtml= $this->PM_ATagWrap($icon,$cmd);

		if ($pid>0)	{
			$rootRec=t3lib_befunc::getRecord('pages',$pid);
			$firstHtml.=$this->wrapIcon('<img src="'.$this->backPath.t3lib_iconWorks::getIcon('pages',$rootRec).'" width="18" height="16" align="top" alt="" />',$rootRec);
		} else {
			$rootRec=array(
				'title'=>$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
				'uid'=>0
			);
			$firstHtml.=$this->wrapIcon('<img src="'.$this->backPath.'gfx/i/_icon_website.gif" width="18" height="16" align="top" alt="" />',$rootRec);
		}
		$this->tree[]=array('HTML'=>$firstHtml,'row'=>$rootRec);
		if ($isOpen)	{
				// Set depth:
			$depthD='<img src="'.$this->backPath.'t3lib/gfx/ol/blank.gif" width="18" height="16" align="top" alt="" />';
			if ($this->addSelfId)	$this->ids[] = $pid;
			$this->getTree($pid,999,$depthD);

			$idH=array();
			$idH[$pid]['uid']=$pid;
			if (count($this->buffer_idH))	$idH[$pid]['subrow']=$this->buffer_idH;
			$this->buffer_idH=$idH;

		}

			// Add tree:
		$treeArr=array_merge($treeArr,$this->tree);

		return $treeArr;
	}
}














/**
 * Main script class for the Import / Export facility
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_impexp
 */
class SC_mod_tools_log_index extends t3lib_SCbase {

	var $pageinfo;			// array containing the current page.

	/**
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->checkExtObj();

		$this->doc = t3lib_div::makeInstance('mediumDoc');
		$this->doc->backPath = $BACK_PATH;

				// JavaScript
		$this->doc->JScode = $this->doc->wrapScriptTags('
			script_ended = 0;
			function jumpToUrl(URL)	{	//
				document.location = URL;
			}
		');

		$this->doc->postCode=$this->doc->wrapScriptTags('
			script_ended = 1;
			if (top.fsMod) top.fsMod.recentIds["web"] = '.intval($this->id).';
		');
		$this->doc->form='<form action="index.php" method="post"><input type="hidden" name="id" value="'.$this->id.'" />';

		$this->content.=$this->doc->startPage($LANG->getLL('title'));
		$this->content.=$this->doc->header($LANG->getLL('title'));
		$this->content.=$this->doc->spacer(5);


		$inData = t3lib_div::_GP('tx_impexp');
		switch((string)$inData['action'])	{
			case 'export':
				$this->exportData($inData);
			break;
			case 'import':
				$this->importData($inData);
			break;
		}

		/**
		IMPORTING DATA:

		Incoming array has syntax:
			GETvar 'id' = import page id (must be readable)

			file = 	(pointing to filename relative to PATH_site)



			[all relation fields are clear, but not files]
			- page-tree is written first
			- then remaining pages (to the root of import)
			- then all other records are written either to related included pages or if not found to import-root (should be a sysFolder in most cases)
			- then all internal relations are set and non-existing relations removed, relations to static tables preserved.
		**/

		/**
		EXPORTING DATA:

		Incoming array has syntax:

				record[]=table:uid,,,,

				FUTURE: list[]=table,,,,:pid,,,

				pagetree[id] = (single id)
				pagetree[levels]=1,2,3, -1=currently unpacked tree.
				pagetree[tables][]=table/_ALL

				external_ref[tables][]=table/_ALL


		EXAMPLE for using the impexp-class for exporting stuff:

				// Create and initialize:
			$this->export = t3lib_div::makeInstance('tx_impexp');
			$this->export->init();
				// Set which tables relations we will allow:
			$this->export->relExclTables[]='tt_news';	// excludes
			$this->export->relOnlyTables[]="tt_news";	// exclusively includes. See comment in the class

				// Adding records:
			$this->export->export_addRecord("pages",$this->pageinfo);
			$this->export->export_addRecord("pages",t3lib_BEfunc::getRecord("pages",38));
			$this->export->export_addRecord("pages",t3lib_BEfunc::getRecord("pages",39));
			$this->export->export_addRecord("tt_content",t3lib_BEfunc::getRecord("tt_content",12));
			$this->export->export_addRecord("tt_content",t3lib_BEfunc::getRecord("tt_content",74));
			$this->export->export_addRecord("sys_template",t3lib_BEfunc::getRecord("sys_template",20));

				// Adding all the relations (recursively so relations has THEIR relations registered as well)
			for($a=0;$a<5;$a++)	{
				$addR = $this->export->export_addDBRelations($a);
				if (!count($addR)) break;
	#				debug("ADDED: ".count($addR),1);
			}

				// Finally load all the files.
			$this->export->export_addFilesFromRelations();	// MUST be after the DBrelations are set so that file from ALL added records are included!

				// Not the internal DAT array is ready to export:
			#debug($this->export->dat);

				// Write export
			$out = $this->export->compileMemoryToFileContent();
			#t3lib_div::writeFile(PATH_site."fileadmin/relations.trd",$out);
			#debug(strlen($out));
		**/














		if ($BE_USER->mayMakeShortcut())	{
			$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('tx_impexp','',$this->MCONF['name']));
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function printContent()	{

		$this->content.=$this->doc->spacer(20);
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}




	/**
	 *
	 * EXPORT FUNCTIONS
	 *
	 */

	/**
	 * @param	[type]		$inData: ...
	 * @return	[type]		...
	 */
	function exportData($inData)	{
		global $TCA;

		$this->export = t3lib_div::makeInstance('tx_impexp');
		$this->export->init($inData['dont_compress']);
		$this->export->relExclTables=array();
		$this->export->relOnlyTables=array();

		if (is_array($inData['external_ref']['tables']))	{
			reset($TCA);
			while(list($table)=each($TCA))	{
				if (in_array($table,$inData['external_ref']['tables']) || in_array('_ALL',$inData['external_ref']['tables']))	{
					if ($GLOBALS['BE_USER']->check('tables_select',$table))	{
						$this->export->relOnlyTables[]=$table;
					}
				}
			}
		}

			// Records
		if (is_array($inData['record']))	{
			reset($inData['record']);
			while(list(,$ref)=each($inData['record']))	{
				$rParts = explode(':',$ref);
				$tName=$rParts[0];
				$uidList=t3lib_div::trimExplode(',',$rParts[1],1);
				reset($uidList);
				while(list(,$rUid)=each($uidList))	{
					$this->export->export_addRecord($rParts[0],t3lib_BEfunc::getRecord($tName,$rUid));
				}
			}
		}

			// Pagetree
		if (is_array($inData['pagetree']))	{
			if ($inData['pagetree']['levels']<0)	{	// Based on click-expandable tree
				$pagetree = t3lib_div::makeInstance('localPageTree');
				$tree = $pagetree->ext_tree($inData['pagetree']['id']);
				$this->treeHTML = $pagetree->printTree($tree);

				$idH=$pagetree->buffer_idH;
#				debug($pagetree->buffer_idH);
			} else {	// Based on depth
					// Drawing tree:
				$sPage = t3lib_BEfunc::getRecord ('pages',$inData['pagetree']['id'],'*',' AND '.$this->perms_clause);
				if (is_array($sPage))	{
					$pid = $inData['pagetree']['id'];
					$tree = t3lib_div::makeInstance('t3lib_pageTree');
					$tree->init('AND '.$this->perms_clause);

					$HTML='<img src="'.$GLOBALS['BACK_PATH'].t3lib_iconWorks::getIcon('pages',$sPage).'" width="18" height="16" align="top" alt="" />';
					$tree->tree[]=Array('row'=>$sPage,'HTML'=>$HTML);
					$tree->buffer_idH=array();
					if ($inData['pagetree']['levels']>0)	$tree->getTree($pid,$inData['pagetree']['levels'],'');

					$idH=array();
					$idH[$pid]['uid']=$pid;
					if (count($tree->buffer_idH))	$idH[$pid]['subrow']=$tree->buffer_idH;


					$pagetree = t3lib_div::makeInstance('localPageTree');
					$this->treeHTML = $pagetree->printTree($tree->tree);

#					debug($idH);
				}
			}
				// In any case we should have a multi-level array, $idH, with the page structure here (and the HTML-code loaded into memory for nice display...)
			if (is_array($idH))	{
				$flatList = $this->export->setPageTree($idH);	// Sets the pagetree and gets a 1-dim array in return with the pages (in correct submission order BTW...)
				reset($flatList);
				while(list($k)=each($flatList))	{
					$this->export->export_addRecord('pages',t3lib_BEfunc::getRecord('pages',$k));

					if (is_array($inData['pagetree']['tables']))	{
						reset($TCA);
						while(list($table)=each($TCA))	{
							if ($table!='pages' && (in_array($table,$inData['pagetree']['tables']) || in_array('_ALL',$inData['pagetree']['tables'])))	{
								if ($GLOBALS['BE_USER']->check('tables_select',$table))	{
									$res = $this->exec_listQueryPid($table,$k);
									while($subTrow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
										$this->export->export_addRecord($table,$subTrow);
									}
								}
							}
						}
					}
				}
			}
		}


			// After adding ALL records we set relations:
#		debug($this->export->relOnlyTables);
		if (count($this->export->relOnlyTables))	{
			for($a=0;$a<10;$a++)	{
				$addR = $this->export->export_addDBRelations($a);
				if (!count($addR)) break;
	#				debug("ADDED: ".count($addR),1);
			}
		}

			// Finally files are added:
		$this->export->export_addFilesFromRelations();	// MUST be after the DBrelations are set so that files from ALL added records are included!

// Now, what's next?

		if ($inData['download_export'])	{
			$out = $this->export->compileMemoryToFileContent();
			$dlFile='T3D_'.substr(ereg_replace('[^[:alnum:]_]','-',$inData['download_export_name']),0,20).'_'.date('d-m-H-i-s').($this->export->doOutputCompress()?'-z':'').'.t3d';

			$mimeType = 'application/octet-stream';
			Header('Content-Type: '.$mimeType);
			Header('Content-Disposition: attachment; filename='.basename($dlFile));
			echo $out;
			exit;

			#debug(strlen($out));
		}




		$this->makeConfigurationForm($inData);


		$content=$this->export->displayContentOverview();
		$this->content.=$this->doc->section('Structure to be exported:',$content,0,1);


		$errors = $this->export->printErrorLog();
		if ($errors)	$this->content.=$this->doc->section('Messages:',$errors,0,1);


		/*
		$this->export->setMetaData('My export of data','My description
with linebreaks

.. andmore ','This is a note',
			$GLOBALS["BE_USER"]->user["username"],
			$GLOBALS["BE_USER"]->user["realName"],
			$GLOBALS["BE_USER"]->user["email"]);

		$this->export->addThumbnail(PATH_site."t3lib/gfx/typo3logo.gif");
		*/
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$pid: ...
	 * @return	[type]		...
	 */
	function exec_listQueryPid($table,$pid)	{
		global $TCA;
		$orderBy = $TCA[$table]['ctrl']['sortby'] ? 'ORDER BY '.$TCA[$table]['ctrl']['sortby'] : $TCA[$table]['ctrl']['default_sortby'];
		return $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'pid='.intval($pid).t3lib_BEfunc::deleteClause($table), '', $GLOBALS['TYPO3_DB']->stripOrderBy($orderBy));
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$inData: ...
	 * @return	[type]		...
	 */
	function makeConfigurationForm($inData)	{
		$nameSuggestion	='';
		if (is_array($inData['pagetree']) && $this->treeHTML)	{
			$row=array();

			$nameSuggestion.='tree_PID'.$inData['pagetree']['id'].'_L'.$inData['pagetree']['levels'];
			$row[]='<tr class="bgColor5">
					<td colspan=2><strong>Export pagetree configuration:</strong></td>
				</tr>';

			$row[]='<tr class="bgColor4">
					<td><strong>Page ID:</strong></td>
					<td>'.htmlspecialchars($inData['pagetree']['id']).'<input type="hidden" value="'.htmlspecialchars($inData['pagetree']['id']).'" name="tx_impexp[pagetree][id]" /></td>
				</tr>';

			$row[]='<tr class="bgColor4">
				<td><strong>Tree:</strong></td>
				<td>'.$this->treeHTML.'</td>
				</tr>';

			$opt = array(
				'-1' => 'Expanded tree',
				'0' => 'Only this page',
				'1' => '1 level',
				'2' => '2 levels',
				'3' => '3 levels',
				'4' => '4 levels',
				'999' => 'Infinite'
			);
			$row[]='<tr class="bgColor4">
				<td><strong>Levels:</strong></td>
				<td>'.$this->renderSelectBox('tx_impexp[pagetree][levels]',$inData['pagetree']['levels'],$opt).'</td>
				</tr>';

			$row[]='<tr class="bgColor4">
				<td><strong>Include tables:</strong></td>
				<td>'.$this->tableSelector('tx_impexp[pagetree][tables]',$inData['pagetree']['tables'],'pages').'</td>
				</tr>';
			$content.='<table border=0 cellpadding=1 cellspacing=1>'.implode('',$row).'</table>';
		}



		if (is_array($inData['record']))	{
			$row[]='<tr class="bgColor5">
					<td colspan=2><strong>Export single record:</strong></td>
				</tr>';
			reset($inData['record']);
			while(list(,$ref)=each($inData['record']))	{
				$rParts = explode(':',$ref);
				$tName=$rParts[0];
				$uidList=t3lib_div::trimExplode(',',$rParts[1],1);
				reset($uidList);
				while(list(,$rUid)=each($uidList))	{
					$nameSuggestion.=$tName.'_'.$rUid;
					$rec = t3lib_BEfunc::getRecord($tName,$rUid);

					$row[]='<tr class="bgColor4">
						<td><strong>Record:</strong></td>
						<td>'.t3lib_iconworks::getIconImage($tName,$rec,$GLOBALS['BACK_PATH'],' align="top"').t3lib_BEfunc::getRecordTitle($tName,$rec,1).'<input type="hidden" name="tx_impexp[record][]" value="'.htmlspecialchars($tName.':'.$rUid).'" /></td>
						</tr>';
				}
			}
			$content.='<table border=0 cellpadding=1 cellspacing=1>'.implode('',$row).'</table>';
		}



		$content.='Include relations to tables:<br />'.$this->tableSelector('tx_impexp[external_ref][tables]',$inData['external_ref']['tables']);
		$content.='<hr /><input type="submit" value="Update" /> - <input type="submit" value="Download export" name="tx_impexp[download_export]" />';
		if ($this->export->compress) $content.='<input type="checkbox" name="tx_impexp[dont_compress]" value="1"'.($inData['dont_compress']?' checked="checked"':'').' />Don\'t compress';
		$content.='<input type="hidden" name="tx_impexp[download_export_name]" value="'.$nameSuggestion.'" />';

		$content.='<input type="hidden" name="tx_impexp[action]" value="export" />';
		$this->content.=$this->doc->section('Export to TYPO3 Document (.t3d)',$content,0,1);
	}







	/**
	 *
	 * IMPORT FUNCTIONS
	 *
	 */

	/**
	 * @param	[type]		$inData: ...
	 * @return	[type]		...
	 */
	function importData($inData)	{
		global $TCA,$LANG;


		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))	{
			if ($BE_USER->user['admin'] && !$this->id)	{
				$this->pageinfo=array('title' => '[root-level]','uid'=>0,'pid'=>0);
			}

			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.path').': '.t3lib_div::fixed_lgd_cs($this->pageinfo['_thePath'],-50);
			$this->content.=$this->doc->section('',$headerSection);

			$import = t3lib_div::makeInstance('tx_impexp');
			$import->init();

			$row=array();

				// User temp files:
			$tempFolder = $this->userTempFolder();
			if ($tempFolder)	{
				$row[]='<tr class="bgColor5">
						<td colspan=2><strong>Upload file:</strong></td>
					</tr>';
				$row[]='<tr class="bgColor5">
						<td colspan=2>

							<!--
								Form, for uploading files:
							-->
							</form>
							<form action="'.$GLOBALS['BACK_PATH'].'tce_file.php" method="post" name="editform" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'">
								<input type="file" name="upload_1"'.$this->doc->formWidth(35).' size="50" />
								<input type="hidden" name="file[upload][1][target]" value="'.htmlspecialchars($tempFolder).'" />
								<input type="hidden" name="file[upload][1][data]" value="1" /><br />

								<input type="hidden" name="redirect" value="'.htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI')).'" />
								<input type="submit" name="submit" value="'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:file_upload.php.submit',1).'" />
								<input type="checkbox" name="overwriteExistingFiles" value="1" checked="checked" /> '.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.php:overwriteExistingFiles',1).'
							</form>
							'.$this->doc->form.'
						</td>
					</tr>';

				$temp_filesInDir = t3lib_div::getFilesInDir($tempFolder,'t3d',1,1);
			}


				// Make input selector:
			$path = 'fileadmin/';	// must have trailing slash.
			$filesInDir = t3lib_div::getFilesInDir(PATH_site.$path,'t3d',1,1);

			if (is_array($temp_filesInDir))	{
				if (is_array($filesInDir))	{
					$filesInDir = array_merge($temp_filesInDir, $filesInDir);
				} else {
					$filesInDir = $temp_filesInDir;
				}
			}

			$row[]='<tr class="bgColor5">
					<td colspan=2><strong>Import file:</strong></td>
				</tr>';

			$opt = array('');
			if (is_array($filesInDir))	{
				while(list(,$file)=each($filesInDir))	{
#					$file=$path.$file;
					$opt[$file]= substr($file,strlen(PATH_site));
				}
			}

			$row[]='<tr class="bgColor4">
				<td><strong>File:</strong></td>
				<td>'.$this->renderSelectBox('tx_impexp[file]',$inData['file'],$opt).'<br />(From path: '.$path.')'.
				(!$import->compress ? '<br /><span class="typo3-red">NOTE: No decompressor available for compressed files!</span>':'').
				'</td>
				</tr>';

/*			if ($this->pageinfo['doktype']!=254)	{
				$row[]='<tr class="bgColor4">
					<td><strong>Warning:</strong></td>
					<td>'.$GLOBALS["TBE_TEMPLATE"]->rfw('If you import into a page which is not a sysFolder you may experience a partial import. If you are in doubt you should import into a sysFolder.').'</td>
					</tr>';
			}
	*/
/*			$row[]='<tr class="bgColor4">
				<td><strong>Include tables:</strong></td>
				<td>'.$this->tableSelector("tx_impexp[pagetree][tables]",$inData["pagetree"]["tables"],"pages").'</td>
				</tr>';
	*/
			$content.='<table border=0 cellpadding=1 cellspacing=1>'.implode('',$row).'</table>';

			$content.='<hr /><input type="submit" value="Preview" />';
			if (!$inData['import_file'])	{
				$content.=' - <input type="submit" value="Import" name="tx_impexp[import_file]" />';
			}
			$content.='<input type="hidden" name="tx_impexp[action]" value="import" />';

			$this->content.=$this->doc->section('Import TYPO3 Document (.t3d)',$content,0,1);



			$inFile = t3lib_div::getFileAbsFileName($inData['file']);
			if ($inFile && @is_file($inFile))	{
				$import->loadFile($inFile,1);

#				debug($import->dat['header']);
				if ($inData['import_file'])	{
					$import->importData($this->id);
					t3lib_BEfunc::getSetUpdateSignal('updatePageTree');
				}

				$import->display_import_pid_record=$this->pageinfo;
				$content=$import->displayContentOverview(1);
				$this->content.=$this->doc->section($inData['import_file']?'Structure has been imported:':'Structure to be imported:',$content,0,1);

				$errors = $import->printErrorLog();
				if ($errors)	$this->content.=$this->doc->section('Messages:',$errors,0,1);
			}
		}

	}

	/**
	 * Returns a selector-box with tables
	 *
	 * @param	[type]		$prefix: ...
	 * @param	[type]		$value: ...
	 * @param	[type]		$excludeList: ...
	 * @return	[type]		...
	 */
	function tableSelector($prefix,$value,$excludeList='')	{
		global $TCA;
		reset($TCA);
		$optValues = array();

		if (!t3lib_div::inList($excludeList,'_ALL'))					$optValues['_ALL']='[ALL tables]';

		while(list($table)=each($TCA))	{
			if ($GLOBALS['BE_USER']->check('tables_select',$table) && !t3lib_div::inList($excludeList,$table))	{
				$optValues[$table]=$table;
			}
		}


			// make box:
		$opt=array();
		$opt[]='<option value=""></option>';
		reset($optValues);
		while(list($k,$v)=each($optValues))	{
			if (is_array($value))	$sel = (in_array($k,$value)?' selected="selected"':'');
			$opt[]='<option value="'.htmlspecialchars($k).'"'.$sel.'>'.htmlspecialchars($v).'</option>';
		}
		return '<select name="'.$prefix.'[]" multiple size="'.t3lib_div::intInRange(count($opt),5,10).'">'.implode('',$opt).'</select>';
	}

	/**
	 * Makes a selector-box from optValues
	 *
	 * @param	[type]		$prefix: ...
	 * @param	[type]		$value: ...
	 * @param	[type]		$optValues: ...
	 * @return	[type]		...
	 */
	function renderSelectBox($prefix,$value,$optValues)	{
		$opt=array();
		$isSelFlag=0;
		reset($optValues);
		while(list($k,$v)=each($optValues))	{
			$sel = (!strcmp($k,$value)?' selected="selected"':'');
			if ($sel)	$isSelFlag++;
			$opt[]='<option value="'.htmlspecialchars($k).'"'.$sel.'>'.htmlspecialchars($v).'</option>';
		}
		if (!$isSelFlag && strcmp('',$value))	$opt[]='<option value="'.$value.'" selected="selected">'.htmlspecialchars("['".$value."']").'</option>';
		return '<select name="'.$prefix.'">'.implode('',$opt).'</select>';
	}




	/**
	 * Returns first temporary folder of the user account (from $FILEMOUNTS)
	 *
	 * @return	string		Absolute path to first "_temp_" folder of the current user, otherwise blank.
	 */
	function userTempFolder()	{
		global $FILEMOUNTS;

		foreach($FILEMOUNTS as $filePathInfo)	{
			$tempFolder = $filePathInfo['path'].'_temp_/';
			if (@is_dir($tempFolder))	{
				return $tempFolder;
			}
		}
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/impexp/app/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/impexp/app/index.php']);
}












// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_tools_log_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
