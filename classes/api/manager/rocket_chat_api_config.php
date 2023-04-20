<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * rocket chat api config class
 *
 * @package     mod_rocketchat
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_rocketchat\api\manager;

use moodle_exception;

class rocket_chat_api_config {
    private $instanceurl;
    private $restapiroot;
    private $apiuser;
    private $apitoken;

    /**
     * @return mixed
     */
    public function get_instanceurl() {
        return $this->instanceurl;
    }

    /**
     * @return mixed
     */
    public function get_restapiroot() {
        return $this->restapiroot;
    }

    /**
     * @return mixed
     */
    public function get_apiuser() {
        return $this->apiuser;
    }

    /**
     * @return mixed
     */
    public function get_api_token() {
        return $this->apitoken;
    }

    public function __construct() {
        if (is_null($this->instanceurl)) {
            if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
                global $CFG;
                require($CFG->dirroot.'/mod/rocketchat/config-test.php');
            }
            $config = get_config('mod_rocketchat');
            if (empty($config->instanceurl)) {
                throw new moodle_exception('RocketChat instance url is empty');
            }
            if (empty($config->restapiroot)) {
                throw new moodle_exception('RocketChat rest api root is empty');
            }
            if (empty($config->apiuser)) {
                throw new moodle_exception('RocketChat api user is empty');
            }
            if (empty($config->apitoken)) {
                throw new moodle_exception('RocketChat api token is empty');
            }
            $this->instanceurl = $config->instanceurl;
            $this->restapiroot = $config->restapiroot;
            $this->apiuser = $config->apiuser;
            $this->apitoken = $config->apitoken;
        }
    }

}
