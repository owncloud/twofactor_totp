@webUI @insulated @enforce-2fa
Feature: Enrol a TOTP app from the verification page when two-factor auth is enforced
  As a user who has never configured TOTP
  I want to be shown both the QR code and the secret on the verification page
  So that I can enrol my TOTP app even if I am not able to scan a QR code

  When two-factor auth is enforced, a user who has not configured TOTP yet is
  sent straight to the verification page and cannot reach the personal security
  settings page. The verification page is therefore the only place where such a
  user can enrol, and it has to offer the secret in text form as well - not
  every user is able to scan a QR code.

  Background:
    Given user "Alice" has been created with default attributes and without skeleton files
    And using OCS API version "2"
    # the admin group has to stay excluded, otherwise the enforcement applies to
    # the administrator as well and every admin request the test framework makes
    # is answered with 401 Unauthorised
    And the administrator has added config key "enforce_2fa_excluded_groups" with value '["admin"]' in app "core"
    And the administrator has added config key "enforce_2fa" with value "yes" in app "core"
    And the user has browsed to the login page


  Scenario: The QR code and the secret are both shown on the verification page
    When user "Alice" logs in using the webUI after a redirect from the "verification" page
    Then the secret code from the QR code should match the one displayed on the verification page


  Scenario: A user who cannot scan the QR code can enrol using the displayed secret
    When user "Alice" logs in using the webUI after a redirect from the "verification" page
    And the user adds one-time key generated from the secret displayed on the verification page
    Then the user should be redirected to a webUI page with the title "Files - %productname%"


  # the "has logged in ... after a redirect from" step cannot be used to arrange
  # this: it asserts that the login ended up on the Files page, which by
  # definition it does not when the user is redirected to the challenge page
  Scenario: The secret is no longer disclosed once the user has enrolled
    Given user "Alice" logs in using the webUI after a redirect from the "verification" page
    And the user adds one-time key generated from the secret displayed on the verification page
    And the user should be redirected to a webUI page with the title "Files - %productname%"
    When the user re-logs in as "Alice" to the two-factor authentication verification page
    Then the enrolment secret should not be displayed on the verification page
