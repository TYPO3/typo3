<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
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
 * Export data to files
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */


class t3lib_exportData {
	var $itemArray = Array();
	var $results = Array();
	var $files=Array();

	var $script="";
	var $id="";
	var $backPath="";

		// Internal
	var $recordsToLoad_fields=array();

	function startExport($data,$allowedTables="*")	{
		global $TCA;
		if ($allowedTables=="*")	{
			reset($TCA);
			$allowedTables="";
			while(list($table)=each($TCA))	{
				$allowedTables.=$table.",";
			}
		}
		
		if (is_array($data))	{
			$list="";
			reset ($data);
			while (list($table,$records) = each($data))	{
				while(list(,$uid)=each($records))	{
					$list.=$table."_".$uid.",";
				}
			}
		} else {
			$list=$data;
		}

//		debug($list);
		$templates = t3lib_div::makeInstance("t3lib_loadDBGroup");
		$templates->fromTC=0;
		$templates->start($list,$allowedTables);		// Need support for MM relations
		$templates->getFromDB();

		$this->itemArray = $templates->itemArray;
		$this->results = $templates->results;
		$this->files=Array();
		
		$this->getReferences();
		$this->buildExport();

//					debug($this->itemArray);
//					debug($this->results);
//					debug($this->files);

	}
	function buildExport()	{
		// Gemme hash af tc-array
		if (is_array($this->results))	{
			while(list($table,$records)=each($this->results))	{
				if (is_array($this->results[$table]))	{
					reset($this->results[$table]);
					while(list($key,$content)=each($this->results[$table]))	{
						while(list($k,$v)=each($content))	{
							if (t3lib_div::testInt($k))	{
								unset($this->results[$table][$key][$k]);
							}
						}
					}
				}
			}					
		}
	}
	function getReferences()	{
		global $TCA;
		reset($this->itemArray);
		while(list($counter,$item)=each($this->itemArray))	{
			t3lib_div::loadTCA($item["table"]);
			if (is_array($TCA[$item["table"]]["columns"]))	{
				while(list($key,$content)=each($TCA[$item["table"]]["columns"]))	{
					$data = $this->results[$item[table]][$item[id]][$key];
					if ($data)	{
						switch($content[config][type])	{
							case "group":
								switch ($content[config][internal_type])	{
									case "file":
										$temp = explode(",",$data);
										while (list($somekey,$someval)=each($temp))	{
											$fileRef = PATH_site.$content[config][uploadfolder]."/".$someval;	// Need support for MM relations
											if ($someval && @is_file($fileRef))	{
												$fileC = t3lib_div::getURL($fileRef);
												$this->files[$item[table].":".$item[id]][$key][$someval]=Array(md5($fileC), $fileC);
												$this->itemArray[$counter][files][$key][]=$someval;
											}
										}
									break;
									case "db":
										$loadDB = t3lib_div::makeInstance("t3lib_loadDBGroup");
										$loadDB->fromTC=0;
										$loadDB->start($data, $content[config][allowed]);	// Need support for MM relations
										$loadDB->getFromDB();
										$this->results = array_merge_recursive($this->results,$loadDB->results);		// !!!! Don't use array_merge_recursive!?
										$this->itemArray[$counter][records][$key]=$loadDB->itemArray;
									break;
								}
							break;
							case "select":
								if ($TCA[$content[config][foreign_table]])	{
									$query = t3lib_BEfunc::foreign_table_where_query($content);
		
									$subres = mysql(TYPO3_db,$query);
									$tempArr = Array();
									while ($subrow = mysql_fetch_assoc($subres))	{
										$tempArr[$subrow[uid]] = t3lib_BEfunc::getRecordTitle($content["config"]["foreign_table"],$subrow);
									}
									$temp = explode(",",$data);
									while (list(,$someval)=each($temp))	{
										if (isset($tempArr[$someval]))	{
//											debug($someval);
										}
									}
								}
							break;
						}
					}
				}
			}
		}
	}
	function write($ver,$file)	{
		switch($ver)	{
			case 1:
				if (@is_dir(dirname($file)))	{
					if($fd = fopen($file,"wb"))	{
						$preHeader=str_pad("Typo Record Document Ver. 1", 100);
						fwrite($fd, $preHeader.serialize(array("itemArray"=>$this->itemArray, "results"=>$this->results, "files"=>$this->files)));
						fclose($fd);
					}
				} else {echo "File not written: unwriteable directory.";}
			break;
		}
	}
	
	
	
	function startImport($file)	{
		if (@is_file($file))	{
			if($fd = fopen($file,"rb"))	{
				$header=fread($fd, 100);
				$version = intval(ereg_replace(".*Ver\.","",$header));
				$this->importHeader = $header;
				$this->importVersion = $version; 
				switch($version)	{
					case 1:
						$content = "";
						while (!feof($fd))	{
							$content.=fread($fd, 5000);
						}
						fclose($fd);
						$this->importData = unserialize($content);
					break;
				}
			}
		}
	}
	function createImport($tempPath,$pid)	{
		global $TCA;
		if (is_array($this->importData["itemArray"]) && @is_dir($tempPath))	{
			$fileProcessor = t3lib_div::makeInstance("t3lib_basicFileFunctions");
			$fileReg=array();
			$tempdir = $tempPath."/".md5(uniqid(""));
			mkdir($tempdir,0777);
			
			if (@is_dir($tempdir))	{
				reset($this->importData["itemArray"]);
				while(list(,$rec)=each($this->importData["itemArray"]))	{
					if ($TCA[$rec["table"]] && is_array($this->importData["results"][$rec[table]][$rec[id]]))	{
						t3lib_div::loadTCA($rec["table"]);
						$ID = uniqid("NEW");
						while(list($field,$value)=each($this->importData["results"][$rec[table]][$rec[id]]))	{
							if ($TCA[$rec["table"]]["columns"][$field])	{


/*								$fType = $TCA[$rec[table]][columns][$field][config][type];
								switch($fType)	{
									case "group":
										switch ($TCA[$rec[table]][columns][$field][config][internal_type])	{
											case "file":
												$temp = explode(",",$data);
												while (list($somekey,$someval)=each($temp))	{
													$fileRef = PATH_site.$content[config][uploadfolder]."/".$someval;
													if ($someval && @is_file($fileRef))	{
														$fileC = t3lib_div::getURL($fileRef);
														$this->files[$item[table].":".$item[id]][$key][]=Array($someval, md5($fileC), $fileC);
														$this->itemArray[$counter][files][$key][]=$content[config][uploadfolder]."/".$someval;
													}
												}
											break;
											case "db":
												$loadDB = t3lib_div::makeInstance("t3lib_loadDBGroup");
												$loadDB->fromTC=0;
												$loadDB->start($data, $content[config][allowed]);
												$loadDB->getFromDB();
												$this->results = array_merge_recursive($this->results,$loadDB->results);
												$this->itemArray[$counter][records][$key]=$loadDB->itemArray;
											break;
										}
									break;
									case "select":
										$data[$rec[table]][$ID][$field] = addslashes($value);
									break;
									default:
										$data[$rec[table]][$ID][$field] = addslashes($value);
									break;
								}*/

								$data[$rec[table]][$ID][$field] = addslashes($value);
							}
						}
						if (is_array($rec[files]))	{
							reset($rec[files]);
							while(list($field,$fileArr)=each($rec[files]))	{
								$fieldContent=Array();
								while(list($counter,$fileRef)=each($fileArr))	{	
									$theFileData = $this->importData["files"][$rec[table].":".$rec[id]][$field][$fileRef];
									if (is_array($theFileData))	{
										$theNewName = $fileProcessor->getUniqueName($fileRef, $tempdir);
										if($fd = fopen($theNewName,"wb"))	{
											fwrite($fd, $theFileData[1]);
											fclose($fd);
											$fileReg[]=$theNewName;
											$fieldContent[]=$theNewName;
										}
									}		
								}
								$data[$rec[table]][$ID][$field] = addslashes(implode($fieldContent,","));
							}
						}
						$data[$rec[table]][$ID][pid] = $pid;
					}
				}

//				debug($data);
				$tce = t3lib_div::makeInstance("t3lib_TCEmain");

				$tce->start($data,Array());
				$tce->process_datamap();
				debug($tce->substNEWwithIDs);
				$tce->clear_cacheCmd($cacheCmd);

					// Delete temp-files and folder
				reset($fileReg);
				while(list(,$file)=each($fileReg))	{
					if (@is_file($file) && $tempdir && substr($file,0,strlen($tempdir))==$tempdir)	{
						unlink($file);
					}
				}
				debug($tempdir);
				rmdir($tempdir);
			}
		}
	}
	function getSource($source)		{
		$output="";
		switch ($source)	{
			case "clipboard":
					// Clipboard
				$output.= '<table border=0 cellpadding=0 cellspacing=0 width=460>';
				$output.= '<tr><td bgColor="'.$GLOBALS["SOBE"]->doc->bgColor5.'"><b>'.fw('CLIPBOARD').'</b></td></tr>';
				if (is_array($GLOBALS["HTTP_GET_VARS"][data]))	{
					reset($GLOBALS["HTTP_GET_VARS"][data]);
					$item_arr=array();
					$table_arr=array();
					while(list($table,$records)=each($GLOBALS["HTTP_GET_VARS"][data]))	{
						$table_arr[]=$table;
						while(list(,$uid)=each($records))	{
							$item_arr[]=$table."_".$uid;
						}
					}
					$fetchedRecords = t3lib_div::makeInstance("t3lib_loadDBGroup");
					$fetchedRecords->start(implode($item_arr,","),implode($table_arr,","));	// Need support for MM relations...
					$fetchedRecords->getFromDB();
					
					$outCode=array();
					if (is_array($fetchedRecords->results))	{
						reset($fetchedRecords->results);
						while(list($table,$records)=each($fetchedRecords->results))	{
							$titleCol = $GLOBALS["TCA"][$table]["ctrl"]["label"];
							while(list(,$row)=each($records))	{
								$code = $row[$titleCol];
								if (!$code) {$code="<i>[".$GLOBALS["LANG"]->php3Lang[labels][no_title]."]</i>";}
								$code="&nbsp;".$code;
								$outCode[]='<img src="'.$this->backPath.t3lib_iconWorks::getIcon($table).'" width=18 height=16 align=top>'.t3lib_div::fixed_lgd($code,40);
								$this->recordsToLoad_fields[]=$table."_".$row[uid];
							}
						}
					}	
					$output.= '<tr><td>'.fw(implode($outCode,"<BR>")).'</td></tr>';
				}

				$output.= '<tr><td>&nbsp;</td></tr>';
				$output.= '<tr><td bgColor="'.$GLOBALS["SOBE"]->doc->bgColor4.'"><b>'.fw('<a href="#" onClick="readClipboard(\''.($this->script.'?id='.$this->id.'&source=clipboard').'\');">Click here to reload clipboard!</a>').'</b></td></tr>';
				$output.= '</table><BR>';
				
				if ($GLOBALS["HTTP_GET_VARS"]["SET"]["export_source"]=="clipboard" && !isset($GLOBALS["HTTP_GET_VARS"]["data"]))	{
					$output='<script language="javascript" type="text/javascript">readClipboard(\''.($this->script.'?id='.$this->id.'&source=clipboard').'\');</script>';
				}
			break;
		}
		return $output;
	}
}



if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["t3lib/class.t3lib_exportdata.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["t3lib/class.t3lib_exportdata.php"]);
}


?>	