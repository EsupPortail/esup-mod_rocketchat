@mod @mod_rocketchat
Feature: mod_rocketchat
  Rocket.Chat remote private group creation retention options

  Background:
    Given the following config values are set as admin:
      | groupnametoformat | moodlebehattest_{$a->courseshortname} | mod_rocketchat |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | John | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |

  @javascript
  Scenario: Create a rocketchat activity and check that no retention options are available
    Given the following config values are set as admin:
      | retentionfeature | 0 | mod_rocketchat |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Rocket.Chat" to section "1"
    And I wait until the page is ready
    Then "#id_retentionenabled" "css_element" should not exist

  @javascript
  Scenario: Create a rocketchat activity and check that retention options are available
    Given the following config values are set as admin:
      | retentionfeature | 1 | mod_rocketchat |
      | retentionenabled | 0 | mod_rocketchat |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Rocket.Chat" to section "1"
    And I wait until the page is ready
    And "retentionenabled" "checkbox" should exist
    And the field "retentionenabled" matches value "0"
    And "overrideglobal" "checkbox" should exist
    And the "overrideglobal" "checkbox" should be disabled
    And "filesonly" "checkbox" should exist
    And the "filesonly" "checkbox" should be disabled
    And "maxage" "field" should exist
    And the "maxage" "field" should be disabled
    When I set the field "retentionenabled" to "checked"
    Then the "overrideglobal" "checkbox" should be enabled
    And the field "overrideglobal" matches value "0"
    And the "filesonly" "checkbox" should be disabled
    And the "excludepinned" "checkbox" should be disabled
    And the "maxage" "field" should be disabled
    When I set the field "overrideglobal" to "checked"
    Then the "filesonly" "checkbox" should be enabled
    And the "excludepinned" "checkbox" should be enabled
    And the "maxage" "field" should be enabled

  @javascript
  Scenario: Edit Rocketchat activity form and check that retention options are visible depending of capabilities
    Given the following config values are set as admin:
      | retentionfeature | 1 | mod_rocketchat |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Rocket.Chat" to section "1"
    And I wait until the page is ready
    And "retentionenabled" "checkbox" should not exist
    And "overrideglobal" "checkbox" should not exist
    And "filesonly" "checkbox" should not exist
    And "maxage" "field" should not exist
    Then I log out
    And I log in as "admin"
    And I set the following system permissions of "Teacher" role:
      | capability | permission |
      | mod/rocketchat:canactivateretentionpolicy | Allow |
    Then I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Rocket.Chat" to section "1"
    And I wait until the page is ready
    And "retentionenabled" "checkbox" should exist
    And "overrideglobal" "checkbox" should not exist
    And "filesonly" "checkbox" should not exist
    And "excludepinned" "checkbox" should not exist
    And "maxage" "field" should not exist
    Then I log out
    And I log in as "admin"
    And I set the following system permissions of "Teacher" role:
      | capability | permission |
      | mod/rocketchat:canactivateretentionglobaloverride | Allow |
    Then I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Rocket.Chat" to section "1"
    And I wait until the page is ready
    And "retentionenabled" "checkbox" should exist
    And "overrideglobal" "checkbox" should exist
    And "filesonly" "checkbox" should not exist
    And "maxage" "field" should not exist
    And "excludepinned" "checkbox" should not exist
    Then I log out
    And I log in as "admin"
    And I set the following system permissions of "Teacher" role:
      | capability | permission |
      | mod/rocketchat:candefineadvancedretentionparamaters | Allow |
    Then I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Rocket.Chat" to section "1"
    And I wait until the page is ready
    And "retentionenabled" "checkbox" should exist
    And "overrideglobal" "checkbox" should exist
    And "filesonly" "checkbox" should exist
    And "maxage" "field" should exist
    And "excludepinned" "checkbox" should exist







