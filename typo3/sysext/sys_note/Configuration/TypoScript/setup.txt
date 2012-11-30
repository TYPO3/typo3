# ======================================================================
# Module setup
# ======================================================================
module.tx_sysnote {
	view {
		layoutRootPath = {$module.tx_sysnote.view.layoutRootPath}
		templateRootPath = {$module.tx_sysnote.view.templateRootPath}
		partialRootPath = {$module.tx_sysnote.view.partialRootPath}
	}
}


# ======================================================================
# Extbase mapping
# ======================================================================
config.tx_extbase.persistence.classes {

	TYPO3\CMS\SysNote\Domain\Model\SysNote.mapping {
		tableName = sys_note
		recordType =
		columns {
			crdate.mapOnProperty = creationDate
			tstamp.mapOnProperty = modificationDate
			cruser.mapOnProperty = author
		}
	}

}