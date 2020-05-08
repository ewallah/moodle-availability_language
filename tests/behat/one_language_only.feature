@ewallah @availability @availability_language @javascript
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
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  Scenario: Only one language pack installed
    When I am on the "C1" "Course" page logged in as "teacher1"
    And I turn editing mode on
    And I add a "Page" to section "1"
    And I set the following fields to these values:
      | Name         | P1 |
      | Description  | x  |
      | Page content | x  |
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Language" "button" should not exist in the "Add restriction..." "dialogue"
