---
name: laravel-flat-architecture
description: >
  Use when working on Laravel controllers, route handlers, feature structure,
  Service/Repository alternatives, or oversized Service classes. Apply this
  skill to keep controllers thin by organizing business operations as small
  Mutation and Query classes, using project-owned AsObject and AsFake traits
  instead of laravel-actions.
---

# Laravel Flat Architecture

## Description

Laravel Flat Architecture is a lightweight application structure for small and
medium Laravel projects. It replaces broad `Service` and `Repository` layers
with focused `Mutation` and `Query` classes.

The goal is not to add more abstraction. The goal is to keep each operation
small, named, testable, and easy to locate:

- `app/Mutations` contains operations that create, update, or delete data.
- `app/Queries` contains read-only operations.
- Controllers stay close to HTTP concerns.
- Each Mutation or Query exposes a `::run(...)` entry point and keeps its logic
  in `handle(...)`.

This pattern is inspired by GraphQL terminology, but it does not require
GraphQL and does not depend on third-party action packages.

## When to Apply

Apply this skill when the task involves any of the following:

- Writing or reviewing a Laravel controller action.
- Deciding where business logic should live.
- Replacing or avoiding `Service` / `Repository` layers.
- Splitting an oversized class such as `OrderService`.
- Designing a folder structure for a Laravel project.
- Seeing Eloquent calls inside controllers, such as `save`, `update`,
  `delete`, `create`, `where`, `find`, `first`, or `get`.

If a project already has many Services or Repositories, do not suggest a large
rewrite by default. Prefer using Flat Architecture for new work first, then
migrating old code only when there is a clear reason.

## Core Rules

### 1. Use project-owned traits, not laravel-actions

Do not install `laravel-actions` for this pattern. Copy the provided traits
from this skill into the project if they do not already exist:

```text
assets/AsObject.php -> app/Concerns/AsObject.php
assets/AsFake.php   -> app/Concerns/AsFake.php
```

Every Mutation and Query should use both traits:

```php
use AsFake, AsObject;
```

`AsObject` provides static entry points such as `::make()`, `::run()`,
`::runIf()`, and `::runUnless()`. Internally, it resolves the class from the
Laravel container and calls `handle(...)`.

`AsFake` provides test helpers such as `::mock()`, `::spy()`, `::shouldRun()`,
`::shouldNotRun()`, and `::allowToRun()`. Use these helpers when testing code
that depends on a Mutation or Query without running its real implementation.

Callers should use `ClassName::run(...)`. Do not instantiate Mutation or Query
classes directly with `new` from application code.

### 2. Create two top-level operation folders

Use these folders:

```text
app/Mutations
app/Queries
```

Do not recreate a generic `Services` layer under another name.

### 3. Classify operations by write behavior

Use a Mutation when the operation changes persistent state:

```text
CreateEvent
UpdateEvent
DeleteEvent
PublishOrder
CancelSubscription
```

Use a Query when the operation only reads data:

```text
GetEvent
GetUserEventsWithPagination
GetActiveOrdersForUser
```

One class should represent one operation. Prefer many small files over a few
large God objects.

### 4. Keep controllers thin, but do not extract blindly

Controllers should coordinate HTTP concerns:

- Read and validate the request.
- Authorize access.
- Call a Mutation or Query.
- Choose a redirect, response, view, or flash message.

Simple Eloquent code may stay in a controller when it is obvious at a glance:
roughly 1-5 lines, no branches, no loops, no business rules, and no cross-model
coordination.

Acceptable example:

```php
$event = Event::where('id', $id)->first();
$event->fill($request->only(['title', 'city']));
$event->save();
```

Move logic into a Mutation or Query when any of these appear:

- `if` / `else`, loops, or multiple conditions.
- Multiple models or tables.
- Business rules beyond request validation and authorization.
- External integrations such as payment providers.
- Logic reused by API controllers, jobs, console commands, or other callers.
- Database transactions.

Use the 3-second test: if a reader cannot understand the intent in a few
seconds, extract it.

### 5. Share behavior without rebuilding Services

When several Mutations or Queries share logic, prefer one of these:

- A parent class for a narrow family of related operations.
- A trait for a reusable behavior.

Do not move shared behavior into a broad `Service` class that grows into the
same God object this architecture is meant to avoid.

### 6. Keep tests aligned with operations

Use one test file per Mutation or Query where practical. A simple coverage
check can scan `app/Mutations` and `app/Queries` and verify that matching test
files exist under `tests/`.

### 7. Use PHPStan as a strict architecture guard

The provided `assets/phpstan.neon` can block direct Eloquent calls in
controllers through `disallowedMethodCalls`. This is intentionally stricter
than human review.

PHPStan can detect method calls, but it cannot judge whether a 1-5 line
controller operation is obvious enough to keep. If the team enables this rule
and wants to keep a deliberate simple exception, add a local
`// @phpstan-ignore-next-line` comment with a reason. Do not weaken the global
rule to allow broad exceptions.

This check expects `larastan/larastan` and `spaze/phpstan-disallowed-calls` to
be installed.

## Examples

### Controller shape

Controllers should mostly read like orchestration:

```php
public function update(UpdateEventRequest $request, Event $event)
{
    $this->authorize('update', $event);

    UpdateEvent::run(
        event: $event,
        title: $request->string('title')->toString(),
        city: $request->string('city')->toString(),
    );

    return redirect()->route('events.show', $event)
        ->with('status', 'Event updated.');
}
```

Full controller example: `references/controller-example.php`.

### Mutation / Query shape

```php
final class UpdateEvent
{
    use AsFake, AsObject;

    public function handle(Event $event, string $title, string $city): Event
    {
        $event->title = $title;
        $event->city = $city;
        $event->save();

        return $event;
    }
}
```

Full Mutation and Query examples: `references/mutation-query-example.php`.

## Naming Convention

- Mutations: verb + noun, such as `CreateEvent`, `UpdateEvent`,
  `DeleteEvent`, `PublishOrder`, or `CancelSubscription`.
- Queries: start with `Get`, such as `GetEvent`,
  `GetUserEventsWithPagination`, or `GetActiveOrdersForUser`.
- Call sites: always use `ClassName::run(...)`.
- Arguments: prefer named arguments for readability.
- Implementation method: put real logic in `handle(...)`.

## References

- Traits: `assets/AsObject.php`, `assets/AsFake.php`.
- PHPStan architecture guard: `assets/phpstan.neon`.
- Controller example: `references/controller-example.php`.
- Mutation / Query example: `references/mutation-query-example.php`.
- Original article by howtomakeaturn: https://codelove.tw/@howtomakeaturn/post/xd0Ykx
- Example project:
  - Mutations: https://yii.tw/app/Mutations
  - Queries: https://yii.tw/app/Queries
  - Architecture repo: https://github.com/howtomakeaturn/laravel-flat-architecture
