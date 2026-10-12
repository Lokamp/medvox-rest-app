import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/faq',
      name: 'faq',
      // Страница помощи: данные приходят из GET /api/v1/main/faq.
      component: () => import('../views/FaqView.vue'),
    },
    {
      path: '/registration',
      name: 'registration',
      // Страница регистрации: пока только фронтенд, эндпоинт появится позже.
      component: () => import('../views/RegistrationView.vue'),
    },
  ],
  // При любом обычном переходе между страницами прокручиваем в самый верх.
  // При навигации «Назад»/«Вперёд» восстанавливаем сохранённую позицию скролла.
  /*
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }

    return { top: 0 }
  },
  */
})

export default router
