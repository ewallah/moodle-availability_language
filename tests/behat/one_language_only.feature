@ewallah @availability @availability_language
Feature: one language only availability_language
  When there is only one language installed
  As a teacher
  I cannot use a language condition to prevent student access

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

  @javascript
  Scenario: Only one language pack installed
    When I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Page" to section "1"
    And I set the following fields to these values:
      | Name         | P1 |
      | Description  | x  |
      | Page content | x  |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Language" "button" should not exist in the "Add restriction..." "dialogue"
    And I click on "Cancel" "button" in the "Add restriction..." "dialogue"
    And I log out
