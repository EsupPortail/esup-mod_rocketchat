@mod @mod_rocketchat
Feature: mod_rocketchat
  Rocket.Chat remote private group creation, deletion to clean Rocket.Chat test server

  Background:
    Given the following config values are set as admin:
      | groupnametoformat | moodlebehattest_{$a->courseshortname} | mod_rocketchat |
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
  Scenario: Create a rocketchat activity displaying in new window and click on resulting link
    Given the following "activities" exist:
      | activity | idnumber | name | intro | groupname | displaytype  | course |
      | rocketchat | rocketchat1 | rocketchat activity | description | moodle | 1 |C1     |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "rocketchat activity" "link"
    And I should see "Join Rocket.Chat session"
    And I am on "Course 1" course homepage with editing mode on
    And I open "rocketchat activity" actions menu
    And I choose "Delete" in the open action menu
    And I click on "Yes" "button"
    Then I should not see "rocketchat activity"

  @javascript
  Scenario: Create a rocketchat activity displaying in current window and click on resulting link
    Given the following "activities" exist:
      | activity | idnumber | name | intro | groupname | displaytype  | course |
      | rocketchat | rocketchat1 | rocketchat activity | description | moodle | 2 |C1     |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "rocketchat activity" "link"
    And I should see "Join Rocket.Chat session"
    And I am on "Course 1" course homepage with editing mode on
    And I open "rocketchat activity" actions menu
    And I choose "Delete" in the open action menu
    And I click on "Yes" "button"
    Then I should not see "rocketchat activity"

  @javascript
  Scenario: Create a rocketchat activity displaying in popup window and click on resulting link
    Given the following "activities" exist:
      | activity | idnumber | name | intro | groupname | displaytype  | course |
      | rocketchat | rocketchat1 | rocketchat activity | description | moodle | 3 |C1     |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "rocketchat activity" "link"
    And I should see "Join Rocket.Chat session"
    And I am on "Course 1" course homepage with editing mode on
    And I open "rocketchat activity" actions menu
    And I choose "Delete" in the open action menu
    And I click on "Yes" "button"
    Then I should not see "rocketchat activity"



