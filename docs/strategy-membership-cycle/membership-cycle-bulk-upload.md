# Bulk Upload

## Criteria
* Will import users on bulk into a roster
* Only available to users with membership_manager role
* Uploads a CSV template including:
    * First Name
    * Last Name
    * Email Address
    * Membership:
        * ESCRS Membership National Society Full
        * ESCRS Membership National Society Trainee

## Restrictions:
* The import must not allow assignment of any other memberships type besides the 2 specified above
* If a membership type apart from the ones listed is detected, the system must:
    * Ignore/Reject the row and not include the record in the import
* The upload only adds users and does not duplicate. Removals must be done manually

## Implementation Notes
* The roster must be linked to a specific membership ID so that the bulk importer knows which membership period to upload those users to
* Could leverage the same importer template Rob used for AFAIK

## Success Criteria
Only users with the Membership_Manager role can access the bulk import tool.
The system must only accept the two memberships as valid.
Any invalid relationship type is skipped
Import only adds users to the roster; no deletions are performed.