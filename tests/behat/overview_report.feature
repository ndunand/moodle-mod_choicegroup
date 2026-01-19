@mod @mod_choicegroup
Feature: Testing overview integration in choicegroup activity
  In order to summarize the choicegroup activity
  As a user
  I need to be able to see the choicegroup activity overview

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
  Scenario: The choicegroup activity overview report should generate log events
    Given the site is running Moodle version 5.0 or higher
    And I am on the "Course 1" "course > activities > choicegroup" page logged in as "teacher1"
    When I am on the "Course 1" "course" page logged in as "teacher1"
    And I navigate to "Reports" in current page administration
    And I click on "Logs" "link"
    And I click on "Get these logs" "button"
    Then I should see "Course activities overview page viewed"
    And I should see "viewed the instance list for the module 'choicegroup'"

  @javascript
  Scenario: The choicegroup activity index redirect to the activities overview
    Given the site is running Moodle version 5.0 or higher
    And I am on the "Group choice 1" "choicegroup activity editing" page logged in as teacher1
    And I set the field "availablegroups" to "A"
    And I press "Add"
    And I set the field "availablegroups" to "B"
    And I press "Add"
    And I press "Save and return to course"
    And I am on the "Group choice 1" "choicegroup activity editing" page logged in as teacher1
    And I expand all fieldsets
    And I click on "Restrict answering to this time period" "checkbox"
    And I press "Save and return to course"
    When I am on the "C1" "course > activities > choicegroup" page logged in as "admin"
    Then I should see "Name" in the "choicegroup_overview_collapsible" "region"
    And I should see "Choice begins at" in the "choicegroup_overview_collapsible" "region"
    And I should see "Choice ends at" in the "choicegroup_overview_collapsible" "region"
    And I should see "Choices" in the "choicegroup_overview_collapsible" "region"
