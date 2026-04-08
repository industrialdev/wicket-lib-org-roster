---
critical_doc_contract: true
critical_section_start: "<!-- CRITICAL:ADDITIONAL_SEATS_START -->"
critical_section_end: "<!-- CRITICAL:ADDITIONAL_SEATS_END -->"
required_items:
  fail_closed_purchase_context: "Purchase form URL generation fails closed when purchase context user-meta cannot be persisted."
  admin_setup_warning: "WordPress administrators see an in-page setup warning when additional-seats prerequisites are incomplete (missing purchasable SKU product, form mapping, or `supplemental-members` page)."
---

# Additional Seats

Canonical reference: `docs/product/ADDITIONAL-SEATS.md`

This file exists as a stable compatibility path for QA regression checks and tooling that still resolves `docs/ADDITIONAL-SEATS.md`.
Critical contract: keep the YAML front matter and the marked critical section unchanged unless the matching QA test contract is intentionally updated.

<!-- CRITICAL:ADDITIONAL_SEATS_START -->
Purchase form URL generation fails closed when purchase context user-meta cannot be persisted.
WordPress administrators see an in-page setup warning when additional-seats prerequisites are incomplete (missing purchasable SKU product, form mapping, or `supplemental-members` page).
<!-- CRITICAL:ADDITIONAL_SEATS_END -->
