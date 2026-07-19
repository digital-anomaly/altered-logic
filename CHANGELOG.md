# Changelog

All notable changes to `digital-anomaly/altered-logic` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).




## [0.1.3] - 2026-07-20

### Added
- Added the ability to override the credentials used at call time, via `->credentials(…)` on `Modex`, `Embed`, `EmbedDefer`, `DocStore`, `DocStoreDefer` and `DocSearch`. Accepts a registered credentials name to apply to every provider, or a map of provider name => credentials name.
- Added `CredentialsException`, thrown when the override input's shape is invalid, and when a matched override names credentials that aren't registered.
- Deferred embeddings and documents are now batched by their credentials override, so requests made with different credentials are sent in separate batches, each under its own key.



## [0.1.2] - 2026-04-22



## [0.1.1] - 2025-12-01



## [0.1.0] - 2025-09-15

### Added
- Initial release
