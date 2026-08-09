import {createRouter,createWebHistory} from 'vue-router';
import Login from '../pages/Login.vue';import Dashboard from '../pages/Dashboard.vue';import ListPage from '../pages/ListPage.vue';import TransactionForm from '../pages/TransactionForm.vue';import Reports from '../pages/Reports.vue';import UserAdministration from '../pages/UserAdministration.vue';import CompanySettings from '../pages/CompanySettings.vue';import ProductCategories from '../pages/ProductCategories.vue';import {can} from '../services/auth';
const resources={products:'products.view',suppliers:'suppliers.view',customers:'customers.view',purchases:'purchases.view',sales:'sales.view',payments:'payments.view','stock-movements':'stock.view'};
const routes=[
 {path:'/login',component:Login},
 {path:'/',component:Dashboard,meta:{auth:true,permission:'dashboard.view'}},
 ...Object.entries(resources).map(([resource,permission])=>({path:`/${resource}`,component:ListPage,props:{resource},meta:{auth:true,permission}})),
 {path:'/purchases/new',component:TransactionForm,props:{type:'purchases'},meta:{auth:true,permission:'purchases.create'}},
 {path:'/sales/new',component:TransactionForm,props:{type:'sales'},meta:{auth:true,permission:'sales.create'}},
 {path:'/reports',component:Reports,meta:{auth:true,permission:'reports.view'}},
 {path:'/users',component:UserAdministration,meta:{auth:true,permission:'users.manage'}},
 {path:'/company-settings',component:CompanySettings,meta:{auth:true,permission:'settings.manage'}},
 {path:'/product-categories',component:ProductCategories,meta:{auth:true,permission:'settings.manage'}},
];
const router=createRouter({history:createWebHistory(),routes});
router.beforeEach(to=>{if(to.meta.auth&&!localStorage.getItem('token'))return'/login';if(to.meta.permission&&!can(to.meta.permission))return'/';return true});
export default router;
