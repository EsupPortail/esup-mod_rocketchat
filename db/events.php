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
 * Plugin event observers are registered here.
 *
 * @package     mod_rocketchat
 * @category    event
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Céline Pervès <cperves@unistra.fr>
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(

    array(
        'eventname' => '\core\event\role_assigned',
        'callback' => '\mod_rocketchat\observers::role_assigned',
    ),
    array(
        'eventname' => '\core\event\role_unassigned',
        'callback' => '\mod_rocketchat\observers::role_unassigned',
    ),
    array(
        'eventname' => 'tool_recyclebin\event\course_bin_item_restored',
        'callback' => '\mod_rocketchat\observers::course_bin_item_restored',
    ),
    array(
        'eventname' => 'tool_recyclebin\event\course_bin_item_created',
        'callback' => '\mod_rocketchat\observers::course_bin_item_created',
    ),
    array(
        'eventname' => 'tool_recyclebin\event\course_bin_item_deleted',
        'callback' => '\mod_rocketchat\observers::course_bin_item_deleted',
    ),
    array(
        'eventname' => 'tool_recyclebin\event\category_bin_item_restored',
        'callback' => '\mod_rocketchat\observers::category_bin_item_restored',
    ),
    array(
        'eventname' => 'tool_recyclebin\event\category_bin_item_created',
        'callback' => '\mod_rocketchat\observers::category_bin_item_created',
    ),
    array(
        'eventname' => 'tool_recyclebin\event\category_bin_item_deleted',
        'callback' => '\mod_rocketchat\observers::category_bin_item_deleted',
    ),
    array(
        'eventname' => '\core\event\course_module_updated',
        'callback' => '\mod_rocketchat\observers::course_module_updated',
    ),
    array(
        'eventname' => '\core\event\user_updated',
        'callback' => '\mod_rocketchat\observers::user_updated',
    )
);
