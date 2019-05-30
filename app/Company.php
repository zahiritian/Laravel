<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends BaseModel
{
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at' , 'contract_start', 'contract_end'];
    protected $uploadPath;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
       'name', 'business_name', 'phone', 'description', 'picture', 'domain' , 'contract_type', 'no_of_employees', 'rate', 'contract_start', 'contract_end', 'status', 'payment_type', 'payment_frequency'
    ];

    /**
     * The attributes to check referential integration.
     *
     * @var array
     */
    public $dependencies = ['customers'];

    /**
     * Payment types.
     *
     * @var array
     */

    const PAYMENT_TYPE_CASH = 'cash';
    const PAYMENT_TYPE_CHEQUE = 'cheque';



    public static $paymentTypes = [self::PAYMENT_TYPE_CASH => 'Cash', self::PAYMENT_TYPE_CHEQUE => 'Cheque'];

    /**
     * Payment Frequencies.
     *
     * @var array
     */

    const PAYMENT_FREQUENCY_DAILY = 'daily';
    const PAYMENT_FREQUENCY_WEEKLY = 'weekly';
    const PAYMENT_FREQUENCY_MONTHLY = 'monthly';
    const PAYMENT_FREQUENCY_QUARTERLY = 'quaterly';
    const PAYMENT_FREQUENCY_BI_ANNUALLY = 'bi-annually';
    const PAYMENT_FREQUENCY_ANNUALLY = 'annually';



    public static $paymentFrequencies = [self::PAYMENT_FREQUENCY_DAILY => 'Daily',
        self::PAYMENT_FREQUENCY_WEEKLY => 'Weekly',
        self::PAYMENT_FREQUENCY_MONTHLY => 'Monthly',
        self::PAYMENT_FREQUENCY_QUARTERLY => 'Quarterly',
        self::PAYMENT_FREQUENCY_BI_ANNUALLY => 'Bi-Annually',
        self::PAYMENT_FREQUENCY_ANNUALLY => 'Annually'];


    // public $appends = ['delivery_slots'];

    public function __construct(array $attributes = []) {
        parent::__construct($attributes);
        $this->uploadPath = storage_path( '/uploads/' );
    }

    /**
     * Accessors & Mutators [STARTS].
     *
     *
     * @return \Illuminate\Database\Eloquent
     */

    public function setStatusAttribute($value){
        $this->attributes['status'] = $value == 'on' ? 1 : 0;
    }

    public function setContractStartAttribute($value){
        $this->attributes['contract_start'] = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('d/m/Y', $value)));
    }

    public function getContractStartAttribute($value){
        return  date('d/m/Y', strtotime($value));
    }

    public function setContractEndAttribute($value){
        $this->attributes['contract_end'] = date('Y-m-d', strtotime(\Carbon\Carbon::createFromFormat('d/m/Y', $value)));
    }

    public function getContractEndAttribute($value){
        return  date('d/m/Y', strtotime($value));
    }

    public function getRateAttribute($value){
        return floor($value);
    }

    // public function getDeliverySlotsAttribute(){
    //     // return  $this->delivery_slots_start.','.$this->delivery_slots_end;
    // }

    /**
     * RELATIONSHIPS [STARTS].
     *
     *
     * @return \Illuminate\Database\Eloquent\Relations
     */

    public function customers(){
        return $this->hasMany(Customer::class);
    }

    public function addresses(){
        return $this->hasMany(AddressCompany::class);
    }

    public function shifts(){
        return $this->hasMany(CompanyShift::class);
    }

    public function companyPayments(){
        return $this->hasMany(CompanyPayment::class);
    }

    public function customizednotification()
    {
        return $this->morphMany(CustomizedNotification::class, 'notifiable');
    }
    // RELATIONSHIPS [ENDS]


    public static function search( $options = [] ) {


        if ( is_array($options) ) {
            $options = array_map(
                function ( $e ) {
                    return is_scalar( $e ) ? trim( $e ) : $e;
                }, $options
            );
        }

        //$query = self::query()->exceptSuperAdmin()->exceptMe();
        $query = self::query();

        #/ name
        if (isset($options['name']) &&  !is_null( $options['name'] ) && $options['name'] != "") {
            $query = $query->where( 'name', 'LIKE', '%' . $options['name'] . '%' );
        }

        #/ email
        if (isset($options['phone']) &&  !is_null( $options['phone'] ) && $options['phone'] != "") {
            $query = $query->where( 'phone', 'LIKE', '%' . $options['phone'] . '%' );
        }

        #/ status
        if (isset($options['status']) &&  !is_null( $options['status'] ) && $options['status'] != -1) {
            $query = $query->where( 'status', 'LIKE', '%' . $options['status'] . '%' );
        }

        #/ city
//        if (isset($options['region_name']) && !empty( $options['region_name'] ) ) {
//            $query = $query->whereHas('region', function ( $query ) use ( $options ) {
//                return $query->where( 'name', 'LIKE', '%' . $options['region_name'] . '%' );
//            });
//        }

//        if (isset($options['order_by']) &&  !is_null( $options['order_by'] ) && $options['order_by'] == "region_name") {
//            $options['order_by'] = 'region_name';
//            $query->select( 'cities.*','regions.name as region_name' );
//            $query->leftJoin('regions', 'cities.region_id', '=', 'regions.id');
//        }


        if (!$count = $query->count()) {
            return false;
        }

        $options['start'] = isset($options['start']) ? $options['start'] : 0;
        $options['length'] = isset($options['length']) ? $options['length'] : 25;


        $query->orderBy( $options['order_by'], $options['order_dir']);


        if ( $options['start'] ) {
            $query = $query->skip( $options['start'] );
        }

        if ( $options['length'] && $options['length'] > 0) {
            $query = $query->take( $options['length'] );
        }

        // dd ( $query->toSql() );
        //dd ( $query->get()->toArray() );
        return [
            'total' => $count,
            'result' => $query->get()
        ];
    }

    public function scopeActive($query){
        return $query->whereStatus(1);
    }

    public function companyContacts() {
        return $this->hasMany( CompanyContact::class);
    }

    public function hasImage() {
        return !empty($this->picture);
    }

    public function imageExists() {
        if ( !$this->hasImage() ) {
            return false;
        }

        $picture = $this->uploadPath . $this->picture;
        if ( is_dir( $picture ) ) {
            return false;
        }

        return file_exists( $picture );
    }

    public function imageUrl() {
        if ( !$this->imageExists() )
            return null;

        return route('upload.url', $this->picture);
    }

}
