# Karakuri - Core Runtime Design

This document describes the minimal Karakuri core runtime architecture.
The goal is to keep the core extremely small (target: ~200-300 lines of PHP)
while allowing the system to load modules, manage configuration, and run the dashboard.

Karakuri is not intended to be a large framework.
Instead it is a mechanism that assembles modular web services.

---

# 1. Core Philosophy

Core must remain:

- Small
- Dependency-free
- Readable
- Predictable

Responsibilities of the core:

- Load configuration
- Load modules
- Provide simple helper API
- Handle basic routing
- Manage module lifecycle

Everything else belongs to modules.

---

# 2. Core Directory

Example structure after install:

```text
core/
  loader.php
  module_loader.php
  router.php
  helpers.php
```

The core should remain minimal.

---

# 3. Entry Point

Primary runtime entry:

`public/index.php`

Example:

```php
require "../core/loader.php";
```

---

# 4. Loader

The loader initializes the system.

Responsibilities:

1. Load configuration
2. Load system report
3. Initialize module loader
4. Start router

Example logic:

- Load `config.json`
- Load `system.json`
- Scan `modules/` folder
- Initialize modules

---

# 5. Module Loader

Module loader scans the modules directory.

`modules/`

Example structure:

- `modules/auth`
- `modules/qr`
- `modules/mail`

Each module contains:

- `module.json`
- `module.php`

Module loader reads `module.json` and registers modules.

---

# 6. Module Activation

Modules must follow a lifecycle.

- install
- enable
- disable
- remove

Enabled modules are stored in:

`storage/modules.json`

Example:

```json
{
  "enabled": ["auth", "qr"]
}
```

---

# 7. Module Initialization

When a module is enabled:

`module.php` is executed.

Example:

`modules/auth/module.php`

A module registers routes, services, or utilities.

---

# 8. Helper API

Core exposes simple helper functions.

Example:

`kr("module.function")`

Example usage:

- `kr("auth.login")`
- `kr("mail.send")`
- `kr("qr.generate")`

Helpers are defined in:

`core/helpers.php`

---

# 9. Router

Router is intentionally minimal.

Basic behavior:

`URL -> module handler`

Example:

- `/login -> auth module`
- `/qr -> qr module`

Router should support:

- GET
- POST

No complex routing system is required.

---

# 10. Module Interface

Modules must expose functions through a predictable interface.

Example:

- `auth.login`
- `auth.logout`
- `qr.generate`
- `mail.send`

These are mapped internally by the core.

---

# 11. Configuration

Configuration files are stored in JSON format.

`storage/config.json`

Example:

```json
{
  "site_name": "Example",
  "allow_manual_modules": false
}
```

---

# 12. System Report

Environment information collected during installation.

`storage/environment.json`

Example:

```json
{
  "php": "8.2",
  "gd": true,
  "imagick": false
}
```

Modules may read this to validate compatibility.

---

# 13. Security Principles

Core must enforce:

- Admin authentication for dashboard
- Manual module approval
- Protected storage directory

Sensitive data must never be public.

---

# 14. Dashboard Integration

Dashboard interacts with the core through helper APIs.

Example:

- Module manager
- System status
- Configuration editor

Dashboard is not part of the core runtime.

---

# 15. Minimal Core Target

Target design:

`~200-300 lines total`

Core should only implement:

- Configuration loading
- Module scanning
- Simple routing
- Helper API

Everything else must be implemented as modules.

---

# 16. Design Goal

The Karakuri core is a mechanical backbone.

Its purpose:

- Prepare runtime
- Load modules
- Connect system parts

`Karakuri Core = Small mechanism`
`Modules = Functional parts`
