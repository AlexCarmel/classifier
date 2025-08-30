import { config } from '@vue/test-utils'
import { vi } from 'vitest'
import '@testing-library/jest-dom'

// Mock Vue Router
const mockRouter = {
  push: vi.fn(),
  replace: vi.fn(),
  go: vi.fn(),
  back: vi.fn(),
  forward: vi.fn(),
  resolve: vi.fn(() => ({ href: '/' })),
  currentRoute: {
    value: {
      path: '/',
      query: {},
      params: {},
      name: 'home',
      meta: {}
    }
  }
}

const mockRoute = {
  path: '/',
  query: {},
  params: {},
  name: 'home',
  meta: {}
}

// Global mocks
config.global.mocks = {
  $router: mockRouter,
  $route: mockRoute
}

// Global plugins
config.global.plugins = [
  // Add any global plugins here
]

// Mock window.scrollTo
Object.defineProperty(window, 'scrollTo', {
  value: vi.fn(),
  writable: true
})

// Mock IntersectionObserver
global.IntersectionObserver = vi.fn(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}))

// Mock ResizeObserver
global.ResizeObserver = vi.fn(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
}))

export { mockRouter, mockRoute }
