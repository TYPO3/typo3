#
# Table structure for table 'tt_content'
#
CREATE TABLE tt_content (
		tx_testdatahandler_select text,
		tx_testdatahandler_select_dynamic text,
		tx_testdatahandler_group text,
		tx_testdatahandler_radio text,
		tx_testdatahandler_checkbox text,
		tx_testdatahandler_checkbox_with_eval text,
		tx_testdatahandler_input_minvalue text,
		tx_testdatahandler_input_minvalue_zero text,
		tx_testdatahandler_text_minvalue text,
		tx_testdatahandler_richttext_minvalue text
);

#
# Table structure for table 'tx_testdatahandler_element'
#
CREATE TABLE tx_testdatahandler_element (
	title tinytext NOT NULL
);
