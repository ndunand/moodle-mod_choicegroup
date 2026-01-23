@mod @mod_choicegroup
Feature: Add choicegroup activity
  In order to have students select options from a choicegroup activity
  As a teacher
  I need to add choicegroup activities to courses

  @javascript
  Scenario: Add a choicegroup activity for Moodle ≥ 4.4 for Moodle ≤ 5.0
    Given the site is running Moodle version 4.4 or higher
    And the site is running Moodle version 5.0 or lower
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "groups" exist:
      | name | course | idnumber |
      | A    | C1     | C1G1     |
      | B    | C1     | C1G2     |
    And I log in as "teacher1"
    When I am on "Course 1" course homepage with editing mode on
    And I open the activity chooser
    And I click on "Add a new Group choice" "link" in the "Add an activity or resource" "dialogue"
    Then I should see "New Group choice"

  @javascript
  Scenario: Add a choicegroup activity for Moodle ≥ 5.1
    Given the site is running Moodle version 5.1 or higher
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And the following "groups" exist:
      | name | course | idnumber |
      | A    | C1     | C1G1     |
      | B    | C1     | C1G2     |
    And I log in as "teacher1"
    When I am on "Course 1" course homepage with editing mode on
    And I open the activity chooser
    And I should see "Group choice" in the "Add an activity or resource" "dialogue"
    And I click on "Add a new Group choice" "link" in the "Add an activity or resource" "dialogue"
    And I click on "Add selected activity" "button" in the "Add an activity or resource" "dialogue"
    Then I should see "New Group choice"
