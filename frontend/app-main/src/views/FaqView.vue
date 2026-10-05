<script setup>
// Страница «Помощь» (FAQ) приложения Main.
// Данные загружаются из публичного эндпоинта GET /api/v1/main/faq.
import { onMounted, ref } from 'vue'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
import { fetchFaq } from '@/api/main'

const items = ref([])
const loading = ref(true)
const error = ref('')

/**
 * Разбить текст ответа на абзацы (пустая строка — разделитель).
 * @param {string} text
 * @returns {string[]}
 */
function toParagraphs(text) {
  return String(text ?? '')
    .split(/\n{2,}/)
    .map((paragraph) => paragraph.trim())
    .filter(Boolean)
}

onMounted(async () => {
  try {
    items.value = await fetchFaq()
  } catch {
    error.value = 'Не удалось загрузить раздел помощи. Попробуйте обновить страницу позже.'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <AppHeader />

  <main class="help-page">
    <section class="hero" aria-labelledby="page-title">
      <p class="eyebrow">Центр помощи</p>
      <h1 id="page-title" class="help-title">Помощь и ответы</h1>
      <p class="intro">Выберите тему, чтобы найти подробный ответ на свой вопрос.</p>
    </section>

    <section class="faq" aria-label="Часто задаваемые вопросы">
      <p v-if="loading" class="faq-status">Загрузка…</p>
      <p v-else-if="error" class="faq-status faq-status--error">{{ error }}</p>
      <p v-else-if="items.length === 0" class="faq-status">Пока нет доступных тем.</p>

      <template v-else>
        <details v-for="item in items" :key="item.id" class="faq-item">
          <summary>
            <span>{{ item.title }}</span>
            <span class="summary-icon" aria-hidden="true"></span>
          </summary>
          <div class="answer">
            <p v-for="(paragraph, index) in toParagraphs(item.description)" :key="index">
              {{ paragraph }}
            </p>
          </div>
        </details>
      </template>
    </section>
  </main>

  <AppFooter />
</template>
