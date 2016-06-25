<?php

/**
* @package ExpressionEngine
* @author Aphichat Panjamanee <http://aphichat.com>
* @version 0.1
* 
*/

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Td_postal_geocode_ext {

  var $name = 'TD Postal Geocode';
  var $version = '0.1';
  var $description = '';
  var $settings_exist = 'n';
  var $docs_url = '';

  var $settings = array();

  /**
   * Constructor
   *
   * @param   mixed   Settings array or empty string if none exist.
   */
  function __construct($settings = '')
  {
    $this->settings = $settings;
  }

  /**
   * Activate Extension
   *
   * This function enters the extension into the exp_extensions table
   *
   * @see https://ellislab.com/codeigniter/user-guide/database/index.html for
   * more information on the db class.
   *
   * @return void
   */
  function activate_extension()
  {
    $this->settings = array(
      'max_link_length' => 18,
      'truncate_cp_links' => 'no',
      'use_in_forum' => 'no'
    );

    $hooks = array(
      'entry_submission_ready' => 'entry_submission_ready',
      'low_search_catch_search' => 'low_search_catch_search',
    );

    foreach ($hooks as $hook => $method)
    {
      $data = array(
        'class' => __CLASS__,
        'method' => $method,
        'hook' => $hook,
        'settings' => serialize($this->settings),
        'priority' => 10,
        'version' => $this->version,
        'enabled' => 'y'
      );
      ee()->db->insert('extensions', $data);
    }
  }

  /**
   * Update Extension
   *
   * This function performs any necessary db updates when the extension
   * page is visited
   *
   * @return  mixed void on update / false if none
   */
  function update_extension($current = '')
  {
    if($current == '' OR $current == $this->version)
    {
      return FALSE;
    }

    ee()->db->where('class', __CLASS__);
    ee()->db->update('extensions', array('version' => $this->version));
  }

  /**
   * Disable Extension
   *
   * This method removes information from the exp_extensions table
   *
   * @return void
   */
  function disable_extension()
  {
    ee()->db->where('class', __CLASS__);
    ee()->db->delete('extensions');
  }

  /**
   * Insert Geocode to fields
   *
   * This method gets Geolocation from the Address field
   *
   * @return void, error
   */
  function entry_submission_ready($meta, $data, $autosave)
  {
    ee()->load->library('api');
    ee()->api->instantiate('channel_entries');

    if($geocode = $this->get_geocode($data['field_id_22']))
    {
      // goo.gl/z50Mvl
      // ee()->api_channel_entries->data['field_id_27'] = $geocode['lat'];
      // ee()->api_channel_entries->data['field_id_28'] = $geocode['lng'];
      ee()->api_channel_form_channel_entries->data['field_id_27'] = $geocode['lat'];
      ee()->api_channel_form_channel_entries->data['field_id_28'] = $geocode['lng'];
    } else {
      return ee()->output->show_user_error('general', 'Geen locatie gevonden...');
    }
  }

  /**
   * Low Search Cat Search
   *
   * This method changes zip code into Geolocation and returns to Low Search
   *
   * @return array
   */
  function low_search_catch_search($data)
  {
    if(isset($data['search:postal']) && $address = $data['search:postal']) {
      $data['distance:from'] = implode('|', $this->get_geocode($address));
      $data['distance:to'] = 'item_lat|item_lng';
      $data['distance:unit'] = 'km';
    }
    return $data;
  }

  /**
   * Get Geocode
   *
   * This method returns the geo-position
   *
   * @return array
   */
  function get_geocode($address)
  {
    $url = 'http://maps.googleapis.com/maps/api/geocode/json?address=';
    $geocode = array();

    $json = file_get_contents($url.urlencode($address));
    $data = json_decode($json);

    if(count($data->results) > 0) {
      foreach($data->results as $value) {
        $geocode['lat'] = $value->geometry->location->lat;
        $geocode['lng'] = $value->geometry->location->lng;
      }
      return $geocode;
    }
    return FALSE;
  }

}
// END CLASS