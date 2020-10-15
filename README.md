# Rocket.Chat activity for Moodle #

This plugin allows teachers to push students from Moodle into a dedicated Rocket.Chat channel.

## Main feature
Adding this activity to a Moodle course will create a channel in Rocket.Chat and push Moodle users associated to this activity as members of this newly created channel. The list of members will then be kept up to date.

## Installation

### Moodle plugin
1. Copy the Rocket.Chat plugin to the `mod` directory of your Moodle instance:

```bash
git clone https://github.com/EsupPortail/esup-mod_rocketchat MOODLE_ROOT_DIRECTORY/mod/rocketchat
```
2. Visit the notifications page to complete the installation process
## Settings
* recyclebin_patch check this if patch is applied to core moodle file admin/tool/recyclebin/classes/course_bin.php
* patch is available in patch subdirectory
* you can apply it with patch command
```bash
patch -p1 /moodlepath/admin/tool/recyclebin/classes/course_bin.php < /moodlepath/patch/admin_tool_recyclebin_classes_course_bin.patch
patch -p1 /moodlepath/admin/tool/recyclebin/classes/category_bin.php < /moodlepath/patch/admin_tool_recyclebin_classes_category_bin.patch
patch -p1 /datas/dev/moodle/moodle_gitworkspaces/moodle35/moodle2_version/user/classes/output/user_roles_editable.php  < /datas/dev/moodle/moodle_gitworkspaces/moodle35/moodle2_uds/patch/user_classes_output_user_roles_editable.patch

```

## Specials capabilities
* mod/rocketchat:change_embedded_display_mode : enable a user to choose embbeded Rocket.Chat web client display mode while eidting the module instance 
* mod/rocketchat:candefineroles : enable a user to change defaults roles mapping while editing th emodule instance
## Unit tests
* to run unit tests that involved Rocket.Chat remote server just create a config-test.php file into the module rocketchat root dorectory
* fill in with following parameters
```php
<?php
set_config('instanceurl','https://rocketchat-server_url','mod_rocketchat');
set_config('restapiroot','/api/v1/','mod_rocketchat');
set_config('apiuser','your_user_on_rocket.chat','mod_rocketchat');
set_config('apipassword','#############','mod_rocketchat');
// fake config test to avoird email domain troubles
set_config('domainmail','your_domain_mail_if_necessary','mod_rocketchat'); // Optional argument.line.

```
## License ##

2020 ESUP-Portail (https://www.esup-portail.org)

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.

## Rocket.Chat moodle module ESUP team
* The Rocket.Chat moodle Esup team is composed of people from various places and from differents working domains.
  * Pedagocical engineer
  * System administrators
  * Developpers
* Institutions
  * Centrale Marseille
  * Université de Lorraine
  * Université de Strasbourg
  * Université de technologie de Troyes
