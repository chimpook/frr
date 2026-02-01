<template>
  <v-dialog v-model="dialog" max-width="500" persistent>
    <v-card>
      <v-card-title class="d-flex align-center">
        <v-icon color="warning" class="mr-2">mdi-alert</v-icon>
        {{ isDeleted ? 'Finding Deleted' : 'Edit Conflict' }}
      </v-card-title>

      <v-card-text>
        <template v-if="isDeleted">
          <p>
            This finding (<strong>{{ conflictData?.id }}</strong>) has been deleted by another user.
          </p>
          <p class="mt-2">Your unsaved changes will be lost.</p>
        </template>

        <template v-else>
          <p>
            This finding (<strong>{{ conflictData?.id }}</strong>) has been modified by another user
            while you were editing it.
          </p>
          <p class="mt-2">How would you like to proceed?</p>

          <v-expansion-panels v-if="conflictData" class="mt-4" variant="accordion">
            <v-expansion-panel title="View remote changes">
              <v-expansion-panel-text>
                <v-list density="compact">
                  <v-list-item>
                    <strong>Location:</strong> {{ conflictData.location }}
                  </v-list-item>
                  <v-list-item>
                    <strong>Risk Level:</strong> {{ conflictData.risk_range }}
                  </v-list-item>
                  <v-list-item>
                    <strong>Resolved:</strong> {{ conflictData.resolved ? 'Yes' : 'No' }}
                  </v-list-item>
                </v-list>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </template>
      </v-card-text>

      <v-card-actions>
        <v-spacer />

        <template v-if="isDeleted">
          <v-btn color="primary" variant="flat" @click="handleDiscard">
            Close
          </v-btn>
        </template>

        <template v-else>
          <v-btn variant="text" @click="handleDiscard">
            Discard My Changes
          </v-btn>
          <v-btn color="primary" variant="outlined" @click="handleReload">
            Reload & Continue
          </v-btn>
          <v-btn color="warning" variant="flat" @click="handleOverwrite">
            Overwrite
          </v-btn>
        </template>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { Finding } from '@/types/Finding';

interface Props {
  modelValue: boolean;
  conflictData: Finding | null;
  conflictType: 'updated' | 'deleted' | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  discard: [];
  reload: [data: Finding];
  overwrite: [];
}>();

const dialog = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const isDeleted = computed(() => props.conflictType === 'deleted');

function handleDiscard(): void {
  emit('discard');
  dialog.value = false;
}

function handleReload(): void {
  if (props.conflictData) {
    emit('reload', props.conflictData);
  }
  dialog.value = false;
}

function handleOverwrite(): void {
  emit('overwrite');
  dialog.value = false;
}
</script>
