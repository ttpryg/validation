# Validation (`ttpryg/validation`)

A framework-agnostic validation layer for PHP 8.1+ that wraps the Symfony
Validator with a Laravel-style string rule syntax. It accepts arrays or
objects, understands both `"email|required|min:6"` rule strings and native
Symfony `Constraint` instances, and throws a single
`ValidationException` whose errors are grouped per field.

---

## ✨ Features

- **Laravel-Style Rules**: Compact pipe-separated rule strings with
  parameters (`min:8`, `in:a,b,c`, `regex:/^[A-Z]+$/`).
- **Native Constraints**: Pass Symfony `Constraint` objects per field, or one
  whole `Collection` (or any other) constraint for full control.
- **Object / DTO Support**: Attribute-mapped classes validate through their
  PHP attributes (`#[Assert\NotBlank]`, `#[Assert\Email]`, ...) — the
  validated object is returned unchanged.
- **Grouped Errors**: `getErrors()` returns `field => [messages...]`, ready to
  serialize into a JSON error payload.
- **Framework Agnostic**: No Slim, PSR-7, or `App\*` dependency. Error
  handlers can type-check the framework-neutral
  `ValidationExceptionInterface` (`getErrors()` / `getStatusCode()`).

---

## 📦 Installation

```bash
composer require ttpryg/validation
```

> **Requirements:** PHP 8.1+ and `symfony/validator` ^6.4 || ^7.0 (installed
> automatically).

---

## 🚀 Usage

```php
use Ttpryg\Validation\Validator;

$validator = new Validator();

$validated = $validator->validate($request->getParsedBody(), [
    'name' => 'required|min:3|max:255',
    'email' => 'required|email',
    'password' => 'required|min:8',
]);
```

`validated()` is an alias of `validate()`; both return the validated data and
throw `Ttpryg\Validation\Exception\ValidationException` on failure.

You can also inject your own Symfony validator (custom groups, locale,
cache, property info):

```php
new Validator($symfonyValidatorInterface);
```

### 1. String rules

```php
$validator->validate($data, [
    'email' => 'required|email',
    'role' => 'required|in:admin,editor,viewer',
    'code' => 'regex:/^[A-Z]{4}$/',
]);
```

### 2. Constraint instances per field

Any `Symfony\Component\Validator\Constraint`, single or in an array:

```php
use Symfony\Component\Validator\Constraints as Assert;

$validator->validate($data, [
    'age' => [new Assert\NotBlank, new Assert\Type('integer'), new Assert\GreaterThanOrEqual(18)],
    'email' => new Assert\Email,
]);
```

`NotBlank` in the list marks the field as required, exactly like the
`required` string rule.

### 3. One raw `Constraint` for the whole payload

```php
use Symfony\Component\Validator\Constraints as Assert;

$validator->validate($data, new Assert\Collection([
    'fields' => ['email' => new Assert\Optional([new Assert\Email])],
    'allowExtraFields' => true,
]));
```

### 4. Objects and DTOs

```php
final class RegisterRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public string $password,
    ) {}
}

$request = $validator->validate(new RegisterRequest($email, $password));
```

The default validator is built with `enableAttributeMapping()`, so attribute
constraints on the class are applied automatically.

---

## 📐 Return-value contract (key stripping)

The shape of the return value depends on how the rules were passed:

| Rules given as | Result |
| --- | --- |
| `array<string, mixed>` (string or Constraint rules) | `array_intersect_key($data, $rules)`: **only the keys present in the rule array are returned**; extra input keys are dropped. This is the whitelist / mass-assignment guard. |
| A single `Constraint` (e.g. `Assert\Collection`) | The input array is returned unchanged, including extra keys, because the package cannot know the field whitelist. |
| An object | The same object instance is returned unchanged. |

```php
$data = ['email' => 'a@b.test', 'is_admin' => true];

$validator->validate($data, ['email' => 'required|email']);
// => ['email' => 'a@b.test']  — 'is_admin' removed
```

Validation itself always allows extra fields (`allowExtraFields => true`), so
unlisted keys never fail validation — they are simply not returned.

---

## ✅ Supported string rules

| Rule | Symfony constraint | Notes |
| --- | --- | --- |
| `required`, `not_blank` | `NotBlank` | Field also becomes `Required` (missing field fails). |
| `email` | `Email` | |
| `min:N` | `Length(min: N)` | String length semantics. |
| `max:N` | `Length(max: N)` | |
| `length:N` | `Length(exactly: N)` | |
| `string`, `type:string` | `Type('string')` | |
| `int`, `integer`, `type:int`, `type:integer` | `Type('integer')` | |
| `bool`, `boolean`, `type:bool`, `type:boolean` | `Type('boolean')` | |
| `array`, `type:array` | `Type('array')` | |
| `numeric` | `Type('numeric')` | |
| `url` | `Url` | |
| `uuid` | `Uuid` | |
| `json` | `Json` | |
| `regex:/pattern/` | `Regex` | Pattern must include its delimiters. |
| `in:a,b,c` | `Choice` | Comma-separated choices. |

Unknown rule names, and rules without a required parameter (for example a bare
`min`), are ignored; a rule string is split on `|`, so `|` cannot appear inside
a `regex` pattern. Use a `Constraint` instance instead when you need anything
else.

Rules without `required`/`not_blank` are wrapped in `Assert\Optional`: a
missing key passes, while a present but empty value is still validated. Rules
with `required` are wrapped in `Assert\Required`, so a missing key fails with
`This field is missing.`

---

## 🧨 Errors

```php
use Ttpryg\Validation\Exception\ValidationException;
use Ttpryg\Validation\Exception\ValidationExceptionInterface;

try {
    $validator->validate($data, $rules);
} catch (ValidationExceptionInterface $e) {
    $e->getErrors();    // ['email' => ['This value should not be blank.'], ...]
    $e->getStatusCode(); // 422
}
```

Catch `ValidationExceptionInterface` in HTTP error handlers to stay decoupled
from the concrete class. `ValidationException` extends `\RuntimeException` and
its `getCode()` is `422`.

---

## 🧪 Development

```bash
composer install
composer test
composer fix:check
```

---

## 📄 License

MIT License.
