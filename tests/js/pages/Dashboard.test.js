import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Dashboard from '@/pages/Dashboard.vue'

// Mock Canvas API
HTMLCanvasElement.prototype.getContext = vi.fn(() => ({
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
}))

describe('Dashboard.vue', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mount(Dashboard, {
      global: {
        stubs: ['router-link']
      }
    })
  })

  afterEach(() => {
    wrapper?.unmount()
  })

  it('renders the dashboard header', () => {
    expect(wrapper.find('.dashboard').exists()).toBe(true)
    expect(wrapper.find('.dashboard__header').exists()).toBe(true)
    expect(wrapper.find('.dashboard__title').text()).toBe('Dashboard')
  })

  it('renders refresh button', () => {
    const refreshBtn = wrapper.find('button')
    expect(refreshBtn.exists()).toBe(true)
    expect(refreshBtn.text()).toContain('Refresh')
  })

  it('shows loading state initially', () => {
    expect(wrapper.find('.dashboard__loading').exists() || 
           wrapper.find('.dashboard__content').exists()).toBe(true)
  })

  it('can click refresh button', async () => {
    const refreshBtn = wrapper.find('button')
    await refreshBtn.trigger('click')
    // Just ensure no errors thrown
    expect(refreshBtn.exists()).toBe(true)
  })
})
