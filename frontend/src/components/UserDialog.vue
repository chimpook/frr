<template>
  <v-dialog :model-value="modelValue" max-width="500" persistent @update:model-value="$emit('update:modelValue', $event)">
    <v-card>
      <v-card-title>
        {{ isEditing ? 'Edit User' : 'Create User' }}
      </v-card-title>
      <v-card-text>
        <v-form ref="formRef">
          <v-text-field
            v-model="form.name"
            label="Name"
            :rules="nameRules"
            :disabled="loading"
            required
          />
          <v-text-field
            v-model="form.email"
            label="Email"
            type="email"
            :rules="emailRules"
            :disabled="loading"
            required
          />
          <v-text-field
            v-model="form.password"
            label="Password"
            :type="showPassword ? 'text' : 'password'"
            :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
            :rules="isEditing ? [] : passwordRules"
            :placeholder="isEditing ? 'Leave empty to keep current password' : ''"
            :disabled="loading"
            :required="!isEditing"
            @click:append="showPassword = !showPassword"
          />
          <v-select
            v-model="form.role"
            label="Role"
            :items="roleOptions"
            item-title="title"
            item-value="value"
            :disabled="loading"
          />
          <v-switch
            v-model="form.active"
            label="Active"
            color="primary"
            :disabled="loading"
          />
        </v-form>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn variant="text" :disabled="loading" @click="handleClose">
          Cancel
        </v-btn>
        <v-btn color="primary" :loading="loading" @click="handleSave">
          {{ isEditing ? 'Update' : 'Create' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import type { User, UserCreate, UserUpdate } from '@/types/Auth';

const props = defineProps<{
  modelValue: boolean;
  user: User | null;
  loading: boolean;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
  save: [data: UserCreate | UserUpdate];
}>();

const formRef = ref();
const showPassword = ref(false);

const form = ref({
  name: '',
  email: '',
  password: '',
  role: 'user',
  active: true,
});

const isEditing = computed(() => !!props.user?.id);

const roleOptions = [
  { title: 'User', value: 'user' },
  { title: 'Admin', value: 'admin' },
];

const nameRules = [
  (v: string) => !!v || 'Name is required',
  (v: string) => (v && v.length >= 2) || 'Name must be at least 2 characters',
];

const emailRules = [
  (v: string) => !!v || 'Email is required',
  (v: string) => /.+@.+\..+/.test(v) || 'Email must be valid',
];

const passwordRules = [
  (v: string) => !!v || 'Password is required',
  (v: string) => (v && v.length >= 6) || 'Password must be at least 6 characters',
];

watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal) {
      if (props.user) {
        form.value = {
          name: props.user.name,
          email: props.user.email,
          password: '',
          role: props.user.roles.includes('ROLE_ADMIN') ? 'admin' : 'user',
          active: props.user.active,
        };
      } else {
        form.value = {
          name: '',
          email: '',
          password: '',
          role: 'user',
          active: true,
        };
      }
      showPassword.value = false;
    }
  }
);

function handleClose() {
  emit('update:modelValue', false);
}

async function handleSave() {
  const { valid } = await formRef.value.validate();
  if (!valid) return;

  const data: UserCreate | UserUpdate = {
    name: form.value.name,
    email: form.value.email,
    roles: form.value.role === 'admin' ? ['ROLE_ADMIN'] : ['ROLE_USER'],
    active: form.value.active,
  };

  if (form.value.password) {
    data.password = form.value.password;
  }

  emit('save', data);
}
</script>
