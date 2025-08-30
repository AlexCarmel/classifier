<template>
  <div class="dashboard">
    <div class="dashboard__header">
      <h1 class="dashboard__title">Dashboard</h1>
      <button @click="refreshData" class="btn btn--secondary" :disabled="loading">
        <span v-if="loading" class="spinner spinner--sm"></span>
        Refresh
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !tickets.length" class="dashboard__loading">
      <div class="spinner"></div>
      <p>Loading dashboard data...</p>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="dashboard__content">
      <!-- Status Cards -->
      <section class="dashboard__section">
        <h2 class="dashboard__section-title">Tickets by Status</h2>
        <div class="status-cards">
          <div 
            v-for="(count, status) in dashboardData.statusCounts" 
            :key="status"
            class="status-card"
            :class="`status-card--${status}`"
          >
            <div class="status-card__icon">
              <span v-html="getStatusIcon(status)"></span>
            </div>
            <div class="status-card__content">
              <div class="status-card__number">{{ count }}</div>
              <div class="status-card__label">{{ getStatusDisplayText(status) }}</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Category Cards -->
      <section class="dashboard__section">
        <h2 class="dashboard__section-title">Tickets by Category</h2>
        <div class="category-cards">
          <div 
            v-for="(count, category) in dashboardData.categoryCounts" 
            :key="category"
            class="category-card"
          >
            <div class="category-card__content">
              <div class="category-card__number">{{ count }}</div>
              <div class="category-card__label">{{ category }}</div>
            </div>
          </div>
          <div v-if="Object.keys(dashboardData.categoryCounts).length === 0" class="category-card category-card--empty">
            <div class="category-card__content">
              <div class="category-card__number">0</div>
              <div class="category-card__label">No Categories</div>
            </div>
          </div>
        </div>
      </section>

      <!-- Charts Section -->
      <section class="dashboard__section">
        <h2 class="dashboard__section-title">Analytics</h2>
        <div class="charts-container">
          <!-- Status Distribution Chart -->
          <div class="chart-card">
            <div class="chart-card__header">
              <h3 class="chart-card__title">Status Distribution</h3>
            </div>
            <div class="chart-card__body">
              <canvas ref="statusChart" class="chart-canvas"></canvas>
            </div>
          </div>

          <!-- Category Distribution Chart -->
          <div class="chart-card">
            <div class="chart-card__header">
              <h3 class="chart-card__title">Category Distribution</h3>
            </div>
            <div class="chart-card__body">
              <canvas ref="categoryChart" class="chart-canvas"></canvas>
            </div>
          </div>
        </div>
      </section>

      <!-- Summary Stats -->
      <section class="dashboard__section">
        <div class="summary-stats">
          <div class="summary-stat">
            <div class="summary-stat__number">{{ dashboardData.totalTickets }}</div>
            <div class="summary-stat__label">Total Tickets</div>
          </div>
          <div class="summary-stat">
            <div class="summary-stat__number">{{ Object.keys(dashboardData.categoryCounts).length }}</div>
            <div class="summary-stat__label">Categories</div>
          </div>
          <div class="summary-stat">
            <div class="summary-stat__number">{{ calculateResolutionRate() }}%</div>
            <div class="summary-stat__label">Resolution Rate</div>
          </div>
        </div>
      </section>
    </div>

    <!-- Error State -->
    <div v-if="error" class="dashboard__error">
      <p>{{ error }}</p>
      <button @click="refreshData" class="btn btn--primary">Try Again</button>
    </div>
  </div>
</template>

<script>
import { ticketAPI, dataHelpers } from '../services/api.js';

export default {
  name: 'Dashboard',
  data() {
    return {
      tickets: [],
      loading: false,
      error: null,
      dashboardData: {
        statusCounts: {
          open: 0,
          in_progress: 0,
          resolved: 0,
          closed: 0
        },
        categoryCounts: {},
        totalTickets: 0
      }
    }
  },
  async mounted() {
    await this.loadData();
  },
  methods: {
    async loadData() {
      this.loading = true;
      this.error = null;
      
      try {
        const response = await ticketAPI.getTickets();
        this.tickets = response.data.data;
        this.dashboardData = dataHelpers.processTicketsForDashboard(this.tickets);
        
        // Create charts after data is loaded
        await this.$nextTick();
        this.createCharts();
      } catch (error) {
        console.error('Error loading dashboard data:', error);
        this.error = 'Failed to load dashboard data. Please try again.';
      } finally {
        this.loading = false;
      }
    },
    
    async refreshData() {
      await this.loadData();
    },
    
    getStatusDisplayText(status) {
      return dataHelpers.getStatusDisplayText(status);
    },
    
    getStatusIcon(status) {
      const icons = {
        open: '📭',
        in_progress: '⏳',
        resolved: '✅',
        closed: '📁'
      };
      return icons[status] || '📄';
    },
    
    calculateResolutionRate() {
      const resolved = this.dashboardData.statusCounts.resolved;
      const closed = this.dashboardData.statusCounts.closed;
      const total = this.dashboardData.totalTickets;
      
      if (total === 0) return 0;
      return Math.round(((resolved + closed) / total) * 100);
    },
    
    createCharts() {
      this.createStatusChart();
      this.createCategoryChart();
    },
    
    createStatusChart() {
      const canvas = this.$refs.statusChart;
      if (!canvas) return;
      
      const ctx = canvas.getContext('2d');
      const data = this.dashboardData.statusCounts;
      
      // Clear canvas
      canvas.width = canvas.offsetWidth;
      canvas.height = canvas.offsetHeight;
      
      const centerX = canvas.width / 2;
      const centerY = canvas.height / 2;
      const radius = Math.min(centerX, centerY) - 20;
      
      const colors = {
        open: '#3182ce',
        in_progress: '#d69e2e',
        resolved: '#38a169',
        closed: '#718096'
      };
      
      const total = Object.values(data).reduce((sum, count) => sum + count, 0);
      if (total === 0) {
        // Draw empty state
        ctx.fillStyle = '#e2e8f0';
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
        ctx.fill();
        
        ctx.fillStyle = '#718096';
        ctx.font = '16px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('No Data', centerX, centerY);
        return;
      }
      
      let currentAngle = -Math.PI / 2; // Start from top
      
      Object.entries(data).forEach(([status, count]) => {
        if (count > 0) {
          const sliceAngle = (count / total) * 2 * Math.PI;
          
          ctx.fillStyle = colors[status];
          ctx.beginPath();
          ctx.moveTo(centerX, centerY);
          ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
          ctx.closePath();
          ctx.fill();
          
          currentAngle += sliceAngle;
        }
      });
      
      // Draw center circle for donut effect
      ctx.fillStyle = '#fff';
      ctx.beginPath();
      ctx.arc(centerX, centerY, radius * 0.5, 0, 2 * Math.PI);
      ctx.fill();
      
      // Draw total in center
      ctx.fillStyle = '#2d3748';
      ctx.font = 'bold 24px sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(total.toString(), centerX, centerY + 8);
    },
    
    createCategoryChart() {
      const canvas = this.$refs.categoryChart;
      if (!canvas) return;
      
      const ctx = canvas.getContext('2d');
      const data = this.dashboardData.categoryCounts;
      
      // Clear canvas
      canvas.width = canvas.offsetWidth;
      canvas.height = canvas.offsetHeight;
      
      const categories = Object.entries(data);
      if (categories.length === 0) {
        ctx.fillStyle = '#718096';
        ctx.font = '16px sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('No Categories', canvas.width / 2, canvas.height / 2);
        return;
      }
      
      const maxCount = Math.max(...Object.values(data));
      const barHeight = 30;
      const barSpacing = 10;
      const maxBarWidth = canvas.width - 150; // Leave space for labels
      
      categories.forEach(([category, count], index) => {
        const y = index * (barHeight + barSpacing) + 20;
        const barWidth = (count / maxCount) * maxBarWidth;
        
        // Draw bar
        ctx.fillStyle = '#3182ce';
        ctx.fillRect(120, y, barWidth, barHeight);
        
        // Draw category label
        ctx.fillStyle = '#2d3748';
        ctx.font = '14px sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText(category, 115, y + barHeight / 2 + 5);
        
        // Draw count
        ctx.fillStyle = '#fff';
        ctx.textAlign = 'left';
        ctx.fillText(count.toString(), 125, y + barHeight / 2 + 5);
      });
    }
  }
}
</script>

<style scoped>
/* Dashboard Layout */
.dashboard {
  max-width: 1200px;
  margin: 0 auto;
}

.dashboard__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.dashboard__title {
  font-size: 2rem;
  font-weight: 600;
  color: #2d3748;
}

.dashboard__loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  color: #718096;
}

.dashboard__error {
  text-align: center;
  padding: 2rem;
  color: #e53e3e;
}

.dashboard__section {
  margin-bottom: 3rem;
}

.dashboard__section-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 1rem;
}

/* Status Cards */
.status-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.status-card {
  background-color: #fff;
  border-radius: 0.5rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
  border-left: 4px solid #e2e8f0;
}

.status-card--open {
  border-left-color: #3182ce;
}

.status-card--in_progress {
  border-left-color: #d69e2e;
}

.status-card--resolved {
  border-left-color: #38a169;
}

.status-card--closed {
  border-left-color: #718096;
}

.status-card__icon {
  font-size: 2rem;
}

.status-card__number {
  font-size: 2rem;
  font-weight: 700;
  color: #2d3748;
}

.status-card__label {
  font-size: 0.875rem;
  color: #718096;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Category Cards */
.category-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.category-card {
  background-color: #fff;
  border-radius: 0.5rem;
  padding: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.category-card--empty {
  background-color: #f7fafc;
  color: #718096;
}

.category-card__number {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 0.5rem;
}

.category-card__label {
  font-size: 0.875rem;
  color: #718096;
}

/* Charts */
.charts-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.chart-card {
  background-color: #fff;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.chart-card__header {
  padding: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.chart-card__title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #2d3748;
}

.chart-card__body {
  padding: 1rem;
  height: 300px;
}

.chart-canvas {
  width: 100%;
  height: 100%;
}

/* Summary Stats */
.summary-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  background-color: #fff;
  border-radius: 0.5rem;
  padding: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.summary-stat {
  text-align: center;
}

.summary-stat__number {
  font-size: 2rem;
  font-weight: 700;
  color: #3182ce;
}

.summary-stat__label {
  font-size: 0.875rem;
  color: #718096;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Small spinner for refresh button */
.spinner--sm {
  width: 16px;
  height: 16px;
  border-width: 2px;
}

/* Responsive */
@media (max-width: 768px) {
  .dashboard__header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .status-cards {
    grid-template-columns: 1fr;
  }
  
  .charts-container {
    grid-template-columns: 1fr;
  }
  
  .summary-stats {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
