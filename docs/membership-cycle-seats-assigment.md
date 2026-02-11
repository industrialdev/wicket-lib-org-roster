# Seat Assignment

## Requirements

### 1. Add "Purchase Additional Seats" Button
* Display on the roster screen for the relevant membership record.
* Button should redirect to a seat purchase form (Gravity Form - IS to configure)

### 2. Seat Purchase Form (IS to Implement)
* Form must receive and store the membership ID for the current season (passed as parameter).
* Organization user enters number of additional seats they wish to purchase.
* On form submission → redirect to WooCommerce checkout with product quantity = number of seats requested.

### 3. Checkout + Seat Increase Logic
* When order moves to Processing /Complete run script that:
    * Increases the seat allocation on that specific membership ID by quantity purchased.
    * Does not affect seats for any other membership cycle (no global seat increase).

### 4. Membership Season Awareness
* Seat assignment must validate against the available seats on that membership record.
* Additional seats purchased mid-cycle must be applied to the correct membership record.
* When a roster is imported, seats are consumed from the corresponding membership record only.

### 5. Max seat assignment alert
* If max assignment is hit, display an error message alerting them that they have reached capacity and prompt them to purchase more seats

---

## Acceptance Criteria
* "Purchase Additional Seats" button is visible on the roster screen per membership record.
* Seat purchase form receives and passes the correct membership ID.
* Seat increase script updates only that membership record’s seat allocation.
* Seats update immediately after successful checkout.
* Roster UI immediately reflects new seat total.