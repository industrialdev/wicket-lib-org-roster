# Org Roster Library (Composer)

This directory is a Composer-installed library for Wicket organization roster management. It exposes the `OrgManagement\OrgMan` entrypoint and ships templates, assets, and service classes.

## Usage

- Install via Composer path/VCS repository into `web/app/libs/wicket-lib-org-roster`
- Autoload classes with Composer (PSR-4: `OrgManagement\` => `src/`)
- Initialize via `\OrgManagement\OrgMan::get_instance()`

### Composer Install

```bash
composer require industrialdev/wicket-lib-org-roster
```

Ensure your application loads Composer's autoloader before using OrgMan or templates.

### Datastar SDK

The Datastar PHP SDK is a Composer dependency (`starfederation/datastar-php`). Do not vendor the SDK in this library.

## Assets

Assets are served from the library directory. If your install path differs, override:
- `wicket/acc/orgman/base_path`
- `wicket/acc/orgman/base_url`

# Org Management Tailwind CSS Compiler

Standalone Tailwind CSS v4 compiler for the org-management module. This setup allows you to compile Tailwind CSS independently without depending on the main theme's build process.

## Setup

1. Install dependencies:
```bash
npm install
```

2. Start development:
```bash
npm run dev
```

3. Watch for changes during development:
```bash
npm run watch
```

4. Build for production (minified):
```bash
npm run build
```

## File Structure

- `public/css/tailwind-input.css` - Main Tailwind input file with `@import "tailwindcss";`
- `public/css/modern-orgman-tailwind-compiled.css` - Compiled CSS output (minified in production)
- `public/css/modern-orgman-legacy-fixes.css` - Legacy fixes (existing)
- `public/css/modern-orgman.css` - Modern styles (existing)
- `tailwind.config.js` - Tailwind configuration (scans all PHP files in this directory)

## Configuration

The Tailwind config is set to:
- Scan all `.php` files in the org-management directory for classes
- Disable preflight to avoid conflicts with existing theme styles
- Output to `public/css/modern-orgman-tailwind-compiled.css`

## WordPress Integration

The compiled CSS is automatically enqueued by WordPress through the `OrgMan.php` class. The enqueue order is:
1. `modern-orgman-legacy-fixes.css` - Legacy fixes
2. `modern-orgman.css` - Modern styles
3. `modern-orgman-tailwind-compiled.css` - Compiled Tailwind CSS

## Adding Custom Styles

Add custom Tailwind styles to `public/css/tailwind-input.css`:

```css
@import "tailwindcss";

@layer base {
  h1 { @apply text-2xl font-bold; }
}

@layer components {
  .btn-primary { @apply bg-blue-500 text-white px-4 py-2 rounded; }
}

/* Custom org-management styles */
.orgman-container {
  @apply max-w-6xl mx-auto p-4;
}
```
