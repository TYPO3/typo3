<?php
/*
 * Register necessary class names with autoloader
 */
$extensionPath = dirname(__FILE__);
return array(
	'language' => $extensionPath . '/lang.php',
	'tx_language_filenotfoundexception' => $extensionPath . '/classes/exception/class.tx_language_filenotfoundexception.php',
	'tx_language_invalidxmlfileexception' => $extensionPath . '/classes/exception/class.tx_language_invalidxmlfileexception.php',
	'tx_language_invalidparserexception' => $extensionPath . '/classes/exception/class.tx_language_invalidparserexception.php',
	'tx_language_parserinterface' => $extensionPath . '/classes/interface/class.tx_language_parserinterface.php',
	'tx_language_cacheabstract' => $extensionPath . '/classes/cache/class.tx_language_cacheabstract.php',
	'tx_language_cachefile' => $extensionPath . '/classes/cache/class.tx_language_cachefile.php',
	'tx_language_cachecachingframework' => $extensionPath . '/classes/cache/class.tx_language_cachecachingframework.php',
	'tx_language_factory' => $extensionPath . '/classes/class.tx_language_factory.php',
	'tx_language_store' => $extensionPath . '/classes/class.tx_language_store.php',
	'tx_language_xmlparserabstract' => $extensionPath . '/classes/xml/class.tx_language_xmlparserabstract.php',
	'tx_language_xliffparser' => $extensionPath . '/classes/xliff/class.tx_language_xliffparser.php',
	'tx_language_llxmlparser' => $extensionPath . '/classes/llxml/class.tx_language_llxmlparser.php',
	'tx_language_llphpparser' => $extensionPath . '/classes/llphp/class.tx_language_llphpparser.php'
);

?>