<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-4">Доступные услуги</h1>

    <ul class="space-y-2">
      <li v-for="service in services" :key="service.id" class="p-4 bg-gray-100 rounded hover:bg-gray-200">
        <Link :href="route('services.show', service.id)">
          <div class="font-semibold">{{ service.name }}</div>
          <div class="text-sm text-gray-600">
            Длительность: {{ service.duration }} мин | {{ service.work_start }} - {{ service.work_end }}
          </div>
        </Link>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

defineProps({
  services: Array
})
</script>