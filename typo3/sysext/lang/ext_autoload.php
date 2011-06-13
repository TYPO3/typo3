<?php
/*
 * Register necessary class names with autoloader
 */
$extensionPath = dirname(__FILE__);
return array(
	'language' => $extensionPath . '/lang.php',
	'tx_lang_cache_abstract' => $extensionPath . '/classes/cache/class.tx_lang_cache_abstract.php',
	'tx_lang_cache_file' => $extensionPath . '/classes/cache/class.tx_lang_cache_file.php',
	'tx_lang_cache_cachingframework' => $extensionPath . '/classes/cache/class.tx_lang_cache_cachingframework.php',
	'tx_lang_exception_filenotfound' => $extensionPath . '/classes/exception/class.tx_lang_exception_filenotfound.php',
	'tx_lang_exception_invalidxmlfile' => $extensionPath . '/classes/exception/class.tx_lang_exception_invalidxmlfile.php',
	'tx_lang_exception_invalidparser' => $extensionPath . '/classes/exception/class.tx_lang_exception_invalidparser.php',
	'tx_lang_factory' => $extensionPath . '/classes/class.tx_lang_factory.php',
	'tx_lang_parser' => $extensionPath . '/classes/interfaces/interface.tx_lang_parser.php',
	'tx_lang_parser_abstractxml' => $extensionPath . '/classes/parser/class.tx_lang_parser_abstractxml.php',
	'tx_lang_parser_llphp' => $extensionPath . '/classes/parser/class.tx_lang_parser_llphp.php',
	'tx_lang_parser_llxml' => $extensionPath . '/classes/parser/class.tx_lang_parser_llxml.php',
	'tx_lang_parser_xliff' => $extensionPath . '/classes/parser/class.tx_lang_parser_xliff.php',
	'tx_lang_store' => $extensionPath . '/classes/class.tx_lang_store.php',
);

?>