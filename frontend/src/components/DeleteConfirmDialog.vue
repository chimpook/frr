<template>
  <v-dialog
    :model-value="modelValue"
    max-width="400"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="text-h5 bg-error text-white pa-4">
        <v-icon class="mr-2">mdi-alert</v-icon>
        Confirm Delete
      </v-card-title>

      <v-card-text class="pa-4">
        <p class="text-body-1">
          Are you sure you want to delete finding <strong>{{ finding?.id }}</strong>?
        </p>
        <p class="text-body-2 text-grey mt-2">
          Location: {{ finding?.location }}
        </p>
        <v-alert type="warning" variant="tonal" class="mt-4">
          This action cannot be undone.
        </v-alert>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4">
        <v-spacer></v-spacer>
        <v-btn variant="text" @click="handleCancel">
          Cancel
        </v-btn>
        <v-btn
          color="error"
          :loading="loading"
          @click="handleConfirm"
        >
          Delete
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { Finding } from '@/types/Finding';

interface Props {
  modelValue: boolean;
  finding: Finding | null;
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void;
  (e: 'confirm'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const loading = ref(false);

function handleCancel() {
  emit('update:modelValue', false);
}

function handleConfirm() {
  loading.value = true;
  emit('confirm');
  loading.value = false;
}
</script>
