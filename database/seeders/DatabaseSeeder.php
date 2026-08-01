<?php
namespace Database\Seeders;
use App\Models\{Customer,Product,Supplier,User};
use App\Services\{PurchaseService,SaleService,StockService};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\{Permission,Role};
use Spatie\Permission\PermissionRegistrar;
class DatabaseSeeder extends Seeder {
    public function run():void{
        $user=User::updateOrCreate(['email'=>env('DEFAULT_USER_EMAIL','admin@example.com')],['name'=>env('DEFAULT_USER_NAME','Admin'),'password'=>env('DEFAULT_USER_PASSWORD','password')]);
        $this->seedRolesAndPermissions($user);
        Customer::updateOrCreate(['is_walk_in'=>true],['name'=>'Walk-in Customer','opening_balance'=>0,'is_active'=>true]);
        foreach(['app_name'=>'Stock Manager','company_name'=>'Stock Manager','company_tagline'=>'Procurement, Sales and Inventory','company_address'=>'','company_phone'=>'','company_email'=>'','company_website'=>'','company_tax_number'=>'','currency'=>'PKR','quantity_precision'=>'3'] as $key=>$value) DB::table('settings')->insertOrIgnore(['key'=>$key,'value'=>$value,'created_at'=>now(),'updated_at'=>now()]);
        if(filter_var(env('SEED_DEMO_DATA',false),FILTER_VALIDATE_BOOL)){
            $this->seedDemoData($user);
        }
    }

    private function seedRolesAndPermissions(User $administrator): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = [
            'dashboard.view',
            'products.view', 'products.manage',
            'suppliers.view', 'suppliers.manage',
            'customers.view', 'customers.manage',
            'purchases.view', 'purchases.create', 'purchases.cancel',
            'sales.view', 'sales.create', 'sales.cancel',
            'stock.view', 'stock.adjust',
            'payments.view', 'payments.create',
            'reports.view',
            'users.manage',
            'settings.manage',
        ];
        foreach ($permissions as $permission) Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        $administratorRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'sanctum']);
        $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'sanctum']);
        $operatorRole = Role::firstOrCreate(['name' => 'Operator', 'guard_name' => 'sanctum']);
        $administratorRole->syncPermissions($permissions);
        $managerRole->syncPermissions(array_values(array_diff($permissions, ['users.manage', 'settings.manage'])));
        $operatorRole->syncPermissions([
            'dashboard.view', 'products.view', 'suppliers.view', 'customers.view',
            'purchases.view', 'purchases.create', 'sales.view', 'sales.create',
            'stock.view', 'payments.view', 'payments.create', 'reports.view',
        ]);
        $administrator->syncRoles([$administratorRole]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedDemoData(User $user): void
    {
        $suppliers=collect([
            ['name'=>'Pak Wholesale Traders','contact_person'=>'Hamza Khan','phone'=>'0300-1112233','email'=>'orders@pakwholesale.test','address'=>'Shah Alam Market, Lahore','opening_balance'=>12500],
            ['name'=>'Metro Office Supplies','contact_person'=>'Sara Ahmed','phone'=>'0312-4455667','email'=>'sales@metrooffice.test','address'=>'I.I. Chundrigar Road, Karachi','opening_balance'=>0],
            ['name'=>'Green Valley Foods','contact_person'=>'Usman Tariq','phone'=>'0333-7788990','email'=>'accounts@greenvalley.test','address'=>'Industrial Estate, Faisalabad','opening_balance'=>8200],
            ['name'=>'Prime Packaging Co.','contact_person'=>'Ali Raza','phone'=>'0301-9900112','email'=>'hello@primepack.test','address'=>'Sundar Industrial Estate, Lahore','opening_balance'=>0],
        ])->mapWithKeys(fn($data)=>[$data['name']=>Supplier::firstOrCreate(['name'=>$data['name']],$data)]);

        $customers=collect([
            ['name'=>'City Mart','phone'=>'0321-1002003','email'=>'citymart@example.test','address'=>'Model Town, Lahore','opening_balance'=>4500],
            ['name'=>'Corner Shop','phone'=>'0302-2223344','address'=>'Gulberg, Lahore','opening_balance'=>0],
            ['name'=>'Fresh Basket','phone'=>'0345-5566778','email'=>'freshbasket@example.test','address'=>'DHA Phase 4, Lahore','opening_balance'=>2800],
            ['name'=>'Office Hub','phone'=>'0315-7788123','email'=>'officehub@example.test','address'=>'Blue Area, Islamabad','opening_balance'=>0],
            ['name'=>'Daily Needs Store','phone'=>'0308-4040506','address'=>'Satellite Town, Rawalpindi','opening_balance'=>1200],
        ])->mapWithKeys(fn($data)=>[$data['name']=>Customer::firstOrCreate(['name'=>$data['name']],$data)]);
        $customers->put('Walk-in Customer',Customer::where('is_walk_in',true)->firstOrFail());

        $products=collect([
            ['sku'=>'STN-001','name'=>'A4 Copier Paper 80gsm','unit'=>'ream','purchase_price'=>1120,'sale_price'=>1350,'minimum_stock_level'=>12,'description'=>'500-sheet premium white copier paper'],
            ['sku'=>'STN-002','name'=>'Blue Ballpoint Pen Pack','unit'=>'box','purchase_price'=>360,'sale_price'=>480,'minimum_stock_level'=>8,'description'=>'Box of 20 smooth-writing pens'],
            ['sku'=>'PKG-001','name'=>'Medium Carton Box','unit'=>'piece','purchase_price'=>95,'sale_price'=>135,'minimum_stock_level'=>25,'description'=>'Five-ply shipping carton'],
            ['sku'=>'PKG-002','name'=>'Packing Tape 2 inch','unit'=>'roll','purchase_price'=>145,'sale_price'=>210,'minimum_stock_level'=>15],
            ['sku'=>'GRC-001','name'=>'Basmati Rice 5kg','unit'=>'bag','purchase_price'=>1420,'sale_price'=>1680,'minimum_stock_level'=>10],
            ['sku'=>'GRC-002','name'=>'Cooking Oil 3L','unit'=>'bottle','purchase_price'=>1280,'sale_price'=>1450,'minimum_stock_level'=>8],
            ['sku'=>'CLN-001','name'=>'Floor Cleaner 1L','unit'=>'bottle','purchase_price'=>285,'sale_price'=>390,'minimum_stock_level'=>10],
            ['sku'=>'CLN-002','name'=>'Tissue Box 200 Pulls','unit'=>'box','purchase_price'=>195,'sale_price'=>275,'minimum_stock_level'=>18],
        ])->mapWithKeys(function($data){
            $product=Product::firstOrCreate(['sku'=>$data['sku']],[...$data,'average_cost'=>0,'current_stock'=>0,'is_active'=>true]);
            return [$data['sku']=>$product];
        });

        if(DB::table('purchases')->where('notes','Demo opening purchase')->exists()) return;

        $purchases=app(PurchaseService::class);
        $sales=app(SaleService::class);
        $purchaseRows=[
            [-24,'Pak Wholesale Traders','cash',25000,[['STN-001',35,1080],['STN-002',28,350],['PKG-002',45,140]]],
            [-18,'Green Valley Foods','bank_transfer',30000,[['GRC-001',30,1380],['GRC-002',25,1250],['CLN-001',30,275]]],
            [-11,'Prime Packaging Co.','cash',8000,[['PKG-001',90,92],['PKG-002',35,143],['CLN-002',50,188]]],
            [-4,'Metro Office Supplies','card',20000,[['STN-001',25,1140],['STN-002',20,365],['CLN-002',30,198]]],
            [0,'Green Valley Foods','bank_transfer',15000,[['GRC-001',18,1440],['GRC-002',14,1290],['CLN-001',20,290]]],
        ];
        foreach($purchaseRows as [$days,$supplier,$method,$paid,$items]){
            $purchases->create(['purchase_date'=>now()->addDays($days)->toDateString(),'supplier_id'=>$suppliers[$supplier]->id,'payment_method'=>$method,
                'supplier_invoice_number'=>'INV-'.str_pad((string)(1000+abs($days)),4,'0',STR_PAD_LEFT),'discount'=>500,'additional_cost'=>750,'paid_amount'=>$paid,
                'notes'=>'Demo opening purchase','items'=>collect($items)->map(fn($i)=>['product_id'=>$products[$i[0]]->id,'quantity'=>$i[1],'unit_price'=>$i[2],'discount'=>0])->all()],$user->id);
        }

        $saleRows=[
            [-15,'City Mart','bank_transfer',15000,[['STN-001',8,1350],['STN-002',6,480],['CLN-002',10,275]]],
            [-9,'Fresh Basket','cash',22000,[['GRC-001',9,1680],['GRC-002',7,1450],['CLN-001',6,390]]],
            [-5,'Office Hub','card',15000,[['STN-001',10,1375],['STN-002',8,490],['PKG-002',12,210]]],
            [-2,'Corner Shop','cash',9000,[['PKG-001',35,135],['PKG-002',15,215],['CLN-002',14,280]]],
            [-1,'Daily Needs Store','cash',12000,[['GRC-001',10,1690],['GRC-002',8,1460],['CLN-001',9,395]]],
            [0,'Walk-in Customer','cash',5790,[['STN-002',3,500],['PKG-001',8,140],['CLN-002',12,285]]],
        ];
        foreach($saleRows as [$days,$customer,$method,$paid,$items]){
            $sales->create(['sale_date'=>now()->addDays($days)->toDateString(),'customer_id'=>$customers[$customer]->id,'payment_method'=>$method,
                'discount'=>250,'tax'=>0,'paid_amount'=>$paid,'notes'=>'Demo sale',
                'items'=>collect($items)->map(fn($i)=>['product_id'=>$products[$i[0]]->id,'quantity'=>$i[1],'unit_price'=>$i[2],'discount'=>0])->all()],$user->id);
        }

        app(StockService::class)->adjust(['product_id'=>$products['PKG-001']->id,'adjustment_date'=>now()->subDay()->toDateString(),'adjustment_type'=>'decrease','quantity'=>2,'reason'=>'Damaged during handling'],$user->id);
        app(StockService::class)->adjust(['product_id'=>$products['STN-002']->id,'adjustment_date'=>now()->toDateString(),'adjustment_type'=>'increase','quantity'=>1,'reason'=>'Count correction'],$user->id);
    }
}
