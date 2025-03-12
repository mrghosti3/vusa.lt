<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ContactController extends PublicController
{
    public function contacts()
    {
        $this->getTenantLinks();
        $this->shareOtherLangURL('contacts', $this->subdomain);

        $tenants = json_decode(base64_decode(request()->input('selectedTenants'))) ??
            collect([Tenant::query()->where('type', 'pagrindinis')->first()->id, $this->tenant->id])->unique();

        $institutions = Institution::query()->with('tenant', 'types:id,title,model_type,slug')
            ->whereHas('tenant', fn ($query) => $query->whereIn('id', $tenants)->select(['id', 'shortname', 'alias'])
            )->withCount('duties')->orderBy('name')->get()->makeHidden(['created_at', 'updated_at', 'deleted_at']);

        $seo = $this->shareAndReturnSEOObject(
            title: __('Kontaktų paieška').' - '.$this->tenant->shortname,
            description: app()->getLocale() === 'lt' ? 'VU SA kontaktų paieškoje vienoje vietoje suraskite visus VU SA kontaktus' : 'In the VU SA contact search, find all VU SA contacts in one place', );

        return Inertia::render('Public/Contacts/ContactsSearch', [
            'institutions' => $institutions->map(function ($institution) {
                return [
                    ...$institution->toArray(),
                    // shorten description and add ellipsis
                    // 'description' => Str::limit(strip_tags($institution->description), 100, '...'),
                    //  TODO: better solution for displaying description or remove completely
                    'description' => '',
                ];
            }),
            'selectedTenants' => $tenants,
        ])->withViewData(
            ['SEOData' => $seo]
        );
    }

    public function institutionContacts($subdomain, $lang, Institution $institution)
    {
        $this->getTenantLinks();

        Inertia::share('otherLangURL', route('contacts.institution', ['subdomain' => $this->subdomain, 'lang' => $this->getOtherLang(), 'institution' => $institution->id]));

        $contacts = $institution->load('duties.current_users.current_duties')->duties->sortBy(function ($duty) {
            return $duty->order;
        })->pluck('current_users')->flatten()->unique('id')->values();

        // make eloquent collection from array
        $contacts = new Collection($contacts);

        return $this->showInstitution($institution, $contacts, $institution->name.' | Kontaktai');
    }

    public function institutionDutyTypeContacts($subdomain, $lang, Type $type)
    {
        $this->getTenantLinks();
        Inertia::share('otherLangURL', route('contacts.dutyType', [
            'subdomain' => $this->subdomain,
            'lang' => $this->getOtherLang(), 'type' => $type->slug]));

        $types = $type->getDescendantsAndSelf();

        if ($this->tenant->type === 'pagrindinis') {
            $institution = Institution::where('alias', '=', 'centrinis-biuras')->first();
        } else {
            $institution = Institution::where('alias', '=', $this->tenant->alias)->firstOrFail();
        }

        // load duties whereHas types
        $contacts = $institution->load(['duties' => function ($query) use ($types) {
            $query->whereHas('types', fn (Builder $query) => $query->whereIn('id', $types->pluck('id')))->with('current_users.current_duties');
        }])->duties->sortBy(function ($duty) {
            return $duty->order;
        })->pluck('current_users')->flatten()->unique('id');

        // keep all contacts, but remove some duties from them, if they are not in the selected types
        $contacts = $contacts->map(function ($contact) use ($types) {
            // You can't overwrite the relations, so we need to use another name
            $contact->filtered_current_duties = $contact->current_duties->filter(function ($duty) use ($types) {
                return $duty->types->intersect($types)->count() > 0;
            });

            return $contact;
        });

        // make eloquent collection from array
        $contacts = new Collection($contacts);

        return $this->showInstitution($institution, $contacts, $institution->name.' | '.ucfirst($type->slug));
    }

    public function studentRepresentatives()
    {
        $this->getTenantLinks();
        $this->shareOtherLangURL('contacts.studentRepresentatives', $this->subdomain);

        $type = Type::query()->where('slug', '=', 'studentu-atstovu-organas')->first();
        $descendants = $type->getDescendantsAndSelf();

        $descendants->load(['institutions' => function ($query) {
            $query
                ->with('duties.current_users:id,name,email,phone,facebook_url,profile_photo_path')
                ->with('tenant:id,alias')
                ->where('tenant_id', '=', $this->tenant->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'alias', 'description']);
        }]);

        // remove descendants without institutions
        $descendants = $descendants->filter(function ($descendant) {
            return $descendant->institutions->count() > 0;
        })->values();

        $seo = $this->shareAndReturnSEOObject(
            title: __('Studentų atstovai').' - '.$this->tenant->shortname,
            description: app()->getLocale() === 'lt' ? $this->tenant->shortname.' studentų atstovų paieškoje vienoje vietoje suraskite visus '.$this->tenant->shortname.'studentų atstovus' : 'In '.$this->tenant->shortname.'contact search find all'.$this->tenant->shortname.'student representatives');

        return Inertia::render('Public/Contacts/ShowStudentReps', [
            'types' => $descendants,
        ])->withViewData([
            'SEOData' => $seo,
        ]);
    }

    private function showInstitution(Institution $institution, Collection $contacts, string $title)
    {
        $seo = $this->shareAndReturnSEOObject(
            title: $title.' - '.$this->tenant->shortname,
            description: Str::limit(strip_tags($institution->description), 160),
            image: $institution->image_url,
        );

        return Inertia::render('Public/Contacts/ContactInstitutionOrType', [
            'institution' => $institution,
            'contacts' => $contacts->map(function ($contact) use ($institution) {
                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'facebook_url' => $contact->facebook_url,
                    // Sometimes the duties may be filtered, e.g. curator duties are not shown in coordinator
                    'duties' => isset($contact->filtered_current_duties) ? $contact->filtered_current_duties->where('institution_id', '=', $institution->id)->values() :
                    $contact->current_duties->where('institution_id', '=', $institution->id)->values(),
                    'profile_photo_path' => $contact->profile_photo_path,
                    'pronouns' => $contact->pronouns,
                    'show_pronouns' => $contact->show_pronouns,
                ];
            }),
        ])->withViewData(
            ['SEOData' => $seo]
        );
    }

    /**
     * contactsCategory
     *
     * @param  array  $rest
     * @return \Inertia\Response
     */
    public function institutionCategory($subdomain, $lang, Type $type)
    {
        $this->getTenantLinks();

        Inertia::share('otherLangURL', route('contacts.category', [
            'subdomain' => $this->subdomain,
            'lang' => $this->getOtherLang(), 'type' => $type->slug]));

        $institutions = $type->load(['institutions' => function ($query) {
            $query->orderBy('name')->with(['tenant' => function ($query) {
                $query->where('type', 'padalinys');
            }]);
        }])->institutions;

        $seo = $this->shareAndReturnSEOObject(
            title: __('Kontaktai').': '.$type->title.' - VU SA',
            description: Str::limit($type->description, 160),
        );

        return Inertia::render('Public/Contacts/ShowContactCategory', [
            'institutions' => $institutions->map(function ($institution) {
                return [
                    ...$institution->toArray(),
                    // shorten description and add ellipsis
                    // 'description' => Str::limit(strip_tags($institution->description), 100, '...'),
                    //  TODO: better solution for displaying description or remove completely
                    'description' => '',
                ];
            }),
            'type' => $type->unsetRelation('institutions'),
        ])->withViewData(
            ['SEOData' => $seo]
        );
    }
}
