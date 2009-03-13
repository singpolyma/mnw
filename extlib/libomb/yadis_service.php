<?php
/**
 * Yadis service representation
 *
 * This class saves the relevant information from a Yadis service,
 * since they have some serialization issues.
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

class OMB_Yadis_Service {
  private $uri;
  private $localid;

  public function __construct($yadis_service) {
    $uris = $yadis_service->getURIs();
    if ($uris == array()) {
      $this->uri = null;
    } else {
      $this->uri = $uris[0];
    }

    $els = $yadis_service->getElements('xrd:LocalID');
    if (!$els) {
      $this->localid = null;
    } else {
      $this->localid = $yadis_service->parser->content($els[0]);
    }
  }

  public function getLocalID() {
    if ($this->localid === null) {
      throw new Exception();
    }
    return $this->localid;
  }

  public function getURI() {
    if ($this->uri === null) {
      throw new Exception();
    }
    return $this->uri;
  }
}
?>
