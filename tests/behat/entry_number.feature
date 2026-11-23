@mod @mod_data @datafield @datafield_autonumber
Feature: Entries get a number when they are saved
  In order to refer to entries by number
  As a teacher
  I need each new entry to get the next number

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | 1        |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
    And the following "activities" exist:
      | activity | name     | course | idnumber |
      | data     | Requests | C1     | data1    |
    And the following "mod_data > fields" exist:
      | database | type       | name    | description         | param1 | param2 |
      | data1    | text       | Request | What do you need?   |        |        |
      | data1    | autonumber | Number  | Given automatically | REQ-   | 4      |

  @javascript
  Scenario: A student adds two entries and each gets the next number
    When I am on the "Requests" "data activity" page logged in as student1
    And I press "Add entry"
    Then I should see "Given when the entry is saved"
    And I set the field "Request" to "Projector in room 4 is broken"
    And I press "saveandview"
    And I should see "REQ-0001"
    And I am on the "Requests" "data activity" page
    And I press "Add entry"
    And I set the field "Request" to "Need a room on Friday"
    And I press "saveandview"
    And I should see "REQ-0002"
    And I am on the "data1" Activity page
    And I should see "REQ-0001"
    And I should see "REQ-0002"
