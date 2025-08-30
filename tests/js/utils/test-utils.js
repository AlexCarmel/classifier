import { mount, shallowMount } from '@vue/test-utils'
import { vi } from 'vitest'
import { createApiMock } from './api-mocks.js'

// Default mount options
export const defaultMountOptions = {
  global: {
    mocks: {
      $router: {
        push: vi.fn(),
        replace: vi.fn(),
        go: vi.fn(),
        back: vi.fn(),
        forward: vi.fn()
      },
      $route: {
        path: '/',
        query: {},
        params: {},
        name: 'home'
      }
    },
    stubs: {
      'router-link': {
        template: '<a @click="navigate" :href="to"><slot /></a>',
        props: ['to'],
        methods: {
          navigate(e) {
            e.preventDefault()
            this.$router?.push?.(this.to)
          }
        }
      },
      'router-view': true
    }
  }
}

// Custom mount function with common defaults
export function mountComponent(component, options = {}) {
  const mergedOptions = {
    ...defaultMountOptions,
    ...options,
    global: {
      ...defaultMountOptions.global,
      ...options.global,
      mocks: {
        ...defaultMountOptions.global.mocks,
        ...options.global?.mocks
      }
    }
  }
  
  return mount(component, mergedOptions)
}

// Shallow mount with defaults
export function shallowMountComponent(component, options = {}) {
  const mergedOptions = {
    ...defaultMountOptions,
    ...options,
    global: {
      ...defaultMountOptions.global,
      ...options.global,
      mocks: {
        ...defaultMountOptions.global.mocks,
        ...options.global?.mocks
      }
    }
  }
  
  return shallowMount(component, mergedOptions)
}

// Mock API service
export function mockApiService() {
  const apiMock = createApiMock()
  
  return {
    ticketAPI: {
      getTickets: apiMock.getTickets,
      getTicket: apiMock.getTicket,
      createTicket: apiMock.createTicket,
      updateTicket: apiMock.updateTicket,
      classifyTicket: apiMock.classifyTicket,
      getClassifyStatus: apiMock.getClassifyStatus
    },
    categoryAPI: {
      getCategories: apiMock.getCategories
    },
    dashboardAPI: {
      getData: apiMock.getDashboardData
    }
  }
}

// Wait for async operations
export const waitFor = (condition, timeout = 1000) => {
  return new Promise((resolve, reject) => {
    const startTime = Date.now()
    
    const check = () => {
      if (condition()) {
        resolve()
      } else if (Date.now() - startTime > timeout) {
        reject(new Error(`Timeout: condition not met within ${timeout}ms`))
      } else {
        setTimeout(check, 10)
      }
    }
    
    check()
  })
}

// Simulate user input
export async function setInputValue(wrapper, selector, value) {
  const input = wrapper.find(selector)
  await input.setValue(value)
  await input.trigger('input')
}

// Simulate form submission
export async function submitForm(wrapper, selector = 'form') {
  const form = wrapper.find(selector)
  await form.trigger('submit.prevent')
}

// Mock fetch for Canvas operations (if needed for Dashboard charts)
export const mockCanvasContext = () => ({
  fillRect: vi.fn(),
  clearRect: vi.fn(),
  getImageData: vi.fn(() => ({ data: new Array(4) })),
  putImageData: vi.fn(),
  createImageData: vi.fn(() => ({ data: new Array(4) })),
  setTransform: vi.fn(),
  drawImage: vi.fn(),
  save: vi.fn(),
  fillText: vi.fn(),
  restore: vi.fn(),
  beginPath: vi.fn(),
  moveTo: vi.fn(),
  lineTo: vi.fn(),
  closePath: vi.fn(),
  stroke: vi.fn(),
  translate: vi.fn(),
  scale: vi.fn(),
  rotate: vi.fn(),
  arc: vi.fn(),
  fill: vi.fn(),
  measureText: vi.fn(() => ({ width: 100 })),
  transform: vi.fn(),
  rect: vi.fn(),
  clip: vi.fn()
})

// Common assertions
export const expectToContainText = (wrapper, text) => {
  expect(wrapper.text()).toContain(text)
}

export const expectToHaveClass = (element, className) => {
  expect(element.classes()).toContain(className)
}

export const expectToBeVisible = (element) => {
  expect(element.isVisible()).toBe(true)
}

export const expectToBeHidden = (element) => {
  expect(element.isVisible()).toBe(false)
}
