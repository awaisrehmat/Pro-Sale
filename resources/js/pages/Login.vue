<script setup>
import {ref} from 'vue';import {useRouter} from 'vue-router';import api from '../services/api';
const email=ref('admin@example.com'),password=ref('password'),error=ref(''),router=useRouter();
async function submit(){error.value='';try{const {data}=await api.post('/login',{email:email.value,password:password.value});localStorage.setItem('token',data.data.token);router.push('/')}catch(e){error.value=e.response?.data?.message||'Unable to log in.'}}
</script>
<template><div class="login"><form class="panel" @submit.prevent="submit"><h1>Stock Manager</h1><p class="muted">Sign in to manage purchasing, sales, and stock.</p><div v-if="error" class="error">{{error}}</div><div class="field"><label>Email</label><input v-model="email" type="email" required></div><div class="field" style="margin-top:12px"><label>Password</label><input v-model="password" type="password" required></div><button style="width:100%;margin-top:18px">Sign in</button></form></div></template>
