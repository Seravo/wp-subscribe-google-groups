<?php
 /**
 * @package mitchnegus\SubscribeBerkeleyGroups
 * @version 1.0
 *
 * wp-subscribe-berkeley-groups - WordPress Plugin/Widget for subscribing to 
 * Google Groups.
 * Copyright (C) 2018 Mitch Negus 
 * (adapted from wp-subscribe-google-groups, a plugin by Seravo Oy)
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

namespace mitchnegus\SubscribeBerkeleyGroups;

// If WP is calling, ABSPATH is defined. And no-one else should call.
if (!defined('ABSPATH')) {
  die();
}

// TODO: Show instructions for manual subscription

/**
 * Widget
 *
 * Provides WordPress widget for subscribing to admin-specified
 * Berkeley Google Group.
 *
 * User enters e-mail address to textfield in widget, clicks 'Subscribe',
 * and gets confirmation mail from Google.
 *
 * @since 1.0
 */
class Widget extends \WP_Widget
{
  /**
   * Constructor for Widget
   */
  public function __construct() {
    parent::__construct(
      Plugin::PLUGIN_ID,
      __(Plugin::PLUGIN_NAME, Plugin::DOMAIN),
      array(
        'classname' => Plugin::PLUGIN_ID,
        'description' => __('Widget for subscribing to Google Groups', Plugin::DOMAIN)
      )
    );
  }

  /**
   * Convert key/value to HTML attribute
   *
   * Also do some magic, depending on attribute name/value.
   *
   * @param $key attribute name
   * @param $value attribute value
   * @param $plain boolean, should we use plain name/id for this field
   * @return returns the HTML code for given attribute
   */
  public function get_html_attr($key, $value, $plain = false) {
    // Boolean values: if true, required=required, else don't display at all
    if (is_bool($value)) {
      if ($value === false) {
        return '';
      }
      $value = $key;
    }
    switch ($key) {
      case 'class':
        $value = sanitize_html_class($value);
        break;
      case 'name':
        if (!$plain) {
          $value = $this->get_field_name($value);
        }
        break;
      case 'id':
        if (!$plain) {
          $value = $this->get_field_id($value);
        }
        break;
      default:
        break;
    }
    return sprintf('%s="%s"', sanitize_html_class($key), esc_attr($value));
  }

  /**
   * Prints label and input field with contents
   * sanitized.
   *
   * @param $title title/label for this field
   * @param $options HTML attributes as PHP array
   * @param $plain should we use plain field name/id for this field?
   *    ie. do not add references to specific widget
   */
  public function formfield($title, $options, $plain = false) {
    if (!empty($title)) {
      $label = sprintf('<label for="%s">%s</label>', '', __($title, Plugin::DOMAIN));
    } else {
      $label = '';
    }
    $attrs = '';
    foreach ($options as $k => $v) {
      $attrs .= ' ' . self::get_html_attr($k, $v, $plain);
    }
    $input = sprintf('<input%s/>', $attrs);
    printf('<p>%s%s</p>', $label, $input);
  }

  /**
   * Display widget preferences in WordPress Admin
   *
   * @param $instance widget instance to edit
   */
  public function form($instance) {
    self::formfield(__('Title:', Plugin::DOMAIN), array(
      'class' => 'widefat',
      'type'  => 'text',
      'id'  => Plugin::TITLE_ID,
      'name'  => Plugin::TITLE_ID,
      'value' => !empty($instance[Plugin::TITLE_ID]) ? $instance[Plugin::TITLE_ID] : Plugin::PLUGIN_NAME,
    ));

    self::formfield(__('Group Name:', Plugin::DOMAIN), array(
      'class' => 'widefat',
      'type'  => 'text',
      'id'  => Plugin::GROUP_ID,
      'name'  => Plugin::GROUP_ID,
      'value' => !empty($instance[Plugin::GROUP_ID]) ? $instance[Plugin::GROUP_ID] : '',
      'pattern' => '[A-Za-z0-9]{1}[A-Za-z0-9_]{1,}',
      'required' => true
    ));
  }

  /**
   * Save new settings
   *
   * @param $new_instance new widget instance, ie. new values
   * @param $old_instance old widget instance, ie. old values
   * @return returns widget instance with updated settings
   */
  public function update($new_instance, $old_instance) {
    $instance = $old_instance;
    $instance[Plugin::TITLE_ID] = strip_tags($new_instance[Plugin::TITLE_ID]);
    $instance[Plugin::GROUP_ID] = strip_tags($new_instance[Plugin::GROUP_ID]);
    return $instance;
  }

  /**
   * Displays widget
   *
   * @param $args see WP documentation
   * @param $instance widget instance
   */
  public function widget($args, $instance) {
    echo $args['before_widget'] . $args['before_title'];
    echo apply_filters('widget_title', $instance[Plugin::TITLE_ID]);
    echo $args['after_title'];

    // If group name is not set, show error message
    if (empty($instance[Plugin::GROUP_ID])) {
      printf('<p>%s</p>', __('Please, enter group name in widget settings.', Plugin::DOMAIN));
      echo $args['after_widget'];
      return;
    }

    ?>
    
    <div class="subscribe-berkeley-groups-widget">
    <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
    <?php
    wp_nonce_field(Plugin::PLUGIN_ID);

    /**
     * WordPress admin-post action
     */
    self::formfield('', array(
      'type' => 'hidden',
      'name' => 'action',
      'value' => Plugin::ACTION_PROCESS
    ), true);

    /**
     * Submit widget number. We use this later to determine
     * the group name we want user subscription request to be
     * sent.
     */
    self::formfield('', array(
      'type' => 'hidden',
      'name' => 'widget_number',
      'value' => $this->number,
      //'value' => $instance[Plugin::GROUP_ID],
    ), true);

    /**
     * E-mail address to be subscribed
     */
    self::formfield(__('E-mail:', Plugin::DOMAIN), array(
      'type' => 'email',
      'id'   => Plugin::EMAIL_ID,
      'name' => Plugin::EMAIL_ID,
      'required' => true
    ), true);

    /**
     * Submit button
     */
    self::formfield('', array(
      'type' => 'submit',
      'value' => __('Subscribe', Plugin::DOMAIN),
    ));
    ?>
    </form>
    <div class="status">
    <?php
    /**
     * If GET parameter is set, check wheter previous action
     * succeeded or errored.
     */
    if (isset($_GET[Plugin::ACTION_PROCESS])) {
      switch ($_GET[Plugin::ACTION_PROCESS]) {
        case 'success':
          $msg = __('Success. You should receive confirmation message in 10 minutes. If not, please subscribe manually.', Plugin::DOMAIN);
          break;
        case 'error':
          $msg = __('Error. Subscribing to group failed. Please, subscribe manually.', Plugin::DOMAIN);
          break;
        default:
          break;
      }
    }
    if (isset($msg)) {
      printf('<p>%s</p>', $msg);
    }
    ?>
    </div><!-- /.status -->
    </div><!-- /.subscribe-berkeley-groups-widget -->
    <?php

    echo $args['after_widget'];
  }

  /**
   * Get Berkeley Google Groups group name
   *
   * @param $widget_number widget number for which we're looking the info
   * @return returns group name or empty string if name can't be determined
   */
  public static function get_group_name($widget_number) {
    $dummy = new Widget();
    $settings = $dummy->get_settings();
    return $settings[$widget_number][Plugin::GROUP_ID];
  }
}
