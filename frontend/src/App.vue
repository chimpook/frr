<template>
  <v-app>
    <v-app-bar color="primary" density="comfortable">
      <v-app-bar-title>
        <v-icon class="mr-2">mdi-fire</v-icon>
        Fire Risk Findings
      </v-app-bar-title>
    </v-app-bar>

    <v-main>
      <v-container fluid class="pa-4">
        <FindingsTable />
      </v-container>
    </v-main>

    <v-snackbar
      v-model="snackbar.show"
      :color="snackbar.color"
      :timeout="3000"
      location="bottom right"
    >
      {{ snackbar.message }}
      <template #actions>
        <v-btn variant="text" @click="snackbar.show = false">
          Close
        </v-btn>
      </template>
    </v-snackbar>
  </v-app>
</template>

<script setup lang="ts">
import { reactive, provide } from 'vue';
import FindingsTable from './components/FindingsTable.vue';

interface Snackbar {
  show: boolean;
  message: string;
  color: string;
}

const snackbar = reactive<Snackbar>({
  show: false,
  message: '',
  color: 'success',
});

const showSnackbar = (message: string, color: string = 'success') => {
  snackbar.message = message;
  snackbar.color = color;
  snackbar.show = true;
};

provide('showSnackbar', showSnackbar);
</script>

<style>
html {
  overflow-y: auto !important;
}
</style>
