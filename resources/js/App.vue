<template>
  <div id="app" class="app">
    <!-- Navigation -->
    <nav class="app-nav">
      <div class="app-nav__container">
        <div class="app-nav__brand">
          <h1 class="app-nav__title">Ticket Classifier</h1>
        </div>
        <ul class="app-nav__menu">
          <li class="app-nav__item">
            <router-link to="/dashboard" class="app-nav__link">Dashboard</router-link>
          </li>
          <li class="app-nav__item">
            <router-link to="/tickets" class="app-nav__link">Tickets</router-link>
          </li>
        </ul>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="app-main">
      <router-view />
    </main>

    <!-- Global Loading Overlay -->
    <div v-if="globalLoading" class="loading-overlay">
      <div class="spinner"></div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'App',
  data() {
    return {
      globalLoading: false
    }
  },
  provide() {
    return {
      setGlobalLoading: (loading) => {
        this.globalLoading = loading;
      }
    }
  }
}
</script>

<style>
/* Global CSS Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background-color: #f8fafc;
  color: #1a202c;
  line-height: 1.6;
}

/* App Layout */
.app {
  min-height: 100vh;
}

/* Navigation */
.app-nav {
  background-color: #fff;
  border-bottom: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.app-nav__container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 64px;
}

.app-nav__title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2d3748;
}

.app-nav__menu {
  display: flex;
  list-style: none;
  gap: 2rem;
}

.app-nav__link {
  text-decoration: none;
  color: #4a5568;
  font-weight: 500;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  transition: all 0.2s;
}

.app-nav__link:hover {
  background-color: #edf2f7;
  color: #2d3748;
}

.app-nav__link.router-link-active {
  background-color: #3182ce;
  color: #fff;
}

/* Main Content */
.app-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
  min-height: calc(100vh - 64px);
}

/* Loading Overlay */
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

/* Spinner */
.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e2e8f0;
  border-top: 4px solid #3182ce;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Button Base Styles */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 0.375rem;
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s;
  gap: 0.5rem;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn--primary {
  background-color: #3182ce;
  color: #fff;
}

.btn--primary:hover:not(:disabled) {
  background-color: #2c5aa0;
}

.btn--secondary {
  background-color: #e2e8f0;
  color: #4a5568;
}

.btn--secondary:hover:not(:disabled) {
  background-color: #cbd5e0;
}

.btn--success {
  background-color: #38a169;
  color: #fff;
}

.btn--success:hover:not(:disabled) {
  background-color: #2f855a;
}

.btn--warning {
  background-color: #d69e2e;
  color: #fff;
}

.btn--warning:hover:not(:disabled) {
  background-color: #b7791f;
}

.btn--danger {
  background-color: #e53e3e;
  color: #fff;
}

.btn--danger:hover:not(:disabled) {
  background-color: #c53030;
}

.btn--sm {
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
}

.btn--lg {
  padding: 0.75rem 1.5rem;
  font-size: 1.125rem;
}

/* Form Elements */
.form-group {
  margin-bottom: 1rem;
}

.form-label {
  display: block;
  margin-bottom: 0.25rem;
  font-weight: 500;
  color: #4a5568;
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: #3182ce;
  box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

/* Card */
.card {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card__header {
  padding: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.card__title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #2d3748;
}

.card__body {
  padding: 1rem;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
  padding: 1rem;
}

.modal {
  background-color: #fff;
  border-radius: 0.5rem;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow: auto;
}

.modal__header {
  padding: 1rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal__title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #2d3748;
}

.modal__close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #a0aec0;
  padding: 0.25rem;
  border-radius: 0.25rem;
}

.modal__close:hover {
  background-color: #edf2f7;
  color: #4a5568;
}

.modal__body {
  padding: 1rem;
}

.modal__footer {
  padding: 1rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

/* Badge */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.5rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.badge--primary {
  background-color: #dbeafe;
  color: #1e40af;
}

.badge--success {
  background-color: #dcfce7;
  color: #166534;
}

.badge--warning {
  background-color: #fef3c7;
  color: #92400e;
}

.badge--danger {
  background-color: #fee2e2;
  color: #991b1b;
}

.badge--gray {
  background-color: #f3f4f6;
  color: #374151;
}

/* Tooltip */
.tooltip {
  position: relative;
  display: inline-block;
}

.tooltip__trigger {
  cursor: help;
  border-bottom: 1px dotted #9ca3af;
}

.tooltip__content {
  position: absolute;
  bottom: 125%;
  left: 50%;
  transform: translateX(-50%);
  background-color: #1f2937;
  color: #fff;
  padding: 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.875rem;
  white-space: nowrap;
  z-index: 1000;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s, visibility 0.2s;
}

.tooltip:hover .tooltip__content {
  opacity: 1;
  visibility: visible;
}

.tooltip__content::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 5px solid transparent;
  border-top-color: #1f2937;
}

/* Utility Classes */
.text-center { text-align: center; }
.text-right { text-align: right; }
.text-left { text-align: left; }

.mb-1 { margin-bottom: 0.25rem; }
.mb-2 { margin-bottom: 0.5rem; }
.mb-3 { margin-bottom: 0.75rem; }
.mb-4 { margin-bottom: 1rem; }
.mb-6 { margin-bottom: 1.5rem; }
.mb-8 { margin-bottom: 2rem; }

.mt-1 { margin-top: 0.25rem; }
.mt-2 { margin-top: 0.5rem; }
.mt-3 { margin-top: 0.75rem; }
.mt-4 { margin-top: 1rem; }
.mt-6 { margin-top: 1.5rem; }
.mt-8 { margin-top: 2rem; }

.flex { display: flex; }
.items-center { align-items: center; }
.justify-between { justify-content: space-between; }
.justify-center { justify-content: center; }
.gap-2 { gap: 0.5rem; }
.gap-4 { gap: 1rem; }

.grid { display: grid; }
.grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
.grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
.grid-cols-4 { grid-template-columns: repeat(4, 1fr); }

.w-full { width: 100%; }
.h-full { height: 100%; }

.hidden { display: none; }

.text-sm { font-size: 0.875rem; }
.text-lg { font-size: 1.125rem; }
.text-xl { font-size: 1.25rem; }
.text-2xl { font-size: 1.5rem; }

.font-medium { font-weight: 500; }
.font-semibold { font-weight: 600; }
.font-bold { font-weight: 700; }

.text-gray-500 { color: #6b7280; }
.text-gray-600 { color: #4b5563; }
.text-gray-700 { color: #374151; }
.text-gray-900 { color: #111827; }

/* Responsive */
@media (max-width: 768px) {
  .app-nav__container {
    flex-direction: column;
    height: auto;
    padding: 1rem;
    gap: 1rem;
  }
  
  .app-nav__menu {
    gap: 1rem;
  }
  
  .app-main {
    padding: 1rem;
  }
  
  .modal {
    margin: 0;
    max-height: 100vh;
    border-radius: 0;
  }
}
</style>
