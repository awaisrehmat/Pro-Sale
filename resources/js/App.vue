<script setup>
import {computed,onMounted,ref} from 'vue';
import {useRoute,useRouter} from 'vue-router';
import api from './services/api';
import AppIcon from './components/common/AppIcon.vue';
import {authCompanies,authPermissions,authToken,authUser,clearAuth,currentCompany,selectCompany,setAuth} from './services/auth';

const route=useRoute(),router=useRouter(),mobileOpen=ref(false);
const logged=computed(()=>route.path!='/login'&&Boolean(authToken.value));
const user=authUser,permissionList=authPermissions;
const company=ref({company_name:currentCompany.value?.name||'Stock Manager',company_tagline:'Business operations'});
const companyMark=computed(()=>company.value.company_name?.replace(/[^a-z0-9]/gi,'').slice(0,2).toUpperCase()||'CO');
const groups=[
 {label:'Overview',links:[['/','dashboard','Dashboard','dashboard.view']]},
 {label:'Operations',links:[['/purchases','purchase','Purchases','purchases.view'],['/sales','sale','Sales','sales.view'],['/payments','payment','Payments','payments.view']]},
 {label:'Directory',links:[['/products','product','Products','products.view'],['/suppliers','supplier','Suppliers','suppliers.view'],['/customers','customer','Customers','customers.view']]},
 {label:'Insights',links:[['/stock-movements','stock','Stock ledger','stock.view'],['/reports','report','Reports','reports.view']]},
 {label:'Administration',links:[['/users','customer','User administration','users.manage'],['/company-settings','settings','Company settings','settings.manage'],['/product-settings','product','Product settings','settings.manage']]},
 {label:'Group',links:[['/companies','settings','Companies','companies.manage']]},
];
const visibleGroups=computed(()=>groups.map(group=>({...group,links:group.links.filter(link=>permissionList.value.includes(link[3]))})).filter(group=>group.links.length));
const title=computed(()=>groups.flatMap(g=>g.links).find(link=>link[0]===route.path)?.[2]||company.value.company_name);
async function logout(){try{await api.post('/logout')}finally{clearAuth();router.push('/login')}}
async function changeCompany(event){const selected=authCompanies.value.find(item=>String(item.id)===event.target.value);if(!selected)return;selectCompany(selected);company.value={company_name:selected.name,company_tagline:'Business operations'};try{const {data}=await api.get('/user');setAuth({token:authToken.value,...data.data});company.value=data.data.company_profile||company.value}catch{}router.go(0)}
onMounted(async()=>{
 try{const {data}=await api.get('/company-profile');company.value=data.data}catch{}
 if(!authToken.value)return;
 try{const {data}=await api.get('/user');setAuth({token:authToken.value,...data.data});company.value=data.data.company_profile||company.value}catch{}
});
</script>

<template>
<div v-if="logged" class="app-shell">
 <div v-if="mobileOpen" class="sidebar-backdrop" @click="mobileOpen=false"></div>
 <aside :class="{open:mobileOpen}">
  <div class="brand"><div class="brand-mark" :class="{'has-logo':company.company_logo_url}"><img v-if="company.company_logo_url" :src="company.company_logo_url" :alt="`${company.company_name} logo`"><template v-else>{{companyMark}}</template></div><div><strong>{{company.company_name}}</strong><span>{{company.company_tagline||'Business operations'}}</span></div></div>
  <nav><section v-for="group in visibleGroups" :key="group.label"><div class="nav-label">{{group.label}}</div><RouterLink v-for="[to,icon,label] in group.links" :to="to" :key="to" @click="mobileOpen=false"><span class="nav-icon"><AppIcon :name="icon" :size="15"/></span>{{label}}</RouterLink></section></nav>
  <div class="sidebar-footer"><div class="user-chip"><span>{{user?.name?.slice(0,1).toUpperCase()||'U'}}</span><div><strong>{{user?.name||'User'}}</strong><small>{{user?.roles?.[0]?.name||'Assigned user'}}</small></div></div><button class="icon-button" title="Log out" @click="logout"><AppIcon name="logout"/></button></div>
 </aside>
 <div class="workspace"><header class="topbar"><button class="mobile-menu" @click="mobileOpen=true"><AppIcon name="menu" :size="20"/></button><div><span class="eyebrow">{{company.company_name}}</span><strong>{{title}}</strong></div><div class="topbar-actions"><select v-if="authCompanies.length>1" class="company-switcher" :value="currentCompany?.id" @change="changeCompany"><option v-for="item in authCompanies" :key="item.id" :value="item.id">{{item.name}}</option></select><span class="live-dot"></span><span>System online</span></div></header><main><RouterView/></main></div>
</div>
<RouterView v-else/>
</template>
