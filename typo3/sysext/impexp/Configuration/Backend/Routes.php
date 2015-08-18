<?php

/**
 * Definitions for routes provided by EXT:impexp
 */
return [
	// Register click menu entry point
	'xMOD_tximpexp' => [
		'path' => '/record/importexport/',
		'controller' => \TYPO3\CMS\Impexp\Controller\ImportExportController::class
	]
];
