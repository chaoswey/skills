# Laravel Skills / Laravel 技能集

Reusable OpenCode Skills for designing Laravel applications with **Flat Architecture** and **explicit data modeling**.

可重複使用的 OpenCode Skills，協助以 **Flat Architecture** 與 **明確的資料建模** 設計 Laravel 應用程式。

---

## What this repository provides / Repository 目的

These skills focus on two complementary aspects of Laravel application design:

本 Repository 提供兩個互補的 Laravel 設計技能：

- **laravel-flat-architecture**
    - Defines **where business behavior belongs**.
    - 決定商業邏輯應該放在哪裡。

- **laravel-dto-value-object**
    - Defines **how data crosses application boundaries**.
    - 決定跨層資料應該如何建模。

Together they help keep Laravel applications:

兩者搭配可讓 Laravel 專案保持：

- Explicit / 明確
- Searchable / 好搜尋
- Type-safe / 型別友善
- Easy to test / 易於測試
- Without unnecessary abstraction / 避免過度抽象

---

## Architecture Overview

```text
Controller
      │
      ▼
DTO / Value Object
      │
      ▼
Mutation / Query
      │
      ▼
Eloquent Model
```

- **Flat Architecture** decides where behavior lives.
- **DTO / Value Object** decides how data crosses boundaries.

- **Flat Architecture** 決定行為放在哪裡。
- **DTO / Value Object** 決定資料如何跨越系統邊界。

---

# Skills / 技能列表

## laravel-flat-architecture

**EN**

A lightweight Laravel architecture for small and medium-sized projects.

Instead of growing large Service classes, business logic is organized into small, focused **Mutation** and **Query** classes, keeping Controllers thin and easy to understand.

Examples use lightweight project-owned `AsObject` and `AsFake` traits instead of depending on `laravel-actions`.

**ZH-TW**

適合中小型 Laravel 專案的輕量 Flat Architecture。

將商業邏輯拆分成單一職責的 **Mutation** / **Query** 類別，而不是累積到大型 Service，讓 Controller 保持單純且容易閱讀。

範例採用專案自有的 `AsObject`、`AsFake` trait，不依賴 `laravel-actions`。

### Use this skill when

- Designing Controller actions
- Replacing oversized Service classes
- Evaluating Service vs Mutation / Query
- Organizing application folders
- Enforcing architecture rules with PHPStan

### 適用情境

- 撰寫或審查 Controller
- 拆分大型 Service
- 評估是否使用 Mutation / Query
- 規劃目錄結構
- 使用 PHPStan 驗證架構規則

---

## laravel-dto-value-object

**EN**

A companion skill for Laravel Flat Architecture.

It helps determine whether boundary-crossing data should be modeled as:

- Named arguments
- DTO
- Value Object
- Entity

Applicable across:

- Controller
- Mutation
- Query
- Event
- Job
- External API

**ZH-TW**

Laravel Flat Architecture 的搭配技能。

協助判斷跨越系統邊界的資料應使用：

- Named arguments
- DTO
- Value Object
- Entity

適用於：

- Controller
- Mutation
- Query
- Event
- Job
- 外部 API

### Use this skill when

- Passing validated request data
- Designing Event payloads
- Designing Job payloads
- Modeling external APIs
- Replacing unclear `array $data`
- Replacing `$request->all()`

### 適用情境

- 驗證後資料進入商業邏輯
- 設計 Event Payload
- 設計 Job Payload
- 建模外部 API
- 取代 `array $data`
- 取代 `$request->all()`

---

## Repository Structure

```text
skills/
├── laravel-flat-architecture/
│   ├── SKILL.md
│   └── SKILL_zh-TW.md
│
└── laravel-dto-value-object/
    ├── SKILL.md
    └── SKILL_zh-TW.md
```

---

## Recommended Usage

Use both skills together when designing new Laravel features.

| Skill | Responsibility |
|--------|----------------|
| laravel-flat-architecture | Decide where business behavior belongs |
| laravel-dto-value-object | Decide how boundary-crossing data should be modeled |

---

## Design Philosophy

We prefer:

- Thin Controllers
- Small Mutation / Query classes
- Explicit data boundaries
- Strong typing
- Easy static analysis
- Easy testing
- Minimal abstraction

---

## 設計理念

本 Repository 希望 Laravel 專案能保持：

- Controller 保持單薄
- Mutation / Query 單一職責
- 明確的資料邊界
- 型別友善
- 容易進行 PHPStan 靜態分析
- 容易測試
- 避免不必要的抽象層