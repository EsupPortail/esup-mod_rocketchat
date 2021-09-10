<?php

require('../../config.php');
require_once('locallib.php');

$roomid = required_param('roomid', PARAM_RAW_TRIMMED);

$returnurl = new moodle_url('/mod/rocketchat/management.php');
var_dump("here");
require_sesskey();

\mod_rocketchat_tools::synchronize_group_members($roomid);

redirect($returnurl);