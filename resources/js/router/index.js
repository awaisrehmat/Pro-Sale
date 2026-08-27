import {createRouter,createWebHistory} from 'vue-router';
import Login from '../pages/Login.vue';import Dashboard from '../pages/Dashboard.vue';import ListPage from '../pages/ListPage.vue';import TransactionForm from '../pages/TransactionForm.vue';import Reports from '../pages/Reports.vue';import UserAdministration from '../pages/UserAdministration.vue';import CompanySettings from '../pages/CompanySettings.vue';import ProductCategories from '../pages/ProductCategories.vue';import {can} from '../services/auth';
import Companies from '../pages/Companies.vue';
const resources={products:'products.view',suppliers:'suppliers.view',customers:'customers.view',purchases:'purchases.view',sales:'sales.view','stock-movements':'stock.view'};
const routes=[
 {path:'/login',component:Login},
 {path:'/',component:Dashboard,meta:{auth:true,permission:'dashboard.view'}},
 ...Object.entries(resources).map(([resource,permission])=>({path:`/${resource}`,component:ListPage,props:{resource},meta:{auth:true,permission}})),
 {path:'/payment-vouchers',component:ListPage,props:{resource:'payments',paymentType:'supplier_payment'},meta:{auth:true,permission:'payments.view'}},
 {path:'/receipt-vouchers',component:ListPage,props:{resource:'payments',paymentType:'customer_payment'},meta:{auth:true,permission:'payments.view'}},
 {path:'/payments',redirect:'/payment-vouchers'},
 {path:'/purchases/new',component:TransactionForm,props:{type:'purchases'},meta:{auth:true,permission:'purchases.create'}},
 {path:'/sales/new',component:TransactionForm,props:{type:'sales'},meta:{auth:true,permission:'sales.create'}},
 {path:'/reports',component:Reports,meta:{auth:true,permission:'reports.view'}},
 {path:'/users',component:UserAdministration,meta:{auth:true,permission:'users.manage'}},
 {path:'/company-settings',component:CompanySettings,meta:{auth:true,permission:'settings.manage'}},
 {path:'/product-settings',component:ProductCategories,meta:{auth:true,permission:'settings.manage'}},
 {path:'/companies',component:Companies,meta:{auth:true,permission:'companies.manage'}},
 {path:'/product-categories',redirect:'/product-settings'},
];
const router=createRouter({history:createWebHistory(),routes});
router.beforeEach(to=>{
 const token=localStorage.getItem('token');
 if(to.meta.auth&&!token)return'/login';
 if(to.meta.permission&&!can(to.meta.permission)){
  const fallback=routes.find(route=>route.meta?.auth&&route.meta?.permission&&can(route.meta.permission));
  if(fallback&&fallback.path!==to.path)return fallback.path;
  // Let the page mount when cached permissions are missing. App.vue then
  // validates the token with /user and clears stale browser sessions safely.
  // Returning "/" here while already navigating to "/" creates a redirect loop.
  return true;
 }
 return true;
});
export default router;
