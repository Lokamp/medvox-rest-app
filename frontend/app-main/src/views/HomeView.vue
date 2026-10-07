<script setup>
// Главная (лендинг) страница приложения Main.
// Публичная страница — собственного эндпоинта на бэкенде у неё нет.
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
</script>

<template>
  <!-- Header -->
  <AppHeader />

  <!-- Hero Section -->
  <section class="hero">
    <div class="container">
      <div class="hero-content">
        <h1 class="hero-title">MedVox - Ваше экспертное мнение <span class="highlight">ценно</span></h1>
        <p class="hero-subtitle">Только для <b>врачей</b> с высшим медицинским образованием. Участвуйте в онлайн-исследованиях ведущих фармацевтических компаний и получайте достойное вознаграждение за свои знания и опыт.</p>
        <div class="hero-actions">
          <a href="/registration" target="_blank" class="btn btn-primary btn-lg">Начать зарабатывать</a>
          <a href="#how-it-works" class="btn btn-outline btn-lg">Узнать больше</a>
        </div>
        <div class="hero-stats">
          <div class="stat">
            <span class="stat-number">39 500+</span>
            <span class="stat-label">врачей в сети</span>
          </div>
          <div class="stat">
            <span class="stat-number">2 569+</span>
            <span class="stat-label">исследований</span>
          </div>
          <div class="stat">
            <span class="stat-number">3.5M+</span>
            <span class="stat-label">выплачено</span>
          </div>
        </div>
      </div>
    </div>
    <div class="hero-bg"></div>
  </section>

  <!-- Specialties Banner -->
  <section class="specialties">
    <div class="container">
      <div class="specialties-track">
        <div class="specialty">Терапевты</div>
        <div class="specialty">Кардиологи</div>
        <div class="specialty">Неврологи</div>
        <div class="specialty">Онкологи</div>
        <div class="specialty">Педиатры</div>
        <div class="specialty">Эндокринологи</div>
        <div class="specialty">Хирурги</div>
        <div class="specialty">Дерматологи</div>
        <div class="specialty">Гастроэнтерологи</div>
        <div class="specialty">Пульмонологи</div>

        <div class="specialty">Терапевты</div>
        <div class="specialty">Кардиологи</div>
        <div class="specialty">Неврологи</div>
        <div class="specialty">Онкологи</div>
        <div class="specialty">Педиатры</div>
        <div class="specialty">Эндокринологи</div>
        <div class="specialty">Хирурги</div>
        <div class="specialty">Дерматологи</div>
        <div class="specialty">Гастроэнтерологи</div>
        <div class="specialty">Пульмонологи</div>
      </div>
    </div>
  </section>

  <!-- How It Works -->
  <section class="how-it-works" id="how-it-works">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Как это работает</h2>
        <p class="section-subtitle">Три простых шага к дополнительному доходу</p>
      </div>
      <div class="steps">
        <div class="step">
          <div class="step-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <div class="step-number">01</div>
          <h3 class="step-title">Зарегистрируйтесь</h3>
          <p class="step-desc">Создайте профиль за 3-4 минуты, укажите специальность, опыт работы и проч.</p>
        </div>
        <div class="step">
          <div class="step-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
              <polyline points="22,6 12,13 2,6"></polyline>
            </svg>
          </div>
          <div class="step-number">02</div>
          <h3 class="step-title">Индивидуальные опросы</h3>
          <p class="step-desc">Мы подберём исследования, соответствующие вашему профилю и специализации</p>
        </div>
        <div class="step">
          <div class="step-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
              <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
          </div>
          <div class="step-number">03</div>
          <h3 class="step-title">Получайте оплату</h3>
          <p class="step-desc">Получайте вознаграждение за каждое пройденное исследование</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Benefits -->
  <section class="benefits" id="benefits">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Почему врачи выбирают MedVox</h2>
        <p class="section-subtitle">Мы создали платформу, которая уважает ваше время и экспертизу</p>
      </div>
      <div class="benefits-grid">
        <div class="benefit-card">
          <div class="benefit-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <path d="M12 6v6l4 2"></path>
            </svg>
          </div>
          <h3 class="benefit-title">Гибкий график</h3>
          <p class="benefit-desc">Участвуйте в исследованиях когда удобно — онлайн-опросы доступны 24/7</p>
        </div>
        <div class="benefit-card">
          <div class="benefit-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="1" x2="12" y2="23"></line>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
          </div>
          <h3 class="benefit-title">Высокое вознаграждение</h3>
          <p class="benefit-desc">Стибльное вознаграждение за исследование в зависимости от сложности и длительности</p>
        </div>
        <div class="benefit-card">
          <div class="benefit-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
          </div>
          <h3 class="benefit-title">Конфиденциальность</h3>
          <p class="benefit-desc">Все данные защищены в соответствии с GDPR, анонимность гарантирована</p>
        </div>
        <div class="benefit-card">
          <div class="benefit-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
            </svg>
          </div>
          <h3 class="benefit-title">Стибльные выплаты</h3>
          <p class="benefit-desc">100% выплаты без задережек после полностю завершенного интервью</p>
        </div>
        <div class="benefit-card">
          <div class="benefit-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="2" y1="12" x2="22" y2="12"></line>
              <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
            </svg>
          </div>
          <h3 class="benefit-title">Международные проекты</h3>
          <p class="benefit-desc">Участвуйте в исследованиях ведущих мировых фармацевтических компаний</p>
        </div>
        <div class="benefit-card">
          <div class="benefit-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
          </div>
          <h3 class="benefit-title">Влияние на медицину</h3>
          <p class="benefit-desc">Ваше мнение помогает разрабатывать лучшие лекарства и методы лечения</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonials -->
  <section class="testimonials" id="testimonials">
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Отзывы наших участников</h2>
        <p class="section-subtitle">Узнайте, что говорят врачи о сотрудничестве с MedVox</p>
      </div>
      <div class="testimonials-slider">
        <div class="testimonial">
          <div class="testimonial-content">
            <p class="testimonial-text">"Отличная возможность поделиться своим опытом и получить за это достойное вознаграждение. Опросы интересные, оплата приходит быстро."</p>
            <div class="testimonial-author">
              <div class="author-avatar">АК</div>
              <div class="author-info">
                <div class="author-name">Андрей Ковалёв</div>
                <div class="author-title">Кардиолог, 12 лет опыта</div>
              </div>
            </div>
          </div>
        </div>
        <div class="testimonial active">
          <div class="testimonial-content">
            <p class="testimonial-text">"Сотрудничаю с MedVox уже два года. Удобно, что можно участвовать в свободное время."</p>
            <div class="testimonial-author">
              <div class="author-avatar">ЕС</div>
              <div class="author-info">
                <div class="author-name">Елена Соколова</div>
                <div class="author-title">Эндокринолог, 8 лет опыта</div>
              </div>
            </div>
          </div>
        </div>
        <div class="testimonial">
          <div class="testimonial-content">
            <p class="testimonial-text">"Приятно осознавать, что мой опыт помогает улучшать медицинские препараты. А финансовый бонус — приятное дополнение к основной работе."</p>
            <div class="testimonial-author">
              <div class="author-avatar">МП</div>
              <div class="author-info">
                <div class="author-name">Михаил Петров</div>
                <div class="author-title">Невролог, 15 лет опыта</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="testimonials-dots">
        <button class="dot" data-index="0"></button>
        <button class="dot active" data-index="1"></button>
        <button class="dot" data-index="2"></button>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta">
    <div class="container">
      <div class="cta-content">
        <h2 class="cta-title">Готовы начать зарабатывать?</h2>
        <p class="cta-text">Присоединяйтесь к 39 500+ врачам, которые уже получают дополнительный доход с MedVox</p>
        <a href="/registration" target="_blank" class="btn btn-white btn-lg">Зарегистрироваться бесплатно</a>
        <p class="cta-note">Регистрация занимает 3 минуты. Регистрация абсолютно бесплатная.</p>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <AppFooter />
</template>
