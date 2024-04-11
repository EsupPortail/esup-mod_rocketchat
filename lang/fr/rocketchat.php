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
 * @author      Celine Pervès <cperves@unistra.fr>
 * @copyright   2020 ESUP-Portail {@link https://www.esup-portail.org/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['description'] = 'Module de synchronisation entre Rockat.Chat et Moodle';
$string['modulename'] = 'Rocket.Chat';
$string['modulenameplural'] = 'Rocket.Chat';
$string['pluginname'] = 'Rocket.Chat';
$string['modulename_help'] = 'En ajoutant cette activité à un  cours Moodle, un canal privé Rocket.Chat sera automatiquent créé.

Les utilisateurs associés, en fonction de leur rôle dans le cours, y seront inscrits et mis à jour automatiquement.

Le canal Rocket.Chat sera alors accessible directement depuis moodle ou via tout autre client Rocket.Chat.

Les fonctionnalités de restriction d\'accès intégrées à moodle ne sont pas disponibles.';
$string['modulename_link'] = 'mod/rocketchat';
$string['name'] = 'Nom de l\'instance (dans le cours)';
$string['instanceurl'] = 'URL de l\'instance Rocket.Chat';
$string['instanceurl_desc'] = 'URL de l\'instance Rocket.Chat (ex: https://rocketchat.univ.fr)';
$string['restapiroot'] = 'Chemin de l\'API Rocket.Chat';
$string['restapiroot_desc'] = 'Adresse de l\'API Rocket.Chat';
$string['apiuser'] = 'Utilisateur API Rocket.Chat';
$string['apiuser_desc'] = 'Utilisateur Rocket.Chat utilisé par Moodle pour interroger l\'API du serveur Rocket.Chat (ne pas mettre de token à cet endroit)';
$string['apitoken'] = 'Token API Rocket.Chat';
$string['apitoken_desc'] = 'Token API  de l\'utilisateur renseigné ci-dessus';
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
$string['rocketchat:addinstance'] = 'Ajouter une ressource Rocket.Chat';
$string['rocketchat:view'] = 'Voir une ressource Rocket.Chat';
$string['rocketchat:candefineroles'] = 'Peut définir les rôles à propager pour les inscriptions aux groupes privés Rocket.Chat';
$string['rocketchat:change_embedded_display_mode'] = 'Peut changer le mode d\'affichage de la ressource (menu de gauche visible ou non)';
$string['rocketchat_nickname'] = '{$a->firstname} {$a->lastname}';
$string['create_user_account_if_not_exists'] = 'Créer le compte Rocket.Chat';
$string['create_user_account_if_not_exists_desc'] = 'Lors de l\'inscription d\'un utilisateur, créé l\'utilisateur correspondant dans Rocket.Chat, s\'il n\'existe pas';
$string['recyclebin_patch'] = 'Le patch pour la corbeille est-il installé ?';
$string['recyclebin_patch_desc'] = 'Ce patch du fichier admin/tool/recyclebin/classes/course_bin.php permet de sauvegarder les cmid et instanceid de la ressource Rocket.Chat lors de sa suppression afin d\'en permettre une restauration ultérieure';
$string['validationgroupnameregex'] = 'Regex de nom de groupe valide';
$string['validationgroupnameregex_desc'] = 'Moodle remplacera les caractères non autorisés par _. Cette expression régulière est l\'exacte négation de l\'expression régulière paramétrée dans Rocket.Chat.';
$string['embedded_display_mode'] = 'Mode d\'affichage réduit';
$string['embedded_display_mode_desc'] = 'Si coché, le menu de gauche de l\'interface Rocket.Chat sera caché, masquant les autres canaux';
$string['embedded_display_mode_setting'] = 'Mode d\'affichage réduit';
$string['embedded_display_mode_setting_desc'] = 'Si coché, le menu de gauche de l\'interface Rocket.Chat sera caché, masquant les autres canaux';
$string['rocketchat:addinstance'] = 'Ajoute une instance Rocket.Chat';
$string['rocketchat:candefineroles'] = 'Autorise la modification de la définition des rôles dans le paramétrage de chaque instance';
$string['modulenameplural'] = 'Instances Rocket.Chat';
$string['removemessages'] = 'Supprime tous les messages';
$string['removeditem'] = 'messages supprimés dans {$a->rocketchatid}';
$string['datastransmittedtorc'] = 'Données transmises dans Rocket.Chat';
$string['privacy:metadata:mod_rocketchat:rocket_chat_server:username'] = 'Login de l\'utilisateur';
$string['privacy:metadata:mod_rocketchat:rocket_chat_server:firstname'] = 'Prénom de l\'utilisateur';
$string['privacy:metadata:mod_rocketchat:rocket_chat_server:firstname'] = 'Nom de l\'utilisateur';
$string['privacy:metadata:mod_rocketchat:rocket_chat_server:email'] = 'Mail de l\'utilisateur';
$string['privacy:metadata:mod_rocketchat:rocket_chat_server:rocketchatids'] = 'Identifiants des groupes Rocket.Chat où est inscrit l\'utilisateur';
$string['privacy:metadata:mod_rocketchat:rocket_chat_server'] = 'Données transmises au serveur Rocket.Chat distant';
$string['connection-success'] = 'La connexion a été établie avec succès.';
$string['testconnection'] = 'Tester la connexion à Rocket.Chat';
$string['testtitle'] = 'Test de connexion Rocket.Chat.';
$string['testconnectionfailure'] = 'Les paramètres suivants doivent être renseignés dans la configuration du plugin :</br>instanceurl, restapiroot, apiuser and apitoken.</br>Veuillez vérifier que tous ces champs sont renseignés.';
$string['settings'] = 'Paramétrage de Rocket.Chat';
$string['errorintestwhilegconnection'] = 'Erreur lors du test de la connection';
$string['connectiontestresult'] = 'Résultat du test de connexion';
$string['groupecreationerror'] = "Erreur de la création du groupe distant sur Rocket.Chat";
$string['testerrormessage'] = 'Message d\'erreur :</br>{$a}';
$string['testerrorcode'] = 'Code erreur : {$a}';
$string['rcgrouperror'] = 'Le groupe distant Rocket.Chat ne peut être récupéré. Veuillez contacter votre administrateur système. Code erreur {$a}.';
$string['usernamehook'] = 'Activer le hook du username.';
$string['usernamehook_desc'] = 'En activant cette option, il est alors possible de transformer le nom d\'utilisateur moodle pour qu\'il corresponde à celui sur Rocket.Chat.</br>Créez un fichier hooklib.php file dans le répertoire d\'installation du module rocketchat.</br>Ajoutez une fonction moodle_username_to_rocketchat qui doit retourner le username moodle transformé pour correspondre à celui sur Rocket.Chat. </br>le fichier hooklib-example.php est fourni à titre d\'example.';
$string['background_enrolment_task'] = 'Effectuer les inscriptions/desinscriptions de cours en tâche différée pour les méthodes d\'inscription sélectionnées.';
$string['background_enrolment_task_desc'] = 'Ceci permet de résoudre des problèmes de performances lors de l\'inscription de grandes quantités d\'utilisateurs.</br>Cela empêchera que l\'utilisateur réalisant l\'inscription attende trop longtemps sur la page d\'inscriptions aux cours.</br>Ce paramètre agit en différant, via une tâche en arrière plan, les inscriptions/désinscriptions au server Rocket.Chat distant</br>Nous vous conseillons fortement de sélectionner les méthodes flatfile et cohort si elles sont activées.';
$string['background_add_instance'] = 'Effectuer les inscriptions à Rocket.Chat en tâche d\'arrière plan à la création d\'une nouvelle instance';
$string['background_add_instance_desc'] = 'Ceci améliorera le délai d\'attente à la création d\'une nouvelle instance';
$string['background_restore'] = 'Effectuer les inscriptions à Rocket.Chat en tâche d\'arrière plan à la duplication du module.';
$string['background_restore_desc'] = 'Ceci améliorera le délai d\'attente à la duplication d\'un module Rocket.Chat.';
$string['background_synchronize'] = 'Effectuer les inscriptions à Rocket.Chat en tâche d\'arrière plan lors de la sychronisation des inscriptions.';
$string['background_synchronize_desc'] = 'Se produit après le retour depuis la corbeille d\'un cours ou d\'un module Rocket.Chat';
$string['background_user_update'] = 'Effectuer les inscriptions à Rocket.Chat en tâche d\'arrière plan lors de la mise à jour d\'informations utilisateur tel que l\'activation/deactivation.';
$string['background_user_update_desc'] = 'Ceci améliorera le délai d\'attente lors de la mise à jour des utilisateurs';
$string['retentionenabled'] = 'Rétention des messages';
$string['retentionenabled_desc'] = 'Activer la rétention de messages';
$string['maxage'] = 'Valeur du temps de rétention des messages(maxAge) pour un groupe Rocket.Chat';
$string['maxage_desc'] = 'Si le mode "Surcharger la rétention globale des messages" est activé, la valeur de temps de rétention des messages sera appliquée au group Rocket.Chat, surchageant le temps de rétention global du serveur. ATTENTION : la valeur 0 déclenche la suppression régulière des messages.';
$string['maxage_limit'] = 'Valeur du temps de rétention maximum des messages(maxAge) pour un groupe Rocket.Chat';
$string['maxage_limit_desc'] = 'Valeur du temps de rétention maximum des messages(maxAge) pour un groupe Rocket.Chat';
$string['limit_override'] = 'Valeur supérieure au maximum ({$a}) pour la plateforme';
$string['filesonly'] = 'Élaguer uniquement les fichiers, conserver les messages';
$string['filesonly_desc'] = 'Si activé, les messages ne sont pas effacés, Mais les fichier sont remplacés par un fichier simple supprimé par message d\'élagage automatique. Quand utilisés conjointement avec "Exclure les messages épinglés", seuls les fichiers non épinglés sont supprimés.';
$string['exludeoinned'] = 'Exclure les messages épinglés';
$string['exludeoinned_desc'] = 'Si activé, les messages épinglés ne sont pas supprimés. Par exemple, si vous avez épinglé quelques messages avec des liens importants, ils restent intacts.';
$string['retentionfeature'] = 'Fonctionalité de réention des messages';
$string['retentionfeature_desc'] = 'Activer le paramétrage de la rétention des message par groupe Rocket.Chat. Attention cette fonctionnalité de Rocket.Chat n\'est valable qu\'à partir de la version 3.10.3 du serveur Rocket.Chat server.';
$string['mod/rocketchat:candefineadvancedretentionparamaters'] = 'Permet de surcharger l\'activation des paramètres de rétention globale des messages dans une instance de Rocket.Chat.';
$string['mod/rocketchat:canactivateretentionpolicy'] = 'Permet de surcharger l\'activation de la rétention des messages dans une instance de Rocket.Chat.';
$string['rocketchat:canactivateretentionpolicy'] = 'Permet de surcharger l\'activation de la rétention des messages dans une instance de Rocket.Chat.';
$string['rocketchat:candefineadvancedretentionparamaters'] = 'Permet de surcharger l\'activation des paramètres de rétention globale des messages dans une instance de Rocket.Chat.';
$string['displaysection'] = 'Affichage';
$string['retentionsection'] = 'Rétention des messages';
$string['rolessection'] = 'Définition des rôles';
$string['pluginname_admin'] = 'Interface admin Rocketchat';
$string['welcome_string'] = 'Interface admin Rocketchat';
$string['header_details'] = 'Details pour le module : ';
$string['days'] = 'jours';
$string['yes'] = 'Oui';
$string['no'] = 'Non';
$string['error'] = 'Erreur !';
$string['details'] = 'Détails';
$string['rocketchat_synchronise_task'] = 'Synchroniser tous les salons rocketchat';
$string['warningapiauthchanges'] = 'Attention , le plugin moodle  Rocket.Chat a changé sont sa méthode d\'authentification . Seule l\'authentification par token est prise en charge.\nVeuillez s\'il vous plait changer le paramétrage du plugin.';
$string['replacementgroupnamecharacters'] = 'Caractères à remplacer dans le nom des groupes';
$string['replacementgroupnamecharacters_desc'] = 'Rentrez ici les couples caractères à remplacer/ caractère de remplacement, séparés par des ";". Un couple par ligne.';
$string['user_creation_adv_options_auth_methods'] = 'Méthodes d\'authentification concernées par l\'ajout d\'options supplémentaires à la création de comptes sur Rocket.chat.';
$string['user_creation_adv_options_auth_methods_desc'] = 'Méthodes d\'authentification concernées par l\'ajout d\'options supplémentaires à la création de comptes sur Rocket.chat.';
$string['user_creation_adv_options_requirePasswordChange'] = 'Changement de mot de passe requis après la création d\'un compte utilisateur.';
$string['user_creation_adv_options_requirePasswordChange_desc'] = 'Changement de mot de passe requis après la création d\'un compte utilisateur.';
$string['user_creation_adv_options_setRandomPassword'] = 'Mettre un mot de passe généré à la création d\'un compte utilisateur.';
$string['user_creation_adv_options_setRandomPassword_desc'] = 'Mettre un mot de passe généré à la création d\'un compte utilisateur.';
$string['user_creation_adv_options_sendWelcomeEmail'] = 'Envoyer un email d\'accueil à la création d\'un compte utilisateur.';
$string['user_creation_adv_options_sendWelcomeEmail_desc'] = 'Envoyer un email d\'accueil à la création d\'un compte utilisateur.';
$string['user_creation_adv_options_verify'] = 'Exiger la vérification du mail à la création d\'un compte utilisateur.';
$string['user_creation_adv_options_verify_desc'] = 'Exiger la vérification du mail à la création d\'un compte utilisateur.';
