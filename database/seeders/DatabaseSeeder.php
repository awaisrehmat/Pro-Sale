<?php
namespace Database\Seeders;
use App\Models\{Company,Customer,Group,Product,Supplier,User,UnitOfMeasurement,ProductCategory,ProductSubcategory};
use App\Tenancy\CompanyContext;
use App\Services\{PurchaseService,SaleService,StockService};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\{Permission,Role};
use Spatie\Permission\PermissionRegistrar;
class DatabaseSeeder extends Seeder {
    public function run():void{
        $group=Group::firstOrCreate(['code'=>'MAIN'],['name'=>'Main Group','is_active'=>true]);
        $user=User::updateOrCreate(['email'=>env('DEFAULT_USER_EMAIL','admin@example.com')],['group_id'=>$group->id,'name'=>env('DEFAULT_USER_NAME','Admin'),'password'=>env('DEFAULT_USER_PASSWORD','password'),'is_group_admin'=>true,'is_active'=>true]);
        $this->seedRolesAndPermissions($user);
        $companies=collect([['name'=>'Main Trading Company','code'=>'MAIN'],['name'=>'Group Distribution Company','code'=>'DIST']])->map(fn($row)=>Company::firstOrCreate(['group_id'=>$group->id,'code'=>$row['code']],['name'=>$row['name'],'is_active'=>true]));
        $user->companies()->syncWithoutDetaching($companies->pluck('id'));
        foreach($companies as $index=>$company){app(CompanyContext::class)->set($company);$this->seedCompany($user,$index===0&&filter_var(env('SEED_DEMO_DATA',false),FILTER_VALIDATE_BOOL));}
    }

    private function seedCompany(User $user,bool $demo):void{
        Customer::updateOrCreate(['is_walk_in'=>true],['name'=>'Walk-in Customer','opening_balance'=>0,'is_active'=>true]);
        $company=app(CompanyContext::class)->company();
        foreach(['app_name'=>$company->name,'company_name'=>$company->name,'company_tagline'=>'Procurement, Sales and Inventory','company_address'=>'','company_phone'=>'','company_email'=>'','company_website'=>'','company_tax_number'=>'','company_logo'=>'','currency'=>'PKR','quantity_precision'=>'3'] as $key=>$value) DB::table('settings')->insertOrIgnore(['company_id'=>$company->id,'key'=>$key,'value'=>$value,'created_at'=>now(),'updated_at'=>now()]);
        foreach([['Piece','pc',0],['Kilogram','kg',3],['Gram','g',3],['Litre','L',3],['Metre','m',3],['Box','box',0],['Pack','pack',0],['Dozen','doz',0],['Carton','ctn',0],['Ream','ream',0],['Bag','bag',0],['Bottle','bottle',0],['Roll','roll',0]] as [$name,$symbol,$places]) UnitOfMeasurement::firstOrCreate(['symbol'=>$symbol],['name'=>$name,'decimal_places'=>$places,'is_active'=>true]);
        $unitAliases=['piece'=>'pc','pieces'=>'pc','pcs'=>'pc'];
        Product::whereNull('unit_of_measurement_id')->get()->each(function(Product $product)use($unitAliases){$symbol=$unitAliases[strtolower($product->unit)]??$product->unit;$uom=UnitOfMeasurement::whereRaw('LOWER(symbol) = ?', [strtolower($symbol)])->first();if($uom)$product->update(['unit_of_measurement_id'=>$uom->id]);});
        $this->seedProductCategories();
        if($demo){
            $this->seedDemoData($user);
            $this->seedProductCategories();
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
            'companies.manage', 'reports.consolidated',
        ];
        foreach ($permissions as $permission) Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        $administratorRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'sanctum']);
        $managerRole = Role::firstOrCreate(['name' => 'Manager', 'guard_name' => 'sanctum']);
        $operatorRole = Role::firstOrCreate(['name' => 'Operator', 'guard_name' => 'sanctum']);
        // Administrator is the unrestricted system role. Sync from the
        // permission table so newly introduced permissions are included too.
        $administratorRole->syncPermissions(
            Permission::where('guard_name', 'sanctum')->get()
        );
        $managerRole->syncPermissions(array_values(array_diff($permissions, ['users.manage', 'settings.manage'])));
        $operatorRole->syncPermissions([
            'dashboard.view', 'products.view', 'suppliers.view', 'customers.view',
            'purchases.view', 'purchases.create', 'sales.view', 'sales.create',
            'stock.view', 'payments.view', 'payments.create', 'reports.view',
        ]);
        $administrator->syncRoles([$administratorRole]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedProductCategories(): void
    {
        $structure = [
            'Stationery & Office' => ['Paper Products', 'Writing Instruments', 'Office Accessories'],
            'Packaging' => ['Carton Boxes', 'Tapes & Adhesives', 'Bags & Wrapping'],
            'Grocery' => ['Rice & Grains', 'Cooking Oil', 'Dry Food'],
            'Cleaning Supplies' => ['Floor Care', 'Tissues & Paper', 'Household Cleaners'],
            'Beverages' => ['Water', 'Soft Drinks', 'Tea & Coffee'],
            'Personal Care' => ['Soap & Sanitizers', 'Hair Care', 'Oral Care'],
        ];

        foreach ($structure as $categoryName => $subcategoryNames) {
            $category = ProductCategory::firstOrCreate(['name' => $categoryName], ['is_active' => true]);
            foreach ($subcategoryNames as $subcategoryName) {
                ProductSubcategory::firstOrCreate(
                    ['product_category_id' => $category->id, 'name' => $subcategoryName],
                    ['is_active' => true]
                );
            }
        }

        $assignments = [
            'STN-001' => ['Stationery & Office', 'Paper Products'],
            'STN-002' => ['Stationery & Office', 'Writing Instruments'],
            'PKG-001' => ['Packaging', 'Carton Boxes'],
            'PKG-002' => ['Packaging', 'Tapes & Adhesives'],
            'GRC-001' => ['Grocery', 'Rice & Grains'],
            'GRC-002' => ['Grocery', 'Cooking Oil'],
            'CLN-001' => ['Cleaning Supplies', 'Floor Care'],
            'CLN-002' => ['Cleaning Supplies', 'Tissues & Paper'],
        ];

        foreach ($assignments as $sku => [$categoryName, $subcategoryName]) {
            $product = Product::where('sku', $sku)->whereNull('product_category_id')->first();
            if (! $product) continue;
            $category = ProductCategory::where('name', $categoryName)->firstOrFail();
            $subcategory = ProductSubcategory::where('product_category_id', $category->id)->where('name', $subcategoryName)->firstOrFail();
            $product->update(['product_category_id' => $category->id, 'product_subcategory_id' => $subcategory->id]);
        }
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
