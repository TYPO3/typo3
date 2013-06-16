<?php
namespace TYPO3\CMS\Install\FolderStructure;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Factory returns default folder structure object hierachie
 */
class DefaultFactory {

	/**
	 * @var array Expected folder structure
	 */
	protected $expectedDefaultStructure = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->expectedDefaultStructure = array(
			// Cut off trailing forward / from PATH_site, so root node has no trailing slash like all others
			'name' => substr(PATH_site, 0, -1),
			'targetPermission' => '2770',
			'children' => array(
				array(
					'name' => 'typo3temp',
					'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
					'targetPermission' => '2770',
					'children' => array(
						array(
							'name' => 'index.html',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\FileNode',
							'targetPermission' => '0660',
							'targetContent' => '',
						),
						array(
							'name' => 'compressor',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
						),
						array(
							'name' => 'cs',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
						),
						array(
							'name' => 'Cache',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
						),
						array(
							'name' => 'GB',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
						),
						array(
							'name' => 'llxml',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
						),
						array(
							'name' => 'locks',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
						),
						array(
							'name' => 'pics',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
						),
						array(
							'name' => 'sprites',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
						),
						array(
							'name' => 'temp',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
						),
					),
				),
				array(
					'name' => 'typo3conf',
					'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
					'targetPermission' => '2770',
					'children' => array(
						array(
							'name' => 'ext',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
						),
						array(
							'name' => 'l10n',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
						),
					),
				),
				array(
					'name' => 'uploads',
					'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
					'targetPermission' => '2770',
					'children' => array(
						array(
							'name' => 'index.html',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\FileNode',
							'targetPermission' => '0660',
							'targetContent' =>
								'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">' . LF .
								'<HTML>' . LF .
								'<HEAD>' . LF .
								TAB . '<TITLE></TITLE>' . LF .
								'<META http-equiv=Refresh Content="0; Url=../">' . LF .
								'</HEAD>' . LF .
								'</HTML>',
						),
						array(
							'name' => 'media',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
							'children' => array(
								array(
									'name' => 'index.html',
									'type' => 'TYPO3\\CMS\\install\\FolderStructure\\FileNode',
									'targetPermission' => '0660',
									'targetContent' => '',
								),
							),
						),
						array(
							'name' => 'pics',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
							'children' => array(
								array(
									'name' => 'index.html',
									'type' => 'TYPO3\\CMS\\install\\FolderStructure\\FileNode',
									'targetPermission' => '0660',
									'targetContent' => '',
								),
							),
						),
						array(
							'name' => 'tf',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
							'children' => array(
								array(
									'name' => 'index.html',
									'type' => 'TYPO3\\CMS\\install\\FolderStructure\\FileNode',
									'targetPermission' => '0660',
									'targetContent' => '',
								),
							),
						),
					),
				),
				array(
					'name' => 'fileadmin',
					'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
					'targetPermission' => '2770',
					'children' => array(
						array(
							'name' => '_temp_',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
							'children' => array(
								array(
									'name' => '.htaccess',
									'type' => 'TYPO3\\CMS\\install\\FolderStructure\\FileNode',
									'targetPermission' => '0660',
									'targetContent' =>
										'# This file restricts access to the fileadmin/_temp_ directory. It is' . LF .
										'# meant to protect temporary files which could contain sensible' . LF .
										'# information. Please do not touch.' . LF .
										LF .
										'Order deny,allow' . LF .
										'Deny from all' . LF,
								),
								array(
									'name' => 'index.html',
									'type' => 'TYPO3\\CMS\\install\\FolderStructure\\FileNode',
									'targetPermission' => '0660',
									'targetContent' => '',
								),
							),
						),
						array(
							'name' => 'user_upload',
							'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
							'targetPermission' => '2770',
							'children' => array(
								array(
									'name' => '_temp_',
									'type' => 'TYPO3\\CMS\\install\\FolderStructure\\DirectoryNode',
									'targetPermission' => '2770',
									'children' => array(
										array(
											'name' => 'index.html',
											'type' => 'TYPO3\\CMS\\install\\FolderStructure\\FileNode',
											'targetPermission' => '0660',
											'targetContent' => '',
										),
									),
								),
								array(
									'name' => 'index.html',
									'type' => 'TYPO3\\CMS\\install\\FolderStructure\\FileNode',
									'targetPermission' => '0660',
									'targetContent' => '',
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Get default structure object hierarchy
	 *
	 * @throws Exception
	 * @return RootNode
	 * @TODO: Use objectManager instead of new (will be injected)
	 * @TODO: Handle targetPermission for files / directory if set in TYPO3_CONF_VARS
	 */
	public function getStructure() {
		$rootNode = new RootNode($this->expectedDefaultStructure, NULL);
		if (!($rootNode instanceof RootNodeInterface)) {
			throw new Exception(
				'Root node must implement RootNodeInterface',
				1366139176
			);
		}
		$structureFacade = new StructureFacade($rootNode);
		if (!($structureFacade instanceof StructureFacadeInterface)) {
			throw new Exception(
				'Structure facade must implement StructureFacadeInterface',
				1366535827
			);
		}
		return $structureFacade;
	}
}