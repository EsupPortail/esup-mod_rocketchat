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
 * Plugin strings are defined here.
 *
 * @package     mod_rocketchat
 * @category    string
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['description'] = 'Module de synchronisation entre Rockat.Chat et Moodle';
$string['modulename'] = 'Rocket.Chat';
$string['modulenameplural'] = 'Rocket.Chat';
$string['pluginname'] = 'Rocket.Chat';
$string['name'] = 'Nom de l\'instance (dans le cours)';
$string['instanceurl'] = 'URL de l\'instance Rocket.Chat';
$string['instanceurl_desc'] = 'URL de l\'instance Rocket.Chat (ex: https://rocketchat.univ.fr)';
$string['restapiroot'] = 'Chemin de l\'API Rocket.Chat';
$string['restapiroot_desc'] = 'Adresse de l\'API Rocket.Chat';
$string['apiuser'] = 'Utilisateur API Rocket.Chat';
$string['apiuser_desc'] = 'Utilisateur API Rocket.Chat';
$string['apipassword'] = 'Mot de passe API Rocket.Chat';
$string['apipassword_desc'] = 'Mot de passe API Rocket.Chat';
$string['norocketchats'] = 'Aucune instance de module Rocket.Chat';
$string['groupnametoformat'] = 'Formatage de nom des groupes';
$string['groupnametoformat_desc'] = 'Les variables utilisables sont les suivantes : moodleid, moodleshortname, moodlefullname, moduleid, modulemoodleid (identifiant unique de la plateforme moodle), courseid, courseshortname, coursefullname';
$string['joinrocketchat'] = 'Rejoindre la session Rocket.Chat';
$string['displaytype'] = 'Mode d\'affichage';
$string['displaynew'] = 'Afficher dans une nouvelle fenêtre';
$string['displaypopup'] = 'Afficher dans une fenêtre pop-up';
$string['displaycurrent'] = 'Afficher dans la fenêtre actuelle';
$string['popupheight'] = 'Hauteur de la pop-up';
$string['popupwidth'] = 'Largeur de la pop-up';
$string['pluginadministration'] = 'Administration Rocket.Chat';
$string['defaultmoderatorroles'] = 'Modérateurs Rocket.Chat';
$string['moderatorroles'] = 'Rôles Moodle qui auront le rôle de modérateur dans Rocket.Chat';
$string['defaultmoderatorroles_desc'] = 'Rôles Moodle qui auront le rôle de modérateur dans Rocket.Chat';
$string['defaultuserroles'] = 'Utilisateurs Rocket.Chat';
$string['userroles'] = 'Rôles Moodle qui auront le rôle d\'utilisateurs Rocket.Chat';
$string['defaultuserroles_desc'] = 'Rôles Moodle qui auront le rôle d\'utilisateurs Rocket.Chat';
$string['mod_rocketchat:addinstance'] = 'Ajouter une instance de module Rocket.Chat';
$string['mod_rocketchat:view'] = 'Voir les instances du module Rocket.Chat';
$string['mod_rocketchat:candefineroles'] = 'Peut définir les rôles à propager pour les inscriptions aux groupes privés Rocket.Chat';
$string['rocketchat_nickname'] = '{$a->firstname} {$a->lastname}';
$string['create_user_account_if_not_exists'] = 'Créer le compte Rocket.Chat';
$string['create_user_account_if_not_exists_desc'] = 'Lors de l\'inscription d\'un utilisateur, créé l\'utilisateur correspondant dans Rocket.Chat, s\'il n\'existe pas';
$string['verbose_mode'] = 'Rocket.Chat api rest in verbose mode';
$string['verbose_mode_desc'] = 'If verbose, Rocket.Chat api rest error messages will be loggued into php error log file.';