// router.js
import Vue from 'vue';
import VueRouter from 'vue-router';
import AdminPanel from './pages/AdminPane.vue';

Vue.use(VueRouter);

const routes = [
  // Существующие маршруты...
  {
    path: '/admin',
    component: AdminPanel,
    beforeEnter: (to, from, next) => {
      // Проверка на админа через локальное состояние компонента App
      const app = document.getElementById('app').__vue__;
      if (app && app.isAdmin) {
        next();
      } else {
        // Делаем дополнительную проверку через API
        fetch('/api/auth/status', {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
          if (data.authenticated && data.user && data.user.role === 'admin') {
            next();
          } else {
            // Перенаправление на главную, если пользователь не админ
            next('/');
            alert('Доступ запрещен. Только для администраторов.');
          }
        })
        .catch(error => {
          console.error('Ошибка при проверке статуса:', error);
          next('/');
        });
      }
    }
  }
];

const router = new VueRouter({
  mode: 'history',
  base: process.env.BASE_URL,
  routes
});

export default router;
