import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from './pages/Dashboard.vue';
import Tickets from './pages/Tickets.vue';
import TicketDetail from './pages/TicketDetail.vue';

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: Dashboard,
    meta: { title: 'Dashboard' }
  },
  {
    path: '/tickets',
    name: 'Tickets',
    component: Tickets,
    meta: { title: 'Tickets' }
  },
  {
    path: '/tickets/:id',
    name: 'TicketDetail',
    component: TicketDetail,
    meta: { title: 'Ticket Detail' },
    props: true
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Update document title based on route meta
router.beforeEach((to, from, next) => {
  const title = to.meta.title || 'Ticket Classifier';
  document.title = title;
  next();
});

export default router;
