<?php
return array(
	'SYS' => array(
		'displayErrors' => '1',
		'debugExceptionHandler' => '',
		'caching' => array(
			'cacheConfigurations' => array(
				'cache_core' => array(
					'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend'
				),
				'cache_classes' => array(
					'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\TransientMemoryBackend'
				),
			)
		)
	)
);
