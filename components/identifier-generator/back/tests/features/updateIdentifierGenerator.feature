@acceptance-back
Feature: Update Identifier Generator

  Background:
    Given the sku identifier attribute
    And the name text attribute
    And the default identifier generator

  Scenario: Can update a valid identifier generator
    When I update the identifier generator
    Then The identifier generator is updated in the repository
    And I should not get any error

  Scenario: Cannot update an unknown identifier generator
    When I try to update an unknown identifier generator
    Then I should get an exception message 'Identifier generator "unknown" does not exist or you do not have permission to access it.'

  Scenario: Cannot update an identifier with non identifier target
    When I try to update an identifier generator with target 'name'
    Then I should get an error message 'target: The "name" attribute code is "pim_catalog_text" type and should be of type identifier'

  Scenario: Cannot update an identifier with not existing target
    When I try to update an identifier generator with target 'toto'
    Then I should get an error message 'target: The "toto" attribute code given as target does not exist'

  Scenario: Cannot update an identifier generator if property does not exist
    When I try to update an identifier generator with an unknown property
    Then I should get an error message 'structure[0][type]: Type "unknown" can only be one of the following: "free_text", "auto_number"'
