# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [2.2.1] - 2024-04-27

### Changed
- abit more code readability improvements

### Fixed
- OCA order stickers not displaying on Back Office


## [2.2] - 2023-12-04

### Changed
- code readability improvements

### Fixed
Prestashop 8.0+ compatibility:
- removed  Validate::isPasswd
- changed ajax-tab.php calls to their respective controllers
- removed $smarty.get.token calls in templates
- removed $this->ps_versions_compliancy max version limitation so it can be iinstalled now


## [2.1.1] - 2023-08-21

### Added
- CHANGELOG.md

### Changed

- composer.json php required extensions
- code readability improvements

### Fixed
- Prestashop 8.0 compatibility: removed  Tools::jsonEncode and Tools::jsonDecode
- Prestashop 8.0 compatibility: replace Order->shipping_number for OrderCarrier->tracking_number


## [2.1.0] - 2021-10-23

### Changed
- Fixed multiple Prestashop 1.7.6+ compatibility issues


## [2.0] - 2020-01-18

### Changed
- First open-source release
- Removed built-in support for checkout modules (please ask your checkout module's dev to add support from their end instead) 