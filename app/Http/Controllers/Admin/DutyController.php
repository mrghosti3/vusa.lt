<?php

namespace App\Http\Controllers\Admin;

use App\Models\Duty;
use App\Models\DutyInstitution;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use App\Models\DutyType;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DutyController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Duty::class, 'duty');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // For search
        $title = $request->title;

        $duties = Duty::when(!is_null($title), function ($query) use ($title) {
            $query->where('name', 'like', "%{$title}%")->orWhere('email', 'like', "%{$title}%");
        })->when(!$request->user()->hasRole('Super Admin'), function ($query) {
            $query->whereHas('institution', function ($query) {
                $query->where('padalinys_id', auth()->user()->padalinys()->id);
            });
        })->with(['institution:id,name,short_name,padalinys_id','institution.padalinys:id,shortname', 'type:id,name'])->paginate(20);

        return Inertia::render('Admin/Contacts/IndexDuties', [
            'duties' => $duties,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $dutyInstitutions = $this->getDutyInstitutionsForForm();
        
        return Inertia::render('Admin/Contacts/CreateDuty', [
            'dutyTypes' => DutyType::all(),
            'dutyInstitutions' => $dutyInstitutions,
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
            'name' => 'required',
            'institution' => 'required',
            'type' => 'required',
        ]);

        $duty = new Duty();

        $duty->name = $request->name;
        $duty->email = $request->email;
        $duty->description = $request->description;
        $duty->attributes = $request->all()['attributes'];
        $duty->institution()->associate($request->institution['id']);
        // TODO: sutvarkyti
        $duty->type_id = $request->type['id'];
        $duty->save();

        return redirect()->route('duties.index')->with('success', 'Pareigybė sėkmingai sukurta!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Http\Response
     */
    public function show(Duty $duty)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Http\Response
     */
    public function edit(Duty $duty)
    {
        $dutyInstitutions = $this->getDutyInstitutionsForForm();

        return Inertia::render('Admin/Contacts/EditDuty', [
            'duty' => [
                'id' => $duty->id,
                'name' => $duty->name,
                'description' => $duty->description,
                'type' => $duty->type,
                'institution' => $duty->institution,
                'email' => $duty->email,
                'attributes' => $duty->attributes,
                'places_to_occupy' => $duty->places_to_occupy,
            ],
            'users' => $duty->users,
            'dutyTypes' => DutyType::all(),
            'dutyInstitutions' => $dutyInstitutions
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Duty $duty)
    {
        $request->validate([
            'name' => 'required',
            'institution' => 'required',
            'type' => 'required',
        ]);

        DB::transaction(function () use ($request, $duty) {
            $duty->update($request->only('name', 'description', 'email', 'attributes'));

            $duty->type_id = $request->type['id'];
            $duty->institution()->disassociate();
            $duty->institution()->associate(DutyInstitution::find($request->institution['id']));
            $duty->save();
        });

        return back()->with('success', 'Pareigybė sėkmingai atnaujinta!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Duty  $duty
     * @return \Illuminate\Http\Response
     */
    public function destroy(Duty $duty)
    {
        $duty->delete();

        return redirect()->route('duties.index')->with('info', 'Pareigybė sėkmingai ištrinta!');
    }

    private function getDutyInstitutionsForForm(): Collection
    {
        return DutyInstitution::select('id', 'name', 'alias', 'padalinys_id')->when(!request()->user()->hasRole('Super Admin'), function ($query) {
                $query->where('padalinys_id', auth()->user()->padalinys()->id);
        })->with('padalinys:id,shortname')->get();
    }
}
