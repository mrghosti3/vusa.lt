<?php

namespace App\Http\Controllers\Admin;

use App\Models\MainPage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use App\Http\Controllers\ResourceController;
use App\Services\ModelIndexer;
use Illuminate\Support\Facades\DB;

class MainPageController extends ResourceController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [MainPage::class, $this->authorizer]);
        
        $search = request()->input('text');

        $indexer = new ModelIndexer();
        $mainPages = $indexer->execute(MainPage::class, $search, 'text', $this->authorizer, false);

        return Inertia::render('Admin/Content/IndexMainPages', [
            'mainPages' => $mainPages->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', [MainPage::class, $this->authorizer]);
        
        return Inertia::render('Admin/Content/CreateMainPage');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', [MainPage::class, $this->authorizer]);
        
        $request->validate([
            'text' => 'required',
            'link' => 'required',
        ]);

        DB::transaction(function () use ($request) {
            
            $mainPage = new MainPage;
            
            $mainPage->text = $request->text;
            $mainPage->link = $request->link;
            $mainPage->lang = $request->lang;
            $mainPage->position = '';
            $mainPage->padalinys()->associate($request->user()->padalinys());
            $mainPage->save();
        });

        return redirect()->route('mainPages.index')->with('success', 'Sėkmingai pridėtas pradinio puslapio mygtukas!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Http\Response
     */
    public function show(MainPage $mainPage)
    {
        $this->authorize('view', [MainPage::class, $mainPage, $this->authorizer]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Http\Response
     */
    public function edit(MainPage $mainPage)
    {        
        $this->authorize('update', [MainPage::class, $mainPage, $this->authorizer]);
        
        return Inertia::render('Admin/Content/EditMainPage', [
            'mainPage' => $mainPage
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MainPage  $mainPage
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, MainPage $mainPage)
    {
        $this->authorize('update', [MainPage::class, $mainPage, $this->authorizer]);
        
        $request->validate([
            'text' => 'required',
            'link' => 'required',
        ]);
        
        DB::transaction(function () use ($request, $mainPage) {
            $mainPage->update($request->only('text', 'link', 'lang'));
        });

        return back()->with('success', 'Sėkmingai atnaujintas pradinio puslapio mygtukas!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\MainPage $mainPage
     * @return \Illuminate\Http\Response
     */
    public function destroy(MainPage $mainPage)
    {
        $this->authorize('delete', [MainPage::class, $mainPage, $this->authorizer]);
        
        $mainPage->delete();

        return redirect()->route('mainPage.index')->with('info', 'Sėkmingai ištrintas pradinio puslapio mygtukas!');
    }
}
