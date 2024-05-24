# Rocket.Chat activity for Moodle #

This plugin allows teachers to keep synchronized users enrolled in a Moodle course into a dedicated Rocket.Chat private channel.

## Main feature
Adding this activity to a Moodle course will create a private channel in Rocket.Chat and push Moodle users associated to this activity as members of this newly created channel. The list of members will then be kept up to date.

It will be possible to access to this Rocket.Chat channel directly from Moodle or through any autonomous Rocket.Chat client.
## System requirements
Php 7.1 or 7.2 required

## Rocket.Chat settings requirements
### Authentication
* LDAP and CAS account fusion is adviced when moodle user account creation is activated
  for moodle Rocket.Chat account
* Manual Moodle account are possible enabling in plugin settings user creation and advanced user creation options
  * user_creation_adv_options_auth_methods choose "manual account" account method
    * every choosen Moodle auth method selected will be concerned by following option on user creation for Rocket.Chat
    * you can choose to activate each following setting depending of the desired bahaviour
    * user_creation_adv_options_requirePasswordChange force password change while creation an account on Rocket.Chat
    * user_creation_adv_options_setRandomPassword set a random password
    * user_creation_adv_options_sendWelcomeEmail send a welcome mail

### Moodle API account creation
* create a RocketChat local account
  * other authentification account such like CAS will work
* confirm it (verified button) through Rocket.Chat administration

### Roles and permissions
* Create a new role on Rocket.Chat with following permissions : 
  * create-user
  * clean-channel-history
  * delete-user
  * create-p
  * unarchive-room
  * view-full-other-user-info
  * view room administration
  * api-bypass-rate-limit
  * edit-room-retention-policy
  * create-personal-access-tokens
* Create an application user (local or CAS)
* Activate user by checling Verified checkbox
* Give the created user user and previous created role
* CAS authentication
  * In case of user account creation in Rocket.Chat through moodle plugin you must activate following settings
    * Trust CAS username
    * Authorize user creation
### Create a personal token for this account
#### Through Visual interface
* logged as the Moodle api account, though My Account -> Personal Access Tokens
* create a new personal access token with `Ignore Two Factor Authentification` checked
#### through command lines
* Retrieve auth token based on login/passowrd
```bash
curl https://chat.univ.fr/api/v1/login -d "username=superadmin&password=superpassword" | cut -d\" -f7-14
# {"userId":"XXXUserIDXXX","authToken":"XXXAuthTokenXXX"}
```
* Generate personal access token
```bash
curl -H "X-Auth-Token: XXXAuthTokenXXX" -H "X-User-Id: XXXUserIDXXX" -H "Content-type:application/json" https://chat.univ.fr/api/v1/users.generatePersonalAccessToken -d '{"tokenName": "moodletoken"}'
# {"token":"XXXPersonalTokenXXX","success":true}
```
* If you want to verify, you can list personal tokens
```bash
curl -H "X-Auth-Token: XXXAuthTokenXXX" -H "X-User-Id: XXXUserIDXXX" -H "Content-type:application/json" https://chat.univ.fr/api/v1/users.getPersonalAccessTokens
# {"tokens":[{"name":"moodletoken","createdAt":"2021-02-09T10:35:38.564Z","lastTokenPart":"XXX"}],"success":true}
```
* Generate a new token in case of lost
```bash
curl -H "X-Auth-Token: XXXAuthTokenXXX" -H "X-User-Id: XXXUserIDXXX" -H "Content-type:application/json" https://chat.univ.fr/api/v1/users.regeneratePersonalAccessToken -d '{"tokenName": "moodletoken"}'
# {"token":"XXXNewPersonalTokenXXX","success":true}
```

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
patch -p1 /moodlepath/admin/tool/recyclebin/classes/course_bin.php < /moodlepath//mod/rocketchat/patch/admin_tool_recyclebin_classes_course_bin.patch
patch -p1 /moodlepath/admin/tool/recyclebin/classes/category_bin.php < /moodlepath/mod/rocketchat/patch/admin_tool_recyclebin_classes_category_bin.patch
patch -p1 /moodlepath/user/classes/output/user_roles_editable.php  < /moodlepath/mod/rocketchat/patch/user_classes_output_user_roles_editable.patch

```

### Authentication settings
* apiuser will be rocket-chat userid
* apitoken a token generated on Rocket.Chat for the apiuser
  * can be generated on Rocket.Chat for apiuser on Profile -> My Account -> Security -> Personal Access Tokens menu
  * don't forget to enable create-personal-access-tokens permission for the apiuser through its role
## Backup settings
On moodle 3.5 versions `backup_auto_activities` backup settings must be checked otherwise Rocket.Chat module restoration from recycle bin will not work.

See [MDL-6621 tracker ticket](https://tracker.moodle.org/browse/MDL-66221) for more informations
## Specials capabilities
the following capabilities define if a role is able to perform some setting in the module instance : 
* mod/rocketchat:change_embedded_display_mode : enable a user to choose embbeded Rocket.Chat web client display mode while eidting the module instance
* mod/rocketchat:candefineroles : enable a user to change defaults roles mapping while editing the module instance
* mod/rocketchat:candefineadvancedretentionparamaters : enable a user to define advanced message retention options
## Unit tests
### settings
* in Rocket.Chat you must adjust the Rate Limiter to enable unit tests large amount of requests
* to run unit tests that involved Rocket.Chat remote server just create a config-test.php file into the module rocketchat root directory
* fill in with following parameters
```php
<?php
set_config('instanceurl','https://rocketchat-server_url','mod_rocketchat');
set_config('restapiroot','/api/v1/','mod_rocketchat');
set_config('apiuser','your_user_on_rocket.chat','mod_rocketchat');
set_config('apitoken','#############','mod_rocketchat');
// fake config test to avoird email domain troubles
set_config('domainmail','your_domain_mail_if_necessary','mod_rocketchat'); // Optional argument.line.
```
### requirements
* see [moodle documentation](https://docs.moodle.org/dev/PHPUnit)

### Provided tests
* don't forget to init phpunit moodle tests environment (requirements)
```bash
cd /www_moodle_path
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_background_enrolments_testcase" mod/rocketchat/tests/backup_enrolments_test.php
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_backup_restore_testcase" mod/rocketchat/tests/backup_restore_test.php
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_course_reset_testcase" mod/rocketchat/tests/course_reset_test.php
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_observer_testcase" mod/rocketchat/tests/observer_test.php
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_api_manager_testcase" mod/rocketchat/tests/rocket_chat_api_manager_test.php
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_recyclebin_category_testcase" mod/rocketchat/tests/recyclebin_category_test.php
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_recyclebin_testcase" mod/rocketchat/tests/recyclebin_test.php
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_privacy_testcase" mod/rocketchat/tests/privacy_provider_test.php
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_tools_testcase" mod/rocketchat/tests/mod_rocketchat_tools_test.php
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_moderator_and_user_roles_testcase" mod/rocketchat/tests/moderator_and_user_roles_test.php
/www_moodle_path/vendor/bin/phpunit "mod_rocketchat_retention_testcase" mod/rocketchat/tests/retention_test.php
```

## Functional tests with behat

### Requirements
* see [moodle documentation](https://docs.moodle.org/dev/Running_acceptance_test)

### Settings
* see phpunit settings section

### Provided tests
* don't forget to init behat moodle tests environment (requirements)
```shell script
cd /www_moodle_path
vendor/bin/behat --config /datas/mdlfarm/behat_datas/behatrun/behat/behat.yml --tags mod_rocketchat
```

for behat funcitonal tests by file

```shell script
cd /www_moodle_path
vendor/bin/behat --config /datas/mdlfarm/behat_datas/behatrun/behat/behat.yml  /www_moodle_path/mod/rocketchat/tests/behat/maxage_limit.feature
vendor/bin/behat --config /datas/mdlfarm/behat_datas/behatrun/behat/behat.yml  /www_moodle_path/mod/rocketchat/tests/behat/rocketchat_activity_creation.feature
vendor/bin/behat --config /datas/mdlfarm/behat_datas/behatrun/behat/behat.yml  /www_moodle_path/mod/rocketchat/tests/behat/rocketchat_retention.feature
vendor/bin/behat --config /datas/mdlfarm/behat_datas/behatrun/behat/behat.yml  /www_moodle_path/mod/rocketchat/tests/behat/rocketchat_test_connection.feature
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

If you need some help, you can contact us via this email address : mod-rocketchat-contact _AT_ esup-portail.org
