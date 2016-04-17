/*
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

/**
 * RequireJS module for the workspace backend module
 */
define([
	'jquery',
	'TYPO3/CMS/Workspaces/Workspaces',
	'TYPO3/CMS/Backend/Tooltip',
	'TYPO3/CMS/Backend/Severity',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Wizard',
	'nprogress',
	'TYPO3/CMS/Backend/jquery.clearable'
], function($, Workspaces, Tooltip, Severity, Modal, Wizard, NProgress) {
	'use strict';

	var Backend = {
		workspaceTitle: '',
		identifiers: {
			searchForm: '#workspace-settings-form',
			searchTextField: '#workspace-settings-form input[name="search-text"]',
			searchSubmitBtn: '#workspace-settings-form button[type="submit"]',
			depthSelector: '#workspace-settings-form [name="depth"]',
			languageSelector: '#workspace-settings-form select[name="languages"]',
			actionForm: '#workspace-actions-form',
			chooseStageAction: '#workspace-actions-form [name="stage-action"]',
			chooseSelectionAction: '#workspace-actions-form [name="selection-action"]',
			chooseMassAction: '#workspace-actions-form [name="mass-action"]',
			container: '#workspace-panel',
			actionIcons: '#workspace-action-icons',
			toggleAll: '.t3js-toggle-all',
			previewLinksButton: '.t3js-preview-link',
			pagination: '#workspace-pagination'
		},
		settings: {
			depth: TYPO3.settings.Workspaces.depth,
			dir: 'ASC',
			id: TYPO3.settings.Workspaces.id,
			language: TYPO3.settings.Workspaces.language,
			limit: 30,
			query: '',
			sort: 'label_Live',
			start: 0,
			filterTxt: ''
		},
		paging: {
			currentPage: 1,
			totalPages: 1,
			totalItems: 0
		},
		allToggled: false,
		elements: {}, // filled in Backend.getElements()
		latestPath: '',
		markedRecordsForMassAction: [],
		indentationPadding: 26
	};

	Backend.initialize = function() {
		Backend.getElements();
		Backend.registerEvents();

		if (TYPO3.settings.Workspaces.depth > 0) {
			Backend.elements.$depthSelector.val(TYPO3.settings.Workspaces.depth);
		}

		Backend.loadWorkspaceComponents();
	};

	Backend.getElements = function() {
		Backend.elements.$searchForm = $(Backend.identifiers.searchForm);
		Backend.elements.$searchTextField = $(Backend.identifiers.searchTextField);
		Backend.elements.$searchSubmitBtn = $(Backend.identifiers.searchSubmitBtn);
		Backend.elements.$depthSelector = $(Backend.identifiers.depthSelector);
		Backend.elements.$languageSelector = $(Backend.identifiers.languageSelector);
		Backend.elements.$container = $(Backend.identifiers.container);
		Backend.elements.$tableBody = Backend.elements.$container.find('tbody');
		Backend.elements.$actionIcons = $(Backend.identifiers.actionIcons);
		Backend.elements.$toggleAll =  $(Backend.identifiers.toggleAll);
		Backend.elements.$chooseStageAction = $(Backend.identifiers.chooseStageAction);
		Backend.elements.$chooseSelectionAction = $(Backend.identifiers.chooseSelectionAction);
		Backend.elements.$chooseMassAction = $(Backend.identifiers.chooseMassAction);
		Backend.elements.$previewLinksButton = $(Backend.identifiers.previewLinksButton);
		Backend.elements.$pagination = $(Backend.identifiers.pagination);
	};

	Backend.registerEvents = function() {
		$(document).on('click', '[data-action="swap"]', function(e) {
			var $tr = $(e.target).closest('tr');
			Workspaces.checkIntegrity(
				{
					selection: [
						{
							liveId: $tr.data('uid'),
							versionId: $tr.data('t3ver_oid'),
							table: $tr.data('table')
						}
					],
					type: 'selection'
				}
			).done(function(response) {
				if (response[0].result.result === 'warning') {
					Backend.addIntegrityCheckWarningToWizard();
				}

				Wizard.setup.forceSelection = false;
				Wizard.addSlide(
					'swap-confirm',
					'Swap',
					TYPO3.lang['window.swap.message'],
					Severity.info
				);
				Wizard.addFinalProcessingSlide(function() {
					// We passed this slide, swap the record now
					Workspaces.sendExtDirectRequest(
						Workspaces.generateExtDirectActionsPayload('swapSingleRecord', [
							$tr.data('table'),
							$tr.data('t3ver_oid'),
							$tr.data('uid')
						])
					).done(function() {
						Wizard.dismiss();
						Backend.getWorkspaceInfos();
						Backend.refreshPageTree();
					});
				}).done(function() {
					Wizard.show();
				});
			});
		}).on('click', '[data-action="prevstage"]', function(e) {
			Backend.sendToStage($(e.target).closest('tr'), 'prev');
		}).on('click', '[data-action="nextstage"]', function(e) {
			Backend.sendToStage($(e.target).closest('tr'), 'next');
		}).on('click', '[data-action="changes"]', Backend.viewChanges
		).on('click', '[data-action="preview"]', Backend.openPreview
		).on('click', '[data-action="open"]', function(e) {
			var $tr = $(e.target).closest('tr'),
				newUrl = TYPO3.settings.FormEngine.moduleUrl + '&returnUrl=' + encodeURIComponent(document.location.href) + '&id=' + TYPO3.settings.Workspaces.id + '&edit[' + $tr.data('table') + '][' + $tr.data('uid') + ']=edit';

			// Append workspace of record in all-workspaces view
			if (TYPO3.settings.Workspaces.allView) {
				newUrl += '&workspace=' + $tr.data('t3ver_wsid');
			}
			window.location.href = newUrl;
		}).on('click', '[data-action="version"]', function(e) {
			var $tr = $(e.target).closest('tr');
			if ($tr.data('table') === 'pages') {
				top.loadEditId($tr.data('t3ver_oid'));
			} else {
				top.loadEditId($tr.data('pid'));
			}
		}).on('click', '[data-action="remove"]', Backend.confirmDeleteRecordFromWorkspace
		).on('click', '[data-action="expand"]', function(e) {
			var $me = $(this),
				$target = Backend.elements.$tableBody.find($me.data('target')),
				iconIdentifier;

			if ($target.first().attr('aria-expanded') === 'true') {
				iconIdentifier = 'apps-pagetree-expand';
			} else {
				iconIdentifier = 'apps-pagetree-collapse';
			}

			$me.html(Backend.getPreRenderedIcon(iconIdentifier));
		});

		Backend.elements.$searchForm.on('submit', function(e) {
			e.preventDefault();
			Backend.settings.filterTxt = Backend.elements.$searchTextField.val();
			Backend.getWorkspaceInfos();
		});

		Backend.elements.$searchTextField.on('keyup', function() {
			var $me = $(this);

			if ($me.val() !== '') {
				Backend.elements.$searchSubmitBtn.removeClass('disabled');
			} else {
				Backend.elements.$searchSubmitBtn.addClass('disabled');
				Backend.getWorkspaceInfos();
			}
		}).clearable(
			{
				onClear: function() {
					Backend.elements.$searchSubmitBtn.addClass('disabled');
					Backend.settings.filterTxt = '';
					Backend.getWorkspaceInfos();
				}
			}
		);

		// checkboxes in the table
		Backend.elements.$toggleAll.on('click', function() {
			Backend.allToggled = !Backend.allToggled;
			Backend.elements.$tableBody.find('input[type="checkbox"]').prop('checked', Backend.allToggled).trigger('change');
		});
		Backend.elements.$tableBody.on('change', 'tr input[type=checkbox]', Backend.handleCheckboxChange);

		// Listen for depth changes
		Backend.elements.$depthSelector.on('change', function(e) {
			var $me = $(this);
			Backend.settings.depth = $me.val();

			Backend.getWorkspaceInfos();
		});

		// Generate preview links
		Backend.elements.$previewLinksButton.on('click', Backend.generatePreviewLinks);

		// Listen for language changes
		Backend.elements.$languageSelector.on('change', function(e) {
			var $me = $(this);
			Backend.settings.language = $me.val();

			Workspaces.sendExtDirectRequest([
				Workspaces.generateExtDirectActionsPayload('saveLanguageSelection', [$me.val()]),
				Workspaces.generateExtDirectPayload('getWorkspaceInfos', Backend.settings)
			]).done(function(response) {
				Backend.elements.$languageSelector.prev().html($me.find(':selected').data('icon'));
				Backend.renderWorkspaceInfos(response[1].result);
			});
		});

		// Listen for actions
		Backend.elements.$chooseStageAction.on('change', Backend.sendToSpecificStageAction);
		Backend.elements.$chooseSelectionAction.on('change', Backend.runSelectionAction);
		Backend.elements.$chooseMassAction.on('change', Backend.runMassAction);

		// clicking an action in the paginator
		Backend.elements.$pagination.on('click', 'a[data-action]', function(e) {
			e.preventDefault();

			var $el = $(this),
				reload = false;

			switch ($el.data('action')) {
				case 'previous':
					if (Backend.paging.currentPage > 1) {
						Backend.paging.currentPage--;
						reload = true;
					}
					break;
				case 'next':
					if (Backend.paging.currentPage < Backend.paging.totalPages) {
						Backend.paging.currentPage++;
						reload = true;
					}
					break;
				case 'page':
					Backend.paging.currentPage = parseInt($el.data('page'));
					reload = true;
					break;
			}

			if (reload) {
				// Adjust settings
				Backend.settings.start = Backend.settings.limit * (Backend.paging.currentPage - 1);
				Backend.getWorkspaceInfos();
			}
		});
	};

	Backend.handleCheckboxChange = function(e) {
		var $checkbox = $(this),
			$tr = $checkbox.parents('tr'),
			table = $tr.data('table'),
			uid = $tr.data('uid'),
			t3ver_oid = $tr.data('t3ver_oid'),
			record = table + ':' + uid + ':' + t3ver_oid;

		if ($checkbox.prop('checked')) {
			Backend.markedRecordsForMassAction.push(record);
			$tr.addClass('warning');
		} else {
			var index = Backend.markedRecordsForMassAction.indexOf(record);
			if (index > -1) {
				Backend.markedRecordsForMassAction.splice(index, 1);
			}
			$tr.removeClass('warning');
		}

		Backend.elements.$chooseStageAction.prop('disabled', Backend.markedRecordsForMassAction.length === 0);
		Backend.elements.$chooseSelectionAction.prop('disabled', Backend.markedRecordsForMassAction.length === 0);
		Backend.elements.$chooseMassAction.prop('disabled', Backend.markedRecordsForMassAction.length > 0);
	};

	/**
	 * Generates the diff view of a record
	 *
	 * @param {Object} diff
	 * @return {$}
	 */
	Backend.generateDiffView = function(diff) {
		var $diff = $('<div />', {class: 'diff'});

		for (var i = 0; i < diff.length; ++i) {
			$diff.append(
				$('<div />', {class: 'diff-item'}).append(
					$('<div />', {class: 'diff-item-title'}).text(diff[i].label),
					$('<div />', {class: 'diff-item-result diff-item-result-inline'}).html(diff[i].content)
				)
			);
		}
		return $diff;
	};

	/**
	 * Generates the comments view of a record
	 *
	 * @param {Object} comments
	 * @return {$}
	 */
	Backend.generateCommentView = function(comments) {
		var $comments = $('<div />');

		for (var i = 0; i < comments.length; ++i) {
			var $panel = $('<div />', {class: 'panel panel-default'});

			if (comments[i].user_comment.length > 0) {
				$panel.append(
					$('<div />', {class: 'panel-body'}).html(comments[i].user_comment)
				);
			}

			$panel.append(
				$('<div />', {class: 'panel-footer'}).append(
					$('<span />', {class: 'label label-success'}).text(comments[i].stage_title),
					$('<span />', {class: 'label label-info'}).text(comments[i].tstamp)
				)
			);

			$comments.append(
				$('<div />', {class: 'media'}).append(
					$('<div />', {class: 'media-left text-center'}).text(comments[i].user_username).prepend(
						$('<div />').html(comments[i].user_avatar)
					),
					$('<div />', {class: 'media-body'}).append($panel)
				)
			);
		}

		return $comments;
	};

	/**
	 * Sends a record to a stage
	 *
	 * @param {Object} $row
	 * @param {String} direction
	 */
	Backend.sendToStage = function($row, direction) {
		var nextStage,
			stageWindowAction,
			stageExecuteAction;

		if (direction === 'next') {
			nextStage = $row.data('nextStage');
			stageWindowAction = 'sendToNextStageWindow';
			stageExecuteAction = 'sendToNextStageExecute';
		} else if (direction === 'prev') {
			nextStage = $row.data('prevStage');
			stageWindowAction = 'sendToPrevStageWindow';
			stageExecuteAction = 'sendToPrevStageExecute';
		} else {
			throw 'Invalid direction given.';
		}

		Workspaces.sendExtDirectRequest(
			Workspaces.generateExtDirectActionsPayload(stageWindowAction, [
				$row.data('uid'), $row.data('table'), $row.data('t3ver_oid')
			])
		).done(function(response) {
			var $modal = Workspaces.renderSendToStageWindow(response);
			$modal.on('button.clicked', function(e) {
				if (e.target.name === 'ok') {
					var $form = $(e.currentTarget).find('form'),
						serializedForm = $form.serializeObject();

					serializedForm.affects = {
						table: $row.data('table'),
						nextStage: nextStage,
						t3ver_oid: $row.data('t3ver_oid'),
						uid: $row.data('uid'),
						elements: []
					};

					Workspaces.sendExtDirectRequest([
						Workspaces.generateExtDirectActionsPayload(stageExecuteAction, [serializedForm]),
						Workspaces.generateExtDirectPayload('getWorkspaceInfos', Backend.settings)
					]).done(function(response) {
						$modal.modal('hide');
						Backend.renderWorkspaceInfos(response[1].result);
						Backend.refreshPageTree();
					});
				}
			});
		});
	};

	/**
	 * Loads the workspace components, like available stage actions and items of the workspace
	 */
	Backend.loadWorkspaceComponents = function() {
		Workspaces.sendExtDirectRequest([
			Workspaces.generateExtDirectPayload('getWorkspaceInfos', Backend.settings),
			Workspaces.generateExtDirectPayload('getStageActions', {}),
			Workspaces.generateExtDirectMassActionsPayload('getMassStageActions', {}),
			Workspaces.generateExtDirectPayload('getSystemLanguages', {})
		]).done(function(response) {
			Backend.elements.$depthSelector.prop('disabled', false);

			// Records
			Backend.renderWorkspaceInfos(response[0].result);

			// Stage actions
			var stageActions = response[1].result.data,
				i;
			for (i = 0; i < stageActions.length; ++i) {
				Backend.elements.$chooseStageAction.append(
					$('<option />').val(stageActions[i].uid).text(stageActions[i].title)
				);
			}

			// Mass actions
			var massActions = response[2].result.data;
			for (i = 0; i < massActions.length; ++i) {
				Backend.elements.$chooseSelectionAction.append(
					$('<option />').val(massActions[i].action).text(massActions[i].title)
				);

				Backend.elements.$chooseMassAction.append(
					$('<option />').val(massActions[i].action).text(massActions[i].title)
				);
			}

			// Languages
			var languages = response[3].result.data;
			for (i = 0; i < languages.length; ++i) {
				var $option = $('<option />').val(languages[i].uid).text(languages[i].title).data('icon', languages[i].icon);
				if (String(languages[i].uid) === String(TYPO3.settings.Workspaces.language)) {
					$option.prop('selected', true);
					Backend.elements.$languageSelector.prev().html(languages[i].icon);
				}
				Backend.elements.$languageSelector.append($option);
			}
			Backend.elements.$languageSelector.prop('disabled', false);
		});
	};

	/**
	 * Gets the workspace infos
	 *
	 * @return {Promise}
	 * @protected
	 */
	Backend.getWorkspaceInfos = function() {
		Workspaces.sendExtDirectRequest(
			Workspaces.generateExtDirectPayload('getWorkspaceInfos', Backend.settings)
		).done(function(response) {
			Backend.renderWorkspaceInfos(response[0].result);
		});
	};

	/**
	 * Renders fetched workspace informations
	 *
	 * @param {Object} result
	 */
	Backend.renderWorkspaceInfos = function(result) {
		Backend.elements.$tableBody.children().remove();
		Backend.allToggled = false;
		Backend.elements.$chooseStageAction.prop('disabled', true);
		Backend.elements.$chooseSelectionAction.prop('disabled', true);
		Backend.elements.$chooseMassAction.prop('disabled', result.data.length === 0);

		Backend.buildPagination(result.total);

		for (var i = 0; i < result.data.length; ++i) {
			var item = result.data[i],
				$actions = $('<div />', {class: 'btn-group'}),
				$integrityIcon = '';

			$actions.append(
				Backend.getAction(item.Workspaces_CollectionChildren > 0 && item.Workspaces_CollectionCurrent !== '', 'expand', 'apps-pagetree-collapse').attr('title', TYPO3.lang['tooltip.swap']).attr('data-target', '[data-collection="' + item.Workspaces_CollectionCurrent + '"]').attr('data-toggle', 'collapse'),
				$('<button />', {class: 'btn btn-default', 'data-action': 'changes', 'data-toggle': 'tooltip', title: TYPO3.lang['tooltip.showChanges']}).append(Backend.getPreRenderedIcon('actions-document-info')),
				Backend.getAction(item.allowedAction_swap && item.Workspaces_CollectionParent === '', 'swap', 'actions-version-swap-version').attr('title', TYPO3.lang['tooltip.swap']),
				Backend.getAction(item.allowedAction_view, 'preview', 'actions-version-workspace-preview').attr('title', TYPO3.lang['tooltip.viewElementAction']),
				$('<button />', {class: 'btn btn-default', 'data-action': 'open', 'data-toggle': 'tooltip', title: TYPO3.lang['tooltip.editElementAction']}).append(Backend.getPreRenderedIcon('actions-open')),
				$('<button />', {class: 'btn btn-default', 'data-action': 'version', 'data-toggle': 'tooltip', title: TYPO3.lang['tooltip.openPage']}).append(Backend.getPreRenderedIcon('actions-version-page-open')),
				Backend.getAction(item.allowedAction_delete, 'remove', 'actions-version-document-remove').attr('title', TYPO3.lang['tooltip.discardVersion']),
				$('<label />', {class: 'btn btn-default btn-checkbox'}).append(
					$('<input />', {type: 'checkbox'}),
					$('<span />', {class: 't3-icon fa'})
				)
			);

			if (item.integrity.messages !== '') {
				$integrityIcon = $(TYPO3.settings.Workspaces.icons[item.integrity.status]);
				$integrityIcon
					.attr('data-toggle', 'tooltip')
					.attr('data-placement', 'top')
					.attr('data-html', true)
					.attr('title', item.integrity.messages);
			}

			if (Backend.latestPath !== item.path_Workspace) {
				Backend.latestPath = item.path_Workspace;
				Backend.elements.$tableBody.append(
					$('<tr />').append(
						$('<th />', {colspan: 6}).text(Backend.latestPath)
					)
				);
			}

			var rowConfiguration = {
				'data-uid': item.uid,
				'data-pid': item.livepid,
				'data-t3ver_oid': item.t3ver_oid,
				'data-t3ver_wsid': item.t3ver_wsid,
				'data-table': item.table,
				'data-next-stage': item.value_nextStage,
				'data-prev-stage': item.value_prevStage,
				'data-stage': item.stage
			};

			if (item.Workspaces_CollectionParent !== '') {
				rowConfiguration['data-collection'] = item.Workspaces_CollectionParent;
				rowConfiguration['class'] = 'collapse';
			}

			Backend.elements.$tableBody.append(
				$('<tr />', rowConfiguration).append(
					$('<td />', {class: 't3js-title-workspace', style: item.Workspaces_CollectionLevel > 0 ? 'padding-left: ' + Backend.indentationPadding * item.Workspaces_CollectionLevel + 'px' : ''}).html(item.icon_Workspace + '&nbsp;' + '<a href="#" data-action="changes"><span class="item-state-' + item.state_Workspace + '">' + item.label_Workspace + '</span></a>'),
					$('<td />', {class: 't3js-title-live'}).html(item.icon_Live + '&nbsp;' + item.label_Live),
					$('<td />').text(item.label_Stage),
					$('<td />').html($integrityIcon),
					$('<td />').html(item.language.icon),
					$('<td />', {class: 'text-right', nowrap: 'nowrap'}).append($actions)
				)
			);

			Tooltip.initialize('[data-toggle="tooltip"]', {
				delay: {
					show: 500,
					hide: 100
				},
				trigger: 'hover',
				container: 'body'
			});
		}
	};

	/**
	 * Renders the pagination
	 *
	 * @param {Number} totalItems
	 */
	Backend.buildPagination = function(totalItems) {
		if (totalItems === 0) {
			Backend.elements.$pagination.contents().remove();
			return;
		}

		Backend.paging.totalItems = totalItems;
		Backend.paging.totalPages = Math.ceil(totalItems / Backend.settings.limit);

		if (Backend.paging.totalPages === 1) {
			// early abort if only one page is available
			Backend.elements.$pagination.contents().remove();
			return;
		}

		var $ul = $('<ul />', {class: 'pagination pagination-block'}),
			liElements = [],
			$controlFirstPage = $('<li />').append(
				$('<a />', {'data-action': 'previous'}).append(
					$('<span />', {class: 't3-icon fa fa-arrow-left'})
				)
			),
			$controlLastPage = $('<li />').append(
				$('<a />', {'data-action': 'next'}).append(
					$('<span />', {class: 't3-icon fa fa-arrow-right'})
				)
			);

		if (Backend.paging.currentPage === 1) {
			$controlFirstPage.disablePagingAction();
		}

		if (Backend.paging.currentPage === Backend.paging.totalPages) {
			$controlLastPage.disablePagingAction();
		}

		for (var i = 1; i <= Backend.paging.totalPages; i++) {
			var $li = $('<li />', {class: Backend.paging.currentPage === i ? 'active' : ''});
			$li.append(
				$('<a />', {'data-action': 'page', 'data-page': i}).append(
					$('<span />').text(i)
				)
			);
			liElements.push($li);
		}

		$ul.append($controlFirstPage, liElements, $controlLastPage);
		Backend.elements.$pagination.html($ul);
	};

	/**
	 * View changes of a record
	 *
	 * @param {Event} e
	 */
	Backend.viewChanges = function(e) {
		e.preventDefault();

		var $tr = $(e.target).closest('tr');

		Workspaces.sendExtDirectRequest(
			Workspaces.generateExtDirectPayload('getRowDetails', {
				stage: $tr.data('stage'),
				t3ver_oid: $tr.data('t3ver_oid'),
				table: $tr.data('table'),
				uid: $tr.data('uid')
			})
		).done(function(response) {
			var item = response[0].result.data[0],
				$content = $('<div />'),
				$tabsNav = $('<ul />', {class: 'nav nav-tabs', role: 'tablist'}),
				$tabsContent = $('<div />', {class: 'tab-content'}),
				modalButtons = [];

			$content.append(
				$('<p />').html(TYPO3.lang['path'].replace('{0}', item.path_Live)),
				$('<p />').html(TYPO3.lang['current_step'].replace('{0}', item.label_Stage).replace('{1}', item.stage_position).replace('{2}', item.stage_count))
			);

			if (item.diff.length > 0) {
				$tabsNav.append(
					$('<li />', {role: 'presentation'}).append(
						$('<a />', {href: '#workspace-changes', 'aria-controls': 'workspace-changes', role: 'tab', 'data-toggle': 'tab'}).text(TYPO3.lang['window.recordChanges.tabs.changeSummary'])
					)
				);
				$tabsContent.append(
					$('<div />', {role: 'tabpanel', class: 'tab-pane', id: 'workspace-changes'}).append(
						$('<div />', {class: 'form-section'}).append(
							Backend.generateDiffView(item.diff)
						)
					)
				);
			}

			if (item.comments.length > 0) {
				$tabsNav.append(
					$('<li />', {role: 'presentation'}).append(
						$('<a />', {href: '#workspace-comments', 'aria-controls': 'workspace-comments', role: 'tab', 'data-toggle': 'tab'}).html(TYPO3.lang['window.recordChanges.tabs.comments'] + '&nbsp;').append(
							$('<span />', {class: 'badge'}).text(item.comments.length)
						)
					)
				);
				$tabsContent.append(
					$('<div />', {role: 'tabpanel', class: 'tab-pane', id: 'workspace-comments'}).append(
						$('<div />', {class: 'form-section'}).append(
							Backend.generateCommentView(item.comments)
						)
					)
				);
			}

			if (item.history.total > 0) {
				$tabsNav.append(
					$('<li />', {role: 'presentation'}).append(
						$('<a />', {href: '#workspace-history', 'aria-controls': 'workspace-history', role: 'tab', 'data-toggle': 'tab'}).text(TYPO3.lang['window.recordChanges.tabs.history'])
					)
				);

				$tabsContent.append(
					$('<div />', {role: 'tabpanel', class: 'tab-pane', id: 'workspace-history'}).append(
						$('<div />', {class: 'form-section'}).append(
							Backend.generateHistoryView(item.history.data)
						)
					)
				);
			}

			// Mark the first tab and pane as active
			$tabsNav.find('li').first().addClass('active');
			$tabsContent.find('.tab-pane').first().addClass('active');

			// Attach tabs
			$content.append(
				$('<div />').append(
					$tabsNav,
					$tabsContent
				)
			);

			if ($tr.data('stage') !== $tr.data('prevStage')) {
				modalButtons.push({
					text: item.label_PrevStage.title,
					active: true,
					btnClass: 'btn-default',
					name: 'prevstage',
					trigger: function () {
						Modal.currentModal.trigger('modal-dismiss');
						Backend.sendToStage($(e.target).closest('tr'), 'prev');
					}
				});
			}

			modalButtons.push({
				text: item.label_NextStage.title,
				active: true,
				btnClass: 'btn-default',
				name: 'nextstage',
				trigger: function () {
					Modal.currentModal.trigger('modal-dismiss');
					Backend.sendToStage($(e.target).closest('tr'), 'next');
				}
			});
			modalButtons.push({
				text: TYPO3.lang['close'],
				active: true,
				btnClass: 'btn-info',
				name: 'cancel',
				trigger: function () {
					Modal.currentModal.trigger('modal-dismiss');
				}
			});

			Modal.show(
				TYPO3.lang['window.recordInformation'].replace('{0}', $.trim($tr.find('.t3js-title-live').text())),
				$content,
				Severity.info,
				modalButtons
			);
		});
	};

	/**
	 * Opens a record in a preview window
	 *
	 * @param {Event} e
	 */
	Backend.openPreview = function(e) {
		var $tr = $(e.target).closest('tr');

		Workspaces.sendExtDirectRequest(
			Workspaces.generateExtDirectActionsPayload('viewSingleRecord', [
				$tr.data('table'), $tr.data('uid')
			])
		).done(function(response) {
			eval(response[0].result);
		});
	};

	/**
	 * Renders the record's history
	 *
	 * @param {Object} data
	 */
	Backend.generateHistoryView = function(data) {
		var $history = $('<div />');

		for (var i = 0; i < data.length; ++i) {
			var $panel = $('<div />', {class: 'panel panel-default'}),
				$diff;

			if (typeof data[i].differences === 'object') {
				if (data[i].differences.length === 0) {
					// Somehow here are no differences. What a pity, skip that record
					continue;
				}
				$diff = $('<div />', {class: 'diff'});

				for (var j = 0; j < data[i].differences.length; ++j) {
					$diff.append(
						$('<div />', {class: 'diff-item'}).append(
							$('<div />', {class: 'diff-item-title'}).text(data[i].differences[j].label),
							$('<div />', {class: 'diff-item-result diff-item-result-inline'}).html(data[i].differences[j].html)
						)
					);
				}

				$panel.append(
					$('<div />').append($diff)
				);
			} else {
				$panel.append(
					$('<div />', {class: 'panel-body'}).text(data[i].differences)
				);
			}
			$panel.append(
				$('<div />', {class: 'panel-footer'}).append(
					$('<span />', {class: 'label label-info'}).text(data[i].datetime)
				)
			);

			$history.append(
				$('<div />', {class: 'media'}).append(
					$('<div />', {class: 'media-left text-center'}).text(data[i].user).prepend(
						$('<div />').html(data[i].user_avatar)
					),
					$('<div />', {class: 'media-body'}).append($panel)
				)
			);
		}

		return $history;
	};

	/**
	 * Shows a confirmation modal and deletes the selected record from workspace.
	 *
	 * @param {Event} e
	 */
	Backend.confirmDeleteRecordFromWorkspace = function(e) {
		var $tr = $(e.target).closest('tr');
		var $modal = Modal.confirm(
			TYPO3.lang['window.discard.title'],
			TYPO3.lang['window.discard.message'],
			Severity.warning,
			[
				{
					text: TYPO3.lang['cancel'],
					active: true,
					btnClass: 'btn-default',
					name: 'cancel',
					trigger: function() {
						$modal.modal('hide');
					}
				}, {
					text: TYPO3.lang['ok'],
					btnClass: 'btn-warning',
					name: 'ok'
				}
			]
		);
		$modal.on('button.clicked', function(e) {
			if (e.target.name === 'ok') {
				Workspaces.sendExtDirectRequest([
					Workspaces.generateExtDirectActionsPayload('deleteSingleRecord', [
						$tr.data('table'),
						$tr.data('uid')
					])
				]).done(function() {
					$modal.modal('hide');
					Backend.getWorkspaceInfos();
					Backend.refreshPageTree();
				});
			}
		});
	};

	/**
	 * Runs a mass action
	 */
	Backend.runSelectionAction = function() {
		var selectedAction = Backend.elements.$chooseSelectionAction.val(),
			integrityCheckRequired = selectedAction !== 'discard';

		if (selectedAction.length === 0) {
			// Don't do anything if that value is empty
			return;
		}

		var affectedRecords = [];
		for (var i = 0; i < Backend.markedRecordsForMassAction.length; ++i) {
			var affected = Backend.markedRecordsForMassAction[i].split(':');
			affectedRecords.push({
				table: affected[0],
				liveId: affected[2],
				versionId: affected[1]
			});
		}

		if (!integrityCheckRequired) {
			Wizard.setup.forceSelection = false;
			Backend.renderSelectionActionWizard(selectedAction, affectedRecords);
		} else {
			Workspaces.checkIntegrity(
				{
					selection: affectedRecords,
					type: 'selection'
				}
			).done(function(response) {
				Wizard.setup.forceSelection = false;
				if (response[0].result.result === 'warning') {
					Backend.addIntegrityCheckWarningToWizard();
				}
				Backend.renderSelectionActionWizard(selectedAction, affectedRecords);
			});
		}
	};

	/**
	 * Adds a slide to the wizard concerning an integrity check warning.
	 */
	Backend.addIntegrityCheckWarningToWizard = function() {
		Wizard.addSlide(
			'integrity-warning',
			'Warning',
			TYPO3.lang['integrity.hasIssuesDescription'] + '<br>' + TYPO3.lang['integrity.hasIssuesQuestion'],
			Severity.warning
		);
	};

	/**
	 * Renders the wizard for selection actions
	 *
	 * @param {String} selectedAction
	 * @param {Object} affectedRecords
	 */
	Backend.renderSelectionActionWizard = function(selectedAction, affectedRecords) {
		Wizard.addSlide(
			'mass-action-confirmation',
			TYPO3.lang['window.selectionAction.title'],
			$('<p />').text(TYPO3.lang['tooltip.' + selectedAction + 'Selected']),
			Severity.warning
		);
		Wizard.addFinalProcessingSlide(function() {
			Workspaces.sendExtDirectRequest(
				Workspaces.generateExtDirectActionsPayload('executeSelectionAction', {
					action: selectedAction,
					selection: affectedRecords
				})
			).done(function() {
				Backend.getWorkspaceInfos();
				Wizard.dismiss();
				Backend.refreshPageTree();
			});
		}).done(function() {
			Wizard.show();

			Wizard.getComponent().on('wizard-dismissed', function() {
				Backend.elements.$chooseSelectionAction.val('');
			});
		});
	};

	/**
	 * Runs a mass action
	 */
	Backend.runMassAction = function() {
		var selectedAction = Backend.elements.$chooseMassAction.val(),
			integrityCheckRequired = selectedAction !== 'discard';

		if (selectedAction.length === 0) {
			// Don't do anything if that value is empty
			return;
		}

		if (!integrityCheckRequired) {
			Wizard.setup.forceSelection = false;
			Backend.renderMassActionWizard(selectedAction);
		} else {
			Workspaces.checkIntegrity(
				{
					language: Backend.settings.language,
					type: selectedAction
				}
			).done(function(response) {
				Wizard.setup.forceSelection = false;
				if (response[0].result.result === 'warning') {
					Backend.addIntegrityCheckWarningToWizard();
				}
				Backend.renderMassActionWizard(selectedAction);
			});
		}
	};

	/**
	 * Renders the wizard for mass actions
	 *
	 * @param {String} selectedAction
	 */
	Backend.renderMassActionWizard = function(selectedAction) {
		var massAction,
			doSwap = false;

		switch (selectedAction) {
			case 'publish':
				massAction = 'publishWorkspace';
				break;
			case 'swap':
				massAction = 'publishWorkspace';
				doSwap = true;
				break;
			case 'discard':
				massAction = 'flushWorkspace';
				break;
		}

		if (massAction === null) {
			throw 'Invalid mass action ' + selectedAction + ' called.';
		}

		Wizard.setup.forceSelection = false;
		Wizard.addSlide(
			'mass-action-confirmation',
			TYPO3.lang['window.massAction.title'],
			$('<p />').html(TYPO3.lang['tooltip.' + selectedAction + 'All'] + '<br><br>' + TYPO3.lang['tooltip.affectWholeWorkspace']),
			Severity.warning
		);
		Wizard.addFinalProcessingSlide(function() {
			Workspaces.sendExtDirectRequest(
				Workspaces.generateExtDirectMassActionsPayload(massAction, {
					init: true,
					total: 0,
					processed: 0,
					language: Backend.settings.language,
					swap: doSwap
				})
			).done(function(response) {
				var payload = response[0].result;
				Workspaces.sendExtDirectRequest(
					Workspaces.generateExtDirectMassActionsPayload(massAction, payload)
				).done(function() {
					Backend.getWorkspaceInfos();
					Wizard.dismiss();
				});
			});
		}).done(function() {
			Wizard.show();

			Wizard.getComponent().on('wizard-dismissed', function() {
				Backend.elements.$chooseMassAction.val('');
			});
		});
	};

	/**
	 * Sends marked records to a stage
	 *
	 * @param {Event} e
	 */
	Backend.sendToSpecificStageAction = function(e) {
		var affectedRecords = [],
			stage = $(e.currentTarget).val();
		for (var i = 0; i < Backend.markedRecordsForMassAction.length; ++i) {
			var affected = Backend.markedRecordsForMassAction[i].split(':');
			affectedRecords.push({
				table: affected[0],
				uid: affected[1],
				t3ver_oid: affected[2]
			});
		}
		Workspaces.sendExtDirectRequest(
			Workspaces.generateExtDirectActionsPayload('sendToSpecificStageWindow', [
				stage, affectedRecords
			])
		).done(function(response) {
			var $modal = Workspaces.renderSendToStageWindow(response);
			$modal.on('button.clicked', function(e) {
				if (e.target.name === 'ok') {
					var $form = $(e.currentTarget).find('form'),
						serializedForm = $form.serializeObject();

					serializedForm.affects = {
						elements: affectedRecords,
						nextStage: stage
					};

					Workspaces.sendExtDirectRequest([
						Workspaces.generateExtDirectActionsPayload('sendToSpecificStageExecute', [serializedForm]),
						Workspaces.generateExtDirectPayload('getWorkspaceInfos', Backend.settings)
					]).done(function(response) {
						$modal.modal('hide');
						Backend.renderWorkspaceInfos(response[1].result);
						Backend.refreshPageTree();
					});
				}
			}).on('modal-destroyed', function() {
				Backend.elements.$chooseStageAction.val('');
			});
		});
	};

	/**
	 * Reloads the page tree
	 */
	Backend.refreshPageTree = function() {
		if (top.TYPO3 && top.TYPO3.Backend && top.TYPO3.Backend.NavigationContainer && top.TYPO3.Backend.NavigationContainer.PageTree) {
			top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
		}
	};

	/**
	 * Renders the action button based on the user's permission.
	 * This method is intended to be dropped once we don't the ExtDirect stuff anymore.
	 *
	 * @returns {$}
	 * @private
	 */
	Backend.getAction = function(condition, action, iconIdentifier) {
		if (condition) {
			return $('<button />', {class: 'btn btn-default', 'data-action': action, 'data-toggle': 'tooltip'}).append(Backend.getPreRenderedIcon(iconIdentifier))
		}
		return $('<span />', {class: 'btn btn-default disabled'}).append(Backend.getPreRenderedIcon('empty-empty'));
	};

	/**
	 * Fetches and renders available preview links
	 */
	Backend.generatePreviewLinks = function() {
		Workspaces.sendExtDirectRequest(
			Workspaces.generateExtDirectActionsPayload('generateWorkspacePreviewLinksForAllLanguages', [
				Backend.settings.id
			])
		).done(function(response) {
			var result = response[0].result,
				$list = $('<dl />');

			$.each(result, function(language, url) {
				$list.append(
					$('<dt />').text(language),
					$('<dd />').append(
						$('<a />', {href: url, target: '_blank'}).text(url)
					)
				);
			});

			Modal.show(
				TYPO3.lang['previewLink'],
				$list,
				Severity.info,
				[{
					text: TYPO3.lang['ok'],
					active: true,
					btnClass: 'btn-info',
					name: 'ok',
					trigger: function() {
						Modal.currentModal.trigger('modal-dismiss');
					}
				}]
			);
		});
	};

	/**
	 * Gets the pre-rendered icon
	 * This method is intended to be dropped once we use Fluid's StandaloneView.
	 *
	 * @param {String} identifier
	 * @returns {$}
	 */
	Backend.getPreRenderedIcon = function(identifier) {
		return Backend.elements.$actionIcons.find('[data-identifier="' + identifier + '"]').clone();
	};

	/**
	 * Serialize a form to a JavaScript object
	 *
	 * @see http://stackoverflow.com/a/1186309/4828813
	 * @return {Object}
	 */
	$.fn.serializeObject = function() {
		var o = {};
		var a = this.serializeArray();
		$.each(a, function() {
			if (typeof o[this.name] !== 'undefined') {
				if (!o[this.name].push) {
					o[this.name] = [o[this.name]];
				}
				o[this.name].push(this.value || '');
			} else {
				o[this.name] = this.value || '';
			}
		});
		return o;
	};

	/**
	 * Changes the markup of a pagination action being disabled
	 */
	$.fn.disablePagingAction = function() {
		$(this).addClass('disabled').find('.t3-icon').unwrap().wrap($('<span />'));
	};

	$(Backend.initialize);
});