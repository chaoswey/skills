---
name: laravel-dto-value-object
description: >
  在採用 laravel-flat-architecture（Mutation / Query 取代 Service /
  Repository）的 Laravel 專案中，規範跨層傳遞的資料該用 DTO、有商業規則的
  值該用 Value Object、有身份與行為的商業物件該用 Entity，避免到處傳遞無型別
  array（$data['name']）。這個 skill 是 laravel-flat-architecture 的搭配
  規範，術語一律對齊 Mutation / Query（不是 Service / Repository）。只要
  對話涉及 Controller 呼叫 Mutation::run() / Query::run()、要幫 Controller
  組資料傳給 Mutation、討論 Mutation/Query 的參數該怎麼設計、Event payload
  設計、Job payload 設計、或是使用者在寫/審查 Laravel 的 Request→handle()
  資料流程，就要主動套用此 skill，即使使用者沒有明確提到「DTO」三個字。
  特別是：只要看到 Controller 把 request 的 all() 或整包 array 直接丟給
  Mutation::run()/Query::run()，就要觸發並建議改成 DTO。
---

# Laravel DTO / Value Object / Entity（搭配 Flat Architecture）

## 前提

本 skill 假設專案採用 `laravel-flat-architecture`：沒有 Service /
Repository，只有 `app/Mutations`（會動資料庫）與 `app/Queries`（純讀取），
每個類別用 `AsObject` trait 提供的 `::run(...)` 靜態方法呼叫，實際邏輯寫在
`handle()` 裡。

如果對話中同時觸發了 `laravel-flat-architecture`，兩者一起套用：
`laravel-flat-architecture` 負責「這段邏輯該不該獨立成 Mutation/Query」，
本 skill 負責「Controller 傳給 Mutation/Query 的資料該長什麼樣子」。

## 目的

避免 Controller → Mutation/Query → Event/Job 之間大量傳遞無型別 array：

```php
$data['name'];
$data['email'];
$data['started_at'];
```

改用有明確結構的物件：

```php
$data->name;
$data->email;
$data->startedAt;
```

目標：型別安全、IDE 自動補全、重構可靠性、可讀性、PHPStan/Psalm 靜態分析。

---

## 原則

### 可以繼續用 array 的情境

* Blade view data（`return view('home', ['news' => $news])`）
* config 設定
* request query string 的暫存整理
* Laravel collection map/filter 中的暫時資料
* 第三方 API 原始 response（尚未整理前）

### 建議改 DTO 的情境

只要資料要跨越以下任一邊界，就用 DTO，不要直接傳 array：

```text
Controller → Mutation::run()
Controller → Query::run()
Mutation/Query → Event payload
Mutation/Query → Job payload
外部 API Request / Response
表單驗證後傳入 handle() 的商業邏輯
```

---

## 不建議寫法

```php
// Controller
public function store(Request $request)
{
    UpdateEvent::run($request->all());
}
```

```php
// app/Mutations/UpdateEvent.php
final class UpdateEvent
{
    use AsFake, AsObject;

    public function handle(array $data): Event
    {
        $event = Event::findOrFail($data['id']);
        $event->title = $data['title'];
        $event->city = $data['city'];
        $event->save();

        return $event;
    }
}
```

問題：

* 不知道 `$data` 有哪些 key，`handle(array $data)` 的簽名沒有任何資訊量
* key 打錯不容易提前發現，IDE 無法補全
* `::run(...)` 原本的 named arguments 優勢完全被 array 吃掉了
* 重構困難，array 結構沒有文件化

---

## 建議寫法：DTO

```php
// app/Mutations/Data/UpdateEventData.php
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

Controller：

```php
public function store(Request $request)
{
    $data = UpdateEventData::fromRequest($request);

    UpdateEvent::run(data: $data);
}
```

Mutation：

```php
// app/Mutations/UpdateEvent.php
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

也可以不包 DTO，直接用 named arguments 當作「輕量 DTO」——如果欄位不多
（大約 3 個以下）且不會被其他 Mutation/Query 重複使用，這樣也可以接受：

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

判斷基準：欄位少、一次性使用 → named arguments 就夠；欄位多、或會被
Controller/Job/Console Command 重複組出來傳給同一個 Mutation/Query →
獨立成 DTO class，放在 `app/Mutations/Data/` 或 `app/Queries/Data/`。

---

## Value Object 使用情境

某個值本身有規則時，用 Value Object：

```php
final readonly class Email
{
    public function __construct(
        public string $value
    ) {
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

適合 Value Object：Email、Money、DateRange、Phone、TaxId、OrderNumber、
ContractPeriod。

在 DTO 裡使用：

```php
final readonly class CreateOrderData
{
    public function __construct(
        public Email $customerEmail,
        public Money $amount,
    ) {}
}
```

---

## Entity 使用情境

Entity 代表有身份識別、且有自己行為的商業物件。在 Flat Architecture 裡，
Eloquent Model（`Event`、`Order`、`Contract`）本身通常就承擔 Entity 的角色，
不需要再額外包一層。只有當某個商業物件**不對應到單一資料表**、或需要跟
Eloquent 的行為（如 mass assignment、lazy loading）明確切開時，才另外寫
Entity：

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

這種物件通常是某個 `Get*` Query 的回傳值，而不是資料庫模型本身。

---

## 實務規則（對齊 Flat Architecture 的角色）

### Controller

負責：接 Request → 驗證 → 組出 DTO（或決定用 named arguments）→ 呼叫
`Mutation::run()` / `Query::run()` → 處理 HTTP 層的事（redirect、view、
flash message）。Controller 不處理商業邏輯，也不該直接組出散亂 array
丟給 `run()`。

### Mutation

`handle()` 的參數用 DTO 或明確的 named arguments，不要用
`handle(array $data)`：

```php
public function handle(CreateOrderData $data): Order
```

避免：

```php
public function handle(array $data): Order
```

### Query

篩選條件多的時候用 DTO：

```php
public function handle(EventSearchData $filters): Collection
```

避免：

```php
public function handle(array $filters): Collection
```

### Event / Job payload

跟 `laravel-flat-architecture` 的規則一致，儘量用明確欄位或 DTO：

```php
SendRenewNoticeJob::dispatch(
    new RenewalNoticeData(
        customerId: $customer->id,
        contractId: $contract->id,
    )
);
```

---

## 命名建議

DTO（放在對應的 `app/Mutations/Data/` 或 `app/Queries/Data/`）：

```text
CreateEventData
UpdateEventData
EventSearchData
RenewalNoticeData
```

Value Object：

```text
Email
Money
DateRange
PhoneNumber
TaxId
```

Entity（僅限不對應單一資料表、或作為 Query 回傳值的物件）：

```text
ContractSummary
UserEventsSummary
```

---

## 判斷標準

* 資料只在一個 function 裡臨時使用 → array 可以
* 欄位少（≤3）、一次性、不會被重複組出來 → named arguments 就夠，不用特地包 DTO
* 資料會跨越 `Controller → Mutation/Query`、`Mutation/Query → Event`、
  `Mutation/Query → Job`，或欄位多、會被多處重複組出來 → 用 DTO
* 值本身有驗證規則（格式、範圍、單位）→ 用 Value Object
* 商業物件不對應單一 Eloquent Model，且有自己的行為（不只是資料）→ 用 Entity
* 商業物件對應單一 Eloquent Model → 直接用 Model 本身，不用另外包 Entity

---

## 核心結論

不要為了形式而把所有 array 都改成 DTO，也不要因為引入 DTO 又把
`Service` 這個角色請回來——本 skill 全程只用 `Mutation` / `Query`
這兩種角色，DTO/Value Object/Entity 是它們的資料層配件，不是新的
架構層。

```text
跨層傳遞、欄位多或會重複使用的資料 → DTO
有商業規則的值 → Value Object
不對應單一 Model、有自己行為的商業物件 → Entity
臨時資料與 View data → array 可以
```