---
name: laravel-flat-architecture
description: >
  Laravel 中小型專案的程式碼組織架構，用「Mutation / Query」取代傳統的
  「Service / Repository」，讓 Controller 保持單薄乾淨。不依賴任何第三方
  套件（例如 laravel-actions），而是用專案自己寫的兩個 trait（AsObject、
  AsFake）讓每個 Mutation/Query 類別擁有 ::run() 呼叫方式與測試用的
  mock/spy 能力。只要對話涉及 Laravel 的 Controller（新增/修改 Controller
  方法、審查 Controller 的 PR、討論業務邏輯該放哪裡、抱怨 Service 類別或
  OrderService 太肥大、討論 Repository pattern、或是要幫 Laravel 專案設計
  新架構/資料夾結構），務必主動套用此 skill 的規則，即使使用者沒有明確說出
  「架構」兩個字。特別是：只要看到或要寫 Laravel Controller 的程式碼，就要
  觸發並檢查是否有邏輯洩漏（save/update/delete/where/find/first/get 等
  Eloquent 呼叫）到 Controller 裡。
---

# Laravel Flat Architecture（扁平式架構）

## 這個 skill 解決什麼問題

傳統 Laravel 社群常見的 Service + Repository 架構，長期下來容易出現兩個問題：

1. **邊界模糊**：程式碼該放 Service、Repository 還是 Controller 說不清楚，
   久了就到處都是。
2. **檔案肥大速度跟不上程式碼成長速度**：Service/Repository 檔案數成長慢，
   但程式碼行數成長快，兩三年後常常出現像 `OrderService.php` 破千行的
   God Object。

Laravel Flat Architecture 的核心想法（受 GraphQL 的 Query/Mutation 概念
啟發，但不是 GraphQL）：**把每一件事拆成一個獨立的 Mutation 或 Query 類別**。
不依賴第三方套件（例如 laravel-actions），而是專案自己維護兩個小 trait
（`AsObject`、`AsFake`，見下方規則 1），讓每個 Mutation/Query 類別都能用
`::run(...)` 呼叫、測試時也能輕鬆 mock/spy，同時保持專案自然變扁平——檔案
很多，但每個檔案都很小（通常不超過兩三百行）。

## 核心規則

1. **不要安裝 laravel-actions，改用專案自己的兩個 trait**：把
   `assets/AsObject.php` 和 `assets/AsFake.php` 複製到
   `app/Concerns/AsObject.php` 與 `app/Concerns/AsFake.php`（如果專案
   還沒有）。
   - `AsObject`：提供 `::make()` / `::run()` / `::runIf()` /
     `::runUnless()`，讓類別可以用靜態方法呼叫，內部透過 Laravel
     container resolve 出實例再呼叫 `handle()`。
   - `AsFake`：提供 `::mock()` / `::spy()` / `::shouldRun()` /
     `::shouldNotRun()` / `::allowToRun()`，讓測試其他程式碼時可以把
     這個 Mutation/Query 假掉，不用真的跑它的邏輯。
   每個 Mutation/Query 類別開頭都寫 `use AsFake, AsObject;`，實際邏輯
   放在 `handle()` 方法裡，呼叫端一律用 `ClassName::run(...)`，不要
   `new` 出來直接呼叫。完整範例見
   `references/mutation-query-example.php`。

2. **建立兩個資料夾**：`app/Mutations` 和 `app/Queries`

3. **依「是否寫入資料庫」分類每個新功能**：
   - 會更新/新增/刪除資料庫資料 → 寫成一個 **Mutation** 類別
   - 純粹讀取、不動資料庫 → 寫成一個 **Query** 類別
   - 一個類別只做一件事，命名用動詞開頭、清楚表達意圖，例如
     `UpdateEvent`、`DeleteEvent`、`GetUserEventsWithPagination`、`GetEvent`

4. **Controller 只負責「呼叫 Mutation/Query + 處理 HTTP 層的事」**
   （例如：驗證權限、決定 redirect 或回傳哪個 view、處理 flash message）。
   當 Controller 方法裡出現 `Model::save()` / `update()` / `delete()` /
   `create()` / `Builder::where()` / `find()` / `first()` / `get()`，先判斷
   這段呼叫是不是「一眼就能看穿的簡單操作」：

   - **可以留在 Controller**：整段操作大約 1~5 行、沒有分支判斷、沒有額外
     的商業邏輯或驗證，單純是「查一筆、填欄位、存檔」這種一看就懂的動作。
     例如：
     ```php
     $event = Event::where('id', $id)->first();
     $event->fill($request->only(['title', 'city']));
     $event->save();
     ```
     這種寫法脈絡很清楚，硬拆成一個 Mutation/Query 類別反而增加不必要的
     間接層，不需要搬。

   - **應該搬進 Mutation/Query**：只要出現下列任一情況，就代表邏輯已經
     超出「一眼看懂」的範圍，建議抽出去：
     - 有 if/else、迴圈、多個條件判斷
     - 牽涉多個 Model 或多張表（例如同時更新訂單又扣庫存）
     - 有驗證、授權以外的商業規則（例如折扣計算、狀態機轉換、金流串接）
     - 之後很可能會被其他地方重複呼叫（例如 API、Job、Console Command
       都要用到同一段邏輯）
     - 需要包在 DB transaction 裡

   判斷基準是「這段程式碼的意圖，讀者掃過去 3 秒內能不能看懂」，而不是
   死板地看有沒有出現某個方法名稱。目的是保持 Controller 乾淨，不是為了
   拆而拆。

5. **共用邏輯不代表要走回 Service 老路**：如果多個 Mutation/Query 有共用
   邏輯，優先考慮：
   - 建一個 Parent 類別讓其他 Mutation/Query 繼承，或
   - 用 Trait 把共用動作抽成獨立檔案，讓其他類別 `use`
   這樣依然維持扁平、每個檔案小而專一的精神，不會退化回一個肥大的
   Service 類別。

6. **測試對應關係單純**：一個 Mutation 對應一個測試檔、一個 Query 對應
   一個測試檔即可。可以寫一支簡單的覆蓋率檢查腳本，掃描
   `app/Mutations` 與 `app/Queries` 下的每個檔案，確認 `tests/` 底下
   有沒有對應的測試檔案。

7. **善用 PHPStan 做架構檢查（但這是刻意從嚴的工具，不代表寫程式碼時的
   判斷標準）**：用 `disallowedMethodCalls` 規則擋掉 Controller 內直接
   呼叫 Eloquent 的 save/update/delete/where/find/first/get，寫入
   CI/CD，讓明顯不符合架構的 PR 直接紅燈擋下。完整設定檔見
   `assets/phpstan.neon`，可直接複製到專案根目錄使用（記得確認專案已安裝
   `larastan/larastan` 和 `spaze/phpstan-disallowed-calls`）。

   注意：PHPStan 只能做「有沒有呼叫某個方法」這種死板的檢查，沒辦法判斷
   「這段程式碼是不是一眼看懂的簡單操作」，所以規則本身沒辦法把規則 4
   的「1~5 行、無分支可留在 Controller」寫進設定檔裡。如果團隊要開這個
   CI 規則，但又想保留規則 4 的彈性，做法是：規則維持全面禁止，遇到確認
   過的簡單例外，在該行程式碼上方加 `// @phpstan-ignore-next-line` 並附
   一句理由，而不是修改 phpstan.neon 放寬規則本身。這樣每個例外都留下
   明確紀錄，方便 Code Review 追蹤，`assets/phpstan.neon` 裡也附了範例
   寫法。

## 什麼時候該主動套用這個 skill

- 使用者要求寫一個新的 Laravel Controller action / route handler
- 使用者請你 review 一段 Laravel Controller 程式碼
- 使用者的 Controller 裡出現了 `->save()`、`->update()`、`->delete()`、
  `->where()`、`::find()`、`->first()`、`->get()` 等呼叫
- 使用者在討論「這段邏輯該放哪裡」「要不要建 Service」「Repository 怎麼設計」
- 使用者抱怨某個 Service 類別太肥大、難維護
- 使用者要規劃一個新 Laravel 專案的資料夾結構

遇到以上情境，主動建議並套用 Mutation/Query 的寫法，而不是預設走
Service + Repository 的老路。如果專案裡已經有大量 Service/Repository，
也可以溫和地提出：新功能先用 Flat Architecture 寫，舊的等有空再視情況遷移，
不需要一次全部重構。

## Controller 範例（正確寫法）

完整範例見 `references/controller-example.php`，重點示範：Controller 方法
簡潔到幾乎只剩下「呼叫 Query/Mutation → 做權限檢查 → 決定回傳什麼」。

## Mutation / Query 類別寫法

完整範例見 `references/mutation-query-example.php`，示範一個 Query 類別
（`GetExistingProductRelationIds`）和一個 Mutation 類別（`UpdateEvent`）
怎麼寫 `use AsFake, AsObject;` + `handle()`，以及測試時如何用 `AsFake`
提供的能力把它 mock 掉。

## 命名慣例

- Mutation：動詞 + 名詞，例如 `CreateEvent`、`UpdateEvent`、`DeleteEvent`、
  `PublishOrder`、`CancelSubscription`
- Query：`Get` 開頭，例如 `GetEvent`、`GetUserEventsWithPagination`、
  `GetActiveOrdersForUser`
- 呼叫方式統一用 `::run(...)` 靜態方法（由專案自己的 `AsObject` trait
  提供，見規則 1），參數用 named arguments 讓呼叫端可讀性更高（例如
  `UpdateEvent::run(event: $event, title: $request->input('name'), ...)`）
- 每個 Mutation/Query 類別開頭都要 `use AsFake, AsObject;`，實際邏輯寫在
  `handle()` 方法裡

## 參考資料

- 自製 trait：`assets/AsObject.php`、`assets/AsFake.php`（放進
  `app/Concerns/`），提供 `::run()` 呼叫方式與測試用 mock/spy 能力，
  取代 laravel-actions 套件
- 原始文章（作者 howtomakeaturn 在 codelove.tw 的分享）：
  https://codelove.tw/@howtomakeaturn/post/xd0Ykx
- 作者的完整開源範例專案：
  - Mutations：https://yii.tw/app/Mutations
  - Queries：https://yii.tw/app/Queries
  - 架構說明 repo：https://github.com/howtomakeaturn/laravel-flat-architecture