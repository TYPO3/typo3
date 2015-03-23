/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

module.exports = function(grunt) {

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		less: {
			t3skin: {
				options: {
					outputSourceFiles: true,
				},
				src: '../typo3/sysext/t3skin/Resources/Private/Styles/t3skin.less',
				dest: '../typo3/sysext/t3skin/Resources/Public/Css/visual/t3skin.css'
			}
		},
		watch: {
			less: {
				files: '../typo3/sysext/t3skin/Resources/Private/Styles/**/*.less',
				tasks: 'less'
			}
		}
	});

	// Register tasks
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-watch');

};
