@webUI
Feature: Use the CLI to delete secrets for users
  As an admin
  I want to be able to use the CLI to delete secrets for users
  So that I can manage OTP for users

  Note: this feature is testing the CLI, but needs webUI steps for scenario setup in "given" steps

  Background:
    Given these users have been created with default attributes and large skeleton files:
      | username |
      | Alice    |
      | new-user |
    And using OCS API version "2"

  Scenario: Delete secret for a user having no secret
    When the administrator invokes occ command "twofactor_totp:delete-secret new-user"
    Then the command should have been successful
    And the command output should contain the text "0 secrets deleted for new-user"
    And user "new-user" should be able to access a skeleton file

  Scenario: Delete secret for a user that has a secret
    Given user "Alice" has logged in using the webUI
    And the user has browsed to the personal security settings page
    And the user has activated TOTP Second-factor auth but not verified
    And the user adds one-time key generated from the secret key using the webUI
    When the administrator invokes occ command "twofactor_totp:delete-secret Alice"
    Then the command should have been successful
    And the command output should contain the text "1 secrets deleted for Alice"
    And user "Alice" using password "%regularuser%" should not be able to download file "textfile0.txt"

  Scenario: Delete secrets for mutiple users
    Given user "Alice" has logged in using the webUI
    And the user has browsed to the personal security settings page
    And the user has activated TOTP Second-factor auth but not verified
    And the user adds one-time key generated from the secret key using the webUI
    And the user logs out of the webUI
    And user "new-user" has logged in using the webUI
    And the user has browsed to the personal security settings page
    And the user has activated TOTP Second-factor auth but not verified
    And the user adds one-time key generated from the secret key using the webUI
    And the user logs out of the webUI
    When the administrator invokes occ command "twofactor_totp:delete-secret Alice new-user"
    Then the command should have been successful
    And the command output should contain the text "1 secrets deleted for Alice"
    And the command output should contain the text "1 secrets deleted for new-user"
    And user "Alice" using password "%regularuser%" should not be able to download file "textfile0.txt"
    And user "new-user" using password "%regularuser%" should not be able to download file "textfile0.txt"

  Scenario: Delete secrets for all users
    Given user "Alice" has logged in using the webUI
    And the user has browsed to the personal security settings page
    And the user has activated TOTP Second-factor auth but not verified
    And the user adds one-time key generated from the secret key using the webUI
    And the user logs out of the webUI
    And user "new-user" has logged in using the webUI
    And the user has browsed to the personal security settings page
    And the user has activated TOTP Second-factor auth but not verified
    And the user adds one-time key generated from the secret key using the webUI
    And the user logs out of the webUI
    When the administrator invokes occ command "twofactor_totp:delete-secret --all"
    Then the command should have been successful
    And the command output should contain the text "2 secrets deleted"
    And user "Alice" using password "%regularuser%" should not be able to download file "textfile0.txt"
    And user "new-user" using password "%regularuser%" should not be able to download file "textfile0.txt"
