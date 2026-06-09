@mod @mod_optiksinav
Feature: Take an optical exam
  In order to assess students with PDF booklet + bubble-sheet exams
  As a teacher and a student
  I need to create an exam with an answer key and have students attempt it

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "users" exist:
      | username | firstname | lastname |
      | teacher1 | Teacher   | One      |
      | student1 | Student   | One      |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    And the following "activity" exists:
      | activity      | optiksinav |
      | course        | C1         |
      | name          | Midterm    |
      | questioncount | 3          |
      | optioncount   | 5          |

  Scenario: Teacher sees the setup checklist before the exam is ready
    Given I am on the "Midterm" "optiksinav activity" page logged in as teacher1
    Then I should see "Setup status"
    And I should see "Question booklet uploaded"
    And I should see "Answer key complete"
    And I should see "Edit answer key"

  Scenario: Teacher fills in the answer key
    Given I am on the "Midterm" "optiksinav activity" page logged in as teacher1
    When I follow "Edit answer key"
    And I set the field "Question 1" to "A"
    And I set the field "Question 2" to "B"
    And I set the field "Question 3" to "C"
    And I press "Save changes"
    Then I should see "Answer key saved"
    And I should see "Answer key complete (3/3)"

  Scenario: Student without a started attempt sees the start button
    Given I am on the "Midterm" "optiksinav activity" page logged in as student1
    Then I should see "Start exam"
