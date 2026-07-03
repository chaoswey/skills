---
name: laravel-dto-value-object
description: >
  Use when designing data that crosses Laravel Controller, Mutation, Query,
  Event, Job, or external API boundaries in a project that follows Laravel Flat
  Architecture. Apply this skill to replace untyped arrays with DTOs, Value
  Objects, or Entities where they improve type safety, readability, IDE support,
  and static analysis.
---

# Laravel DTO, Value Objects and Entities

## Description

This skill complements `laravel-flat-architecture`.

Laravel Flat Architecture decides where behavior belongs: `Mutation` for writes,
`Query` for reads, and controllers for HTTP orchestration. This skill decides
what shape data should have when it moves across those boundaries.

The main rule is simple: do not pass vague arrays through business boundaries.

Avoid this:

```php
$data['name'];
$data['email'];
$data['started_at'];
```

Prefer explicit objects or explicit named arguments:

```php
$data->name;
$data->email;
$data->startedAt;
```

Use DTOs, Value Objects, and Entities to make data structures visible to the
reader, the IDE, and PHPStan or Psalm.

## When to Apply

Apply this skill when the task involves any of these boundaries:

```text
Controller -> Mutation::run()
Controller -> Query::run()
Mutation/Query -> Event payload
Mutation/Query -> Job payload
External API request / response
Validated form input passed into business logic
```

Also apply it when reviewing code that passes `$request->all()`, `array $data`,
or `array $filters` into a Mutation or Query.

If `laravel-flat-architecture` also applies, use both skills together:

- `laravel-flat-architecture` decides whether behavior should become a Mutation
  or Query.
- This skill decides whether the data passed into that Mutation or Query should
  be named arguments, a DTO, a Value Object, or an Entity.

## Core Rules

### 1. Arrays are fine for local and framework-shaped data

Do not turn every array into a class. Arrays are still appropriate for:

- Blade view data, such as `return view('home', ['news' => $news])`.
- Configuration values.
- Temporary query-string normalization.
- Temporary values inside collection `map` / `filter` callbacks.
- Raw third-party API responses before they are normalized.

Use classes when data crosses a meaningful application boundary.

### 2. Do not pass request arrays into Mutations or Queries

Avoid controller code like this:

```php
public function store(Request $request)
{
    UpdateEvent::run($request->all());
}
```

Avoid Mutation or Query signatures like this:

```php
public function handle(array $data): Event
```

Problems:

- The signature does not document required fields.
- Misspelled keys fail late.
- IDE autocompletion cannot help.
- Static analysis cannot verify the structure.
- The benefits of `::run(...)` named arguments are lost.

### 3. Use named arguments for small one-off inputs

If an operation has only a few fields, is used once, and does not need to be
shared by controllers, jobs, or commands, named arguments are often enough:

```php
UpdateEvent::run(
    event: $event,
    title: $request->string('title')->toString(),
    city: $request->string('city')->toString(),
);
```

```php
public function handle(Event $event, string $title, string $city): Event
{
    $event->title = $title;
    $event->city = $city;
    $event->save();

    return $event;
}
```

Use this as a lightweight DTO when the input is small, obvious, and not reused.

### 4. Use DTOs for boundary-crossing structured data

Create a DTO when data has several fields, is reused, or crosses boundaries.

Example DTO:

```php
final readonly class UpdateEventData
{
    public function __construct(
        public int $id,
        public string $title,
        public string $city,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            id: (int) $request->route('event'),
            title: $request->string('title')->toString(),
            city: $request->string('city')->toString(),
        );
    }
}
```

Controller:

```php
public function store(Request $request)
{
    UpdateEvent::run(data: UpdateEventData::fromRequest($request));
}
```

Mutation:

```php
final class UpdateEvent
{
    use AsFake, AsObject;

    public function handle(UpdateEventData $data): Event
    {
        $event = Event::findOrFail($data->id);
        $event->title = $data->title;
        $event->city = $data->city;
        $event->save();

        return $event;
    }
}
```

Recommended locations:

```text
app/Mutations/Data
app/Queries/Data
```

### 5. Use Value Objects for values with rules

Use a Value Object when a single value has validation rules, formatting rules,
units, ranges, or domain meaning.

Example:

```php
final readonly class Email
{
    public function __construct(public string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

Good Value Object candidates:

```text
Email
Money
DateRange
PhoneNumber
TaxId
OrderNumber
ContractPeriod
```

Use Value Objects inside DTOs when the field itself has business meaning:

```php
final readonly class CreateOrderData
{
    public function __construct(
        public Email $customerEmail,
        public Money $amount,
    ) {}
}
```

### 6. Use Entities only for business objects with identity and behavior

In Laravel applications, Eloquent models usually already act as Entities. Do
not wrap `Event`, `Order`, or `Contract` in another Entity class just for form.

Create a separate Entity only when the object:

- Does not map cleanly to a single database table, or
- Must be isolated from Eloquent behavior such as mass assignment or lazy
  loading, and
- Has behavior of its own, not only data fields.

Example Query return object:

```php
final class ContractSummary
{
    public function __construct(
        public int $id,
        public string $customerName,
        public CarbonImmutable $startedAt,
        public CarbonImmutable $endedAt,
        public int $total,
    ) {}

    public function isActive(): bool
    {
        return now()->between($this->startedAt, $this->endedAt);
    }

    public function remainingValue(): float
    {
        $totalDays = $this->startedAt->diffInDays($this->endedAt);
        $remainingDays = now()->diffInDays($this->endedAt);

        return $this->total * ($remainingDays / $totalDays);
    }
}
```

This kind of object is usually returned by a `Get*` Query. It is not a database
model replacement by default.

## Examples

### Controller responsibility

A controller may build a DTO, call a Mutation or Query, then return an HTTP
response:

```php
public function update(UpdateEventRequest $request, Event $event)
{
    $data = UpdateEventData::fromRequest($request);

    UpdateEvent::run(event: $event, data: $data);

    return redirect()->route('events.show', $event);
}
```

The controller should not build a loose array and pass it across the boundary.

### Mutation signature

Prefer this:

```php
public function handle(CreateOrderData $data): Order
```

Avoid this:

```php
public function handle(array $data): Order
```

### Query filters

When filters are complex, use a DTO:

```php
public function handle(EventSearchData $filters): Collection
```

Avoid vague filter arrays:

```php
public function handle(array $filters): Collection
```

### Event and Job payloads

Prefer explicit fields or DTO payloads:

```php
SendRenewNoticeJob::dispatch(
    new RenewalNoticeData(
        customerId: $customer->id,
        contractId: $contract->id,
    )
);
```

## Naming Convention

DTO names should describe the operation or boundary:

```text
CreateEventData
UpdateEventData
EventSearchData
RenewalNoticeData
```

Value Object names should describe the value itself:

```text
Email
Money
DateRange
PhoneNumber
TaxId
```

Entity names should describe a business object or projection with identity and
behavior:

```text
ContractSummary
UserEventsSummary
```

## Decision Guide

- Data used only inside one function: array is fine.
- Three or fewer fields, one-off use, no reuse: named arguments are enough.
- Data crosses Controller, Mutation, Query, Event, Job, or external API
  boundaries: use a DTO.
- Data has validation rules, ranges, formats, units, or business meaning: use a
  Value Object.
- Business object maps to one Eloquent model: use the model.
- Business object does not map to one model and has its own behavior: use an
  Entity.

## References

- Companion skill: `laravel-flat-architecture`.
- Flat Architecture folders: `app/Mutations`, `app/Queries`.
- DTO folders: `app/Mutations/Data`, `app/Queries/Data`.
- Trait convention: Mutation and Query classes use `AsFake` and `AsObject`, and
  expose behavior through `::run(...)` and `handle(...)`.
