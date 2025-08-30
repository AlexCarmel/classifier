import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import NewTicketModal from '@/components/NewTicketModal.vue'

// Basic test that actually matches the component
describe('NewTicketModal.vue', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mount(NewTicketModal, {
      props: {
        categories: [
          { id: '1', name: 'Technical Support' },
          { id: '2', name: 'Bug Report' }
        ]
      },
      global: {
        stubs: ['router-link']
      }
    })
  })

  afterEach(() => {
    wrapper?.unmount()
  })

  it('renders the modal structure', () => {
    expect(wrapper.find('.modal-overlay').exists()).toBe(true)
    expect(wrapper.find('.modal').exists()).toBe(true)
    expect(wrapper.find('.modal__header').exists()).toBe(true)
    expect(wrapper.find('.modal__title').text()).toBe('Create New Ticket')
  })

  it('renders form fields', () => {
    expect(wrapper.find('#subject').exists()).toBe(true)
    expect(wrapper.find('#body').exists()).toBe(true)
    expect(wrapper.find('#category').exists()).toBe(true)
    expect(wrapper.find('#status').exists()).toBe(true)
  })

  it('emits close when close button clicked', async () => {
    await wrapper.find('.modal__close').trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('emits close when overlay clicked', async () => {
    await wrapper.find('.modal-overlay').trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('populates category options', () => {
    const select = wrapper.find('#category')
    const options = select.findAll('option')
    
    // Should have default option + category options
    expect(options.length).toBeGreaterThanOrEqual(2)
  })
})
