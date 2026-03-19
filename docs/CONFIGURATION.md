# Configuration

Current runtime source of truth: `src/Config/OrgManConfig.php`

This document defines the proposed canonical configuration schema for the library and maps every current config area into that schema.

Use `docs/INSTALLATION.md` for setup wiring.

## Status

- The runtime still reads the current legacy-shaped config array in `OrgManConfig`.
- This document defines the target schema we should move to.
- Breaking key renames are acceptable.
- Every moved or renamed path below includes a migration destination so downstream site configs can be updated later.

## Canonical Top-Level Categories

- `access`
- `membership`
- `relationships`
- `member_management`
- `groups`
- `removal`
- `presentation`
- `integrations`
- `platform`

## Canonical Schema

### `access`

#### `access.roles`

- `access.roles.owner`
  - Default: `membership_owner`
  - Canonical owner role slug.
- `access.roles.manager`
  - Default: `membership_manager`
  - Canonical manager role slug.
- `access.roles.editor`
  - Default: `org_editor`
  - Canonical editor role slug.
- `access.roles.aliases`
  - Default: `[]`
  - Optional role normalization map.
- `access.roles.labels.membership_manager`
  - Default: `Membership Manager`
- `access.roles.labels.org_editor`
  - Default: `Org. Editor`
- `access.roles.labels.membership_owner`
  - Default: `Membership Owner`
- `access.roles.labels.*`
  - Extra display labels for any role slug surfaced in the UI.
- `access.roles.descriptions.*`
  - Optional descriptions shown alongside role checkboxes in Add Member and Edit Permissions modals.
  - Useful for explaining what each role does (e.g., `'org_editor' => 'Ability to edit Organization\'s profile'`).
  - The description appears in lighter grey text next to the role label.

#### `access.permissions`

- `access.permissions.organization_edit_roles`
  - Default: `['org_editor']`
- `access.permissions.manage_member_roles`
  - Default: `['membership_manager', 'membership_owner']`
- `access.permissions.add_member_roles`
  - Default: `['membership_manager', 'membership_owner']`
- `access.permissions.remove_member_roles`
  - Default: `['membership_manager', 'membership_owner']`
- `access.permissions.purchase_seat_roles`
  - Default: `['membership_owner', 'membership_manager', 'org_editor']`
- `access.permissions.any_management_roles`
  - Default: `['org_editor', 'membership_manager', 'membership_owner']`
- `access.permissions.prevent_owner_removal`
  - Default: `false`
- `access.permissions.prevent_owner_assignment`
  - Default: `true`
- `access.permissions.relationship_grants.enabled`
  - Default: `false`
  - Enables permission grants based on relationship type.
- `access.permissions.relationship_grants.roles_by_type.ceo`
  - Default: `['org_editor', 'membership_manager']`
- `access.permissions.relationship_grants.roles_by_type.primary_hr_contact`
  - Default: `['org_editor', 'membership_manager']`
- `access.permissions.relationship_grants.roles_by_type.member_contact`
  - Default: `['org_editor', 'membership_manager']`
- `access.permissions.relationship_grants.roles_by_type.employee_staff`
  - Default: `[]`
- `access.permissions.relationship_grants.roles_by_type.advertising_sponsor_contact`
  - Default: `[]`
- `access.permissions.relationship_grants.roles_by_type.advertising_sponsor_billing`
  - Default: `[]`
- `access.permissions.relationship_grants.roles_by_type.*`
  - Role grants keyed by relationship type slug.
- `access.permissions.role_only_management_access.enabled`
  - Default: `false`
- `access.permissions.role_only_management_access.allowed_roles`
  - Default: `['membership_owner']`

### `membership`

- `membership.strategy`
  - Default: `direct`
  - Allowed values: `direct`, `cascade`, `groups`, `membership_cycle`
- `membership.resolution.prefer_current_cycle`
  - Default: `false`
  - Current path lives under `feature_flags`, but conceptually belongs here.

#### `membership.cycle`

- `membership.cycle.key`
  - Default: `membership_cycle`
  - Internal strategy identifier.
- `membership.cycle.permissions.add_member_roles`
  - Default: `['membership_manager']`
- `membership.cycle.permissions.remove_member_roles`
  - Default: `['membership_manager']`
- `membership.cycle.permissions.purchase_seat_roles`
  - Default: `['membership_owner', 'membership_manager', 'org_editor']`
- `membership.cycle.prevent_owner_removal`
  - Default: `true`
- `membership.cycle.require_explicit_membership_uuid`
  - Default: `true`
- `membership.cycle.removal.end_date_anchor`
  - Default: inherits `removal.end_date_anchor`
  - Allowed: `action_time`, `day_start_utc`

#### `membership.seat_limits`

- `membership.seat_limits.tier_max_assignments`
  - Default: `[]`
  - Optional seat-cap overrides keyed by membership tier name.
- `membership.seat_limits.tier_name_case_sensitive`
  - Default: `false`

### `relationships`

#### `relationships.defaults`

- `relationships.defaults.type`
  - Default: `Position`
- `relationships.addition.type`
  - Default: `position`

#### `relationships.removal`

- `relationships.removal.end_date_anchor`
  - Default: inherits `removal.end_date_anchor`
  - Allowed: `action_time`, `day_start_utc`

#### `relationships.filters`

- `relationships.filters.allowlist`
  - Default: `[]`
- `relationships.filters.denylist`
  - Default: `[]`

#### `relationships.display`

- `relationships.display.member_card_active_only`
  - Default: `false`

#### `relationships.labels`

- `relationships.labels.custom.ceo`
  - Default: `CEO`
- `relationships.labels.custom.primary_hr_contact`
  - Default: `Primary HR Contact`
- `relationships.labels.custom.employee_staff`
  - Default: `Employee`
- `relationships.labels.custom.member_contact`
  - Default: `Member Contact`
- `relationships.labels.special.advertising_sponsor_contact`
  - Default: `Advertising/Sponsor Contact`
- `relationships.labels.special.advertising_sponsor_billing`
  - Default: `Advertising/Sponsor Billing Contact`
- `relationships.labels.custom.*`
  - Additional labels for standard or site-defined relationship types.
- `relationships.labels.special.*`
  - Labels for special relationship types.

### `member_management`

#### `member_management.addition`

- `member_management.addition.auto_assign_roles`
  - Default: `[]`
- `member_management.addition.base_member_role`
  - Default: `member`
- `member_management.addition.repair_stale_relationship_without_membership`
  - Default: `true`
- `member_management.addition.auto_opt_in_communications.enabled`
  - Default: `true`
- `member_management.addition.auto_opt_in_communications.email`
  - Default: `true`
- `member_management.addition.auto_opt_in_communications.sublists`
  - Default: `['one', 'two', 'three', 'four', 'five']`

#### `member_management.removal`

- `member_management.removal.direct.preserve_relationship`
  - Default: `false`
  - When `false`, direct strategy sets the org relationship `ends_at` value to the action time.
  - When `true`, direct strategy keeps the relationship active and only strips org-scoped roles.

#### `member_management.forms.add_member`

- `member_management.forms.add_member.layout`
  - Default: `full`
- `member_management.forms.add_member.fields.first_name.enabled`
  - Default: `true`
- `member_management.forms.add_member.fields.first_name.required`
  - Default: `true`
- `member_management.forms.add_member.fields.first_name.label`
  - Default: `First Name`
- `member_management.forms.add_member.fields.last_name.enabled`
  - Default: `true`
- `member_management.forms.add_member.fields.last_name.required`
  - Default: `true`
- `member_management.forms.add_member.fields.last_name.label`
  - Default: `Last Name`
- `member_management.forms.add_member.fields.email.enabled`
  - Default: `true`
- `member_management.forms.add_member.fields.email.required`
  - Default: `true`
- `member_management.forms.add_member.fields.email.label`
  - Default: `Email Address`
- `member_management.forms.add_member.fields.relationship_type.enabled`
  - Default: `false`
- `member_management.forms.add_member.fields.relationship_type.required`
  - Default: `false`
- `member_management.forms.add_member.fields.relationship_type.label`
  - Default: `Relationship Type`
- `member_management.forms.add_member.fields.description.enabled`
  - Default: `true`
- `member_management.forms.add_member.fields.description.required`
  - Default: `false`
- `member_management.forms.add_member.fields.description.label`
  - Default: `Description`
- `member_management.forms.add_member.fields.description.input_type`
  - Default: `textarea`
- `member_management.forms.add_member.fields.permissions.enabled`
  - Default: `true`
- `member_management.forms.add_member.fields.permissions.required`
  - Default: `true`
- `member_management.forms.add_member.fields.permissions.label`
  - Default: `Permissions`
- `member_management.forms.add_member.fields.permissions.allowlist`
  - Default: `[]`
- `member_management.forms.add_member.fields.permissions.denylist`
  - Default: `[]`
- `member_management.forms.add_member.allow_relationship_type_editing`
  - Default: `false`
- `member_management.forms.add_member.clear_form_on_error`
  - Default: `false`
  - When enabled, the Add Member form will be cleared/reset when an error occurs during submission.
  - Useful for sites that want to clear potentially invalid data from the form on failure.

#### `member_management.permissions_modal`

- `member_management.permissions_modal.allowlist`
  - Default: `[]`
- `member_management.permissions_modal.denylist`
  - Default: `[]`

#### `member_management.edit`

- `member_management.edit.require_active_membership_for_role_updates`
  - Default: `false`

#### `member_management.bulk_upload`

- `member_management.bulk_upload.batch_size`
  - Default: `25`
- `member_management.bulk_upload.columns.first_name.enabled`
  - Default: `true`
- `member_management.bulk_upload.columns.first_name.required`
  - Default: `true`
- `member_management.bulk_upload.columns.first_name.header`
  - Default: `First Name`
- `member_management.bulk_upload.columns.first_name.aliases`
  - Default: `['first name', 'firstname', 'first']`
- `member_management.bulk_upload.columns.last_name.enabled`
  - Default: `true`
- `member_management.bulk_upload.columns.last_name.required`
  - Default: `true`
- `member_management.bulk_upload.columns.last_name.header`
  - Default: `Last Name`
- `member_management.bulk_upload.columns.last_name.aliases`
  - Default: `['last name', 'lastname', 'last']`
- `member_management.bulk_upload.columns.email.enabled`
  - Default: `true`
- `member_management.bulk_upload.columns.email.required`
  - Default: `true`
- `member_management.bulk_upload.columns.email.header`
  - Default: `Email Address`
- `member_management.bulk_upload.columns.email.aliases`
  - Default: `['email address', 'email', 'e-mail']`
- `member_management.bulk_upload.columns.relationship_type.enabled`
  - Default: `true`
- `member_management.bulk_upload.columns.relationship_type.required`
  - Default: `true`
- `member_management.bulk_upload.columns.relationship_type.header`
  - Default: `Relationship Type`
- `member_management.bulk_upload.columns.relationship_type.aliases`
  - Default: `['relationship type', 'relationship']`
- `member_management.bulk_upload.columns.roles.enabled`
  - Default: `true`
- `member_management.bulk_upload.columns.roles.required`
  - Default: `false`
- `member_management.bulk_upload.columns.roles.header`
  - Default: `Roles`
- `member_management.bulk_upload.columns.roles.aliases`
  - Default: `['roles', 'permissions', 'role']`
- `member_management.bulk_upload.relationship_type.required`
  - Default: `true`
- `member_management.bulk_upload.relationship_type.allowed_types`
  - Default: `['employee_staff', 'grade_4']`
- `member_management.bulk_upload.relationship_type.aliases.employee`
  - Default: `employee_staff`
- `member_management.bulk_upload.relationship_type.aliases.grade 4`
  - Default: `grade_4`
- `member_management.bulk_upload.relationship_type.aliases.grade_4`
  - Default: `grade_4`
- `member_management.bulk_upload.relationship_type.aliases.*`
  - Relationship type normalization map for imported values.

### `groups`

#### `groups.matching`

- `groups.matching.tag_name`
  - Default: `Roster Management`
- `groups.matching.tag_case_sensitive`
  - Default: `false`

#### `groups.roles`

- `groups.roles.management`
  - Default: `['president', 'delegate', 'alternate_delegate', 'council_delegate', 'council_alternate_delegate', 'correspondent']`
- `groups.roles.roster`
  - Default: `['member', 'observer']`
- `groups.roles.member`
  - Default: `member`
- `groups.roles.observer`
  - Default: `observer`
- `groups.roles.seat_limited`
  - Default: `['member']`

#### `groups.list`

- `groups.list.page_size`
  - Default: `20`
- `groups.list.member_page_size`
  - Default: `15`

#### `groups.additional_info`

- `groups.additional_info.key`
  - Default: `association`
- `groups.additional_info.value_field`
  - Default: `name`
- `groups.additional_info.fallback_to_org_uuid`
  - Default: `true`

#### `groups.removal`

- `groups.removal.mode`
  - Default: `end_date`
- `groups.removal.end_date_anchor`
  - Default: inherits `removal.end_date_anchor`
  - Allowed: `action_time`, `day_start_utc`
- `groups.removal.end_date_format`
  - Default: `Y-m-d\\TH:i:s\\Z`

#### `groups.presentation`

- `groups.presentation.enable_group_profile_edit`
  - Default: `true`
- `groups.presentation.use_unified_member_list`
  - Default: `true`
- `groups.presentation.use_unified_member_view`
  - Default: `true`
- `groups.presentation.show_edit_permissions`
  - Default: `false`
- `groups.presentation.search_clear_requires_submit`
  - Default: `true`
- `groups.presentation.editable_fields`
  - Default: `['name', 'description']`

### `removal`

- `removal.end_date_anchor`
  - Default: `action_time`
  - Allowed: `action_time`, `day_start_utc`
  - Shared default for membership, relationship, and group end-dating. Strategy-specific configs may override it.

### `presentation`

#### `presentation.organization_list`

- `presentation.organization_list.page_size`
  - Default: `5`
- `presentation.organization_list.use_custom_title`
  - Default: `false`
- `presentation.organization_list.custom_title`
  - Default: `''`

#### `presentation.relationships`

- `presentation.relationships.show_type`
  - Default: `false`
  - Replaces `ui.hide_relationship_type` with a positive boolean.
- `presentation.relationships.show_special_types`
  - Default: `false`

#### `presentation.member_list`

- `presentation.member_list.use_unified`
  - Default: `true`
- `presentation.member_list.show_edit_permissions`
  - Default: `true`
- `presentation.member_list.show_remove_button`
  - Default: `true`
- `presentation.member_list.show_bulk_upload`
  - Default: `false`
- `presentation.member_list.display_roles.allowlist`
  - Default: `[]`
- `presentation.member_list.display_roles.denylist`
  - Default: `[]`
- `presentation.member_list.account_status.enabled`
  - Default: `true`
- `presentation.member_list.account_status.show_unconfirmed_label`
  - Default: `true`
- `presentation.member_list.account_status.confirmed_tooltip`
  - Default: `Account confirmed`
- `presentation.member_list.account_status.unconfirmed_tooltip`
  - Default: `Account not confirmed`
- `presentation.member_list.account_status.unconfirmed_label`
  - Default: `Account not confirmed`
- `presentation.member_list.seat_limit_message`
  - Default: `All seats have been assigned. Please purchase additional seats to add more members.`
- `presentation.member_list.remove_policy_callout.enabled`
  - Default: `false`
- `presentation.member_list.remove_policy_callout.placement`
  - Default: `above_members`
- `presentation.member_list.remove_policy_callout.title`
  - Default: `Remove Members`
- `presentation.member_list.remove_policy_callout.message`
  - Default: `To remove a member from your organization, please contact your association directly.`
- `presentation.member_list.remove_policy_callout.email`
  - Default: `''`

#### `presentation.member_view`

- `presentation.member_view.use_unified`
  - Default: `true`
- `presentation.member_view.search_clear_requires_submit`
  - Default: `false`

#### `presentation.member_card`

- `presentation.member_card.fields.name.enabled`
  - Default: `true`
- `presentation.member_card.fields.name.label`
  - Default: `Name`
- `presentation.member_card.fields.job_title.enabled`
  - Default: `true`
- `presentation.member_card.fields.job_title.label`
  - Default: `Job Title`
- `presentation.member_card.fields.description.enabled`
  - Default: `true`
- `presentation.member_card.fields.description.label`
  - Default: `Description`
- `presentation.member_card.fields.description.input_type`
  - Default: `textarea`
- `presentation.member_card.fields.email.enabled`
  - Default: `true`
- `presentation.member_card.fields.email.label`
  - Default: `Email`
- `presentation.member_card.fields.roles.enabled`
  - Default: `true`
- `presentation.member_card.fields.roles.label`
  - Default: `Roles`
- `presentation.member_card.fields.relationship_type.enabled`
  - Default: `false`
- `presentation.member_card.fields.relationship_type.label`
  - Default: `Relationship`

### `integrations`

#### `integrations.additional_seats`

- `integrations.additional_seats.enabled`
  - Default: `true`
- `integrations.additional_seats.sku`
  - Default: `additional-seats`
- `integrations.additional_seats.form_id`
  - Default: `0`
- `integrations.additional_seats.form_slug`
  - Default: `additional-seats`
- `integrations.additional_seats.min_quantity`
  - Default: `1`
- `integrations.additional_seats.max_quantity`
  - Default: `900`

#### `integrations.documents`

- `integrations.documents.allowed_types`
  - Default: `['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif']`
- `integrations.documents.max_size`
  - Default: `10485760`

#### `integrations.notifications`

- `integrations.notifications.confirmation_email_from`
  - Default: `no-reply@wicketcloud.com`

#### `integrations.business_info`

- `integrations.business_info.seat_limit_info`
  - Default: `null`

### `platform`

#### `platform.cache`

- `platform.cache.enabled`
  - Default: `false`
- `platform.cache.duration`
  - Default: `300`

## Migration Map

The runtime still uses the current paths below. These are the target destinations for the refactor.

### Top-Level Namespace Moves

- `roster.* -> membership.*`
- `feature_flags.* -> membership.resolution.*`
- `roles.* -> access.roles.*`
- `role_labels.membership_manager -> access.roles.labels.membership_manager`
- `role_labels.membership_owner -> access.roles.labels.membership_owner`
- `role_labels.org_editor -> access.roles.labels.org_editor`
- `role_labels.* -> access.roles.labels.*`
- `permissions.* -> access.permissions.*`
- `membership_cycle.* -> membership.cycle.*`
- `seat_policy.* -> membership.seat_limits.*`
- `relationships.* -> relationships.*`
- `relationship_types.* -> relationships.labels.*`
- `member_addition.* -> member_management.addition.*`
- `member_addition_form.* -> member_management.forms.add_member.*`
- `edit_permissions_modal.* -> member_management.permissions_modal.*`
- `member_edit.* -> member_management.edit.*`
- `bulk_upload.* -> member_management.bulk_upload.*`
- `groups.* -> groups.*`
- `ui.* -> presentation.*`
- `additional_seats.* -> integrations.additional_seats.*`
- `documents.* -> integrations.documents.*`
- `notifications.* -> integrations.notifications.*`
- `business_info.* -> integrations.business_info.*`
- `cache.* -> platform.cache.*`

### Detailed Key Moves

- `roster.strategy -> membership.strategy`
- `feature_flags.membership_resolution_prefer_current_cycle -> membership.resolution.prefer_current_cycle`

- `roles.owner -> access.roles.owner`
- `roles.manager -> access.roles.manager`
- `roles.editor -> access.roles.editor`
- `roles.aliases -> access.roles.aliases`

- `role_labels.* -> access.roles.labels.*`

- `permissions.edit_organization -> access.permissions.organization_edit_roles`
- `permissions.manage_members -> access.permissions.manage_member_roles`
- `permissions.add_members -> access.permissions.add_member_roles`
- `permissions.remove_members -> access.permissions.remove_member_roles`
- `permissions.purchase_seats -> access.permissions.purchase_seat_roles`
- `permissions.any_management -> access.permissions.any_management_roles`
- `permissions.prevent_owner_removal -> access.permissions.prevent_owner_removal`
- `permissions.prevent_owner_assignment -> access.permissions.prevent_owner_assignment`
- `permissions.relationship_based_permissions -> access.permissions.relationship_grants.enabled`
- `permissions.relationship_roles_map.ceo -> access.permissions.relationship_grants.roles_by_type.ceo`
- `permissions.relationship_roles_map.primary_hr_contact -> access.permissions.relationship_grants.roles_by_type.primary_hr_contact`
- `permissions.relationship_roles_map.member_contact -> access.permissions.relationship_grants.roles_by_type.member_contact`
- `permissions.relationship_roles_map.employee_staff -> access.permissions.relationship_grants.roles_by_type.employee_staff`
- `permissions.relationship_roles_map.advertising_sponsor_contact -> access.permissions.relationship_grants.roles_by_type.advertising_sponsor_contact`
- `permissions.relationship_roles_map.advertising_sponsor_billing -> access.permissions.relationship_grants.roles_by_type.advertising_sponsor_billing`
- `permissions.relationship_roles_map.* -> access.permissions.relationship_grants.roles_by_type.*`
- `permissions.role_only_management_access.enabled -> access.permissions.role_only_management_access.enabled`
- `permissions.role_only_management_access.allowed_roles -> access.permissions.role_only_management_access.allowed_roles`

- `membership_cycle.strategy_key -> membership.cycle.key`
- `membership_cycle.permissions.add_roles -> membership.cycle.permissions.add_member_roles`
- `membership_cycle.permissions.remove_roles -> membership.cycle.permissions.remove_member_roles`
- `membership_cycle.permissions.purchase_seats_roles -> membership.cycle.permissions.purchase_seat_roles`
- `membership_cycle.permissions.prevent_owner_removal -> membership.cycle.prevent_owner_removal`
- `membership_cycle.member_management.require_explicit_membership_uuid -> membership.cycle.require_explicit_membership_uuid`

- `seat_policy.tier_max_assignments -> membership.seat_limits.tier_max_assignments`
- `seat_policy.tier_name_case_sensitive -> membership.seat_limits.tier_name_case_sensitive`

- `relationships.default_type -> relationships.defaults.type`
- `relationships.member_addition_type -> relationships.addition.type`
- `relationships.allowed_relationship_types -> relationships.filters.allowlist`
- `relationships.exclude_relationship_types -> relationships.filters.denylist`
- `relationships.member_card_active_only -> relationships.display.member_card_active_only`

- `relationship_types.custom_types.ceo -> relationships.labels.custom.ceo`
- `relationship_types.custom_types.primary_hr_contact -> relationships.labels.custom.primary_hr_contact`
- `relationship_types.custom_types.employee_staff -> relationships.labels.custom.employee_staff`
- `relationship_types.custom_types.member_contact -> relationships.labels.custom.member_contact`
- `relationship_types.custom_types.* -> relationships.labels.custom.*`
- `relationship_types.special_types.advertising_sponsor_contact -> relationships.labels.special.advertising_sponsor_contact`
- `relationship_types.special_types.advertising_sponsor_billing -> relationships.labels.special.advertising_sponsor_billing`
- `relationship_types.special_types.* -> relationships.labels.special.*`

- `member_addition.auto_assign_roles -> member_management.addition.auto_assign_roles`
- `member_addition.base_member_role -> member_management.addition.base_member_role`
- `member_addition.repair_stale_relationship_without_membership -> member_management.addition.repair_stale_relationship_without_membership`
- `member_addition.auto_opt_in_communications.enabled -> member_management.addition.auto_opt_in_communications.enabled`
- `member_addition.auto_opt_in_communications.email -> member_management.addition.auto_opt_in_communications.email`
- `member_addition.auto_opt_in_communications.sublists -> member_management.addition.auto_opt_in_communications.sublists`

- `member_addition_form.layout -> member_management.forms.add_member.layout`
- `member_addition_form.fields.first_name.enabled -> member_management.forms.add_member.fields.first_name.enabled`
- `member_addition_form.fields.first_name.required -> member_management.forms.add_member.fields.first_name.required`
- `member_addition_form.fields.first_name.label -> member_management.forms.add_member.fields.first_name.label`
- `member_addition_form.fields.last_name.enabled -> member_management.forms.add_member.fields.last_name.enabled`
- `member_addition_form.fields.last_name.required -> member_management.forms.add_member.fields.last_name.required`
- `member_addition_form.fields.last_name.label -> member_management.forms.add_member.fields.last_name.label`
- `member_addition_form.fields.email.enabled -> member_management.forms.add_member.fields.email.enabled`
- `member_addition_form.fields.email.required -> member_management.forms.add_member.fields.email.required`
- `member_addition_form.fields.email.label -> member_management.forms.add_member.fields.email.label`
- `member_addition_form.fields.relationship_type.enabled -> member_management.forms.add_member.fields.relationship_type.enabled`
- `member_addition_form.fields.relationship_type.required -> member_management.forms.add_member.fields.relationship_type.required`
- `member_addition_form.fields.relationship_type.label -> member_management.forms.add_member.fields.relationship_type.label`
- `member_addition_form.fields.description.enabled -> member_management.forms.add_member.fields.description.enabled`
- `member_addition_form.fields.description.required -> member_management.forms.add_member.fields.description.required`
- `member_addition_form.fields.description.label -> member_management.forms.add_member.fields.description.label`
- `member_addition_form.fields.description.input_type -> member_management.forms.add_member.fields.description.input_type`
- `member_addition_form.fields.permissions.enabled -> member_management.forms.add_member.fields.permissions.enabled`
- `member_addition_form.fields.permissions.required -> member_management.forms.add_member.fields.permissions.required`
- `member_addition_form.fields.permissions.label -> member_management.forms.add_member.fields.permissions.label`
- `member_addition_form.fields.permissions.allowed_roles -> member_management.forms.add_member.fields.permissions.allowlist`
- `member_addition_form.fields.permissions.excluded_roles -> member_management.forms.add_member.fields.permissions.denylist`
- `member_addition_form.allow_relationship_type_editing -> member_management.forms.add_member.allow_relationship_type_editing`

- `edit_permissions_modal.allowed_roles -> member_management.permissions_modal.allowlist`
- `edit_permissions_modal.excluded_roles -> member_management.permissions_modal.denylist`

- `member_edit.require_active_membership_for_role_updates -> member_management.edit.require_active_membership_for_role_updates`

- `bulk_upload.batch_size -> member_management.bulk_upload.batch_size`
- `bulk_upload.columns.first_name.* -> member_management.bulk_upload.columns.first_name.*`
- `bulk_upload.columns.last_name.* -> member_management.bulk_upload.columns.last_name.*`
- `bulk_upload.columns.email.* -> member_management.bulk_upload.columns.email.*`
- `bulk_upload.columns.relationship_type.* -> member_management.bulk_upload.columns.relationship_type.*`
- `bulk_upload.columns.roles.* -> member_management.bulk_upload.columns.roles.*`
- `bulk_upload.relationship_type.required -> member_management.bulk_upload.relationship_type.required`
- `bulk_upload.relationship_type.allowed_types -> member_management.bulk_upload.relationship_type.allowed_types`
- `bulk_upload.relationship_type.aliases.* -> member_management.bulk_upload.relationship_type.aliases.*`

- `groups.tag_name -> groups.matching.tag_name`
- `groups.tag_case_sensitive -> groups.matching.tag_case_sensitive`
- `groups.manage_roles -> groups.roles.management`
- `groups.roster_roles -> groups.roles.roster`
- `groups.member_role -> groups.roles.member`
- `groups.observer_role -> groups.roles.observer`
- `groups.seat_limited_roles -> groups.roles.seat_limited`
- `groups.list.page_size -> groups.list.page_size`
- `groups.list.member_page_size -> groups.list.member_page_size`
- `groups.additional_info.key -> groups.additional_info.key`
- `groups.additional_info.value_field -> groups.additional_info.value_field`
- `groups.additional_info.fallback_to_org_uuid -> groups.additional_info.fallback_to_org_uuid`
- `groups.removal.end_date_anchor -> groups.removal.end_date_anchor`
- `groups.removal.mode -> groups.removal.mode`
- `groups.removal.end_date_format -> groups.removal.end_date_format`
- `removal.end_date_anchor -> removal.end_date_anchor`
- `groups.ui.enable_group_profile_edit -> groups.presentation.enable_group_profile_edit`
- `groups.ui.use_unified_member_list -> groups.presentation.use_unified_member_list`
- `groups.ui.use_unified_member_view -> groups.presentation.use_unified_member_view`
- `groups.ui.show_edit_permissions -> groups.presentation.show_edit_permissions`
- `groups.ui.search_clear_requires_submit -> groups.presentation.search_clear_requires_submit`
- `groups.ui.editable_fields -> groups.presentation.editable_fields`

- `ui.organization_list.page_size -> presentation.organization_list.page_size`
- `ui.organization_list.use_custom_title -> presentation.organization_list.use_custom_title`
- `ui.organization_list.custom_title -> presentation.organization_list.custom_title`
- `ui.hide_relationship_type -> presentation.relationships.show_type`
  - Value inversion required: old `true` becomes new `false`.
- `ui.show_special_relationships -> presentation.relationships.show_special_types`
- `ui.member_list.use_unified -> presentation.member_list.use_unified`
- `ui.member_list.show_edit_permissions -> presentation.member_list.show_edit_permissions`
- `ui.member_list.show_remove_button -> presentation.member_list.show_remove_button`
- `ui.member_list.show_bulk_upload -> presentation.member_list.show_bulk_upload`
- `ui.member_list.display_roles_allowlist -> presentation.member_list.display_roles.allowlist`
- `ui.member_list.display_roles_exclude -> presentation.member_list.display_roles.denylist`
- `ui.member_list.account_status.enabled -> presentation.member_list.account_status.enabled`
- `ui.member_list.account_status.show_unconfirmed_label -> presentation.member_list.account_status.show_unconfirmed_label`
- `ui.member_list.account_status.confirmed_tooltip -> presentation.member_list.account_status.confirmed_tooltip`
- `ui.member_list.account_status.unconfirmed_tooltip -> presentation.member_list.account_status.unconfirmed_tooltip`
- `ui.member_list.account_status.unconfirmed_label -> presentation.member_list.account_status.unconfirmed_label`
- `ui.member_list.seat_limit_message -> presentation.member_list.seat_limit_message`
- `ui.member_list.remove_policy_callout.enabled -> presentation.member_list.remove_policy_callout.enabled`
- `ui.member_list.remove_policy_callout.placement -> presentation.member_list.remove_policy_callout.placement`
- `ui.member_list.remove_policy_callout.title -> presentation.member_list.remove_policy_callout.title`
- `ui.member_list.remove_policy_callout.message -> presentation.member_list.remove_policy_callout.message`
- `ui.member_list.remove_policy_callout.email -> presentation.member_list.remove_policy_callout.email`
- `ui.member_view.use_unified -> presentation.member_view.use_unified`
- `ui.member_view.search_clear_requires_submit -> presentation.member_view.search_clear_requires_submit`
- `ui.member_card_fields.name.enabled -> presentation.member_card.fields.name.enabled`
- `ui.member_card_fields.name.label -> presentation.member_card.fields.name.label`
- `ui.member_card_fields.job_title.enabled -> presentation.member_card.fields.job_title.enabled`
- `ui.member_card_fields.job_title.label -> presentation.member_card.fields.job_title.label`
- `ui.member_card_fields.description.enabled -> presentation.member_card.fields.description.enabled`
- `ui.member_card_fields.description.label -> presentation.member_card.fields.description.label`
- `ui.member_card_fields.description.input_type -> presentation.member_card.fields.description.input_type`
- `ui.member_card_fields.email.enabled -> presentation.member_card.fields.email.enabled`
- `ui.member_card_fields.email.label -> presentation.member_card.fields.email.label`
- `ui.member_card_fields.roles.enabled -> presentation.member_card.fields.roles.enabled`
- `ui.member_card_fields.roles.label -> presentation.member_card.fields.roles.label`
- `ui.member_card_fields.relationship_type.enabled -> presentation.member_card.fields.relationship_type.enabled`
- `ui.member_card_fields.relationship_type.label -> presentation.member_card.fields.relationship_type.label`

- `additional_seats.enabled -> integrations.additional_seats.enabled`
- `additional_seats.sku -> integrations.additional_seats.sku`
- `additional_seats.form_id -> integrations.additional_seats.form_id`
- `additional_seats.form_slug -> integrations.additional_seats.form_slug`
- `additional_seats.min_quantity -> integrations.additional_seats.min_quantity`
- `additional_seats.max_quantity -> integrations.additional_seats.max_quantity`

- `documents.allowed_types -> integrations.documents.allowed_types`
- `documents.max_size -> integrations.documents.max_size`

- `business_info.seat_limit_info -> integrations.business_info.seat_limit_info`

- `notifications.confirmation_email_from -> integrations.notifications.confirmation_email_from`

- `cache.enabled -> platform.cache.enabled`
- `cache.duration -> platform.cache.duration`

## Notes

- The current runtime still reads the legacy schema. This document is the target shape, not the active parser contract.
- `presentation.relationships.show_type` is intentionally positive. It replaces the harder-to-read negative flag `ui.hide_relationship_type`.
- Member management is intentionally unified into one category because the current code already couples member addition, edit rules, permission filtering, and bulk upload.
- `ConfigService` also exposes dedicated filters for some runtime-resolved values such as additional seats form settings and document limits.
- Site-specific docs under `docs/configs/` are examples only. They are not loaded automatically.
