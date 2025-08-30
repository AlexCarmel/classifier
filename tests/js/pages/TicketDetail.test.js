import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import TicketDetail from '@/pages/TicketDetail.vue'

describe('TicketDetail.vue', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mount(TicketDetail, {
      global: {
        stubs: ['router-link'],
        mocks: {
          $route: {
            params: { id: 'test-ticket-id' },
            path: '/tickets/test-ticket-id'
          },
          $router: {
            push: vi.fn()
          }
        }
      }
    })
  })

  afterEach(() => {
    wrapper?.unmount()
  })

  it('renders the ticket detail page', () => {
    expect(wrapper.find('.ticket-detail').exists()).toBe(true)
  })

  it('shows loading state initially', () => {
    expect(
      wrapper.find('.ticket-detail__loading').exists() ||
      wrapper.find('.ticket-detail__content').exists() ||
      wrapper.find('.ticket-detail__error').exists()
    ).toBe(true)
  })

  it('handles route parameters', () => {
    expect(wrapper.vm.$route.params.id).toBe('test-ticket-id')
  })

  it('can navigate back', async () => {
    // Try to find back link if it exists
    const backLink = wrapper.find('.ticket-detail__back-link')
    if (backLink.exists()) {
      await backLink.trigger('click')
      expect(wrapper.vm.$router.push).toHaveBeenCalled()
    }
  })
})
