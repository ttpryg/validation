# Changelog

All notable changes to `ttpryg/validation` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial implementation: `Ttpryg\Validation\Validator`, a framework-agnostic
  validation facade over the Symfony Validator with a Laravel-style
  `"email|required|min:6"` rule-string parser (`validate()` and its
  `validated()` alias).
- `validate()` accepts `array|object` data and `array|Constraint` rules:
  pipe-separated rule strings, one or more Symfony `Constraint` instances per
  field, or a single raw `Constraint` (such as `Assert\Collection`) for the
  whole payload.
- Object/DTO validation through Symfony attribute mapping
  (`enableAttributeMapping()`), returning the same instance on success.
- A documented public contract for the return value: with array-style rules
  the result is `array_intersect_key($data, $rules)`, so keys that are not
  declared in the rule list are stripped, while extra fields never fail
  validation (`allowExtraFields => true`).
- Supported string rules: `required`/`not_blank`, `email`, `min`, `max`,
  `length`, `string`, `int`/`integer`, `bool`/`boolean`, `array`, `numeric`,
  `url`, `uuid`, `json`, `regex`, `in`, and the `type:*` aliases. Rules marked
  required are wrapped in `Assert\Required`, all others in `Assert\Optional`.
- Exceptions: the `ExceptionInterface` root marker, the framework-neutral
  `ValidationExceptionInterface` (`getErrors()` / `getStatusCode()`) so HTTP
  error handlers can react without depending on this package, and
  `ValidationException` (extends `\RuntimeException`, code and status `422`)
  carrying errors grouped as `field => [messages...]`.
- Test suite (20 tests) covering array rules, Constraint rules, raw
  collection constraints, attribute-mapped DTOs, key stripping, required vs
  optional semantics, and the exception contract, plus Rector, Pint,
  PHPUnit 10, and a GitHub Actions workflow for PHP 8.1–8.3.
