<template>
  <NConfigProvider :locale="enUS" :theme="isDark ? darkTheme : undefined"
    :theme-overrides="isDark ? darkThemeOverrides : themeOverrides">
    <NMessageProvider>
      <AdminLayout>
        <main
          class="mb-4 mr-12 grid max-w-7xl grid-cols-[1fr_auto] items-start overflow-auto pb-8 pl-4 pt-4">
          <slot />
        </main>
      </AdminLayout>
    </NMessageProvider>
  </NConfigProvider>
</template>

<script setup lang="ts">
import { NConfigProvider, NMessageProvider, darkTheme, enUS } from "naive-ui";
import { useDark } from "@vueuse/core";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";

// import "@/echo";

const isDark = useDark();

const themeOverrides = {
  common: {
    primaryColor: "#bd2835FF",
    primaryColorHover: "#CD3543FF",
    primaryColorPressed: "#CC2130FF",
    primaryColorSuppl: "#B93945FF",
    borderRadius: "5px",
  },
  Carousel: {
    dotColor: "#ebebeb",
    dotColorActive: "#bd2835CC",
    arrowColor: "#bbbbbb",
  },
  Layout: {
    color: "rgb(250 248 248)",
    headerColor: "rgb(250 248 248)",
    footerColor: "rgb(250 248 248)",
  },
};

const darkThemeOverrides = {
  common: {
    primaryColor: "#bd2835FF",
    primaryColorHover: "#CD3543FF",
    primaryColorPressed: "#CC2130FF",
    primaryColorSuppl: "#B93945FF",
  },
  Layout: {
    color: "rgb(30 30 33)",
    headerColor: "rgb(30 30 33)",
    footerColor: "rgb(30 30 33)",
  },
};
</script>

<style scoped>
main {
  scroll-margin: 5rem 0 0 0;
}
</style>
