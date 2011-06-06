/**
 * SallyCMS - JavaScript-Bibliothek
 */

(function($, undef) {
	var imageExtensions = ['png', 'gif', 'jpg', 'jpeg', 'bmp'];

	changeImage = function(id, img) {
		$('#' + id).attr('src', img);
	};

	makeWinObj = function(name, url, posx, posy, width, height, extra) {
		if (extra == 'toolbar') extra = 'scrollbars=yes,toolbar=yes';
		else if (extra == 'empty') extra = 'scrollbars=no,toolbar=no';
		else extra = 'scrollbars=yes,toolbar=no' + extra;

		this.name = name;
		this.url  = url;
		this.obj  = window.open(url, name, 'width='+width+',height='+height+',' + extra);

		this.obj.moveTo(posx, posy);
		this.obj.focus();
	};

	closeAll = function() {
		for (var i in winObj) {
			winObj[i].obj.close();
		}
	};

	newWindow = function(name, link, width, height, type) {
		if (width == 0)  width  = 550;
		if (height == 0) height = 400;

		if (type == 'scrollbars') extra = 'toolbar';
		else if (type == 'empty') extra = 'empty';
		else extra = type;

		if (type == 'nav') {
			posx   = parseInt(screen.width / 2) - 390;
			posy   = parseInt(screen.height / 2) - 314;
			width  = 320;
			height = 580;
		}
		else if (type == 'content') {
			posx   = parseInt(screen.width / 2) - 60;
			posy   = parseInt(screen.height / 2) - 314;
			width  = 470;
			height = 580;
		}
		else {
			posx = parseInt((screen.width-width) / 2);
			posy = parseInt((screen.height-height) / 2) - 24;
		}

		winObj.push(new makeWinObj(name, link, posx, posy, width, height, extra));
	};

	winObj = [];

	// -------------------------------------------------------------------------------------------------------------------

	newPoolWindow = function(link) {
		newWindow('rexmediapopup', link, 760, 600, ',status=yes,resizable=yes');
	};

	newLinkMapWindow = function(link) {
		newWindow('linkmappopup', link, 760, 600, ',status=yes,resizable=yes');
	};

	openMediaDetails = function(id, file_id, file_category_id) {
		if (id === undef) id = '';
		newPoolWindow('index.php?page=mediapool&subpage=detail&opener_input_field=' + id + '&file_id=' + file_id + '&file_category_id=' + file_category_id);
	};

	openMediaPool = function(id) {
		if (id === undef) id = '';
		newPoolWindow('index.php?page=mediapool&opener_input_field=' + id);
	};

	openREXMedia = function(id, param) {
		var mediaid = 'REX_MEDIA_'+id;
		var value   = $('#' + mediaid).val();

		if (param === undef) {
			param = '';
		}

		if (value) {
			param += '&subpage=detail&file_name=' + value;
		}

		newPoolWindow('index.php?page=mediapool' + param + '&opener_input_field=' + mediaid);
	};

	deleteREXMedia = function(id) {
		$('#REX_MEDIA_' + id).val('');
	};

	addREXMedia = function(id, params) {
		if (params === undef) params = '';
		newPoolWindow('index.php?page=mediapool&subpage=upload&opener_input_field=REX_MEDIA_'+id+params);
	};

	openLinkMap = function(id, param) {
		if (id    === undef) id    = '';
		if (param === undef) param = '';
		newLinkMapWindow('index.php?page=linkmap&opener_input_field='+id+param);
	};

	setValue = function(id, value) {
		$('#'+id).val(value);
	};

	setAllCheckBoxes = function(fieldName, checkbox) {
		jQuery('input[name=\'' + fieldName + '\']').prop('checked', checkbox.checked);
	};

	deleteREXLink = function(id) {
		$('#LINK_' + id).val('');
		$('#LINK_' + id + '_NAME').val('');
	};

	openREXMedialist = function(id) {
		var medialist = 'REX_MEDIALIST_' + id;
		var selected  = $('#REX_MEDIALIST_SELECT_' + id + ' option:selected');

		if (selected.length > 0) {
			var param = '&action=media_details&file_name=' + selected.val();
		}
		else if (param === undef) {
			var param = '';
		}

		newPoolWindow('index.php?page=mediapool' + param + '&opener_input_field=' + medialist);
	};

	addREXMedialist = function(id, params) {
		if (params === undef) params = '';
		newPoolWindow('index.php?page=mediapool&subpage=upload&opener_input_field=REX_MEDIALIST_'+id+params);
	};

	deleteREXMedialist = function(id) {
		deleteREX(id, 'REX_MEDIALIST_', 'REX_MEDIALIST_SELECT_');
	};

	moveREXMedialist = function(id, direction) {
		moveREX(id, 'REX_MEDIALIST_', 'REX_MEDIALIST_SELECT_', direction);
	};

	writeREXMedialist = function(id) {
		writeREX(id, 'REX_MEDIALIST_', 'REX_MEDIALIST_SELECT_');
	};

	openREXLinklist = function(id, param) {
		var
			linklist = 'REX_LINKLIST_' + id,
			selected = $('#REX_LINKLIST_SELECT_' + id + ' option:selected');

		if (selected.length > 0) {
			param = '&action=link_details&file_name=' + selected.val();
		}
		else if (param === undef) {
			param = '';
		}

		newLinkMapWindow('index.php?page=linkmap&opener_input_field='+linklist+param);
	};

	deleteREXLinklist = function(id) {
		deleteREX(id, 'REX_LINKLIST_', 'REX_LINKLIST_SELECT_');
	};

	moveREXLinklist = function(id, direction) {
		moveREX(id, 'REX_LINKLIST_', 'REX_LINKLIST_SELECT_', direction);
	};

	writeREXLinklist = function(id) {
		writeREX(id, 'REX_LINKLIST_', 'REX_LINKLIST_SELECT_');
	};

	deleteREX = function(id, i_list, i_select) {
		var
			$select  = $('#' + i_select + id),
			position = $('option:selected', $select).index();

		if (position == -1) return;
		$('option:eq(' + position + ')', $select).remove();

		var length = $('option', $select).length;

		if (length >= 1) {
			if (length <= position) position--;
			$('#' + i_select + id + ' option:eq(' + position + ')').prop('selected', true);
		}

		writeREX(id, i_list, i_select);
	};

	moveREX = function(id, i_list, i_select, direction) {
		var
			$select   = $('#' + i_select + id),
			$selected = $('option:selected', $select);

		if (!$selected.length) return;

		if (direction == 'top') {
			$select.prepend($selected);
		}
		else if (direction == 'up') {
			$($selected).prev().insertAfter($selected);
		}
		else if (direction == 'down') {
			$($selected).next().insertBefore($selected);
		}
		else if (direction == 'bottom') {
			$select.append($selected);
		}

		writeREX(id, i_list, i_select);
	}

	/* übertrage Werte aus der Selectbox in einer hidden input field */
	writeREX = function(id, input, select) {
		var
			$target  = $('#' + input + id),
			elements = [];

		$('#' + select + id + ' option').each(function(){
			elements.push($(this).val());
		});

		$target.val(elements.join(','));
	};

	moveItem = function(arr, from, to) {
		if (from == to || to < 0) {
			return arr;
		}

		var tmp = arr[from], index = from;

		if (from > to) {
			for (; index > to; index--) {
				arr[index] = arr[index-1];
			}
		}
		else {
			for (; index < to; index++) {
				arr[index] = arr[index+1];
			}
		}

		arr[to] = tmp;
		return arr;
	};

	// Checkbox mit der ID <id> anhaken

	checkInput = function(id) {
		$('#' + id).prop('checked', true);
	};

	// ------------------ Preview fuer REX_MEDIA_BUTTONS, REX_MEDIALIST_BUTTONS

	rexShowMediaPreview = function() {
		var value;

		if ($(this).hasClass('rex-widget-media')) {
			value = $('input[type=text]', this).val();
		}
		else {
			value = $('select option:selected', this).text();
		}

		var div = $('.rex-media-preview', this);
		var url = '../imageresize/246a__' + value;

		if (value && value.length != 0 && $.inArray(extension, imageExtensions)) {
			// img tag nur einmalig einfügen, ggf erzeugen wenn nicht vorhanden
			var img = $('img', div);

			if (img.length == 0) {
				div.html('<img />');
				img = $('img', div);
			}

			img.attr('src', url);

			// warten bis der layer komplett ausgeblendet ist

			if (div.css('height') == 'auto') {
				div.fadeIn('normal');
			}
		}
		else {
			div.slideUp('fast');
		}
	};

	sly_disableLogin = function(timerElement) {
		var nextTime = parseInt(timerElement.html(), 10) - 1;

		timerElement.html(nextTime + '');

		if (nextTime > 0) {
			setTimeout(sly_disableLogin, 1000, timerElement);
		}
		else {
			$('div.rex-message p span').html($('#loginformular').data('message'));
			$('#loginformular input:not(:hidden)').prop('disabled', false);
			$('#rex_user_login').focus();
		}
	};

	sly_startLoginTimer = function(message) {
		var timerElement = $('div.rex-message p span strong');

		if (timerElement.length == 1) {
			$('#loginformular input:not(:hidden)').prop('disabled', true);
			$('#loginformular').data('message', message);
			setTimeout(sly_disableLogin, 1000, timerElement);
		}
	};

	sly_catsChecked = function() {
		var c_checked = $('#userperm_cat_all').prop('checked');
		var m_checked = $('#userperm_media_all').prop('checked');
		var slider    = $('#rex-page-user .sly-form .rex-form-wrapper .sly-num7');

		$('#userperm_cat').prop('disabled', c_checked);
		$('#userperm_media').prop('disabled', m_checked);

		if (c_checked && m_checked)
			slider.slideUp('slow');
		else
			slider.slideDown('slow');
	},

	sly_addListOption = function(parentSpan, title, key) {
		var list = $('select', parentSpan);

		if (list.find('option[value=\''+key+'\']').length == 0) {
			list.append($('<option>').val(key).text(title));
			sly_createList(list);
		}
	},

	sly_moveListItem = function(ev) {
		var
			link     = $(this),
			span     = link.parents('span.sly-widget'),
			list     = $('select', span),
			func     = link.attr('rel'),
			selected = $('option:selected', list);

		if (selected) {
			switch (func) {
				case 'up':
					selected.insertBefore(selected.prev());
					break;
				case 'down':
					selected.insertAfter(selected.next());
					break;
				case 'top':
					selected.detach();
					list.prepend(selected);
					break;
				case 'bottom':
					selected.detach();
					list.append(selected);
					break;
			}

			sly_createList(list);
		}

		return false;
	},

	sly_createList = function(list) {
		var ids = [], options = $('option', list), len = options.length, i = 0;

		for (; i < len; ++i) {
			ids.push(options[i].value);
		}

		list.parents('span').find('input[type=hidden]').val(ids.join(','));
	};

})(jQuery);

jQuery(function($) {
	// Lösch-Links in Tabellen

	$('table.rex-table').delegate('a.sly-delete, input.sly-button-delete', 'click', function() {
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

   // Medialist-Preview neu anzeigen, beim Wechsel der Auswahl

	$('.rex-widget-medialist.rex-widget-preview').click(rexShowMediaPreview);

	$('.rex-widget-media.rex-widget-preview, .rex-widget-medialist.rex-widget-preview')
		.bind('mousemove', rexShowMediaPreview)
		.bind('mouseleave', function() {
			var div = $('.rex-media-preview', this);

			if (div.css('height') != 'auto') {
				div.slideUp('normal');
			}
		});

	$('#rex-navi-page-mediapool a').click(function() {
		newPoolWindow('index.php?page=mediapool');
		return false;
	});

	// Login-Formular

	if ($('#rex-form-login').length > 0) {
		$('#rex-form-login').focus();
		$('#javascript').val('1');
	}

	// Benutzer-Formular

	if ($('#rex-page-user .sly-form').length > 0) {
		var wrapper = $('#rex-page-user .sly-form .rex-form-wrapper');
		var sliders = wrapper.find('.sly-num6,.sly-num7');

		$('#is_admin').change(function() {
			if ($(this).is(':checked')) {
				$('#userperm_module').prop('disabled', true);
				sliders.slideUp('slow');
			}
			else {
				$('#userperm_module').prop('disabled', false);
				sliders.slideDown('slow');
				sly_catsChecked();
			}
		});

		sly_catsChecked();
		$('#userperm_cat_all, #userperm_media_all').change(sly_catsChecked);

		// init behaviour

		if ($('#is_admin').is(':checked')) {
			$('#userperm_module').prop('disabled', true);
			sliders.hide();
		}

		if ($('#userperm_cat_all').is(':checked') && $('#userperm_media_all').is(':checked')) {
			wrapper.find('.sly-num7').hide();
		}
	}

	// Formularframework

	$('.rex-form .sly-select-checkbox-list a').live('click', function() {
		var rel   = $(this).attr('rel');
		var boxes = $(this).parents('p').find('.rex-chckbx');
		boxes.prop('checked', rel === 'all');
		return false;
	});

	// allen vom Browser unterstützten Elementen die Klasse ua-supported geben

	var types = Modernizr.inputtypes;

	for (var type in types) {
		if (types.hasOwnProperty(type) && types[type]) {
			$('input[type='+type+']').addClass('ua-supported');
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
			min:   input.attr('min'),
			max:   input.attr('max'),
			value: input.val(),
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
			span    = '<span class="sly-form-i18n-switch"><input type="checkbox" name="equal__'+id+'" id="equal__'+id+'" value="1"'+checked+' /><\/span>'

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

	// Linklist-Buttons

	if ($.fn.autocomplete) {
		$('.sly-link-filter').autocomplete({
			url:            'index.php',
			paramName:      'q',
			extraParams:    {page: 'api', func: 'linklistbutton_search'},
			maxCacheLength: 50,
			matchContains:  true,
			resultsClass:   'sly-filter-results',
			showResult:     function(value, data) {
				return '<span class="name"><strong>' + value + '</strong></span><br/><span class="cat">' + data[1] + '</span>';
			},
			onItemSelect:   function(item) {
				var
					input = $('input:focus'),
					span  = input.parents('.sly-widget');

				sly_addListOption(span, item.value, item.data[0]);
				input.val('');
			}
		});
	}

	$('select.sly-linklist').bind('keydown', 'del', function() {
		var sel  = $('option:selected', $(this));
		var next = sel.next();

		sel.remove();
		next.prop('selected', true);
		sly_createList($(this));
	});

	$('body').delegate('.sly-linklistbutton .sly-icons a[rel]', 'click', sly_moveListItem);

	$('.sly-module-select').change(function(){
		$(this).closest('form').submit();
	});
});
