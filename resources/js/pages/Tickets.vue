<template>
  <div class="tickets">
    <!-- Header -->
    <div class="tickets__header">
      <h1 class="tickets__title">Tickets</h1>
      <button @click="showNewTicketModal = true" class="btn btn--primary">
        New Ticket
      </button>
    </div>

    <!-- Filters and Search -->
    <div class="tickets__filters">
      <div class="tickets__search">
        <input
          v-model="filters.search"
          type="text"
          placeholder="Search tickets..."
          class="form-input"
          @input="handleSearch"
        />
      </div>
      
      <div class="tickets__filter-controls">
        <select v-model="filters.status" @change="applyFilters" class="form-select">
          <option value="">All Statuses</option>
          <option value="open">Open</option>
          <option value="in_progress">In Progress</option>
          <option value="resolved">Resolved</option>
          <option value="closed">Closed</option>
        </select>
        
        <select v-model="filters.categoryId" @change="applyFilters" class="form-select">
          <option value="">All Categories</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>
        
        <button @click="clearFilters" class="btn btn--secondary">
          Clear Filters
        </button>
      </div>
    </div>

    <!-- View Toggle -->
    <div class="tickets__view-toggle">
      <button 
        @click="viewMode = 'table'" 
        :class="['btn', viewMode === 'table' ? 'btn--primary' : 'btn--secondary']"
      >
        Table View
      </button>
      <button 
        @click="viewMode = 'cards'" 
        :class="['btn', viewMode === 'cards' ? 'btn--primary' : 'btn--secondary']"
      >
        Card View
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="tickets__loading">
      <div class="spinner"></div>
      <p>Loading tickets...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="tickets__error">
      <p>{{ error }}</p>
      <button @click="loadTickets" class="btn btn--primary">Try Again</button>
    </div>

    <!-- Tickets Content -->
    <div v-else class="tickets__content">
      <!-- Table View -->
      <div v-if="viewMode === 'table'" class="ticket-table">
        <table class="ticket-table__table">
          <thead class="ticket-table__header">
            <tr>
              <th>Subject</th>
              <th>Category</th>
              <th>Status</th>
              <th>Confidence</th>
              <th>Explanation</th>
              <th>Created</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="ticket-table__body">
            <tr 
              v-for="ticket in paginatedTickets" 
              :key="ticket.id"
              class="ticket-table__row"
              @click="openTicketDetail(ticket.id)"
            >
              <td class="ticket-table__cell ticket-table__cell--subject">
                <div class="ticket-subject">
                  <span class="ticket-subject__text">{{ ticket.subject }}</span>
                  <span v-if="ticket.explanation" class="ticket-subject__badge">
                    📝
                  </span>
                </div>
              </td>
              <td class="ticket-table__cell">
                <span v-if="ticket.category" class="badge badge--primary">
                  {{ ticket.category.name }}
                </span>
                <span v-else class="badge badge--gray">No Category</span>
              </td>
              <td class="ticket-table__cell">
                <span :class="['badge', getStatusBadgeClass(ticket.status)]">
                  {{ getStatusDisplayText(ticket.status) }}
                </span>
              </td>
              <td class="ticket-table__cell">
                <span v-if="ticket.confidence !== null" class="confidence-meter">
                  {{ formatConfidence(ticket.confidence) }}
                </span>
                <span v-else class="text-gray-500">N/A</span>
              </td>
              <td class="ticket-table__cell">
                <div v-if="ticket.explanation" class="tooltip">
                  <span class="tooltip__trigger">ℹ️</span>
                  <div class="tooltip__content">{{ ticket.explanation }}</div>
                </div>
                <span v-else class="text-gray-500">-</span>
              </td>
              <td class="ticket-table__cell">
                {{ formatDate(ticket.created_at) }}
              </td>
              <td class="ticket-table__cell ticket-table__cell--actions">
                <button 
                  @click.stop="classifyTicket(ticket.id)"
                  :disabled="classifyingTickets.includes(ticket.id)"
                  class="btn btn--sm btn--success"
                >
                  <span v-if="classifyingTickets.includes(ticket.id)" class="spinner spinner--sm"></span>
                  <span v-else>Classify</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <div v-if="paginatedTickets.length === 0" class="ticket-table__empty">
          <p>No tickets found matching your criteria.</p>
        </div>
      </div>

      <!-- Card View -->
      <div v-else class="ticket-cards">
        <div 
          v-for="ticket in paginatedTickets" 
          :key="ticket.id"
          class="ticket-card"
          @click="openTicketDetail(ticket.id)"
        >
          <div class="ticket-card__header">
            <h3 class="ticket-card__title">{{ ticket.subject }}</h3>
            <div class="ticket-card__badges">
              <span v-if="ticket.explanation" class="badge badge--warning">📝</span>
              <span :class="['badge', getStatusBadgeClass(ticket.status)]">
                {{ getStatusDisplayText(ticket.status) }}
              </span>
            </div>
          </div>
          
          <div class="ticket-card__body">
            <p class="ticket-card__body-text">{{ truncateText(ticket.body, 150) }}</p>
          </div>
          
          <div class="ticket-card__footer">
            <div class="ticket-card__meta">
              <span v-if="ticket.category" class="badge badge--primary">
                {{ ticket.category.name }}
              </span>
              <span v-if="ticket.confidence !== null" class="confidence-meter">
                {{ formatConfidence(ticket.confidence) }}
              </span>
              <small class="text-gray-500">{{ formatDate(ticket.created_at) }}</small>
            </div>
            
            <button 
              @click.stop="classifyTicket(ticket.id)"
              :disabled="classifyingTickets.includes(ticket.id)"
              class="btn btn--sm btn--success"
            >
              <span v-if="classifyingTickets.includes(ticket.id)" class="spinner spinner--sm"></span>
              <span v-else>Classify</span>
            </button>
          </div>
        </div>
        
        <div v-if="paginatedTickets.length === 0" class="ticket-cards__empty">
          <p>No tickets found matching your criteria.</p>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="pagination">
        <button 
          @click="currentPage = currentPage - 1"
          :disabled="currentPage === 1"
          class="btn btn--secondary btn--sm"
        >
          Previous
        </button>
        
        <span class="pagination__info">
          Page {{ currentPage }} of {{ totalPages }} ({{ filteredTickets.length }} tickets)
        </span>
        
        <button 
          @click="currentPage = currentPage + 1"
          :disabled="currentPage === totalPages"
          class="btn btn--secondary btn--sm"
        >
          Next
        </button>
      </div>
    </div>

    <!-- New Ticket Modal -->
    <NewTicketModal
      v-if="showNewTicketModal"
      :categories="categories"
      @close="showNewTicketModal = false"
      @created="handleTicketCreated"
    />
  </div>
</template>

<script>
import { ticketAPI, categoryAPI, dataHelpers } from '../services/api.js';
import NewTicketModal from '../components/NewTicketModal.vue';

export default {
  name: 'Tickets',
  components: {
    NewTicketModal
  },
  data() {
    return {
      tickets: [],
      categories: [],
      loading: false,
      error: null,
      viewMode: 'table', // 'table' or 'cards'
      showNewTicketModal: false,
      classifyingTickets: [],
      
      // Filters
      filters: {
        search: '',
        status: '',
        categoryId: ''
      },
      
      // Pagination
      currentPage: 1,
      itemsPerPage: 10,
      
      // Search debounce
      searchTimeout: null
    }
  },
  computed: {
    filteredTickets() {
      let filtered = [...this.tickets];
      
      // Text search
      if (this.filters.search.trim()) {
        const searchTerm = this.filters.search.toLowerCase();
        filtered = filtered.filter(ticket => 
          ticket.subject.toLowerCase().includes(searchTerm) ||
          ticket.body.toLowerCase().includes(searchTerm) ||
          (ticket.explanation && ticket.explanation.toLowerCase().includes(searchTerm))
        );
      }
      
      // Status filter
      if (this.filters.status) {
        filtered = filtered.filter(ticket => ticket.status === this.filters.status);
      }
      
      // Category filter
      if (this.filters.categoryId) {
        filtered = filtered.filter(ticket => 
          ticket.category && ticket.category.id === this.filters.categoryId
        );
      }
      
      return filtered;
    },
    
    totalPages() {
      return Math.ceil(this.filteredTickets.length / this.itemsPerPage);
    },
    
    paginatedTickets() {
      const start = (this.currentPage - 1) * this.itemsPerPage;
      const end = start + this.itemsPerPage;
      return this.filteredTickets.slice(start, end);
    }
  },
  async mounted() {
    await this.loadInitialData();
  },
  methods: {
    async loadInitialData() {
      await Promise.all([
        this.loadTickets(),
        this.loadCategories()
      ]);
    },
    
    async loadTickets() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await ticketAPI.getTickets();
        this.tickets = response.data.data;
      } catch (error) {
        console.error('Error loading tickets:', error);
        this.error = 'Failed to load tickets. Please try again.';
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
    
    handleSearch() {
      // Reset to first page when searching
      this.currentPage = 1;
      
      // Debounce search to avoid too many updates
      if (this.searchTimeout) {
        clearTimeout(this.searchTimeout);
      }
      this.searchTimeout = setTimeout(() => {
        // Search is handled by computed property
      }, 300);
    },
    
    applyFilters() {
      this.currentPage = 1; // Reset to first page when filtering
    },
    
    clearFilters() {
      this.filters = {
        search: '',
        status: '',
        categoryId: ''
      };
      this.currentPage = 1;
    },
    
    async classifyTicket(ticketId) {
      if (this.classifyingTickets.includes(ticketId)) return;
      
      this.classifyingTickets.push(ticketId);
      
      try {
        const response = await ticketAPI.classifyTicket(ticketId);
        
        // Update the ticket in our local list
        const ticketIndex = this.tickets.findIndex(t => t.id === ticketId);
        if (ticketIndex !== -1) {
          this.tickets[ticketIndex] = response.data.data.ticket;
        }
        
        // Show success message
        this.$root.$emit('show-toast', {
          type: 'success',
          message: 'Ticket classified successfully!'
        });
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
        this.classifyingTickets = this.classifyingTickets.filter(id => id !== ticketId);
      }
    },
    
    openTicketDetail(ticketId) {
      this.$router.push(`/tickets/${ticketId}`);
    },
    
    async handleTicketCreated() {
      this.showNewTicketModal = false;
      await this.loadTickets();
      
      this.$root.$emit('show-toast', {
        type: 'success',
        message: 'Ticket created successfully!'
      });
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
    },
    
    truncateText(text, maxLength = 100) {
      return dataHelpers.truncateText(text, maxLength);
    }
  }
}
</script>

<style scoped>
/* Tickets Layout */
.tickets {
  max-width: 1200px;
  margin: 0 auto;
}

.tickets__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.tickets__title {
  font-size: 2rem;
  font-weight: 600;
  color: #2d3748;
}

/* Filters */
.tickets__filters {
  background-color: #fff;
  border-radius: 0.5rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tickets__search {
  margin-bottom: 1rem;
}

.tickets__filter-controls {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.tickets__filter-controls .form-select {
  min-width: 150px;
}

/* View Toggle */
.tickets__view-toggle {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

/* Loading and Error States */
.tickets__loading,
.tickets__error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  color: #718096;
}

.tickets__error {
  color: #e53e3e;
}

/* Table View */
.ticket-table {
  background-color: #fff;
  border-radius: 0.5rem;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-bottom: 1.5rem;
}

.ticket-table__table {
  width: 100%;
  border-collapse: collapse;
}

.ticket-table__header th {
  background-color: #f7fafc;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #4a5568;
  border-bottom: 1px solid #e2e8f0;
}

.ticket-table__row {
  cursor: pointer;
  transition: background-color 0.2s;
}

.ticket-table__row:hover {
  background-color: #f7fafc;
}

.ticket-table__cell {
  padding: 1rem;
  border-bottom: 1px solid #e2e8f0;
  vertical-align: middle;
}

.ticket-table__cell--subject {
  max-width: 300px;
}

.ticket-table__cell--actions {
  width: 100px;
}

.ticket-subject {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.ticket-subject__text {
  font-weight: 500;
  color: #2d3748;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.ticket-subject__badge {
  font-size: 0.875rem;
}

.ticket-table__empty {
  padding: 3rem;
  text-align: center;
  color: #718096;
}

/* Card View */
.ticket-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.ticket-card {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.ticket-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.ticket-card__header {
  padding: 1rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.ticket-card__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #2d3748;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
}

.ticket-card__badges {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.ticket-card__body {
  padding: 1rem;
}

.ticket-card__body-text {
  color: #4a5568;
  line-height: 1.6;
}

.ticket-card__footer {
  padding: 1rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 1rem;
}

.ticket-card__meta {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
}

.ticket-cards__empty {
  grid-column: 1 / -1;
  padding: 3rem;
  text-align: center;
  color: #718096;
}

/* Confidence Meter */
.confidence-meter {
  font-weight: 600;
  color: #38a169;
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  padding: 2rem 0;
}

.pagination__info {
  color: #718096;
  font-size: 0.875rem;
}

/* Small spinner */
.spinner--sm {
  width: 16px;
  height: 16px;
  border-width: 2px;
}

/* Responsive */
@media (max-width: 768px) {
  .tickets__header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .tickets__filter-controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .tickets__filter-controls .form-select {
    min-width: auto;
  }
  
  .ticket-table__table {
    font-size: 0.875rem;
  }
  
  .ticket-table__cell {
    padding: 0.5rem;
  }
  
  .ticket-cards {
    grid-template-columns: 1fr;
  }
  
  .pagination {
    flex-direction: column;
    gap: 0.5rem;
  }
}

/* Hide table on small screens if needed */
@media (max-width: 640px) {
  .ticket-table {
    overflow-x: auto;
  }
}
</style>
