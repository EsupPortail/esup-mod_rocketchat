@mod @mod_rocketchat
Feature: mod_rocketchat
  Rocket.Chat activity block link

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
  Scenario: Create a rocketchat activity and click on resulting link
    Given the following "activities" exist:
      | activity | idnumber | name | intro | groupname | course |
      | rocketchat | rocketchat1 | rocketchat activity | description | moodle |C1     |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add the "Activities" block
    And I click on "Rocket.Chat instances" "link" in the "Activities" "block"
    And I should see "rocketchat activity"
    And I click on "rocketchat activity" "link" in the "0" "table_row"
    And I should see "Join Rocket.Chat session"
