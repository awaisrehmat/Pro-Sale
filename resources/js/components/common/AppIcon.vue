<script setup>
defineProps({name:String,size:{type:Number,default:16},stroke:{type:Number,default:1.8}});
</script>
<template><svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" :stroke-width="stroke" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
<template v-if="name==='dashboard'"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></template>
<template v-else-if="name==='purchase'"><path d="M3 5h2l2 11h11l2-7H7"/><circle cx="9" cy="20" r="1"/><circle cx="17" cy="20" r="1"/><path d="M14 3v6m-3-3 3 3 3-3"/></template>
<template v-else-if="name==='sale'"><path d="M6 2h12l2 5-2 3H6L4 7l2-5Z"/><path d="M6 10v11h12V10M9 14h6"/></template>
<template v-else-if="name==='payment'"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></template>
<template v-else-if="name==='payment-out'"><rect x="2" y="6" width="15" height="12" rx="2"/><path d="M2 10h15M5 14h3M16 3l5 5-5 5M21 8h-7"/></template>
<template v-else-if="name==='receipt'"><path d="M5 3h14v18l-2-1.5L15 21l-2-1.5L11 21l-2-1.5L7 21l-2-1.5V3Z"/><path d="M9 8h6M9 12h6M9 16h3"/></template>
<template v-else-if="name==='expense'"><path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h5M15 16l3 3m0-3-3 3"/></template>
<template v-else-if="name==='product'"><path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 7 9 5 9-5v10l-9 5-9-5V7Z"/><path d="M12 12v10"/></template>
<template v-else-if="name==='supplier'"><path d="M3 21V8l9-5 9 5v13"/><path d="M7 21v-6h10v6M8 10h.01M12 10h.01M16 10h.01"/></template>
<template v-else-if="name==='customer'"><circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0M17 11a4 4 0 0 1 5 4M18 21a6 6 0 0 0-2-4.5"/></template>
<template v-else-if="name==='stock'"><path d="M4 7h16v14H4zM2 3h20v4H2zM9 11h6"/></template>
<template v-else-if="name==='report'"><path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/></template>
<template v-else-if="name==='plus'"><path d="M12 5v14M5 12h14"/></template>
<template v-else-if="name==='search'"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></template>
<template v-else-if="name==='eye'"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></template>
<template v-else-if="name==='edit'"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5Z"/></template>
<template v-else-if="name==='print'"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></template>
<template v-else-if="name==='download'"><path d="M12 3v12m-5-5 5 5 5-5M5 21h14"/></template>
<template v-else-if="name==='upload'"><path d="M12 16V4m-5 5 5-5 5 5M5 20h14"/></template>
<template v-else-if="name==='close'"><path d="m6 6 12 12M18 6 6 18"/></template>
<template v-else-if="name==='logout'"><path d="M10 17l5-5-5-5M15 12H3M15 3h5a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-5"/></template>
<template v-else-if="name==='menu'"><path d="M4 6h16M4 12h16M4 18h16"/></template>
<template v-else-if="name==='calendar'"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></template>
<template v-else-if="name==='settings'"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1.1V21h-4v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.4H3v-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1.1V3h4v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 9c.18.37.48.67.85.85.35.17.74.25 1.14.24H21v4h-.09A1.7 1.7 0 0 0 19.4 15Z"/></template>
<template v-else><circle cx="12" cy="12" r="9"/><path d="M12 8v4l3 2"/></template>
</svg></template>
