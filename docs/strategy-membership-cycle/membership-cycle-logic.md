# ESCRS Roster Management Requirements

ESCRS roster management differs from our standard implementation. They will manage rosters by membership cycle, and bulk-upload a new roster each year rather than carrying forward existing members. We need to support **two roster screens** tied to the membership period and ensure seat assignments map correctly to the membership record for both trainees and full members rosters.

## Memberships:
* ESCRS Membership National Society Full
* ESCRS Membership National Society Trainee
* Each membership may be:
    * 1-year duration, or
    * 3-year duration (with separate membership records for each start year)

## Applicable Roles:
* Membership_owner and Membership_Manager

---

## Requirements

### 1. Roster Structure
* Each membership record must have its own roster.
* Rosters are tied to Membership ID, not just the organization.
* Membership records may exist concurrently for the same organization.
* During an active calendar year, a National Society may have up to 8 active rosters, representing:
    * Full – 1 Year
    * Full – 3 Year (starting this year)
    * Full – 3 Year (starting last year)
    * Full – 3 Year (starting the previous year)
    * Trainee – 1 Year
    * Trainee – 3 Year (starting this year)
    * Trainee – 3 Year (starting last year)
    * Trainee – 3 Year (starting the previous year)

### 2. Renewal Period Handling
* When renewal opens, National Societies Orgs renew only the memberships ending that year.
* For each renewed membership, the NS selects the number of seats required for the upcoming cycle.
* Renewing creates new membership records, each with an empty roster.
* NS admins then bulk-upload members into each new roster.
    1. During renewal periods:
        * Both current active memberships and newly renewed memberships are visible.
        * **This may result in up to 12 rosters being visible at once (8 active + up to 4 renewed)**

### 3. First-Year Migration Considerations
* Legacy memberships with non-December end dates (e.g., July) will be imported as separate membership records.
* During the first year of migration only, this can result in up to 16 roster screens (December + June end-date variants).
* This behaviour is expected and transitional.

### 4. Cycle Handling
* Each yearly roster upload should be treated as a fresh roster (no assumed carry-forward).
* Roster must be tied to the specific membership ID representing that year's membership cycle.
* Membership records may be:
    * Active
    * Upcoming (renewed but not yet active)
* Roster assignment must respect the membership's active or delayed status.

### 5. Seat Logic
* Seat limits are tied to the Membership ID.
* Seat assignment must validate against the available seats on that membership record.
* Additional seats purchased mid-cycle must be applied to the correct membership record.
* When a roster is imported, the system must assign seats to the correct membership period (active or delayed).

### 6. Display Logic
* Roster UI must clearly indicate which membership record is being managed.
* Each roster should display:
    * Membership tier (Full / Trainee)
    * Duration (1-year / 3-year)
    * Start year (and implicitly end year)
    * Examples:
        * Full – 3 Year (Start 2023)
        * Trainee – 1 Year (2025)
* The UI must not assume a fixed number of rosters.
* **Card Display**

### 7. Data Linking
* Roster = Membership ID (not just Org).
* This ensures:
    * Seat assignment accuracy
    * Correct application of delayed vs active memberships
    * No accidental mix between years

---

## Acceptance Criteria
* Two roster screens exist and switch based on cycle: **[active year]** vs **[following year]**.
* Bulk import assigns roster members to the season.
* Seat validation correctly enforces maximum seats per membership cycle.
* Roster and seat assignments reflect the correct membership year.
* No roster auto-carryover between cycles.