<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal">
      <div class="modal__header">
        <h2 class="modal__title">Create New Ticket</h2>
        <button @click="$emit('close')" class="modal__close">&times;</button>
      </div>
      
      <form @submit.prevent="submitForm" class="modal__body">
        <div class="form-group">
          <label for="subject" class="form-label">Subject *</label>
          <input
            id="subject"
            v-model="form.subject"
            type="text"
            class="form-input"
            :class="{ 'form-input--error': errors.subject }"
            placeholder="Enter ticket subject"
            required
          />
          <div v-if="errors.subject" class="form-error">{{ errors.subject[0] }}</div>
        </div>
        
        <div class="form-group">
          <label for="body" class="form-label">Description *</label>
          <textarea
            id="body"
            v-model="form.body"
            class="form-textarea"
            :class="{ 'form-textarea--error': errors.body }"
            placeholder="Describe the issue or request in detail"
            rows="6"
            required
          ></textarea>
          <div v-if="errors.body" class="form-error">{{ errors.body[0] }}</div>
        </div>
        
        <div class="form-group">
          <label for="category" class="form-label">Category</label>
          <select
            id="category"
            v-model="form.category_id"
            class="form-select"
            :class="{ 'form-select--error': errors.category_id }"
          >
            <option value="">Select a category (optional)</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
          <div v-if="errors.category_id" class="form-error">{{ errors.category_id[0] }}</div>
        </div>
        
        <div class="form-group">
          <label for="status" class="form-label">Status *</label>
          <select
            id="status"
            v-model="form.status"
            class="form-select"
            :class="{ 'form-select--error': errors.status }"
            required
          >
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
          </select>
          <div v-if="errors.status" class="form-error">{{ errors.status[0] }}</div>
        </div>
        
        <div class="form-group">
          <label for="explanation" class="form-label">Notes/Explanation</label>
          <textarea
            id="explanation"
            v-model="form.explanation"
            class="form-textarea"
            :class="{ 'form-textarea--error': errors.explanation }"
            placeholder="Additional notes or explanation (optional)"
            rows="3"
          ></textarea>
          <div v-if="errors.explanation" class="form-error">{{ errors.explanation[0] }}</div>
        </div>
        
        <div class="form-group">
          <label for="confidence" class="form-label">Confidence Level</label>
          <div class="confidence-input">
            <input
              id="confidence"
              v-model.number="form.confidence"
              type="range"
              min="1"
              max="100"
              class="confidence-slider"
              :class="{ 'confidence-slider--error': errors.confidence }"
            />
            <span class="confidence-value">{{ form.confidence || 0 }}%</span>
          </div>
          <div v-if="errors.confidence" class="form-error">{{ errors.confidence[0] }}</div>
          <small class="form-help">How confident are you about this ticket's categorization?</small>
        </div>
      </form>
      
      <div class="modal__footer">
        <button @click="$emit('close')" type="button" class="btn btn--secondary">
          Cancel
        </button>
        <button @click="submitForm" :disabled="submitting" class="btn btn--primary">
          <span v-if="submitting" class="spinner spinner--sm"></span>
          <span v-else>Create Ticket</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ticketAPI } from '../services/api.js';

export default {
  name: 'NewTicketModal',
  props: {
    categories: {
      type: Array,
      default: () => []
    }
  },
  emits: ['close', 'created'],
  data() {
    return {
      form: {
        subject: '',
        body: '',
        category_id: '',
        status: 'open',
        explanation: '',
        confidence: null
      },
      errors: {},
      submitting: false
    }
  },
  methods: {
    async submitForm() {
      if (this.submitting) return;
      
      this.submitting = true;
      this.errors = {};
      
      try {
        // Prepare form data
        const formData = { ...this.form };
        
        // Remove empty values
        if (!formData.category_id) delete formData.category_id;
        if (!formData.explanation) delete formData.explanation;
        if (!formData.confidence) delete formData.confidence;
        
        const response = await ticketAPI.createTicket(formData);
        
        if (response.data.success) {
          this.$emit('created', response.data.data);
        }
      } catch (error) {
        console.error('Error creating ticket:', error);
        
        if (error.response?.status === 422) {
          // Validation errors
          this.errors = error.response.data.errors || {};
        } else {
          // General error
          this.$root.$emit('show-toast', {
            type: 'error',
            message: error.response?.data?.message || 'Failed to create ticket. Please try again.'
          });
        }
      } finally {
        this.submitting = false;
      }
    },
    
    resetForm() {
      this.form = {
        subject: '',
        body: '',
        category_id: '',
        status: 'open',
        explanation: '',
        confidence: null
      };
      this.errors = {};
    }
  }
}
</script>

<style scoped>
/* Form Styling */
.form-group {
  margin-bottom: 1.5rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #374151;
}

.form-input,
.form-select,
.form-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 1rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: #3182ce;
  box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1);
}

.form-input--error,
.form-select--error,
.form-textarea--error {
  border-color: #e53e3e;
}

.form-input--error:focus,
.form-select--error:focus,
.form-textarea--error:focus {
  border-color: #e53e3e;
  box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1);
}

.form-error {
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: #e53e3e;
}

.form-help {
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.form-textarea {
  resize: vertical;
  min-height: 100px;
}

/* Confidence Input */
.confidence-input {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.confidence-slider {
  flex: 1;
  height: 6px;
  border-radius: 3px;
  background: #e2e8f0;
  outline: none;
  appearance: none;
}

.confidence-slider::-webkit-slider-thumb {
  appearance: none;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #3182ce;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.confidence-slider::-moz-range-thumb {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #3182ce;
  cursor: pointer;
  border: none;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.confidence-slider--error {
  background: #fee2e2;
}

.confidence-value {
  font-weight: 600;
  color: #3182ce;
  min-width: 50px;
  text-align: right;
}

/* Small spinner for submit button */
.spinner--sm {
  width: 16px;
  height: 16px;
  border-width: 2px;
}

/* Modal adjustments */
.modal {
  max-width: 600px;
  width: 100%;
}

.modal__body {
  max-height: 70vh;
  overflow-y: auto;
}

/* Responsive */
@media (max-width: 640px) {
  .confidence-input {
    flex-direction: column;
    align-items: stretch;
    gap: 0.5rem;
  }
  
  .confidence-value {
    text-align: center;
  }
}
</style>
