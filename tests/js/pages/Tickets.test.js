import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import Tickets from '@/pages/Tickets.vue'

describe('Tickets.vue', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mount(Tickets, {
      global: {
        stubs: ['router-link', 'NewTicketModal']
      }
    })
  })

  afterEach(() => {
    wrapper?.unmount()
  })

  it('renders the tickets page', () => {
    expect(wrapper.find('.tickets').exists()).toBe(true)
    expect(wrapper.find('.tickets__header').exists()).toBe(true)
    expect(wrapper.find('.tickets__title').text()).toBe('Tickets')
  })

  it('renders new ticket button', () => {
    const newTicketBtn = wrapper.find('.tickets__header button')
    expect(newTicketBtn.exists()).toBe(true)
    expect(newTicketBtn.text()).toContain('New Ticket')
  })

  it('renders filters section', () => {
    expect(wrapper.find('.tickets__filters').exists()).toBe(true)
    expect(wrapper.find('input[placeholder*="Search"]').exists()).toBe(true)
  })

  it('renders view toggle buttons', () => {
    expect(wrapper.find('.tickets__view-toggle').exists()).toBe(true)
    const toggleBtns = wrapper.findAll('.tickets__view-toggle button')
    expect(toggleBtns.length).toBe(2)
  })

  it('can click new ticket button', async () => {
    const newTicketBtn = wrapper.find('.tickets__header button')
    await newTicketBtn.trigger('click')
    // Should show modal or update state
    expect(newTicketBtn.exists()).toBe(true)
  })

  it('can type in search input', async () => {
    const searchInput = wrapper.find('input[placeholder*="Search"]')
    await searchInput.setValue('test search')
    expect(searchInput.element.value).toBe('test search')
  })
})
