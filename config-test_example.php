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
 * config unit test file
 *
 * @package     mod_rocketchat
 * @category    config test file
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
set_config('instanceurl', 'https://rocketchat-server_url', 'mod_rocketchat');
set_config('restapiroot', '/api/v1/', 'mod_rocketchat');
set_config('apiuser', 'your_user_on_rocket.chat', 'mod_rocketchat');
set_config('apitoken', '#############', 'mod_rocketchat');
// Fake config test to avoid email domain troubles.
set_config('domainmail', 'your_domain_mail_if_necessary', 'mod_rocketchat'); // Optional argument.line.
set_config('usernamehook', 0, 'mod_rocketchat');
// 1 if activated, need hooklib.php with moodle_username_to_rocketchat function.
