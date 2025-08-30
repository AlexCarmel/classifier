import { vi } from 'vitest'

// Mock ticket data
export const mockTickets = [
  {
    id: '01HJQKX7R8M3CXVN5QZ8WDPJE3',
    subject: 'Login Issue',
    body: 'Cannot log into the application',
    status: 'open',
    category_id: '01HJQKX7R8M3CXVN5QZ8WDPJE1',
    category: {
      id: '01HJQKX7R8M3CXVN5QZ8WDPJE1',
      name: 'Technical Support'
    },
    explanation: 'User authentication problem',
    confidence: 85,
    created_at: '2024-01-15T10:30:00Z',
    updated_at: '2024-01-15T10:30:00Z'
  },
  {
    id: '01HJQKX7R8M3CXVN5QZ8WDPJE4',
    subject: 'Feature Request',
    body: 'Please add dark mode support',
    status: 'in_progress',
    category_id: '01HJQKX7R8M3CXVN5QZ8WDPJE2',
    category: {
      id: '01HJQKX7R8M3CXVN5QZ8WDPJE2',
      name: 'Feature Request'
    },
    explanation: 'Enhancement request for UI',
    confidence: 90,
    created_at: '2024-01-14T14:20:00Z',
    updated_at: '2024-01-15T09:15:00Z'
  }
]

// Mock categories data
export const mockCategories = [
  {
    id: '01HJQKX7R8M3CXVN5QZ8WDPJE1',
    name: 'Technical Support',
    tickets_count: 15
  },
  {
    id: '01HJQKX7R8M3CXVN5QZ8WDPJE2',
    name: 'Feature Request',
    tickets_count: 8
  },
  {
    id: '01HJQKX7R8M3CXVN5QZ8WDPJE3',
    name: 'Bug Reports',
    tickets_count: 12
  }
]

// Mock dashboard data
export const mockDashboardData = {
  statusCounts: {
    open: 20,
    in_progress: 15,
    resolved: 8,
    closed: 5
  },
  categoryCounts: {
    'Technical Support': 15,
    'Feature Request': 8,
    'Bug Reports': 12,
    'General': 3
  }
}

// API Mock Factory
export const createApiMock = () => {
  return {
    // Ticket API methods
    getTickets: vi.fn(() => Promise.resolve({
      data: {
        success: true,
        data: mockTickets,
        pagination: {
          current_page: 1,
          last_page: 3,
          per_page: 15,
          total: 35,
          from: 1,
          to: 15
        }
      }
    })),
    
    getTicket: vi.fn((id) => Promise.resolve({
      data: {
        success: true,
        data: mockTickets.find(t => t.id === id) || mockTickets[0]
      }
    })),
    
    createTicket: vi.fn((ticketData) => Promise.resolve({
      data: {
        success: true,
        data: {
          ...ticketData,
          id: '01HJQKX7R8M3CXVN5QZ8WDPJE5',
          created_at: new Date().toISOString(),
          updated_at: new Date().toISOString()
        }
      }
    })),
    
    updateTicket: vi.fn((id, updates) => Promise.resolve({
      data: {
        success: true,
        data: {
          ...mockTickets.find(t => t.id === id),
          ...updates,
          updated_at: new Date().toISOString()
        }
      }
    })),
    
    classifyTicket: vi.fn((id) => Promise.resolve({
      data: {
        success: true,
        data: {
          ticket: {
            ...mockTickets.find(t => t.id === id),
            explanation: 'AI-generated explanation',
            confidence: 88,
            category: mockCategories[0]
          },
          classification: {
            category: 'Technical Support',
            explanation: 'AI-generated explanation',
            confidence: 88
          },
          rate_limit_status: {
            calls_made: 5,
            max_calls: 10,
            remaining_calls: 5,
            window_seconds: 60
          }
        }
      }
    })),
    
    getClassifyStatus: vi.fn(() => Promise.resolve({
      data: {
        success: true,
        data: {
          rate_limit_status: {
            calls_made: 5,
            max_calls: 10,
            remaining_calls: 5,
            window_seconds: 60
          },
          openai_enabled: true
        }
      }
    })),
    
    // Category API methods
    getCategories: vi.fn(() => Promise.resolve({
      data: {
        success: true,
        data: mockCategories
      }
    })),
    
    // Dashboard methods
    getDashboardData: vi.fn(() => Promise.resolve({
      data: {
        success: true,
        data: {
          tickets: mockTickets,
          categories: mockCategories
        }
      }
    }))
  }
}

// Error response mock
export const createErrorResponse = (status = 500, message = 'Server Error') => ({
  response: {
    status,
    data: {
      success: false,
      message
    }
  }
})

// Async utilities for testing
export const flushPromises = () => new Promise(resolve => setTimeout(resolve))

export const waitForNextTick = () => new Promise(resolve => process.nextTick(resolve))
