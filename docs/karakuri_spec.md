# Karakuri - Concept and Architecture

## 1. Overview

Karakuri is a lightweight web service scaffolding system written in PHP.

Its purpose is to quickly generate the structural foundation of a web service without requiring a heavy framework, package manager, or complex setup.

Core philosophy:

- Minimal dependencies
- Simple installation
- Modular feature design
- Compatibility with shared hosting environments

Karakuri is not intended to be a full framework or CMS. Instead, it acts as a service skeleton generator and modular runtime.

The design concept is inspired by the Japanese word "karakuri", meaning a mechanism or hidden device that performs actions through internal structure.

Conceptually:

`single script -> expand internal structure -> assemble modules -> web service skeleton`

---

## 2. Core Philosophy

Karakuri aims to reduce developer friction.

Design principles:

1. Minimal learning cost
2. No heavy frameworks
3. Works on shared hosting
4. No dependency manager required
5. Modular architecture
6. Developer-friendly structure

The goal is to let developers (or AI-assisted developers) focus on service logic rather than infrastructure setup.

---

## 3. Installation Model

Karakuri uses a self-expanding installer.

Example script:

`karakuri.php`

Running the script expands the project structure.

Flow:

`upload karakuri.php -> execute -> project structure generated -> karakuri.php deletes itself`

Primary install mode:

`https://example.com/karakuri.php` (web installer)

Optional:

`php karakuri.php` (CLI helper mode)

---

## 4. Base Directory Structure

Example generated structure:

```text
project/
  core/
  modules/
  libraries/
  storage/
  public/
  dashboard/
  config/
```

Description:

| Directory | Purpose |
| --- | --- |
| core | Internal Karakuri runtime |
| modules | Feature modules |
| libraries | External libraries |
| storage | JSON configuration and storage |
| public | Web root files |
| dashboard | Admin UI |
| config | Configuration files |

---

## 5. Core System

The core system should remain extremely small.

Responsibilities:

- Module loader
- Configuration loader
- Routing helper
- Module lifecycle control
- Security checks

Core must avoid external dependencies.

---

## 6. Modules

Modules provide service functionality.

Examples:

```text
modules/
  auth/
  mail/
  qr/
  upload/
  admin/
```

Each module is self-contained.

Example structure:

```text
modules/auth/
  module.json
  module.php
  views/
```

Module metadata example:

```json
{
  "name": "auth",
  "version": "1.0",
  "description": "User authentication"
}
```

Modules can be installed by:

1. GitHub registry
2. Git repository install (recommended)
3. Manual folder installation

---

## 7. External Libraries

External libraries are stored separately.

Example:

```text
libraries/
  phpmailer/
  phpqrcode/
```

Modules may depend on libraries, but the core does not.

Example usage:

`modules/mail -> libraries/phpmailer`

---

## 8. Module Installation Sources

Modules can be installed from three sources.

### Official Registry

Modules are listed in a registry file hosted on GitHub.

Example file:

`registry.json`

Example content:

```json
{
  "modules": [
    {
      "name": "auth",
      "type": "git",
      "url": "https://github.com/example/auth-module.git"
    }
  ]
}
```

### Git Repository Install (Recommended)

An admin installs a module from a Git repository URL.

### ZIP Upload (Optional Compatibility)

An admin uploads a module archive only when needed for compatibility.

### Manual Install

A user places a module folder in:

`modules/`

Manual modules are disabled until approved.

---

## 9. Security Model

Manual module installation is disabled by default.

An admin must explicitly enable it.

This is conceptually similar to unknown-source installation models.

Configuration example:

`allow_manual_modules = false`

When enabled, the system detects unverified modules and requests manual approval.

---

## 10. Dashboard

The admin dashboard provides:

- Module management
- Module installation
- Configuration editing
- Module approval
- System updates

Example dashboard sections:

- Module Manager
- System Settings
- Library Manager
- Module Approval

---

## 11. Configuration

Karakuri prefers JSON configuration.

Example:

`storage/config.json`

Example contents:

```json
{
  "site_name": "Example",
  "allow_manual_modules": false
}
```

Database configuration may also be stored here.

Supported storage types may include:

- SQLite
- MySQL
- JSON storage

---

## 12. API Design Philosophy

Karakuri emphasizes simple function patterns.

Example naming style:

`auth_login()`
`mail_send()`
`qr_generate()`

or

`kr("auth.login")`
`kr("mail.send")`

This keeps module interaction predictable.

---

## 13. Offline Capability

Karakuri should operate fully offline once installed.

Network access is only required for:

- Module downloads
- Registry updates

Core runtime must not require network access.

---

## 14. Development Philosophy

Karakuri aims to function as a developer toolkit rather than a framework.

It prepares infrastructure such as:

- Authentication
- Menu systems
- Dashboard
- Routing
- Configuration
- Module management

Developers or AI systems can then build service logic on top of this structure.

---

## 15. Intended Benefits

Karakuri enables:

- Fast service prototyping
- AI-assisted development
- Minimal hosting requirements
- Modular expansion
- Simple maintenance

---

## 16. Summary

Karakuri is a lightweight modular web service foundation designed around:

- Simplicity
- Modularity
- Self-contained installation
- Minimal dependencies

It acts as a mechanical system for assembling web services, inspired by the concept of karakuri mechanisms.
