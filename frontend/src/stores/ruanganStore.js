import { defineStore } from 'pinia'
import * as ruanganService from '@/services/ruanganService'
import { useAuthStore } from '@/stores/authStore'

export const useRuanganStore = defineStore('ruangan', {
  state: () => ({
    items: [],
    isLoading: false,
    error: null,
  }),
  actions: {
    async fetchItems() {
      this.isLoading = true
      this.error = null
      try {
        const data = await ruanganService.getRuangans()
        this.items = data.map(item => ({
          id: item.id,
          code: item.code,
          name: item.name,
          category: item.category,
          building: item.building,
          capacity: item.capacity,
          area: item.area,
          facilities: item.facilities || [],
          school_id: item.school_id,
          school_name: item.school ? item.school.name : null,
          foto: item.foto ? `http://127.0.0.1:8000/storage/${item.foto}` : null,
        }))
      } catch (err) {
        console.error('Failed to fetch ruangan:', err)
        this.error = 'Gagal mengambil data ruangan'
      } finally {
        this.isLoading = false
      }
    },
    
    async add(item) {
      const auth = useAuthStore()
      
      const payload = {
        school_id: auth.user.school_id,
        code: item.code || '',
        name: item.name || '',
        category: item.category || '',
        building: item.building || '',
        capacity: item.capacity || 0,
        area: item.area || 0,
        facilities: item.facilities || []
      }

      const response = await ruanganService.createRuangan(payload)
      await this.fetchItems()
      return response
    },
    
    async update(id, updatedItem) {
      const payload = {
        code: updatedItem.code || '',
        name: updatedItem.name || '',
        category: updatedItem.category || '',
        building: updatedItem.building || '',
        capacity: updatedItem.capacity || 0,
        area: updatedItem.area || 0,
        facilities: updatedItem.facilities || []
      }

      const response = await ruanganService.updateRuangan(id, payload)
      await this.fetchItems()
      return response
    },
    
    async delete(id) {
      await ruanganService.deleteRuangan(id)
      this.items = this.items.filter(item => item.id !== id)
    },
    
    getById(id) {
      return this.items.find(item => String(item.id) === String(id))
    }
  }
})
