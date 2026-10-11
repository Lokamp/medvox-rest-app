<script setup>
import { RouterView } from 'vue-router'
</script>

<template>
  <!-- Глобальный макет приложения: flex-колонка на всю высоту экрана.
       Центральная часть растягивается, а подвал (AppFooter) прижимается к низу. -->
  <div class="app-layout">
    <RouterView />
  </div>
</template>
