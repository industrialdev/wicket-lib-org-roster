# Direct Strategy: Add / Remove

## Add

- strategy key: `direct`
- scope: organization-first
- optional context:
  - `membership_uuid` or `membership_id`
  - `relationship_type`
  - `relationship_description`
  - `roles`

Current add flow:

- create or update the person
- resolve target membership UUID
- validate explicit membership scope when provided
- create missing person-to-organization relationship
- assign membership seat
- assign base member role, configured auto-roles, and optional form roles
- log touchpoint and attempt assignment email

## Remove

- requires:
  - `org_id`
  - `person_uuid`
  - `person_membership_id` in context

Current remove flow strips org-scoped roles and respects owner-protection config.
