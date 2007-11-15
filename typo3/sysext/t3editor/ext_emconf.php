<?php

########################################################################
# Extension Manager/Repository config file for ext: "t3editor"
#
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
        'title' => 'Codeeditor t3editor',
        'description' => 'javascript-driven codeeditor with syntax highlighting for TS, HTML, CSS and more',
        'category' => 'be',
        'shy' => 0,
        'dependencies' => '',
        'conflicts' => 'pmktextarea',
        'priority' => '',
        'loadOrder' => '',
        'module' => '',
        'state' => 'alpha',
        'internal' => 0,
        'uploadfolder' => 1,
        'createDirs' => '',
        'modify_tables' => '',
        'clearCacheOnLoad' => 1,
        'lockType' => '',
        'author' => 'Tobias Liebig',
        'author_email' => 'mail_typo3@etobi.de',
        'author_company' => '',
        'CGLcompliance' => '',
        'CGLcompliance_note' => '',
        'version' => '0.0.5',
		'_md5_values_when_last_written' => '',
        'constraints' => array(
                'depends' => array(
                        'php' => '4.1.0-',
                        'typo3' => '4.1-',
                ),
                'conflicts' => array(
                        'pmktextarea' => '',
                ),
                'suggests' => array(
                ),
        ),
        'suggests' => array(
        ),
);
?>
