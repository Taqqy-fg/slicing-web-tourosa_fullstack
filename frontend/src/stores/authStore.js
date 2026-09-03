import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { apiClient } from '../api/client'

export const useAuthStore = defineStore('auth', () => {
    const token = ref(localStorage.getItem('auth_token') || sessionStorage.getItem('auth_token') || null)
    const user = ref(null)

    const isAuthenticated = computed(() => !!token.value)

    const permissions = computed(() => user.value?.permissions || [])
    const roles = computed(() => user.value?.roles || [])

    function hasPermission(name) {
        if (user.value?.is_superadmin) return true
        return permissions.value.includes(name)
    }

    function hasAnyPermission(names) {
        if (user.value?.is_superadmin) return true
        return names.some(n => permissions.value.includes(n))
    }

    function hasRole(name) {
        return roles.value.some(r => r.name === name)
    }

    async function login(email, password, rememberMe = true) {
        const data = await apiClient.post('/login', { email, password })
        token.value = data.token
        user.value = data.user
        const storage = rememberMe ? localStorage : sessionStorage
        storage.setItem('auth_token', data.token)
        return data
    }

    async function logout() {
        try {
            await apiClient.post('/logout')
        } catch {
            // token may already be invalid, proceed anyway
        }
        token.value = null
        user.value = null
        localStorage.removeItem('auth_token')
        sessionStorage.removeItem('auth_token')
    }

    async function fetchUser() {
        if (!token.value) return null
        try {
            const data = await apiClient.get('/me')
            user.value = data
            return data
        } catch {
            token.value = null
            user.value = null
            localStorage.removeItem('auth_token')
            sessionStorage.removeItem('auth_token')
            return null
        }
    }

    async function updateProfile(payload) {
        const data = await apiClient.put('/profile', payload)
        user.value = data.user
        return data
    }

    return { token, user, isAuthenticated, permissions, roles, hasPermission, hasAnyPermission, hasRole, login, logout, fetchUser, updateProfile }
})
