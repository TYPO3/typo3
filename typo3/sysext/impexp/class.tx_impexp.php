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
 * TRD file Import/Export library (TYPO3 Record Document)
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   98: class tx_impexp
 *  124:     function init($dontCompress=0)
 *  140:     function setMetaData($title,$description,$notes,$packager_username,$packager_name,$packager_email)
 *  157:     function addThumbnail($imgFilepath)
 *  179:     function setPageTree($idH)
 *  191:     function flatInversePageTree($idH,$a=array())
 *  212:     function export_addRecord($table,$row,$relationLevel=0)
 *  247:     function export_addDBRelations($relationLevel=0)
 *  303:     function export_addFilesFromRelations()
 *  350:     function getRelations($table,$row)
 *  423:     function flatDBrels($dbrels)
 *  445:     function loadContent($filecontent)
 *  462:     function getNextContentPart($filecontent,&$pointer,$unserialize=0,$name='')
 *  489:     function loadFile($filename,$all=0)
 *  510:     function getNextFilePart($fd,$unserialize=0,$name='')
 *  535:     function compileMemoryToFileContent()
 *  556:     function doOutputCompress()
 *  567:     function addFilePart($data,$compress=0)
 *  578:     function error($msg)
 *  587:     function printErrorLog()
 *  597:     function destPathFromUploadFolder ($folder)
 *  616:     function importData($pid)
 *  720:     function getNewTCE()
 *  734:     function setRelations()
 *  785:     function addToMapId($substNEWwithIDs)
 *  804:     function initImportVars()
 *  815:     function unlinkTempFiles()
 *  838:     function addSingle($table,$uid,$pid)
 *  878:     function import_addFileNameToBeCopied($fI)
 *  902:     function getTempPathFileName($fN)
 *  923:     function displayContentOverview	()
 *  993:     function traversePageTree($pT,&$lines,$preCode='')
 * 1022:     function traversePageRecords($pT,&$lines)
 * 1049:     function traverseallrecords($pT,&$lines)
 * 1071:     function singleRecordLines($table,$uid,&$lines,$preCode,$checkImportInPidRecord=0)
 * 1138:     function addRelations($rels,&$lines,$preCode,$recurCheck=array())
 * 1180:     function checkDokType($checkTable,$doktype)
 *
 * TOTAL FUNCTIONS: 36
 * (This index is automatically created/updated by the extension 'extdeveval")
 *
 */

require_once(PATH_t3lib.'class.t3lib_tcemain.php');










/**
 * TRD file Import/Export library (TYPO3 Record Document)
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_impexp
 */
class tx_impexp {
	var $maxFileSize = 1000000;		// 1MB max file size
	var $maxRecordSize = 1000000;	// 1MB max record size
	var $maxExportSize = 10000000;	// 10MB max export size

	var $dat = array();
	var $display_import_pid_record='';		// If set to a page-record, then the preview display of the content will expect this page-record to be the target for the import and accordingly display validation information.



		// Internal, dynamic:
	var $import_mapId = array();
	var $import_newId = array();
	var $import_newId_pids = array();
	var $errorLog = array();

	var $relExclTables = array();	// add table names here which will not be included into export if found as relations.
	var $relOnlyTables = array();	// add table names here which are THE ONLY ones which will be included into export if found as relations. (activated if array is not empty)

	var $compress=0;
	var $dontCompress=0;


	/**
	 * Init
	 *
	 * @param	[type]		$dontCompress: ...
	 * @return	[type]		...
	 */
	function init($dontCompress=0)	{
		$this->compress = function_exists('gzcompress');
		$this->dontCompress = $dontCompress;
	}

	/**
	 * Sets meta data
	 *
	 * @param	[type]		$title: ...
	 * @param	[type]		$description: ...
	 * @param	[type]		$notes: ...
	 * @param	[type]		$packager_username: ...
	 * @param	[type]		$packager_name: ...
	 * @param	[type]		$packager_email: ...
	 * @return	[type]		...
	 */
	function setMetaData($title,$description,$notes,$packager_username,$packager_name,$packager_email)	{
		$this->dat['header']['meta']=array(
			'title'=>$title,
			'description'=>$description,
			'notes'=>$notes,
			'packager_username'=>$packager_username,
			'packager_name'=>$packager_name,
			'packager_email'=>$packager_email
		);
	}

	/**
	 * Sets a thumbnail image to the exported file
	 *
	 * @param	[type]		$imgFilepath: ...
	 * @return	[type]		...
	 */
	function addThumbnail($imgFilepath)	{
		if (@is_file($imgFilepath))	{
			$imgInfo = @getimagesize($imgFilepath);
			if (is_array($imgInfo))	{
				$fileContent = t3lib_div::getUrl($imgFilepath);
				$this->dat['header']['thumbnail']=array(
					'imgInfo' => $imgInfo,
					'content' => $fileContent,
					'filesize' => strlen($fileContent),
					'filemtime' => filemtime($imgFilepath),
					'filename' => basename($imgFilepath)
				);
			}
		}
	}

	/**
	 * Sets the page-tree array in the header and returns the array in a flattend version
	 *
	 * @param	[type]		$idH: ...
	 * @return	[type]		...
	 */
	function setPageTree($idH)	{
		$this->dat['header']['pagetree']=$idH;
		return $this->flatInversePageTree($idH);
	}

	/**
	 * Recursively flattening the idH array (for setPageTree() function)
	 *
	 * @param	[type]		$idH: ...
	 * @param	[type]		$a: ...
	 * @return	[type]		...
	 */
	function flatInversePageTree($idH,$a=array())	{
		if (is_array($idH))	{
			$idH = array_reverse($idH);
			reset($idH);
			while(list($k,$v)=each($idH))	{
				$a[$v['uid']]=$v['uid'];
				if (is_array($v['subrow']))	$a=$this->flatInversePageTree($v['subrow'],$a);
			}
		}
		return $a;
	}

	/**
	 * Adds the record $row from $table.
	 * No checking for relations done here. Pure data.
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$row: ...
	 * @param	[type]		$relationLevel: ...
	 * @return	[type]		...
	 */
	function export_addRecord($table,$row,$relationLevel=0)	{
		if (strcmp($table,'') && is_array($row) && $row['uid']>0)	{
			if (!isset($this->dat['records'][$table.':'.$row['uid']]))	{
					// header info:
				$headerInfo=array();
				$headerInfo['uid']=$row['uid'];
				$headerInfo['pid']=$row['pid'];
				$headerInfo['title']=t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($table,$row),40);
				$headerInfo['size']=strlen(serialize($row));
				if ($relationLevel)	$headerInfo['relationLevel'] = $relationLevel;
				if ($headerInfo['size']<$this->maxRecordSize)	{
					$this->dat['header']['records'][$table][$row['uid']]=$headerInfo;

						// pid lookup:
					$this->dat['header']['pid_lookup'][$row['pid']][$table][$row['uid']]=1;

						// data:
					$this->dat['records'][$table.':'.$row['uid']]=array();
					$this->dat['records'][$table.':'.$row['uid']]['data']=$row;
					$this->dat['records'][$table.':'.$row['uid']]['rels']=$this->getRelations($table,$row);

					$this->dat['header']['records'][$table][$row['uid']]['rels']=$this->flatDBrels($this->dat['records'][$table.':'.$row['uid']]['rels']);
				} else $this->error('Record '.$table.':'.$row['uid'].' was larger than maxRecordSize ('.t3lib_div::formatSize($this->maxRecordSize).')');
			} else $this->error('Record '.$table.':'.$row['uid'].' already added.');
		}
	}

	/**
	 * This analyses the existing added records, finds all database relations to records and adds these records to the export file.
	 * This function can be called recursively until it returns an empty array. In principle it should not allow to infinite recursivity, but you better set a limit...
	 * Call this BEFORE the ext_addFilesFromRelations
	 *
	 * @param	[type]		$relationLevel: ...
	 * @return	[type]		...
	 */
	function export_addDBRelations($relationLevel=0)	{
		global $TCA;
#echo "<HR>";
		$addR=array();
		if (is_array($this->dat['records']))	{
			reset($this->dat['records']);
			while(list($k)=each($this->dat['records']))	{
				if (is_array($this->dat['records'][$k]))	{
					reset($this->dat['records'][$k]['rels']);
					while(list($fieldname,$vR)=each($this->dat['records'][$k]['rels']))	{
						if ($vR['type']=='db')	{
							reset($vR['itemArray']);
							while(list(,$fI)=each($vR['itemArray']))	{
								$rId = $fI['table'].':'.$fI['id'];
								if (isset($TCA[$fI['table']]) && !$TCA[$fI['table']]['ctrl']['is_static']
										&& !in_array($fI['table'],$this->relExclTables)
										&& (!count($this->relOnlyTables) || in_array($fI['table'],$this->relOnlyTables))
										)	{
									if (isset($this->dat['records'][$rId]))	{
		#								debug($rId.": OK",1);
									} else {
		#								debug($rId.": --",1);
										$addR[$rId]=$fI;
									}
								}
							}
						}
					}
				}
			}
		} else $this->error('There were no records available.');

#debug($addR);
		if (count($addR))	{
			reset($addR);
			while(list(,$fI)=each($addR))	{
				$row = t3lib_BEfunc::getRecord($fI['table'],$fI['id']);
				if (is_array($row))	{
					$this->export_addRecord($fI['table'],$row,$relationLevel+1);
				}
				$rId = $fI['table'].':'.$fI['id'];
				if (!isset($this->dat['records'][$rId]))	{
					$this->dat['records'][$rId]='NOT_FOUND';
					$this->error('Relation record '.$rId.' was not found!');
				}
			}
		}
		return $addR;
	}

	/**
	 * This adds all files in relations.
	 * Call this method AFTER adding all records including relations.
	 *
	 * @return	[type]		...
	 */
	function export_addFilesFromRelations()	{
		if (is_array($this->dat['records']))	{
			reset($this->dat['records']);
			while(list($k)=each($this->dat['records']))	{
				if (is_array($this->dat['records'][$k]['rels']))	{
					reset($this->dat['records'][$k]['rels']);
					while(list($fieldname,$vR)=each($this->dat['records'][$k]['rels']))	{
						if ($vR['type']=='file')	{
							reset($vR['newValueFiles']);
							while(list(,$fI)=each($vR['newValueFiles']))	{
								if (@is_file($fI['ID_absFile']))	{
									if (filesize($fI['ID_absFile'])<$this->maxFileSize)	{
										$fileRec=array();
										$fileRec['filesize']=filesize($fI['ID_absFile']);
										$fileRec['filename']=basename($fI['ID_absFile']);
										$fileRec['filemtime']=filemtime($fI['ID_absFile']);
										$fileRec['record_ref']=$k.'/'.$fieldname;

											// Setting this data in the header
										$this->dat['header']['files'][$fI['ID']]=$fileRec;

											// ... and for the recordlisting, why not let us know WHICH relations there was...
										$refParts=explode(':',$k,2);
										if (!is_array($this->dat['header']['records'][$refParts[0]][$refParts[1]]['filerefs']))	$this->dat['header']['records'][$refParts[0]][$refParts[1]]['filerefs']=array();
										$this->dat['header']['records'][$refParts[0]][$refParts[1]]['filerefs'][]=$fI['ID'];

											// ... and finally add the heavy stuff:
										$fileRec['content']=t3lib_div::getUrl($fI['ID_absFile']);
										$fileRec['content_md5']=md5($fileRec['content']);
										$this->dat['files'][$fI['ID']] = $fileRec;
									} else  $this->error($fI['ID_absFile'].' was larger than the maxFileSize ('.t3lib_div::formatSize($this->maxFileSize).')! Skipping.');
								} else $this->error($fI['ID_absFile'].' was not a file! Skipping.');
							}
						}
					}
				}
			}
		} else $this->error('There were no records available.');
	}

	/**
	 * Returns relation information for a $table/$row-array
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
	function getRelations($table,$row)	{
		global $TCA;
		t3lib_div::loadTCA($table);
		$uid=$row['uid'];
		$nonFields = explode(',','uid,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody,pid');

		$outRow=array();
		reset($row);
		while (list($field,$value)=each($row))	{
			if (!in_array($field,$nonFields) && is_array($TCA[$table]['columns'][$field]))	{
				$conf = $TCA[$table]['columns'][$field]['config'];

					// Take care of files...
				if ($conf['type']=='group' && $conf['internal_type']=='file')	{
					if ($conf['MM'])	{
						$theFileValues=array();
						$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
						$dbAnalysis->start('','files',$conf['MM'],$uid);
						reset($dbAnalysis->itemArray);
						while (list($somekey,$someval)=each($dbAnalysis->itemArray))	{
//										debug($someval['id']);
							if ($someval['id'])	{
								$theFileValues[]=$someval['id'];
							}
						}
					} else {
						$theFileValues = explode(',',$value);
					}
//								debug($theFileValues);
					reset($theFileValues);
					$uploadFolder = $conf['uploadfolder'];
					$dest = $this->destPathFromUploadFolder($uploadFolder);
					$newValue = array();
					$newValueFiles = array();
					while (list(,$file)=each($theFileValues))	{
						if (trim($file))	{
							$realFile = $dest.'/'.trim($file);
							if (@is_file($realFile))	{
								$newValueFiles[] = array('fieldvalue'=>$file,'ID'=>md5($realFile),'ID_absFile'=>$realFile);	// the order should be preserved here because
							} else $this->error('Missing file: '.$realFile);
						}
					}
					$outRow[$field]=array(
						'type'=>'file',
						'newValueFiles'=>$newValueFiles,
					);
				}
					// db record lists:
				if (($conf['type']=='group' && $conf['internal_type']=='db') ||	($conf['type']=='select' && $conf['foreign_table']))	{
					$allowedTables = $conf['type']=='group' ? $conf['allowed'] : $conf['foreign_table'].','.$conf['neg_foreign_table'];
					$prependName = $conf['type']=='group' ? $conf['prepend_tname'] : $conf['neg_foreign_table'];

					$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
					$dbAnalysis->start($value,$allowedTables,$conf['MM'],$uid);

					$outRow[$field]=array(
						'type'=>'db',
#						'tableArray' => $dbAnalysis->tableArray,
						'itemArray' => $dbAnalysis->itemArray,
#						'getValueArray' => $dbAnalysis->getValueArray($prependName)
					);
				}
			}
		}
		return $outRow;
	}

	/**
	 * DB relations flattend to 1-dim array.
	 *
	 * @param	[type]		$dbrels: ...
	 * @return	[type]		...
	 */
	function flatDBrels($dbrels)	{
		$list=array();
		reset($dbrels);
		while(list($table,$dat)=each($dbrels))	{
			if ($dat['type']=='db')	{
				reset($dat['itemArray']);
				while(list(,$i)=each($dat['itemArray']))	{
					$list[$i['table'].':'.$i['id']]=$i;
				}
			}
		}
#		if (count($list))	debug($list);
		return $list;
#		debug($dbrels);
	}

	/**
	 * Loads TRD file content into the $this->dat array
	 *
	 * @param	[type]		$filecontent: ...
	 * @return	[type]		...
	 */
	function loadContent($filecontent)	{
		$pointer = 0;

		$this->dat['header'] = $this->getNextContentPart($filecontent,$pointer,1,'header');
		$this->dat['records'] = $this->getNextContentPart($filecontent,$pointer,1,'records');
		$this->dat['files'] = $this->getNextContentPart($filecontent,$pointer,1,'files');
	}

	/**
	 * Returns the next content part from the $filecontent
	 *
	 * @param	[type]		$filecontent: ...
	 * @param	[type]		$pointer: ...
	 * @param	[type]		$unserialize: ...
	 * @param	[type]		$name: ...
	 * @return	[type]		...
	 */
	function getNextContentPart($filecontent,&$pointer,$unserialize=0,$name='')	{
		$initStrLen = 32+1+1+1+10+1;
			// getting header data
		$initStr = substr($filecontent,$pointer,$initStrLen);
		$pointer+=$initStrLen;
		$initStrDat=explode(':',$initStr);
		if (!strcmp($initStrDat[3],''))	{
			$datString = substr($filecontent,$pointer,intval($initStrDat[2]));
			$pointer+=intval($initStrDat[2])+1;
			if (!strcmp(md5($datString),$initStrDat[0]))	{
				if ($initStrDat[1])	{
					if ($this->compress)	{
						$datString=gzuncompress($datString);
					} else debug('Content read error: This file requires decompression, but this server does not offer gzcompress()/gzuncompress() functions.',1);
				}
				return $unserialize ? unserialize($datString) : $datString;
			} else debug('MD5 check failed ('.$name.')');
		} else debug('Content read error: InitString had a wrong length. ('.$name.')');
	}

	/**
	 * Loads the header section/all of the $filename into memory
	 *
	 * @param	[type]		$filename: ...
	 * @param	[type]		$all: ...
	 * @return	[type]		...
	 */
	function loadFile($filename,$all=0)	{
		if (@is_file($filename))	{
			if($fd = fopen($filename,'rb'))	{
				$this->dat['header']=$this->getNextFilePart($fd,1,'header');
				if ($all)	{
					$this->dat['records']=$this->getNextFilePart($fd,1,'records');
					$this->dat['files']=$this->getNextFilePart($fd,1,'files');
				}
			} else debug('Error opening file: '.$filename);
			fclose($fd);
		} else debug('Filename not found: '.$filename);
	}

	/**
	 * Returns the next content part form the fileresource, $fd
	 *
	 * @param	[type]		$fd: ...
	 * @param	[type]		$unserialize: ...
	 * @param	[type]		$name: ...
	 * @return	[type]		...
	 */
	function getNextFilePart($fd,$unserialize=0,$name='')	{
		$initStrLen = 32+1+1+1+10+1;

			// getting header data
		$initStr = fread($fd,$initStrLen);
		$initStrDat=explode(':',$initStr);
		if (!strcmp($initStrDat[3],''))	{
			$datString = fread($fd,intval($initStrDat[2]));
			fread($fd,1);
			if (!strcmp(md5($datString),$initStrDat[0]))	{
				if ($initStrDat[1])	{
					if ($this->compress)	{
						$datString=gzuncompress($datString);
					} else debug('Content read error: This file requires decompression, but this server does not offer gzcompress()/gzuncompress() functions.',1);
				}
				return $unserialize ? unserialize($datString) : $datString;
			} else debug('MD5 check failed ('.$name.')');
		} else debug('File read error: InitString had a wrong length. ('.$name.')');
	}

	/**
	 * This compiles and returns the data content for an exported file
	 *
	 * @return	[type]		...
	 */
	function compileMemoryToFileContent()	{
		$compress=$this->doOutputCompress();
		$out='';

		// adding header:
		$out.=$this->addFilePart(serialize($this->dat['header']),$compress);

		// adding records:
		$out.=$this->addFilePart(serialize($this->dat['records']),$compress);

		// adding files:
		$out.=$this->addFilePart(serialize($this->dat['files']),$compress);

		return $out;
	}

	/**
	 * Returns true if the output should be compressed.
	 *
	 * @return	[type]		...
	 */
	function doOutputCompress()	{
		return $this->compress && !$this->dontCompress;
	}

	/**
	 * Returns a content part for a filename being build.
	 *
	 * @param	[type]		$data: ...
	 * @param	[type]		$compress: ...
	 * @return	[type]		...
	 */
	function addFilePart($data,$compress=0)	{
		if ($compress)	$data=gzcompress($data);
		return md5($data).':'.($compress?'1':'0').':'.str_pad(strlen($data),10,'0',STR_PAD_LEFT).':'.$data.':';
	}

	/**
	 * Sets error message.
	 *
	 * @param	[type]		$msg: ...
	 * @return	[type]		...
	 */
	function error($msg)	{
		$this->errorLog[]=$msg;
	}

	/**
	 * Returns a table with the error-messages.
	 *
	 * @return	[type]		...
	 */
	function printErrorLog()	{
		return t3lib_div::view_array($this->errorLog);
	}

	/**
	 * Returns destination path to an upload folder given by $folder
	 *
	 * @param	[type]		$folder: ...
	 * @return	[type]		...
	 */
	function destPathFromUploadFolder ($folder)	{
		return PATH_site.$folder;
	}










	/**
	 * Imports the internal data array to $pid.
	 *
	 * @param	[type]		$pid: ...
	 * @return	[type]		...
	 */
	function importData($pid)	{
		global $TCA;
#debug($this->dat['header']);
#debug($this->dat['records']);

			// These vars MUST last for the whole section not being cleared. They are used by the method setRelations() which are called at the end of the import session.
		$this->import_mapId=array();
		$this->import_newId=array();
		$this->import_newId_pids=array();



			// BEGIN pages session
		if (is_array($this->dat['header']['records']['pages']))	{
				// $pageRecords is a copy of the pages array in the imported file. Records here are unset one by one when the addSingle function is called.
			$pageRecords = $this->dat['header']['records']['pages'];
			$this->initImportVars();	// Init each tcemain session with this!
				// First add page tree if any
			if (is_array($this->dat['header']['pagetree']))	{
				$pagesFromTree=$this->flatInversePageTree($this->dat['header']['pagetree']);
				reset($pagesFromTree);
				while(list(,$uid)=each($pagesFromTree))	{
					$thisRec = $this->dat['header']['records']['pages'][$uid];
						// PID: Set the main $pid, unless a NEW-id is found
					$setPid = isset($this->import_newId_pids[$thisRec['pid']])	? $this->import_newId_pids[$thisRec['pid']] : $pid;
					$this->addSingle('pages',$uid,$setPid);
					unset($pageRecords[$uid]);
				}
			}
#debug($pageRecords);
				// Then add all remaining pages.
			if (count($pageRecords))	{
				reset($pageRecords);
				while(list($table,$recs)=each($pageRecords))	{
					reset($recs);
					while(list($uid)=each($recs))	{
						$this->addSingle($table,$uid,$pid);
					}
				}
			}

				// Now write to database:
			$tce = $this->getNewTCE();
			$tce->start($this->import_data,Array());
			$tce->process_datamap();

				// post-processing: Removing files and registering new ids (end all tcemain sessions with this)
			$this->addToMapId($tce->substNEWwithIDs);
			$this->unlinkTempFiles();
		}

#debug($this->import_mapId);
			// BEGIN tcemain session (rest except pages)
		$this->initImportVars();	// Init each tcemain session with this!
		if (is_array($this->dat['header']['records']))	{
			reset($this->dat['header']['records']);
			while(list($table,$recs)=each($this->dat['header']['records']))	{
				if ($table!='pages')	{
					reset($recs);
					while(list($uid,$thisRec)=each($recs))	{
						// PID: Set the main $pid, unless a NEW-id is found
						$setPid = isset($this->import_mapId['pages'][$thisRec['pid']]) ? $this->import_mapId['pages'][$thisRec['pid']] : $pid;
						if (is_array($TCA[$table]) && $TCA[$table]['ctrl']['rootLevel'])	{
							$setPid=0;
						}
#debug($setPid);
#debug($thisRec);
						$this->addSingle($table,$uid,$setPid);
					}
				}
			}
		} else debug('Error: No records defined in internal data array.');

#debug($this->unlinkFiles);
#debug($this->alternativeFileName);
#debug($this->import_data);

			// Now write to database:
		$tce = $this->getNewTCE();
		$tce->reverseOrder=1;	// Because all records are being submitted in their correct order with positive pid numbers - and so we should reverse submission order internally.
		$tce->start($this->import_data,Array());
		$tce->process_datamap();


			// post-processing: Removing files and registering new ids (end all tcemain sessions with this)
		$this->addToMapId($tce->substNEWwithIDs);
		$this->unlinkTempFiles();

#debug($this->import_newId);
#debug($tce->substNEWwithIDs);
#		$tce->clear_cacheCmd($cacheCmd);
			// END tcemain sessions


			// Finally all the database record references must be fixed. This is done after all records have supposedly been written to database:
			// $this->import_mapId will indicate two things: 1) that a record WAS written to db and 2) that it has got a new id-number.
		$this->setRelations();
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getNewTCE()	{
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;
		$tce->dontProcessTransformations=1;
		$tce->enableLogging=0;
		$tce->alternativeFileName = $this->alternativeFileName;
		return $tce;
	}

	/**
	 * At the end of the import process all relations should be set properly (that is relations to imported records are all re-created so imported records are correctly related again)
	 *
	 * @return	[type]		...
	 */
	function setRelations()	{
		global $TCA;

		$updateData=array();
		reset($this->import_newId);
		while(list($nId,$dat)=each($this->import_newId))	{
			$table=$dat['table'];
			$uid=$dat['uid'];	// original UID - NOT the new one!
			if (is_array($this->import_mapId[$table]) && isset($this->import_mapId[$table][$uid]))	{
				$thisNewUid = $this->import_mapId[$table][$uid];
				if (is_array($this->dat['records'][$table.':'.$uid]['rels']))	{
					reset($this->dat['records'][$table.':'.$uid]['rels']);
					while(list($field,$config)=each($this->dat['records'][$table.':'.$uid]['rels']))	{
						switch((string)$config['type'])	{
							case 'db':
								if (count($config['itemArray']))	{
									$valArray=array();
									reset($config['itemArray']);
									while(list(,$relDat)=each($config['itemArray']))	{
										if (is_array($this->import_mapId[$relDat['table']]) && isset($this->import_mapId[$relDat['table']][$relDat['id']]))	{
											#debug('FOUND: '.$relDat['table'].':'.$relDat['id'],1);
											$valArray[]=$relDat['table'].'_'.$this->import_mapId[$relDat['table']][$relDat['id']];
										} elseif (is_array($TCA[$relDat['table']]) && $TCA[$relDat['table']]['ctrl']['is_static']) {
											#debug('STATIC: '.$relDat['table'].':'.$relDat['id'],1);
											$valArray[]=$relDat['table'].'_'.$relDat['id'];
										} else {
											debug('Lost relation: '.$relDat['table'].':'.$relDat['id'],1);
										}
									}
									$updateData[$table][$thisNewUid][$field]=implode(',',$valArray);
								}
							break;
						}
					}
				} else debug('Error: no record was found in data array!',1);
			} else debug('Error: this records is NOT created it seems! ('.$table.':'.$uid.')',1);
		}
		if (count($updateData))	{
#debug($updateData);
			$tce = $this->getNewTCE();
			$tce->start($updateData,Array());
			$tce->process_datamap();
		}
	}

	/**
	 * Registers the substNEWids in memory.
	 *
	 * @param	[type]		$substNEWwithIDs: ...
	 * @return	[type]		...
	 */
	function addToMapId($substNEWwithIDs)	{
		reset($this->import_data);
		while(list($table,$recs)=each($this->import_data))	{
			reset($recs);
			while(list($id)=each($recs))	{
				$old_uid = $this->import_newId[$id]['uid'];
				if (isset($substNEWwithIDs[$id]))	{
					$this->import_mapId[$table][$old_uid]=$substNEWwithIDs[$id];
				} else debug('Possible error: '.$table.':'.$old_uid.' had no new id assigned to it. This indicates that the record was not added to database during import. Please check changelog!',1);
			}
		}

	}

	/**
	 * Initializes the import proces variables. This should be done for each time you have a session calling tcemain (remember also to unlink files after these sessions).
	 *
	 * @return	[type]		...
	 */
	function initImportVars()	{
		$this->import_data=array();
		$this->unlinkFiles=array();
		$this->alternativeFileName=array();
	}

	/**
	 * Cleaning up all the temporary files stored in typo3temp/ folder
	 *
	 * @return	[type]		...
	 */
	function unlinkTempFiles()	{
		$tempPath = $this->getTempPathFileName('');
#debug($tempPath);
		reset($this->unlinkFiles);
		while(list(,$fileName)=each($this->unlinkFiles))	{
			if (t3lib_div::isFirstPartOfStr($fileName,$tempPath))	{
				unlink($fileName);
				clearstatcache();
				if (is_file($fileName))	{
					debug('Error: '.$fileName.' was NOT unlinked as it should have been!',1);
				}
			} else debug('Error: '.$fileName.' was not in temp-path. Not removed!',1);
		}
	}

	/**
	 * Adds a single record to the $importData array. Also copies files to tempfolder. However all references are set to blank for now.
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$pid: ...
	 * @return	[type]		...
	 */
	function addSingle($table,$uid,$pid)	{
		$record = $this->dat['records'][$table.':'.$uid]['data'];
		if (is_array($record))	{
			$ID = uniqid('NEW');
			$this->import_newId[$ID] = array('table'=>$table,'uid'=>$uid);
			if ($table=='pages')	$this->import_newId_pids[$uid]=$ID;

			$this->import_data[$table][$ID]=$record;
			$this->import_data[$table][$ID]['tx_impexp_origuid']=$this->import_data[$table][$ID]['uid'];
			unset($this->import_data[$table][$ID]['uid']);
			$this->import_data[$table][$ID]['pid']=$pid;

			reset($this->dat['records'][$table.':'.$uid]['rels']);
			while(list($field,$config)=each($this->dat['records'][$table.':'.$uid]['rels']))	{
				$this->import_data[$table][$ID][$field]='';
				switch((string)$config['type'])	{
					case 'db':
#debug($config);
						// ... later
					break;
					case 'file':
						$valArr=array();
						reset($config['newValueFiles']);
						while(list(,$fI)=each($config['newValueFiles']))	{
							$valArr[]=$this->import_addFileNameToBeCopied($fI);
#debug($fI);
						}
						$this->import_data[$table][$ID][$field]=implode(',',$valArr);
					break;
				}
			}
		} else debug('Error: no record was found in data array!',1);
	}

	/**
	 * Writes the file from import array to temp dir and returns the filename of it.
	 *
	 * @param	[type]		$fI: ...
	 * @return	[type]		...
	 */
	function import_addFileNameToBeCopied($fI)	{
		if (is_array($this->dat['files'][$fI['ID']]))	{
			$tmpFile=$this->getTempPathFileName('import_'.$GLOBALS['EXEC_TIME'].'_'.$fI['ID'].'.tmp');
			if (!@is_file($tmpFile))	{
				t3lib_div::writeFile($tmpFile,$this->dat['files'][$fI['ID']]['content']);
				clearstatcache();
				if (@is_file($tmpFile))	{
					if (filesize($tmpFile)==$this->dat['files'][$fI['ID']]['filesize'])	{
						$this->unlinkFiles[]=$tmpFile;
						$this->alternativeFileName[$tmpFile]=$fI['fieldvalue'];
#debug($tmpFile,1);
						return $tmpFile;
					} else debug('Error: temporary file '.$tmpFile.' had a size ('.filesize($tmpFile).') different from the original ('.$this->dat['files'][$fI['ID']]['filesize'].')',1);
				} else debug('Error: temporary file '.$tmpFile.' was not written as it should have been!',1);
			} else debug('Error: temporary file '.$tmpFile.' existed already!',1);
		} else debug('Error: No file found for ID '.$fI['ID'],1);
	}

	/**
	 * Returns the absolute path to typo3temp/ (for writing temporary files from the import ...)
	 *
	 * @param	[type]		$fN: ...
	 * @return	[type]		...
	 */
	function getTempPathFileName($fN)	{
		return PATH_site.'typo3temp/'.$fN;
	}













	/**
	 * Displays an overview of the header-content.
	 *
	 * @return	[type]		...
	 */
	function displayContentOverview	()	{
#		unset($this->dat['records']);
		unset($this->dat['files']);
#		debug($this->dat['header']);

		$this->remainHeader = $this->dat['header'];
		if (is_array($this->remainHeader))	{

			if (is_array($this->dat['header']['pagetree']))	{
				reset($this->dat['header']['pagetree']);
				$lines=array();
				$this->traversePageTree($this->dat['header']['pagetree'],$lines);

				$rows=array();
	#			debug($lines);
				reset($lines);
				while(list(,$r)=each($lines))	{
					$rows[]='<tr bgcolor="'.$r['bgColor'].'">
						<td nowrap="nowrap">'.$r['preCode'].$r['title'].'</td>
						<td nowrap="nowrap">'.t3lib_div::formatSize($r['size']).'</td>
						<td nowrap="nowrap">'.($r['msg']?'<span class="typo3-red">'.$r['msg'].'</span>':'').'</td>
					</tr>';
				}
				$rows[]='<tr>
					<td><img src="clear.gif" width="300" height="1" alt="" /></td>
					<td></td>
					<td></td>
				</tr>';
				$out = '<strong>Inside pagetree:</strong><br /><br /><table border="0" cellpadding="0" cellspacing="0">'.implode('',$rows).'</table><br /><br />';
			}


			$lines=array();
			if (is_array($this->remainHeader['records']['pages']))	{
				$this->traversePageRecords($this->remainHeader['records']['pages'],$lines);
			}
			$this->traverseAllRecords($this->remainHeader['records'],$lines);

			if (count($lines))	{
				$rows=array();
	#			debug($lines);
				reset($lines);
				while(list(,$r)=each($lines))	{
					$rows[]='<tr bgcolor="'.$r['bgColor'].'">
						<td nowrap="nowrap">'.$r['preCode'].$r['title'].'</td>
						<td nowrap="nowrap">'.t3lib_div::formatSize($r['size']).'</td>
						<td nowrap="nowrap">'.($r['msg']?'<span class="typo3-red">'.$r['msg'].'</span>':'').'</td>
					</tr>';
				}
				$rows[]='<tr>
					<td><img src="clear.gif" width="300" height="1" alt="" /></td>
					<td></td>
					<td></td>
				</tr>';
				$out.= '<strong>Outside pagetree:</strong><br /><br /><table border="0" cellpadding="0" cellspacing="0">'.implode('',$rows).'</table>';
			}

	#debug($this->remainHeader);
		}
		return $out;
	}

	/**
	 * Go through page tree for display
	 *
	 * @param	[type]		$pT: ...
	 * @param	[type]		$lines: ...
	 * @param	[type]		$preCode: ...
	 * @return	[type]		...
	 */
	function traversePageTree($pT,&$lines,$preCode='')	{
		reset($pT);
		while(list($k,$v)=each($pT))	{
			$this->singleRecordLines('pages',$k,$lines,$preCode);
				// Subrecords:
			if (is_array($this->dat['header']['pid_lookup'][$k]))	{
				reset($this->dat['header']['pid_lookup'][$k]);
				while(list($t,$recUidArr)=each($this->dat['header']['pid_lookup'][$k]))	{
					if ($t!='pages')	{
						reset($recUidArr);
						while(list($ruid)=each($recUidArr))	{
							$this->singleRecordLines($t,$ruid,$lines,$preCode.'&nbsp;&nbsp;&nbsp;&nbsp;');
						}
					}
				}
				unset($this->remainHeader['pid_lookup'][$k]);
			}
				// Subpages:
			if (is_array($v['subrow']))		$this->traversePageTree($v['subrow'],$lines,$preCode.'&nbsp;&nbsp;&nbsp;&nbsp;');
		}
	}

	/**
	 * Go through remaining pages (not in tree)
	 *
	 * @param	[type]		$pT: ...
	 * @param	[type]		$lines: ...
	 * @return	[type]		...
	 */
	function traversePageRecords($pT,&$lines)	{
		reset($pT);
		while(list($k,$rHeader)=each($pT))	{
			$this->singleRecordLines('pages',$k,$lines,'',1);
				// Subrecords:
			if (is_array($this->dat['header']['pid_lookup'][$k]))	{
				reset($this->dat['header']['pid_lookup'][$k]);
				while(list($t,$recUidArr)=each($this->dat['header']['pid_lookup'][$k]))	{
					if ($t!='pages')	{
						reset($recUidArr);
						while(list($ruid)=each($recUidArr))	{
							$this->singleRecordLines($t,$ruid,$lines,$preCode.'&nbsp;&nbsp;&nbsp;&nbsp;');
						}
					}
				}
				unset($this->remainHeader['pid_lookup'][$k]);
			}
		}
	}

	/**
	 * Go through ALL records (if the pages are displayed first, those will not be amoung these!)
	 *
	 * @param	[type]		$pT: ...
	 * @param	[type]		$lines: ...
	 * @return	[type]		...
	 */
	function traverseallrecords($pT,&$lines)	{
		reset($pT);
		while(list($t,$recUidArr)=each($pT))	{
			if ($t!='pages')	{
				reset($recUidArr);
				while(list($ruid)=each($recUidArr))	{
					$this->singleRecordLines($t,$ruid,$lines,$preCode,1);
				}
			}
		}
	}

	/**
	 * Add entries for a single record
	 *
	 * @param	[type]		$table: ...
	 * @param	[type]		$uid: ...
	 * @param	[type]		$lines: ...
	 * @param	[type]		$preCode: ...
	 * @param	[type]		$checkImportInPidRecord: ...
	 * @return	[type]		...
	 */
	function singleRecordLines($table,$uid,&$lines,$preCode,$checkImportInPidRecord=0)	{
		global $TCA,$BE_USER;

		$record = $this->dat['header']['records'][$table][$uid];
		unset($this->remainHeader['records'][$table][$uid]);
		if (!is_array($record))	debug('MISSING RECORD: '.$table.':'.$uid,1);

		$pInfo=array();
		$pInfo['ref']=$table.':'.$uid;
		if (!isset($TCA[$table]))	{
			$pInfo['preCode']=$preCode;
			$pInfo['msg']="UNKNOWN TABLE '".$pInfo['ref']."'";
			$pInfo['title']='<em>'.htmlspecialchars($record['title']).'</em>';
		} else {
			if (is_array($this->display_import_pid_record))	{
				if ($checkImportInPidRecord)	{
					if (!$BE_USER->doesUserHaveAccess($this->display_import_pid_record,$table=='pages'?8:16))	{
						$pInfo['msg'].="'".$pInfo['ref']."' cannot be INSERTED on this page! ";
					}
					if (!$this->checkDokType($table,$this->display_import_pid_record['doktype']) && !$TCA[$table]['ctrl']['rootLevel'])	{
						$pInfo['msg'].="'".$table."' cannot be INSERTED on this page type (change to 'sysFolder'!) ";
					}
				}
				if (!$BE_USER->check('tables_modify',$table))	{$pInfo['msg'].="You are not allowed to CREATE '".$table."' tables! ";}

				if ($TCA[$table]['ctrl']['readOnly'])	{$pInfo['msg'].="TABLE '".$table."' is READ ONLY! ";}
				if ($TCA[$table]['ctrl']['adminOnly'] && !$BE_USER->isAdmin())	{$pInfo['msg'].="TABLE '".$table."' is ADMIN ONLY! ";}
				if ($TCA[$table]['ctrl']['is_static'])	{$pInfo['msg'].="TABLE '".$table."' is a STATIC TABLE! ";}
				if ($TCA[$table]['ctrl']['rootLevel'])	{$pInfo['msg'].="TABLE '".$table."' will be inserted on ROOT LEVEL! ";}
			}
			$pInfo['preCode']=$preCode.t3lib_iconworks::getIconImage($table,$this->dat['records'][$table.':'.$uid]['data'],$GLOBALS['BACK_PATH'],'align="top" title="'.htmlspecialchars($table.':'.$uid).'"');
			$pInfo['title']=htmlspecialchars($record['title']);
		}
		$pInfo['bgColor']=$table=='pages' ? t3lib_div::modifyHTMLColor($GLOBALS['TBE_TEMPLATE']->bgColor4,-10,-10,-10) : t3lib_div::modifyHTMLColor($GLOBALS['TBE_TEMPLATE']->bgColor4,20,20,20);
		$pInfo['size']=$record['size'];
		$lines[]=$pInfo;
			// Files
		if (is_array($record['filerefs']))	{
			reset($record['filerefs']);
			while(list(,$ID)=each($record['filerefs']))	{
				$fI=$this->dat['header']['files'][$ID];
				if (!is_array($fI))	debug('MISSING FILE: '.$ID,1);
				$pInfo=array();
				$pInfo['preCode']=$preCode.'&nbsp;&nbsp;&nbsp;&nbsp;<img src="'.$GLOBALS['BACK_PATH'].'t3lib/gfx/rel_file.gif" width="13" height="12" align="top" alt="" />';
				$pInfo['title']=htmlspecialchars($fI['filename']);
				$pInfo['ref']='FILE';
				$pInfo['size']=$fI['filesize'];
				$pInfo['bgColor']=t3lib_div::modifyHTMLColor($GLOBALS['TBE_TEMPLATE']->bgColor4,10,10,10);
				$lines[]=$pInfo;
				unset($this->remainHeader['files'][$ID]);
			}
		}
			// DB:
		if (is_array($record['rels']))	{
			$this->addRelations($record['rels'],$lines,$preCode);
		}
	}

	/**
	 * Add relations entries for a record's rels-array
	 *
	 * @param	[type]		$rels: ...
	 * @param	[type]		$lines: ...
	 * @param	[type]		$preCode: ...
	 * @param	[type]		$recurCheck: ...
	 * @return	[type]		...
	 */
	function addRelations($rels,&$lines,$preCode,$recurCheck=array())	{
		reset($rels);
		while(list(,$dat)=each($rels))	{
			$table=$dat['table'];
			$uid=$dat['id'];
			$pInfo=array();
			$Iprepend='';
			$pInfo['ref']=$table.':'.$uid;
			if (!in_array($pInfo['ref'],$recurCheck))	{
				$record = $this->dat['header']['records'][$table][$uid];
				if (!is_array($record))	{
					if (isset($GLOBALS['TCA'][$table]) && $GLOBALS['TCA'][$table]['ctrl']['is_static'])	{
						$pInfo['title']=htmlspecialchars('STATIC: '.$pInfo['ref']);
						$Iprepend='_static';
					} else {
						$pInfo['title']=htmlspecialchars($pInfo['ref']);
						$pInfo['msg']='LOST RELATION';
						$Iprepend='_lost';
#						debug('MISSING relation: '.$table.':'.$uid,1);
					}
				} else {
					$pInfo['title']=htmlspecialchars($record['title']);
					$pInfo['size']=$record['size'];
				}

				$pInfo['preCode']=$preCode.'&nbsp;&nbsp;&nbsp;&nbsp;<img src="'.$GLOBALS['BACK_PATH'].'t3lib/gfx/rel_db'.$Iprepend.'.gif" width="13" height="12" align="top" title="'.$pInfo['ref'].'" alt="" />';
				$pInfo['bgColor']=t3lib_div::modifyHTMLColor($GLOBALS['TBE_TEMPLATE']->bgColor4,10,10,10);
				$lines[]=$pInfo;
				if (is_array($record) && is_array($record['rels']))	{
					$this->addRelations($record['rels'],$lines,$preCode.'&nbsp;&nbsp;',array_merge($recurCheck,array($pInfo['ref'])));
				}
			} else debug($pInfo['ref'].' was recursive...');
		}
	}

	/**
	 * Verifies that a table is allowed on a certain doktype of a page
	 *
	 * @param	[type]		$checkTable: ...
	 * @param	[type]		$doktype: ...
	 * @return	[type]		...
	 */
	function checkDokType($checkTable,$doktype)	{
		global $PAGES_TYPES;
		$allowedTableList = isset($PAGES_TYPES[$doktype]['allowedTables']) ? $PAGES_TYPES[$doktype]['allowedTables'] : $PAGES_TYPES['default']['allowedTables'];
		$allowedArray = t3lib_div::trimExplode(',',$allowedTableList,1);
		if (strstr($allowedTableList,'*') || in_array($checkTable,$allowedArray))	{		// If all tables or the table is listed as a allowed type, return true
			return true;
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/impexp/class.tx_impexp.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/impexp/class.tx_impexp.php']);
}
?>