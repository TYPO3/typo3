Ext.ns('Ext.ux', 'Ext.ux.menu', 'Ext.ux.form');

Ext.ux.DateTimePicker = Ext.extend(Ext.DatePicker, {

	timeFormat: 'H:i',

	initComponent: function() {
		var t = this.timeFormat.split(':');
		this.hourFormat = t[0];
		this.minuteFormat = t[1];

		Ext.ux.DateTimePicker.superclass.initComponent.call(this);
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
		this.value = value.clearTime(true);
		if (this.el) {
			this.update(this.value);
		}
		this.hourField.setValue(value.format(this.hourFormat));
		this.minuteField.setValue(value.format(this.minuteFormat));
	},

	/**
	 * Sets the value of the time field
	 * @param {Date} value The date to set
	 */
	setTime: function(value) {
		this.hourField.setValue(value.format(this.hourFormat));
		this.minuteField.setValue(value.format(this.minuteFormat));
	},

	/**
	 * Updates the date value with the time entered
	 * @param {Date} value The date to which time should be added
	 */
	addTimeToValue: function(date) {
		return date.clearTime().add(Date.HOUR, this.hourField.getValue()).add(Date.MINUTE, this.minuteField.getValue());
	},

	onRender: function(container, position) {
		var m = [
			'<table cellspacing="0">',
			'<tr><td class="x-date-left"><a href="#" title="',
			this.prevText ,
			'">&#160;</a></td><td class="x-date-middle" align="center"></td><td class="x-date-right"><a href="#" title="',
			this.nextText ,
			'">&#160;</a></td></tr>',
			'<tr><td colspan="3"><table class="x-date-inner" cellspacing="0"><thead><tr>'
		];
		var dn = this.dayNames;
		for (var i = 0; i < 7; i++) {
			var d = this.startDay + i;
			if (d > 6) {
				d = d - 7;
			}
			m.push('<th><span>', dn[d].substr(0, 1), '</span></th>');
		}
		m[m.length] = "</tr></thead><tbody><tr>";
		for (var i = 0; i < 42; i++) {
			if (i % 7 == 0 && i != 0) {
				m[m.length] = "</tr><tr>";
			}
			m[m.length] = '<td><a href="#" hidefocus="on" class="x-date-date" tabIndex="1"><em><span></span></em></a></td>';
		}
		m.push('</tr></tbody></table></td></tr>',
			this.showToday ? '<tr><td colspan="3" class="x-date-bottom" align="center"></td></tr>' : '',
			'</table><div class="x-date-mp"></div>'
		);

		var el = document.createElement("div");
		el.className = "x-date-picker";
		el.innerHTML = m.join("");

		container.dom.insertBefore(el, position);

		this.el = Ext.get(el);
		this.eventEl = Ext.get(el.firstChild);

		new Ext.util.ClickRepeater(this.el.child("td.x-date-left a"), {
			handler: this.showPrevMonth,
			scope: this,
			preventDefault:true,
			stopDefault:true
		});

		new Ext.util.ClickRepeater(this.el.child("td.x-date-right a"), {
			handler: this.showNextMonth,
			scope: this,
			preventDefault:true,
			stopDefault:true
		});

		this.mon(this.eventEl, "mousewheel", this.handleMouseWheel, this);

		this.monthPicker = this.el.down('div.x-date-mp');
		this.monthPicker.enableDisplayMode('block');

		var kn = new Ext.KeyNav(this.eventEl, {
			"left": function(e) {
				e.ctrlKey ?
					this.showPrevMonth() :
					this.update(this.activeDate.add("d", -1));
			},

			"right": function(e) {
				e.ctrlKey ?
					this.showNextMonth() :
					this.update(this.activeDate.add("d", 1));
			},

			"up": function(e) {
				e.ctrlKey ?
					this.showNextYear() :
					this.update(this.activeDate.add("d", -7));
			},

			"down": function(e) {
				e.ctrlKey ?
					this.showPrevYear() :
					this.update(this.activeDate.add("d", 7));
			},

			"pageUp": function(e) {
				this.showNextMonth();
			},

			"pageDown": function(e) {
				this.showPrevMonth();
			},

			"enter": function(e) {
				e.stopPropagation();
				this.fireEvent("select", this, this.value);
				return true;
			},

			scope : this
		});

		this.mon(this.eventEl, "click", this.handleDateClick, this, {delegate: "a.x-date-date"});

		this.el.select("table.x-date-inner").unselectable();
		this.cells = this.el.select("table.x-date-inner tbody td");
		this.textNodes = this.el.query("table.x-date-inner tbody span");

		this.mbtn = new Ext.Button({
			text: "&#160;",
			tooltip: this.monthYearText,
			renderTo: this.el.child("td.x-date-middle", true)
		});

		this.mon(this.mbtn, 'click', this.showMonthPicker, this);
		this.mbtn.el.child('em').addClass("x-btn-arrow");

		if (this.showToday) {
			this.todayKeyListener = this.eventEl.addKeyListener(Ext.EventObject.SPACE, this.selectToday, this);
			var today = (new Date()).dateFormat(this.format);
			this.todayBtn = new Ext.Button({
				text: String.format(this.todayText, today),
				tooltip: String.format(this.todayTip, today),
				handler: this.selectToday,
				scope: this
			});
		}

		this.formPanel = new Ext.form.FormPanel({
			layout: 'column',
			renderTo: this.el.child("td.x-date-bottom", true),
			baseCls: 'x-plain',
			hideBorders: true,
			labelAlign: 'left',
			labelWidth: 10,
			forceLayout: true,
			items: [
				{
					columnWidth: .4,
					layout: 'form',
					baseCls: 'x-plain',
					items: [
						{
							xtype: 'textfield',
							id: this.getId() + '_hour',
							maxLength: 2,
							fieldLabel: '',
							labelWidth: 30,
							width: 30,
							minValue: 0,
							maxValue: 24,
							allowBlank: false,
							labelSeparator: '',
							tabIndex: 1,
							maskRe: /[0-9]/
						}
					]
				},
				{
					columnWidth: .3,
					layout: 'form',
					baseCls: 'x-plain',
					items: [
						{
							xtype: 'textfield',
							id:	this.getId() + '_minute',
							maxLength: 2,
							fieldLabel: ':',
							labelWidth: 10,
							width: 30,
							minValue: 0,
							maxValue: 59,
							allowBlank: false,
							labelSeparator: '',
							tabIndex: 2,
							maskRe: /[0-9]/
						}
					]
				},
				{
					columnWidth: .3,
					layout: 'form',
					baseCls: 'x-plain',
					items: [this.todayBtn]
				}
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
		this.update(this.value);
	},

	// private
	handleDateClick : function(e, t) {
		e.stopEvent();
		if (t.dateValue && !Ext.fly(t.parentNode).hasClass("x-date-disabled")) {
			this.setValue(this.addTimeToValue(new Date(t.dateValue)));
			this.fireEvent("select", this, this.value);
		}
	},

	selectToday : function() {
		if (this.todayBtn && !this.todayBtn.disabled) {
			this.setValue(new Date());
			this.fireEvent("select", this, this.value);
		}
	},

	update : function(date, forceRefresh) {
		Ext.ux.DateTimePicker.superclass.update.call(this, date, forceRefresh);

		if (this.showToday) {
			this.setTime(new Date());
		}
	}
});
Ext.reg('datetimepicker', Ext.ux.DateTimePicker);


Ext.ux.menu.DateTimeMenu = Ext.extend(Ext.menu.Menu, {
	enableScrolling : false,
	hideOnClick : true,
	cls: 'x-date-menu x-datetime-menu',
	initComponent : function() {

		Ext.apply(this, {
			plain: true,
			showSeparator: false,
			items: this.picker = new Ext.ux.DateTimePicker(Ext.apply({
				internalRender: this.strict || !Ext.isIE,
				ctCls: 'x-menu-datetime-item x-menu-date-item'
			}, this.initialConfig))
		});
		this.picker.purgeListeners();

		Ext.ux.menu.DateTimeMenu.superclass.initComponent.call(this);
		this.relayEvents(this.picker, ['select']);
		this.on('select', this.menuHide, this);
		if (this.handler) {
			this.on('select', this.handler, this.scope || this)
		}
	},
	menuHide: function() {
		if (this.hideOnClick) {
			this.hide(true);
		}
	}
});
Ext.reg('datetimemenu', Ext.ux.menu.DateTimeMenu);