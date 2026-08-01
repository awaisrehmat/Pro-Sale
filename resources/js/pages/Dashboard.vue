<script setup>
import {computed,onMounted,ref} from 'vue';
import api from '../services/api';
import AppIcon from '../components/common/AppIcon.vue';
import {can} from '../services/auth';

const d=ref({activity:[],low_stock:[],recent_sales:[],recent_purchases:[]}),loading=ref(true),error=ref('');
onMounted(async()=>{try{d.value=(await api.get('/dashboard')).data.data}catch(e){error.value=e.response?.data?.message||'Unable to load the dashboard.'}finally{loading.value=false}});
const money=v=>'PKR '+Number(v||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
const compact=v=>Number(v||0).toLocaleString(undefined,{notation:'compact',maximumFractionDigits:1});
const maxActivity=computed(()=>Math.max(1,...d.value.activity.flatMap(x=>[x.sales,x.purchases])));
const monthMargin=computed(()=>Number(d.value.sales_month)>0?Number(d.value.gross_profit_month)/Number(d.value.sales_month)*100:0);
const dayTotal=computed(()=>Number(d.value.sales_today||0)+Number(d.value.purchases_today||0));
const metrics=computed(()=>[
 {label:'Monthly sales',value:money(d.value.sales_month),note:'Revenue this month',tone:'blue',icon:'sale'},
 {label:'Gross profit',value:money(d.value.gross_profit_month),note:`${monthMargin.value.toFixed(1)}% gross margin`,tone:'green',icon:'report'},
 {label:'Inventory value',value:money(d.value.stock_value),note:`${Number(d.value.total_stock||0).toLocaleString()} units on hand`,tone:'violet',icon:'stock'},
 {label:'Active products',value:Number(d.value.total_products||0).toLocaleString(),note:`${d.value.low_stock.length} need attention`,tone:'amber',icon:'product'},
]);
const paymentClass=status=>status==='paid'?'success':status==='partial'?'warning':'neutral';
</script>

<template>
<div class="dashboard-hero">
 <div><span class="eyebrow">Business command center</span><h1>Good {{new Date().getHours()<12?'morning':new Date().getHours()<17?'afternoon':'evening'}}</h1><p>A focused view of revenue, inventory, balances, and today’s activity.</p></div>
 <div class="page-actions"><RouterLink v-if="can('purchases.create')" class="btn secondary with-icon" to="/purchases/new"><AppIcon name="purchase"/>New purchase</RouterLink><RouterLink v-if="can('sales.create')" class="btn with-icon" to="/sales/new"><AppIcon name="sale"/>New sale</RouterLink></div>
</div>

<div v-if="loading" class="dashboard-loading"><span class="spinner dark"></span><strong>Preparing your dashboard…</strong></div>
<div v-else-if="error" class="error">{{error}}</div>
<template v-else>
 <section class="dashboard-pulse">
  <div class="pulse-title"><span>Today</span><strong>{{new Date().toLocaleDateString(undefined,{weekday:'long',day:'numeric',month:'short'})}}</strong></div>
  <div><span>Sales</span><strong>{{money(d.sales_today)}}</strong><i class="pulse-dot green"></i></div>
  <div><span>Purchases</span><strong>{{money(d.purchases_today)}}</strong><i class="pulse-dot blue"></i></div>
  <div><span>Activity value</span><strong>{{money(dayTotal)}}</strong><i class="pulse-dot violet"></i></div>
 </section>

 <div class="dashboard-metrics"><article class="dashboard-metric" v-for="m in metrics" :key="m.label"><div class="metric-icon" :class="m.tone"><AppIcon :name="m.icon" :size="17"/></div><div class="dashboard-metric-copy"><span>{{m.label}}</span><strong>{{m.value}}</strong><small>{{m.note}}</small></div></article></div>

 <div class="dashboard-primary-grid">
  <section class="panel dashboard-chart-panel"><div class="panel-head"><div><h2>Sales and purchasing trend</h2><p>Daily transaction value over the last seven days</p></div><div class="chart-legend"><span><i class="sales"></i>Sales</span><span><i class="purchases"></i>Purchases</span></div></div>
   <div class="dashboard-chart"><div class="chart-y"><span>{{compact(maxActivity)}}</span><span>{{compact(maxActivity/2)}}</span><span>0</span></div><div class="chart-plot"><div class="chart-grid-lines"><i></i><i></i><i></i></div><div class="bar-group" v-for="day in d.activity" :key="day.date" :title="`${day.date}: Sales ${money(day.sales)}, Purchases ${money(day.purchases)}`"><div class="bars"><i class="purchase-bar" :style="{height:`${Math.max(4,day.purchases/maxActivity*164)}px`}"></i><i class="sales-bar" :style="{height:`${Math.max(4,day.sales/maxActivity*164)}px`}"></i></div><span>{{day.date}}</span></div></div></div>
  </section>

  <section class="panel dashboard-attention"><div class="panel-head"><div><h2>Financial position</h2><p>Open balances requiring follow-up</p></div><span class="attention-count">{{d.low_stock.length}}</span></div>
   <div class="balance-card receivable"><span>Customer receivables</span><strong>{{money(d.customer_due)}}</strong><small>Money expected from customers</small></div>
   <div class="balance-card payable"><span>Supplier payables</span><strong>{{money(d.supplier_due)}}</strong><small>Money owed to suppliers</small></div>
   <RouterLink to="/reports" class="attention-link"><span><i></i><strong>{{d.low_stock.length}} low-stock product{{d.low_stock.length===1?'':'s'}}</strong></span><span>Review inventory →</span></RouterLink>
  </section>
 </div>

 <div class="dashboard-secondary-grid">
  <section class="panel dashboard-table"><div class="panel-head"><div><h2>Recent sales</h2><p>Latest customer invoices and payment status</p></div><RouterLink to="/sales" class="text-link">View all →</RouterLink></div><div class="table-wrap"><table><thead><tr><th>Invoice</th><th>Customer</th><th>Status</th><th class="number">Total</th></tr></thead><tbody><tr v-for="s in d.recent_sales" :key="s.id"><td><strong>{{s.sale_number}}</strong><small>{{s.sale_date}}</small></td><td>{{s.customer?.name||'Walk-in customer'}}</td><td><span class="status" :class="paymentClass(s.payment_status)">{{s.payment_status}}</span></td><td class="number"><strong>{{money(s.grand_total)}}</strong></td></tr><tr v-if="!d.recent_sales.length"><td colspan="4" class="empty-state">No sales recorded yet.</td></tr></tbody></table></div></section>
  <section class="panel low-stock-panel"><div class="panel-head"><div><h2>Inventory watchlist</h2><p>Products at or below their reorder level</p></div><RouterLink to="/reports" class="text-link">Stock report →</RouterLink></div><div class="dashboard-stock-list"><div v-for="p in d.low_stock.slice(0,5)" :key="p.id"><div class="product-avatar">{{p.name.slice(0,2).toUpperCase()}}</div><div><strong>{{p.name}}</strong><span>{{p.sku}} · Minimum {{Number(p.minimum_stock_level).toLocaleString()}}</span></div><div class="stock-level"><strong>{{Number(p.current_stock).toLocaleString()}}</strong><small>{{p.unit}}</small></div></div><div v-if="!d.low_stock.length" class="healthy-stock"><span>✓</span><div><strong>Inventory looks healthy</strong><small>All products are above their minimum level.</small></div></div></div></section>
 </div>
</template>
</template>
