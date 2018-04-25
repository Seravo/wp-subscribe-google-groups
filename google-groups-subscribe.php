<?php
/**
 * Plugin Name: Subscribe Google Groups
 * Version: 1.0
 * Plugin URI: https://github.com/Seravo/wp-subscribe-google-groups
 * Description: Widget for subscribing to Google Groups.
 * Author: Seravo Oy
 * Author URI: https://seravo.com/
 * Text Domain: subscribe-google-groups
 * Domain Path: /languages/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Seravo\SubscribeGoogleGroups
 * @version 1.0
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

// TODO: create_nonce($widget_number)
// TODO: validate nonce w/ widget_number
// TODO: Limit subscription requests using Recaptcha or another mechanism

namespace Seravo\SubscribeGoogleGroups;

// Don't allow user to access this file directly
if (!defined('ABSPATH')) {
  die();
}

require_once('widget.php');

/**
 * @since 1.0
 */
class Plugin {
  // Toggle debug mode (log messages?)
  const DEBUG = false;

  const PLUGIN_NAME = 'Subscribe Google Groups';
  const PLUGIN_ID = 'subscribe-google-groups';

  // TODO: This could be configurable
  const SUBSCRIPTION_ADDRESS = '%s+subscribe@googlegroups.com';
  //const SUBSCRIPTION_URL = 'https://groups.google.com/forum/#!forum/%s/join';

  // Object reference for JavaScript
  const PLUGIN_OBJ = 'Subscribe_Google_Groups_AJAX';

  // i18n language domain
  const DOMAIN = 'subscribe-google-groups';

  // WP admin-post action name
  const ACTION_PROCESS = 'process_subscribe_google_groups';

  // Form field ids
  const TITLE_ID = 'title';
  const GROUP_ID = 'group';
  const EMAIL_ID = 'email';

  // For singleton class
  private static $single;

  /**
   * Simple logging/debugging helper
   * @param $s string to log
   */
  public static function log($s) {
    if (self::DEBUG) {
      error_log(sprintf('%s: %s', self::PLUGIN_ID, $s));
    }
  }

  /**
   * Constructor for Plugin
   */
  public function __construct() {
    if (isset(self::$single)) {
      return;
    }
    self::$single = $this;

    add_action('plugins_loaded', array($this, 'loadTextDomain'));
    add_action('widgets_init', array($this, 'registerWidget'));
    add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));

    // Handle admin-post
    add_action('admin_post_nopriv_' . self::ACTION_PROCESS, array($this, 'processRequest'));
    add_action('admin_post_' . self::ACTION_PROCESS, array($this, 'processRequest'));
    
    // Handle admin-ajax
    add_action('wp_ajax_nopriv_' . self::ACTION_PROCESS, array($this, 'processRequestAjax'));
    add_action('wp_ajax_' . self::ACTION_PROCESS, array($this, 'processRequestAjax'));
  }

  /**
   * Load translations
   */
  public function loadTextDomain() {
    $locale = apply_filters('plugin_locale', get_locale(), self::DOMAIN);
    $path = WP_LANG_DIR . DIRECTORY_SEPARATOR . self::PLUGIN_ID . DIRECTORY_SEPARATOR;
    $path .= self::DOMAIN . '-' . $locale . '.mo';

    //load_textdomain(self::DOMAIN, $path);
    load_plugin_textdomain(self::DOMAIN, false, plugin_dir_path(__FILE__) . DIRECTORY_SEPARATOR . 'languages');
  }

  /**
   * Add scripts
   */
  public function enqueueScripts() {
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-tooltip');
    wp_enqueue_script(self::PLUGIN_ID, plugin_dir_url(__FILE__) . 'js/process.js', array('jquery'), time(), true);

    // TODO: Register our custom js/process.js

    wp_localize_script(self::PLUGIN_ID, self::PLUGIN_OBJ, array(
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce(self::PLUGIN_ID),
      'class' => self::PLUGIN_ID,
      'successMessage' => __('Success. You should receive confirmation message in 10 minutes. If not, please subscribe manually.', self::DOMAIN),
      'errorMessage' => __('Error. Subscribing to group failed. Please, subscribe manually.', self::DOMAIN)
    ));
  }

  /**
   * Register our widget to WordPress
   */
  public static function registerWidget() {
    register_widget('Seravo\SubscribeGoogleGroups\Widget');
  }

  /**
   * Return subscription address for given group
   */
  public static function formatGroupAddress($group) {
    return sanitize_email(sprintf(self::SUBSCRIPTION_ADDRESS, $group));
  }

  /**
   * Read group address using widget number in $_POST
   * Expects that you've validated nonce
   */
  public static function getGroupAddress($widget_number) {
    if ($widget_number == -1) {
      return '';
    }
    $group = Widget::get_group_name($widget_number);
    $to_address = self::formatGroupAddress($group);
    return $to_address;
  }

  /**
   * Read From address from $_POST
   */
  public static function getFromAddress() {
    return isset($_POST[self::EMAIL_ID]) ? sanitize_email($_POST[self::EMAIL_ID]) : '';
  }

  /**
   * Validate nonces for this plugin
   *
   * @return returns true if nonce is valid, else return false
   */
  public static function validNonce() {
    if (!isset($_POST['_wpnonce']) ||
      !wp_verify_nonce($_POST['_wpnonce'], self::PLUGIN_ID)) {
      self::log('Invalid nonce!');
      return false;
    }
    return true;
  }

  /**
   * Process subscription form submission
   *
   * @param $ajax are we processing AJAX request?
   * @return Return JSON if we're replying to AJAX query, otherwise redirect
   *     to previous page with status set in query parameter.
   */
  public static function processRequest($ajax = false) {
    $res = array(
      'status' => 'unknown',
      'widget_number' => isset($_POST['widget_number']) ? (int)$_POST['widget_number'] : -1
    );

    if (self::validNonce()) {
      $to = self::getGroupAddress($res['widget_number']);
      $from = self::getFromAddress();
      $res['status'] = self::sendMail($from, $to) ? 'success' : 'error';
    } else {
      $res['status'] = 'error';
    }

    if ($ajax) {
      wp_send_json($res);
    } else {
      $referer = isset($_POST['_wp_http_referer']) ? $_POST['_wp_http_referer'] : '';
      $url = add_query_arg(self::ACTION_PROCESS, $res['status'], home_url($referer));
      return wp_safe_redirect($url);
    }
  }

  /**
   * Process AJAX request
   *
   * @see processRequest($ajax)
   */
  public static function processRequestAjax() {
    return self::processRequest(true);
  }

  /**
   * Send email using wp_mail and "faked" From -address
   *
   * Successfully sent doesn't mean message will arrive to receiver (To:).
   * Mail filtering (like SPF/DKIM) might block messages sent from hosts
   * not specified in domain configuration.
   *
   * @param $from From-address (mail is sent from)
   * @param $to To-address (send mail to)
   * @param $subject E-mail message subject
   * @return boolean returns
   *     true if message was sent successfully,
   *     false if failure occured
   */
  public static function sendMail($from, $to) {
    if (!is_email($from) ||
      !is_email($to)) {
      return false;
    }

    $subj = sprintf('Subscribe to %s', $to);
    $msg = sprintf('Subscribe to %s. Sent from %s.', $to, home_url());

    self::log(sprintf('Send subscription mail for \'%s\' to \'%s\'.', $from, $to));
    return wp_mail(
      $to,
      $subj,
      $msg,
      array(
        sprintf('From: %s', $from)
      )
    );
  }
}

new Plugin();
