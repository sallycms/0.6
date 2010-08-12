/*
 * Copyright (c) 2010, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

/**
 * SallyCMS - JavaScript-Bibliothek
 */

var
	sally      = true,
	pageloaded = false;

(function($, undef) {
	$.noConflict();

	/* Variablen & andere private Eigenschaften */

	var
		imgExts = ['png', 'gif', 'jpg', 'jpeg', 'bmp'],
		windows = [],

		mediaListPrefix       = 'REX_MEDIALIST_',
		mediaListSelectPrefix = 'REX_MEDIALIST_SELECT_',
		linkListPrefix        = 'REX_LINKLIST_',
		linkListSelectPrefix  = 'REX_LINKLIST_SELECT_',
		popupParams           = ',status=yes,resizable=yes',

		sly_getBaseUrl = function(opener, type) {
			if (opener === undef) opener = '';
			if (type === undef) type = 'mediapool';
			return 'index.php?page=' + type + '&opener_input_field=' + opener;
		},

		sly_disableIfChecked = function(selector) {
			var
				disabled = 'disabled',
				checked  = $(selector+'_all').is(':checked');

			$(selector).attr(disabled, checked ? disabled : '');
			return checked;
		};

	/* Funktionen, die global verfügbar sein sollen. */

	changeImage = function(id, img) {
		$('#' + id).attr('src', img);
	};

	makeWinObj = function(name, url, posx, posy, width, height, extra) {
		if (extra == 'toolbar') extra = 'scrollbars=yes,toolbar=yes';
		else if (extra == 'empty') extra = 'scrollbars=no,toolbar=no';
		else extra = 'scrollbars=yes,toolbar=no' + extra;

		var obj = window.open(url, name, 'width='+width+',height='+height+','+extra);
		obj.moveTo(posx, posy);
		obj.focus();

		this.name = name;
		this.url  = url;
		this.obj  = obj;
	};

	closeAll = function() {
		var len = windows.length, i = 0;
		for (; i < len; ++i) windows[i].obj.close();
	};

	newWindow = function(name, link, width, height, type) {
		if (width == 0)  width  = 550;
		if (height == 0) height = 400;

		var
			extra      = type,
			halfWidth  = parseInt(screen.width / 2, 10),
			halfHeight = parseInt(screen.height / 2, 10);

		if (type == 'scrollbars') extra = 'toolbar';
		else if (type == 'empty') extra = 'empty';

		if (type == 'nav') {
			posx   = halfWidth - 390;
			posy   = halfHeight - 314;
			width  = 320;
			height = 580;
		}
		else if (type == 'content') {
			posx   = halfWidth - 60;
			posy   = halfHeight - 314;
			width  = 470;
			height = 580;
		}
		else {
			posx = parseInt((screen.width-width) / 2);
			posy = parseInt((screen.height-height) / 2) - 24;
		}

		windows.push(new makeWinObj(name, link, posx, posy, width, height, extra));
	};

	// -------------------------------------------------------------------------------------------------------------------


	newPoolWindow = function(link) {
		newWindow('rexmediapopup', link, 760, 600, popupParams);
	};

	newLinkMapWindow = function(link) {
		newWindow('linkmappopup', link, 760, 600, popupParams);
	};

	openMediaDetails = function(id, file_id, file_category_id) {
		newPoolWindow(sly_getBaseUrl(id) + '&subpage=detail&file_id=' + file_id + '&file_category_id=' + file_category_id);
	};

	openMediaPool = function(id) {
		newPoolWindow(sly_getBaseUrl(id));
	};

	openREXMedia = function(id, param) {
		var
			mediaid = 'REX_MEDIA_' + id,
			value   = $('#' + mediaid).val();

		if (param === undef) {
			param = '';
		}

		if (value) {
			param += '&subpage=detail&file_name=' + value;
		}

		newPoolWindow(sly_getBaseUrl(mediaid) + param);
	};

	deleteREXMedia = function(id) {
		$('#REX_MEDIA_' + id).val('');
	};

	addREXMedia = function(id, params) {
		if (params === undef) params = '';
		newPoolWindow(sly_getBaseUrl('REX_MEDIA_' + id) + '&action=media_upload&subpage=add_file'+params);
	};

	openLinkMap = function(id, param) {
		if (param === undef) param = '';
		newLinkMapWindow(sly_getBaseUrl(id, 'linkmap') + param);
	};

	setValue = function(id, value) {
		$('#'+id).val(value);
	};

	setAllCheckBoxes = function(fieldName, checkbox) {
		$('input[name=' + fieldName + ']').attr('checked', checkbox.checked ? 'checked' : '');
	};

	deleteREXLink = function(id) {
		$('LINK_' + id).val('');
		$('LINK_' + id + '_NAME').val('');
	};

	openREXMedialist = function(id) {
		var
			medialist = mediaListPrefix + id,
			selected  = $('#' + mediaListSelectPrefix + id + ' option:selected'),
			param     = '';

		if (selected.length > 0) {
			param = '&action=media_details&file_name=' + selected.val();
		}

		newPoolWindow(sly_getBaseUrl(medialist) + param);
	};

	addREXMedialist = function(id, params) {
		if (params === undef) params = '';
		newPoolWindow(sly_getBaseUrl(mediaListPrefix + id) + '&action=media_upload&subpage=add_file' + params);
	};

	deleteREXMedialist = function(id) {
		deleteREX(id, mediaListPrefix, mediaListSelectPrefix);
	};

	moveREXMedialist = function(id, direction) {
		moveREX(id, mediaListPrefix, mediaListSelectPrefix, direction);
	};

	writeREXMedialist = function(id) {
		writeREX(id, mediaListPrefix, mediaListSelectPrefix);
	};

	openREXLinklist = function(id, param) {
		var
			linklist = linkListPrefix + id,
			selected = $('#' + linkListSelectPrefix + id + ' option:selected');

		if (selected.length > 0) {
			param = '&action=link_details&file_name=' + selected.val();
		}
		else if (param === undefined) {
			param = '';
		}

		newLinkMapWindow(sly_getBaseUrl(linklist, 'linkmap') + param);
	};

	deleteREXLinklist = function(id) {
		deleteREX(id, linkListPrefix, linkListSelectPrefix);
	};

	moveREXLinklist = function(id, direction) {
		moveREX(id, linkListPrefix, linkListSelectPrefix, direction);
	};

	writeREXLinklist = function(id) {
		writeREX(id, linkListPrefix, linkListSelectPrefix);
	};

	deleteREX = function(id, i_list, i_select) {
		var
			$select  = $('#' + i_select + id),
			length   = 0,
			position = $('option:selected', $select).index();

		if (position != -1) {
			$('option:eq(' + position + ')', $select).remove();
			length = $('option', $select).length;

			if (length >= 1) {
				if (length <= position) position--;
				$('#' + i_select + id + ' option:eq(' + position + ')').attr('selected', 'selected');
				writeREX(id, i_list, i_select);
			}
		}
	};

	moveREX = function(id, i_list, i_select, direction) {
		var
			$select   = $('#' + i_select + id),
			$selected = $('option:selected', $select);

		if ($selected.length) {
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
	};

	/* übertrage Werte aus der Selectbox in ein hidden input field */

	writeREX = function(id, input, select) {
		var
			$target  = $('#' + input + id),
			options  = $('#' + select + id + ' option'),
			length   = options.length,
			i        = 0,
			elements = [];

		for (; i < length; ++i) {
			elements.push(options[i].text);
		}

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
		$('#' + id).attr('checked', 'checked');
	};

	// ------------------ Preview fuer REX_MEDIA_BUTTONS, REX_MEDIALIST_BUTTONS

	rexShowMediaPreview = function() {
		var
			value = '',
			div   = $('.rex-media-preview', this),
			url   = '',
			img   = null;

		if ($(this).hasClass('rex-widget-media')) {
			value = $('input[type=text]', this).val();
		}
		else {
			value = $('select option:selected', this).text();
		}

		url = '../index.php?rex_resize=246a__' + value;

		if (value && value.length != 0 && $.inArray(extension, imgExts)) {
			// img tag nur einmalig einfügen, ggf erzeugen wenn nicht vorhanden
			img = $('img', div);

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
			$('div.rex-message p span').html($('#login_message').text());
			$('#loginformular input:not(:hidden)').attr('disabled', '');
			$('#rex-form-login').focus();
		}
	};

	sly_startLoginTimer = function() {
		var timerElement = $('div.rex-message p span strong'), disabled = 'disabled';

		if (timerElement.length == 1) {
			$('#loginformular input:not(:hidden)').attr(disabled, disabled);
			setTimeout(sly_disableLogin, 1000, timerElement);
		}
	};

	sly_catsChecked = function() {
		var
			c_checked = sly_disableIfChecked('#userperm_cat'),
			m_checked = sly_disableIfChecked('#userperm_media'),
			container = $('#cats_mcats_perms'),
			speed     = 'slow';

		if (c_checked && m_checked)
			container.slideUp(speed);
		else
			container.slideDown(speed);
	};

	/* on dom loaded ... */

	$(function() {
		pageloaded = true;

		var
			/* Medienpool */

			mediapool              = $('#rex-form-mediapool-media'),
			mediaMethod            = $('#media_method'),
			disabled               = 'disabled',
			checkedSelector        = ':checked',
			previewWidgetSelector  = '.rex-widget-preview',
			medialistPreviewWidget = '.rex-widget-medialist' + previewWidgetSelector,
			mediaPreviewWidget     = '.rex-widget-media' + previewWidgetSelector,

			/* Loginseite */

			loginForm = $('#rex-form-login'),

			/* Benutzerformular */

			userPage            = $('#rex-page-user'),
			isUserAdminCheckbox = $('#is_admin', userPage),
			boxAllCats          = $('#userperm_cat_all', userPage),
			boxAllMediaCats     = $('#userperm_media_all', userPage);

		// Medienpool-Events

		$('.sly-button-delete', mediapool).click(function() {
			if (confirm($(this).attr('rel')+'?')) {
				mediaMethod.val('delete_selectedmedia');
			}
			else {
				return false;
			}
		});

		$('.sly-button-changecat', mediapool).click(function() {
			mediaMethod.val('updatecat_selectedmedia');
		});

		// Lösch-Links in Tabellen

		$('table.rex-table').delegate('a.sly-delete', 'click', function() {
			var question = $(this).parents('table').attr('rel') || 'Löschen?';
			return confirm(question);
		});

		// Links in neuem Fenster öffnen

		$('a.sly-blank').attr('target', '_blank');

	   // Medialist-Preview neu anzeigen, beim Wechsel der Auswahl

		$(medialistPreviewWidget).click(rexShowMediaPreview);

		$(mediaPreviewWidget + ',' + medialistPreviewWidget)
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

		if (loginForm.length > 0) {
			loginForm.focus();
			$('#javascript').val('1');
		}

		// Benutzer-Formular

		if ($('#rex-page-user #rex-form-user-editmode')) {
			isUserAdminCheckbox.click(function() {
				if (this.checked) {
					$('#userperm-module').attr(disabled, disabled);
					$('#cats_mcats_perms').slideUp('slow');
					$('#cats_mcats_box').slideUp('slow');
				}
				else {
					$('#userperm-module').attr(disabled, '');
					$('#cats_mcats_box').slideDown('slow');
					sly_catsChecked();
				}
			});

			boxAllCats.click(sly_catsChecked);
			boxAllMediaCats.click(sly_catsChecked);

			// init behaviour

			if (isUserAdminCheckbox.is(checkedSelector)) {
				$('#userperm-module').attr(disabled, disabled);
				$('#cats_mcats_perms').hide();
				$('#cats_mcats_box').hide();
			}

			if (boxAllCats.length == 1 && boxAllCats[0].checked && boxAllMediaCats[0].checked) {
				$('#cats_mcats_perms').hide();
			}
		}
	});
})(jQuery);
