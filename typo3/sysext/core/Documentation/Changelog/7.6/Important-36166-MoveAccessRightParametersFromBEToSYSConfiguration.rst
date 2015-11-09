=============================================================================
Important: #36166 - Move access right parameters from BE to SYS configuration
=============================================================================

Description
===========

The access permission parameters (fileCreateMask, folderCreateMask, createGroup) have been moved from
`$GLOBALS['TYPO3_CONF_VARS']['BE']` to `$GLOBALS['TYPO3_CONF_VARS']['SYS']`.
An Upgrade Wizard ensures the correct migrations of the settings.