@mod @mod_rocketchat
Feature: mod_rocketchat
  Rocket.Chat admin test connection page

  @javascript
  Scenario: Test conneciton trhough Rocket.Chat admin page
    Given I log in as "admin"
    And I navigate to "Plugins > Activity modules > Rocket.Chat > Test connection to Rocket.Chat" in site administration
    Then I should see "Connection succesfully establish"





