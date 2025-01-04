<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', 'AuthController@me');
    Route::post('logout', 'AuthController@logout');

    // role
    Route::prefix('auth')->group(function () {
        Route::post('checkUsername', 'AuthController@checkUsername');
        Route::post('checkEmail', 'AuthController@checkEmail');
    });

    // role
    Route::prefix('role')->group(function () {
        Route::post('getAll', 'RoleController@getAll');
        Route::post('getByID', 'RoleController@getByID');
        Route::post('post', 'RoleController@post');
        Route::post('update', 'RoleController@update');
        Route::post('delete', 'RoleController@delete');
    });

    // user
    Route::prefix('user')->group(function () {
        Route::post('getAll', 'UserController@getAll');
        Route::post('getByID', 'UserController@getByID');
        Route::post('uploadImage', 'UserController@uploadImage');
        Route::post('removeImage', 'UserController@removeImage');
        Route::post('post', 'UserController@post');
        Route::post('update', 'UserController@update');
        Route::post('delete', 'UserController@delete');
    });

    // bizpars
    Route::prefix('bizpar')->group(function () {
        Route::post('getAll', 'BizparController@getAll');
        Route::post('getByType', 'BizparController@getByType');
        Route::post('getByKey', 'BizparController@getByKey');
        Route::post('post', 'BizparController@post');
        Route::post('update', 'BizparController@update');
        Route::post('delete', 'BizparController@delete');
    });

    // customers
    Route::prefix('customer')->group(function () {
        Route::post('getAll', 'CustomerController@getAll');
        Route::post('getByID', 'CustomerController@getByID');
        Route::post('post', 'CustomerController@post');
        Route::post('uploadImage', 'CustomerController@uploadImage');
        Route::post('removeImage', 'CustomerController@removeImage');
        Route::post('update', 'CustomerController@update');
        Route::post('delete', 'CustomerController@delete');
    });

    // address
    Route::prefix('address')->group(function () {
        Route::post('getAll', 'AddressController@getAll');
        Route::post('getByID', 'AddressController@getByID');
        Route::post('post', 'AddressController@post');
        Route::post('update', 'AddressController@update');
        Route::post('delete', 'AddressController@delete');
    });

    // payment
    Route::prefix('payment')->group(function () {
        Route::post('getAll', 'PaymentController@getAll');
        Route::post('getByID', 'PaymentController@getByID');
        Route::post('post', 'PaymentController@post');
        Route::post('uploadImage', 'PaymentController@uploadImage');
        Route::post('removeImage', 'PaymentController@removeImage');
        Route::post('update', 'PaymentController@update');
        Route::post('delete', 'PaymentController@delete');
    });

    // shipment
    Route::prefix('shipment')->group(function () {
        Route::post('getAll', 'ShipmentController@getAll');
        Route::post('getByID', 'ShipmentController@getByID');
        Route::post('post', 'ShipmentController@post');
        Route::post('uploadImage', 'ShipmentController@uploadImage');
        Route::post('removeImage', 'ShipmentController@removeImage');
        Route::post('update', 'ShipmentController@update');
        Route::post('delete', 'ShipmentController@delete');
    });

    // category
    Route::prefix('category')->group(function () {
        Route::post('getAll', 'CategoryController@getAll');
        Route::post('getByID', 'CategoryController@getByID');
        Route::post('post', 'CategoryController@post');
        Route::post('uploadImage', 'CategoryController@uploadImage');
        Route::post('removeImage', 'CategoryController@removeImage');
        Route::post('update', 'CategoryController@update');
        Route::post('delete', 'CategoryController@delete');
    });

    // table
    Route::prefix('table')->group(function () {
        Route::post('getAllWithShop', 'TableController@getAllWithShop');
        Route::post('getAll', 'TableController@getAll');
        Route::post('getByID', 'TableController@getByID');
        Route::post('post', 'TableController@post');
        Route::post('saveTable', 'TableController@saveTable');
        Route::post('uploadImage', 'TableController@uploadImage');
        Route::post('removeImage', 'TableController@removeImage');
        Route::post('update', 'TableController@update');
        Route::post('delete', 'TableController@delete');
    });

    // product
    Route::prefix('product')->group(function () {
        Route::post('getAll', 'ProductController@getAll');
        Route::post('getByID', 'ProductController@getByID');
        Route::post('post', 'ProductController@post');
        Route::post('uploadImage', 'ProductController@uploadImage');
        Route::post('removeImage', 'ProductController@removeImage');
        Route::post('update', 'ProductController@update');
        Route::post('delete', 'ProductController@delete');
    });

    // product image
    Route::prefix('productImage')->group(function () {
        Route::post('getAll', 'ProductImageController@getAll');
        Route::post('getByID', 'ProductImageController@getByID');
        Route::post('post', 'ProductImageController@post');
        Route::post('update', 'ProductImageController@update');
        Route::post('delete', 'ProductImageController@delete');
    });

    // product detail
    Route::prefix('productDetail')->group(function () {
        Route::post('getAll', 'ProductDetailController@getAll');
        Route::post('getByID', 'ProductDetailController@getByID');
        Route::post('post', 'ProductDetailController@post');
        Route::post('update', 'ProductDetailController@update');
        Route::post('delete', 'ProductDetailController@delete');
    });

    // product detail
    Route::prefix('productToping')->group(function () {
        Route::post('getAll', 'ProductTopingsController@getAll');
        Route::post('post', 'ProductTopingsController@post');
        Route::post('delete', 'ProductTopingsController@delete');
    });

    // toping
    Route::prefix('toping')->group(function () {
        Route::post('getAll', 'TopingController@getAll');
        Route::post('getByID', 'TopingController@getByID');
        Route::post('uploadImage', 'TopingController@uploadImage');
        Route::post('removeImage', 'TopingController@removeImage');
        Route::post('post', 'TopingController@post');
        Route::post('update', 'TopingController@update');
        Route::post('delete', 'TopingController@delete');
    });

    // partner
    Route::prefix('partner')->group(function () {
        Route::post('getAll', 'PartnerController@getAll');
        Route::post('getByID', 'PartnerController@getByID');
        Route::post('post', 'PartnerController@post');
        Route::post('update', 'PartnerController@update');
        Route::post('delete', 'PartnerController@delete');
    });

    // partner configuration
    Route::prefix('partnerConfiguration')->group(function () {
        Route::post('getAll', 'PartnerConfigurationController@getAll');
        Route::post('getByID', 'PartnerConfigurationController@getByID');
        Route::post('post', 'PartnerConfigurationController@post');
        Route::post('update', 'PartnerConfigurationController@update');
        Route::post('delete', 'PartnerConfigurationController@delete');
    });

    // order
    Route::prefix('order')->group(function () {
        Route::post('getDashboard', 'OrderController@getDashboard');
        Route::post('getReport', 'OrderController@getReport');
        Route::post('downloadReport', 'OrderController@downloadReport');
        Route::post('getAll', 'OrderController@getAll');
        Route::post('getByID', 'OrderController@getByID');
        Route::post('getCountCustomerByID', 'OrderController@getCountCustomerByID');
        Route::post('getCountByID', 'OrderController@getCountByID');
        Route::post('getCountByStatus', 'OrderController@getCountByStatus');
        Route::post('getCountByStatusCustomer', 'OrderController@getCountByStatusCustomer');
        Route::post('post', 'OrderController@post');
        Route::post('postOrderStatus', 'OrderController@postOrderStatus');
        Route::post('postOrderPaymentStatus', 'OrderController@postOrderPaymentStatus');
        Route::post('postCustomer', 'OrderController@postCustomer');
        Route::post('postAdmin', 'OrderController@postAdmin');
        Route::post('update', 'OrderController@update');
        Route::post('updateAdmin', 'OrderController@updateAdmin');
        Route::post('delete', 'OrderController@delete');
    });

    // orderItem
    Route::prefix('orderItem')->group(function () {
        Route::post('getAll', 'OrderItemController@getAll');
        Route::post('post', 'OrderItemController@post');
        Route::post('update', 'OrderItemController@update');
        Route::post('delete', 'OrderItemController@delete');
    });

    // article
    Route::prefix('article')->group(function () {
        Route::post('getAll', 'ArticlesController@getAll');
        Route::post('getByID', 'ArticlesController@getByID');
        Route::post('post', 'ArticlesController@post');
        Route::post('uploadImage', 'ArticlesController@uploadImage');
        Route::post('removeImage', 'ArticlesController@removeImage');
        Route::post('update', 'ArticlesController@update');
        Route::post('delete', 'ArticlesController@delete');
    });

    // benefit
    Route::prefix('benefit')->group(function () {
        Route::post('getAll', 'BenefitsController@getAll');
        Route::post('getByID', 'BenefitsController@getByID');
        Route::post('post', 'BenefitsController@post');
        Route::post('uploadImage', 'BenefitsController@uploadImage');
        Route::post('removeImage', 'BenefitsController@removeImage');
        Route::post('update', 'BenefitsController@update');
        Route::post('delete', 'BenefitsController@delete');
    });

    // feedback
    Route::prefix('feedback')->group(function () {
        Route::post('getAll', 'FeedbacksController@getAll');
        Route::post('getByID', 'FeedbacksController@getByID');
        Route::post('post', 'FeedbacksController@post');
        Route::post('uploadImage', 'FeedbacksController@uploadImage');
        Route::post('removeImage', 'FeedbacksController@removeImage');
        Route::post('update', 'FeedbacksController@update');
        Route::post('delete', 'FeedbacksController@delete');
    });

    // cart
    Route::prefix('cart')->group(function () {
        Route::post('getAll', 'CartController@getAll');
        Route::post('getByID', 'CartController@getByID');
        Route::post('getCountByID', 'CartController@getCountByID');
        Route::post('getCountAll', 'CartController@getCountAll');
        Route::post('getCountCustomerAll', 'CartController@getCountCustomerAll');
        Route::post('post', 'CartController@post');
        Route::post('update', 'CartController@update');
        Route::post('delete', 'CartController@delete');
        Route::post('deleteByUserID', 'CartController@deleteByUserID');
    });

    // wishe lists
    Route::prefix('wishelist')->group(function () {
        Route::post('getAll', 'WisheListController@getAll');
        Route::post('checkWisheList', 'WisheListController@checkWisheList');
        Route::post('post', 'WisheListController@post');
        Route::post('update', 'WisheListController@update');
        Route::post('delete', 'WisheListController@delete');
    });

    // permission
    Route::prefix('permission')->group(function () {
        Route::post('getAll', 'PermissionController@getAll');
        Route::post('getByID', 'PermissionController@getByID');
        Route::post('post', 'PermissionController@post');
        Route::post('update', 'PermissionController@update');
        Route::post('delete', 'PermissionController@delete');
    });

    // role permission
    Route::prefix('rolepermission')->group(function () {
        Route::post('getAll', 'RolePermissionController@getAll');
        Route::post('checkRolePermission', 'RolePermissionController@checkRolePermission');
        Route::post('post', 'RolePermissionController@post');
        Route::post('update', 'RolePermissionController@update');
        Route::post('delete', 'RolePermissionController@delete');
    });

    // visitors
    Route::prefix('visitor')->group(function () {
        Route::post('getAll', 'VisitorController@getAll');
        Route::post('getByID', 'VisitorController@getByID');
        Route::post('checkVisitor', 'VisitorController@checkVisitor');
        Route::post('post', 'VisitorController@post');
        Route::post('update', 'VisitorController@update');
        Route::post('delete', 'VisitorController@delete');
    });

    // shop 
    Route::prefix('shop')->group(function () {
        Route::post('getAll', 'ShopController@getAll');
        Route::post('getByID', 'ShopController@getByID');
        Route::post('uploadImage', 'ShopController@uploadImage');
        Route::post('removeImage', 'ShopController@removeImage');
        Route::post('exit', 'ShopController@exit');
        Route::post('post', 'ShopController@post');
        Route::post('update', 'ShopController@update');
        Route::post('delete', 'ShopController@delete');
    });

    // notification
    Route::prefix('notification')->group(function () {
        Route::post('getAll', 'NotificationController@getAll');
        Route::post('getByID', 'NotificationController@getByID');
        Route::post('getCount', 'NotificationController@getCount');
        Route::post('post', 'NotificationController@post');
        Route::post('update', 'NotificationController@update');
        Route::post('delete', 'NotificationController@delete');
    });

    // catalog
    Route::prefix('catalog')->group(function () {
        Route::post('getAll', 'CatalogController@getAll');
        Route::post('post', 'CatalogController@post');
        Route::post('update', 'CatalogController@update');
        Route::post('delete', 'CatalogController@delete');
    });

    // employee 
    Route::prefix('employee')->group(function () {
        Route::post('getAll', 'EmployeeController@getAll');
        Route::post('getByID', 'EmployeeController@getByID');
        Route::post('uploadImage', 'EmployeeController@uploadImage');
        Route::post('removeImage', 'EmployeeController@removeImage');
        Route::post('post', 'EmployeeController@post');
        Route::post('update', 'EmployeeController@update');
        Route::post('delete', 'EmployeeController@delete');
    });

    // shift 
    Route::prefix('shift')->group(function () {
        Route::post('getAll', 'ShiftController@getAll');
        Route::post('getByID', 'ShiftController@getByID');
        Route::post('post', 'ShiftController@post');
        Route::post('update', 'ShiftController@update');
        Route::post('delete', 'ShiftController@delete');
    });

    // employee shifts 
    Route::prefix('employeeShift')->group(function () {
        Route::post('getAll', 'EmployeeShiftController@getAll');
        Route::post('post', 'EmployeeShiftController@post');
        Route::post('delete', 'EmployeeShiftController@delete');
    });

    // position
    Route::prefix('position')->group(function () {
        Route::post('getAll', 'PositionController@getAll');
        Route::post('getByID', 'PositionController@getByID');
        Route::post('post', 'PositionController@post');
        Route::post('update', 'PositionController@update');
        Route::post('delete', 'PositionController@delete');
    });

    // cashbook
    Route::prefix('cashbook')->group(function () {
        Route::post('getAll', 'CashbookController@getAll');
        Route::post('getByID', 'CashbookController@getByID');
        Route::post('getCurrent', 'CashbookController@getCurrent');
        Route::post('post', 'CashbookController@post');
        Route::post('update', 'CashbookController@update');
        Route::post('delete', 'CashbookController@delete');
    });

    // expense types
    Route::prefix('expense-type')->group(function () {
        Route::post('getAll', 'ExpenseTypeController@getAll');
        Route::post('getByID', 'ExpenseTypeController@getByID');
        Route::post('post', 'ExpenseTypeController@post');
        Route::post('uploadImage', 'ExpenseTypeController@uploadImage');
        Route::post('removeImage', 'ExpenseTypeController@removeImage');
        Route::post('update', 'ExpenseTypeController@update');
        Route::post('delete', 'ExpenseTypeController@delete');
    });

    // expense lists
    Route::prefix('expense-list')->group(function () {
        Route::post('getAll', 'ExpenseListController@getAll');
        Route::post('getByID', 'ExpenseListController@getByID');
        Route::post('post', 'ExpenseListController@post');
        Route::post('uploadImage', 'ExpenseListController@uploadImage');
        Route::post('removeImage', 'ExpenseListController@removeImage');
        Route::post('update', 'ExpenseListController@update');
        Route::post('delete', 'ExpenseListController@delete');
    });

    // platform 
    Route::prefix('platform')->group(function () {
        Route::post('getAll', 'PlatformController@getAll');
        Route::post('getByID', 'PlatformController@getByID');
        Route::post('post', 'PlatformController@post');
        Route::post('uploadImage', 'PlatformController@uploadImage');
        Route::post('removeImage', 'PlatformController@removeImage');
        Route::post('update', 'PlatformController@update');
        Route::post('delete', 'PlatformController@delete');
    });

    // discount 
    Route::prefix('discount')->group(function () {
        Route::post('getAll', 'DiscountController@getAll');
        Route::post('getByID', 'DiscountController@getByID');
        Route::post('post', 'DiscountController@post');
        Route::post('uploadImage', 'DiscountController@uploadImage');
        Route::post('removeImage', 'DiscountController@removeImage');
        Route::post('update', 'DiscountController@update');
        Route::post('delete', 'DiscountController@delete');
    });

    Route::prefix('logs')->group(function () {
        Route::get('getAll', 'GenericLogController@getAuditLogs');
    });
});

Route::prefix('auth')->group(function () {
    Route::post('login', 'AuthController@login');
    Route::post('loginUsername', 'AuthController@loginUsername');
    Route::post('register', 'AuthController@register');
});

Route::prefix('public')->group(function () {
    Route::post('shopByID', 'PublicController@shopByID');
    Route::post('product', 'PublicController@product');
    Route::post('category', 'PublicController@category');
    Route::post('productByID', 'PublicController@productByID');
    Route::post('orderByID', 'PublicController@orderByID');
    Route::post('tables', 'PublicController@tables');
    Route::post('payments', 'PublicController@payments');
    Route::post('createOrder', 'PublicController@createOrder');
    Route::post('sendNotif', 'PublicController@sendNotif');
});

Route::prefix('order')->group(function () {
    Route::post('downloadReceipt', 'OrderController@downloadReceipt');
});
