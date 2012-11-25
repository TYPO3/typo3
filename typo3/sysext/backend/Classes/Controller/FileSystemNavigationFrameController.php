<?php
namespace TYPO3\CMS\Backend\Controller;

/**
 * Main script class for rendering of the folder tree
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class FileSystemNavigationFrameController {

	// Internal, dynamic:
	// Content accumulates in this variable.
	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * @var \TYPO3\CMS\Filelist\FileListFolderTree $foldertree the folder tree object
	 * @todo Define visibility
	 */
	public $foldertree;

	/**
	 * document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	/**
	 * @todo Define visibility
	 */
	public $backPath;

	// Internal, static: GPvar:
	/**
	 * @todo Define visibility
	 */
	public $currentSubScript;

	/**
	 * @todo Define visibility
	 */
	public $cMR;

	/**
	 * Initialiation of the script class
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function init() {
		// Setting backPath
		$this->backPath = $GLOBALS['BACK_PATH'];
		// Setting GPvars:
		$this->currentSubScript = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('currentSubScript');
		$this->cMR = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cMR');
		// Create folder tree object:
		/** @var $foldertree \TYPO3\CMS\Filelist\FileListFolderTree */
		$this->foldertree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Filelist\\FileListFolderTree');
		$this->foldertree->ext_IconMode = $GLOBALS['BE_USER']->getTSConfigVal('options.folderTree.disableIconLinkToContextmenu');
		$this->foldertree->thisScript = 'alt_file_navframe.php';
	}

	/**
	 * initialization for the visual parts of the class
	 * Use template rendering only if this is a non-AJAX call
	 *
	 * @return void
	 */
	public function initPage() {
		// Setting highlight mode:
		$this->doHighlight = !$GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.disableTitleHighlight');
		// Create template object:
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/alt_file_navframe.html');
		$this->doc->showFlashMessages = FALSE;
		// Adding javascript code for AJAX (prototype), drag&drop and the filetree as well as the click menu code
		$this->doc->getDragDropCode('folders');
		$this->doc->getContextMenuCode();
		// Setting JavaScript for menu.
		$this->doc->JScode .= $this->doc->wrapScriptTags(($this->currentSubScript ? 'top.currentSubScript=unescape("' . rawurlencode($this->currentSubScript) . '");' : '') . '

		function initFlashUploader(path) {
			path = decodeURIComponent(path);
			var flashUploadOptions = {
				uploadURL: top.TS.PATH_typo3 + "ajax.php",
				uploadFileSizeLimit: "' . \TYPO3\CMS\Core\Utility\GeneralUtility::getMaxUploadFileSize() . '",
				uploadFileTypes: {
					allow:  "' . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']['webspace']['allow'] . '",
					deny: "' . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']['webspace']['deny'] . '"
				},
				uploadFilePostName: "upload_1",
				uploadPostParams: {
					"file[upload][1][target]": path,
					"file[upload][1][data]": 1,
					"file[upload][1][charset]": "utf-8",
					"ajaxID": "TYPO3_tcefile::process"
				}
			};

				// get the flashUploaderWindow instance from the parent frame
			var flashUploader = top.TYPO3.FileUploadWindow.getInstance(flashUploadOptions);
				// add an additional function inside the container to show the checkbox option
			var infoComponent = new top.Ext.Panel({
				autoEl: { tag: "div" },
				height: "auto",
				bodyBorder: false,
				border: false,
				hideBorders: true,
				cls: "t3-upload-window-infopanel",
				id: "t3-upload-window-infopanel-addition",
				html: \'<label for="overrideExistingFilesCheckbox"><input id="overrideExistingFilesCheckbox" type="checkbox" onclick="setFlashPostOptionOverwriteExistingFiles(this);" />\' + top.String.format(top.TYPO3.LLL.fileUpload.infoComponentOverrideFiles) + \'</label>\'
			});
			flashUploader.add(infoComponent);

				// do a reload of this frame once all uploads are done
			flashUploader.on("totalcomplete", function() {
				jumpTo (top.rawurlencode(path), "", "", "");
			});

				// this is the callback function that delivers the additional post parameter to the flash application
			top.setFlashPostOptionOverwriteExistingFiles = function(checkbox) {
				var uploader = top.TYPO3.getInstance("FileUploadWindow");
				if (uploader.isVisible()) {
					uploader.swf.addPostParam("overwriteExistingFiles", (checkbox.checked == true ? 1 : 0));
				}
			};
		}


		// setting prefs for foldertree
		Tree.ajaxID = "SC_alt_file_navframe::expandCollapse";

		// Function, loading the list frame from navigation tree:
		function jumpTo(id, linkObj, highlightID, bank) {
			var theUrl = top.TS.PATH_typo3 + top.currentSubScript ;
			if (theUrl.indexOf("?") != -1) {
				theUrl += "&id=" + id
			} else {
				theUrl += "?id=" + id
			}
			top.fsMod.currentBank = bank;
			top.TYPO3.Backend.ContentContainer.setUrl(theUrl);

			' . ($this->doHighlight ? 'Tree.highlightActiveItem("file", highlightID + "_" + bank);' : '') . '
			' . (!$GLOBALS['CLIENT']['FORMSTYLE'] ? '' : 'if (linkObj) linkObj.blur(); ') . '
			return false;
		}
		' . ($this->cMR ? ' jumpTo(top.fsMod.recentIds[\'file\'],\'\');' : ''));
	}

	/**
	 * Main function, rendering the folder tree
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Produce browse-tree:
		$tree = $this->foldertree->getBrowsableTree();
		// Outputting page tree:
		$this->content .= $tree;
		// Adding javascript for drag & drop activation and highlighting
		$this->content .= $this->doc->wrapScriptTags('
			' . ($this->doHighlight ? 'Tree.highlightActiveItem("", top.fsMod.navFrameHighlightedID["file"]);' : '') . '
			Tree.registerDragDropHandlers();');
		// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers = array(
			'IMG_RESET' => '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/close_gray.gif', ' width="16" height="16"') . ' id="treeFilterReset" alt="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.resetFilter') . '" ' . 'title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.resetFilter') . '" />',
			'CONTENT' => $this->content
		);
		$subparts = array();
		// Possible filter/search like in page tree
		$subparts['###SECOND_ROW###'] = '';
		// Build the <body> for the module
		$this->content = $this->doc->startPage('TYPO3 Folder Tree');
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers, $subparts);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => '',
			'refresh' => ''
		);
		// Refresh
		$buttons['refresh'] = '<a href="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-refresh') . '</a>';
		// CSH
		$buttons['csh'] = str_replace('typo3-csh-inline', 'typo3-csh-inline show-right', \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'filetree', $GLOBALS['BACK_PATH']));
		return $buttons;
	}

	/**********************************
	 *
	 * AJAX Calls
	 *
	 **********************************/
	/**
	 * Makes the AJAX call to expand or collapse the foldertree.
	 * Called by typo3/ajax.php
	 *
	 * @param array $params Additional parameters (not used here)
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj The TYPO3AJAX object of this request
	 * @return void
	 */
	public function ajaxExpandCollapse($params, $ajaxObj) {
		$this->init();
		$tree = $this->foldertree->getBrowsableTree();
		if ($this->foldertree->getAjaxStatus() === FALSE) {
			$ajaxObj->setError($tree);
		} else {
			$ajaxObj->addContent('tree', $tree);
		}
	}

}


?>