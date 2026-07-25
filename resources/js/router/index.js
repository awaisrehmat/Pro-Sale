import {createRouter,createWebHistory} from 'vue-router';
import Login from '../pages/Login.vue'; import Dashboard from '../pages/Dashboard.vue'; import ListPage from '../pages/ListPage.vue'; import TransactionForm from '../pages/TransactionForm.vue'; import Reports from '../pages/Reports.vue';
const routes=[{path:'/login',component:Login},{path:'/',component:Dashboard,meta:{auth:true}},
 ...['products','suppliers','customers','purchases','sales','payments','stock-movements'].map(x=>({path:`/${x}`,component:ListPage,props:{resource:x},meta:{auth:true}})),
 {path:'/purchases/new',component:TransactionForm,props:{type:'purchases'},meta:{auth:true}},{path:'/sales/new',component:TransactionForm,props:{type:'sales'},meta:{auth:true}},
 {path:'/reports',component:Reports,meta:{auth:true}}];
const router=createRouter({history:createWebHistory(),routes});router.beforeEach(to=>to.meta.auth&&!localStorage.getItem('token')?'/login':true);export default router;
