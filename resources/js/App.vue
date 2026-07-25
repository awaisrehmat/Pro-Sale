<script setup>
import {computed} from 'vue';import {useRoute,useRouter} from 'vue-router';import api from './services/api';
const route=useRoute(),router=useRouter(),logged=computed(()=>route.path!='/login'&&localStorage.getItem('token'));
const links=[['/','Dashboard'],['/products','Products'],['/suppliers','Suppliers'],['/customers','Customers'],['/purchases','Purchases'],['/sales','Sales'],['/stock-movements','Stock'],['/payments','Payments'],['/reports','Reports']];
async function logout(){try{await api.post('/logout')}finally{localStorage.removeItem('token');router.push('/login')}}
</script>
<template><div v-if="logged" class="shell"><aside><div class="brand">Stock Manager</div><nav><RouterLink v-for="[to,label] in links" :to="to" :key="to">{{label}}</RouterLink></nav><button class="logout" @click="logout">Log out</button></aside><main><RouterView/></main></div><RouterView v-else/></template>
