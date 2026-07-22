# Laravel Skills

Reusable agent skills for Laravel application design.

This repository provides two complementary skills:

- `laravel-flat-architecture`: decides where business behavior belongs.
- `laravel-dto-value-object`: decides how data should cross application boundaries.

Together, they help Laravel projects stay explicit, searchable, type-friendly, testable, and free from unnecessary abstraction.

---

## Installation

Install all skills from this repository:

```bash
npx skills add chaoswey/skills
```

Install one skill:

```bash
npx skills add chaoswey/skills/laravel-flat-architecture
npx skills add chaoswey/skills/laravel-dto-value-object
```

---

## Skills

| Skill | Focus |
| --- | --- |
| `laravel-flat-architecture` | Controllers, Mutations, Queries, folder structure, and Service alternatives. |
| `laravel-dto-value-object` | DTOs, Value Objects, Entities, named arguments, and boundary-crossing data. |

---

## laravel-flat-architecture

`laravel-flat-architecture` is a lightweight architecture skill for small and medium Laravel projects.

It encourages business operations to live in small, focused classes instead of broad Service or Repository layers:

- `app/Mutations` for write operations.
- `app/Queries` for read-only operations.
- Controllers for HTTP orchestration.
- Project-owned `AsObject` and `AsFake` traits for simple callable objects and test fakes.

Use this skill when you are:

- Writing or reviewing Laravel controller actions.
- Splitting an oversized Service class.
- Deciding whether code belongs in a Controller, Mutation, Query, or Model.
- Designing feature folders for a Laravel application.
- Adding architecture rules for PHPStan.

### Example Prompt

```text
Use laravel-flat-architecture to design an order checkout flow.
Keep the controller thin, place writes in Mutations, reads in Queries,
and avoid introducing a broad OrderService.
```

---

## laravel-dto-value-object

`laravel-dto-value-object` complements `laravel-flat-architecture` by focusing on data shape.

It helps decide when boundary-crossing data should stay as named arguments and when it should become a DTO, Value Object, or Entity.

Use this skill when data moves between:

- Controller and Mutation or Query.
- Mutation or Query and Event.
- Controller or Mutation and Job.
- External API and application code.

Use this skill when you are:

- Passing validated request data into business logic.
- Designing Event payloads.
- Designing Job payloads.
- Modeling external API responses.
- Replacing unclear `array $data` parameters.
- Replacing `$request->all()` usage.

### Example Prompt

```text
Use laravel-dto-value-object to model the validated request data
for creating a subscription. Replace unclear array payloads with
explicit DTOs or Value Objects only where they improve clarity.
```

---

## Recommended Usage

Use both skills together when designing a Laravel feature:

```text
Controller
      |
      v
DTO / Value Object
      |
      v
Mutation / Query
      |
      v
Eloquent Model
```

- `laravel-flat-architecture` decides where behavior belongs.
- `laravel-dto-value-object` decides how data crosses boundaries.

---

## Repository Structure

```text
skills/
├── laravel-flat-architecture/
│   ├── SKILL.md
│   ├── SKILL_zh-TW.md
│   ├── assets/
│   └── references/
│
├── laravel-dto-value-object/
│   ├── SKILL.md
│   └── SKILL_zh-TW.md
│
└── readme.md
```

---

## Design Principles

These skills prefer:

- Thin controllers.
- Small Mutation and Query classes.
- Explicit data boundaries.
- Strong typing where it improves clarity.
- Static-analysis-friendly code.
- Easy tests.
- Minimal abstraction.

---

# Laravel 技能集

可重複使用的 Laravel 應用程式設計 Agent Skills。

此 repository 提供兩個互補的 skills：

- `laravel-flat-architecture`：決定商業邏輯應該放在哪裡。
- `laravel-dto-value-object`：決定資料應該如何跨越應用程式邊界。

兩者搭配可讓 Laravel 專案保持明確、好搜尋、型別友善、容易測試，並避免不必要的抽象層。

---

## 安裝

安裝此 repository 的全部 skills：

```bash
npx skills add chaoswey/skills
```

只安裝單一 skill：

```bash
npx skills add chaoswey/skills/laravel-flat-architecture
npx skills add chaoswey/skills/laravel-dto-value-object
```

---

## 技能列表

| Skill | 重點 |
| --- | --- |
| `laravel-flat-architecture` | Controllers、Mutations、Queries、目錄結構、Service 替代方案。 |
| `laravel-dto-value-object` | DTO、Value Object、Entity、具名參數、跨邊界資料。 |

---

## laravel-flat-architecture

`laravel-flat-architecture` 是適合中小型 Laravel 專案的輕量架構 skill。

它鼓勵將商業操作放在小型、專注的類別中，而不是累積到寬泛的 Service 或 Repository 層：

- `app/Mutations` 放寫入操作。
- `app/Queries` 放唯讀查詢操作。
- Controllers 負責 HTTP 流程協調。
- 專案自有 `AsObject` 與 `AsFake` traits 負責簡單 callable objects 與測試 fake。

適用情境：

- 撰寫或審查 Laravel controller actions。
- 拆分過大的 Service 類別。
- 判斷程式碼應該放在 Controller、Mutation、Query 或 Model。
- 設計 Laravel 應用程式功能目錄。
- 加入 PHPStan 架構規則。

### 範例 Prompt

```text
使用 laravel-flat-architecture 設計訂單結帳流程。
讓 controller 保持單薄，寫入放在 Mutations，讀取放在 Queries，
避免建立過於寬泛的 OrderService。
```

---

## laravel-dto-value-object

`laravel-dto-value-object` 搭配 `laravel-flat-architecture` 使用，重點是資料形狀。

它協助判斷跨越邊界的資料應該維持具名參數，或改成 DTO、Value Object、Entity。

適用於資料在以下邊界間移動時：

- Controller 與 Mutation 或 Query。
- Mutation 或 Query 與 Event。
- Controller 或 Mutation 與 Job。
- 外部 API 與應用程式程式碼。

適用情境：

- 將驗證後 request data 傳入商業邏輯。
- 設計 Event payload。
- 設計 Job payload。
- 建模外部 API 回應。
- 取代不清楚的 `array $data` 參數。
- 取代 `$request->all()` 用法。

### 範例 Prompt

```text
使用 laravel-dto-value-object 建模建立訂閱時的 validated request data。
只在能提升清楚度時，用明確 DTO 或 Value Object 取代模糊 array payload。
```

---

## 建議用法

設計 Laravel 功能時，建議兩個 skills 搭配使用：

```text
Controller
      |
      v
DTO / Value Object
      |
      v
Mutation / Query
      |
      v
Eloquent Model
```

- `laravel-flat-architecture` 決定商業邏輯放在哪裡。
- `laravel-dto-value-object` 決定資料如何跨越邊界。

---

## Repository 結構

```text
skills/
├── laravel-flat-architecture/
│   ├── SKILL.md
│   ├── SKILL_zh-TW.md
│   ├── assets/
│   └── references/
│
├── laravel-dto-value-object/
│   ├── SKILL.md
│   └── SKILL_zh-TW.md
│
└── readme.md
```

---

## 設計原則

這些 skills 偏好：

- Controller 單薄。
- 小型 Mutation 與 Query 類別。
- 明確資料邊界。
- 在能提升清楚度時使用強型別。
- 對靜態分析友善的程式碼。
- 容易測試。
- 最少必要抽象。
