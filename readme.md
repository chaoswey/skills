# Laravel Skills / Laravel 技能集

This repository contains reusable OpenCode skills for Laravel architecture and data modeling.

聚焦 Laravel 架構設計與跨層資料建模。

## Skills / 技能列表

### [laravel-flat-architecture](laravel-flat-architecture)

**EN:** A lightweight Laravel application structure for small and medium projects. It keeps controllers thin by organizing business operations as focused `Mutation` and `Query` classes, using project-owned `AsObject` and `AsFake` traits instead of `laravel-actions`.

**ZH-TW：** 適合中小型 Laravel 專案的扁平式架構。用專注的小型 `Mutation` / `Query` 類別整理商業操作，讓 Controller 保持單薄，並使用專案自有的 `AsObject`、`AsFake` trait 取代 `laravel-actions`。

Use this skill when working on:

- Laravel controller actions or route handlers
- Service / Repository alternatives
- Oversized service classes
- Mutation / Query folder structure
- Architecture rules enforced by PHPStan

適用情境：

- 撰寫或審查 Laravel Controller / route handler
- 討論是否取代 Service / Repository
- 拆分過大的 Service 類別
- 規劃 Mutation / Query 目錄結構
- 用 PHPStan 做架構規則檢查

### [laravel-dto-value-object](laravel-dto-value-object)

**EN:** A companion skill for Laravel Flat Architecture. It defines when to use named arguments, DTOs, Value Objects, or Entities for data crossing Controller, Mutation, Query, Event, Job, and external API boundaries.

**ZH-TW：** Laravel Flat Architecture 的搭配技能。用來判斷跨越 Controller、Mutation、Query、Event、Job、外部 API 邊界的資料，應該使用 named arguments、DTO、Value Object，或 Entity。

Use this skill when working on:

- Controller input passed into `Mutation::run()` or `Query::run()`
- Validated request data passed into business logic
- Event or Job payload design
- External API request / response modeling
- Replacing unclear `array $data` or `$request->all()` flows

適用情境：

- Controller 將輸入傳給 `Mutation::run()` 或 `Query::run()`
- 表單驗證後的資料要進入商業邏輯
- 設計 Event 或 Job payload
- 建模外部 API request / response
- 取代不清楚的 `array $data` 或 `$request->all()` 流程

## Language Files / 語言檔案

Each skill includes English and Traditional Chinese versions:

每個技能都包含英文與繁體中文版本：

```text
SKILL.md        English version
SKILL_zh-TW.md  Traditional Chinese version
```

## Recommended Usage / 建議使用方式

Use both Laravel skills together when designing new Laravel features:

設計新的 Laravel 功能時，建議兩個技能一起使用：

1. `laravel-flat-architecture` decides where behavior belongs: `Mutation`, `Query`, or controller.
2. `laravel-dto-value-object` decides what shape boundary-crossing data should have: named arguments, DTO, Value Object, or Entity.

1. `laravel-flat-architecture` 判斷行為應該放在 `Mutation`、`Query`，還是留在 Controller。
2. `laravel-dto-value-object` 判斷跨邊界資料應該長什麼樣子：named arguments、DTO、Value Object，或 Entity。

## Design Direction / 設計方向

**EN:** Keep Laravel applications explicit, searchable, type-friendly, and easy to test without adding unnecessary abstraction.

**ZH-TW：** 讓 Laravel 應用保持明確、好搜尋、利於型別分析、容易測試，同時避免不必要的抽象層。
