@mod @mod_choicegroup
Feature: Minimum enrollment limit for choicegroup activity
  In order to ensure students enroll in a minimum number of groups
  As a teacher
  I need to be able to set a minimum enrollment limit on a multiple-enrollment choicegroup

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Vinnie    | Student1 | student1@example.com |
      | teacher1 | Darrell   | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "groups" exist:
      | name | course | idnumber |
      | A    | C1     | C1G1     |
      | B    | C1     | C1G2     |
      | C    | C1     | C1G3     |
    And the following "activities" exist:
      | activity    | name           | intro                      | course | idnumber     |
      | choicegroup | Group choice 1 | Group choice 1 for testing | C1     | choicegroup1 |

  @javascript
  Scenario: Teacher can configure a minimum enrollment limit and the value is saved
    Given I am on the "Group choice 1" "choicegroup activity editing" page logged in as teacher1
    When I check "Allow enrollment to multiple groups"
    And I set the field "Min. enrollments" to "2"
    And I set the field "availablegroups" to "A"
    And I press "Add"
    And I set the field "availablegroups" to "B"
    And I press "Add"
    And I set the field "availablegroups" to "C"
    And I press "Add"
    And I press "Save and return to course"
    And I am on the "Group choice 1" "choicegroup activity editing" page logged in as teacher1
    Then the field "Min. enrollments" matches value "2"

  @javascript
  Scenario: Validation error when minimum enrollment exceeds maximum enrollment
    Given I am on the "Group choice 1" "choicegroup activity editing" page logged in as teacher1
    When I check "Allow enrollment to multiple groups"
    And I set the field "Max. enrollments" to "2"
    And I set the field "Min. enrollments" to "3"
    And I set the field "availablegroups" to "A"
    And I press "Add"
    And I press "Save and return to course"
    Then I should see "Minimum enrollments cannot be greater than maximum enrollments"

  @javascript
  Scenario: Student sees info message when only a minimum enrollment limit is set
    Given I am on the "Group choice 1" "choicegroup activity editing" page logged in as teacher1
    And I check "Allow enrollment to multiple groups"
    And I set the field "Min. enrollments" to "2"
    And I set the field "availablegroups" to "A"
    And I press "Add"
    And I set the field "availablegroups" to "B"
    And I press "Add"
    And I set the field "availablegroups" to "C"
    And I press "Add"
    And I press "Save and return to course"
    When I am on the "Group choice 1" "choicegroup activity" page logged in as student1
    Then I should see "You must enroll in at least 2 groups"

  @javascript
  Scenario: Student sees range info message when minimum and maximum are different
    Given I am on the "Group choice 1" "choicegroup activity editing" page logged in as teacher1
    And I check "Allow enrollment to multiple groups"
    And I set the field "Min. enrollments" to "2"
    And I set the field "Max. enrollments" to "3"
    And I set the field "availablegroups" to "A"
    And I press "Add"
    And I set the field "availablegroups" to "B"
    And I press "Add"
    And I set the field "availablegroups" to "C"
    And I press "Add"
    And I press "Save and return to course"
    When I am on the "Group choice 1" "choicegroup activity" page logged in as student1
    Then I should see "You must enroll in between 2 and 3 groups"

  @javascript
  Scenario: Student sees exact enrollment message when minimum equals maximum
    Given I am on the "Group choice 1" "choicegroup activity editing" page logged in as teacher1
    And I check "Allow enrollment to multiple groups"
    And I set the field "Min. enrollments" to "2"
    And I set the field "Max. enrollments" to "2"
    And I set the field "availablegroups" to "A"
    And I press "Add"
    And I set the field "availablegroups" to "B"
    And I press "Add"
    And I set the field "availablegroups" to "C"
    And I press "Add"
    And I press "Save and return to course"
    When I am on the "Group choice 1" "choicegroup activity" page logged in as student1
    Then I should see "You must enroll in 2 groups"

  @javascript
  Scenario: Student sees info message when only a maximum enrollment limit is set
    Given I am on the "Group choice 1" "choicegroup activity editing" page logged in as teacher1
    And I check "Allow enrollment to multiple groups"
    And I set the field "Max. enrollments" to "3"
    And I set the field "availablegroups" to "A"
    And I press "Add"
    And I set the field "availablegroups" to "B"
    And I press "Add"
    And I set the field "availablegroups" to "C"
    And I press "Add"
    And I press "Save and return to course"
    When I am on the "Group choice 1" "choicegroup activity" page logged in as student1
    Then I should see "You can enroll in up to 3 groups"
