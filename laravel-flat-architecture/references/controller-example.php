<?php

// 這是 Laravel Flat Architecture 下 Controller 應該長的樣子：
// 每個方法只做「呼叫 Query/Mutation + 權限檢查 + 決定回應」，
// 完全不出現 Eloquent 的 save/update/delete/where/find/first/get。

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyEventController extends Controller
{
    function index(Request $request)
    {
        $events = \App\Queries\GetUserEventsWithPagination::run(Auth::user()->id);

        return view('my-events.index', compact('events'));
    }

    function edit($id, Request $request)
    {
        $event = \App\Queries\GetEvent::run($id);

        if ($event->user->id != Auth::user()->id) {
            abort(403);
        }

        return view('add-event', compact('event'));
    }

    function update($id, Request $request)
    {
        $event = \App\Queries\GetEvent::run($id);

        if ($event->user->id != Auth::user()->id) {
            abort(403);
        }

        $imageFileName = null;

        if ($request->hasFile('image')) {
            $imageFileName = handleImage($request);
        }

        $event = \App\Mutations\UpdateEvent::run(
            event: $event,
            title: $request->input('name'),
            description: $request->input('description'),
            city: $request->input('city'),
            url: $request->input('url'),
            placeKey: $request->input('place_name'),
            category: $request->input('category'),
            fromDate: $request->input('from'),
            toDate: $request->input('to'),
            inDates: explode(',', $request->get('in_dates')),
            imageFileName: $imageFileName,
        );

        return redirect()->to('/my-events')->with('status', '活動修改成功');
    }

    function destroy($id, Request $request)
    {
        $event = \App\Queries\GetEvent::run($id);

        if ($event->user->id != Auth::user()->id) {
            abort(403);
        }

        \App\Mutations\DeleteEvent::run($event);

        return redirect()->to('/my-events')->with('status', '活動刪除成功');
    }
}

// 可以留在 Controller 的例子（1~5 行、無分支、一眼看懂，不用硬拆）：
//
// function toggleFeatured($id)
// {
//     $event = Event::where('id', $id)->first();
//     $event->is_featured = !$event->is_featured;
//     $event->save();
//     return back();
// }

// 應該搬進 Mutation/Query 的例子（有商業邏輯、多張表、需要 transaction）：
//
// function update($id, Request $request)
// {
//     $event = Event::where('id', $id)->first();   // ❌ 有權限檢查+多欄位
//     if ($event->user->id != Auth::user()->id) {  //    更新+可能重複被
//         abort(403);                              //    其他地方呼叫，
//     }                                             //    應該搬進 Query/
//     $event->fill($request->only([...]));         //    Mutation
//     if ($request->hasFile('image')) {
//         $event->image = handleImage($request);
//     }
//     $event->save();
//     EventStats::incrementUpdateCount($event->id);
//     return redirect()->to('/my-events');
// }