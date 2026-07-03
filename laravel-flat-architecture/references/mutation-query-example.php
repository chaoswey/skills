<?php

// Mutation / Query 類別的標準寫法：不依賴 laravel-actions 套件，
// 而是 use 專案自己寫的兩個 trait（見 assets/AsObject.php、assets/AsFake.php，
// 建議放在 app/Concerns/AsObject.php 和 app/Concerns/AsFake.php）。
//
// - AsObject 提供 ::make() / ::run() / ::runIf() / ::runUnless()，
//   讓類別可以用靜態方法呼叫，內部其實是透過 Laravel container
//   resolve 出實例後呼叫 handle()。
// - AsFake 提供測試時要用的 ::mock() / ::spy() / ::shouldRun() /
//   ::shouldNotRun() / ::allowToRun()，讓你在測試其他程式碼時，可以把
//   這個 Mutation/Query 假掉，不用真的執行它的邏輯。

namespace App\Queries;

use App\Concerns\AsFake;
use App\Concerns\AsObject;
use App\Models\Product;
use Illuminate\Support\Collection;

class GetExistingProductRelationIds
{
    use AsFake, AsObject;

    /**
     * 類別的實際邏輯都寫在 handle()，
     * 呼叫端一律透過 ::run(...) 觸發，不要直接 new 這個類別。
     */
    public function handle(Product $product): Collection
    {
        return $product->relations()->pluck('id');
    }
}

// 呼叫端寫法（在 Controller 或另一個 Mutation/Query 裡）：
//
// $ids = GetExistingProductRelationIds::run($product);

// Mutation 範例（會寫入資料庫）：
//
// namespace App\Mutations;
//
// use App\Concerns\AsFake;
// use App\Concerns\AsObject;
// use App\Models\Event;
//
// class UpdateEvent
// {
//     use AsFake, AsObject;
//
//     public function handle(
//         Event $event,
//         string $title,
//         string $description,
//         ?string $imageFileName = null,
//     ): Event {
//         $event->fill([
//             'title' => $title,
//             'description' => $description,
//         ]);
//
//         if ($imageFileName) {
//             $event->image = $imageFileName;
//         }
//
//         $event->save();
//
//         return $event;
//     }
// }

// 測試裡假掉一個 Mutation/Query（AsFake 提供的能力）：
//
// UpdateEvent::shouldRun()->once()->andReturn($fakeEvent);
// // 或
// GetExistingProductRelationIds::mock()
//     ->shouldReceive('handle')
//     ->andReturn(collect([1, 2, 3]));