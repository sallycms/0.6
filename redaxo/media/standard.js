/**
 * SallyCMS - JavaScript-Bibliothek
 */

var redaxo = true;
var sally  = true;
jQuery.noConflict();

(function($) {
	pageloaded      = false;
	imageExtensions = ['png', 'gif', 'jpg', 'jpeg', 'bmp'];

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
		if (typeof(id) == 'undefined') id = '';
		newPoolWindow('index.php?page=mediapool&subpage=detail&opener_input_field=' + id + '&file_id=' + file_id + '&file_category_id=' + file_category_id);
	};

	openMediaPool = function(id) {
		if (typeof(id) == 'undefined') id = '';
		newPoolWindow('index.php?page=mediapool&opener_input_field=' + id);
	};

	openREXMedia = function(id, param) {
		var mediaid = 'REX_MEDIA_'+id;
		var value   = $('#' + mediaid).val();

		if (typeof(param) == 'undefined') {
			param = '';
		}

		if (value) {
			param += '&subpage=detail&file_name=' + value;
		}

		newPoolWindow('index.php?page=mediapool' + param + '&opener_input_field=' + mediaid);
	};

	deleteREXMedia = function(id) {
		$('REX_MEDIA_' + id).val();
	};

	addREXMedia = function(id,params) {
		if (typeof(params) == 'undefined') params = '';
		newPoolWindow('index.php?page=mediapool&action=media_upload&subpage=add_file&opener_input_field=REX_MEDIA_'+id+params);
	};

	openLinkMap = function(id, param) {
		if (typeof(id)    == 'undefined') id    = '';
		if (typeof(param) == 'undefined') param = '';
		newLinkMapWindow('index.php?page=linkmap&opener_input_field='+id+param);
	};

	setValue = function(id, value) {
		$('#'+id).val(value);
	};

	setAllCheckBoxes = function(fieldName, checkbox) {
		jQuery('input[name=' + fieldName + ']').attr('checked', checkbox.checked ? 'checked' : '');
	};

	deleteREXLink = function(id) {
		$('LINK_' + id).val('');
		$('LINK_' + id + '_NAME').val('');
	};

	openREXMedialist = function(id) {
		var medialist = 'REX_MEDIALIST_' + id;
		var selected  = $('#REX_MEDIALIST_SELECT_' + id + ' option:selected');

		if (selected.length > 0) {
			param = '&action=media_details&file_name=' + selected.val();
		}
		else if (typeof(param) == 'undefined') {
			param = '';
		}

		newPoolWindow('index.php?page=mediapool' + param + '&opener_input_field=' + medialist);
	};

	addREXMedialist = function(id, params) {
		if (typeof(params) == 'undefined') params = '';
		newPoolWindow('index.php?page=mediapool&action=media_upload&subpage=add_file&opener_input_field=REX_MEDIALIST_'+id+params);
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
		var linklist = 'REX_LINKLIST_' + id;
		var selected = $('#REX_LINKLIST_SELECT_' + id + ' option:selected');

		if (selected.length > 0) {
			param = '&action=link_details&file_name=' + selected.val();
		}
		else if (typeof(param) == 'undefined') {
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
		var options  = $('#' + i_list + id + ' option');
		var length   = options.length;
		var position = options.filter(':selected').index();

		if (position == -1) return;

		options[position] = null;
		length--;

		// Wenn das erste gelöscht wurde
		if (position == 0) {
			// Und es gibt noch weitere,
			// -> selektiere das "neue" erste
			if (length > 0) options[0].selected = 'selected';
		}
		else {
			// -> selektiere das neue an der Stelle >position<
			if (length > position) options[position].selected = 'selected';
			else options[position-1].selected = 'selected';
		}

		writeREX(id, i_list, i_select);
	};

	moveREX = function(id, i_list, i_select, direction) {
		var options      = $('#' + i_select + id + ' option');
		var elements     = [];
		var was_selected = [];

		for (var i = 0; i < options.length; ++i) {
			was_selected[i] = false;
			elements[i]     = {
				value: options[i].value,
				title: options[i].text
			};
		}

		var inserted  = 0;
		var was_moved = [];

		was_moved[-1]             = true;
		was_moved[options.length] = true;

		if (direction == 'top') {
			for (var i = 0; i < options.length; ++i) {
				if (options[i].selected) {
					elements = moveItem(elements, i, inserted);
					was_selected[inserted] = true;
					inserted++;
				}
			}
		}

		if (direction == 'up') {
			for (var i = 0; i < options.length; ++i) {
				was_moved[i] = false;

				if (options[i].selected) {
					to = i - 1;

					if (was_moved[to]) {
						to = i;
					}

					elements         = moveItem(elements, i, to);
					was_selected[to] = true;
					was_moved[to]    = true;
				}
			}
		}

		if (direction == 'down') {
			for (var i = options.length - 1; i >= 0; --i) {
				was_moved[i] = false;

				if (options[i].selected) {
					to = i + 1;

					if (was_moved[to]) {
						to = i;
					}

					elements         = moveItem(elements, i, to);
					was_selected[to] = true;
					was_moved[to]    = true;
				}
			}
		}

		if (direction == 'bottom') {
			inserted = 0;

			for (var i = options.length - 1; i >= 0; --i) {
				if (options[i].selected) {
					to = sourcelength - inserted - 1;

					if (to > options.length) {
						to = options.length;
					}

					elements         = moveItem(elements, i, to);
					was_selected[to] = true;
					inserted++;
				}
			}
		}

		for (var i = 0; i < options.length; ++i) {
			options[i] = new Option(elements[i].title, elements[i].value);
			options[i].selected = was_selected[i];
		}

		writeREX(id, i_list, i_select);
	}

	/* übertrage Werte aus der Selectbox in einer hidden input field */
	writeREX = function(id, input, select) {
		var options  = $('#' + select + id + ' option');
		var target   = $('#' + input + id);
		var elements = [];

		for (var i = 0; i < options.length; ++i) {
			elements.push(options[i].text);
		}

		target.val(elements.join(','));
	};

	moveItem = function(arr, from, to) {
		if (from == to || to < 0) {
			return arr;
		}

		tmp = arr[from];

		if (from > to) {
			for (index = from; index > to; index--) {
				arr[index] = arr[index-1];
			}
		}
		else {
			for (index = from; index < to; index++) {
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
		var value;

		if ($(this).hasClass('rex-widget-media')) {
			value = $('input[type=text]', this).val();
		}
		else {
			value = $('select option:selected', this).text();
		}

		var div = $('.rex-media-preview', this);
		var url = '../index.php?rex_resize=246a__' + value;

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
			$('div.rex-message p span').html($('#login_message').text());
			$('#loginformular input:not(:hidden)').attr('disabled', '');
			$('#rex-form-login').focus();
		}
	};
	
	sly_startLoginTimer = function() {
		var timerElement = $('div.rex-message p span strong');
		
		if (timerElement.length == 1) {
			$('#loginformular input:not(:hidden)').attr('disabled', 'disabled');
			setTimeout(sly_disableLogin, 1000, timerElement);
		}
	};

})(jQuery);

jQuery(function($) {
	pageloaded = true;
	
	// Medienpool-Events

	$('#rex-form-mediapool-media .sly-button-delete').click(function() {
		if (confirm($(this).attr('rel')+'?')) {
			$('#media_method').val('delete_selectedmedia');
		}
		else {
			return false;
		}
	});

	$('#rex-form-mediapool-media .sly-button-changecat').click(function() {
		$('#media_method').val('updatecat_selectedmedia');
	});
	
	// Lösch-Links in Tabellen

	$('table.rex-table').delegate('a.sly-delete', 'click', function() {
		var table    = $(this).parents('table');
		var question = table.attr('rel');
		
		if (!question) {
			question = 'Löschen?';
		}
		
		if (!confirm(question)) {
			return false;
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
	$('#rex-navi-page-mediapool a').click(function(){
		newPoolWindow('index.php?page=mediapool');
		return false;
	});
	
	// Login-Formular
	
	if ($('#rex-form-login').length > 0) {
		$('#rex-form-login').focus();
		$('#javascript').val('1');
	}
});
