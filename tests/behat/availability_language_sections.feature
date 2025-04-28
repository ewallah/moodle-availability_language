@ewallah @availability @availability_language
Feature: availability_language sections
  In order to control student access to sections
  As a admin
  I need to add language
  As a teacher
  I need to set language conditions which prevent student access

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format | numsections |
      | Course 1 | C1        | weeks  | 5           |
    And the following "users" exist:
      | username |
      | teacher1 |
      | student1 |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And I log in as "admin"
    And I navigate to "Language > Language packs" in site administration
    And I set the field "Available language packs" to "fr"
    And I press "Install selected language pack(s)"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: Restrict sections based on language
    # Section1 for English users only hidden.
    When I edit the section "1"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Language" "button" should exist in the "Add restriction..." "dialogue"
    And I click on "Language" "button" in the "Add restriction..." "dialogue"
    Then I should see "Please set" in the "region-main" "region"
    And I set the field "Language" to "en"
    Then I should not see "Please set" in the "region-main" "region"
    And I click on "Save changes" "button"

    # Section2 for English users only.
    And I am on "Course 1" course homepage with editing mode on
    When I edit the section "2"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Language" "button" should exist in the "Add restriction..." "dialogue"
    And I click on "Language" "button" in the "Add restriction..." "dialogue"
    And I set the field "Language" to "en"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I click on "Save changes" "button"

    # Section3 for French users only hidden.
    And I am on "Course 1" course homepage with editing mode on
    When I edit the section "3"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Language" "button" should exist in the "Add restriction..." "dialogue"
    And I click on "Language" "button" in the "Add restriction..." "dialogue"
    And I set the field "Language" to "fr"
    And I click on "Save changes" "button"

    # Section4 for French users only.
    And I am on "Course 1" course homepage with editing mode on
    When I edit the section "4"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Language" "button" should exist in the "Add restriction..." "dialogue"
    And I click on "Language" "button" in the "Add restriction..." "dialogue"
    And I set the field "Language" to "fr"
    And I click on ".availability-item .availability-eye img" "css_element"
    And I click on "Save changes" "button"

    # Log in as student.
    When I log out
    And I am on the "C1" "Course" page logged in as "student1"
    Then I should see "Current week" in the "region-main" "region"
    And I follow "Preferences" in the user menu
    And I follow "Preferred language"
    And I set the field "lang" to "fr"
    And I click on "Save changes" "button"
    And I am on "Course 1" course homepage
    But I should not see "Current week" in the "region-main" "region"

  @javascript
  Scenario: Restrict section0 visible based on language
    When I edit the section "0"
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    Then "Language" "button" should exist in the "Add restriction..." "dialogue"
