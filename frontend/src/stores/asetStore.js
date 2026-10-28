import { defineStore } from 'pinia'
import * as asetService from '@/services/asetService'
import { useAuthStore } from '@/stores/authStore'

export const useAsetStore = defineStore('aset', {
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
        const data = await asetService.getAsets()
        this.items = data.map(item => ({
          id: item.id,
          code: item.code,
          name: item.name,
          category: item.category,
          brand: item.brand,
          building: item.building,
          floor: item.floor,
          room: item.room,
          condition: item.condition,
          date: item.date,
          value: item.value,
          photo: item.foto ? `http://127.0.0.1:8000/storage/${item.foto}` : null,
          school_id: item.school_id,
          school_name: item.school ? item.school.name : null
        }))
      } catch (err) {
        console.error('Failed to fetch aset:', err)
        this.error = 'Gagal mengambil data aset'
      } finally {
        this.isLoading = false
      }
    },
    
    async add(item) {
      const auth = useAuthStore()
      
      const formData = new FormData()
      formData.append('school_id', auth.user.school_id)
      formData.append('code', item.code || '')
      formData.append('name', item.name || '')
      if (item.category) formData.append('category', item.category)
      if (item.brand) formData.append('brand', item.brand)
      if (item.building) formData.append('building', item.building)
      if (item.floor) formData.append('floor', item.floor)
      if (item.room) formData.append('room', item.room)
      if (item.condition) formData.append('condition', item.condition)
      if (item.date) formData.append('date', item.date)
      if (item.value) formData.append('value', item.value)
      
      if (item.photo && item.photo instanceof File) {
        formData.append('foto', item.photo)
      }

      const response = await asetService.createAset(formData)
      await this.fetchItems()
      return response
    },
    
    async update(id, updatedItem) {
      const formData = new FormData()
      
      formData.append('code', updatedItem.code || '')
      formData.append('name', updatedItem.name || '')
      if (updatedItem.category) formData.append('category', updatedItem.category)
      if (updatedItem.brand) formData.append('brand', updatedItem.brand)
      if (updatedItem.building) formData.append('building', updatedItem.building)
      if (updatedItem.floor) formData.append('floor', updatedItem.floor)
      if (updatedItem.room) formData.append('room', updatedItem.room)
      if (updatedItem.condition) formData.append('condition', updatedItem.condition)
      if (updatedItem.date) formData.append('date', updatedItem.date)
      if (updatedItem.value) formData.append('value', updatedItem.value)
      
      if (updatedItem.photo && updatedItem.photo instanceof File) {
        formData.append('foto', updatedItem.photo)
      }

      const response = await asetService.updateAset(id, formData)
      await this.fetchItems()
      return response
    },
    
    async delete(id) {
      await asetService.deleteAset(id)
      this.items = this.items.filter(item => item.id !== id)
    },
    
    getById(id) {
      return this.items.find(item => String(item.id) === String(id))
    }
  }
})
