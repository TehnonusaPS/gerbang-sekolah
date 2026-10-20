import api from "./api"

export async function fetchAllSiswa(params) {
  const response = await api.get("/management/students", { params })
  return response.data
}

export async function createSiswa(data) {
  let formData
  if (data instanceof FormData) {
    formData = data
  } else {
    formData = new FormData()
    for (const key in data) {
      if (data[key] !== null && data[key] !== undefined) {
        if (key === "foto" && typeof data[key] === "string") {
          continue
        }
        formData.append(key, data[key])
      }
    }
  }
  const response = await api.post("/management/students", formData)
  return response.data
}

export async function getSiswaDetail(id) {
  const response = await api.get(`/management/students/${id}`)
  return response.data
}

export async function updateSiswa(id, data) {
  let formData
  if (data instanceof FormData) {
    formData = data
  } else {
    formData = new FormData()
    for (const key in data) {
      if (data[key] !== null && data[key] !== undefined) {
        if (key === "foto" && typeof data[key] === "string") {
          continue
        }
        formData.append(key, data[key])
      }
    }
  }
  // Spoof PUT method for multipart form-data in Laravel
  if (!formData.has("_method")) {
    formData.append("_method", "PUT")
  }
  const response = await api.post(`/management/students/${id}`, formData)
  return response.data
}

export async function deleteSiswa(id) {
  const response = await api.delete(`/management/students/${id}`)
  return response.data
}
