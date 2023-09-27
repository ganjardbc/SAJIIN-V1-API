<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            'DataBizpars',
            'DatabaseRoles',
            'DatabaseUsers',
            'DatabaseShipments',
            'DatabasePayments',
            'DatabaseCategories',
            'DatabaseTopings',
            'DatabaseProducts',
            'DatabaseProductDetails',
            'DatabaseProductImages',
            'DatabaseProductTopings',
            'DatabasePartners',
            'DatabasePartnerConfigurations',
            'DatabaseOrders',
            'DatabaseOrderItems',
            'DatabaseArticles',
            'DatabaseBenefits',
            'DatabaseFeedbacks',
            'DatabaseCarts',
            'DatabaseWishelists',
            'DatabasePermission',
            'DatabaseRolePermission',
            'DatabaseShops',
            'DatabaseTables',
            'DatabaseVisitors',
            'DatabaseCustomer',
            'DatabaseAddress',
            'DatabaseCatalogs',
            'DatabasePositions',
            'DatabaseEmployees',
            'DatabaseShifts',
            'DatabaseEmployeeShifts',
            'DatabaseNotifications'
        ]);
    }
}
