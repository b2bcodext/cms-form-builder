# B2Bcodext - Cms Form Builder

CMS Form Builder is a flexible OroCommerce extension that allows you to easily create
forms via UI. 

No longer need to have a dev team in order to add a form to your storefront. With this extension you can create forms in minutes without writing even a single line of code.

## Installation

The easiest way to install CMS Form Builder is by using [Composer](https://getcomposer.org):

```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar require b2bcodext/cms-form-builder
```

or if you have Composer already installed, just run:

```bash
composer require b2bcodext/cms-form-builder
```

After installation and clearing cache, forms are accessible under Marketing > Cms Forms in the back-office menu. Read more on [how you can create your first form.](./Resources/doc/user_doc.md#how-to-create-your-first-form)


## Features

- Easily embed your forms in landing pages.
- Email notifications sent on every form response.
    - Possibility to set different custom email templates per email.
- Export form responses to CSV.
- Possibility to add additional CSS to form fields directly from UI.
- Many field types supported out of the box, like Email (with validation support) or Hidden field. [Full list](./Resources/doc/field_types.md).
   - Especially useful when creating special forms for marketing campaign which gives you possibility to embed custom values (like campaign code) without exposing that to end users
- Seamless integration with ORO Reports engine. Powerful tool for creating custom reports based on form responses.

### Features for developers

> Flexibility Driven Development

- Easy custom validation rules setup via YAML or events. [More info](./Resources/doc/dev_doc.md#validation).
    - E.g. check if a given email already exist in a system or validate product SKU from Request for Quote form
- Easily create your own form field types with custom type-specific options and/or custom validation. [Have a look at the example.](./Resources/doc/dev_doc.md#how-to-add-new-field-type)
- Most of the functionality is covered either with interfaces, events or DI tags which gives you a possibility to inject your code on every single stage of the process.

## Documentation

- [For developers](./Resources/doc/dev_doc.md)
- [For users](./Resources/doc/user_doc.md)

## License

[OSL-3.0](./LICENSE) Copyright (c) 2019 Daniel Nahrebecki <daniel@b2bcodext.com>
