CREATE TABLE sys_refindex (
	# EXT:form BE module related DatabaseService needs this index for "form usage count" lookups
	# @todo: Solve differently somehow. It is essentially needed because not all form.yaml
	#        are FAL resources, but can be provided by extensions, too. See the registered
	#        softref parser for more details, too.
	KEY lookup_string (ref_string(191)),
	# Prevent full table scan for queries like "WHERE softref_key='formPersistenceIdentifier' and ref_uid > 0"
	KEY idx_softref_key (softref_key,ref_uid)
);
