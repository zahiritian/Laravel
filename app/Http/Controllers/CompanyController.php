<?php

namespace App\Http\Controllers;

use App\Company;
use App\Http\Requests\CompanyRequest;
use App\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Classes\CDataGrid;
use App\Classes\CComponents;
use App\Classes\Export;
use App\City;


class CompanyController extends Controller
{
    private $model, $section, $components;
    /**
     * Constructor
     */
    public function __construct( Company $company, CComponents $components ) {

        $this->model = $company;
        $this->components = $components;

        $this->section = new \stdClass();
        $this->section->title = 'Company';
        $this->section->heading = 'Company';
        $this->section->slug = 'company';
        $this->section->folder = 'companies';
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->checkPermission('view-company');

        $section = $this->section;
        
        $section->breadcrumbs = $this->components->breadcrumb([$section->heading => route($section->slug.'.index')]);

        return view($section->folder.'.index', compact('section'));
    }

    /**
     * Get model list via ajax request.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request) {
        $section = $this->section;
        $input_all = collect($request->all());
        $filters = $this->parseFilters($input_all);

        $companies = $this->model::search( $filters );
        if ( !$companies ) {
            return CDataGrid::getResponse( [], 0, 0 );
        }

        $data = [];
        foreach ( $companies['result'] as $i => $company) {
            $checked = $company->status ? ' checked=""' : "";
            $data[] = [
                $company->name,
                $company->phone,
                $company->status ? '<div class="badge badge-success">Active</div>' : '<div class="badge badge-danger">Inactive</div>',
                $this->components->groupButton(
                    [
                        [
                            'title'      => 'View',
                            'url'        => route($section->slug.'.show', $company->id),
                            'icon'       => 'fa fa-eye',
                            'permission' => 'view-company',
                            'attributes' => [
                                'class'  => 'btn-success'
                            ]
                        ],[
                            'title'      => 'Edit',
                            'url'        => route($section->slug.'.edit', $company->id),
                            'icon'       => 'fa fa-pencil',
                            'permission' => 'edit-company',
                            'attributes' => [
                                'class'  => 'btn-info'
                            ]
                        ],[
                            'title'      => 'Trash',
                            'url'        => route($section->slug.'.destroy', $company->id),
                            'icon'       => 'fa fa-trash',
                            'permission' => 'delete-company',
                            'attributes' => [
                                'class'      => ' grid-action-archive btn-danger',
                                'data-id'    => $company->id,
                                'data-name'  => $company->name
                            ]
                        ],
                    ]
                )
            ];
        }

        return CDataGrid::getResponse( $data, $companies['total'] );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->checkPermission('create-company');

        $company = [];
        $delivery_slots = [];
        $section = $this->section;
        $section->title = 'Add Company';
        $section->heading = 'Add Company';
        $section->method = 'POST';
        $section->breadcrumbs = $this->components->breadcrumb(['Company' => route($section->slug.'.index'), $section->heading => route($section->slug.'.create')]);
        
        $section->route = $section->slug.'.store';

        return view($section->folder.'.form', compact('company', 'section'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CompanyRequest $request)
    {
        $this->checkPermission('create-company');
        
        // dd($request->all(), $request->image);
        $section = $this->section;
        if( $request->hasFile('image') ){
            $getimageName = time().rand(1000,9999).'.'.$request->image->getClientOriginalExtension();
            $request->image->move(storage_path('uploads'), $getimageName);
            $request->request->add(['picture' => $getimageName]);
        }
        if( !$request->picture )
        $request->request->add(['status' => null]);
        
        $request->request->add(['contract_start' => $request->contract_start_submit, 'contract_end' => $request->contract_end_submit]);
        // $request->contract_end = $request->contract_end_submit;
        // dd($request->all());
        
        $company = $this->model::create($request->all());

        
        
        if($request->companyContacts){
            $company->companyContacts()->createMany($request->companyContacts);
        }

        $company->addresses()->createMany($request->addresses);

        $company->shifts()->createMany($this->parsedShifts($request->shifts));

        $request->session()->flash('alert-success', 'Record has been added successfully.');
        return response(['status' => true, 'redirect' => route($section->slug.'.index')]);
        // return redirect()->route($section->slug.'.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        
        $this->checkPermission('view-company');

        $section = $this->section;
        $section->title = 'View Company';
        $section->heading = 'View Company';
        $section->breadcrumbs = $this->components->breadcrumb(['Company' => route($section->slug.'.index'), $section->heading => route($section->slug.'.show', $company)]);

        return view($section->folder.'.view', compact('section', 'company'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  object  Company
     * @return \Illuminate\Http\Response
     */
    public function edit(Company $company)
    {
        $this->checkPermission('edit-company');
        
        $section = $this->section;
        $section->title = 'Edit Company';
        $section->heading = 'Edit Company';
        $section->method = 'PUT';
        $section->breadcrumbs = $this->components->breadcrumb(['Company' => route($section->slug.'.index'), $section->heading => route($section->slug.'.edit', $company)]);

        $section->route = [$section->slug.'.update', $company];
        return view($section->folder.'.form', compact('company', 'section'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  object  Company
     * @return \Illuminate\Http\Response
     */
    public function update(CompanyRequest $request, Company $company)
    {

        $this->checkPermission('edit-company');
        
        $section = $this->section;

        // if Company status not checked, append status in $request
        if( !$request->status )
            $request->request->add(['status' => null]);

        if( $request->payment_type == "cash" )
            $request['payment_frequency'] = "";

        if( $request->hasFile('image') ){
            $getimageName = time().rand(1000,9999).'.'.$request->image->getClientOriginalExtension();
            $request->image->move(storage_path('uploads'), $getimageName);
            $request->request->add(['picture' => $getimageName]);
        }
        $request->merge(['contract_start' => $request->contract_start_submit]);
        $request->merge(['contract_end' => $request->contract_end_submit]);

        $company->update($request->all());
        $company->companyContacts()->delete();
        $company->companyContacts()->createMany($request->companyContacts);

        $company->addresses()->delete();
        $company->addresses()->createMany($request->addresses);

        $company->shifts()->delete();
        $company->shifts()->createMany($this->parsedShifts($request->shifts));

        $request->session()->flash('alert-success', 'Record has been updated successfully.');
        return response(['status' => true, 'redirect' => route($section->slug.'.index')]);
        // return redirect()->route($section->slug.'.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Company $company)
    {
        $this->checkPermission('delete-company');

        $section = $this->section;
        $company->addresses()->delete();
        $company->shifts()->delete();
        return $this->checkajaxrequest($company);

       // return redirect()->route($section->slug.'.index');
    }

    /**
     * get areas of of selected city.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function areas($id = null){
        $data['areas'] = [];
        if( request()->ajax() ){
            $data['areas'] = getAreas($id)->toSelect(0, 'Select Area');
        }

        return $data;
    }

    /**
     * get areas of of selected city.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deliveryTimeslots($id = null){
        $data['delivery_slots'] = [];
        if( request()->ajax() ){
            $data['delivery_slots'] = getDeliveryTimeslots($id)->prepend('Select Timeslot', 0 );
        }

        return $data;
    }

    protected function parsedShifts($shifts){
        $shifts = collect($shifts);

        return $shifts->map(function($shift){
            $slot = explode('|', $shift['delivery_slots']);
            $shift['delivery_slots_start'] = $slot[0];
            $shift['delivery_slots_end'] = $slot[1];
            unset($shift['delivery_slots']);
            return $shift;
        })->all();
    }

    public function  exportCompany(Request $request)
    {

        $input_all = collect($request->all());
        $filters =  $this->parseFilters($input_all);
        try{
            $response =  Export::exportCompany($filters, 'Company Data', 'Sheet 1','xlsx');
            if(count($response) < 1)
            {
                $request->session()->flash('alert-danger', 'No records found to export.');

                return redirect()->back();
            }
        }catch(Exception $e)
        {
            return $e->getMessage();
        }

    }
}
