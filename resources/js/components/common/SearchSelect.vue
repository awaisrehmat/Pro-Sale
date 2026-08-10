<script setup>
import {computed,nextTick,onBeforeUnmount,onMounted,ref,watch} from 'vue';

const props=defineProps({modelValue:[String,Number],options:{type:Array,default:()=>[]},placeholder:{type:String,default:'Type to search…'},disabled:Boolean,required:Boolean});
const emit=defineEmits(['update:modelValue','change']);
const open=ref(false),query=ref(''),active=ref(0),input=ref(null),menuStyle=ref({});
const selected=computed(()=>props.options.find(option=>String(option.value)===String(props.modelValue)));
const filtered=computed(()=>{const term=query.value.trim().toLowerCase();return (term?props.options.filter(option=>option.label.toLowerCase().includes(term)):props.options).slice(0,100)});
watch(()=>props.modelValue,()=>{if(!open.value)query.value=selected.value?.label||''},{immediate:true});
watch(filtered,()=>active.value=0);
function updatePosition(){if(!input.value||!open.value)return;const rect=input.value.getBoundingClientRect();menuStyle.value={position:'fixed',left:`${rect.left}px`,top:`${rect.bottom+4}px`,width:`${rect.width}px`}}
function focus(){if(props.disabled)return;query.value='';open.value=true;nextTick(()=>{input.value?.select();updatePosition()})}
function choose(option){emit('update:modelValue',option.value);emit('change',option.value);query.value=option.label;open.value=false}
function typed(){open.value=true;updatePosition();if(selected.value&&query.value!==selected.value.label)emit('update:modelValue','')}
function keydown(event){if(event.key==='ArrowDown'){event.preventDefault();active.value=Math.min(active.value+1,filtered.value.length-1)}else if(event.key==='ArrowUp'){event.preventDefault();active.value=Math.max(active.value-1,0)}else if(event.key==='Enter'&&open.value&&filtered.value[active.value]){event.preventDefault();choose(filtered.value[active.value])}else if(event.key==='Escape'){open.value=false;query.value=selected.value?.label||''}}
function close(){setTimeout(()=>{open.value=false;query.value=selected.value?.label||''},150)}
onMounted(()=>{window.addEventListener('resize',updatePosition);window.addEventListener('scroll',updatePosition,true)});
onBeforeUnmount(()=>{window.removeEventListener('resize',updatePosition);window.removeEventListener('scroll',updatePosition,true)});
</script>
<template><div class="search-select" :class="{open,disabled}"><input ref="input" v-model="query" :placeholder="placeholder" :disabled="disabled" :required="required&&!modelValue" autocomplete="off" role="combobox" :aria-expanded="open" @focus="focus" @click="focus" @input="typed" @keydown="keydown" @blur="close"><span class="search-select-chevron" aria-hidden="true"></span><Teleport to="body"><div v-if="open" class="search-select-menu search-select-overlay" :style="menuStyle"><button v-for="(option,index) in filtered" :key="option.value" type="button" :class="{active:index===active,selected:String(option.value)===String(modelValue)}" @mousedown.prevent="choose(option)"><span>{{option.label}}</span><small v-if="option.meta">{{option.meta}}</small></button><div v-if="!filtered.length" class="search-select-empty">No matching options</div></div></Teleport></div></template>
