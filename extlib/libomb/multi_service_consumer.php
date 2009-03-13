<?php

require_once 'constants.php';
require_once 'service_consumer.php';

/**
 * OMB service representation
 *
 * This class represents a complete remote OMB service. It provides discovery
 * and execution of the service‘s methods. 
 *
 * PHP version 5
 *
 * LICENSE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package   OMB
 * @author    Adrian Lang <mail@adrianlang.de>
 * @copyright 2009 Adrian Lang
 * @license   http://www.gnu.org/licenses/agpl.html GNU AGPL 3.0
 **/

class OMB_Multi_Service_Consumer {
  $services;

  public function __construct ($services_url, $consumer_url) {
    $this->services = array();
    foreach($services_url as $service_url) {
      $this->services[$service_url] = new OMB_Service_Consumer($service_url, $consumer_url);
    }
  }

  public function updateProfile($profile) {
    foreach($services as $service) {
      $service->updateProfile($profile);
    }
  }

  public function postNotice($notice) {
    foreach($services as $service) {
      $service->updateProfile($profile);
    }
  }
}
