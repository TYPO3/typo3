<?php
class t3lib_CategoryRegistry implements t3lib_Singleton {

	/**
	 * @var array
	 */
	protected $registry = array();

	/**
	 * @return t3lib_CategoryRegistry
	 */
	public static function getInsance() {
		return t3lib_div::makeInstance('t3lib_CategoryRegistry');
	}

	/**
	 * @param string $extensionKey
	 * @param string $tableName
	 * @param string $fieldName
	 * @return boolean
	 */
	public function add($extensionKey, $tableName, $fieldName) {
		if (empty($GLOBALS['TCA'][$tableName])) {
			return FALSE;
		}

		$this->registry[$extensionKey][$tableName] = $fieldName;
		return TRUE;
	}

	/**
	 * @return string
	 */
	public function getDatabaseTableDefinitions() {
		$sqlContents = '';

		foreach ($this->registry as $extensionKey => $configuration) {
			$sqlContents .= $this->getDatabaseTableDefinition($extensionKey);
		}

		return $sqlContents;
	}

	/**
	 * makeCategorizable('recycler', 'pages', 'categories', array());
	 *
	 * recycler
	 *		pages = categories
	 * 		tt_content = categories
	 *
	 * tt_news
	 *		pages = news_categories
	 * 		tt_content = categories
	 *
	 * @param string $extensionKey
	 * @return string
	 */
	public function getDatabaseTableDefinition($extensionKey) {
		$sqlContents = '';

		$template = str_repeat(PHP_EOL, 3) . 'CREATE TABLE %s (' . PHP_EOL .
			'%s int(11) DEFAULT \'0\' NOT NULL' . PHP_EOL .
		');' . str_repeat(PHP_EOL, 3);

		foreach ($this->registry[$extensionKey] as $tableName => $fieldName) {
			$sqlContents .= sprintf($template, $tableName, $fieldName);
		}

		return $sqlContents;
	}
}
?>