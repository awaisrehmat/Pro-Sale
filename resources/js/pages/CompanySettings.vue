<script setup>
import {onMounted,ref} from 'vue';
import api from '../services/api';
import AppIcon from '../components/common/AppIcon.vue';

const form=ref({company_name:'',company_tagline:'',company_address:'',company_phone:'',company_email:'',company_website:'',company_tax_number:'',currency:'PKR'});
const loading=ref(true),saving=ref(false),uploading=ref(false),error=ref(''),notice=ref('');

onMounted(async()=>{try{const {data}=await api.get('/company-settings');form.value={...form.value,...data.data}}catch(e){error.value=e.response?.data?.message||'Unable to load company details.'}finally{loading.value=false}});
async function save(){saving.value=true;error.value='';notice.value='';try{const {data}=await api.put('/company-settings',form.value);form.value=data.data;notice.value=data.message}catch(e){error.value=Object.values(e.response?.data?.errors||{}).flat().join(' ')||e.response?.data?.message||'Unable to save company details.'}finally{saving.value=false}}
async function uploadLogo(event){const file=event.target.files?.[0];if(!file)return;uploading.value=true;error.value='';notice.value='';const body=new FormData();body.append('logo',file);try{const {data}=await api.post('/company-settings/logo',body);form.value={...form.value,...data.data};notice.value=data.message}catch(e){error.value=Object.values(e.response?.data?.errors||{}).flat().join(' ')||e.response?.data?.message||'Unable to upload the logo.'}finally{uploading.value=false;event.target.value=''}}
async function removeLogo(){if(!confirm('Remove the company logo from future vouchers?'))return;uploading.value=true;error.value='';try{const {data}=await api.delete('/company-settings/logo');form.value={...form.value,...data.data};notice.value=data.message}catch(e){error.value=e.response?.data?.message||'Unable to remove the logo.'}finally{uploading.value=false}}
</script>

<template>
<div class="page-head"><div><span class="eyebrow">Administration</span><h1>Company settings</h1><p class="page-subtitle">Manage the identity and contact details printed on official documents.</p></div></div>
<div v-if="loading" class="panel empty-state">Loading company details…</div>
<form v-else class="panel company-settings-form" @submit.prevent="save">
  <div class="panel-head"><div><h2>Business profile</h2><p>These details appear on purchase vouchers, invoices, receipts, and payment vouchers.</p></div><span class="company-preview-mark">{{form.company_name?.slice(0,2).toUpperCase()||'CO'}}</span></div>
  <div v-if="error" class="error">{{error}}</div><div v-if="notice" class="success-message">{{notice}}</div>
  <section class="company-logo-setting"><div class="company-logo-preview"><img v-if="form.company_logo_url" :src="form.company_logo_url" alt="Company logo"><span v-else>{{form.company_name?.replace(/[^a-z0-9]/gi,'').slice(0,2).toUpperCase()||'CO'}}</span></div><div><strong>Company logo</strong><p>Upload a clear PNG or JPG up to 2 MB. A square or landscape logo with a white or transparent background prints best.</p><div class="company-logo-actions"><label class="btn secondary with-icon" :class="{disabled:uploading}"><AppIcon name="upload"/>{{uploading?'Uploading…':form.company_logo_url?'Replace logo':'Upload logo'}}<input type="file" accept="image/png,image/jpeg" :disabled="uploading" @change="uploadLogo"></label><button v-if="form.company_logo_url" type="button" class="secondary danger-button" :disabled="uploading" @click="removeLogo">Remove</button></div></div></section>
  <div class="form-grid">
    <div class="field"><label>Company name</label><input v-model.trim="form.company_name" required maxlength="120"></div>
    <div class="field"><label>Tagline</label><input v-model.trim="form.company_tagline" maxlength="160" placeholder="Procurement, Sales and Inventory"></div>
    <div class="field"><label>Currency code</label><input v-model.trim="form.currency" required maxlength="10" placeholder="PKR"></div>
    <div class="field"><label>Phone</label><input v-model.trim="form.company_phone" maxlength="50"></div>
    <div class="field"><label>Email</label><input v-model.trim="form.company_email" type="email" maxlength="120"></div>
    <div class="field"><label>Website</label><input v-model.trim="form.company_website" maxlength="160" placeholder="www.example.com"></div>
    <div class="field"><label>Tax / registration number</label><input v-model.trim="form.company_tax_number" maxlength="80"></div>
    <div class="field company-address"><label>Business address</label><textarea v-model.trim="form.company_address" rows="3" maxlength="500"></textarea></div>
  </div>
  <div class="form-actions"><button class="with-icon" :disabled="saving"><AppIcon name="edit"/>{{saving?'Saving…':'Save company details'}}</button></div>
</form>
</template>
