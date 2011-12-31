/*
 * Copyright (c) 2012, webvariants GbR, http://www.webvariants.de
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 *
 * Inspired by the checkimg plugin by Yves Astier (yves.astier@hotmail.fr),
 * rewritten to chang the license to MIT and allow commercial usage.
 *
 * Requires jQuery 1.6+ (prop vs attr).
 */
(function($) {
	$.fn.imgCheckbox = function(imgOff, imgOn, path) {
		return this.each(function() {
			path = path || '';

			var
				// put images in an array to switch between them
				images = [path + imgOff, path + imgOn],

				// remember the checkbox
				chkbx = $(this),

				// create a new img tag right after the checkbox
				img = chkbx.hide().after('<img />').next(),

				isChecked = function() {
					return chkbx.prop('checked') ? 1 : 0;
				},

				copyAttr = function(attr) {
					var a = chkbx.attr(attr);
					img.attr(attr, a === null ? '' : a);
				};

			// set src, title and alt
			img.attr('src', images[isChecked()]);
			copyAttr('title');
			copyAttr('alt');

			img.click(function() {
				var checked = 1 - isChecked();
				img.attr('src', images[checked ? 1 : 0]);
				chkbx.prop('checked', checked);
			});
		});
	};
})(jQuery);
