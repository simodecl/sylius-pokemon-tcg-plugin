@pokemon_tcg
Feature: Accessing the Pokemon TCG dashboard
    In order to manage my Pokemon TCG catalog
    As an Administrator
    I want to see the Pokemon TCG dashboard

    Background:
        Given I am logged in as an administrator

    @ui
    Scenario: Viewing the Pokemon TCG dashboard
        When I visit the Pokemon TCG dashboard page
        Then I should see "Pokemon TCG Manager"
