import { defineStore } from 'pinia'
import * as perpustakaanService from '@/services/perpustakaanService'
import { useAuthStore } from '@/stores/authStore'

export const usePerpustakaanStore = defineStore('perpustakaan', {
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
        const data = await perpustakaanService.getPerpustakaans()
        // Map backend fields to frontend fields
        this.items = data.map(item => ({
          id: item.id,
          name: item.name,
          isbn: item.isbn,
          tahunTerbit: item.publication_year ? item.publication_year.toString() : '',
          penulis: item.author,
          penerbit: item.publisher,
          kategori: item.category,
          jumlahStok: item.stock ? item.stock.toString() : '0',
          lokasiRak: item.rack_location,
          deskripsi: item.description,
          photo: item.foto ? `http://127.0.0.1:8000/storage/${item.foto}` : null,
          school_id: item.school_id,
          school_name: item.school ? item.school.name : null
        }))
      } catch (err) {
        console.error('Failed to fetch perpustakaan:', err)
        this.error = 'Gagal mengambil data perpustakaan'
      } finally {
        this.isLoading = false
      }
    },
    
    async add(item) {
      const auth = useAuthStore()
      
      const formData = new FormData()
      formData.append('school_id', auth.user.school_id)
      formData.append('name', item.name || '')
      if (item.isbn) formData.append('isbn', item.isbn)
      if (item.tahunTerbit) formData.append('publication_year', item.tahunTerbit)
      if (item.penulis) formData.append('author', item.penulis)
      if (item.penerbit) formData.append('publisher', item.penerbit)
      if (item.kategori) formData.append('category', item.kategori)
      if (item.jumlahStok) formData.append('stock', item.jumlahStok)
      if (item.lokasiRak) formData.append('rack_location', item.lokasiRak)
      if (item.deskripsi) formData.append('description', item.deskripsi)
      
      // If photo is a File object
      if (item.photo && item.photo instanceof File) {
        formData.append('foto', item.photo)
      }

      const response = await perpustakaanService.createPerpustakaan(formData)
      await this.fetchItems() // Refresh list
      return response
    },
    
    async update(id, updatedItem) {
      const formData = new FormData()
      
      formData.append('name', updatedItem.name || '')
      if (updatedItem.isbn) formData.append('isbn', updatedItem.isbn)
      if (updatedItem.tahunTerbit) formData.append('publication_year', updatedItem.tahunTerbit)
      if (updatedItem.penulis) formData.append('author', updatedItem.penulis)
      if (updatedItem.penerbit) formData.append('publisher', updatedItem.penerbit)
      if (updatedItem.kategori) formData.append('category', updatedItem.kategori)
      if (updatedItem.jumlahStok) formData.append('stock', updatedItem.jumlahStok)
      if (updatedItem.lokasiRak) formData.append('rack_location', updatedItem.lokasiRak)
      if (updatedItem.deskripsi) formData.append('description', updatedItem.deskripsi)
      
      // If photo is a File object, append it
      if (updatedItem.photo && updatedItem.photo instanceof File) {
        formData.append('foto', updatedItem.photo)
      }

      const response = await perpustakaanService.updatePerpustakaan(id, formData)
      await this.fetchItems() // Refresh list
      return response
    },
    
    async delete(id) {
      await perpustakaanService.deletePerpustakaan(id)
      this.items = this.items.filter(item => item.id !== id)
    },
    
    getById(id) {
      return this.items.find(item => String(item.id) === String(id))
    }
  }
})
