import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { requiresGuest: true },
  },
  {
    path: '/',
    name: 'findings',
    component: () => import('@/views/FindingsView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/users',
    name: 'users',
    component: () => import('@/views/UsersView.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/',
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Initialize auth state if not done yet
  if (authStore.token && !authStore.user) {
    try {
      await authStore.fetchUser();
    } catch {
      // Token invalid, will redirect to login
    }
  }

  const isAuthenticated = authStore.isAuthenticated;
  const isAdmin = authStore.isAdmin;

  // Redirect authenticated users away from login page
  if (to.meta.requiresGuest && isAuthenticated) {
    next({ name: 'findings' });
    return;
  }

  // Redirect unauthenticated users to login
  if (to.meta.requiresAuth && !isAuthenticated) {
    next({ name: 'login' });
    return;
  }

  // Redirect non-admin users away from admin pages
  if (to.meta.requiresAdmin && !isAdmin) {
    next({ name: 'findings' });
    return;
  }

  next();
});

export default router;
