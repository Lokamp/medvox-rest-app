<script setup>
// Общий подвал приложения Main.
// Используется на главной странице и на странице помощи.
import { RouterLink } from 'vue-router'
</script>

<template>
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <RouterLink to="/" class="logo">
            <span class="logo-text">MedVox<span class="logo-domain">.eu</span></span>
          </RouterLink>
          <p class="footer-desc">
            Платформа для медицинских исследований, объединяющая врачей и фармацевтические компании.
          </p>
        </div>
      </div>
      <div class="footer-bottom">
        <p>© 2026 MedVox.eu. Все права защищены.</p>
      </div>
    </div>
  </footer>
</template>
