<script setup>
// Страница регистрации приложения Main.
// Только фронтенд: бэкенд-эндпоинт POST /api/v1/main/register будет реализован позже.
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'

// Отправка формы пока не выполняется — эндпоинт появится позже.
function onSubmit() {
  // TODO: POST /api/v1/main/register
}
</script>

<template>
  <AppHeader />

  <main class="registration-page">
    <section class="registration-intro" aria-labelledby="page-title">
      <p class="eyebrow">Создание аккаунта</p>
      <h1 id="page-title" class="registration-title">Регистрационная анкета</h1>
      <p class="intro">Все поля помеченные звездочкой * обязательны к заполнению</p>
    </section>

    <form class="registration-form" @submit.prevent="onSubmit">
      <fieldset class="form-section">
        <legend>Личные данные</legend>
        <div class="fields-grid">
          <div class="field">
            <label for="surname">Фамилия <span aria-hidden="true">*</span></label>
            <input id="surname" name="surname" type="text" required />
          </div>
          <div class="field">
            <label for="name">Имя <span aria-hidden="true">*</span></label>
            <input id="name" name="name" type="text" required />
          </div>
          <div class="field">
            <label for="patronymic">Отчество <span aria-hidden="true">*</span></label>
            <input id="patronymic" name="patronymic" type="text" required />
          </div>
          <div class="field">
            <label for="birth-date">Дата рождения <span aria-hidden="true">*</span></label>
            <input id="birth-date" name="birth-date" type="date" required />
          </div>
          <div class="field field-full">
            <span class="field-label">Пол <span aria-hidden="true">*</span></span>
            <div class="radio-group">
              <label class="radio-option">
                <input type="radio" name="gender" value="male" required /> Мужчина
              </label>
              <label class="radio-option">
                <input type="radio" name="gender" value="female" /> Женщина
              </label>
            </div>
          </div>
        </div>
      </fieldset>

      <fieldset class="form-section">
        <legend>Специализация</legend>
        <div class="field">
          <label for="specialization">Специализация <span aria-hidden="true">*</span></label>
          <select id="specialization" name="specialization" required>
            <option value="">— Выберите специализацию —</option>
            <option>Лечебное дело</option>
            <option>Педиатрия</option>
            <option>Стоматология</option>
          </select>
        </div>
      </fieldset>

      <fieldset class="form-section">
        <legend>Специальность</legend>
        <div class="fields-grid">
          <div class="field field-full">
            <label for="main-specialty">
              Основная медицинская специальность <span aria-hidden="true">*</span>
            </label>
            <select id="main-specialty" name="main-specialty" required>
              <option value="">— Выберите специальность —</option>
              <option>Терапия</option>
              <option>Хирургия</option>
              <option>Кардиология</option>
            </select>
          </div>
          <div class="field field-full">
            <label for="additional-specialty">Дополнительная медицинская специальность</label>
            <select id="additional-specialty" name="additional-specialty">
              <option value="">— Нет дополнительной специальности —</option>
              <option>Неврология</option>
              <option>Онкология</option>
              <option>Эндокринология</option>
            </select>
          </div>
        </div>
      </fieldset>

      <fieldset class="form-section">
        <legend>Условия практики</legend>
        <div class="field">
          <label for="practice-conditions">
            В каких условиях Вы ведете свою практику? <span aria-hidden="true">*</span>
          </label>
          <select id="practice-conditions" name="practice-conditions" required>
            <option value="">— Выберите условия —</option>
            <option>В амбулаторном ЛПУ</option>
            <option>В стационарном ЛПУ</option>
            <option>Скорая неотложная помощь</option>
            <option>Другое</option>
          </select>
        </div>
      </fieldset>

      <fieldset class="form-section">
        <legend>Опыт работы</legend>
        <div class="field">
          <label for="experience-year">
            С какого года Вы работаете в медицине? <span aria-hidden="true">*</span>
          </label>
          <select id="experience-year" name="experience-year" required>
            <option value="">— Выберите год —</option>
            <option>2020</option>
            <option>2015</option>
            <option>2010</option>
            <option>2005</option>
            <option>2000</option>
          </select>
        </div>
      </fieldset>

      <fieldset class="form-section">
        <legend>Контакты и место работы</legend>
        <div class="fields-grid">
          <div class="field">
            <label for="country">Страна <span aria-hidden="true">*</span></label>
            <input id="country" name="country" type="text" value="Россия" readonly required />
          </div>
          <div class="field">
            <label for="city">Город проживания <span aria-hidden="true">*</span></label>
            <select id="city" name="city" required>
              <option value="">— Выберите город —</option>
              <option>Москва</option>
              <option>Санкт-Петербург</option>
              <option>Казань</option>
              <option>Екатеринбург</option>
            </select>
          </div>
          <div class="field">
            <label for="email">E-mail <span aria-hidden="true">*</span></label>
            <input id="email" name="email" type="email" autocomplete="email" required />
          </div>
          <div class="field">
            <label for="password">Пароль <span aria-hidden="true">*</span></label>
            <input
              id="password"
              name="password"
              type="password"
              autocomplete="new-password"
              required
            />
          </div>
          <div class="field">
            <label for="phone">Номер телефона <span aria-hidden="true">*</span></label>
            <input id="phone" name="phone" type="tel" autocomplete="tel" required />
          </div>
          <div class="field">
            <label for="workplace">Название места работы <span aria-hidden="true">*</span></label>
            <input id="workplace" name="workplace" type="text" required />
          </div>
        </div>
      </fieldset>

      <section class="form-submit" aria-label="Подтверждение регистрации">
        <p>
          Нажимая на кнопку зарегистрироваться Вы соглашаетесь с условиями
          <a href="#">Пользовательского соглашения</a>.
        </p>
        <button type="submit">Зарегистрироваться</button>
      </section>
    </form>
  </main>

  <AppFooter />
</template>
