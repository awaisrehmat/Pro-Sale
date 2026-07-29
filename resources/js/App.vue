<script setup>
import {computed,ref} from 'vue';import {useRoute,useRouter} from 'vue-router';import api from './services/api';import AppIcon from './components/common/AppIcon.vue';import {can,clearAuth,currentUser} from './services/auth';
const route=useRoute(),router=useRouter(),mobileOpen=ref(false),logged=computed(()=>route.path!='/login'&&localStorage.getItem('token')),user=currentUser();
const groups=[
 {label:'Overview',links:[['/','dashboard','Dashboard','dashboard.view']]},
 {label:'Operations',links:[['/purchases','purchase','Purchases','purchases.view'],['/sales','sale','Sales','sales.view'],['/payments','payment','Payments','payments.view']]},
 {label:'Directory',links:[['/products','product','Products','products.view'],['/suppliers','supplier','Suppliers','suppliers.view'],['/customers','customer','Customers','customers.view']]},
 {label:'Insights',links:[['/stock-movements','stock','Stock ledger','stock.view'],['/reports','report','Reports','reports.view'],['/users','customer','User administration','users.manage']]},
];
const visibleGroups=computed(()=>groups.map(group=>({...group,links:group.links.filter(link=>can(link[3]))})).filter(group=>group.links.length));
const title=computed(()=>groups.flatMap(g=>g.links).find(link=>link[0]===route.path)?.[2]||'Stock Manager');
async function logout(){try{await api.post('/logout')}finally{clearAuth();router.push('/login')}}
</script>
<template><div v-if="logged" class="app-shell"><div v-if="mobileOpen" class="sidebar-backdrop" @click="mobileOpen=false"></div><aside :class="{open:mobileOpen}"><div class="brand"><div class="brand-mark">SM</div><div><strong>Stock Manager</strong><span>Business operations</span></div></div><nav><section v-for="group in visibleGroups" :key="group.label"><div class="nav-label">{{group.label}}</div><RouterLink v-for="[to,icon,label] in group.links" :to="to" :key="to" @click="mobileOpen=false"><span class="nav-icon"><AppIcon :name="icon" :size="15"/></span>{{label}}</RouterLink></section></nav><div class="sidebar-footer"><div class="user-chip"><span>{{user?.name?.slice(0,1).toUpperCase()||'U'}}</span><div><strong>{{user?.name||'User'}}</strong><small>{{user?.roles?.[0]?.name||'Assigned user'}}</small></div></div><button class="icon-button" title="Log out" @click="logout"><AppIcon name="logout"/></button></div></aside><div class="workspace"><header class="topbar"><button class="mobile-menu" @click="mobileOpen=true"><AppIcon name="menu" :size="20"/></button><div><span class="eyebrow">Workspace</span><strong>{{title}}</strong></div><div class="topbar-actions"><span class="live-dot"></span><span>System online</span></div></header><main><RouterView/></main></div></div><RouterView v-else/></template>
