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
 * Plugin internal classes, functions and constants are defined here.
 *
 * @package     mod_rocketchat
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class mod_rocketchat_tools{
    public static function rocketchat_group_name($cmid, $course){
        global $CFG, $SITE;
        $formatarguments = new stdClass();
        $formatarguments->moodleshortname =  $SITE->shortname;
        $formatarguments->moodlefullnamename =  $SITE->fullname;
        $formatarguments->moodleid =  sha1($CFG->wwwroot);
        $formatarguments->moduleid =  $cmid;
        $formatarguments->modulemoodleid =  sha1($SITE->shortname.'_'.$cmid);
        $formatarguments->courseid =  $course->id;
        $formatarguments->courseshortname =  $course->shortname;
        $formatarguments->coursefullname =  $course->fullname;
        $groupnameformat = get_config('mod_rocketchat', 'groupnametoformat');
        $groupnameformat = is_null($groupnameformat) ? '{$a->moodleid}_{$a->courseshortname}_{$a->moduleid}' : $groupnameformat;
        return self::format_string($groupnameformat,$formatarguments);
    }



    public static function format_string($string, $a) {
        if ($a !== null) {
            // Process array's and objects (except lang_strings).
            if (is_array($a) or (is_object($a) && !($a instanceof lang_string))) {
                $a = (array)$a;
                $search = array();
                $replace = array();
                foreach ($a as $key => $value) {
                    if (is_int($key)) {
                        // We do not support numeric keys - sorry!
                        continue;
                    }
                    if (is_array($value) or (is_object($value) && !($value instanceof lang_string))) {
                        // We support just string or lang_string as value.
                        continue;
                    }
                    $search[]  = '{$a->'.$key.'}';
                    $replace[] = (string)$value;
                }
                if ($search) {
                    $string = str_replace($search, $replace, $string);
                }
            } else {
                $string = str_replace('{$a}', (string)$a, $string);
            }
        }
        return $string;
    }
}