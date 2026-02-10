# Frontend Development

This document explains the reactive frontend patterns used in the `wicket-lib-org-roster` library.

## 1. Reactive Pattern (Datastar)

The library uses [Datastar](https://data-star.dev/) to provide a Single Page Application (SPA) feel within WordPress without the complexity of a heavy framework like React or Vue.

### 1.1 Signals
State is stored in "signals" defined in the HTML.
- **Initialization**: `data-signals='{ "show_modal": false, "search": "" }'`
- **Binding**: Use `data-bind` to sync inputs with signals.
- **Usage**: Use signals in expressions, e.g., `data-show="signals.show_modal"`.

### 1.2 Server-Sent Events (SSE)
Instead of returning JSON, our REST API returns SSE streams that patch the DOM directly.
- **Trigger**: `data-on-click="$$post('/wp-json/org-management/v1/...')"`
- **Response**: The server uses `DatastarSSE` to send HTML fragments.
- **Merging**: Fragments are merged into the DOM based on the `selector` and `mode` (Inner, Outer, Append, etc.).

### 1.3 Fragments
Fragments are small pieces of PHP templates. When an action occurs (e.g., adding a member), the server renders the "Success" fragment and the "Updated List" fragment, sending them both in a single SSE stream.

## 2. Styling (Scoped Vanilla CSS + BEM)

### 2.1 Prefixing
To avoid conflicts with WordPress themes and other plugins, all scoped utility-style classes **MUST** use the `wt_` prefix and component classes should follow BEM naming.
- **Example (scoped utilities)**: `wt_bg-blue-600 wt_text-white wt_p-4`
- **Example (BEM)**: `members-search__input members-search__actions`

### 2.2 Source of Truth
- **Primary stylesheet**: `public/css/modern-orgman-static.css`
- **Build tooling**: None required at runtime (no Tailwind/NPM pipeline)

## 3. The "Unified View"
The Unified View is a search-centric interface for managing rosters.
- **Loading States**: Use the `searching` signal to show/hide loading indicators during API calls.
- **Search Logic**: Search is typically "Submit-only" for groups (requiring an enter/click) to reduce API load, but can be "Instant" for other strategies depending on config.

## 4. Modals and Overlays
Modals are managed via signals and the `notifications-container.php`.
- Action handlers patch the modal content into the container and set the `show_modal` signal to `true`.
- Closing the modal simply sets the signal back to `false`.
