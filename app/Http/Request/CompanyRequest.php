<?php

namespace App\Http\Requests;

use App\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        switch ( strtoupper(request()->_method) ) {
            case 'PUT':
                $rules = [
                    'name' => 'required|max:255|unique:companies,name,'.request()->company->id.',id,deleted_at,NULL',
                    'domain'  => 'required|check_domain|unique:companies,domain,'.request()->company->id.',id,deleted_at,NULL',
                    /*'business_name' => 'required|max:255',*/
                    'rate' => 'required|between:0.1,9999999.99',
                    'contract_type' => 'required|in:fixed,variable',
                    "no_of_employees" => "required_if:contract_type,==,fixed|nullable|numeric",
                    'phone' => 'required|phone3|unique:companies,phone,'.request()->company->id.',id,deleted_at,NULL',
                    'contract_start' => 'required|date_format:'.dateFormat(\App\Setting::getItem('date_format')),
                    'contract_end' => 'required|date_format:'.dateFormat(\App\Setting::getItem('date_format')).'|after:contract_start',
                    'companyContacts.*.phone' => 'nullable|phone3',
                    'companyContacts.*.email' => 'nullable|email',
                    'image' => 'image|mimes:jpeg,png,jpg,gif|max:4096',
                    'addresses' => 'required',
                    'addresses.*.address' => 'required',
                    'addresses.*.city_id' => 'required',
                    'addresses.*.area_id' => 'required',
                    'addresses.*.latitude' => 'required|cords',
                    'addresses.*.longitude' => 'required|cords',
                    'shifts' => 'required',
                    'shifts.*.shift_id' => 'required',
                    'shifts.*.delivery_slots' => 'required',
                    'payment_type' => 'required',
                ];

                if( request()->get('addresses') )
                    $rules['default_address'] = 'required';

                if( request()->get('shifts') )
                    $rules['default_shift'] = 'required';

                if (!request()->company->imageExists())
                    $rules['image'] = 'required_if:status,on|image|mimes:jpeg,png,jpg,gif|max:4096';
                
                break;

            default:
                $rules = [
                    'name' => 'required|max:255|unique:companies,name,NULL,domain,deleted_at,NULL',
                    'domain'  => 'required|check_domain|unique:companies,domain,NULL,domain,deleted_at,NULL',
                    /*'business_name' => 'required|max:255',*/
                    'rate' => 'required|between:0.1,9999999.99',
                    'contract_type' => 'required|in:fixed,variable',
                    'no_of_employees' => 'nullable|required_if:contract_type,fixed|numeric',
                    'phone' => 'required|phone3|unique:companies,phone,NULL,domain,deleted_at,NULL',
                    'contract_start' => 'required|date_format:'.dateFormat(\App\Setting::getItem('date_format')),
                    'contract_end' => 'required|date_format:'.dateFormat(\App\Setting::getItem('date_format')).'|after:contract_start',
                    'image' => 'image|mimes:jpeg,png,jpg,gif|max:4096',
                    'companyContacts.*.phone' => 'nullable|phone3',
                    'companyContacts.*.email' => 'nullable|email',
                    'addresses' => 'required',
                    'addresses.*.address' => 'required',
                    'addresses.*.city_id' => 'required',
                    'addresses.*.area_id' => 'required',
                    'addresses.*.latitude' => 'required|cords',
                    'addresses.*.longitude' => 'required|cords',
                    'shifts' => 'required',
                    'shifts.*.shift_id' => 'required',
                    'shifts.*.delivery_slots' => 'required',
                    'payment_type' => 'required',

                ];

                if( request()->get('addresses') )
                    $rules['default_address'] = 'required';

                if( request()->get('shifts') )
                    $rules['default_shift'] = 'required';
                
        }

        return $rules;
    }

    public function attributes() {
        return [
            // 'addresses.*.city_id' => 'City',
            // 'addresses.*.area_id' => 'Area'
        ];
    }

    public function messages(){
        $messages = [];
        $messages['shift_id.required'] = 'The shift field is required.';
        $messages['delivery_slots.required'] = 'The delivery slots field is required.';
        $messages['image.required_if'] = 'In order to activate company, you must have to upload Company Logo.';
        $messages['contract_end.after'] = 'The contract end date must be greater then contract start date';
        $messages['addresses.required'] = 'Company must have atleast one address.';
        $messages['shifts.required'] = 'Company must have atleast one shift.';
        $messages['default_address.required'] = 'Company must have atleast one default address.';
        $messages['default_shift.required'] = 'Company must have atleast one default shift.';
        foreach (request()->get('companyContacts') as $key => $item) {
            $messages['companyContacts.'.$key.'.email.email'] = title_case($item['contact_type']).' email must be a valid email address';
            $messages['companyContacts.'.$key.'.phone.phone3'] = title_case($item['contact_type']).' phone must be in this format  '.dailingCode().'0001234567';
        }

        if( request()->get('addresses') ){
            foreach (request()->get('addresses') as $key => $address) {
                $messages['addresses.'.$key.'.address.required'] = 'Address is required at row '.($key+1);
                $messages['addresses.'.$key.'.city_id.required'] = 'City is required at row '.($key+1);
                $messages['addresses.'.$key.'.area_id.required'] = 'Area is required at row '.($key+1);
                $messages['addresses.'.$key.'.latitude.required'] = 'Latitude is required at row '.($key+1);
                $messages['addresses.'.$key.'.latitude.cords'] = 'Latitude seems invalid at row '.($key+1);
                $messages['addresses.'.$key.'.longitude.required'] = 'Longitude is required at row '.($key+1);
                $messages['addresses.'.$key.'.longitude.cords'] = 'Longitude seems invalid at row '.($key+1);
            }
        }

        if( request()->get('shifts') ){
            foreach (request()->get('shifts') as $key => $shift) {
                $messages['shifts.'.$key.'.shift_id.required'] = 'Shift is required at row '.($key+1);
                $messages['shifts.'.$key.'.delivery_slots.required'] = 'Delivery Timeslot is required at row '.($key+1);
            }
        }

        return $messages;
    }

    //replace the request with trimmed request here
    protected function prepareForValidation(){
        $defaultAddress = null;
        $defaultShift = null;
        if( request()->get('addresses') ){
            $defaultAddress = collect(request()->get('addresses'))->where('is_default', 1)->count();
        }
        
        if( request()->get('shifts') ){
            $defaultShift = collect(request()->get('shifts'))->where('is_default', 1)->count();
        }



        $this->request->add(['default_address' => $defaultAddress ?: null, 'default_shift' => $defaultShift ?: null]);
    }

    protected function failedValidation(Validator $validator){
        $errors = (new ValidationException($validator))->errors();
        throw new HttpResponseException(response()->json([
            'code' => 400,
            'status' => false,
            'errors' => $errors
        ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY));
    }
}

