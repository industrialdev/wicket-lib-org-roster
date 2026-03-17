# Active Site Config Docs

This folder contains documentation snapshots of active site override configurations. These files are not runtime configuration, but they are intended to mirror the real site override files for sites currently using `wicket-lib-org-roster`.

## Current Site Mappings

- `docs/configs/CCHL.md`
  - source of truth: `../cchl-website-wordpress/src/web/app/themes/industrial/custom/org-roster.php`
  - `direct` strategy
- `docs/configs/ESCRS.md`
  - source of truth: `../escrs-website-wordpress/src/web/app/themes/wicket-child/custom/org-roster.php`
  - `membership_cycle` strategy
- `docs/configs/IAA.md`
  - source of truth: `../iaa-website-wordpress/src/web/app/themes/wicket-child/custom/orgroster.php`
  - `groups` strategy
- `docs/configs/MSA.md`
  - source of truth: `../msa-website-wordpress/src/web/app/themes/wicket-child/custom/org-roster.php`
  - `cascade` strategy
- `docs/configs/NJBIA.md`
  - source of truth: `../njbia-website-wordpress/src/wp-content/themes/njbia/theme/inc/org-roster.php`
  - `cascade` strategy

## Important Rule

These files are manually maintained documentation. The library does not load them, validate them, or synchronize them automatically with external site repositories. When a site override changes, update the matching file in `docs/configs/` to keep the documentation aligned with the real override.
