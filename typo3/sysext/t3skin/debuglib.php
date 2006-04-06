<?php
/************************************************ 
** Title.........: PHP4+ Debug Helper
** Author........: Thomas Sch��ler <code at atomar dot de> 
** Filename......: debuglib.php(s)
** Last changed..: 03.04.2004 12:09
** License.......: Free to use. Postcardware ;)
**
*************************************************
** 
** Functions in this library:
** 
** print_a( array array [,bool show object vars] [,int returnmode] )
**
**   prints arrays in a readable, understandable form.
**   if mode is defined the function returns the output instead of
**   printing it to the browser
**
**   print_a( $array, #, 1 ) shows also object properties
**   print_a( $array, 1, # ) returns the table as a string instead of printing it to the output buffer
**   print_a( $array, 2, # ) opens the table in a new window.
**   !NEW! print_a($array, string, #) opens the output in individual windows indentied by the string.
**   print_a( $array, 3, # ) opens a new browser window with a serialized version of your array (save as a textfile and can it for later use ;).
**   
** show_vars( [bool verbose] [, bool show_object_vars ] [, int limit] )
**
**   use this function on the bottom of your script to see all
**   superglobals and global variables in your script in a nice
**   formated way
**   
**   show_vars() without parameter shows $_GET, $_POST, $_SESSION,
**   $_FILES and all global variables you've defined in your script
**
**   show_vars(1) shows $_SERVER and $_ENV in addition
**   show_vars(#,1) shows also object properties
**   show_vars(#, #, 15) shows only the first 15 entries in a numerical keyed array (or an array with more than 50 entries)  ( standard is 5 )
**   show_vars(#, #, 0) shows all entries
**
**
**   
** ** print_result( result_handle ) **
**   prints a mysql_result set returned by mysql_query() as a table
**   this function is work in progress! use at your own risk
**
**
** Happy debugging and feel free to email me your comments.
**
**
**
** History: (starting at 2003-02-24)
**
**   - added tooltips to the td's showing the type of keys and values (thanks Itomic)
** 2003-07-16
**   - pre() function now trims trailing tabulators
** 2003-08-01
**   - silly version removed.. who needs a version for such a thing ;)
** 2003-09-24
**   - changed the parameters of print_a() a bit
**     see above
**   - addet the types NULL and bolean to print_a()
**   - print_a() now replaces trailing spaces in string values with red underscores
** 2003-09-24 (later that day ;)
**   - oops.. fixed the print_a() documentation.. parameter order was wrong
**   - added mode 3 to the second parameter 
** 2003-09-25
**   - added a limit parameter to the show_vars() and print_a() functions
**     default for show_vars() is 5
**     show_vars(#,#, n) changes that (0 means show all entries)
**     print_a() allways shows all entries by default
**     print_a(#,#,#, n) changes that
**     
**     this parameter is used to limit the output of arrays with a numerical index (like long lists of similiar elements)
**     i added this option for performance reasons
**     it has no effect on arrays where one ore more keys are not number-strings
** 2003-09-27
**   - reworked the pre() and _remove_exessive_leading_tabs() functions
**     they now work like they should :)
**   - some cosmetic changes
** 2003-10-28
**   - fixed multiline string display
** 2003-11-14
**   - argh! uploaded the wrong version :/ ... fixed.. sorry
** 2003-11-16
**   - fixed a warning triggered by _only_numeric_keys()
**     thanx Peter Valdemar :)
**   - fixed a warning when print_a was called directly on an object
**     thanx Hilton :)
** 2003-12-01
**   - added slashes in front of the print_a(#,3) output
** 2004-03-17
**   - fixed a problem when print_a(#,2) was called on an array containing newlines
** 2004-03-26
**   - added a variation of mode 2 for print_a().
**     when a string is passed as the second parameter, a new window with the string as prefix gets opened for every differend string.. #TODO_COMMENT#
************************************************/


# This file must be the first include on your page.

/* used for tracking of generation-time */
{
	$MICROTIME_START = microtime();
	@$GLOBALS_initial_count = count($GLOBALS);
}

/************************************************ 
** print_a class and helper function
** prints out an array in a more readable way
** than print_r()
**
** based on the print_a() function from
** Stephan Pirson (Saibot)
************************************************/

class Print_a_class {
	
	# this can be changed to TRUE if you want
	var $look_for_leading_tabs = FALSE;

	var $output;
	var $iterations;
	var $key_bg_color = '1E32C8';
	var $value_bg_color = 'DDDDEE';
	var $fontsize = '8pt';
	var $keyalign = 'center';
	var $fontfamily = 'Verdana';
	var $show_object_vars;
	var $limit;
	
	// function Print_a_class() {}
	
	# recursive function!
	
	/* this internal function looks if the given array has only numeric values as  */
	function _only_numeric_keys( $array ) {
		$test = TRUE;
		if (is_array($array)) {

			foreach( array_keys( $array ) as $key ) {
				if( !is_numeric( $key ) )	$test = FALSE; /* #TODO# */
			}
			return $test;
		} else {

			return FALSE;

		}


	}
	
	function _handle_whitespace( $string ) {
		$string = str_replace(' ', '&nbsp;', $string);
		$string = preg_replace(array('/&nbsp;$/', '/^&nbsp;/'), '<span style="color:red;">_</span>', $string); /* replace spaces at the start/end of the STRING with red underscores */
		$string = preg_replace('/\t/', '&nbsp;&nbsp;<span style="border-bottom:#'.$this->value_bg_color.' solid 1px;">&nbsp;</span>', $string); /* replace tabulators with '_ _' */
		return $string;
	}
	
	function print_a($array, $iteration = FALSE, $key_bg_color = FALSE) {
		$key_bg_color or $key_bg_color = $this->key_bg_color;
			
		# lighten up the background color for the key td's =)
		if( $iteration ) {
			for($i=0; $i<6; $i+=2) {
				$c = substr( $key_bg_color, $i, 2 );
				$c = hexdec( $c );
				( $c += 15 ) > 255 and $c = 255;
				isset($tmp_key_bg_color) or $tmp_key_bg_color = '';
				$tmp_key_bg_color .= sprintf( "%02X", $c );
			}
			$key_bg_color = $tmp_key_bg_color;
		}
		
		# build a single table ... may be nested
		$this->output .= '<table style="border:none;" cellspacing="1">';
		$only_numeric_keys = ($this->_only_numeric_keys( $array ) || count( $array ) > 50);
		$i = 0;
		foreach( $array as $key => $value ) {
			
			if( $only_numeric_keys && $this->limit && $this->limit == $i++ ) break; /* if print_a() was called with a fourth parameter #TODO# */
			
			$value_style_box = 'color:black;';
			$key_style = 'color:white;';
			
			$type = gettype( $value );
			# print $type.'<br />';
			
			# change the color and format of the value and set the values title
			$type_title = $type;
			$value_style_content = '';
			switch( $type ) {
				case 'array':
					if( empty( $value ) ) $type_title = 'empty array';
					break;
				
				case 'object':
					$key_style = 'color:#FF9B2F;';
					break;
				
				case 'integer':
					$value_style_box = 'color:green;';
					break;
				
				case 'double':
					$value_style_box = 'color:blue;';
					break;
				
				case 'boolean':
					if( $value == TRUE ) {
						$value_style_box = 'color:#D90081;';
					} else {
						$value_style_box = 'color:#84009F;';
					}
					break;
					
				case 'NULL':
					$value_style_box = 'color:darkorange;';
					break;
				
				case 'string':
					if( $value == '' ) {
						
						$value_style_box = 'color:darkorange;';
						$value = "''";
						$type_title = 'empty string';
						
					} else {
						
						$value_style_box = 'color:black;';
						$value = htmlspecialchars( $value );
						if( $this->look_for_leading_tabs && _check_for_leading_tabs( $value ) ) {
							$value = _remove_exessive_leading_tabs( $value );
						}
						$value = $this->_handle_whitespace( $value );
						$value = nl2br($value);
						
						/* use different color for string background */
						if(strstr($value, "\n")) $value_style_content = 'background:#ECEDFE;';
						
					}
					break;
			}

			$this->output .= '<tr>';
			$this->output .= '<td nowrap="nowrap" align="'.$this->keyalign.'" style="padding:0px 3px 0px 3px;background-color:#'.$key_bg_color.';'.$key_style.';font:bold '.$this->fontsize.' '.$this->fontfamily.';" title="'.gettype( $key ).'['.$type_title.']">';
			$this->output .= $this->_handle_whitespace( $key );
			$this->output .= '</td>';
			$this->output .= '<td nowrap="nowrap" style="background-color:#'.$this->value_bg_color.';font: '.$this->fontsize.' '.$this->fontfamily.'; color:black;">';

			
			# value output
			if($type == 'array' && preg_match('/#RAS/', $key) ) { /* only used for special recursive array constructs which i use sometimes */
				$this->output .= '<div style="'.$value_style_box.'">recursion!</div>';
			} elseif($type == 'array') {
				if( ! empty( $value ) ) {
					$this->print_a( $value, TRUE, $key_bg_color );
				} else {
					$this->output .= '<span style="color:darkorange;">[]</span>';	
				}
			} elseif($type == 'object') {
				if( $this->show_object_vars ) {
					$this->print_a( get_object_vars( $value ), TRUE, $key_bg_color );
				} else {
					$this->output .= '<div style="'.$value_style_box.'">OBJECT</div>';
				}
			} elseif($type == 'boolean') {
				$this->output .= '<div style="'.$value_style_box.'" title="'.$type.'">'.($value ? 'TRUE' : 'FALSE').'</div>';
			} elseif($type == 'NULL') {
				$this->output .= '<div style="'.$value_style_box.'" title="'.$type.'">NULL</div>';
			} else {
				$this->output .= '<div style="'.$value_style_box.'" title="'.$type.'"><span style="'.$value_style_content.'">'.$value.'</span></div>';
			}
			
			$this->output .= '</td>';
			$this->output .= '</tr>';
		}
		
		$entry_count = count( $array );
		$skipped_count = $entry_count - $this->limit;
		
		if( $only_numeric_keys && $this->limit && count($array) > $this->limit) {
			$this->output .= '<tr title="showing '.$this->limit.' of '.$entry_count.' entries in this array"><td style="text-align:right;color:darkgray;background-color:#'.$key_bg_color.';font:bold '.$this->fontsize.' '.$this->fontfamily.';">...</td><td style="background-color:#'.$this->value_bg_color.';font:'.$this->fontsize.' '.$this->fontfamily.';color:darkgray;">['.$skipped_count.' skipped]</td></tr>';
		}
		$this->output .= '</table>';
	}
}

# helper function.. calls print_a() inside the print_a_class
function print_a( $array, $mode = 0, $show_object_vars = FALSE, $limit = FALSE ) {
	$output = '';
	
	if( is_array( $array ) or is_object( $array ) ) {
		
		if( empty( $array ) ) {
			$output .= '<span style="color:red;font-size:small;">print_a( empty array )</span>';
		}
		
		$pa = &new Print_a_class;
		$show_object_vars and $pa->show_object_vars = TRUE;
		if( $limit ) {
			$pa->limit = $limit;
			// $output .= '<span style="color:red;">showing only '.$limit.' entries for arrays with numeric keys</span>';
		}
		
		if ( is_object($array) ) {

	      $pa->print_a( get_object_vars($array) );

		} else {

	      $pa->print_a( $array );
		}
		
		# $output = $pa->output; unset($pa);
		$output .= $pa->output;
	} else {
		$output .= '<span style="color:red;font-size:small;">print_a( '.gettype( $array ).' )</span>';
	}
	
	if($mode === 0 || $mode == NULL || $mode == FALSE) {
		print $output;
		return TRUE;
	}

	if($mode == 1) {
		return $output;
	}
	
	if(is_string($mode) || $mode == 2 ) {
		$debugwindow_origin = $_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
		print '
			<script type="text/javascript" language="JavaScript">
				var debugwindow; 	
				debugwindow = window.open("", "T_'.md5($_SERVER['HTTP_HOST']).(is_string($mode)  ? $mode : '').'", "menubar=no,scrollbars=yes,resizable=yes,width=640,height=480");
				debugwindow.document.open();
				debugwindow.document.write("'.str_replace(array("\r\n", "\n", "\r"), '\n', addslashes($output)).'");
				debugwindow.document.close();
				debugwindow.document.title = "'.(is_string($mode) ? "($mode)" : '').' Debugwindow for : http://'.$debugwindow_origin.'";
				debugwindow.focus();
			</script>
		';
	}

	if($mode == 3) {
		print '
			<script type="text/javascript" language="JavaScript">
				var debugwindow; 	
				debugwindow = window.open("", "S_'.md5($_SERVER['HTTP_HOST']).'", "menubar=yes,scrollbars=yes,resizable=yes,width=640,height=480");
				debugwindow.document.open();
				debugwindow.document.write("unserialize(\''.str_replace("'", "\\'", addslashes( str_replace(array("\r\n", "\n", "\r"), '\n', serialize($array) ) ) ).'\');");
				debugwindow.document.close();
				debugwindow.document.title = "Debugwindow for : http://'.$debugwindow_origin.'";
				debugwindow.focus();
			</script>
		';
	}
	
}


// shows mysql-result as a table.. # not ready yet :(
function print_result($RESULT) {
	
	if(!$RESULT) return;
	
	
	if(mysql_num_rows($RESULT) < 1) return;
	$fieldcount = mysql_num_fields($RESULT);
	
	for($i=0; $i<$fieldcount; $i++) {
		$tables[mysql_field_table($RESULT, $i)]++;
	}
	
	print '
		<style type="text/css">
			.rs_tb_th {
				font-family: Verdana;
				font-size:9pt;
				font-weight:bold;
				color:white;
			}
			.rs_f_th {
				font-family:Verdana;
				font-size:7pt;
				font-weight:bold;
				color:white;
			}
			.rs_td {
				font-family:Verdana;
				font-size:7pt;
			}
		</style>
		<script type="text/javascript" language="JavaScript">
			var lastID;
			function highlight(id) {
				if(lastID) {
					lastID.style.color = "#000000";
					lastID.style.textDecoration = "none";
				}
				tdToHighlight = document.getElementById(id);
				tdToHighlight.style.color ="#FF0000";
				tdToHighlight.style.textDecoration = "underline";
				lastID = tdToHighlight;
			}
		</script>
	';

	print '<table bgcolor="#000000" cellspacing="1" cellpadding="1">';
	
	print '<tr>';
	foreach($tables as $tableName => $tableCount) {
		$col == '0054A6' ? $col = '003471' : $col = '0054A6';
		print '<th colspan="'.$tableCount.'" class="rs_tb_th" style="background-color:#'.$col.';">'.$tableName.'</th>';
	}
	print '</tr>';
	
	print '<tr>';
	for($i=0;$i < mysql_num_fields($RESULT);$i++) {
		$FIELD = mysql_field_name($RESULT, $i);
		$col == '0054A6' ? $col = '003471' : $col = '0054A6';
		print '<td align="center" bgcolor="#'.$col.'" class="rs_f_th">'.$FIELD.'</td>';
	}
	print '</tr>';

	mysql_data_seek($RESULT, 0);

	while($DB_ROW = mysql_fetch_array($RESULT, MYSQL_NUM)) {
		$pointer++;
		if($toggle) {
			$col1 = "E6E6E6";
			$col2 = "DADADA";
		} else {
			$col1 = "E1F0FF";
			$col2 = "DAE8F7";
		}
		$toggle = !$toggle;
		print '<tr id="ROW'.$pointer.'" onMouseDown="highlight(\'ROW'.$pointer.'\');">';
		foreach($DB_ROW as $value) {
			$col == $col1 ? $col = $col2 : $col = $col1;
			print '<td valign="top" bgcolor="#'.$col.'" class="rs_td" nowrap>'.nl2br($value).'</td>';
		}
		print '</tr>';
	}
	print '</table>';
	mysql_data_seek($RESULT, 0);
}


######################
# reset the millisec timer
#
function reset_script_runtime() {
	$GLOBALS['MICROTIME_START'] = microtime();
}


######################
# function returns the milliseconds passed
#
function script_runtime() {
	$MICROTIME_END		= microtime();
	$MICROTIME_START	= explode(' ', $GLOBALS['MICROTIME_START']);
	$MICROTIME_END		= explode(' ', $MICROTIME_END);
	$GENERATIONSEC		= $MICROTIME_END[1] - $MICROTIME_START[1];
	$GENERATIONMSEC	= $MICROTIME_END[0] - $MICROTIME_START[0];
	$GENERATIONTIME	= substr($GENERATIONSEC + $GENERATIONMSEC, 0, 8);
	
	return (float) $GENERATIONTIME;
}


######################
# function shows all superglobals and script defined global variables
# show_vars() without the first parameter shows all superglobals except $_ENV and $_SERVER
# show_vars(1) shows all
# show_vars(#,1) shows object properties in addition
#
function show_vars($show_all_vars = FALSE, $show_object_vars = FALSE, $limit = 5) {
	if($limit === 0) $limit = FALSE;
	
	function _script_globals() {
		global $GLOBALS_initial_count;
	
		$varcount = 0;
	
		foreach($GLOBALS as $GLOBALS_current_key => $GLOBALS_current_value) {
			if(++$varcount > $GLOBALS_initial_count) {
				/* die wollen wir nicht! */
				if ($GLOBALS_current_key != 'HTTP_SESSION_VARS' && $GLOBALS_current_key != '_SESSION') {
					$script_GLOBALS[$GLOBALS_current_key] = $GLOBALS_current_value;
				}
			}
		}
		
		unset($script_GLOBALS['GLOBALS_initial_count']);
		return $script_GLOBALS;
	}
	
	if(isset($GLOBALS['no_vars'])) return;
	
	$script_globals = _script_globals();
	print '
		<style type="text/css" media="screen">
			.vars-container {
				font-family: Verdana, Arial, Helvetica, Geneva, Swiss, SunSans-Regular, sans-serif;
				font-size: 8pt;
				padding:5px;
			}
			.varsname {
				font-weight:bold;
			}
			.showvars {
				background:white;
				border-style:dotted;
				border-width:1px;
				padding:2px;
				font-family: Verdana, Arial, Helvetica, Geneva, Swiss, SunSans-Regular, sans-serif;
				font-size:10pt;
				font-weight:bold;"
			}
		</style>
		<style type="text/css" media="print">
			.showvars {
				display:none;
				visibility:invisible;
			}
		</style>
	';

	print '<br />
		<div class="showvars">
		DEBUG <span style="color:red;font-weight:normal;font-size:9px;">(runtime: '.script_runtime().' sec)</span>
	';

	$vars_arr['script_globals'] = array('global script variables', '#7ACCC8');
	$vars_arr['_GET'] = array('$_GET', '#7DA7D9');
	$vars_arr['_POST'] = array('$_POST', '#F49AC1');
	$vars_arr['_FILES'] = array('$_FILES', '#82CA9C');
	$vars_arr['_SESSION'] = array('$_SESSION', '#FCDB26');
	$vars_arr['_COOKIE'] = array('$_COOKIE', '#A67C52');

	if($show_all_vars) {
		$vars_arr['_SERVER'] =  array('SERVER', '#A186BE');
		$vars_arr['_ENV'] =  array('ENV', '#7ACCC8');
	}

	foreach($vars_arr as $vars_name => $vars_data) {
		if($vars_name != 'script_globals') global $$vars_name;
		if($$vars_name) {
			print '<div class="vars-container" style="background-color:'.$vars_data[1].';"><span class="varsname">'.$vars_data[0].'</span><br />';
			print_a($$vars_name, NULL, $show_object_vars, $limit);
			print '</div>';
		}
	}
	print '</div>';
}


######################
# function prints/returns strings wrapped between <pre></pre>
#
function pre( $string, $return_mode = FALSE, $tabwidth = 3 ) {
	$tab = str_repeat('&nbsp;', $tabwidth);
	$string = preg_replace('/\t+/em', "str_repeat( ' ', strlen('\\0') * $tabwidth );", $string); /* replace all tabs with spaces */
	
	$out = '<pre>'.$string."</pre>\n";
	
	if($return_mode) {
		return $out;
	} else {
		print $out;
	}
}

function _check_for_leading_tabs( $string ) {
	return preg_match('/^\t/m', $string);
}

function _remove_exessive_leading_tabs( $string ) {
	/* remove whitespace lines at start of the string */
	$string = preg_replace('/^\s*\n/', '', $string);
	/* remove whitespace at end of the string */
	$string = preg_replace('/\s*$/', '', $string);
	
	# kleinste Anzahl von f�hrenden TABS z�hlen
	preg_match_all('/^\t+/', $string, $matches);
	$minTabCount = strlen(@min($matches[0]));
	
	# und entfernen
	$string = preg_replace('/^\t{'.$minTabCount.'}/m', '', $string);
				
	return $string;
}

?>
