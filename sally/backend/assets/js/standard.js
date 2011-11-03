/**
 * SallyCMS - JavaScript-Bibliothek
 */

var sly = {};

// do not use dots inside callback names
var slyLinkWidgetCallback  = null;
var slyMediaWidgetCallback = null;

(function($, sly, undef) {
	/////////////////////////////////////////////////////////////////////////////
	// Popups

	var openPopups = [];

	sly.Popup = function(name, url, posx, posy, width, height, extra) {
		this.name = name;
		this.url  = url;
		this.obj  = window.open(url, name, 'width='+width+',height='+height+extra);

		this.obj.moveTo(posx, posy);
		this.obj.focus();

		openPopups[name] = this;

		this.close = function() {
			this.obj.close();
		};

		this.setGlobal = function(name, value) {
			this.obj[name] = value;
		};
	};

	sly.closeAllPopups = function() {
		for (var name in openPopups) {
			if (openPopups.hasOwnProperty(name)) {
				openPopups[name].close();
			}
		}
	};

	sly.openCenteredPopup = function(name, link, width, height, extra) {
		if (width === 0)  width  = 550;
		if (height === 0) height = 400;

		if (extra === undef) {
			extra = ',scrollbars=yes,toolbar=no,status=yes,resizable=yes';
		}

		var posx = parseInt((screen.width-width) / 2, 10);
		var posy = parseInt((screen.height-height) / 2, 10) - 24;

		return new sly.Popup(name, link, posx, posy, width, height, extra);
	};

	/////////////////////////////////////////////////////////////////////////////
	// Mediapool

	sly.openMediapool = function(subpage, value, callback) {
		var url = 'index.php?page=mediapool';

		if (value) {
			url += '_detail&file_name='+value;
		}
		else if (subpage && (subpage != 'detail' || value)) {
			url += '_' + subpage;
		}

		if (callback) {
			url += '&callback='+callback;
		}

		return sly.openCenteredPopup('slymediapool', url, 760, 600);
	};

	sly.openLinkmap = function(value, callback) {
		var url = 'index.php?page=linkmap';

		if (value) {
			url += '&category_id='+value;
		}

		if (callback) {
			url += '&callback='+callback;
		}

		return sly.openCenteredPopup('slylinkmap', url, 760, 600);
	};

	/////////////////////////////////////////////////////////////////////////////
	// Helper

	var inherit = function(subClass, baseClass) {
		var tmpClass = function() {};
		tmpClass.prototype = baseClass.prototype;
		subClass.prototype = new tmpClass();
	};

	/////////////////////////////////////////////////////////////////////////////
	// Abstract Widget

	sly.AbstractWidget = function(elem) {
		this.element    = $(elem);
		this.valueInput = this.element.find('input[rel=value]');
		this.nameInput  = this.element.find('input[rel=name]');

		// register events
		var icons = this.element.find('.sly-icons');
		icons.delegate('a[rel=open]',   'click', $.proxy(this.onOpen,   this));
		icons.delegate('a[rel=add]',    'click', $.proxy(this.onAdd,    this));
		icons.delegate('a[rel=delete]', 'click', $.proxy(this.onDelete, this));
	};

	sly.AbstractWidget.prototype = {
		getValue: function() {
			return this.valueInput.val();
		},

		setValue: function(identifier, title) {
			this.valueInput.val(identifier);
			this.nameInput.val(title);
			return true; // signalize the popup to close itself
		},

		clear: function() {
			this.setValue('', '');
		},

		onOpen: function() {
			return false;
		},

		onAdd: function() {
			return false;
		},

		onDelete: function() {
			this.clear();
			return false;
		}
	};

	/////////////////////////////////////////////////////////////////////////////
	// Media Widgets

	sly.MediaWidget = function(elem) {
		sly.AbstractWidget.call(this, elem);
	};

	inherit(sly.MediaWidget, sly.AbstractWidget);

	sly.MediaWidget.prototype.onOpen = function() {
		sly.openMediapool('detail', this.getValue(), 'slyMediaWidgetCallback');
		slyMediaWidgetCallback = $.proxy(this.setValue, this);
		return false;
	};

	sly.MediaWidget.prototype.onAdd = function() {
		sly.openMediapool('upload', '', 'slyMediaWidgetCallback');
		slyMediaWidgetCallback = $.proxy(this.setValue, this);
		return false;
	};

	/////////////////////////////////////////////////////////////////////////////
	// Link Widgets

	sly.LinkWidget = function(elem) {
		sly.AbstractWidget.call(this, elem);
	};

	inherit(sly.LinkWidget, sly.AbstractWidget);

	sly.LinkWidget.prototype.onOpen = function() {
		var catID = this.element.data('catid');

		sly.openLinkmap(catID, 'slyLinkWidgetCallback');
		slyLinkWidgetCallback = $.proxy(this.setValue, this);

		return false;
	};

	/////////////////////////////////////////////////////////////////////////////
	// Generic lists

	sly.AbstractListWidget = function(elem) {
		this.element = $(elem);
		this.input   = this.element.find('input');
		this.list    = this.element.find('select');

		// register events
		var icons = this.element.find('.sly-icons.move');
		icons.delegate('a', 'click', $.proxy(this.onMove, this));

		icons = this.element.find('.sly-icons.edit');
		icons.delegate('a[rel=open]',   'click', $.proxy(this.onOpen,   this));
		icons.delegate('a[rel=add]',    'click', $.proxy(this.onAdd,    this));
		icons.delegate('a[rel=delete]', 'click', $.proxy(this.onDelete, this));
	};

	sly.AbstractListWidget.prototype = {
		getElements: function() {
			var options = this.list.find('option');
			var result  = [];

			for (var i = 0, len = options.length; i < len; ++i) {
				result.push(options[i].value);
			}

			return result;
		},

		getSelected: function() {
			var selected = this.list.find('option:selected');
			return selected.length ? selected.val() : null;
		},

		clear: function() {
			this.list.find('option').remove();
			this.input.val('');
		},

		addValue: function(identifier, title) {
			this.list.append($('<option>').val(identifier).text(title));
			this.createList();
			return false; // signalize the popup to keep open
		},

		onDelete: function() {
			var selected = this.list.find('option:selected');

			if (selected.length === 0) {
				return false;
			}

			// find the element to select
			var toSelect = selected.next();

			if (toSelect.length === 0) {
				toSelect = selected.prev();
			}

			// remove element and mark the next/prev one
			selected.remove();
			toSelect.prop('selected', true);

			// re-create the <input>
			this.createList();

			// done
			return false;
		},

		onMove: function(event) {
			var direction = $(event.target).closest('a').attr('rel');
			var list      = this.list;
			var selected  = list.find('option:selected');

			if (selected.length === 0) {
				return false;
			}

			if (direction === 'top') {
				list.prepend(selected);
			}
			else if (direction === 'up') {
				selected.prev().insertAfter(selected);
			}
			else if (direction === 'down') {
				selected.next().insertBefore(selected);
			}
			else if (direction === 'bottom') {
				list.append(selected);
			}

			this.createList();
			return false;
		},

		createList: function() {
			this.input.val(this.getElements().join(','));
		},

		onOpen: function() {
			return false;
		},

		onAdd: function() {
			return false;
		}
	};

	/////////////////////////////////////////////////////////////////////////////
	// Medialist Widgets

	sly.MedialistWidget = function(elem) {
		sly.AbstractListWidget.call(this, elem);
	};

	inherit(sly.MedialistWidget, sly.AbstractListWidget);

	sly.MedialistWidget.prototype.onOpen = function() {
		sly.openMediapool('detail', this.getSelected(), 'slyMediaWidgetCallback');
		slyMediaWidgetCallback = $.proxy(this.addValue, this);
		return false;
	};

	sly.MedialistWidget.prototype.onAdd = function() {
		sly.openMediapool('upload', '', 'slyMediaWidgetCallback');
		slyMediaWidgetCallback = $.proxy(this.addValue, this);
		return false;
	};

	/////////////////////////////////////////////////////////////////////////////
	// Linklist Widgets

	sly.LinklistWidget = function(elem) {
		sly.AbstractListWidget.call(this, elem);
	};

	inherit(sly.LinklistWidget, sly.AbstractListWidget);

	sly.LinklistWidget.prototype.onOpen = function() {
		var catID = this.element.data('catid');

		sly.openLinkmap(catID, 'slyLinkWidgetCallback');
		slyLinkWidgetCallback = $.proxy(this.addValue, this);

		return false;
	};

	/////////////////////////////////////////////////////////////////////////////
	// Misc functions

	sly.disableLogin = function(timerElement) {
		var nextTime = parseInt(timerElement.html(), 10) - 1;

		timerElement.html(nextTime + '');

		if (nextTime > 0) {
			setTimeout(sly.disableLogin, 1000, timerElement);
		}
		else {
			$('div.rex-message p span').html($('#loginformular').data('message'));
			$('#loginformular input:not(:hidden)').prop('disabled', false);
			$('#rex_user_login').focus();
		}
	};

	sly.startLoginTimer = function(message) {
		var timerElement = $('div.rex-message p span strong');

		if (timerElement.length == 1) {
			$('#loginformular input:not(:hidden)').prop('disabled', true);
			$('#loginformular').data('message', message);
			setTimeout(sly.disableLogin, 1000, timerElement);
		}
	};

	sly.setModernizrCookie = function() {
		if (typeof Modernizr === 'undefined') return false;

		var contents = [];

		for (var group in Modernizr) {
			if (Modernizr.hasOwnProperty(group)) {
				var val = Modernizr[group];
				group = '"' + group + '"';

				if (typeof val === 'object') {
					var list = [];

					for (var capability in val) {
						list.push('"' + capability + '":' + (val[capability] ? 1 : 0));
					}

					contents.push(group + ':{' + list.join(',') + '}');
				}
				else {
					contents.push(group + ':"' + val + '"');
				}
			}
		}

		contents = '{' + contents.join(',') + '}';
		document.cookie = 'sly_modernizr='+escape(contents);
	};

	sly.addDatepickerToggler = function(picker, value) {
		var name     = picker.attr('name');
		var input    = $('<input type="hidden" value="" />').attr('name', (value === 0 ? '' : '_')+name);
		var span     = $('<span class="sly-date-disabled" style="cursor:pointer">(&hellip;)</span>');
		var checkbox = $('<input type="checkbox" value="1" class="sly-form-checkbox" />');

		span.click(function() {
			$(this).prevAll().click();
		});

		checkbox.change(function() {
			var on = this.checked;

			picker.toggle(on).attr('name', (on?'':'_')+name);
			span.toggle(!on);
			input.attr('name', (on?'_':'')+name);
		});

		picker.before(checkbox).after(input).after(span);

		if (value !== 0) {
			checkbox.prop('checked', true);
			span.hide();
		}
		else {
			checkbox.prop('checked', false);
			picker.hide();
		}
	};

	var catsChecked = function() {
		var c_checked = $('#userperm_cat_all').prop('checked');
		var m_checked = $('#userperm_media_all').prop('checked');
		var slider    = $('#sly-page-user .sly-form .rex-form-wrapper .sly-num7');

		$('#userperm_cat').prop('disabled', c_checked);
		$('#userperm_media').prop('disabled', m_checked);

		if (c_checked && m_checked)
			slider.slideUp('slow');
		else
			slider.slideDown('slow');
	};

	var updateStartpageSelect = function() {
		var isAdmin   = $('#is_admin').is(':checked');
		var hasPerms  = $('#userperm_sprachen').val() ? true : false;
		var list      = $('#userperm_startpage');
		var structure = list.find('option[value=structure]');
		var isStruct  = structure.is(':selected');

		if (isAdmin || hasPerms) {
			structure.prop('disabled', false);
		}
		else {
			structure.prop('disabled', true);
			if (isStruct) list.find('option[value=profile]').prop('selected', true);
		}
	};

	/////////////////////////////////////////////////////////////////////////////
	// dom:loaded handler

	$(function() {
		// Init widgets

		$('.sly-widget').each(function() {
			var self = $(this);

			if (self.is('.sly-link')) {
				new sly.LinkWidget(this);
			}
			else if (self.is('.sly-media')) {
				new sly.MediaWidget(this);
			}
			else if (self.is('.sly-linklist')) {
				new sly.LinklistWidget(this);
			}
			else if (self.is('.sly-medialist')) {
				new sly.MedialistWidget(this);
			}
		});

		// "check all" function for mediapool

		$('.sly-check-all').click(function() {
			var target = $(this).data('target');
			$('input[name=\'' + target + '\']').prop('checked', this.checked);
		});

		// Lösch-Links in Tabellen

		$('table.sly-table').delegate('a.sly-delete, input.sly-button-delete', 'click', function() {
			var table    = $(this).parents('table');
			var question = table.attr('rel');

			if (!question) {
				question = 'Löschen?';
			}

			return confirm(question);
		});

		// Filter-Funktionen in sly_Table

		$('.sly-table-extras-filter input[class^=filter_input_]').keyup(function(event) {
			// Klassen- und Tabellennamen ermitteln

			var
				className = $(this).attr('class'),
				tableName = className.replace('filter_input_', ''),
				table     = $('#' + tableName),
				c         = event.keyCode;

			// Wert auch in allen anderen Suchfeldern übernehmen

			$('input.' + className).val($(this).val());

			// Tabelle filtern

			event.preventDefault();

			if (c == 8 || c == 46 || c == 109 || c == 189 || (c >= 65 && c <= 90) || (c >= 48 && c <= 57)) {
				var keyword = new RegExp($(this).val(), 'i');

				$('tbody tr', table).each(function() {
					var $tr = $(this);
					$('td', $tr).filter(function() {
						return keyword.test($(this).text());
					}).length ? $tr.show() : $tr.hide();
				});
			}
		});

		// Links in neuem Fenster öffnen

		$('a.sly-blank').attr('target', '_blank');

	   // open mediapool in popup

		$('#rex-navi-page-mediapool a').click(function() {
			sly.openMediapool();
			return false;
		});

		// Login-Formular

		if ($('#rex-form-login').length > 0) {
			$('#rex-form-login').focus();
			$('#javascript').val('1');
		}

		// Benutzer-Formular

		if ($('#sly-page-user .sly-form').length > 0) {
			var wrapper = $('#sly-page-user .sly-form .rex-form-wrapper');
			var sliders = wrapper.find('.sly-num6,.sly-num7');

			$('#is_admin').change(function() {
				if ($(this).is(':checked')) {
					$('#userperm_module').prop('disabled', true);
					sliders.slideUp('slow');
				}
				else {
					$('#userperm_module').prop('disabled', false);
					sliders.slideDown('slow');
					catsChecked();
				}
			});

			catsChecked();
			$('#userperm_cat_all, #userperm_media_all').change(catsChecked);

			// init behaviour

			if ($('#is_admin').is(':checked')) {
				$('#userperm_module').prop('disabled', true);
				sliders.hide();
			}

			if ($('#userperm_cat_all').is(':checked') && $('#userperm_media_all').is(':checked')) {
				wrapper.find('.sly-num7').hide();
			}

			// remove structure from list of possible startpages as long as the
			// user neither is admin nor has any language permissions

			updateStartpageSelect();
			$('#is_admin, #userperm_sprachen').change(updateStartpageSelect);
		}

		// Formularframework

		$('.sly-form .sly-select-checkbox-list a').live('click', function() {
			var rel   = $(this).attr('rel');
			var boxes = $(this).parents('p').find('input');
			boxes.prop('checked', rel === 'all');
			return false;
		});

		// allen vom Browser unterstützten Elementen die Klasse ua-supported geben

		if (typeof Modernizr !== 'undefined') {
			var types = Modernizr.inputtypes;

			for (var type in types) {
				if (types.hasOwnProperty(type) && types[type]) {
					$('input[type='+type+']').addClass('ua-supported');
				}
			}
		}

		// Fallback-Implementierung für type=range via jQuery UI Slider

		$('input[type=range]:not(.ua-supported)').each(function() {
			var input  = $(this);
			var slider = $('<div></div>').attr('id', input.attr('id') + '-slider');
			var hidden = $('<input type="hidden" value="" />');

			// remove the old input element and replace it with a new, hidden one
			input.after(hidden);
			hidden.val(input.val()).attr('name', input.attr('name')).attr('id', input.attr('id'));

			// create a new div that will be the slider
			input.after(slider);
			slider.addClass('sly-slider').slider({
				min:    input.attr('min'),
				max:    input.attr('max'),
				value:  input.val(),
				change: function(event) {
					hidden.val(slider.slider('value'));
				}
			});

			// remove it
			input.remove();
		});

		// Mehrsprachige Formulare initialisieren

		// Checkboxen erzeugen

		$('.rex-form-row.sly-form-multilingual').each(function() {
			var
				$this   = $(this),
				equal   = $this.is(':visible'),
				id      = $this.attr('rel'),
				checked = equal ? ' checked="checked"': '',
				span    = '<span class="sly-form-i18n-switch"><input type="checkbox" name="equal__'+id+'" id="equal__'+id+'" value="1"'+checked+' /><\/span>';

			if (equal) {
				$('label:first', this).after(span);
			}
			else {
				var container = $this.next('div.sly-form-i18n-container');
				$('label:first', container).after(span);
			}
		});

		// Checkboxen initialisieren

		var checkboxes = $('.sly-form-i18n-switch input[id^=equal__]');

		if (checkboxes.length > 0) {
			checkboxes.imgCheckbox('off.png', 'on.png', 'assets/form-i18n-switch-').next().click(function() {
				var
					checkbox       = $(this).prev('input'),
					shown          = !checkbox[0].checked, // was already changed before this event handler
					id             = checkbox.attr('id').replace(/^equal__/, ''),
					container      = $('div[rel="'+id+'"]'),
					span           = checkbox.parent('span'),
					equalcontainer = $('div.sly-form-i18n-container.c-'+id);

				if (shown) {
					container.hide();
					equalcontainer.show();
					$('label:first', equalcontainer).after(span);
				}
				else {
					container.show();
					equalcontainer.hide();
					$('label:first', container).after(span);
				}
			});
		}

		// Module selection on content page

		$('.sly-module-select').change(function() {
			$(this).closest('form').submit();
		});

		$('body.sly-popup').unload(sly.closeAllPopups);
	});
})(jQuery, sly);
