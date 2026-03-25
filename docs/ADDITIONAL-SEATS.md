# Additional Seats

This document defines how the additional-seats purchase flow works in `wicket-lib-org-roster`, how to configure it, and how to set up the required WooCommerce products.

## Purpose

The additional-seats flow allows an authorized organization manager to buy more seats mid-cycle from the organization management area.

The flow updates:
- Organization seat count in membership post meta (`org_seats`)
- Subscription seat limit meta (`seat_limit`)
- Subscription line item quantity for the membership seat product
- Subscription line item quantity for the discount seat product
- MDP `max_assignments` via API

## Required Integrations

- WooCommerce
- Gravity Forms
- Wicket API client (`wicket_api_client`) for MDP updates

## Required Products

Configure these products in WooCommerce using SKUs.

1. Additional Seats purchase product
- Config key: `integrations.additional_seats.sku`
- Default: `additional-seats`
- Site-level fallback in service: also checks `corporate-seats`

2. Discount Seats subscription product
- Config key: `integrations.additional_seats.discount_sku`
- Default: `corporate-seat-discount`
- Used to offset renewal pricing based on purchased additional seats

Important: SKU is the only product marker for this flow. Do not rely on static product IDs.

## Configuration Keys

Path: `integrations.additional_seats`

- `enabled` (bool)
- `sku` (string): additional seats product SKU
- `discount_sku` (string): discount seats product SKU
- `form_id` (int)
- `form_slug` (string)
- `min_quantity` (int)
- `max_quantity` (int)

Example override in theme config filter:

```php
$config['integrations']['additional_seats']['enabled'] = true;
$config['integrations']['additional_seats']['sku'] = 'additional-seats';
$config['integrations']['additional_seats']['discount_sku'] = 'corporate-seat-discount';
$config['integrations']['additional_seats']['form_slug'] = 'additional-seats';
$config['integrations']['additional_seats']['min_quantity'] = 1;
$config['integrations']['additional_seats']['max_quantity'] = 900;
```

## End-to-End Runtime Flow

1. User submits Gravity Form for additional seats.
2. Form handler stores purchase context and adds additional-seats product to cart.
3. User completes checkout.
4. Order status hooks trigger additional-seats order processing.
5. Handler resolves membership context (`membership_post_id`, `subscription_id`, additional seat quantity).
6. Handler updates seat quantities:
- Membership seat product line item is set to new total seats.
- Discount seat product line item is incremented by seats purchased in this order.
- If discount line item is missing, it is added to the subscription with the purchased quantity.
7. Handler recalculates/saves subscription totals.
8. Handler updates membership meta and MDP max assignments.
9. Handler marks order as processed (`additional_seats_processed`) to prevent double-processing.

## Subscription Quantity Rules

Given:
- Existing seat quantity = `current_seats`
- Newly purchased seats = `additional_seats`

Then:
- Membership seat product quantity = `current_seats + additional_seats`
- Discount seat product quantity = existing discount quantity + `additional_seats`

If no discount product exists on subscription, create it with quantity `additional_seats`.

## Authorization

Purchase permissions are checked with org management permissions:
- Roles configured in `access.permissions.purchase_seat_roles`
- Default roles typically include membership owner/manager/editor

## Operational Notes

- Handler runs on multiple WooCommerce order lifecycle hooks and is idempotent using order meta (`additional_seats_processed`).
- Product resolution is WPML-aware and resolves translated products when available.
- If discount SKU cannot be resolved, seat update continues and a warning is logged.
- Purchase form URL generation fails closed when purchase context user-meta cannot be persisted.
- WordPress administrators see an in-page setup warning when additional-seats prerequisites are incomplete (missing purchasable SKU product, form mapping, or `supplemental-members` page).

## Validation Checklist (Implementation Teams)

1. Confirm both SKUs exist and are purchasable.
2. Confirm additional-seats form is mapped and reachable.
3. Submit a test purchase for quantity > 0.
4. Confirm subscription updates:
- Seat line item reflects new total seats.
- Discount line item increments by purchased quantity (or is added if missing).
5. Confirm `seat_limit` subscription meta and `org_seats` membership meta updated.
6. Confirm MDP membership `max_assignments` updated.
7. Confirm order has `additional_seats_processed = true`.

## Troubleshooting

If discount quantity does not move:
- Verify `integrations.additional_seats.discount_sku` matches the WooCommerce product SKU exactly.
- Verify discount product is purchasable.
- Check WooCommerce logs for `source = wicket-orgman` around additional-seats processing.

If order does not process:
- Verify order contains the additional seats product SKU.
- Verify required membership context is present (`membership_post_id_renew` or membership lookup data).
- Verify WooCommerce order status hook execution and no early return due to `additional_seats_processed`.

## Implementation Team Setup (WP-Admin Only)

This section is for implementation teams who configure sites in WooCommerce and WordPress admin only (no code changes).

### What must exist before go-live

1. Additional Seats product in WooCommerce
- Product type can be the same type used by your site for seat purchases.
- SKU must match the configured additional seats SKU (commonly `additional-seats`).
- Product must be purchasable and visible to the checkout flow.

2. Discount Seats product in WooCommerce
- SKU must match the configured discount SKU (commonly `corporate-seat-discount`).
- Product must be purchasable.
- This product is used on the subscription to offset renewal pricing for purchased extra seats.

3. Additional Seats Gravity Form
- The form used for seat purchases must be published and reachable from the supplemental members flow.
- It must collect:
  - Organization identifier (`org_uuid`)
  - Membership identifier (`membership_id` or `membership_uuid`)
  - Quantity (seat count)

4. WooCommerce Subscriptions active
- The customer must have an active subscription containing the seat product line item.

5. Supplemental members account page
- A `my-account` post with slug `supplemental-members` must exist.
- It should host the additional seats form route for checkout entry.

### Pre-launch admin checklist

1. Open both products in WooCommerce and confirm SKUs are exact (no typos, no trailing spaces).
2. Confirm both products are purchasable.
3. Confirm checkout works for a test user account.
4. Confirm the test user can access Organization Management and the add-seats form.

### Post-purchase validation in WP-Admin

After a test purchase of N additional seats:

1. Open the order in WooCommerce.
- Confirm order includes the Additional Seats product and quantity N.

2. Open the related subscription in WooCommerce.
- Confirm seat product quantity increased to total seats.
- Confirm discount product quantity increased by N.
- If the discount line was missing before purchase, confirm it was added with quantity N.

3. Confirm order processing marker.
- In order custom fields/meta, confirm `additional_seats_processed` is present/true.

### Common setup mistakes

1. Discount SKU does not match product SKU exactly.
2. Discount product exists but is not purchasable.
3. Additional seats form is mapped incorrectly or missing membership/org context fields.
4. Testing done on a user/subscription that is not linked to the expected membership record.
