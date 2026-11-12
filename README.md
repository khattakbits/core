# khattakbits/core

KhattakBits shared kernel. Extracted from the starter kit so every product app
(and the control plane) installs the same audit, mailer, documents, RBAC and
settings code.

**Status:** extraction in progress (steps 1–2 of `docs/platform/03-EXTRACTION-PLAN.md`).

## Install (host app)

```json
"repositories": [
    { "type": "path", "url": "../khattakbits-core", "options": { "symlink": true } }
],
"require": {
    "khattakbits/core": "*@dev"
}
```

Production uses a `vcs` repository pointing at the private GitHub repo.

## Layout

```
src/
├── CoreServiceProvider.php   auto-discovered via extra.laravel.providers
├── Support/                  Naming, TableQuery, DocumentColumns, ModuleRegistry
└── Exceptions/               DocumentException
```

More folders (`Models`, `Http`, `Jobs`, `config`, `database`, `routes`) arrive in
later extraction steps.
