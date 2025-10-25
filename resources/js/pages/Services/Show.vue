<template>
  <div class="p-6 relative">
    <Link href="/services" class="text-blue-500 hover:underline mb-4 block">← Назад</Link>

    <h1 class="text-2xl font-bold mb-2">{{ service.name }}</h1>
    <p class="text-gray-600 mb-4">
      Время работы: {{ service.work_start }} - {{ service.work_end }}
    </p>

    <!-- Форма клиента -->
    <div class="mb-6">
      <label class="block mb-2 font-medium">Ваше имя</label>
      <input
        v-model="name"
        type="text"
        class="w-full border rounded p-2 mb-4"
        placeholder="Введите имя"
      />

      <label class="block mb-2 font-medium">Телефон</label>
      <input
        v-model="phone"
        type="text"
        class="w-full border rounded p-2"
        placeholder="+7 (999) 123-45-67"
      />
    </div>

    <!-- Выбор даты -->
    <div class="grid grid-cols-6 gap-2 mb-4">
      <button
        v-for="day in weekDays"
        :key="day.date"
        @click="selectDate(day.date)"
        :class="[
          'p-2 rounded transition-colors text-center',
          selectedDate === day.date
            ? 'bg-blue-500 text-white shadow-md'
            : 'bg-gray-100 hover:bg-gray-200'
        ]"
      >
        {{ day.label }}
      </button>
    </div>

    <!-- Слоты -->
    <div v-if="filteredSlots.length">
      <h2 class="text-lg font-semibold mb-2">Доступные слоты (10:00–20:00 МСК)</h2>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="slot in filteredSlots"
          :key="slot"
          class="px-3 py-2 border rounded hover:bg-blue-100 transition"
          @click="bookSlot(slot)"
        >
          {{ slot }}
        </button>
      </div>
    </div>

    <div v-else class="text-gray-500">Выберите день, чтобы увидеть слоты</div>

    <!-- Модальное окно -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white p-6 rounded shadow-lg text-center">
        <h3 class="text-lg font-bold mb-3 text-green-600">✅ Запись успешно создана!</h3>
        <p class="text-gray-600">Скоро вы будете перенаправлены на главную страницу.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  service: Object
})

const service = props.service

const name = ref('')
const phone = ref('')
const slots = ref([])
const selectedDate = ref(null)
const showModal = ref(false)

const today = new Date()

// создаём неделю, исключая воскресенье
const weekDays = Array.from({ length: 7 }, (_, i) => {
  const date = new Date(today)
  date.setDate(today.getDate() + i)
  return date
})
  .filter(d => d.getDay() !== 0)
  .map(date => ({
    date: date.toISOString().split('T')[0],
    label: date.toLocaleDateString('ru-RU', { weekday: 'short', day: 'numeric' })
  }))

const fetchSlots = async (date) => {
  try {
    const response = await axios.get(`/services/${service.id}/available-slots`, {
      params: { date }
    })
    slots.value = Array.isArray(response.data)
      ? response.data
      : response.data.slots || []
  } catch (e) {
    console.error('Ошибка загрузки слотов', e)
    slots.value = []
  }
}

function selectDate(date) {
  selectedDate.value = date
  fetchSlots(date)
}

// Фильтруем только допустимое время (10:00–20:00 МСК)
const filteredSlots = computed(() => {
  const start = 10
  const end = 20
  return slots.value.filter(slot => {
    const hour = parseInt(slot.split(':')[0])
    return hour >= start && hour < end
  })
})

const bookSlot = async (slot) => {
  if (!selectedDate.value) return alert('Выберите дату')
  if (!name.value.trim() || !phone.value.trim())
    return alert('Введите имя и телефон перед бронированием')

  try {
    await axios.post(`/services/${service.id}/bookings`, {
      name: name.value,
      phone: phone.value,
      date: selectedDate.value,
      start_time: slot,
    })

    showModal.value = true
    setTimeout(() => {
      showModal.value = false
      router.visit('/services') // редирект на начальный экран
    }, 2000)
  } catch (error) {
    console.error(error)
    alert(error.response?.data?.message || 'Ошибка при создании записи')
  }
}
</script>

<style scoped>
/* Чтобы модальное окно было поверх всех элементов */
body.modal-open {
  overflow: hidden;
}
</style>
