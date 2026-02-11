# Design

This document details the implementation-specific design choices for the `wicket-lib-org-roster` library.

## 1. Service Design
Services in `src/Services/` are designed for high cohesion and low coupling.
- **Lazy Loading**: Services like `PermissionService` and `ConnectionService` are instantiated within `MemberService` only when accessed.
- **Dependency Injection**: Services receive configuration or other services via their constructors (e.g., `MemberService` requires `ConfigService`).
- **Caching Logic**: Services handle their own cache keys and durations, utilizing `ConfigHelper` to determine if caching is enabled globally.

## 2. API & Controller Design
Controllers in `src/Controllers/` extend a base `ApiController`.
- **Permission Checking**: Every request is validated through `check_permission()`, which uses `PermissionService` to verify the user's role against the target organization.
- **Response Handling**: Controllers use `success()` and `error()` methods to ensure standardized JSON structures.
- **SSE Integration**: For reactive updates, controllers utilize the `DatastarSSE` helper to stream HTML fragments back to the client.

## 3. Frontend & Reactivity Design

### 3.1 Datastar Implementation
The UI avoids full-page reloads by using Datastar signals and SSE.
- **Signals**: Used for UI state (e.g., `show_modal`, `search_query`).
- **Fragment Patching**: The `DatastarSSE` helper patches specific DOM elements using `Inner` or `Outer` patch modes.
- **Script Execution**: The server can trigger client-side scripts (e.g., a 5-second countdown to reload) via the SSE stream.

### 3.2 Template Structure
- **Templates**: `templates/` contains top-level wrappers injected into the WordPress content area.
- **Partials**: `templates-partials/` contains the actual UI components and logic fragments.
- **Process Handlers**: `templates-partials/process/` contains PHP scripts that handle the logic for specific actions (like adding or removing a member) and generate the SSE responses.

## 4. Caching Strategy
The library uses WordPress Transients for caching expensive API calls.
- **Key Generation**: Cache keys are MD5 hashes of the parameters (e.g., `org_uuid`, `page`, `size`) to ensure uniqueness.
- **Invalidation**: Methods like `clear_members_cache` proactively delete transients when a relevant update occurs (e.g., after adding a new member).

## 5. Security Design
- **Capability Checks**: Roles like `membership_owner`, `membership_manager`, and `org_editor` are mapped to specific actions (edit, manage, add, remove).
- **Self-Management Prevention**: Logic is in place to prevent users from accidentally removing their own managing roles.
- **Input Sanitization**: All inputs from REST requests are sanitized using standard WordPress functions (`sanitize_text_field`, etc.).
