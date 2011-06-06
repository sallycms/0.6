// änderungen / 090521:
// OK beim einlesen im american mode zeit umrechnen
// OK datum einlesen
// OK beim ändern des input feldes widget anpassen / neu einlesen
// OK >> wenn keine stunde/minute angegeben ist 00 angeben
// OK sprache datepicker
// OK sprache durch parameter reingeben
// OK mehrere felder bedienen
// OK datepicker als klickbar machen

/**
fixes / 090521:
	- convert widget time to am/pm if american mode is on
	- show correct date from input
	- on change at input field modify widget
	- if there is no hour/minute given in input, show 00
	- start datepicker in given language
	- new parameter for language
	- able to work on many fields
	- write date from datepicker

rewrite (xrstf) / 100711:
   - [cosmetic] wrapped in an anonymous function
   - [cosmetic] correct indentation
   - [cosmetic] removed tons of duplicate selectors (jQuery(this)...)
   - [cosmetic] passes JSLint now
   - [functional] fixed a problem with dates like "1. Jan 1970 HH:MM:SS"
*/
(function($) {
	$.fn.datetime = function(options) {
		var userLang    = options.userLang || 'en';
		var b24Hour     = !(options.americanMode || false);
		var markerClass = 'hasDateTime';
		var dateFormat  = options.dateFormat;

		return this.each(function() {
			/* Define here so that JSlint doesn't complain. */

			function writeDate(text, type) {
				var p = $('#pickerplug');

				if (type == 'time') {
					p.data('lasttime', text + ':00');
				}
				else {
					p.data('lastdate', text);
				}

				$(p.data('inputfield')).val(
					p.data('lastdate') + ' ' + p.data('lasttime')
				);
			}

			var datepicker_def = {
				changeMonth:     true,
				changeYear:      true,
				dateFormat:      dateFormat,
				showButtonPanel: true,
				onSelect:        writeDate
			};

			var lang = {};

			lang.en = {
				time:   'Time',
				hour:   'Hour',
				minute: 'Minute',
				close:  'Close'
			};

			lang.de = {
				time:   'Zeit',
				hour:   'Stunde',
				minute: 'Minute',
				close:  'Schließen'
			};

			$(this)
				.data('sets', datepicker_def)
				.data('userLang', userLang)
				.data('b24Hour', b24Hour);

			function writeTime(fragment, type) {
				var time = '';

				switch (type) {
					case 'hour':
						var hours = parseInt(fragment, 10);

						if (!$('#pickerplug').data('b24Hour') && hours > 11) {
							hours -= 12;
							$('.dayPeriod').text('pm');
						}
						else if (!$('#pickerplug').data('b24Hour')) {
							$('.dayPeriod').text('am');
						}

						if (hours < 10) {
							hours = '0'.concat(hours);
						}

						if (fragment < 10) {
							fragment = '0'.concat(parseInt(fragment, 10));
						}

						$('#tpSelectedTime .selHrs').text(hours);
						time = fragment + ':' + $('#tpSelectedTime .selMins').text();
						break;

					case 'minute':
						var minutes = ((fragment < 10) ? '0' :'') + parseInt(fragment, 10);
						$('#tpSelectedTime .selMins').text(minutes);
						time = $('#hourSlider').slider('option', 'value') + ':' + minutes;
						break;
				}

				return time;
			}

			function parseTime(obj) {
				var time = ($(obj).val() || $(this).val()).split(' ');
				var date = null;

				// Bei Zeitangaben wie "01. Jan 1990 01:00" erhalten wir ["01.", "Jan", "1990", "01:00"].
				// Das korrigieren wir einfach wieder, indem die ersten (n-1) Elemente wieder
				// zusammengesetzt werden.

				if (time.length >= 2) {
					date = time.slice(0, -1).join(' ');
					time = time.pop();
				}
				else {
					date = time[0];
					time = '00:00:00';
				}

				$('#pickerplug').data('lastdate', date).data('lasttime', time);

				time = time.split(':');

				if (time.length < 2) {
					time.push('00');
				}

				var hour	  = time[0] || '00';
				var minute = time[1] || '00';

				writeTime(hour, 'hour');
				writeTime(minute, 'minute');

				$('#hourSlider').slider('option', 'value', hour);
				$('#minuteSlider').slider('option', 'value', minute);

				$('#datepicker').datepicker(
					'setDate',
					$.datepicker.parseDate(datepicker_def.dateFormat, $('#pickerplug').data('lastdate'))
				);
			}

			function closePickPlug(event) {
				var t = $(event.target);

				if ((t.parents('#pickerplug').length || t.hasClass(markerClass)) && !t.hasClass('ui-datepicker-close')) {
					return;
				}

				$('#pickerplug').hide('slow');
				$(this)
					.unbind('click', closePickPlug)
					.unbind('keyup', parseTime)
					.removeClass(markerClass);
			}

			function renderPickerPlug(b24Hour_, lang_) {
				var loadedLang = lang[lang_] || lang.en;

				if (!$('#pickerplug').length) {
					var htmlins = '<ul id="pickerplug">';
					htmlins += '<li>';
					htmlins += '<div id="datepicker"></div>';
					htmlins += '</li>';
					htmlins += '<li>';
					htmlins += '<div id="timepicker" class="ui-corner-all ui-widget-content">';
					htmlins += '<h3 id="tpSelectedTime">';
					htmlins += '	<span id="text_time"></span>';
					htmlins += '	<span class="selHrs" >00</span>';
					htmlins += '	<span class="delim" >:</span>';
					htmlins += '	<span class="selMins">00</span>';
					htmlins += '	<span class="dayPeriod">am</span>';
					htmlins += '</h3>';
					htmlins += '<ul id="sliderContainer">';
					htmlins += '	<li>';
					htmlins += '        <h4 id="text_hour"></h4>';
					htmlins += '        <div id="hourSlider" class="slider"></div>';
					htmlins += '	</li>';
					htmlins += '	<li>';
					htmlins += '        <h4 id="text_minute"></h4>';
					htmlins += '        <div id="minuteSlider" class="slider"></div>';
					htmlins += '	</li>';
					htmlins += '</ul>';
					htmlins += '</div>';
					htmlins += '<button type="button" class="ui-datepicker-close ui-state-default ui-priority-primary ui-corner-all" id="text_close"></button>';
					htmlins += '</li>';
					htmlins += '</ul>';
					$('body').append(htmlins);

					$('#datepicker').datepicker();
					$(document).mousedown(closePickPlug);
					$('#pickerplug .ui-datepicker-close').click(closePickPlug);

					// Slider
					$('#hourSlider').slider({
						orientation: 'vertical',
						range: 'min',
						min:   0,
						max:   23,
						step:  1,

						slide: function(event, ui) {
							writeDate(writeTime(ui.value, 'hour'), 'time');
						},

						change: function(event, ui) {
							$('#tpSelectedTime .selHrs').effect('highlight', 1000);
						}
					});

					// Slider
					$('#minuteSlider').slider({
						orientation: 'vertical',
						range: 'min',
						min:   0,
						max:   55,
						step:  5,

						slide: function(event, ui) {
							writeDate(writeTime(ui.value, 'minute'), 'time');
						},

						change: function(event, ui) {
							$('#tpSelectedTime .selMins').effect('highlight', 1000);
						}
					});

					// Inline editor bind

					$('#tpSelectedTime .selHrs').keyup(function(e) {
						var me = $(this);

						if ((e.which <= 57 && e.which >= 48) && (me.text() >= 1 && me.text() <= 12)) {
							$('#hourSlider').slider('value', parseInt(me.text(), 10));
						}
						else{
							me.val(me.text().slice(0, -1));
						}
					});

					// Inline editor bind

					$('#tpSelectedTime .selMins').keyup(function(e){
						var me = $(this);

						if ((e.which <= 57 && e.which >= 48) && (me.text() >= 0 && me.text() <= 59)) {
							$('#minuteSlider').slider('value', parseInt(me.text(), 10));
						}
						else{
							me.val(me.text().slice(0, -1));
						}
					});
				} // if ($('#pickerplug').length == 0)

				$('.dayPeriod').toggle(!b24Hour);
				$('#text_time').text(loadedLang.time);
				$('#text_hour').text(loadedLang.hour);
				$('#text_minute').text(loadedLang.minute);
				$('#text_close').text(loadedLang.close);

				$('#pickerplug').data('userLang', lang_);
				$('#pickerplug').data('b24Hour', b24Hour_);
			} // renderPickerPlug()

			$(this).bind('focus', function() {
				var me      = $(this);
				var top     = me.offset().top + me.outerHeight();
				var left    = me.offset().left;
				var tpicker = $('#pickerplug');
				var dpicker = $('#datepicker');

				if (me.data('userLang') != tpicker.data('userLang') || me.data('b24Hour') != tpicker.data('userLang')) {
					renderPickerPlug(me.data('b24Hour'), me.data('userLang'));
				}

				// Fragt mich nicht warum, aber die Objekte müssen hier nochmal aktualisiert werden...

				tpicker = $('#pickerplug');
				dpicker = $('#datepicker');

				tpicker.css({
					left: left + 'px',
					top:  top + 'px'
				}).show('slow');

				if (me.data('userLang') != 'en' && lang[me.data('userLang')]) {
					dpicker.datepicker('option', $.extend({}, $.datepicker.regional[me.data('userLang')]));
					dpicker.datepicker('option', $.extend(me.data('sets')));
				}
				else {
					dpicker.datepicker('option', $.extend({}, $.datepicker.regional['']));
					dpicker.datepicker('option', $.extend(me.data('sets')));
				}

				parseTime(this);

				// Fragt mich nicht warum, aber die Objekte müssen hier nochmal aktualisiert werden...

				tpicker = $('#pickerplug');
				dpicker = $('#datepicker');

				if (tpicker.css('display') == 'none') {
					tpicker.show('slow');
				}

				me.bind('keyup',parseTime).addClass(markerClass);
				tpicker.data('inputfield', this);
			});
		});
	};
})(jQuery);
