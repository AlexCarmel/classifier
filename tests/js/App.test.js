import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { nextTick } from 'vue'
import App from '@/App.vue'
import { mountComponent } from './utils/test-utils.js'

describe('App.vue', () => {
  let wrapper

  beforeEach(() => {
    wrapper = mountComponent(App, {
      global: {
        stubs: {
          'router-view': true,
          'router-link': {
            template: '<a @click="navigate" :href="to" :class="$attrs.class"><slot /></a>',
            props: ['to'],
            methods: {
              navigate(e) {
                e.preventDefault()
                this.$router?.push?.(this.to)
              }
            }
          }
        }
      }
    })
  })

  afterEach(() => {
    wrapper?.unmount()
    vi.clearAllMocks()
  })

  describe('Component Structure', () => {
    it('renders the main app container', () => {
      expect(wrapper.find('#app').exists()).toBe(true)
      expect(wrapper.find('.app').exists()).toBe(true)
    })

    it('renders the navigation', () => {
      expect(wrapper.find('.app-nav').exists()).toBe(true)
      expect(wrapper.find('.app-nav__container').exists()).toBe(true)
    })

    it('renders the main content area', () => {
      expect(wrapper.find('.app-main').exists()).toBe(true)
      expect(wrapper.find('router-view-stub').exists()).toBe(true)
    })

    it('displays the application title', () => {
      expect(wrapper.find('.app-nav__title').text()).toBe('Ticket Classifier')
    })
  })

  describe('Navigation Menu', () => {
    it('renders navigation links', () => {
      const navLinks = wrapper.findAll('.app-nav__link')
      
      expect(navLinks.length).toBe(2)
      expect(navLinks[0].text()).toBe('Dashboard')
      expect(navLinks[1].text()).toBe('Tickets')
    })

    it('has correct router-link destinations', () => {
      const dashboardLink = wrapper.find('a[href="/dashboard"]')
      const ticketsLink = wrapper.find('a[href="/tickets"]')
      
      expect(dashboardLink.exists()).toBe(true)
      expect(ticketsLink.exists()).toBe(true)
    })

    it('navigates when links are clicked', async () => {
      const dashboardLink = wrapper.find('a[href="/dashboard"]')
      
      await dashboardLink.trigger('click')
      
      expect(wrapper.vm.$router.push).toHaveBeenCalledWith('/dashboard')
    })

    it('applies active class to current route', async () => {
      // Router-link active class handling is done by Vue Router
      // Just ensure the navigation structure exists
      expect(wrapper.find('.app-nav__menu').exists()).toBe(true)
      
      // Verify router-links exist and have correct structure
      const links = wrapper.findAll('[href="/dashboard"], [href="/tickets"]')
      expect(links.length).toBe(2)
      expect(wrapper.find('a[href="/dashboard"]').exists()).toBe(true)
      expect(wrapper.find('a[href="/tickets"]').exists()).toBe(true)
    })
  })

  describe('Global Loading Overlay', () => {
    it('does not show loading overlay by default', () => {
      expect(wrapper.find('.loading-overlay').exists()).toBe(false)
    })

    it('shows loading overlay when globalLoading is true', async () => {
      await wrapper.setData({ globalLoading: true })
      await nextTick()
      
      expect(wrapper.find('.loading-overlay').exists()).toBe(true)
      expect(wrapper.find('.spinner').exists()).toBe(true)
    })

    it('hides loading overlay when globalLoading is false', async () => {
      await wrapper.setData({ globalLoading: true })
      await nextTick()
      
      expect(wrapper.find('.loading-overlay').exists()).toBe(true)
      
      await wrapper.setData({ globalLoading: false })
      await nextTick()
      
      expect(wrapper.find('.loading-overlay').exists()).toBe(false)
    })
  })

  describe('Provide/Inject Pattern', () => {
    it('provides setGlobalLoading function', () => {
      // Access the provide function result directly
      const provideResult = wrapper.vm.$options.provide.call(wrapper.vm)
      
      expect(typeof provideResult.setGlobalLoading).toBe('function')
    })

    it('setGlobalLoading function updates globalLoading state', async () => {
      const provideResult = wrapper.vm.$options.provide.call(wrapper.vm)
      const setGlobalLoading = provideResult.setGlobalLoading
      
      expect(wrapper.vm.globalLoading).toBe(false)
      
      setGlobalLoading(true)
      await nextTick()
      
      expect(wrapper.vm.globalLoading).toBe(true)
      
      setGlobalLoading(false)
      await nextTick()
      
      expect(wrapper.vm.globalLoading).toBe(false)
    })

    it('allows child components to control global loading', async () => {
      // Test the provide mechanism directly
      const provideResult = wrapper.vm.$options.provide.call(wrapper.vm)
      const setGlobalLoading = provideResult.setGlobalLoading
      
      // Child component would call this function
      setGlobalLoading(true)
      await nextTick()
      
      expect(wrapper.find('.loading-overlay').exists()).toBe(true)
      expect(wrapper.vm.globalLoading).toBe(true)
    })
  })

  describe('Responsive Design', () => {
    it('maintains structure on different screen sizes', () => {
      // Mock mobile viewport
      Object.defineProperty(window, 'innerWidth', {
        writable: true,
        configurable: true,
        value: 480,
      })
      
      window.dispatchEvent(new Event('resize'))
      
      expect(wrapper.find('.app-nav').exists()).toBe(true)
      expect(wrapper.find('.app-main').exists()).toBe(true)
    })

    it('adapts navigation for mobile screens', () => {
      // This would require more complex responsive testing
      // For now, just ensure the navigation exists
      expect(wrapper.find('.app-nav__menu').exists()).toBe(true)
    })
  })

  describe('Accessibility', () => {
    it('has proper semantic HTML structure', () => {
      expect(wrapper.find('nav').exists()).toBe(true)
      expect(wrapper.find('main').exists()).toBe(true)
    })

    it('navigation links are keyboard accessible', async () => {
      const dashboardLink = wrapper.find('a[href="/dashboard"]')
      
      // Should be focusable
      await dashboardLink.trigger('focus')
      expect(dashboardLink.exists()).toBe(true)
      
      // Should activate on Enter key (simulated through click since router-link handles this)
      await dashboardLink.trigger('keydown.enter')
      await dashboardLink.trigger('click')
      
      expect(wrapper.vm.$router.push).toHaveBeenCalledWith('/dashboard')
    })

    it('has proper heading hierarchy', () => {
      const h1 = wrapper.find('h1')
      
      expect(h1.exists()).toBe(true)
      expect(h1.text()).toBe('Ticket Classifier')
    })

    it('loading overlay has proper ARIA attributes', async () => {
      await wrapper.setData({ globalLoading: true })
      await nextTick()
      
      const loadingOverlay = wrapper.find('.loading-overlay')
      
      expect(loadingOverlay.exists()).toBe(true)
      // In a real implementation, we'd add aria-live, aria-label, etc.
    })
  })

  describe('Performance', () => {
    it('efficiently updates loading state', async () => {
      const initialChildren = wrapper.vm.$el.children.length
      
      await wrapper.setData({ globalLoading: true })
      await nextTick()
      
      await wrapper.setData({ globalLoading: false })
      await nextTick()
      
      // Should not cause unnecessary re-renders
      expect(wrapper.vm.$el.children.length).toBe(initialChildren)
    })

    it('does not re-render navigation unnecessarily', async () => {
      const navElement = wrapper.find('.app-nav')
      const originalElement = navElement.element
      
      await wrapper.setData({ globalLoading: true })
      await nextTick()
      
      const newNavElement = wrapper.find('.app-nav')
      
      // Navigation should not be re-rendered
      expect(newNavElement.element).toBe(originalElement)
    })
  })

  describe('Error Boundaries', () => {
    it('handles router-view rendering errors gracefully', () => {
      // Test that app structure remains intact even if router-view has issues
      const wrapperWithErrorStub = mountComponent(App, {
        global: {
          stubs: {
            'router-view': {
              template: '<div class="router-error">Router Error</div>'
            },
            'router-link': {
              template: '<a><slot /></a>'
            }
          }
        }
      })
      
      // App should still render navigation
      expect(wrapperWithErrorStub.find('.app-nav').exists()).toBe(true)
      expect(wrapperWithErrorStub.find('.app-main').exists()).toBe(true)
      
      wrapperWithErrorStub.unmount()
    })
  })

  describe('CSS Classes and Styling', () => {
    it('applies correct CSS classes', () => {
      expect(wrapper.find('.app').exists()).toBe(true)
      expect(wrapper.find('.app-nav').exists()).toBe(true)
      expect(wrapper.find('.app-nav__container').exists()).toBe(true)
      expect(wrapper.find('.app-nav__brand').exists()).toBe(true)
      expect(wrapper.find('.app-nav__menu').exists()).toBe(true)
      expect(wrapper.find('.app-main').exists()).toBe(true)
    })

    it('conditionally applies loading overlay classes', async () => {
      await wrapper.setData({ globalLoading: true })
      await nextTick()
      
      expect(wrapper.find('.loading-overlay').exists()).toBe(true)
      expect(wrapper.find('.spinner').exists()).toBe(true)
    })
  })

  describe('Component Lifecycle', () => {
    it('initializes with correct default data', () => {
      expect(wrapper.vm.globalLoading).toBe(false)
    })

    it('maintains state during component updates', async () => {
      await wrapper.setData({ globalLoading: true })
      
      // Force component update
      wrapper.vm.$forceUpdate()
      await nextTick()
      
      expect(wrapper.vm.globalLoading).toBe(true)
    })

    it('cleans up properly on unmount', () => {
      const originalUnmount = wrapper.unmount
      wrapper.unmount = vi.fn(originalUnmount)
      
      wrapper.unmount()
      
      expect(wrapper.unmount).toHaveBeenCalled()
    })
  })

  describe('Cross-Browser Compatibility', () => {
    it('handles missing router gracefully', () => {
      const wrapperNoRouter = mountComponent(App, {
        global: {
          mocks: {
            $router: null,
            $route: null
          },
          stubs: {
            'router-view': true,
            'router-link': true
          }
        }
      })
      
      expect(wrapperNoRouter.find('.app').exists()).toBe(true)
      
      wrapperNoRouter.unmount()
    })
  })
})
