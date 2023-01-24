@mod @mod_choicegroup @mod_choicegroup_groupingrestriction
Feature: In case we have a grouping restriction conflicting choices are not shown in the choicegroup activity.

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
      | name     | course | idnumber |
      | Group A1 | C1     | GGA1     |
      | Group A2 | C1     | GGA2     |
      | Group B1 | C1     | GGB1     |
      | Group B2 | C1     | GGB2     |
    And the following "groupings" exist:
      | name       | course | idnumber |
      | Grouping A | C1     | GA       |
      | Grouping B | C1     | GB       |
      | Grouping 1 | C1     | G1       |
      | Grouping 2 | C1     | G2       |
    And the following "grouping groups" exist:
      | grouping | group   |
      | GA       | GGA1    |
      | GA       | GGA2    |
      | GB       | GGB1    |
      | GB       | GGB2    |
      | G1       | GGA1    |
      | G1       | GGB1    |
      | G2       | GGB2    |
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    # Create a new Group Choice with Group A1 available.
    And I press "Add an activity or resource"
    And I click on "Add a new Group choice" "link" in the "Add an activity or resource" "dialogue"
    And I set the following fields to these values:
      | Group choice name | Choose your group        |
    And I set the field "availablegroups" to "Grouping A"
    And I set the field "availablegroups" to "Group A1"
    And I press "Add Group"
    And I press "Save and return to course"
    And I am on "Course 1" course homepage with editing mode on
    And I press "Add an activity or resource"
    # Create a new Group Choice with Group B1,2 available and restricted.
    And I click on "Add a new Group choice" "link" in the "Add an activity or resource" "dialogue"
    And I click on "collapseElement-4" "link"
    And I set the following fields to these values:
      | Group choice name | Choose second group      |
      | restrictbygrouping| 1                        |
    And I set the field "availablegroups" to "Grouping B"
    And I press "Add Grouping"
    And I set the field "id_selectedgroupings" to "Grouping 1"
    And I set the field "id_restrictchoicesbehaviour" to "Hide group from group list"
    And I press "Save and return to course"
    And I log out

  @javascript
  Scenario: Basic Test - I can still select a choice (normally use the choicegroup activity)
  without the groupingrestriction feature.
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I am on the "Choose your group" "choicegroup activity" page logged in as student1
    And I should see "Group A1" in the ".choicegroups" "css_element"
    And I click on "//table[@class='choicegroups']/tbody/tr[2]/td[1]/input" "xpath_element"
    And I click on "Save my choice" "button"
    And I should see "Your selection: Group A1" in the "#yourselection" "css_element"

  @javascript
  Scenario: Basic Test - I can still select a choice (normally use the choicegroup activity) without the groupingrestriction feature.
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I am on the "Choose your group" "choicegroup activity" page logged in as student1
    And I should see "Group A1" in the ".choicegroups" "css_element"
    And I click on "//table[@class='choicegroups']/tbody/tr[2]/td[1]/input" "xpath_element"
    And I click on "Save my choice" "button"
    And I am on "Course 1" course homepage
    And I am on the "Choose second group" "choicegroup activity" page logged in as student1
    Then I should not see "Group B1" in the ".choicegroups" "css_element"
    And I should see "Group B2" in the ".choicegroups" "css_element"

  @javascript
  Scenario Outline: With two choicegroup activities (one restricted to Groping1 and with hide group limitationbehaviour)
  student1 assigned to A1 can not see B1, student2 who is assigned to GGB1 can see B1 and A1 (as not restircted)
    Given the following "group members" exist:
      | user     | group         |
      | <user>   | <groupassign> |
    # As a student assigned to A1 I do see A1, B2 and NOT B1. As a student assigned to B1 I can see all since
      # 1. mod_choicegroup is not restricted!
    And I log in as "<user>"
    And I am on "Course 1" course homepage
    And I am on the "Choose your group" "choicegroup activity" page logged in as <user>
    And I should <A1visible> "Group A1" in the ".choicegroups" "css_element"
    And I am on the "Choose second group" "choicegroup activity" page logged in as <user>
    And I should <B1visible> "Group B1" in the ".choicegroups" "css_element"
    And I should see "Group B2" in the ".choicegroups" "css_element"

    Examples:
      | user     | groupassign   | B1visible | A1visible  |
      | student1 | GGA1          | not see   | see        |
      | student2 | GGB1          | see       | see        |

  @javascript
  Scenario Outline: Depending on the visibility of the activity different layouts are shown
    Given the following "group members" exist:
      | user       | group   |
      | student1   | GGA1    |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I open "Choose second group" actions menu
    And I click on "Edit settings" "link" in the "Choose second group" activity
    And I click on "collapseElement-4" "link"
    And I set the field "id_restrictchoicesbehaviour" to "<limitationbehaviour>"
    And I press "Save and return to course"
    And I log out
    And I log in as "student1"
    And I am on "Course 1" course homepage
    And I am on the "Choose your group" "choicegroup activity" page logged in as student1
    And I should see "Group A1" in the ".choicegroups" "css_element"
    And I am on "Course 1" course homepage
    And I am on the "Choose second group" "choicegroup activity" page logged in as student1
    And I should see "Group B1" in the ".choicegroups" "css_element"
    And I should see "Group B2" in the ".choicegroups" "css_element"
    And I should <seenotice> "(You are already assigned to a group which contradicts this choice)" in the ".choicegroups" "css_element"

    Examples:
      | limitationbehaviour               | seenotice   |
      | Show group as dimmed              | not see     |
      | Show group with limitation notice | see         |

  @javascript
  Scenario: With a choicegroup activities restricted to a grouping the teacher sees all choices.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    # As a teacher without group assignment I see all choices.
    And I am on the "Choose your group" "choicegroup activity" page logged in as teacher1
    Then I should see "Group A1" in the ".choicegroups" "css_element"
    And I am on the "Choose second group" "choicegroup activity" page logged in as teacher1
    And I should see "Group B1" in the ".choicegroups" "css_element"
    And I should see "Group B2" in the ".choicegroups" "css_element"

  @javascript
  Scenario: Corner Case illegal assignment
    Given the following "group members" exist:
      | user       | group   |
      | student1   | GGA1    |
      | student1   | GGB1    |
    And I log in as "student1"
    And I am on the "Choose your group" "choicegroup activity" page logged in as student1
    Then I should see "Group A1" in the ".choicegroups" "css_element"
    And I am on the "Choose second group" "choicegroup activity" page logged in as student1
    And I should see "Your Group assigment are conflicting. Therefore you can not participate in this groupchoice activity." in the ".choicegrouprestrictionerror" "css_element"
