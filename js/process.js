/**
 * Google Groups Subscribe
 *
 * AJAX processing for email submission form
 * - add <input type=hidden name=ajax value=ajax> fields to GGS forms
 * - hijack form submission, send forms using AJAX
 * - display status message when data is received
 *
 *
 * wp-subscribe-google-groups - WordPress Plugin/Widget for subscribing to Google Groups.
 * Copyright (C) 2017 Seravo Oy <https://seravo.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

jQuery(document).ready(function($) {
  var url = Subscribe_Google_Groups_AJAX.ajaxUrl;
  var ggs_forms = '.widget.' + Subscribe_Google_Groups_AJAX.class + ' form';

  // On submit, prevent default action & do AJAX query,
  // display status when finished
  $(ggs_forms).on('submit', function(event) {
    event.preventDefault();
    $.post(url, $(this).serialize(), function(data) {
      // FIXME: This can be done in a much cleaner way.
      var id = Subscribe_Google_Groups_AJAX.class + '-' + parseInt(data['widget_number']);
      var e = $('#' + id + ' .status');

      // FIXME: blocks below move when .status is resized
      if (data.status == 'success') {
        e.text(Subscribe_Google_Groups_AJAX.successMessage);
        e.show().fadeOut(10000);
      } else {
        e.text(Subscribe_Google_Groups_AJAX.errorMessage);
        e.show().fadeOut(10000);
      }
    });
  });
});
