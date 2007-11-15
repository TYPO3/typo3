This folder contains all modules that are shipped with the install-tool.

Every single module consists of a folder with the name of the module. Inside of this folder, there
has to be a class-file that fits the naming rules. e.g.: database/class.tx_install_module_database.php
All other stuff like resource folders etc. can be located inside the module-folder.

The following default modules come with the install-tool:

overview (default)
	This module is a meta-module with the only job to display the other modules in a certain way.
	For example it, display a matrix of all existing modules in a 4xn matrix of graphical buttons.
	This module is selected by default.

database
	Checks if everything is set up currectly in the database. This includes general
	settings for the connectivity and checks if the existing database fits the current
	TCA.
	
directories
	Checks if all needed directories are existing and if not if they can be created automatically.
	It also checks if all upload directories are available and if they can be created.
	
statistics
	Displays several statistics about the installation. Size on harddisk, size of database etc.