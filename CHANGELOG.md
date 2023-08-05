# Release Notes for Newsletter

## 2.2.0 - 2023-08-05

### Changed

- Switch from Sendinblue to Brevo PHP SDK and update adapter accordingly

> {note} Previous Sendinblue adapter class has been renamed from `juban\newsletter\adapters\Sendinblue`
> to `juban\newsletter\adapters\Brevo`.
> 
> Plugin configuration automatic update will occur during migrations to ensure adapter portability.

## 2.1.0 - 2022-07-23

> {note} The plugin’s package name has changed to `jub/craft-newsletter`. You can update the plugin by
> running `composer require jub/craft-newsletter && composer remove simplonprod/craft-newsletter`.

### Changed

- Migrate plugin to `jub/craft-newsletter`
- Updated plugin logo
- Update NewsletterController.php to use Craft 4 asModelFailure and asModelSuccess unified methods

## 2.0.0 - 2022-05-15

### Added

- Added Craft 4 compatibility

## 1.5.1 - 2022-03-01

### Fixed

- Fixes a bug that could produce an error when saving the configuration from the control panel if a service was already
  configured.

## 1.5.0 - 2022-02-28

### Added

- Sendinblue Double Opt-in support (thx to @kringkaste)
- Google reCAPTCHA activation can be set with environment variable

### Changed

- Bump minimum required Craft version to 3.7.29

## 1.4.0 - 2021-07-13

### Added

- AJAX support (thx to @jerome2710)

## 1.3.0 - 2021-05-09

### Added

- Setting to enable Google reCAPTCHA if available
- Settings override warning

### Changed

- Changed package signature to `simplonprod/craft-newsletter` for more consistency
- Updated plugin icon
- Updated README
- Small refactoring

## 1.2.0 - 2021-04-01

### Added

- Google reCAPTCHA plugin support

## 1.1.0 - 2021-03-24

### Added

- Mailchimp adapter
- Sendinblue adapter

## 1.0.1 - 2021-01-30

### Changed

- Remove session notice on submission success

## 1.0.0 - 2021-01-26

### Changed

- Better Mailjet errors handling

### Added

- Front-end form example in README

## 1.0.0-beta.1 - 2021-01-25

- Initial release of public beta.
