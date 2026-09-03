<script setup>
import PageLoader from './components/PageLoader.vue'
import ToastContainer from './components/ToastContainer.vue'
</script>

<template>
  <PageLoader />
  <ToastContainer />
  <router-view />
</template>
