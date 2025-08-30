import axios from 'axios';

// Create axios instance with base configuration
const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Request interceptor for adding auth headers if needed
api.interceptors.request.use(
  (config) => {
    // Add any auth tokens here if needed in the future
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor for handling errors
api.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    console.error('API Error:', error);
    
    // Handle specific error cases
    if (error.response?.status === 401) {
      // Handle unauthorized access
      console.warn('Unauthorized access');
    } else if (error.response?.status === 422) {
      // Handle validation errors
      console.warn('Validation error:', error.response.data);
    } else if (error.response?.status >= 500) {
      // Handle server errors
      console.error('Server error:', error.response.data);
    }
    
    return Promise.reject(error);
  }
);

// API methods
export const ticketAPI = {
  // Get all tickets with optional filters
  getTickets(params = {}) {
    return api.get('/tickets', { params });
  },
  
  // Get single ticket by ID
  getTicket(id) {
    return api.get(`/tickets/${id}`);
  },
  
  // Create new ticket
  createTicket(data) {
    return api.post('/tickets', data);
  },
  
  // Update ticket
  updateTicket(id, data) {
    return api.patch(`/tickets/${id}`, data);
  },
  
  // Classify ticket
  classifyTicket(id) {
    return api.post(`/tickets/${id}/classify`);
  },
  
  // Get classification status
  getClassifyStatus() {
    return api.get('/tickets/classify/status');
  }
};

export const categoryAPI = {
  // Get all categories
  getCategories() {
    return api.get('/categories');
  }
};

// Helper functions for data processing
export const dataHelpers = {
  // Format confidence as percentage
  formatConfidence(confidence) {
    if (confidence === null || confidence === undefined) return 'N/A';
    return `${confidence}%`;
  },
  
  // Format date
  formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { 
      hour: '2-digit', 
      minute: '2-digit' 
    });
  },
  
  // Get status badge class
  getStatusBadgeClass(status) {
    const statusClasses = {
      'open': 'badge--primary',
      'in_progress': 'badge--warning',
      'resolved': 'badge--success',
      'closed': 'badge--gray'
    };
    return statusClasses[status] || 'badge--gray';
  },
  
  // Get status display text
  getStatusDisplayText(status) {
    const statusTexts = {
      'open': 'Open',
      'in_progress': 'In Progress',
      'resolved': 'Resolved',
      'closed': 'Closed'
    };
    return statusTexts[status] || status;
  },
  
  // Truncate text
  truncateText(text, maxLength = 100) {
    if (!text) return '';
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
  },
  
  // Process tickets data for dashboard counters
  processTicketsForDashboard(tickets) {
    const statusCounts = {
      open: 0,
      in_progress: 0,
      resolved: 0,
      closed: 0
    };
    
    const categoryCounts = {};
    
    tickets.forEach(ticket => {
      // Count by status
      if (statusCounts.hasOwnProperty(ticket.status)) {
        statusCounts[ticket.status]++;
      }
      
      // Count by category
      if (ticket.category) {
        const categoryName = ticket.category.name;
        categoryCounts[categoryName] = (categoryCounts[categoryName] || 0) + 1;
      }
    });
    
    return {
      statusCounts,
      categoryCounts,
      totalTickets: tickets.length
    };
  }
};

export default api;
