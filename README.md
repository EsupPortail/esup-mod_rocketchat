# Rocket.Chat activity for Moodle #

This plugin allows teachers to push students from Moodle into a dedicated Rocket.Chat channel.

## Main feature
Adding this activity to a Moodle course will create a channel in Rocket.Chat and push Moodle users associated to this activity as members of this newly created channel. The list of members will then be kept up to date.

## Installation
### Rocket.Chat REST PHP API
A simple php api to communicate with Rocket.Chat Web service in REST
Use composer to manage your dependencies and download :

```bash
composer require esup-portail/rocket-chat-rest-client
```

### Moodle plugin
1. Copy the Rocket.Chat plugin to the `mod` directory of your Moodle instance: `git clone https://github.com/EsupPortail/esup-mod_rocketchat MOODLE_ROOT_DIRECTORY/mod/rocketchat`
2. Visit the notifications page to complete the installation process

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
