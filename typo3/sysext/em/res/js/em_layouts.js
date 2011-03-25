/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Kamper <info@sk-typo3.de>
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
 * ExtJS for the extension manager.
 *
 *
 * @author Steffen Kamper <info@sk-typo3.de>
 * @package TYPO3
 * @subpackage extension manager
 */
Ext.ns('TYPO3.EM');

TYPO3.EM.Layouts = {

	getInfoTemplate: function() {
		// later get template via Ajax
		return new Ext.XTemplate(
			'<div class="em-extlist-extinfo">',
				'<h2>{icon} {title} ({extkey})</h2>',
				'<p class="desc">{description}</p>',
				'<div class="em-extlist-extinfo-sub">',
					'<div class="em-extlist-extinfo-left">',
						'<h3>' + TYPO3.lang.show_details + '</h3>',
						'<p><label>' + TYPO3.lang.extInfoArray_author + ':</label> {author}<br />',
						'<label>' + TYPO3.lang.extInfoArray_version + ':</label> {version}<br />',
						'<label>' + TYPO3.lang.extInfoArray_category + ':</label> {category}<br />',
						'<label>' + TYPO3.lang.extInfoArray_state + ':</label> {state}<br />',
						'<label>' + TYPO3.lang.extInfoArray_shy + ':</label> {shyword}<br />',
						'<label>' + TYPO3.lang.extInfoArray_internal + ':</label> {internal}<br />',
						'<label>' + TYPO3.lang.ext_details_doNotLoadInFE + ':</label> {[this.loadInFe(values)]}<br />',
						'<label>' + TYPO3.lang.extInfoArray_depends_on + ':</label> {depends}<br />',
						'<label>' + TYPO3.lang.extInfoArray_conflicts_with + ':</label> {conflicts}<br />',
						'<label>' + TYPO3.lang.extInfoArray_suggests + ':</label> {suggests}<br />',
					'</div>',
					'<div class="em-extlist-extinfo-right">',
						'<h3>' + TYPO3.lang.show_links + '</h3>',
						'<p><label>' + TYPO3.lang.cmd_downloadext + ':</label> {download}</p> ',
						'<tpl if="doc">',
							'<p><label>' + TYPO3.lang.cmd_readdoc + ':</label> {doc}</p>',
						'</tpl>',
						'<tpl if="updateModule">',
							'<h3>' + TYPO3.lang.cmd_update + '</h3>',
							'<p><a href="#" id="update-check-{extkey}" class="update-check-link">' + TYPO3.lang.ext_details_checkUpdateScript + '</a></p>',
						'</tpl>',
					'</div>',
				'</div>',
			'</div>',
			{
				loadInFe: function(values) {
					return values.doNotLoadInFE ? TYPO3.lang.no : TYPO3.lang.yes;
				}
			}
		);
	},

	remoteExtensionInfo: function() {
		return new Ext.XTemplate(
			'<div class="em-info">',
				'<p><label>' + TYPO3.lang.extInfoArray_title + ':' +
						'</label><a href="http://typo3.org/extensions/repository/view/{extkey}/current/" target="_blank" title="'
		 				+ TYPO3.lang.cmd_viewOnline + '">{title}</a></p>',
				'<p><label>' + TYPO3.lang.listRowHeader_ext_key + '</label>{extkey}</p>',
				'<p><label>' + TYPO3.lang.extInfoArray_category + ':</label>{[TYPO3.EM.App.getCategoryLabel(values.category)]}</p>',
				'<p><label>' + TYPO3.lang.extInfoArray_version + ':</label>{version}</p>',
				'<p><label>' + TYPO3.lang.extInfoArray_downloads + ':</label>{alldownloadcounter} / {downloadcounter}</p>',
				'<p><label>' + TYPO3.lang.extInfoArray_lastUpdate + ':</label>{lastuploaddate:this.getDateFormat}</p>',
				'<p><label>' + TYPO3.lang.extInfoArray_state + ':</label>{state}</p>',
				'<p><label>' + TYPO3.lang.extInfoArray_author + ':</label>{[this.getAuthor(values)]}</p>',
				'<p><label>' + TYPO3.lang.extInfoArray_versions + ':</label>{versions}</p>',
				'<p><label>' + TYPO3.lang.extInfoArray_description + ':</label>{description:this.getDescription}</p>',
			'</div>',
		{
			getDescription: function(value) {
				return value ? value : '';
			},

			getAuthor: function(values) {
				if (values.author && values.author_email) {
					return '<a class="email" href="mailto:' + values.author_email + '">' + values.author + '</a>';
				}
				return values.authorname;
			},
			getDateFormat: function(value) {
				return value.format("d-m-Y");
			}
		}
		);
	},

	getExtensionRules: function() {
		return [
			'<div class="extvalidinfo">',
				'<b>' + TYPO3.lang.registerkeys_rules_heading + '</b>',
				'<ul>',
					'<li>' + TYPO3.lang.registerkeys_rules_allowedcharacters + '</li>',
					'<li>' + TYPO3.lang.registerkeys_rules_prefixes + '</li>',
					'<li>' + TYPO3.lang.registerkeys_rules_startandend + '</li>',
					'<li>' + TYPO3.lang.registerkeys_rules_length + '</li>',
				'</ul>',
			'</div>'
		].join('');
	},
	repositoryInfo: function() {
		return new Ext.XTemplate(
			'<span class="typo3-message message-notice" ',
			'qtip="{updated:this.updatedFormat}<br>' + TYPO3.lang.extensions_repository_short + ' {count}">',
			'{updated:this.updatedFormat}&nbsp;&nbsp;',
			TYPO3.lang.extensions_repository_short + ' {count}',
			'</span>',
			{
				updatedFormat: function(value) {
					return TYPO3.lang.ext_list_last_updated.replace('%s', value).replace('(', '').replace(')', '');
				}
			}
		);
	},

	showExtInfo: function (data) {
		data.shyword = data.shy ? 'Yes' : 'No';
		return this.getInfoTemplate().applyTemplate(data);
	}
};

