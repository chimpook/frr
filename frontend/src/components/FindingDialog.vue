<template>
  <v-dialog
    :model-value="modelValue"
    max-width="600"
    persistent
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <v-card>
      <v-card-title class="text-h5 bg-primary text-white pa-4">
        {{ mode === 'create' ? 'Add New Finding' : 'Edit Finding' }}
      </v-card-title>

      <v-card-text class="pa-4">
        <v-form ref="formRef" v-model="valid" @submit.prevent="handleSubmit">
          <v-text-field
            v-model="form.location"
            label="Location"
            :rules="[rules.required]"
            class="mb-3"
          ></v-text-field>

          <v-select
            v-model="form.risk_range"
            label="Risk Level"
            :items="riskLevels"
            :rules="[rules.required]"
            class="mb-3"
          >
            <template #item="{ item, props }">
              <v-list-item v-bind="props">
                <template #prepend>
                  <v-chip :color="getRiskColor(item.value)" size="small" label class="mr-2">
                    {{ item.value }}
                  </v-chip>
                </template>
              </v-list-item>
            </template>
          </v-select>

          <v-textarea
            v-model="form.comment"
            label="Comment"
            :rules="[rules.required]"
            rows="3"
            class="mb-3"
          ></v-textarea>

          <v-textarea
            v-model="form.recommendations"
            label="Recommendations"
            :rules="[rules.required]"
            rows="3"
            class="mb-3"
          ></v-textarea>

          <v-checkbox
            v-model="form.resolved"
            label="Resolved"
            color="success"
            hide-details
          ></v-checkbox>
        </v-form>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4">
        <v-spacer></v-spacer>
        <v-btn variant="text" @click="handleCancel">
          Cancel
        </v-btn>
        <v-btn
          color="primary"
          :disabled="!valid"
          :loading="loading"
          @click="handleSubmit"
        >
          {{ mode === 'create' ? 'Create' : 'Save' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import type { Finding, RiskRange } from '@/types/Finding';

interface Props {
  modelValue: boolean;
  finding: Finding | null;
  mode: 'create' | 'edit';
}

interface Emits {
  (e: 'update:modelValue', value: boolean): void;
  (e: 'save', data: any): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const formRef = ref();
const valid = ref(false);
const loading = ref(false);

const riskLevels: RiskRange[] = ['Low', 'Medium', 'High'];

const form = ref({
  location: '',
  risk_range: 'Medium' as RiskRange,
  comment: '',
  recommendations: '',
  resolved: false,
});

const rules = {
  required: (value: string) => !!value || 'This field is required',
};

function getRiskColor(risk: string): string {
  switch (risk) {
    case 'High':
      return 'error';
    case 'Medium':
      return 'warning';
    case 'Low':
      return 'success';
    default:
      return 'grey';
  }
}

function resetForm() {
  form.value = {
    location: '',
    risk_range: 'Medium',
    comment: '',
    recommendations: '',
    resolved: false,
  };
}

function populateForm(finding: Finding) {
  form.value = {
    location: finding.location,
    risk_range: finding.risk_range,
    comment: finding.comment,
    recommendations: finding.recommendations,
    resolved: finding.resolved,
  };
}

watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal) {
      if (props.mode === 'edit' && props.finding) {
        populateForm(props.finding);
      } else {
        resetForm();
      }
    }
  }
);

watch(
  () => props.finding,
  (newVal) => {
    if (props.modelValue && props.mode === 'edit' && newVal) {
      populateForm(newVal);
    }
  }
);

function handleCancel() {
  emit('update:modelValue', false);
}

async function handleSubmit() {
  if (!valid.value) return;

  loading.value = true;
  try {
    emit('save', { ...form.value });
  } finally {
    loading.value = false;
  }
}
</script>
