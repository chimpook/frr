<template>
  <v-dialog :model-value="modelValue" max-width="400" @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title class="text-h6">
        Confirm Delete
      </v-card-title>
      <v-card-text>
        <p>Are you sure you want to delete the user <strong>{{ user?.name }}</strong>?</p>
        <p class="text-caption text-grey mt-2">This action cannot be undone.</p>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" :disabled="loading" @click="$emit('update:modelValue', false)">
          Cancel
        </v-btn>
        <v-btn color="error" :loading="loading" @click="$emit('confirm')">
          Delete
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import type { User } from '@/types/Auth';

defineProps<{
  modelValue: boolean;
  user: User | null;
  loading: boolean;
}>();

defineEmits<{
  'update:modelValue': [value: boolean];
  confirm: [];
}>();
</script>
