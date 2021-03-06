/**
 * SallyCMS - JavaScript-Bibliothek
 */

/*global window:false, screen:false, document:false, navigator:false, Modernizr:false, jQuery:false, confirm:false, escape:false*/

var sly = {};

(function($, sly, win, undef) {
	/////////////////////////////////////////////////////////////////////////////
	// Popups

	var openPopups = [];

	sly.Popup = function(name, url, posx, posy, width, height, extra) {
		var ua = navigator.userAgent;

		// ensure names are somewhat unique
		name += (new Date()).getTime();

		this.name = name;
		this.url  = url;
		this.obj  = win.open(url, name, 'width='+width+',height='+height+extra);

		// Don't position the popup in Chrome 18 and 20.
		//   bug details: http://code.google.com/p/chromium/issues/detail?id=114762
		//   workaround:  http://code.google.com/p/chromium/issues/detail?id=115585
		// Remove this once Chrome 18/20 is not used anymore (~ September 2012)

		if (ua.indexOf('Chrome/18.') === -1 && ua.indexOf('Chrome/20.') === -1) {
			this.obj.moveTo(posx, posy);
			this.obj.focus();
		}

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

	sly.openMediapool = function(subpage, value, callback, filetypes, categories) {
		var url = 'index.php?page=mediapool';

		if (value) {
			url += '_detail&file_name='+value;
		}
		else if (subpage && (subpage !== 'detail' || value)) {
			url += '_' + subpage;
		}

		if (callback) {
			url += '&callback='+callback;
		}

		if ($.isArray(filetypes) && filetypes.length > 0) {
			url += '&args[types]='+filetypes.join('|');
		}

		if ($.isArray(categories) && categories.length > 0) {
			url += '&args[categories]='+categories.join('|');
		}

		return sly.openCenteredPopup('slymediapool', url, 760, 600);
	};

	sly.openLinkmap = function(value, callback, articletypes, categories) {
		var url = 'index.php?page=linkmap';

		if (value) {
			url += '&category_id='+value;
		}

		if (callback) {
			url += '&callback='+callback;
		}

		if ($.isArray(articletypes) && articletypes.length > 0) {
			url += '&args[types]='+articletypes.join('|');
		}

		if ($.isArray(categories) && categories.length > 0) {
			url += '&args[categories]='+categories.join('|');
		}

		return sly.openCenteredPopup('slylinkmap', url, 760, 600);
	};

	/////////////////////////////////////////////////////////////////////////////
	// Helper

	sly.inherit = function(subClass, baseClass) {
		var tmpClass = function() {};
		tmpClass.prototype = baseClass.prototype;
		subClass.prototype = new tmpClass();
	};

	var getCallbackName = function(base) {
		return base + (new Date()).getTime();
	};

	var readLists = function(el, name) {
		var values = ((el.data(name) || '')+'').split('|'), len = values.length, i = 0, res = [];

		for (; i < len; ++i) {
			if (values[i].length > 0) {
				res.push(values[i]);
			}
		}

		return res;
	};

	/////////////////////////////////////////////////////////////////////////////
	// Abstract Widget

	sly.AbstractWidget = function(elem) {
		this.element    = $(elem);
		this.valueInput = this.element.find('input.value');
		this.nameInput  = this.element.find('input.name');

		// register events
		var icons = this.element.find('.sly-icons');
		icons.delegate('a.fct-open',   'click', $.proxy(this.onOpen,   this));
		icons.delegate('a.fct-add',    'click', $.proxy(this.onAdd,    this));
		icons.delegate('a.fct-delete', 'click', $.proxy(this.onDelete, this));
	};

	sly.AbstractWidget.prototype = {
		getValue: function() {
			return this.valueInput.val();
		},

		setValue: function(identifier, title) {
			this.valueInput.val(identifier);
			this.nameInput.val(title);

			// notify listeners
			this.valueInput.change();

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

		this.filetypes  = readLists(this.element, 'filetypes');
		this.categories = readLists(this.element, 'categories');
	};

	sly.inherit(sly.MediaWidget, sly.AbstractWidget);

	sly.MediaWidget.prototype.onOpen = function() {
		var cb = getCallbackName('slymediawidget');
		sly.openMediapool('detail', this.getValue(), cb, this.filetypes, this.categories);
		win[cb] = $.proxy(this.setValue, this);
		return false;
	};

	sly.MediaWidget.prototype.onAdd = function() {
		var cb = getCallbackName('slymediawidget');
		sly.openMediapool('upload', '', cb, this.filetypes, this.categories);
		win[cb] = $.proxy(this.setValue, this);
		return false;
	};

	/////////////////////////////////////////////////////////////////////////////
	// Link Widgets

	sly.LinkWidget = function(elem) {
		sly.AbstractWidget.call(this, elem);

		this.articletypes = readLists(this.element, 'articletypes');
		this.categories   = readLists(this.element, 'categories');
	};

	sly.inherit(sly.LinkWidget, sly.AbstractWidget);

	sly.LinkWidget.prototype.onOpen = function() {
		var catID = this.element.data('catid'), cb = getCallbackName('slylinkwidget');

		sly.openLinkmap(catID, cb, this.articletypes, this.categories);
		win[cb] = $.proxy(this.setValue, this);

		return false;
	};

	/////////////////////////////////////////////////////////////////////////////
	// Generic lists

	sly.AbstractListWidget = function(elem) {
		this.element = $(elem);
		this.input   = this.element.find('input[type=hidden]');
		this.list    = this.element.find('select');
		this.min     = this.element.data('min') || 0;
		this.max     = this.element.data('max') || -1;

		// register events
		var icons = this.element.find('.sly-icons.move');
		icons.delegate('a', 'click', $.proxy(this.onMove, this));

		icons = this.element.find('.sly-icons.edit');
		icons.delegate('a.fct-open',   'click', $.proxy(this.onOpen,   this));
		icons.delegate('a.fct-add',    'click', $.proxy(this.onAdd,    this));
		icons.delegate('a.fct-delete', 'click', $.proxy(this.onDelete, this));
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

		getCount: function() {
			return this.list.find('option').length;
		},

		getSelected: function() {
			var selected = this.list.find('option:selected');
			return selected.length ? selected.val() : null;
		},

		clear: function() {
			this.list.find('option').remove();
			this.input.val('').change();
		},

		addValue: function(identifier, title) {
			var count = this.getCount(), max = this.max;

			if (count === max) {
				return true; // close the popup
			}

			this.list.append($('<option>').val(identifier).text(title));
			this.createList();

			var full = (count+1) === max;

			if (full) {
				this.element.addClass('at-max');
			}

			this.element.toggleClass('at-min', (count+1) <= this.min);

			// close if the maximum number of elements is reached
			return full;
		},

		onDelete: function() {
			var selected = this.list.find('option:selected');

			if (selected.length === 0) {
				return false;
			}

			// only delete as many elements as we are allowed to
			var possible = this.getCount() - this.min;

			if (possible === 0) {
				return false;
			}

			selected = selected.slice(0, possible);

			// find the element to select
			var toSelect = selected.next();

			if (toSelect.length === 0) {
				toSelect = selected.prev();
			}

			// remove element and mark the next/prev one
			selected.remove();
			toSelect.prop('selected', true);

			// update classes
			this.element.removeClass('at-max').toggleClass('at-min', this.getCount() === this.min);

			// re-create the <input>
			this.createList();

			// done
			return false;
		},

		onMove: function(event) {
			var direction = event.target.className.replace('fct-', '');
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
			this.input.val(this.getElements().join(',')).change();
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

		this.filetypes  = readLists(this.element, 'filetypes');
		this.categories = readLists(this.element, 'categories');
	};

	sly.inherit(sly.MedialistWidget, sly.AbstractListWidget);

	sly.MedialistWidget.prototype.onOpen = function() {
		if (this.getCount() === this.max) return false;
		var cb = getCallbackName('slymedialistwidget');
		sly.openMediapool('detail', this.getSelected(), cb, this.filetypes, this.categories);
		win[cb] = $.proxy(this.addValue, this);
		return false;
	};

	sly.MedialistWidget.prototype.onAdd = function() {
		if (this.getCount() === this.max) return false;
		var cb = getCallbackName('slymedialistwidget');
		sly.openMediapool('upload', '', cb, this.filetypes, this.categories);
		win[cb] = $.proxy(this.addValue, this);
		return false;
	};

	/////////////////////////////////////////////////////////////////////////////
	// Linklist Widgets

	sly.LinklistWidget = function(elem) {
		sly.AbstractListWidget.call(this, elem);

		this.articletypes = readLists(this.element, 'articletypes');
		this.categories   = readLists(this.element, 'categories');
	};

	sly.inherit(sly.LinklistWidget, sly.AbstractListWidget);

	sly.LinklistWidget.prototype.onOpen = function() {
		if (this.getCount() === this.max) return false;
		var catID = this.element.data('catid'), cb = getCallbackName('slylinklistwidget');

		sly.openLinkmap(catID, cb, this.articletypes, this.categories);
		win[cb] = $.proxy(this.addValue, this);

		return false;
	};

	/////////////////////////////////////////////////////////////////////////////
	// Misc functions

	sly.disableLogin = function(timerElement) {
		var nextTime = parseInt(timerElement.html(), 10) - 1;

		timerElement.html(nextTime + '');

		if (nextTime > 0) {
			win.setTimeout(sly.disableLogin, 1000, timerElement);
		}
		else {
			$('div.sly-message p span').html($('#sly_login_form').data('message'));
			$('#sly_login_form input:not(:hidden)').prop('disabled', false);
		}
	};

	sly.startLoginTimer = function(message) {
		var timerElement = $('div.sly-message p span strong');

		if (timerElement.length === 1) {
			$('#sly_login_form input:not(:hidden)').prop('disabled', true);
			$('#sly_login_form').data('message', message);
			win.setTimeout(sly.disableLogin, 1000, timerElement);
		}
	};

	sly.setModernizrCookie = function() {
		if (typeof Modernizr === 'undefined') return false;

		// Audio and video elements get squashed together and are stored as
		// just true or false. We have to manually copy all elements to make
		// them available inside of our cookie. Sigh.
		var m = Modernizr, copy = JSON.parse(JSON.stringify(m)), key;

		copy.audio = {};
		copy.video = {};

		for (key in m.audio) {
			copy.audio[key] = m.audio[key];
		}

		for (key in m.video) {
			copy.video[key] = m.video[key];
		}

		// Keep the cookie small and remove all underscore properties.
		copy._version       = undef;
		copy._prefixes      = undef;
		copy._domPrefixes   = undef;
		copy._cssomPrefixes = undef;

		document.cookie = 'sly_modernizr='+escape(JSON.stringify(copy));
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

	sly.initWidgets = function(context) {
		$('.sly-widget:not(.sly-initialized)', context).each(function() {
			var self = $(this), init = false;

			if (self.is('.sly-link')) {
				new sly.LinkWidget(this); init = true;
			}
			else if (self.is('.sly-media')) {
				new sly.MediaWidget(this); init = true;
			}
			else if (self.is('.sly-linklist')) {
				new sly.LinklistWidget(this); init = true;
			}
			else if (self.is('.sly-medialist')) {
				new sly.MedialistWidget(this); init = true;
			}

			if (init) {
				self.addClass('sly-initialized');
			}
		});
	};

	/////////////////////////////////////////////////////////////////////////////
	// dom:loaded handler

	$(function() {
		// Init widgets

		sly.initWidgets();

		// "check all" function for mediapool

		$('.sly-check-all').click(function() {
			var target = $(this).data('target');
			$('input[name=\'' + target + '\']').prop('checked', this.checked);
		});

		// Lösch-Links

		$('a.sly-delete, input.sly-button-delete').click(function() {
			return confirm('Sicher?');
		});

		// use a distinct class to make sure when we change the delete question to
		// something like 'Are you sure you want to delete...?', existing code does
		// not look weird.

		$('a.sly-confirm-me').click(function() {
			return confirm('Sicher?');
		});

		// Filter-Funktionen in sly_Table

		$('.sly-table-extras-filter input[class*=filter_input_]').keyup(function(event) {
			// Klassen- und Tabellennamen ermitteln

			var
				className = $(this).attr('class'),
				tableName = className.match(/filter_input_([a-zA-Z0-9-_]+)/)[1],
				table     = $('#' + tableName),
				c         = event.keyCode;

			// Wert auch in allen anderen Suchfeldern übernehmen

			$('input.' + className).val($(this).val());

			// Tabelle filtern

			event.preventDefault();

			if (c === 8 || c === 46 || c === 109 || c === 189 || (c >= 65 && c <= 90) || (c >= 48 && c <= 57)) {
				var keyword = new RegExp($(this).val(), 'i');

				$('tbody tr', table).each(function() {
					var row = $(this);

					$('td', row).filter(function() {
						return keyword.test($(this).text());
					}).length ? row.show() : row.hide();
				});
			}
		});

		// Links in neuem Fenster öffnen

		$('a.sly-blank').attr('target', '_blank');

		// open mediapool in popup

		$('#sly-navi-page-mediapool a').click(function() {
			sly.openMediapool();
			return false;
		});

		// form framework

		$('.sly-form .sly-select-checkbox-list a').live('click', function() {
			var rel   = $(this).attr('rel');
			var boxes = $(this).closest('div').find('input');
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

			// Fallback-Implementierung für autofocus

			if (!Modernizr.input.autofocus) {
				$('*[autofocus]').focus();
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
				change: function() {
					hidden.val(slider.slider('value'));
				}
			});

			// remove it
			input.remove();
		});

		var sly_apply_chosen = function(container) {
			// run Chosen, but transform manual indentation (aka prefixing values with '&nbsp;'s)
			// into lvl-N classes, or else the quick filter function of Chosen will not work
			// properly.
			if (typeof $.fn.chosen !== 'undefined') {
				var options = $('select:not(.sly-no-chosen) option', container), len = options.length, i = 0, depth, option;

				for (; i < len; ++i) {
					option = $(options[i]);
					depth  = option.html().match(/^(&nbsp;)*/)[0].length / 6;

					if (depth > 0) {
						option.addClass('sly-lvl-'+depth).html(option.html().substr(depth*6));
					}
				}

				$('.sly-form-select:not(.sly-no-chosen)', container).data('placeholder', 'Bitte auswählen').chosen();
			}
		};

		sly_apply_chosen($('body'));

		// listen to rowAdded event
		$('body').bind('rowAdded', function(event) {
			sly_apply_chosen(event.currentTarget);
		});

		// Mehrsprachige Formulare initialisieren

		// Checkboxen erzeugen

		$('.sly-form-row.sly-form-multilingual').each(function() {
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

		// toggle cache options
		$('#sly-system-toggle-cache').click(function() {
			$('#sly-form-system-caches p').slideToggle();
			return false;
		});

		// use ajax to install/activate addOns
		var errorHider = null;

		$('#sly-page-addon .sly-addonlist').delegate('a:not(.sly-blank)', 'click', function() {
			var
				link     = $(this),
				list     = $('.sly-addonlist'),
				rows     = $('.component', list),
				row      = link.closest('.component'),
				errorrow = $('.error', list);

			// hide error row
			errorrow.hide();

			// clear timeout
			if (errorHider) {
				win.clearTimeout(errorHider);
			}

			// show extra div that will contain the loading animation
			rows.prepend('<div class="blocker"></div>');
			row.addClass('working');

			var updateAddOnStatus = function(stati) {
				for (var key in stati) {
					if (!stati.hasOwnProperty(key)) continue;
					var status = stati[key], comp = $('.component[data-key="' + key + '"]');
					comp.attr('class', status.classes + ' component');
					$('.deps', comp).html(status.deps);
				}
			};

			$.ajax({
				url: link.attr('href')+'&json=1',
				cache: false,
				dataType: 'json',
				type: 'POST',
				success: function(xhr) {
					updateAddOnStatus(xhr.stati);
					row.removeClass('working');
					$('.blocker').remove();

					if (xhr.status !== true) {
						row.after(errorrow);
						$('span', errorrow).html(xhr.message);
						errorrow.show();
						errorHider = win.setTimeout(function() { errorrow.slideUp(); }, 10000);
					}
				}
			});

			return false;
		});

		$('body.sly-popup').unload(sly.closeAllPopups);
	});
})(jQuery, sly, window);
