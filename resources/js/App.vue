<script setup>
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "./services/api";
import AppIcon from "./components/common/AppIcon.vue";
const route = useRoute(),
    router = useRouter(),
    mobileOpen = ref(false),
    logged = computed(
        () => route.path != "/login" && localStorage.getItem("token"),
    );
const groups = [
    { label: "Overview", links: [["/", "dashboard", "Dashboard"]] },
    {
        label: "Operations",
        links: [
            ["/purchases", "purchase", "Purchases"],
            ["/sales", "sale", "Sales"],
            ["/payments", "payment", "Payments"],
        ],
    },
    {
        label: "Directory",
        links: [
            ["/products", "product", "Products"],
            ["/suppliers", "supplier", "Suppliers"],
            ["/customers", "customer", "Customers"],
        ],
    },
    {
        label: "Insights",
        links: [
            ["/stock-movements", "stock", "Stock ledger"],
            ["/reports", "report", "Reports"],
        ],
    },
];
const title = computed(
    () =>
        groups.flatMap((g) => g.links).find((l) => l[0] === route.path)?.[2] ||
        "Stock Manager",
);
async function logout() {
    try {
        await api.post("/logout");
    } finally {
        localStorage.removeItem("token");
        router.push("/login");
    }
}
</script>
<template>
    <div v-if="logged" class="app-shell">
        <div
            v-if="mobileOpen"
            class="sidebar-backdrop"
            @click="mobileOpen = false"
        ></div>
        <aside :class="{ open: mobileOpen }">
            <div class="brand">
                <div class="brand-mark">SM</div>
                <div>
                    <strong>Stock Manager</strong
                    ><span>Business operations</span>
                </div>
            </div>
            <nav>
                <section v-for="group in groups" :key="group.label">
                    <div class="nav-label">{{ group.label }}</div>
                    <RouterLink
                        v-for="[to, icon, label] in group.links"
                        :to="to"
                        :key="to"
                        @click="mobileOpen = false"
                        ><span class="nav-icon"><AppIcon :name="icon" :size="15"/></span
                        >{{ label }}</RouterLink
                    >
                </section>
            </nav>
            <div class="sidebar-footer">
                <div class="user-chip">
                    <span>A</span>
                    <div>
                        <strong>Administrator</strong>
                        <!-- <small>Single user</small> -->
                    </div>
                </div>
                <button class="icon-button" title="Log out" @click="logout"><AppIcon name="logout"/></button>
            </div>
        </aside>
        <div class="workspace">
            <header class="topbar">
                <button class="mobile-menu" @click="mobileOpen = true"><AppIcon name="menu" :size="20"/></button>
                <div>
                    <span class="eyebrow">Workspace</span
                    ><strong>{{ title }}</strong>
                </div>
                <div class="topbar-actions">
                    <span class="live-dot"></span><span>System online</span>
                </div>
            </header>
            <main><RouterView /></main>
        </div>
    </div>
    <RouterView v-else />
</template>
