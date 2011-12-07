Ext.define('Ext.ux.DateTimePicker', {
	extend: 'Ext.picker.Date',
	alias:['widget.datetimepicker'],

	timeFormat: 'H:i',
	showToday: true,
	ariaTitle: '',
	cls: 'typo3-datetime-picker',
	/**
	 * ExtJS 4.0.7
	 * Remove title that overlays qtips
	 */
	renderTpl: [
		'<div class="{cls}" id="{id}" role="grid">',
		    '<div role="presentation" class="{baseCls}-header">',
			'<div class="{baseCls}-prev"><a id="{id}-prevEl" href="#" role="button" title="{prevText}"></a></div>',
			'<div class="{baseCls}-month" id="{id}-middleBtnEl"></div>',
			'<div class="{baseCls}-next"><a id="{id}-nextEl" href="#" role="button" title="{nextText}"></a></div>',
		    '</div>',
		    '<table id="{id}-eventEl" class="{baseCls}-inner" cellspacing="0" role="presentation" title="{ariaTitle} {value:this.longDay}">',
			'<thead role="presentation"><tr role="presentation">',
			    '<tpl for="dayNames">',
				'<th role="columnheader" title="{.}"><span>{.:this.firstInitial}</span></th>',
			    '</tpl>',
			'</tr></thead>',
			'<tbody role="presentation"><tr role="presentation">',
			    '<tpl for="days">',
				'{#:this.isEndOfWeek}',
				'<td role="gridcell" id="{[Ext.id()]}">',
				    '<a role="presentation" href="#" hidefocus="on" class="{parent.baseCls}-date" tabIndex="1">',
					'<em role="presentation"><span role="presentation"></span></em>',
				    '</a>',
				'</td>',
			    '</tpl>',
			'</tr></tbody>',
		    '</table>',
		    '<tpl if="showToday">',
			'<div id="{id}-footerEl" role="presentation" class="{baseCls}-footer"></div>',
		    '</tpl>',
		'</div>',
		{
		    firstInitial: function(value) {
			return value.substr(0,1);
		    },
		    isEndOfWeek: function(value) {
			// convert from 1 based index to 0 based
			// by decrementing value once.
			value--;
			var end = value % 7 === 0 && value !== 0;
			return end ? '</tr><tr role="row">' : '';
		    },
		    longDay: function(value){
			return Ext.Date.format(value, this.longDayFormat);
		    }
		}
	],

	initComponent: function() {
		var t = this.timeFormat.split(':');
		this.hourFormat = t[0];
		this.minuteFormat = t[1];
		this.callParent(arguments);
	},

	/**
	 * Replaces any existing {@link #minDate} with the new value and refreshes the DatePicker.
	 * @param {Date} value The minimum date that can be selected
	 */
	setMinTime: function(dt) {
		this.minTime = dt;
		this.update(this.value, true);
	},

	/**
	 * Replaces any existing {@link #maxDate} with the new value and refreshes the DatePicker.
	 * @param {Date} value The maximum date that can be selected
	 */
	setMaxTime: function(dt) {
		this.maxTime = dt;
		this.update(this.value, true);
	},

	/**
	 * Returns the value of the date/time field
	 */
	getValue: function() {
		return this.addTimeToValue(this.value);
	},

	/**
	 * Sets the value of the date/time field
	 * @param {Date} value The date to set
	 */
	setValue: function(value) {
		var old = this.value;
		this.value = Ext.Date.clearTime(value,true);
		if (this.el) {
			this.update(this.value);
		}
		this.hourField.setValue(Ext.Date.format(value, this.hourFormat));
		this.minuteField.setValue(Ext.Date.format(value, this.minuteFormat));
	},

	/**
	 * Sets the value of the time field
	 * @param {Date} value The date to set
	 */
	setTime: function(value) {
		this.hourField.setValue(Ext.Date.format(value, this.hourFormat));
		this.minuteField.setValue(Ext.Date.format(value, this.minuteFormat));
	},

	/**
	 * Updates the date value with the time entered
	 * @param {Date} value The date to which time should be added
	 */
	addTimeToValue: function(date) {
		var localDate = Ext.Date.clearTime(date);
		return Ext.Date.add(Ext.Date.add(localDate, Ext.Date.HOUR, this.hourField.inputEl.getValue()), Ext.Date.MINUTE, this.minuteField.inputEl.getValue());
	},

	onRender: function (container, position) {
		var me = this,
			days = new Array(me.numDays),
			today = Ext.Date.format(new Date(), me.format);

		me.callParent(arguments);
			// Destroying today button created by the parent class
		me.todayBtn.destroy();

		me.formPanel = Ext.create('Ext.form.Panel', {
			cls: 'typo3-datetime-picker-footer',
			layout: 'column',
			renderTo: me.footerEl,
			defaults: {
				labelAlign: 'left',
				labelSeparator: ''
			},
			items: [{
					columnWidth: .3,
					xtype: 'textfield',
					cls: 'typo3-datetime-picker-hour',
					id: this.getId() + '_hour',
					maxLength: 2,
					fieldLabel: ' ',
					labelWidth: 16,
					width: 20,
					minValue: 0,
					maxValue: 24,
					allowBlank: false,
					tabIndex: 1,
					maskRe: /[0-9]/
				},{
					columnWidth: .3,
					xtype: 'textfield',
					cls: 'typo3-datetime-picker-minute',
					id: this.getId() + '_minute',
					maxLength: 2,
					fieldLabel: ' :',
					labelWidth: 5,
					width: 20,
					minValue: 0,
					maxValue: 59,
					allowBlank: false,
					tabIndex: 2,
					maskRe: /[0-9]/
				}, Ext.create('Ext.button.Button', {
					columnWidth: .4,
					text: Ext.String.format(me.todayText, today),
					tooltip: Ext.String.format(me.todayTip, today),
					handler: me.selectToday,
					scope: me
				})
			]
		});

		this.hourField = Ext.getCmp(this.getId() + '_hour');
		this.minuteField = Ext.getCmp(this.getId() + '_minute');

		this.hourField.on('blur', function(field) {
			var old = field.value;
			var h = parseInt(field.getValue());
			if (h > 23) {
				field.setValue(old);
			}
		});

		this.minuteField.on('blur', function(field) {
			var old = field.value;
			var h = parseInt(field.getValue());
			if (h > 59) {
				field.setValue(old);
			}
		});

		if (Ext.isIE) {
			this.el.repaint();
		}
	},

		// private
	handleDateClick: function (e, t) {
		e.stopEvent();
		if (t.dateValue && !Ext.fly(t.parentNode).hasCls("x-date-disabled")) {
			this.setValue(this.addTimeToValue(new Date(t.dateValue)));
			this.fireEvent("select", this, this.value);
		}
	},

	selectToday: function() {
		if (this.todayBtn && !this.todayBtn.disabled) {
			this.setValue(new Date());
			this.fireEvent("select", this, this.value);
		}
	},

	update: function(date, forceRefresh) {
		this.callParent(arguments);
		if (this.showToday) {
			this.setTime(new Date());
		}
	}
});

Ext.define('Ext.ux.menu.DateTimeMenu', {
	extend: 'Ext.menu.Menu',
	alias: ['widget.datetimemenu'],
	floating: true,
	enableScrolling: false,
	hideOnClick: true,
	cls: 'x-date-menu x-datetime-menu',
	initComponent: function () {
		Ext.apply(this, {
			plain: true,
			showSeparator: false,
			items: this.picker = Ext.create('Ext.ux.DateTimePicker', Ext.apply({
				internalRender: this.strict || !Ext.isIE,
				ctCls: 'x-menu-datetime-item x-menu-date-item'
			}, this.initialConfig))
		});
		this.picker.clearListeners();

		this.callParent(arguments);
		this.relayEvents(this.picker, ['select']);
		this.on('select', this.menuHide, this);
		if (this.handler) {
			this.on('select', this.handler, this.scope || this);
		}
	},
	menuHide: function() {
		if (this.hideOnClick) {
			this.hide();
		}
	}
});