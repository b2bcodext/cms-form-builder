# B2Bcodext CMS Form Builder

Build storefront forms for OroCommerce through the back-office UI — no code, no deployment.

## Table of Contents

- [Overview](#overview)
- [Requirements](#requirements)
- [Installation](#installation)
- [Features](#features)
- [Extension Points](#extension-points)
- [Tests](#tests)
- [Known Issues & TODOs](#known-issues--todos)
- [License](#license)
- [Resources](#resources)

## Overview

The bundle lets a back-office user assemble a form — its fields, validation, notifications and
success behavior — and publish it to the storefront, without a developer writing a form type or a
controller. Marketers own the whole lifecycle; developers extend it only where they need something
the field-type catalog does not cover.

Five entities carry the model: `CmsForm` is the form itself, `CmsFormField` its fields,
`CmsFormNotification` the per-form email notifications, and `CmsFormResponse` / `CmsFieldResponse`
a submission and its individual field values. The runtime flow is
`FormBuilder` (turns a `CmsForm` into a Symfony form) → storefront submit → response entities
persisted → notification queued.

## Requirements

Requires **OroCommerce 7.0** (`oro/commerce: 7.0.*`) on PHP 8.5.

## Installation

1. Require the package:

   ```bash
   composer require b2bcodext/cms-form-builder
   ```

2. Apply it to the application:

   ```bash
   bin/console cache:clear
   bin/console oro:platform:update --env=prod --force
   ```

Forms are then available under **Marketing > Cms Forms** in the back-office menu. See
[how to create your first form](src/B2bCode/Bundle/CmsFormBundle/Resources/doc/user_doc.md#how-to-create-your-first-form).

## Features

### Backend

- Form management UI under **Marketing > Cms Forms**, routed under the `/cms-form` prefix
  (`Controller/FormController.php`), with AJAX field editing, reordering and field preview under
  `/cms-form/ajax` (`Controller/AjaxFormController.php`).
- A field-type catalog with per-type options and validation — see
  [field types](src/B2bCode/Bundle/CmsFormBundle/Resources/doc/field_types.md).
- Per-form email notifications (`CmsFormNotification`), each able to use its own email template;
  sending goes through `Notification\SendEmailNotification`.
- Responses exported to CSV through the Oro import/export batch job
  (`ImportExport/`), scoped per form.
- Responses are ordinary configurable Doctrine entities rather than an opaque log, so they are
  available to the platform's generic entity tooling.

### Frontend

- Storefront form rendering and submission over the layout stack
  (`Controller/Frontend/FormController.php`, `Controller/Frontend/AjaxFormController.php`,
  `Resources/views/layouts`), with per-field validation errors returned to the page and an optional
  redirect URL on success.
- Forms can be embedded in landing pages and other CMS content; the bundle registers its Twig
  functions with the CMS and email Twig sandboxes via
  `DependencyInjection/Compiler/TwigSandboxConfigurationPass.php`.

### Access Control

Declared in `Resources/config/oro/acls.yml`:

- Entity permissions on `CmsForm` (VIEW / CREATE / EDIT / DELETE) and on `CmsFormField`
  (CREATE / EDIT / DELETE), governing the back-office UI.
- An action ACL `b2b_code_cms_frontend_form_respond` in the `commerce` group, granted to the
  `BUYER`, `ADMINISTRATOR` and `ANONYMOUS` frontend roles by the data migration
  `Migrations/Data/ORM/data/frontend_roles.yml`, and enforced on the storefront submit endpoint
  (`Controller/Frontend/AjaxFormController::respondAction()`). Revoking it stops submission — the storefront endpoint is refused rather than
  accepted. The functional suite pins the anonymous case (403 is the corresponding refusal for an
  authenticated customer user, and follows from Symfony's standard handling rather than from a test
  in this package). The default grant to
  `ANONYMOUS` keeps storefront forms open to unauthenticated visitors out of the box.

## Extension Points

| Contract | Purpose |
|---|---|
| `B2bCode\Bundle\CmsFormBundle\Provider\FieldTypeProviderInterface` | Contribute field types. Register with the `b2b_code_cms_form.field_type_provider` service tag. |
| `B2bCode\Bundle\CmsFormBundle\Validator\ConstraintProviderInterface` | Supply the validation constraints for a form (`getConstraintsForForm(CmsForm)`). |
| `B2bCode\Bundle\CmsFormBundle\Notification\NotificationInterface` | Replace or add to how a submission is notified. |
| `B2bCode\Bundle\CmsFormBundle\Builder\FormBuilderInterface` | Replace how a `CmsForm` becomes a Symfony form. |

Validation rules can also be declared in YAML or contributed from an event listener — see the
[developer documentation](src/B2bCode/Bundle/CmsFormBundle/Resources/doc/dev_doc.md#validation) and
[how to add a new field type](src/B2bCode/Bundle/CmsFormBundle/Resources/doc/dev_doc.md#how-to-add-new-field-type).

## Tests

Both suites live in `src/B2bCode/Bundle/CmsFormBundle/Tests` and ship with the package. Run them from
the application the package is installed into. The two suites use **different** configurations, and
the difference matters: the unit suite runs against the package's own `phpunit.xml.dist` with the
application's autoloader passed explicitly (the package has no vendor directory of its own), while the
functional suite must run against the **application's** `phpunit.xml.dist`, which supplies the Oro test
bootstrap. Do not "correct" one to match the other.

### Unit Tests

```bash
bin/phpunit -c vendor/b2bcodext/cms-form-builder/phpunit.xml.dist --testsuite=unit \
    --bootstrap ./vendor/autoload.php
```

### Functional Tests

Require an installed test environment (`bin/console oro:install --env=test`):

```bash
bin/phpunit -c phpunit.xml.dist \
    vendor/b2bcodext/cms-form-builder/src/B2bCode/Bundle/CmsFormBundle/Tests/Functional
```

### Static Analysis & Code Style

```bash
bin/phpcs --standard=vendor/b2bcodext/cms-form-builder/phpcs.xml.dist \
    vendor/b2bcodext/cms-form-builder/src
```

`phpcs` ships with the Oro application. PHPStan does not — install it (the package declares
`phpstan/phpstan: ^2.1` in `require-dev`) and run:

```bash
bin/phpstan analyse -c vendor/b2bcodext/cms-form-builder/phpstan.neon
```

`phpstan.neon` runs at level 6 and excludes `Tests`. On 7.0 the phpcs ruleset resolves to Oro's
`Oro/ruleset.xml` and additionally enforces `declare(strict_types=1)`, which every file in the
package carries.

### CI

Omitted — the package ships no CI workflow. The hosting GitHub space does not support GitHub Actions,
so the checks above are run locally and are not re-run automatically on push or pull request.

## Known Issues & TODOs

- `ImportExport/Reader/FormResponseReader::createSourceEntityQueryBuilder()` accepts an `$ids`
  argument but does not apply it, so a batched async export re-reads the whole form's responses
  instead of the requested slice. Current behavior is pinned by a unit test; changing it is a
  maintainer decision. On installations with large response volumes this is a real cost, not a
  theoretical one — the batch size stops bounding the query.
- Sixteen `@todo` occurrences remain in non-test source (one of them inside a string literal).
  The largest clusters are `Form/Extension/ChoiceFieldExtension.php` (3 — two asking for the
  choice-field handling to be reworked into data transformers or data mappers, which the bundle
  does not currently use) and `Controller/Frontend/AjaxFormController.php` (3, in the submit
  endpoint discussed under Access Control); the rest sit in the Twig extension, the validation
  collection and provider, the field-type registry, the response repository, notification sending
  and the schema installer. The `Generic.Commenting.Todo` sniff is excluded in `phpcs.xml.dist` so they are kept
  verbatim rather than deleted to satisfy a linter.

## License

[OSL-3.0](LICENSE) — Copyright (c) 2019 Daniel Nahrebecki

## Resources

  * [Source](https://github.com/b2bcodext/cms-form-builder)
  * [OroCommerce Documentation](https://doc.oroinc.com)
  * [Contributing](https://doc.oroinc.com/community/contribute/)
  * [Reporting a Security Issue](https://doc.oroinc.com/community/report-issues/security/)
