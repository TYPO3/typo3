<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) Vincent Blavet <vincent@phpconcept.net>
 *  (c) 2005-2010 Karsten Dambekalns <karsten@typo3.org>
 *  All rights reserved
 *
 *  This library is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU Lesser General Public
 *  License as published by the Free Software Foundation; either
 *  version 2.1 of the License, or (at your option) any later version.
 *
 *  This library is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *  Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public
 *  License along with this library; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *  MA  02110-1301  USA
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Module: Extension manager
 *
 * @author	Vincent Blavet <vincent@phpconcept.net>
 * @author	Karsten Dambekalns <karsten@typo3.org>
 */


// Constants
define('ARCHIVE_ZIP_READ_BLOCK_SIZE', 2048);

// File list separator
define('ARCHIVE_ZIP_SEPARATOR', ',');

define('ARCHIVE_ZIP_TEMPORARY_DIR', '');

// Error codes
define('ARCHIVE_ZIP_ERR_NO_ERROR', 0);
define('ARCHIVE_ZIP_ERR_WRITE_OPEN_FAIL', -1);
define('ARCHIVE_ZIP_ERR_READ_OPEN_FAIL', -2);
define('ARCHIVE_ZIP_ERR_INVALID_PARAMETER', -3);
define('ARCHIVE_ZIP_ERR_MISSING_FILE', -4);
define('ARCHIVE_ZIP_ERR_FILENAME_TOO_LONG', -5);
define('ARCHIVE_ZIP_ERR_INVALID_ZIP', -6);
define('ARCHIVE_ZIP_ERR_BAD_EXTRACTED_FILE', -7);
define('ARCHIVE_ZIP_ERR_DIR_CREATE_FAIL', -8);
define('ARCHIVE_ZIP_ERR_BAD_EXTENSION', -9);
define('ARCHIVE_ZIP_ERR_BAD_FORMAT', -10);
define('ARCHIVE_ZIP_ERR_DELETE_FILE_FAIL', -11);
define('ARCHIVE_ZIP_ERR_RENAME_FILE_FAIL', -12);
define('ARCHIVE_ZIP_ERR_BAD_CHECKSUM', -13);
define('ARCHIVE_ZIP_ERR_INVALID_ARCHIVE_ZIP', -14);
define('ARCHIVE_ZIP_ERR_MISSING_OPTION_VALUE', -15);
define('ARCHIVE_ZIP_ERR_INVALID_PARAM_VALUE', -16);

// Warning codes
define('ARCHIVE_ZIP_WARN_NO_WARNING', 0);
define('ARCHIVE_ZIP_WARN_FILE_EXIST', 1);

// Methods parameters
define('ARCHIVE_ZIP_PARAM_PATH', 'path');
define('ARCHIVE_ZIP_PARAM_ADD_PATH', 'add_path');
define('ARCHIVE_ZIP_PARAM_REMOVE_PATH', 'remove_path');
define('ARCHIVE_ZIP_PARAM_REMOVE_ALL_PATH', 'remove_all_path');
define('ARCHIVE_ZIP_PARAM_SET_CHMOD', 'set_chmod');
define('ARCHIVE_ZIP_PARAM_EXTRACT_AS_STRING', 'extract_as_string');
define('ARCHIVE_ZIP_PARAM_NO_COMPRESSION', 'no_compression');

define('ARCHIVE_ZIP_PARAM_PRE_EXTRACT', 'callback_pre_extract');
define('ARCHIVE_ZIP_PARAM_POST_EXTRACT', 'callback_post_extract');
define('ARCHIVE_ZIP_PARAM_PRE_ADD', 'callback_pre_add');
define('ARCHIVE_ZIP_PARAM_POST_ADD', 'callback_post_add');


/**
 * Class for unpacking zip archive files
 *
 * @author   Vincent Blavet <vincent@blavet.net>
 * @author   Karsten Dambekalns <karsten@typo3.org>
 */
class tx_em_Tools_Unzip {
	/**
	 * The filename of the zip archive.
	 *
	 * @var string Name of the Zip file
	 */
	var $_zipname = '';

	/**
	 * File descriptor of the opened Zip file.
	 *
	 * @var int Internal zip file descriptor
	 */
	var $_zip_fd = 0;

	/**
	 * @var int last error code
	 */
	var $_error_code = 1;

	/**
	 * @var string Last error description
	 */
	var $_error_string = '';

	/**
	 * tx_em_Tools_Unzip Class constructor. This flavour of the constructor only
	 * declare a new tx_em_Tools_Unzip object, identifying it by the name of the
	 * zip file.
	 *
	 * @param	string  $p_zipname  The name of the zip archive to create
	 * @access public
	 */
	public function __construct($p_zipname) {

		// Check the zlib
		if (!extension_loaded('zlib')) {
			throw new RuntimeException(
				'TYPO3 Fatal Error: ' . "The extension 'zlib' couldn't be found.\n" .
						"Please make sure your version of PHP was built " .
						"with 'zlib' support.\n",
				1270853984
			);
		}

		// Set the attributes
		$this->_zipname = $p_zipname;
		$this->_zip_fd = 0;

		return;
	}


	/**
	 * This method extract the files and folders which are in the zip archive.
	 * It can extract all the archive or a part of the archive by using filter
	 * feature (extract by name, by index, by ereg, by preg). The extraction
	 * can occur in the current path or an other path.
	 * All the advanced features are activated by the use of variable
	 * parameters.
	 * The return value is an array of entry descriptions which gives
	 * information on extracted files (See listContent()).
	 * The method may return a success value (an array) even if some files
	 * are not correctly extracted (see the file status in listContent()).
	 * The supported variable parameters for this method are :
	 *   'add_path' : Path where the files and directories are to be extracted
	 *
	 * @access public
	 * @param	mixed  $p_params  An array of variable parameters and values.
	 * @return mixed An array of file description on success,
	 *			   0 on an unrecoverable failure, an error code is logged.
	 */
	function extract($p_params = 0) {
		$this->_errorReset();

		// Check archive
		if (!$this->_checkFormat()) {
			return (0);
		}

		// Set default values
		if ($p_params === 0) {
			$p_params = array();
		}
		if ($this->_check_parameters($p_params,
			array('extract_as_string' => false,
				'add_path' => '',
				'remove_path' => '',
				'remove_all_path' => false,
				'callback_pre_extract' => '',
				'callback_post_extract' => '',
				'set_chmod' => 0)) != 1) {
			return 0;
		}

		// Call the extracting fct
		$v_list = array();
		if ($this->_extractByRule($v_list, $p_params) != 1) {
			unset($v_list);
			return (0);
		}

		return $v_list;
	}

	/**
	 * Method that gives the lastest error code.
	 *
	 * @access public
	 * @return integer The error code value.
	 */
	function errorCode() {
		return ($this->_error_code);
	}

	/**
	 * This method gives the latest error code name.
	 *
	 * @access public
	 * @param  boolean $p_with_code  If TRUE, gives the name and the int value.
	 * @return string The error name.
	 */
	function errorName($p_with_code = false) {
		$v_const_list = get_defined_constants();

		// Extract error constants from all const.
		for (reset($v_const_list);
			list($v_key, $v_value) = each($v_const_list);) {
			if (substr($v_key, 0, strlen('ARCHIVE_ZIP_ERR_')) == 'ARCHIVE_ZIP_ERR_') {
				$v_error_list[$v_key] = $v_value;
			}
		}

		// Search the name form the code value
		$v_key = array_search($this->_error_code, $v_error_list, TRUE);
		if ($v_key != false) {
			$v_value = $v_key;
		} else {
			$v_value = 'NoName';
		}

		if ($p_with_code) {
			return ($v_value . ' (' . $this->_error_code . ')');
		} else {
			return ($v_value);
		}
	}

	/**
	 * This method returns the description associated with the latest error.
	 *
	 * @access public
	 * @param  boolean $p_full If set to TRUE gives the description with the
	 *						 error code, the name and the description.
	 *						 If set to false gives only the description
	 *						 and the error code.
	 * @return string The error description.
	 */
	function errorInfo($p_full = false) {
		if ($p_full) {
			return ($this->errorName(TRUE) . " : " . $this->_error_string);
		} else {
			return ($this->_error_string . " [code " . $this->_error_code . "]");
		}
	}


	/**
	 * tx_em_Tools_Unzip::_checkFormat()
	 *
	 * { Description }
	 *
	 * @param integer $p_level
	 */
	function _checkFormat($p_level = 0) {
		$v_result = TRUE;

		// Reset the error handler
		$this->_errorReset();

		// Look if the file exits
		if (!is_file($this->_zipname)) {
			// Error log
			$this->_errorLog(ARCHIVE_ZIP_ERR_MISSING_FILE,
					"Missing archive file '" . $this->_zipname . "'");
			return (false);
		}

		// Check that the file is readeable
		if (!is_readable($this->_zipname)) {
			// Error log
			$this->_errorLog(ARCHIVE_ZIP_ERR_READ_OPEN_FAIL,
					"Unable to read archive '" . $this->_zipname . "'");
			return (false);
		}

		// Check the magic code
		// TBC

		// Check the central header
		// TBC

		// Check each file header
		// TBC

		// Return
		return $v_result;
	}


	/**
	 * tx_em_Tools_Unzip::_openFd()
	 *
	 * { Description }
	 *
	 */
	function _openFd($p_mode) {
		$v_result = 1;

		// Look if already open
		if ($this->_zip_fd != 0) {
			$this->_errorLog(ARCHIVE_ZIP_ERR_READ_OPEN_FAIL,
					'Zip file \'' . $this->_zipname . '\' already open');
			return tx_em_Tools_Unzip::errorCode();
		}

		// Open the zip file
		if (($this->_zip_fd = @fopen($this->_zipname, $p_mode)) == 0) {
			$this->_errorLog(ARCHIVE_ZIP_ERR_READ_OPEN_FAIL,
					'Unable to open archive \'' . $this->_zipname
							. '\' in ' . $p_mode . ' mode');
			return tx_em_Tools_Unzip::errorCode();
		}

		// Return
		return $v_result;
	}

	/**
	 * tx_em_Tools_Unzip::_closeFd()
	 *
	 * { Description }
	 *
	 */
	function _closeFd() {
		$v_result = 1;

		if ($this->_zip_fd != 0) {
			@fclose($this->_zip_fd);
		}
		$this->_zip_fd = 0;

		// Return
		return $v_result;
	}


	/**
	 * tx_em_Tools_Unzip::_convertHeader2FileInfo()
	 *
	 * { Description }
	 *
	 */
	function _convertHeader2FileInfo($p_header, &$p_info) {
		$v_result = 1;

		// Get the interesting attributes
		$p_info['filename'] = $p_header['filename'];
		$p_info['stored_filename'] = $p_header['stored_filename'];
		$p_info['size'] = $p_header['size'];
		$p_info['compressed_size'] = $p_header['compressed_size'];
		$p_info['mtime'] = $p_header['mtime'];
		$p_info['comment'] = $p_header['comment'];
		$p_info['folder'] = (($p_header['external'] & 0x00000010) == 0x00000010);
		$p_info['index'] = $p_header['index'];
		$p_info['status'] = $p_header['status'];

		// Return
		return $v_result;
	}


	// Function : _extractByRule()
	// Description :
	//   Extract a file or directory depending of rules (by index, by name, ...)
	// Parameters :
	//   $p_file_list : An array where will be placed the properties of each
	//                  extracted file
	//   $p_path : Path to add while writing the extracted files
	//   $p_remove_path : Path to remove (from the file memorized path) while writing the
	//                    extracted files. If the path does not match the file path,
	//                    the file is extracted with its memorized path.
	//                    $p_remove_path does not apply to 'list' mode.
	//                    $p_path and $p_remove_path are commulative.
	// Return Values :
	//   1 on success,0 or less on error (see error code list)

	/**
	 * tx_em_Tools_Unzip::_extractByRule()
	 *
	 * { Description }
	 *
	 */
	function _extractByRule(&$p_file_list, &$p_params) {
		$v_result = 1;

		$p_path = $p_params['add_path'];
		$p_remove_path = $p_params['remove_path'];
		$p_remove_all_path = $p_params['remove_all_path'];

		// Check the path
		if (($p_path == "")
				|| ((substr($p_path, 0, 1) != "/")
						&& (substr($p_path, 0, 3) != "../") && (substr($p_path, 1, 2) != ":/"))) {
			$p_path = "./" . $p_path;
		}

		// Reduce the path last (and duplicated) '/'
		if (($p_path != "./") && ($p_path != "/")) {
			// Look for the path end '/'
			while (substr($p_path, -1) == "/") {
				$p_path = substr($p_path, 0, strlen($p_path) - 1);
			}
		}

		// Open the zip file
		if (($v_result = $this->_openFd('rb')) != 1) {
			return $v_result;
		}

		// Read the central directory informations
		$v_central_dir = array();
		if (($v_result = $this->_readEndCentralDir($v_central_dir)) != 1) {
			// Close the zip file
			$this->_closeFd();

			return $v_result;
		}

		// Start at beginning of Central Dir
		$v_pos_entry = $v_central_dir['offset'];

		// Read each entry
		$j_start = 0;
		for ($i = 0, $v_nb_extracted = 0; $i < $v_central_dir['entries']; $i++) {
			// Read next Central dir entry
			@rewind($this->_zip_fd);
			if (@fseek($this->_zip_fd, $v_pos_entry)) {
				$this->_closeFd();

				$this->_errorLog(ARCHIVE_ZIP_ERR_INVALID_ARCHIVE_ZIP,
					'Invalid archive size');

				return tx_em_Tools_Unzip::errorCode();
			}

			// Read the file header
			$v_header = array();
			if (($v_result = $this->_readCentralFileHeader($v_header)) != 1) {
				$this->_closeFd();

				return $v_result;
			}

			// Store the index
			$v_header['index'] = $i;

			// Store the file position
			$v_pos_entry = ftell($this->_zip_fd);


			// Go to the file position
			@rewind($this->_zip_fd);
			if (@fseek($this->_zip_fd, $v_header['offset'])) {
				// Close the zip file
				$this->_closeFd();

				// Error log
				$this->_errorLog(ARCHIVE_ZIP_ERR_INVALID_ARCHIVE_ZIP, 'Invalid archive size');

				// Return
				return tx_em_Tools_Unzip::errorCode();
			}

			// Extracting the file
			if (($v_result = $this->_extractFile($v_header, $p_path, $p_remove_path, $p_remove_all_path, $p_params)) != 1) {
				// Close the zip file
				$this->_closeFd();

				return $v_result;
			}

			// Get the only interesting attributes
			if (($v_result = $this->_convertHeader2FileInfo($v_header, $p_file_list[$v_nb_extracted++])) != 1) {
				// Close the zip file
				$this->_closeFd();

				return $v_result;
			}
		}

		// Close the zip file
		$this->_closeFd();

		// Return
		return $v_result;
	}

	/**
	 * tx_em_Tools_Unzip::_extractFile()
	 *
	 * { Description }
	 *
	 */
	function _extractFile(&$p_entry, $p_path, $p_remove_path, $p_remove_all_path, &$p_params) {
		$v_result = 1;

		// Read the file header
		$v_header = '';
		if (($v_result = $this->_readFileHeader($v_header)) != 1) {
			// Return
			return $v_result;
		}


		// Check that the file header is coherent with $p_entry info
		// TBC

		// Look for all path to remove
		if ($p_remove_all_path == TRUE) {
			// Get the basename of the path
			$p_entry['filename'] = basename($p_entry['filename']);
		}

			// Look for path to remove
		else {
			if ($p_remove_path != "") {
				//if (strcmp($p_remove_path, $p_entry['filename'])==0)
				if ($this->_tool_PathInclusion($p_remove_path, $p_entry['filename']) == 2) {

					// Change the file status
					$p_entry['status'] = "filtered";

					// Return
					return $v_result;
				}

				$p_remove_path_size = strlen($p_remove_path);
				if (substr($p_entry['filename'], 0, $p_remove_path_size) == $p_remove_path) {

					// Remove the path
					$p_entry['filename'] = substr($p_entry['filename'], $p_remove_path_size);

				}
			}
		}

			// added by TYPO3 secteam to check for invalid paths
		if (!t3lib_div::validPathStr($p_entry['filename'])) {
				return $v_result;
		}

		// Add the path
		if ($p_path != '') {
			$p_entry['filename'] = $p_path . "/" . $p_entry['filename'];
		}

		// Look for pre-extract callback
		if ((isset($p_params[ARCHIVE_ZIP_PARAM_PRE_EXTRACT]))
				&& ($p_params[ARCHIVE_ZIP_PARAM_PRE_EXTRACT] != '')) {

			// Generate a local information
			$v_local_header = array();
			$this->_convertHeader2FileInfo($p_entry, $v_local_header);

			// Call the callback
			// Here I do not use call_user_func() because I need to send a reference to the
			// header.
			eval('$v_result = ' . $p_params[ARCHIVE_ZIP_PARAM_PRE_EXTRACT] . '(ARCHIVE_ZIP_PARAM_PRE_EXTRACT, $v_local_header);');
			if ($v_result == 0) {
				// Change the file status
				$p_entry['status'] = "skipped";
				$v_result = 1;
			}

			// Update the informations
			// Only some fields can be modified
			$p_entry['filename'] = $v_local_header['filename'];
		}

		// Trace

		// Look if extraction should be done
		if ($p_entry['status'] == 'ok') {

			// Look for specific actions while the file exist
			if (file_exists($p_entry['filename'])) {
				// Look if file is a directory
				if (is_dir($p_entry['filename'])) {
					// Change the file status
					$p_entry['status'] = "already_a_directory";

					// Return
					//return $v_result;
				}
					// Look if file is write protected
				else {
					if (!is_writeable($p_entry['filename'])) {
						// Change the file status
						$p_entry['status'] = "write_protected";

						// Return
						//return $v_result;
					}

						// Look if the extracted file is older
					else {
						if (filemtime($p_entry['filename']) > $p_entry['mtime']) {
							// Change the file status
							$p_entry['status'] = "newer_exist";

							// Return
							//return $v_result;
						}
					}
				}
			}

				// Check the directory availability and create it if necessary
			else {
				if ((($p_entry['external'] & 0x00000010) == 0x00000010) || (substr($p_entry['filename'], -1) == '/')) {
					$v_dir_to_check = $p_entry['filename'];
				}
				else {
					if (!strstr($p_entry['filename'], "/")) {
						$v_dir_to_check = "";
					}
					else
					{
						$v_dir_to_check = dirname($p_entry['filename']);
					}
				}

				if (($v_result = $this->_dirCheck($v_dir_to_check, (($p_entry['external'] & 0x00000010) == 0x00000010))) != 1) {
					// Change the file status
					$p_entry['status'] = "path_creation_fail";

					// Return
					//return $v_result;
					$v_result = 1;
				}
			}
		}

		// Look if extraction should be done
		if ($p_entry['status'] == 'ok') {
			// Do the extraction (if not a folder)
			if (!(($p_entry['external'] & 0x00000010) == 0x00000010)) {
				// Look for not compressed file
				if ($p_entry['compressed_size'] == $p_entry['size']) {
					// Opening destination file
					if (($v_dest_file = @fopen($p_entry['filename'], 'wb')) == 0) {
						// Change the file status
						$p_entry['status'] = "write_error";

						// Return
						return $v_result;
					}


					// Read the file by ARCHIVE_ZIP_READ_BLOCK_SIZE octets blocks
					$v_size = $p_entry['compressed_size'];
					while ($v_size != 0) {
						$v_read_size = ($v_size < ARCHIVE_ZIP_READ_BLOCK_SIZE ? $v_size : ARCHIVE_ZIP_READ_BLOCK_SIZE);
						$v_buffer = fread($this->_zip_fd, $v_read_size);
						$v_binary_data = pack('a' . $v_read_size, $v_buffer);
						@fwrite($v_dest_file, $v_binary_data, $v_read_size);
						$v_size -= $v_read_size;
					}

					// Closing the destination file
					fclose($v_dest_file);

					// Change the file mtime
					touch($p_entry['filename'], $p_entry['mtime']);
				} else {
					// Trace

					// Opening destination file
					if (($v_dest_file = @fopen($p_entry['filename'], 'wb')) == 0) {

						// Change the file status
						$p_entry['status'] = "write_error";

						return $v_result;
					}


					// Read the compressed file in a buffer (one shot)
					$v_buffer = @fread($this->_zip_fd, $p_entry['compressed_size']);

					// Decompress the file
					$v_file_content = gzinflate($v_buffer);
					unset($v_buffer);

					// Write the uncompressed data
					@fwrite($v_dest_file, $v_file_content, $p_entry['size']);
					unset($v_file_content);

					// Closing the destination file
					@fclose($v_dest_file);

					// Change the file mtime
					@touch($p_entry['filename'], $p_entry['mtime']);
				}

				// Look for chmod option
				if ((isset($p_params[ARCHIVE_ZIP_PARAM_SET_CHMOD]))
						&& ($p_params[ARCHIVE_ZIP_PARAM_SET_CHMOD] != 0)) {

					// Change the mode of the file
					chmod($p_entry['filename'], $p_params[ARCHIVE_ZIP_PARAM_SET_CHMOD]);
				}

			}
		}

		// Look for post-extract callback
		if ((isset($p_params[ARCHIVE_ZIP_PARAM_POST_EXTRACT]))
				&& ($p_params[ARCHIVE_ZIP_PARAM_POST_EXTRACT] != '')) {

			// Generate a local information
			$v_local_header = array();
			$this->_convertHeader2FileInfo($p_entry, $v_local_header);

			// Call the callback
			// Here I do not use call_user_func() because I need to send a reference to the
			// header.
			eval('$v_result = ' . $p_params[ARCHIVE_ZIP_PARAM_POST_EXTRACT] . '(ARCHIVE_ZIP_PARAM_POST_EXTRACT, $v_local_header);');
		}

		// Return
		return $v_result;
	}

	/**
	 * tx_em_Tools_Unzip::_readFileHeader()
	 *
	 * { Description }
	 *
	 */
	function _readFileHeader(&$p_header) {
		$v_result = 1;

		// Read the 4 bytes signature
		$v_binary_data = @fread($this->_zip_fd, 4);
		$v_data = unpack('Vid', $v_binary_data);

		// Check signature
		if ($v_data['id'] != 0x04034b50) {

			// Error log
			$this->_errorLog(ARCHIVE_ZIP_ERR_BAD_FORMAT, 'Invalid archive structure');

			// Return
			return tx_em_Tools_Unzip::errorCode();
		}

		// Read the first 42 bytes of the header
		$v_binary_data = fread($this->_zip_fd, 26);

		// Look for invalid block size
		if (strlen($v_binary_data) != 26) {
			$p_header['filename'] = "";
			$p_header['status'] = "invalid_header";

			// Error log
			$this->_errorLog(ARCHIVE_ZIP_ERR_BAD_FORMAT, "Invalid block size : " . strlen($v_binary_data));

			// Return
			return tx_em_Tools_Unzip::errorCode();
		}

		// Extract the values
		$v_data = unpack('vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len', $v_binary_data);

		// Get filename
		$p_header['filename'] = fread($this->_zip_fd, $v_data['filename_len']);

		// Get extra_fields
		if ($v_data['extra_len'] != 0) {
			$p_header['extra'] = fread($this->_zip_fd, $v_data['extra_len']);
		}
		else {
			$p_header['extra'] = '';
		}

		// Extract properties
		$p_header['compression'] = $v_data['compression'];
		$p_header['size'] = $v_data['size'];
		$p_header['compressed_size'] = $v_data['compressed_size'];
		$p_header['crc'] = $v_data['crc'];
		$p_header['flag'] = $v_data['flag'];

		// Recuperate date in UNIX format
		$p_header['mdate'] = $v_data['mdate'];
		$p_header['mtime'] = $v_data['mtime'];
		if ($p_header['mdate'] && $p_header['mtime']) {
			// Extract time
			$v_hour = ($p_header['mtime'] & 0xF800) >> 11;
			$v_minute = ($p_header['mtime'] & 0x07E0) >> 5;
			$v_seconde = ($p_header['mtime'] & 0x001F) * 2;

			// Extract date
			$v_year = (($p_header['mdate'] & 0xFE00) >> 9) + 1980;
			$v_month = ($p_header['mdate'] & 0x01E0) >> 5;
			$v_day = $p_header['mdate'] & 0x001F;

			// Get UNIX date format
			$p_header['mtime'] = mktime($v_hour, $v_minute, $v_seconde, $v_month, $v_day, $v_year);

		} else {
			$p_header['mtime'] = $GLOBALS['EXEC_TIME'];
		}

		// Other informations

		// TBC
		//for(reset($v_data); $key = key($v_data); next($v_data)) {
		//}

		// Set the stored filename
		$p_header['stored_filename'] = $p_header['filename'];

		// Set the status field
		$p_header['status'] = "ok";

		// Return
		return $v_result;
	}

	/**
	 * tx_em_Tools_Unzip::_readCentralFileHeader()
	 *
	 * { Description }
	 *
	 */
	function _readCentralFileHeader(&$p_header) {
		$v_result = 1;

		// Read the 4 bytes signature
		$v_binary_data = @fread($this->_zip_fd, 4);
		$v_data = unpack('Vid', $v_binary_data);

		// Check signature
		if ($v_data['id'] != 0x02014b50) {

			// Error log
			$this->_errorLog(ARCHIVE_ZIP_ERR_BAD_FORMAT, 'Invalid archive structure');

			// Return
			return tx_em_Tools_Unzip::errorCode();
		}

		// Read the first 42 bytes of the header
		$v_binary_data = fread($this->_zip_fd, 42);

		// Look for invalid block size
		if (strlen($v_binary_data) != 42) {
			$p_header['filename'] = "";
			$p_header['status'] = "invalid_header";

			// Error log
			$this->_errorLog(ARCHIVE_ZIP_ERR_BAD_FORMAT, "Invalid block size : " . strlen($v_binary_data));

			// Return
			return tx_em_Tools_Unzip::errorCode();
		}

		// Extract the values
		$p_header = unpack('vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset', $v_binary_data);

		// Get filename
		if ($p_header['filename_len'] != 0) {
			$p_header['filename'] = fread($this->_zip_fd, $p_header['filename_len']);
		}
		else
		{
			$p_header['filename'] = '';
		}

		// Get extra
		if ($p_header['extra_len'] != 0) {
			$p_header['extra'] = fread($this->_zip_fd, $p_header['extra_len']);
		}
		else
		{
			$p_header['extra'] = '';
		}

		// Get comment
		if ($p_header['comment_len'] != 0) {
			$p_header['comment'] = fread($this->_zip_fd, $p_header['comment_len']);
		}
		else
		{
			$p_header['comment'] = '';
		}

		// Extract properties

		// Recuperate date in UNIX format
		if ($p_header['mdate'] && $p_header['mtime']) {
			// Extract time
			$v_hour = ($p_header['mtime'] & 0xF800) >> 11;
			$v_minute = ($p_header['mtime'] & 0x07E0) >> 5;
			$v_seconde = ($p_header['mtime'] & 0x001F) * 2;

			// Extract date
			$v_year = (($p_header['mdate'] & 0xFE00) >> 9) + 1980;
			$v_month = ($p_header['mdate'] & 0x01E0) >> 5;
			$v_day = $p_header['mdate'] & 0x001F;

			// Get UNIX date format
			$p_header['mtime'] = mktime($v_hour, $v_minute, $v_seconde, $v_month, $v_day, $v_year);

		} else {
			$p_header['mtime'] = $GLOBALS['EXEC_TIME'];
		}

		// Set the stored filename
		$p_header['stored_filename'] = $p_header['filename'];

		// Set default status to ok
		$p_header['status'] = 'ok';

		// Look if it is a directory
		if (substr($p_header['filename'], -1) == '/') {
			$p_header['external'] = 0x41FF0010;
		}


		// Return
		return $v_result;
	}

	/**
	 * tx_em_Tools_Unzip::_readEndCentralDir()
	 *
	 * { Description }
	 *
	 */
	function _readEndCentralDir(&$p_central_dir) {
		$v_result = 1;

		// Go to the end of the zip file
		$v_size = filesize($this->_zipname);
		@fseek($this->_zip_fd, $v_size);
		if (@ftell($this->_zip_fd) != $v_size) {
			$this->_errorLog(ARCHIVE_ZIP_ERR_BAD_FORMAT,
					'Unable to go to the end of the archive \''
							. $this->_zipname . '\'');
			return tx_em_Tools_Unzip::errorCode();
		}

		// First try : look if this is an archive with no commentaries
		// (most of the time)
		// in this case the end of central dir is at 22 bytes of the file end
		$v_found = 0;
		if ($v_size > 26) {
			@fseek($this->_zip_fd, $v_size - 22);
			if (($v_pos = @ftell($this->_zip_fd)) != ($v_size - 22)) {
				$this->_errorLog(ARCHIVE_ZIP_ERR_BAD_FORMAT,
						'Unable to seek back to the middle of the archive \''
								. $this->_zipname . '\'');
				return tx_em_Tools_Unzip::errorCode();
			}

			// Read for bytes
			$v_binary_data = @fread($this->_zip_fd, 4);
			$v_data = unpack('Vid', $v_binary_data);

			// Check signature
			if ($v_data['id'] == 0x06054b50) {
				$v_found = 1;
			}

			$v_pos = ftell($this->_zip_fd);
		}

		// Go back to the maximum possible size of the Central Dir End Record
		if (!$v_found) {
			$v_maximum_size = 65557; // 0xFFFF + 22;
			if ($v_maximum_size > $v_size) {
				$v_maximum_size = $v_size;
			}
			@fseek($this->_zip_fd, $v_size - $v_maximum_size);
			if (@ftell($this->_zip_fd) != ($v_size - $v_maximum_size)) {
				$this->_errorLog(ARCHIVE_ZIP_ERR_BAD_FORMAT,
						'Unable to seek back to the middle of the archive \''
								. $this->_zipname . '\'');
				return tx_em_Tools_Unzip::errorCode();
			}

			// Read byte per byte in order to find the signature
			$v_pos = ftell($this->_zip_fd);
			$v_bytes = 0x00000000;
			while ($v_pos < $v_size) {
				// Read a byte
				$v_byte = @fread($this->_zip_fd, 1);

				//  Add the byte
				$v_bytes = ($v_bytes << 8) | Ord($v_byte);

				// Compare the bytes
				if ($v_bytes == 0x504b0506) {
					$v_pos++;
					break;
				}

				$v_pos++;
			}

			// Look if not found end of central dir
			if ($v_pos == $v_size) {
				$this->_errorLog(ARCHIVE_ZIP_ERR_BAD_FORMAT,
					"Unable to find End of Central Dir Record signature");
				return tx_em_Tools_Unzip::errorCode();
			}
		}

		// Read the first 18 bytes of the header
		$v_binary_data = fread($this->_zip_fd, 18);

		// Look for invalid block size
		if (strlen($v_binary_data) != 18) {
			$this->_errorLog(ARCHIVE_ZIP_ERR_BAD_FORMAT,
					"Invalid End of Central Dir Record size : "
							. strlen($v_binary_data));
			return tx_em_Tools_Unzip::errorCode();
		}

		// Extract the values
		$v_data = unpack('vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_size', $v_binary_data);

		// Check the global size
		if (($v_pos + $v_data['comment_size'] + 18) != $v_size) {
			$this->_errorLog(ARCHIVE_ZIP_ERR_BAD_FORMAT,
				"Fail to find the right signature");
			return tx_em_Tools_Unzip::errorCode();
		}

		// Get comment
		if ($v_data['comment_size'] != 0) {
			$p_central_dir['comment'] = fread($this->_zip_fd, $v_data['comment_size']);
		}
		else
		{
			$p_central_dir['comment'] = '';
		}

		$p_central_dir['entries'] = $v_data['entries'];
		$p_central_dir['disk_entries'] = $v_data['disk_entries'];
		$p_central_dir['offset'] = $v_data['offset'];
		$p_central_dir['size'] = $v_data['size'];
		$p_central_dir['disk'] = $v_data['disk'];
		$p_central_dir['disk_start'] = $v_data['disk_start'];

		// Return
		return $v_result;
	}

	/**
	 * tx_em_Tools_Unzip::_dirCheck()
	 *
	 * { Description }
	 *
	 * @param [type] $p_is_dir
	 */
	function _dirCheck($p_dir, $p_is_dir = false) {
		$v_result = 1;

		// Remove the final '/'
		if (($p_is_dir) && (substr($p_dir, -1) == '/')) {
			$p_dir = substr($p_dir, 0, strlen($p_dir) - 1);
		}

		// Check the directory availability
		if ((is_dir($p_dir)) || ($p_dir == "")) {
			return 1;
		}

		// Extract parent directory
		$p_parent_dir = dirname($p_dir);

		// Just a check
		if ($p_parent_dir != $p_dir) {
			// Look for parent directory
			if ($p_parent_dir != "") {
				if (($v_result = $this->_dirCheck($p_parent_dir)) != 1) {
					return $v_result;
				}
			}
		}

		// Create the directory
		if (!@mkdir($p_dir, 0777)) {
			$this->_errorLog(ARCHIVE_ZIP_ERR_DIR_CREATE_FAIL,
				"Unable to create directory '$p_dir'");
			return tx_em_Tools_Unzip::errorCode();
		}

		// Return
		return $v_result;
	}

	/**
	 * tx_em_Tools_Unzip::_check_parameters()
	 *
	 * { Description }
	 *
	 * @param integer $p_error_code
	 * @param string $p_error_string
	 */
	function _check_parameters(&$p_params, $p_default) {

		// Check that param is an array
		if (!is_array($p_params)) {
			$this->_errorLog(ARCHIVE_ZIP_ERR_INVALID_PARAMETER,
				'Unsupported parameter, waiting for an array');
			return tx_em_Tools_Unzip::errorCode();
		}

		// Check that all the params are valid
		for (reset($p_params); list($v_key, $v_value) = each($p_params);) {
			if (!isset($p_default[$v_key])) {
				$this->_errorLog(ARCHIVE_ZIP_ERR_INVALID_PARAMETER,
						'Unsupported parameter with key \'' . $v_key . '\'');

				return tx_em_Tools_Unzip::errorCode();
			}
		}

		// Set the default values
		for (reset($p_default); list($v_key, $v_value) = each($p_default);) {
			if (!isset($p_params[$v_key])) {
				$p_params[$v_key] = $p_default[$v_key];
			}
		}

		// Check specific parameters
		$v_callback_list = array('callback_pre_add', 'callback_post_add',
			'callback_pre_extract', 'callback_post_extract');
		for ($i = 0; $i < sizeof($v_callback_list); $i++) {
			$v_key = $v_callback_list[$i];
			if ((isset($p_params[$v_key])) && ($p_params[$v_key] != '')) {
				if (!function_exists($p_params[$v_key])) {
					$this->_errorLog(ARCHIVE_ZIP_ERR_INVALID_PARAM_VALUE,
							"Callback '" . $p_params[$v_key]
									. "()' is not an existing function for "
									. "parameter '" . $v_key . "'");
					return tx_em_Tools_Unzip::errorCode();
				}
			}
		}

		return (1);
	}

	/**
	 * tx_em_Tools_Unzip::_errorLog()
	 *
	 * { Description }
	 *
	 * @param integer $p_error_code
	 * @param string $p_error_string
	 */
	function _errorLog($p_error_code = 0, $p_error_string = '') {
		$this->_error_code = $p_error_code;
		$this->_error_string = $p_error_string;
	}

	/**
	 * tx_em_Tools_Unzip::_errorReset()
	 *
	 * { Description }
	 *
	 */
	function _errorReset() {
		$this->_error_code = 1;
		$this->_error_string = '';
	}

	/**
	 * _tool_PathReduction()
	 *
	 * { Description }
	 *
	 */
	function _tool_PathReduction($p_dir) {
		$v_result = "";

		// Look for not empty path
		if ($p_dir != "") {
			// Explode path by directory names
			$v_list = explode("/", $p_dir);

			// Study directories from last to first
			for ($i = sizeof($v_list) - 1; $i >= 0; $i--) {
				// Look for current path
				if ($v_list[$i] == ".") {
					// Ignore this directory
					// Should be the first $i=0, but no check is done
				} else {
					if ($v_list[$i] == "..") {
						// Ignore it and ignore the $i-1
						$i--;
					} else {
						if (($v_list[$i] == "") && ($i != (sizeof($v_list) - 1)) && ($i != 0)) {
							// Ignore only the double '//' in path,
							// but not the first and last '/'
						} else {
							$v_result = $v_list[$i] . ($i != (sizeof($v_list) - 1) ? "/" . $v_result : "");
						}
					}
				}
			}
		}

		// Return
		return $v_result;
	}

	/**
	 * _tool_PathInclusion()
	 *
	 * { Description }
	 *
	 */
	function _tool_PathInclusion($p_dir, $p_path) {
		$v_result = 1;

		// Explode dir and path by directory separator
		$v_list_dir = explode("/", $p_dir);
		$v_list_dir_size = sizeof($v_list_dir);
		$v_list_path = explode("/", $p_path);
		$v_list_path_size = sizeof($v_list_path);

		// Study directories paths
		$i = 0;
		$j = 0;
		while (($i < $v_list_dir_size) && ($j < $v_list_path_size) && ($v_result)) {
			// Look for empty dir (path reduction)
			if ($v_list_dir[$i] == '') {
				$i++;
				continue;
			}
			if ($v_list_path[$j] == '') {
				$j++;
				continue;
			}

			// Compare the items
			if (($v_list_dir[$i] != $v_list_path[$j]) && ($v_list_dir[$i] != '') && ($v_list_path[$j] != '')) {
				$v_result = 0;
			}

			// Next items
			$i++;
			$j++;
		}

		// Look if everything seems to be the same
		if ($v_result) {
			// Skip all the empty items
			while (($j < $v_list_path_size) && ($v_list_path[$j] == '')) {
				$j++;
			}
			while (($i < $v_list_dir_size) && ($v_list_dir[$i] == '')) {
				$i++;
			}

			if (($i >= $v_list_dir_size) && ($j >= $v_list_path_size)) {
				// There are exactly the same
				$v_result = 2;
			} else {
				if ($i < $v_list_dir_size) {
					// The path is shorter than the dir
					$v_result = 0;
				}
			}
		}

		// Return
		return $v_result;
	}

	/**
	 * _tool_CopyBlock()
	 *
	 * { Description }
	 *
	 * @param integer $p_mode
	 */
	function _tool_CopyBlock($p_src, $p_dest, $p_size, $p_mode = 0) {
		$v_result = 1;

		if ($p_mode == 0) {
			while ($p_size != 0) {
				$v_read_size = ($p_size < ARCHIVE_ZIP_READ_BLOCK_SIZE
						? $p_size : ARCHIVE_ZIP_READ_BLOCK_SIZE);
				$v_buffer = @fread($p_src, $v_read_size);
				@fwrite($p_dest, $v_buffer, $v_read_size);
				$p_size -= $v_read_size;
			}
		} else {
			if ($p_mode == 1) {
				while ($p_size != 0) {
					$v_read_size = ($p_size < ARCHIVE_ZIP_READ_BLOCK_SIZE
							? $p_size : ARCHIVE_ZIP_READ_BLOCK_SIZE);
					$v_buffer = @gzread($p_src, $v_read_size);
					@fwrite($p_dest, $v_buffer, $v_read_size);
					$p_size -= $v_read_size;
				}
			} else {
				if ($p_mode == 2) {
					while ($p_size != 0) {
						$v_read_size = ($p_size < ARCHIVE_ZIP_READ_BLOCK_SIZE
								? $p_size : ARCHIVE_ZIP_READ_BLOCK_SIZE);
						$v_buffer = @fread($p_src, $v_read_size);
						@gzwrite($p_dest, $v_buffer, $v_read_size);
						$p_size -= $v_read_size;
					}
				}
				else {
					if ($p_mode == 3) {
						while ($p_size != 0) {
							$v_read_size = ($p_size < ARCHIVE_ZIP_READ_BLOCK_SIZE
									? $p_size : ARCHIVE_ZIP_READ_BLOCK_SIZE);
							$v_buffer = @gzread($p_src, $v_read_size);
							@gzwrite($p_dest, $v_buffer, $v_read_size);
							$p_size -= $v_read_size;
						}
					}
				}
			}
		}

		// Return
		return $v_result;
	}

	/**
	 * _tool_Rename()
	 *
	 * { Description }
	 *
	 */
	function _tool_Rename($p_src, $p_dest) {
		$v_result = 1;

		// Try to rename the files
		if (!@rename($p_src, $p_dest)) {

			// Try to copy & unlink the src
			if (!@copy($p_src, $p_dest)) {
				$v_result = 0;
			} else {
				if (!@unlink($p_src)) {
					$v_result = 0;
				}
			}
		}

		// Return
		return $v_result;
	}

	/**
	 * _tool_TranslateWinPath()
	 *
	 * { Description }
	 *
	 * @param [type] $p_remove_disk_letter
	 */
	function _tool_TranslateWinPath($p_path, $p_remove_disk_letter = TRUE) {
		if (stristr(php_uname(), 'windows')) {
			// Look for potential disk letter
			if (($p_remove_disk_letter)
					&& (($v_position = strpos($p_path, ':')) != false)) {
				$p_path = substr($p_path, $v_position + 1);
			}
			// Change potential windows directory separator
			if ((strpos($p_path, '\\') > 0) || (substr($p_path, 0, 1) == '\\')) {
				$p_path = strtr($p_path, '\\', '/');
			}
		}
		return $p_path;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/tools/class.tx_em_tools_unzip.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/em/classes/tools/class.tx_em_tools_unzip.php']);
}
?>