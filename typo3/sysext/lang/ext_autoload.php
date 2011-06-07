<?php
/*
 * Register necessary class names with autoloader
 */
$extensionPath = dirname(__FILE__);
return array(
	'language' => $extensionPath . '/lang.php',
	'tx_lang_filenotfoundexception' => $extensionPath . '/classes/exception/class.tx_lang_filenotfoundexception.php',
	'tx_lang_invalidxmlfileexception' => $extensionPath . '/classes/exception/class.tx_lang_invalidxmlfileexception.php',
	'tx_lang_invalidparserexception' => $extensionPath . '/classes/exception/class.tx_lang_invalidparserexception.php',
	'tx_lang_parserinterface' => $extensionPath . '/classes/interface/class.tx_lang_parserinterface.php',
	'tx_lang_cacheabstract' => $extensionPath . '/classes/cache/class.tx_lang_cacheabstract.php',
	'tx_lang_cachefile' => $extensionPath . '/classes/cache/class.tx_lang_cachefile.php',
	'tx_lang_cachecachingframework' => $extensionPath . '/classes/cache/class.tx_lang_cachecachingframework.php',
	'tx_lang_factory' => $extensionPath . '/classes/class.tx_lang_factory.php',
	'tx_lang_store' => $extensionPath . '/classes/class.tx_lang_store.php',
	'tx_lang_xmlparserabstract' => $extensionPath . '/classes/xml/class.tx_lang_xmlparserabstract.php',
	'tx_lang_xliffparser' => $extensionPath . '/classes/xliff/class.tx_lang_xliffparser.php',
	'tx_lang_llxmlparser' => $extensionPath . '/classes/llxml/class.tx_lang_llxmlparser.php',
	'tx_lang_llphpparser' => $extensionPath . '/classes/llphp/class.tx_lang_llphpparser.php'
);

?>