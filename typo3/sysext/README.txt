typo3/sysext/

"System" extensions for TYPO3
This is also a global repository for extensions in TYPO3, just like the global extensions (in typo3/ext/)
System extensions cannot (by default at least) be updated like global and local extensions; They are meant to always be distributed with the core (while global extensions may not) and to the user they will probably be understood more like a part of the core since they come along with the core. But technically they are extensions for various reasons.

Currently the system extensions are:

"cms" - the TYPO3 frontend which most projects uses.
"lang" - the system language labels for TYPO3. This is only an extension in order to utilize the typo3.org translation interface - thats the only reason. This is DEFINITELY needed by the core of TYPO3 - otherwise you would see no texts anywhere... :-)
