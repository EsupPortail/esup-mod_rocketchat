@mod @mod_rocketchat
Feature: mod_rocketchat

  Background:
    Given the following config values are set as admin:
      | groupnametoformat | moodlebehattest_{$a->courseshortname} | mod_rocketchat |
      | retentionfeature | 1 | mod_rocketchat |
      | retentionenabled | 1 | mod_rocketchat |
      | maxage_limit | 100 | mod_rocketchat |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | John | Teacher1 | teacher1@example.com |
      | student1 | Jane | Student1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |

  @javascript
  Scenario: Create a rocketchat activity and click on resulting link
    Given the following "activities" exist:
      | activity | idnumber | name | intro | groupname | course | maxage |
      | rocketchat | rocketchat1 | rocketchat activity | description | moodle |C1     | 90 |
    And I log in as "admin"
    And I set the following system permissions of "Teacher" role:
      | capability | permission |
      | mod/rocketchat:canactivateretentionpolicy | Allow |
    Then I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I open "rocketchat activity" actions menu
    And I choose "Edit settings" in the open action menu
    And I wait until the page is ready
    And "retentionenabled" "checkbox" should exist
    When I set the field "retentionenabled" to "checked"
    And the "maxage" "field" should be enabled
    And I set the field "maxage" to "400"
    And I press "Save and display"
    And I should see "Retention time exceeded maximum setting of 100" in the "#id_error_maxage" "css_element"
    Then I set the field "maxage" to "90"
    And I press "Save and display"
    And I should see "Join Rocket.Chat session"





