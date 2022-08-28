<?php

namespace App\Http\Controllers\Admin;

use App\Models\Calendar;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CalendarController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Calendar::class, 'calendar');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $padaliniai = $request->padaliniai;
        $title = $request->title;

        $calendar = Calendar::
            // check if admin, if not return only pages from current user padalinys
            when(!$request->user()->isAdmin(), function ($query) use ($request) {
                $query->where('padalinys_id', '=', $request->user()->padalinys()->id);
                // check request for padaliniai, if not empty return only pages from request padaliniai
            })->when(!empty($padaliniai), function ($query) use ($padaliniai) {
                $query->whereIn('padalinys_id', $padaliniai);
            })->when(!is_null($title), function ($query) use ($title) {
                $query->where('title', 'like', "%{$title}%");
            })->with(['padalinys' => function ($query) {
                $query->select('id', 'shortname', 'alias');
            }])->with('category')->orderByDesc('date')->paginate(20);

        return Inertia::render('Admin/Calendar/IndexCalendarEvents', [
            'calendar' => $calendar,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {        
        return Inertia::render('Admin/Calendar/CreateCalendarEvent', [
            'categories' => Category::all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'title' => 'required',
            'description' => 'required',
        ]);

        Calendar::create([
            'date' => $request->date,
            'end_date' => $request->end_date,
            'title' => $request->title,
            'description' => $request->description,
            'padalinys_id' => auth()->user()->padalinys()->id,
            'location' => $request->location,
            'url' => $request->url,
            'category' => $request->category,
            'attributes' => $request->all()['attributes']
        ]);

        return redirect()->route('calendar.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Calendar  $calendar
     * @return \Illuminate\Http\Response
     */
    public function show(Calendar $calendar)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Calendar  $calendar
     * @return \Illuminate\Http\Response
     */
    public function edit(Calendar $calendar)
    {
        return Inertia::render('Admin/Calendar/EditCalendarEvent', [
            'calendar' => $calendar,
            'categories' => Category::all(),
            'images' => $calendar->getMedia('images')
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Calendar  $calendar
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Calendar $calendar)
    {
        $request->all();

        $request->validate([
            'date' => 'required|date',
            'title' => 'required',
            'description' => 'required',
        ]);

        DB::transaction(function () use ($request, $calendar) {
            $calendar->update($request->only(['date', 'end_date', 'title', 'description', 'location', 'url', 'category', 'attributes']));

            // if request has files
            
            $images = $request->file('images');

            if ($images) {
                foreach ($images as $image) {
                    $calendar->addMedia($image['file'])->toMediaCollection('images');
                }
            }

            $calendar->save();
        });
        

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Calendar $calendar
     * @return \Illuminate\Http\Response
     */
    public function destroy(Calendar $calendar)
    {
        $calendar->delete();

        return redirect()->route('calendar.index');
    }

    public function destroyMedia(Calendar $calendar, Media $media) {
        $this->authorize('destroyMedia', $calendar);
        
        $calendar->getMedia('images')->where('id', '=', $media->id)->first()->delete();
    }
}
