<?php
namespace Database\Seeders;
use App\Models\{Customer,Product,Supplier,User};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class DatabaseSeeder extends Seeder {
    public function run():void{
        User::updateOrCreate(['email'=>env('DEFAULT_USER_EMAIL','admin@example.com')],['name'=>env('DEFAULT_USER_NAME','Admin'),'password'=>env('DEFAULT_USER_PASSWORD','password')]);
        Customer::updateOrCreate(['is_walk_in'=>true],['name'=>'Walk-in Customer','opening_balance'=>0,'is_active'=>true]);
        foreach(['app_name'=>'Stock Manager','currency'=>'PKR','quantity_precision'=>'3'] as $key=>$value) DB::table('settings')->updateOrInsert(compact('key'),['value'=>$value,'created_at'=>now(),'updated_at'=>now()]);
        if(filter_var(env('SEED_DEMO_DATA',false),FILTER_VALIDATE_BOOL)){
            $supplier=Supplier::firstOrCreate(['name'=>'Demo Supplier'],['phone'=>'000-0000000']);
            Customer::firstOrCreate(['name'=>'Demo Customer'],['phone'=>'000-0000001']);
            Product::firstOrCreate(['sku'=>'DEMO-001'],['name'=>'Demo Product','unit'=>'piece','purchase_price'=>100,'sale_price'=>140,'average_cost'=>100,'minimum_stock_level'=>5]);
        }
    }
}
