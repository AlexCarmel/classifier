<template>
  <div class="ticket-detail">
    <!-- Loading State -->
    <div v-if="loading" class="ticket-detail__loading">
      <div class="spinner"></div>
      <p>Loading ticket details...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="ticket-detail__error">
      <h2>Error Loading Ticket</h2>
      <p>{{ error }}</p>
      <div class="ticket-detail__error-actions">
        <button @click="loadTicket" class="btn btn--primary">Try Again</button>
        <router-link to="/tickets" class="btn btn--secondary">Back to Tickets</router-link>
      </div>
    </div>

    <!-- Ticket Content -->
    <div v-else-if="ticket" class="ticket-detail__content">
      <!-- Header -->
      <div class="ticket-detail__header">
        <div class="ticket-detail__breadcrumb">
          <router-link to="/tickets" class="ticket-detail__back-link">← Back to Tickets</router-link>
        </div>
        
        <div class="ticket-detail__title-section">
          <h1 class="ticket-detail__title">{{ ticket.subject }}</h1>
          <div class="ticket-detail__badges">
            <span v-if="ticket.category" class="badge badge--primary">
              {{ ticket.category.name }}
            </span>
            <span :class="['badge', getStatusBadgeClass(ticket.status)]">
              {{ getStatusDisplayText(ticket.status) }}
            </span>
            <span v-if="ticket.explanation" class="badge badge--warning">
              📝 Has Notes
            </span>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="ticket-detail__main">
        <!-- Left Column - Ticket Content -->
        <div class="ticket-detail__content-column">
          <!-- Ticket Body -->
          <div class="ticket-detail__section">
            <h2 class="ticket-detail__section-title">Description</h2>
            <div class="ticket-detail__body">
              <p>{{ ticket.body }}</p>
            </div>
          </div>

          <!-- Explanation & Confidence (Read-only) -->
          <div v-if="ticket.explanation || ticket.confidence" class="ticket-detail__section">
            <h2 class="ticket-detail__section-title">Classification Details</h2>
            <div class="classification-details">
              <div v-if="ticket.explanation" class="classification-details__item">
                <strong>Explanation:</strong>
                <p>{{ ticket.explanation }}</p>
              </div>
              <div v-if="ticket.confidence !== null" class="classification-details__item">
                <strong>Confidence:</strong>
                <span class="confidence-meter">{{ formatConfidence(ticket.confidence) }}</span>
              </div>
            </div>
          </div>

          <!-- Metadata -->
          <div class="ticket-detail__section">
            <h2 class="ticket-detail__section-title">Ticket Information</h2>
            <div class="ticket-metadata">
              <div class="ticket-metadata__item">
                <strong>Created:</strong>
                <span>{{ formatDate(ticket.created_at) }}</span>
              </div>
              <div class="ticket-metadata__item">
                <strong>Last Updated:</strong>
                <span>{{ formatDate(ticket.updated_at) }}</span>
              </div>
              <div v-if="ticket.created_by" class="ticket-metadata__item">
                <strong>Created By:</strong>
                <span>{{ ticket.created_by }}</span>
              </div>
              <div v-if="ticket.updated_by" class="ticket-metadata__item">
                <strong>Updated By:</strong>
                <span>{{ ticket.updated_by }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Actions & Editing -->
        <div class="ticket-detail__actions-column">
          <!-- Quick Actions -->
          <div class="ticket-detail__section">
            <h2 class="ticket-detail__section-title">Actions</h2>
            <div class="ticket-actions">
              <button 
                @click="runClassification"
                :disabled="classifying"
                class="btn btn--primary btn--full-width"
              >
                <span v-if="classifying" class="spinner spinner--sm"></span>
                <span v-else>🤖 Run Classification</span>
              </button>
              
              <button 
                @click="toggleEditMode"
                class="btn btn--secondary btn--full-width"
              >
                <span v-if="editMode">Cancel Edit</span>
                <span v-else>✏️ Edit Ticket</span>
              </button>
            </div>
          </div>

          <!-- Edit Form -->
          <div v-if="editMode" class="ticket-detail__section">
            <h2 class="ticket-detail__section-title">Edit Ticket</h2>
            <form @submit.prevent="saveChanges" class="edit-form">
              <!-- Category Dropdown -->
              <div class="form-group">
                <label for="category" class="form-label">Category</label>
                <select
                  id="category"
                  v-model="editForm.category_id"
                  class="form-select"
                  :class="{ 'form-select--error': editErrors.category_id }"
                >
                  <option value="">No Category</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
                <div v-if="editErrors.category_id" class="form-error">{{ editErrors.category_id[0] }}</div>
              </div>

              <!-- Status Dropdown -->
              <div class="form-group">
                <label for="status" class="form-label">Status</label>
                <select
                  id="status"
                  v-model="editForm.status"
                  class="form-select"
                  :class="{ 'form-select--error': editErrors.status }"
                >
                  <option value="open">Open</option>
                  <option value="in_progress">In Progress</option>
                  <option value="resolved">Resolved</option>
                  <option value="closed">Closed</option>
                </select>
                <div v-if="editErrors.status" class="form-error">{{ editErrors.status[0] }}</div>
              </div>

              <!-- Notes Textarea -->
              <div class="form-group">
                <label for="explanation" class="form-label">Notes/Explanation</label>
                <textarea
                  id="explanation"
                  v-model="editForm.explanation"
                  class="form-textarea"
                  :class="{ 'form-textarea--error': editErrors.explanation }"
                  placeholder="Add notes or explanation..."
                  rows="4"
                ></textarea>
                <div v-if="editErrors.explanation" class="form-error">{{ editErrors.explanation[0] }}</div>
              </div>

              <!-- Save/Cancel Buttons -->
              <div class="edit-form__actions">
                <button 
                  type="submit"
                  :disabled="saving"
                  class="btn btn--success btn--full-width"
                >
                  <span v-if="saving" class="spinner spinner--sm"></span>
                  <span v-else>💾 Save Changes</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Classification Status (if available) -->
          <div v-if="classificationStatus" class="ticket-detail__section">
            <h2 class="ticket-detail__section-title">Classification Status</h2>
            <div class="classification-status">
              <div class="classification-status__item">
                <strong>API Calls Made:</strong>
                <span>{{ classificationStatus.calls_made }}/{{ classificationStatus.max_calls }}</span>
              </div>
              <div class="classification-status__item">
                <strong>Remaining:</strong>
                <span>{{ classificationStatus.remaining_calls }} calls</span>
              </div>
              <div class="classification-status__item">
                <strong>OpenAI Enabled:</strong>
                <span :class="openaiEnabled ? 'text-success' : 'text-warning'">
                  {{ openaiEnabled ? 'Yes' : 'No' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ticketAPI, categoryAPI, dataHelpers } from '../services/api.js';

export default {
  name: 'TicketDetail',
  props: {
    id: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      ticket: null,
      categories: [],
      loading: false,
      error: null,
      editMode: false,
      editForm: {
        category_id: '',
        status: '',
        explanation: ''
      },
      editErrors: {},
      saving: false,
      classifying: false,
      classificationStatus: null,
      openaiEnabled: false
    }
  },
  async mounted() {
    await this.loadInitialData();
  },
  watch: {
    id: {
      handler() {
        this.loadInitialData();
      },
      immediate: false
    }
  },
  methods: {
    async loadInitialData() {
      await Promise.all([
        this.loadTicket(),
        this.loadCategories(),
        this.loadClassificationStatus()
      ]);
    },
    
    async loadTicket() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await ticketAPI.getTicket(this.id);
        this.ticket = response.data.data;
        this.initializeEditForm();
      } catch (error) {
        console.error('Error loading ticket:', error);
        
        if (error.response?.status === 404) {
          this.error = 'Ticket not found.';
        } else {
          this.error = 'Failed to load ticket details. Please try again.';
        }
      } finally {
        this.loading = false;
      }
    },
    
    async loadCategories() {
      try {
        const response = await categoryAPI.getCategories();
        this.categories = response.data.data;
      } catch (error) {
        console.error('Error loading categories:', error);
      }
    },
    
    async loadClassificationStatus() {
      try {
        const response = await ticketAPI.getClassifyStatus();
        this.classificationStatus = response.data.data.rate_limit_status;
        this.openaiEnabled = response.data.data.openai_enabled;
      } catch (error) {
        console.error('Error loading classification status:', error);
      }
    },
    
    initializeEditForm() {
      if (this.ticket) {
        this.editForm = {
          category_id: this.ticket.category?.id || '',
          status: this.ticket.status,
          explanation: this.ticket.explanation || ''
        };
      }
    },
    
    toggleEditMode() {
      this.editMode = !this.editMode;
      this.editErrors = {};
      
      if (this.editMode) {
        this.initializeEditForm();
      }
    },
    
    async saveChanges() {
      if (this.saving) return;
      
      this.saving = true;
      this.editErrors = {};
      
      try {
        // Prepare data for update
        const updateData = { ...this.editForm };
        
        // Remove empty category_id
        if (!updateData.category_id) {
          updateData.category_id = null;
        }
        
        const response = await ticketAPI.updateTicket(this.id, updateData);
        
        if (response.data.success) {
          this.ticket = response.data.data;
          this.editMode = false;
          
          this.$root.$emit('show-toast', {
            type: 'success',
            message: 'Ticket updated successfully!'
          });
        }
      } catch (error) {
        console.error('Error updating ticket:', error);
        
        if (error.response?.status === 422) {
          // Validation errors
          this.editErrors = error.response.data.errors || {};
        } else {
          this.$root.$emit('show-toast', {
            type: 'error',
            message: error.response?.data?.message || 'Failed to update ticket.'
          });
        }
      } finally {
        this.saving = false;
      }
    },
    
    async runClassification() {
      if (this.classifying) return;
      
      this.classifying = true;
      
      try {
        const response = await ticketAPI.classifyTicket(this.id);
        
        if (response.data.success) {
          this.ticket = response.data.data.ticket;
          this.classificationStatus = response.data.data.rate_limit_status;
          
          this.$root.$emit('show-toast', {
            type: 'success',
            message: 'Ticket classified successfully!'
          });
        }
      } catch (error) {
        console.error('Error classifying ticket:', error);
        
        let errorMessage = 'Failed to classify ticket.';
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message;
        }
        
        this.$root.$emit('show-toast', {
          type: 'error',
          message: errorMessage
        });
      } finally {
        this.classifying = false;
      }
    },
    
    // Helper methods
    getStatusBadgeClass(status) {
      return dataHelpers.getStatusBadgeClass(status);
    },
    
    getStatusDisplayText(status) {
      return dataHelpers.getStatusDisplayText(status);
    },
    
    formatConfidence(confidence) {
      return dataHelpers.formatConfidence(confidence);
    },
    
    formatDate(dateString) {
      return dataHelpers.formatDate(dateString);
    }
  }
}
</script>

<style scoped>
/* Ticket Detail Layout */
.ticket-detail {
  max-width: 1200px;
  margin: 0 auto;
}

.ticket-detail__loading,
.ticket-detail__error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  text-align: center;
}

.ticket-detail__error {
  color: #e53e3e;
}

.ticket-detail__error h2 {
  margin-bottom: 1rem;
  color: #2d3748;
}

.ticket-detail__error-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

/* Header */
.ticket-detail__header {
  margin-bottom: 2rem;
}

.ticket-detail__breadcrumb {
  margin-bottom: 1rem;
}

.ticket-detail__back-link {
  color: #3182ce;
  text-decoration: none;
  font-weight: 500;
}

.ticket-detail__back-link:hover {
  text-decoration: underline;
}

.ticket-detail__title-section {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.ticket-detail__title {
  font-size: 2rem;
  font-weight: 600;
  color: #2d3748;
  flex: 1;
}

.ticket-detail__badges {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  flex-shrink: 0;
}

/* Main Content */
.ticket-detail__main {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
}

.ticket-detail__section {
  background-color: #fff;
  border-radius: 0.5rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-bottom: 1.5rem;
}

.ticket-detail__section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 0.5rem;
}

/* Ticket Body */
.ticket-detail__body {
  color: #4a5568;
  line-height: 1.7;
}

.ticket-detail__body p {
  margin-bottom: 1rem;
}

/* Classification Details */
.classification-details__item {
  margin-bottom: 1rem;
}

.classification-details__item strong {
  display: block;
  margin-bottom: 0.25rem;
  color: #2d3748;
}

.classification-details__item p {
  color: #4a5568;
  margin: 0;
}

.confidence-meter {
  font-weight: 600;
  color: #38a169;
  font-size: 1.125rem;
}

/* Metadata */
.ticket-metadata__item {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f7fafc;
}

.ticket-metadata__item:last-child {
  border-bottom: none;
}

.ticket-metadata__item strong {
  color: #4a5568;
}

/* Actions */
.ticket-actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.btn--full-width {
  width: 100%;
  justify-content: center;
}

/* Edit Form */
.edit-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.edit-form__actions {
  margin-top: 1rem;
}

/* Classification Status */
.classification-status__item {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f7fafc;
}

.classification-status__item:last-child {
  border-bottom: none;
}

.text-success {
  color: #38a169;
  font-weight: 600;
}

.text-warning {
  color: #d69e2e;
  font-weight: 600;
}

/* Small spinner */
.spinner--sm {
  width: 16px;
  height: 16px;
  border-width: 2px;
}

/* Responsive */
@media (max-width: 768px) {
  .ticket-detail__title-section {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .ticket-detail__main {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .ticket-detail__error-actions {
    flex-direction: column;
    align-items: center;
  }
}
</style>
