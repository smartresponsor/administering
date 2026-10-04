# Administering

Administering is the Symfony administrative-governance component for the platform. It is both a reusable Symfony bundle and a standalone Symfony application used for local verification, debugging, and release/RC proof.

## Runtime and package identity

- Composer package: `administering/administration`
- PHP: `^8.4`
- Symfony: `^8.1`
- Namespace: `App\Administering\`
- Bundle/standalone mode: reusable component plus `bin/console`/application boot surfaces

Development uses `composer.json` with local first-party path repositories and symlinks. Production uses `composer.prod.json` with packaged/VCS dependencies and no sibling filesystem links.

## Responsibility boundary

Administering owns administrative governance, operator-facing back-office behavior, administration operation orchestration, diagnostics, and repository-owned RC proof/handoff contracts.

Adjacent platform responsibilities remain in their owning components:

- `cruding/crud` owns generic application CRUD mechanics and route grammar. EasyAdmin CRUD used inside the administrative back-office is the explicit admin-surface exception.
- `objecting/object` owns reusable system-field packs; Administering owns its entities, mappings, migrations, and business semantics.
- `viewing/view` owns the shared rendering boundary.
- `interfacing/interface` owns shared shell/interface templates and rendering contracts.
- `collectioning/collection` owns provider-neutral collection-query semantics.
- `tabling/table` owns backend table-definition contracts.
- `failing/failure` owns the shared failure mechanism and Symfony failure integration.

Administering does not introduce `src/Domain`, Port/Adapter/Adaptor architecture, or an alternative namespace root.

## Local verification

Install the repository dependencies using the development Composer manifest, then use the repository-owned gates:

```text
composer validate --strict --check-lock
composer quality
composer quality:local
```

The full Gating profile is exposed through `composer gate` and is also part of `composer quality`.

For browser/behavioral coverage, the repository provides Playwright through the npm scripts:

```text
npm test
```

The terminal RC proof/handoff chain is exposed through `composer quality:rc-3rc` when runtime proof execution is applicable.

## Documentation

Architecture and operational documentation lives under `docs/`. `CMCP_CHANGELOG.md` is the Console-MCP orchestration journal, not the product changelog.

`README.md` is the canonical repository-facing overview. `README.adoc` is intentionally only a thin entry point so Markdown and AsciiDoc do not become divergent narrative copies.
