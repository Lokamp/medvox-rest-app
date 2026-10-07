<script setup>
// Общая шапка приложения Main.
// Используется на главной странице, на странице помощи, на странице регистрации и в личном кабинете пользователя.
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

const menuOpen = ref(false)
const scrolled = ref(false)

function toggleMenu() {
  menuOpen.value = !menuOpen.value
}

function closeMenu() {
  menuOpen.value = false
}

function onScroll() {
  scrolled.value = window.scrollY > 10
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
})
</script>

<template>
  <header class="header" :class="{ scrolled }">
    <div class="container header-container">
      <RouterLink to="/" class="logo" @click="closeMenu">
        <span class="logo-text">MedVox</span>
      </RouterLink>
      <nav class="nav" :class="{ active: menuOpen }">
        <RouterLink to="/faq" class="nav-link" @click="closeMenu">Помощь</RouterLink>
        <a href="#" class="nav-link">Войти в личный кабинет</a>
        <a href="/registration" target="_blank" class="btn btn-primary nav-btn">Зарегистрироваться</a>
      </nav>
      <button
        class="menu-toggle"
        :class="{ active: menuOpen }"
        type="button"
        aria-label="Открыть меню"
        :aria-expanded="menuOpen"
        @click="toggleMenu"
      >
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </header>
</template>
