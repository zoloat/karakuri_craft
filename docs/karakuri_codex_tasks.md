# Karakuri - Implementation Task Specification

This document converts the Karakuri design discussion into a step-by-step implementation plan
that can be executed by an AI coding system such as Codex.

Karakuri is a lightweight modular web-service construction mechanism written in PHP.
The system installs from a single bootstrap script and expands into a minimal modular runtime.

---

# Step 0 - Project Goal

Karakuri must provide:

- Extremely simple installation
- Compatibility with shared hosting
- Modular extension system
- Minimal dependencies
- AI-friendly architecture
- Offline operation after install

The system should function as a web-service construction kit rather than a full framework.

---

# Step 1 - Bootstrap Installer

Entry file:

`karakuri.php`

The file is uploaded to a server and opened via browser (primary mode).

Example:

`https://example.com/karakuri.php`

Installer responsibilities:

1. Run environment checks
2. Display system information
3. Show Karakuri explanation and license
4. Request user confirmation
5. Expand core system

---

# Step 2 - Environment Detection

Installer must collect server information.

Required checks:

- PHP version
- GD extension
- Imagick extension
- PDO
- SQLite
- MySQL
- curl
- zip
- Write permissions
- Directory creation ability

Save report to:

`storage/environment.json`

Example:

```json
{
  "php_version": "8.2",
  "gd": true,
  "imagick": false,
  "pdo": true,
  "sqlite": true,
  "mysql": true,
  "curl": true,
  "zip": true
}
```

This report will later be used for module compatibility checks.

---

# Step 3 - Display System Report

Before installation the user must see the environment report.

Example display:

- PHP version: OK
- GD: OK
- Imagick: Missing
- SQLite: OK
- MySQL: OK

User must understand the capabilities of their server.

---

# Step 4 - Terms and Installation Confirmation

Display:

- Explanation of Karakuri
- Security notice
- Module system explanation
- License / usage agreement

User must click:

`Accept and Install`

---

# Step 5 - Core Expansion

Once accepted, the installer expands the minimal system.

Create folders:

- `core/`
- `modules/`
- `storage/`
- `dashboard/`
- `config/`
- `public/`

Then remove the installer:

- Delete `karakuri.php`
- Or rename to `install.lock`

---

# Step 6 - Setup Phase

After expansion redirect to:

`/setup`

The setup wizard performs:

1. Admin account creation
2. Storage initialization
3. Optional database configuration
4. Module security configuration

---

# Step 7 - Admin Account Creation

Admin credentials are required for dashboard access.

Input fields:

- Admin username
- Admin password

Store credentials in:

`storage/admin.json`

Example:

```json
{
  "user": "admin",
  "password": "hashed_password"
}
```

Password must use:

`password_hash()`

---

# Step 8 - Storage Structure

Sensitive data must be placed in a protected folder.

Structure:

```text
storage/
  admin.json
  environment.json
  system.json
  logs/
```

If storage is inside web root, create:

`storage/.htaccess`

Example:

`Deny from all`

---

# Step 9 - Optional Configuration

Setup should optionally allow:

Database type selection:

- JSON storage
- SQLite
- MySQL

Also allow configuration of:

`allow_manual_modules`

Default:

`false`

---

# Step 10 - Dashboard Initialization

After setup redirect to:

`/dashboard`

Dashboard displays:

- System information
- Installed modules
- Server environment
- Module manager

---

# Step 11 - Module Manager

Module manager must support installation methods with Git as first choice.

1. Registry install (Git URL)
2. Git repository URL install (recommended)
3. Manual folder install

ZIP upload may exist only as an optional compatibility path.
Manual modules must be disabled until approved.

---

# Step 12 - Module Registry

Registry file hosted remotely.

Example:

`registry.json`

Example contents:

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

Dashboard resolves this list and installs modules by type.

---

# Step 13 - Module Structure

Each module must be self-contained.

Example:

`modules/auth/`

Contains:

- `module.json`
- `module.php`
- `lib/`
- `views/`

---

# Step 14 - Module Metadata

File:

`module.json`

Example:

```json
{
  "name": "QR Generator",
  "slug": "qr",
  "type": "output",
  "category": "media",
  "author": {
    "name": "Example Dev",
    "contact": "dev@example.com"
  },
  "requires": {
    "php": ">=8.0",
    "extensions": ["gd"]
  }
}
```

---

# Step 15 - Module Classification

Modules must define two classification properties.

`type`

Functional role:

- input
- output
- service
- utility
- system

`category`

Domain grouping:

- media
- security
- payment
- communication
- database

---

# Step 16 - Module Libraries

Modules may include their own libraries.

Example:

`modules/qr/lib/`

This prevents dependency conflicts between modules.

Removing a module must remove its libraries.

---

# Step 17 - Module Lifecycle

Modules follow a simple lifecycle:

- install
- enable
- disable
- remove

Removing a module deletes its folder and dependencies.

---

# Step 18 - System State File

System information should be tracked.

`storage/system.json`

Example:

```json
{
  "php": "8.2",
  "gd": true,
  "modules": [
    "auth",
    "mail"
  ]
}
```

This allows compatibility checks during module installation.

---

# Step 19 - Offline Operation

After installation the system must operate fully offline.

Network access is only required for:

- Module downloads
- Registry updates

Core runtime must not depend on internet access.

---

# Step 20 - Design Objective

Karakuri acts as a modular web-service construction mechanism.

The system prepares infrastructure such as:

- Authentication
- Dashboard
- Module management
- Configuration
- Environment detection

Developers or AI systems can then build the service logic on top of this structure.
