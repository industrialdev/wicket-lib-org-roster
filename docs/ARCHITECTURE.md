# Architecture

This document describes the high-level architecture of the `wicket-lib-org-roster` library.

## 1. System Overview
The library is designed as a modular extension for WordPress, providing organization and roster management capabilities powered by the Wicket MDP. It acts as a bridge between the Wicket API, WooCommerce, and the WordPress frontend.

## 2. Core Patterns

### 2.1 Orchestrator Pattern (`OrgMan`)
The `OrgManagement\OrgMan` class serves as the central orchestrator. It is a singleton responsible for:
- Initialization and dependency loading.
- Configuration management.
- Hook registration (Actions/Filters).
- Asset management.

### 2.2 Strategy Pattern (Roster Management)
The library decouples member management logic from the `MemberService` using the Strategy pattern. This allows the system to switch between different management modes without changing the service interface:
- **DirectAssignmentStrategy**: Standard role/connection assignment.
- **CascadeStrategy**: Complex membership-based cascading logic.
- **GroupsStrategy**: MDP Group-based management with tag filtering.

### 2.3 Service Layer
All business logic is encapsulated in a service layer. Services are domain-specific and typically lazily instantiated to minimize overhead.

## 3. Component Interaction
- **Frontend**: A reactive UI built with Datastar and Tailwind CSS.
- **Backend API**: WordPress REST API controllers that delegate to the service layer.
- **Data Source**: Wicket MDP API (External).
- **Payment/Seats**: WooCommerce integration for purchasing and updating seat limits.

## 4. High-Level Data Flow
1. **Request**: User action triggers a Datastar-powered REST call.
2. **Processing**: Controllers validate permissions via `PermissionService` and invoke the appropriate business logic in the service layer.
3. **External Sync**: Services interact with the Wicket API and/or WooCommerce to persist changes.
4. **Response**: The system returns Server-Sent Events (SSE) to patch the frontend UI reactively.

## 5. External Dependencies
- **Wicket MDP**: Primary source of truth for people, organizations, and memberships.
- **WooCommerce**: Handles seat limit transactions.
- **Datastar**: Provides the frontend reactivity framework.
- **Tailwind CSS**: Utility-first styling with a `wt:` prefix to prevent collisions.
