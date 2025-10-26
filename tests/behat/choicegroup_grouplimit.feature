@mod @mod_choicegroup
Feature: Use the choicegroup activity with groups limits
  In order to use choicegroup in a course with groups having limits
  As a teacher
  I need to be able to set group limits

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Vinnie    | Student1 | student1@example.com |
      | student2 | Ann       | Student2 | student2@example.com |
      | teacher1 | Darrell   | Teacher1 | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
      | teacher1 | C1     | editingteacher |
    And the following "groups" exist:
      | name | course | idnumber |
      | A    | C1     | C1G1     |
      | B    | C1     | C1G2     |
      | C    | C1     | C1G3     |
      | D    | C1     | C1G4     |
    And the following "activities" exist:
      | activity    | name           | intro                      | course | idnumber     |
      | choicegroup | Group choice 1 | Group choice 1 for testing | C1     | choicegroup1 |

  @javascript
  Scenario: Set a choicegroup activity to have groups with limits
    Given I am on the "Group choice 1" "choicegroup activity editing" page logged in as teacher1
    And I set the following fields to these values:
      | Limit the number of responses allowed | Enable                   |
      | General limitation                    | 2                        |
    And I set the field "availablegroups" to "A"
    And I press "Add"
    And I set the field "availablegroups" to "B"
    And I press "Add"
    And I press "Apply to all groups"
    And I press "Save and return to course"
    And I am on the "Group choice 1" "choicegroup activity editing" page logged in as teacher1
    Then I should see "A ⦗2⦘"
    # Student view.
    When I am on the "Group choice 1" "choicegroup activity" page logged in as student1
    Then I should see "A"

  @javascript
  Scenario: Change limits in a choicegroup activity with group limits
    Given I am on the "Group choice 1" "choicegroup activity editing" page logged in as teacher1
    And I set the following fields to these values:
      | Limit the number of responses allowed | Enable                   |
      | General limitation                    | 2                        |
    And I set the field "availablegroups" to "A"
    And I press "Add"
    And I set the field "availablegroups" to "B"
    And I press "Add"
    And I press "Apply to all groups"
    And I press "Save and return to course"
    And I am on the "Group choice 1" "choicegroup activity editing" page logged in as teacher1
    And I should see "A ⦗2⦘"
    And I set the field "selectedGroups" to "A ⦗2⦘"
    And I should see "Limit For  A:"
    And I press the tab key
    And I type "3"
    And I press the tab key
    And I press "Save and return to course"
    When I am on the "Group choice 1" "choicegroup activity editing" page logged in as teacher1
    Then I should see "A ⦗3⦘"
