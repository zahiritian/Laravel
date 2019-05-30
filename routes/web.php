<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*Route::get('/', function () {
    return view('frontend.index');
});*/

Route::get(
	'uploads/{filename}', function ( $filename ) {

	$path = storage_path() . DIRECTORY_SEPARATOR . 'uploads' .
		DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $filename );

	if ( is_dir( $path ) || !File::exists( $path ) ) {
		abort( 404 );
	}

	$file = File::get( $path );
	$ext = explode( '.', $filename );
	$ext = array_pop( $ext );
	switch ( strtolower( $ext ) ) {
		case 'gif':
			$type = 'image/gif';
			break;
		case 'jpg':
			$type = 'image/jpeg';
			break;
		case 'png':
			$type = 'image/png';
			break;
		case 'txt':
			$type = 'text/plain';
			break;
		case 'doc':
			$type = 'application/msword';
			break;
		case 'mp4':
			$type = 'video/mp4';
			break;
		default:
			$type = 'application/octet-stream';
	}

	$response = Response::make( $file, 200 );
	$response->header( "Content-Type", $type );

	return $response;
}
)->where( 'filename', '.*' )->name('upload.url');

Route::get('/', 'FrontEndController@index')->name('frontend.index');
Route::post('send/contact', 'FrontEndController@sendContactEmail')->name('send.contact');
Route::post('send/workplace', 'FrontEndController@sendWorkPlaceEmail')->name('send.workplace');
Route::post('send/eatery', 'FrontEndController@sendEateryEmail')->name('send.eatery');

Auth::routes();
Route::get('/delivery-automation/{hubs}',function($hubs){

    $hubs = json_decode($hubs);
    if( hasPermission('create-delivery-run') && App\Shift::checkshift(date('H:i:s'))->count() && $hubs){
    	\Artisan::call('deliveryrun:automate',[
    	    'hubs' => $hubs
        ]);
    	$type = 'alert-success';
    	$message = 'Delivery Runs generated successfully.';
    }else{
    	$type = 'alert-info';
    	if(count($hubs) > 0){
            $message = 'No shift found.';
        } else {
            $message = 'No Hubs found.';
        }
	}
	
	if(!request()->session()->has('alert-warning'))
		request()->session()->flash($type, $message);
		
    return redirect(route('delivery-runs.index'));
})->name('delivery-automation');


Route::get('/hub-inventory/addon-update',function(){
    \Artisan::call('hub-inventory-addon:update');
});

Route::get('/daily-night-notification',function(){
    \Artisan::call('notification:daily-night');
});

Route::get('/time-based-notification',function(){
    \Artisan::call('notification:time-based');
});

// Route::get('/winning-meals',function(){
//     \Artisan::call('winning-meals:select');
// });

// Route::get('/home', 'HomeController@index')->name('home');
Route::group(['middleware' => ['auth', 'web']], function () {

	Route::post('change-status', 'Controller@changeStatus')->name('change-status');
	
	Route::get('/dashboard', 'DashboardController@index')->name('dashboard');

	Route::get('setting', 'SettingController@edit')->name('setting.edit');
	Route::post('setting', 'SettingController@update')->name('setting.update');
	Route::delete('setting/delete-image', 'SettingController@deleteImage');

    Route::post('users/list', ['as'=>'users.list', 'uses'=>'UsersController@list']);
    Route::get('users/export', ['as'=>'users.export', 'uses'=>'UsersController@exportUsers']);
	Route::resource('users', 'UsersController');

    Route::post('roles/list', ['as'=>'roles.list', 'uses'=>'RolesController@list']);
    Route::get('roles/export', ['as'=>'roles.export', 'uses'=>'RolesController@exportRoles']);
	Route::resource('roles', 'RolesController');

    Route::post('country/list', ['as'=>'country.list', 'uses'=>'CountryController@list']);
    Route::get('country/export', ['as'=>'country.export', 'uses'=>'CountryController@exportCountry']);
	Route::resource('country', 'CountryController')->except([
	    'create', 'store', 'destroy'
	]);
	
    Route::post('city/list', ['as'=>'city.list', 'uses'=>'CityController@list']);
    Route::get('city/export', ['as'=>'city.export', 'uses'=>'CityController@exportCity']);
	Route::resource('city','CityController');
	
    Route::get('hub/inventory/{hub}', ['as'=>'hub.get-inventory', 'uses'=>'HubController@getInventory']);
    Route::post('hub/inventory-list/{hub}', ['as'=>'hub.get-inventory-list', 'uses'=>'HubController@getInventoryList']);
    Route::post('hub/update-inventory', ['as'=>'hub.update-inventory', 'uses'=>'HubController@updateInventory']);
    Route::post('hub/list', ['as'=>'hub.list', 'uses'=>'HubController@list']);
    Route::get('hub/areas/{id}',['as' => 'hub.areas','uses'=>'HubController@areas']);
    Route::get('hub/export',['as' => 'hub.export','uses'=>'HubController@hubExport']);
    Route::resource('hub','HubController');

    Route::post('area/list', ['as'=>'area.list', 'uses'=>'AreaController@list']);
    Route::get('area/export', ['as'=>'area.export', 'uses'=>'AreaController@exportAreas']);
    Route::resource('area','AreaController');

    Route::post('regions/list', ['as'=>'regions.list', 'uses'=>'RegionsController@list']);
    Route::get('regions/export', ['as'=>'regions.export', 'uses'=>'RegionsController@exportRegion']);
    Route::resource('regions','RegionsController');

	Route::post('riders/list', ['as'=>'riders.list', 'uses'=>'RidersController@list']);
	Route::resource('riders','RidersController')->except([
	    'create', 'store'
	]);
    Route::get('rider/export', ['as'=>'rider.export', 'uses'=>'RidersController@exportRider']);

	Route::post('meals/list', ['as'=>'meals.list', 'uses'=>'MealsController@list']);
	Route::resource('meals','MealsController');
    Route::get('meal/export', ['as'=>'meals.export', 'uses'=>'MealsController@exportMeals']);

    Route::post('restaurants/list', ['as'=>'restaurants.list', 'uses'=>'RestaurantsController@list']);
    Route::get('restaurants/areas/{id}',['as' => 'restaurants.areas','uses'=>'RestaurantsController@areas']);
    Route::get('restaurants/export',['as' => 'restaurants.export','uses'=>'RestaurantsController@exportRestaurants']);
    Route::resource('restaurants','RestaurantsController');

	Route::post('schedules/list', ['as'=>'schedules.list', 'uses'=>'SchedulesController@list']);
	Route::patch('schedules/increase-fake-votes/{schedule}', ['as'=>'schedules.increase-fake-votes', 'uses'=>'SchedulesController@increseFakeVotes']);
	Route::post('schedules/get-hub-meals',['as' => 'schedules.hub_meals','uses'=>'SchedulesController@getHubsMeals']);
	Route::resource('schedules','SchedulesController');
    Route::get('schedule/export',['as'=>'schedule.export', 'uses' => 'SchedulesController@exportSchedule']);
    Route::post('categories/list', ['as'=>'categories.list', 'uses'=>'CategoriesController@list']);
    Route::get('categories/export', ['as'=>'categories.export', 'uses'=>'CategoriesController@exportCategories']);
    Route::resource('categories','CategoriesController');

    Route::post('addons/list', ['as'=>'addons.list', 'uses'=>'AddonsController@list']);
    Route::get('addons/export', ['as'=>'addons.export', 'uses'=>'AddonsController@exportAddons']);
    Route::resource('addons','AddonsController');

	Route::post('customers/list', ['as'=>'customers.list', 'uses'=>'CustomersController@list']);
	Route::resource('customers','CustomersController')->except([
	    'create', 'store'
	]);
	Route::get('customer/export', ['as'=>'customer.export','uses'=>'CustomersController@exportCustomer']);

	Route::post('shifts/list', ['as'=>'shifts.list', 'uses'=>'ShiftsController@list']);
	Route::resource('shifts','ShiftsController');
    Route::get('shift/export',['as'=>'shift.export', 'uses'=>'ShiftsController@exportShift']);
    Route::post('company/list', ['as'=>'company.list', 'uses'=>'CompanyController@list']);
    Route::get('company/areas/{id?}',['as' => 'company.areas','uses'=>'CompanyController@areas']);
    Route::get('company/export',['as' => 'company.export','uses'=>'CompanyController@exportCompany']);
	Route::get('company/delivery-slots/{id?}',['as' => 'company.delivery-timeslots','uses'=>'CompanyController@deliveryTimeslots']);
    Route::resource('company','CompanyController');
	
	Route::post('company/{company}/company_payment/list', ['as'=>'company.company_payment.list', 'uses'=>'CompanyPaymentController@list']);
	Route::resource('company.company_payment','CompanyPaymentController');

	Route::post('orders/list', ['as'=>'orders.list', 'uses'=>'OrdersController@list']);
	Route::get('orders/place-order', ['as'=>'orders.place', 'uses'=>'OrdersController@placeOrder']);
	Route::resource('orders','OrdersController')->only([
	    'index', 'edit', 'show', 'update'
	]);
    Route::get('order/export', ['as'=>'orders.export', 'uses'=>'OrdersController@exportOrder']);
    Route::get('order/cancel-order-auto', ['as'=>'orders.cancel.auto', 'uses'=>'OrdersController@cancelOrdersAuto']);
    Route::get('order/cancel-remaining-order', ['as'=>'orders.cancel.remaining', 'uses'=>'OrdersController@cancelRemainingOrders']);
    Route::post('order/cancel-single-order/{id}', ['as'=>'orders.single.cancel', 'uses'=>'OrdersController@singleOrderCancelAfterShiftEnd']);
    Route::post('order/complete-single-order/{id}', ['as'=>'orders.single.complete', 'uses'=>'OrdersController@singleOrderCompleteAfterShiftEnd']);

    Route::post('purchase-runs/restaurants-list', ['as'=>'orders-restaurants.list', 'uses'=>'PurchaseRunsController@getRestaurants']);
	Route::post('purchase-runs/meals-list', ['as'=>'orders-meals.list', 'uses'=>'PurchaseRunsController@getMeals']);
	Route::post('purchase-runs/meal-details', ['as'=>'orders-meals.details', 'uses'=>'PurchaseRunsController@getMealDetails']);
	Route::post('purchase-runs/match-meal-details', ['as'=>'orders-meals.match-details', 'uses'=>'PurchaseRunsController@matchMealDetails']);
	Route::post('purchase-runs/hubs-list', ['as'=>'orders-meals.hubs-list', 'uses'=>'PurchaseRunsController@getHubs']);
	Route::post('purchase-runs/hub-riders-list', ['as'=>'orders-meals.hub-riders-list', 'uses'=>'PurchaseRunsController@getHubRiders']);

	Route::post('purchase-runs/list', ['as'=>'purchase-runs.list', 'uses'=>'PurchaseRunsController@list']);
	Route::resource('purchase-runs','PurchaseRunsController');
    Route::get('purchase-run/export',['as'=>'purchase-run.export','uses'=>'PurchaseRunsController@purchaseRunExport']);

	Route::post('delivery-runs/list', ['as'=>'delivery-runs.list', 'uses'=>'DeliveryRunsController@list']);
    Route::get('delivery-runs/export',['as'=>'delivery-runs.export', 'uses'=>'DeliveryRunsController@deliveryRunExport']);
    Route::resource('delivery-runs','DeliveryRunsController');

    Route::post('daily-order-report', ['as'=>'daily-order-report.getDailyOrderReport', 'uses'=>'DashboardController@getDailyOrderReport']);
	Route::get('daily-order-report',['as'=>'daily-order-report', 'uses'=>'DashboardController@dailyOrderReport']);

	Route::get('export-daily-order-report', ['as'=>'daily-order-report.ExportDailyOrderReport', 'uses'=>'DashboardController@ExportDailyOrderReport']);
    
    Route::post('delivery-runs/list', ['as'=>'delivery-runs.list', 'uses'=>'DeliveryRunsController@list']);
    
    Route::resource('delivery-runs','DeliveryRunsController')->except([
	    'create', 'store'
	]);

	Route::post('faq/list', ['as'=>'faq.list', 'uses'=>'FaqController@list']);
	Route::get('faq/export', ['as'=>'faq.export', 'uses'=>'FaqController@exportFaq']);
	Route::resource('faq','FaqController');

	Route::post('quote/list', ['as'=>'quote.list', 'uses'=>'QuoteController@list']);
	Route::get('quote/export', ['as'=>'quote.export', 'uses'=>'QuoteController@exportQuote']);
	Route::resource('quote','QuoteController');

	Route::post('get-help/list', ['as'=>'get-help.list', 'uses'=>'HelpController@list']);
    Route::get('get-help',['as'=>'get-help.index', 'uses'=>'HelpController@index']);
    Route::get('get-help/export',['as'=>'get-help.export', 'uses'=>'HelpController@exportCustomerQuery']);

	Route::post('package/list', ['as'=>'package.list', 'uses'=>'PackageController@list']);
	Route::get('package/export', ['as'=>'package.export', 'uses'=>'PackageController@exportPackages']);
	Route::resource('package','PackageController');

	Route::post('vehicle-type/list', ['as'=>'vehicle-type.list', 'uses'=>'VehicleTypeController@list']);
	Route::get('vehicle-type/export', ['as'=>'vehicle-type.export', 'uses'=>'VehicleTypeController@exportVehicleType']);
	Route::resource('vehicle-type','VehicleTypeController');

	Route::post('suggest-meal/list', ['as'=>'suggest-meal.list', 'uses'=>'SuggestMealController@list']);
	Route::get('suggest-meal',['as'=>'suggest-meal.index', 'uses'=>'SuggestMealController@index']);
    Route::get('suggest-meal/export',['as'=>'suggest-meal.export','uses'=>'SuggestMealController@exportSuggestedMeal']);
	Route::post('donate-meal/list', ['as'=>'donate-meal.list', 'uses'=>'DonateMealController@list']);
    Route::get('donate-meal/export',['as'=>'donate-meal.export','uses'=>'DonateMealController@exportDonatedMeals']);
    Route::resource('donate-meal','DonateMealController');

    Route::post('push-notification/list', ['as'=>'push-notification.list', 'uses'=>'PushNotificationController@list']);
    Route::get('push-notification/export', ['as'=>'push-notification.export', 'uses'=>'PushNotificationController@exportPushNotification']);
	Route::delete('push-notification/delete/{pushNotification}', 'PushNotificationController@deleteimage');
	Route::resource('push-notification','PushNotificationController')->only([
		'index', 'edit', 'update', 'show'
	]);

    Route::get('rider-inventory/', ['as' => 'rider-inventory.index' , 'uses' => 'RiderInventoryController@index' ]);
    Route::post('rider-inventory/list-purchase', ['as'=>'rider-inventory.list.purchase', 'uses'=>'RiderInventoryController@getListPurchase']);
    Route::post('rider-inventory/list-delivery', ['as'=>'rider-inventory.list.delivery', 'uses'=>'RiderInventoryController@getListDelivery']);
    Route::post('rider-inventory/list-delivery-addons', ['as'=>'rider-inventory.list.delivery-addons', 'uses'=>'RiderInventoryController@getListDeliveryAddons']);
    Route::patch('rider-inventory/update-return', ['as'=>'rider-inventory.update-return', 'uses'=>'RiderInventoryController@updateReturnQty']);
    Route::patch('rider-inventory/update-received-qty', ['as'=>'rider-inventory.update-received-qty', 'uses'=>'RiderInventoryController@updateReceivedQty']);
    Route::get('rider-inventory/export', ['as' => 'rider-inventory.export' , 'uses' => 'RiderInventoryController@riderInventoryExport' ]);

    Route::get('hub-inventory/', ['as' => 'hub-inventory.index' , 'uses' => 'HubInventoryController@index' ]);
    Route::post('hub-inventory/list', ['as'=>'hub-inventory.list', 'uses'=>'HubInventoryController@getList']);

    Route::post('hub-inventory/addon/list', ['as'=>'hub-inventory.addonlist', 'uses'=>'HubInventoryController@getAddonList']);

    Route::get('hub-inventory/export', ['as'=>'hub-inventory-export', 'uses'=>'HubInventoryController@hubInventoryExport']);

	//Collection
	Route::get('cash-management/', ['as'=>'cash-management.index', 'uses' => 'CollectionController@index']);
	Route::post('cash-management/list', ['as'=>'cash-management.list', 'uses'=>'CollectionController@getList']);
	Route::get('cash-management/export', ['as'=>'cash-management.export', 'uses'=>'CollectionController@exportCollection']);
	Route::patch('cash-management/update-daily-cash', ['as'=>'cash-management.daily-cash', 'uses'=>'CollectionController@updateDailyCash']);
	Route::patch('cash-management/update-deposit-cash', ['as'=>'cash-management.deposit-cash', 'uses'=>'CollectionController@updateDeposit']);
	
    Route::resource('customized-notification','CustomizedNotificationController')->only([
		'create', 'store'
	]);
	Route::get('customized-notification/audience/{id}','CustomizedNotificationController@audiences');

    Route::post('reasons/list', ['as'=>'reasons.list', 'uses'=>'ReasonsController@list']);
    Route::get('reasons/export', ['as'=>'reasons.export', 'uses'=>'ReasonsController@exportReasons']);
	Route::resource('reasons','ReasonsController');


	Route::post('banks/list', ['as'=>'banks.list', 'uses'=>'BanksController@list']);
    Route::get('banks/export', ['as'=>'banks.export', 'uses'=>'BanksController@exportBank']);
    Route::resource('banks','BanksController');

    Route::post('activity-logs/list', ['as'=>'activity-logs.list', 'uses'=>'ActivityLogController@list']);
    Route::get('activity-logs/export', ['as'=>'activity-logs.export', 'uses'=>'ActivityLogController@exportActivityLog']);
    Route::resource('activity-logs','ActivityLogController');

});
