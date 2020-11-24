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
 * hookfile
 *
 * @package     mod_rocketchat
 * @category    config test file
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @author Céline Pervès<cperves@unistra.fr>
 * from Pascal Rigaux <Pascal.Rigaux@univ-paris1.fr> suggestion
 * regexp from https://github.com/RocketChat/Rocket.Chat/blob/develop/app/lib/server/functions/saveUser.js#L109
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function moodle_username_to_rocketchat($moodleusername) {
    return preg_replace(
        '/[^0-9a-zA-Z-_.]/', '__',
        preg_replace('/@univ-paris1[.]fr$/', '', $moodleusername));
}
