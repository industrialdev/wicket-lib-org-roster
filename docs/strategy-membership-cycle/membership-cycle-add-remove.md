# Adding/Removing (to both trainees and full members membership rosters)

## Add a User to the Roster
* Only those with Membership_Manager role can add users by entering:
    * First Name
    * Last Name
    * Email Address
* The system will:
    * Check if the person already exists in Wicket
        * If not:
            * Create a new user profile
            * Establish selected permissions (from the drop-down list above)
            * Assign to active org membership start date = date added
        * If exists but not linked to org:
            * Assign org membership seat
        * If exists and already has active membership with the org:
            * Display error message: "This person already exists"

## Remove a User
* Clicking "Remove" next to a user:
    * Ends membership assignment for that user with the organization
        * End date to match date of the removal action
    * Does not delete their user profile or impact other roles
    * Membership_owner can't be removed from the roster

## Max seat assignment alert
* If max assignment is hit, display an error message alerting them that they have reached capacity and prompt them to purchase more seats ( ✅ Seat Assignment )

---

## Success Criteria
* Only users with the Membership_Manager role can add or remove individuals from their organization's roster.
* The system handles new and existing users appropriately based on whether they exist in Wicket and have active membership to the org.
* Removing a user ends their org membership assignment (sets end date), but does not delete their profile