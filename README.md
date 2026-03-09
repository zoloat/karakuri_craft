# karakuri_craft

Lightweight modular PHP web-service scaffolding with a web-first installer.

## Current Scope

- Web-first bootstrap installer: `karakuri.php`
- Minimal core runtime:
  - `core/loader.php`
  - `core/router.php`
  - `core/module_loader.php`
  - `core/helpers.php`
- Setup flow:
  - Create admin account (`storage/admin.json`)
  - Save initial config (`storage/config.json`)
  - Initialize enabled modules (`storage/modules.json`)
- Dashboard:
  - Login / logout
  - Basic system summary
  - Module enable/disable manager
- Sample module:
  - `modules/welcome`
  - Routes: `/` and `/health`

## Quick Start

1. Put this project under your PHP web environment.
2. Open `karakuri.php` in a browser.
3. Click through setup at `public/index.php/setup`.
4. Login to dashboard at `public/index.php/dashboard/login`.

## Runtime Files

Generated or mutable runtime files are stored in `storage/` and are ignored by git.

## Notes

- Install mode policy:
  - Web installer is the primary mode.
  - CLI execution is optional helper mode.
- Module distribution policy:
  - Git-based distribution is preferred.
  - ZIP is optional compatibility path.
